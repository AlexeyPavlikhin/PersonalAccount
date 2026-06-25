<?php

/**
 * Универсальный кэш полей формы документов (требования п. 21–25).
 * Ключ привязки и перечень кэшируемых полей задаются в document_templates.
 * Повторно уже сохранённые значения не записываются; новая строка — только при появлении новых данных.
 */

require_once __DIR__ . '/bank_account_validation.php';

/**
 * Список кодов полей для кэширования из JSON-конфигурации шаблона.
 *
 * @return string[]
 */
function msll_document_decode_cache_fields($cache_fields_json): array
{
    if (!is_string($cache_fields_json) || trim($cache_fields_json) === '') {
        return array();
    }

    $decoded = json_decode($cache_fields_json, true);
    if (!is_array($decoded)) {
        return array();
    }

    $result = array();
    foreach ($decoded as $field_code) {
        $normalized = trim((string) $field_code);
        if ($normalized !== '') {
            $result[] = $normalized;
        }
    }

    return array_values(array_unique($result));
}

/**
 * Нормализация значения ключа кэша (ИНН и аналоги — только цифры).
 */
function msll_document_normalize_cache_key_value(string $cache_key_field, string $cache_key_value): string
{
    $field = trim($cache_key_field);
    $value = trim($cache_key_value);

    if ($field === 'inn') {
        return msll_digits_only($value);
    }

    return $value;
}

/**
 * Стабильная сериализация payload для сравнения и хранения.
 */
function msll_document_encode_cache_payload(array $cached_payload): string
{
    ksort($cached_payload);

    $json = json_encode($cached_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $json === false ? '' : $json;
}

/**
 * Все пары поле→значение из payload уже есть в кэше для данного ключа.
 */
function msll_document_cache_payload_is_fully_stored(
    PDO $connection,
    string $cache_key_field,
    string $cache_key_value,
    array $cached_payload,
    array $cache_field_codes
): bool {
    if (count($cached_payload) === 0) {
        return true;
    }

    $restore_options = msll_document_collect_cache_restore_options(
        $connection,
        $cache_key_field,
        $cache_key_value,
        $cache_field_codes
    );

    foreach ($cached_payload as $field_code => $value) {
        $bucket = $restore_options[$field_code] ?? null;
        if (!is_array($bucket) || !is_array($bucket['options'] ?? null)) {
            return false;
        }

        $already_stored = false;
        foreach ($bucket['options'] as $option) {
            if (trim((string) ($option['value'] ?? '')) === $value) {
                $already_stored = true;
                break;
            }
        }

        if (!$already_stored) {
            return false;
        }
    }

    return true;
}

/**
 * Точное совпадение JSON-снимка с уже сохранённой записью.
 */
function msll_document_cache_snapshot_exists(
    PDO $connection,
    string $cache_key_field,
    string $cache_key_value,
    string $cached_data_json
): bool {
    if ($cached_data_json === '') {
        return true;
    }

    $query = $connection->prepare("
        SELECT 1
        FROM document_field_cache
        WHERE cache_key_field = :cache_key_field
          AND cache_key_value = :cache_key_value
          AND cached_data_json = :cached_data_json
        LIMIT 1
    ");
    $query->bindParam(':cache_key_field', $cache_key_field, PDO::PARAM_STR);
    $query->bindParam(':cache_key_value', $cache_key_value, PDO::PARAM_STR);
    $query->bindParam(':cached_data_json', $cached_data_json, PDO::PARAM_STR);
    $query->execute();

    return (bool) $query->fetchColumn();
}

/**
 * Сохранение кэшируемых полей после успешной генерации DOCX (без дубликатов).
 */
function msll_document_save_field_cache_on_generation(PDO $connection, array $template, array $form_data): void
{    $cache_key_field = trim((string) ($template['cache_key_field'] ?? ''));
    $cache_field_codes = msll_document_decode_cache_fields($template['cache_fields_json'] ?? '');

    if ($cache_key_field === '' || count($cache_field_codes) === 0) {
        return;
    }

    $cache_key_value = msll_document_normalize_cache_key_value(
        $cache_key_field,
        trim((string) ($form_data[$cache_key_field] ?? ''))
    );

    if ($cache_key_value === '') {
        return;
    }

    if ($cache_key_field === 'inn' && !msll_is_valid_inn($cache_key_value)) {
        return;
    }

    $cached_payload = array();
    foreach ($cache_field_codes as $field_code) {
        $raw_value = trim((string) ($form_data[$field_code] ?? ''));
        if ($raw_value === '') {
            continue;
        }

        if ($field_code === 'bik') {
            $raw_value = msll_digits_only($raw_value);
            if (!msll_is_valid_bik($raw_value)) {
                continue;
            }
        }

        if ($field_code === 'checking_account') {
            $bik = msll_digits_only(trim((string) ($form_data['bik'] ?? '')));
            $raw_value = msll_digits_only($raw_value);
            if ($bik === '' || !msll_is_valid_checking_account($raw_value, $bik)) {
                continue;
            }
        }

        $cached_payload[$field_code] = $raw_value;
    }

    if (count($cached_payload) === 0) {
        return;
    }

    if (msll_document_cache_payload_is_fully_stored(
        $connection,
        $cache_key_field,
        $cache_key_value,
        $cached_payload,
        $cache_field_codes
    )) {
        return;
    }

    $cached_data_json = msll_document_encode_cache_payload($cached_payload);
    if ($cached_data_json === '') {
        return;
    }

    if (msll_document_cache_snapshot_exists($connection, $cache_key_field, $cache_key_value, $cached_data_json)) {
        return;
    }

    $query = $connection->prepare("
        INSERT INTO document_field_cache (cache_key_field, cache_key_value, cached_data_json)
        VALUES (:cache_key_field, :cache_key_value, :cached_data_json)
    ");
    $query->bindParam(':cache_key_field', $cache_key_field, PDO::PARAM_STR);
    $query->bindParam(':cache_key_value', $cache_key_value, PDO::PARAM_STR);
    $query->bindParam(':cached_data_json', $cached_data_json, PDO::PARAM_STR);
    $query->execute();
}

/**
 * Варианты восстановления по каждому кэшируемому полю (последние записи — первыми).
 *
 * @return array<string, array{options: array<int, array{cache_id:int, value:string, saved_at:string}>, latest: string}>
 */
function msll_document_collect_cache_restore_options(
    PDO $connection,
    string $cache_key_field,
    string $cache_key_value,
    array $cache_field_codes
): array {
    $normalized_key_field = trim($cache_key_field);
    $normalized_key_value = msll_document_normalize_cache_key_value($cache_key_field, $cache_key_value);

    if ($normalized_key_field === '' || $normalized_key_value === '' || count($cache_field_codes) === 0) {
        return array();
    }

    $query = $connection->prepare("
        SELECT cache_id, cached_data_json, updated_at
        FROM document_field_cache
        WHERE cache_key_field = :cache_key_field AND cache_key_value = :cache_key_value
        ORDER BY updated_at DESC, cache_id DESC
    ");
    $query->bindParam(':cache_key_field', $normalized_key_field, PDO::PARAM_STR);
    $query->bindParam(':cache_key_value', $normalized_key_value, PDO::PARAM_STR);
    $query->execute();

    $rows = $query->fetchAll(PDO::FETCH_ASSOC);
    $seen_values = array();
    $result = array();

    foreach ($cache_field_codes as $field_code) {
        $result[$field_code] = array(
            'options' => array(),
            'latest' => '',
        );
        $seen_values[$field_code] = array();
    }

    foreach ($rows as $row) {
        $payload = json_decode((string) ($row['cached_data_json'] ?? ''), true);
        if (!is_array($payload)) {
            continue;
        }

        $cache_id = (int) ($row['cache_id'] ?? 0);
        $saved_at = trim((string) ($row['updated_at'] ?? ''));

        foreach ($cache_field_codes as $field_code) {
            if (!array_key_exists($field_code, $payload)) {
                continue;
            }

            $value = trim((string) $payload[$field_code]);
            if ($value === '' || isset($seen_values[$field_code][$value])) {
                continue;
            }

            $seen_values[$field_code][$value] = true;
            $result[$field_code]['options'][] = array(
                'cache_id' => $cache_id,
                'value' => $value,
                'saved_at' => $saved_at,
            );

            if ($result[$field_code]['latest'] === '') {
                $result[$field_code]['latest'] = $value;
            }
        }
    }

    return $result;
}

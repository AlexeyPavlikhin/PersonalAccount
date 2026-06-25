<?php

/**
 * Валидация form_data, маппинг в плейсхолдеры DOCX (field_map_json) и имя файла скачивания.
 *
 * field_map_json — только особые правила (переименование, несколько плейсхолдеров из одного поля).
 * По умолчанию в DOCX передаются все ключи form_data после enrich с теми же именами.
 */

require_once __DIR__ . '/bank_account_validation.php';

/** field_map_json / filter_tags_json из БД → ассоциативный массив. */
function msll_document_decode_json_map($value): array
{
    if (is_array($value)) {
        return $value;
    }

    $json = trim((string) $value);
    if ($json === '') {
        return array();
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : array();
}

function msll_document_normalize_scalar($value): string
{
    if (is_array($value) || is_object($value)) {
        return '';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    return trim((string) $value);
}

/**
 * Сверка ввода с document_template_fields; field_errors — для подсветки полей на форме.
 */
function msll_document_prepare_form_data(array $fields, array $input_data): array
{
    $normalized_values = array();
    $errors = array();
    $field_errors = array();

    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }

        $field_code = trim((string) ($field['field_code'] ?? ''));
        if ($field_code === '') {
            continue;
        }

        $default_value = msll_document_normalize_scalar($field['default_value'] ?? '');
        $field_type = strtolower(trim((string) ($field['field_type'] ?? 'text')));
        if (array_key_exists($field_code, $input_data) && $field_type === 'table' && is_array($input_data[$field_code])) {
            $normalized_values[$field_code] = $input_data[$field_code];
            continue;
        }

        $value = array_key_exists($field_code, $input_data)
            ? msll_document_normalize_scalar($input_data[$field_code])
            : $default_value;

        $normalized_values[$field_code] = $value;
    }

    // производные поля (signer_name_genitive и др.) могут прийти с фронта после дозаполнения
    foreach ($input_data as $key => $value) {
        $field_code = trim((string) $key);
        if ($field_code === '') {
            continue;
        }
        if (!array_key_exists($field_code, $normalized_values)) {
            if (is_array($value)) {
                $normalized_values[$field_code] = $value;
            } else {
                $normalized_values[$field_code] = msll_document_normalize_scalar($value);
            }
        }
    }

    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }

        $field_code = trim((string) ($field['field_code'] ?? ''));
        if ($field_code === '') {
            continue;
        }

        $field_type = strtolower(trim((string) ($field['field_type'] ?? 'text')));
        $value = $normalized_values[$field_code] ?? '';
        $field_label = trim((string) ($field['field_label'] ?? $field_code));

        if ($field_type === 'table' && !empty($field['is_required'])) {
            $table_rows = is_array($value) ? $value : array();
            if (count($table_rows) === 0) {
                $errors[] = 'Поле "' . $field_label . '" должно содержать хотя бы одну строку.';
                $field_errors[$field_code] = 'Добавьте строку в таблицу.';
            }
            continue;
        }

        if (!is_array($value)) {
            $value = msll_document_normalize_scalar($value);
        }

        if (!empty($field['is_required']) && $value === '') {
            $field_label = trim((string) ($field['field_label'] ?? $field_code));
            $errors[] = 'Поле "' . $field_label . '" обязательно для заполнения.';
            $field_errors[$field_code] = 'Поле обязательно для заполнения.';
            continue;
        }

        if (is_array($value) || $value === '') {
            continue;
        }

        if ($field_code === 'inn' && !msll_is_valid_inn($value)) {
            $errors[] = 'Поле "' . $field_label . '" должно содержать корректный ИНН.';
            $field_errors[$field_code] = 'Некорректный ИНН.';
            continue;
        }

        if ($field_code === 'bik' && !msll_is_valid_bik($value)) {
            $errors[] = 'Поле "' . $field_label . '" должно содержать корректный БИК.';
            $field_errors[$field_code] = 'Некорректный БИК.';
            continue;
        }

        if ($field_code === 'email' && !msll_is_valid_email_optional($value)) {
            $errors[] = 'Поле "' . $field_label . '" должно содержать корректный e-mail.';
            $field_errors[$field_code] = 'Некорректный e-mail.';
            continue;
        }

        // Дата договора редактируется в форме; в DOCX уходит разбивка по полям (см. document_derived_fields)
        if ($field_code === 'contract_date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $errors[] = 'Поле "' . $field_label . '" должно быть в формате ГГГГ-ММ-ДД.';
            $field_errors[$field_code] = 'Некорректная дата.';
            continue;
        }

        if ($field_code === 'checking_account') {
            $bik_value = $normalized_values['bik'] ?? '';
            if (!msll_is_valid_bik($bik_value)) {
                $errors[] = 'Для проверки поля "' . $field_label . '" необходимо указать корректный БИК.';
                $field_errors[$field_code] = 'Нужен корректный БИК для проверки счёта.';
                if (!isset($field_errors['bik'])) {
                    $field_errors['bik'] = 'Укажите корректный БИК.';
                }
                continue;
            }

            if (!msll_is_valid_checking_account($value, $bik_value)) {
                $errors[] = 'Поле "' . $field_label . '" не прошло проверку контрольного числа.';
                $field_errors[$field_code] = 'Некорректный расчётный счёт.';
                continue;
            }
        }

        if ($field_code === 'corr_account') {
            $bik_value = $normalized_values['bik'] ?? '';
            if (!msll_is_valid_bik($bik_value)) {
                $errors[] = 'Для проверки поля "' . $field_label . '" необходимо указать корректный БИК.';
                $field_errors[$field_code] = 'Нужен корректный БИК для проверки счёта.';
                if (!isset($field_errors['bik'])) {
                    $field_errors['bik'] = 'Укажите корректный БИК.';
                }
                continue;
            }

            if (!msll_is_valid_corr_account($value, $bik_value)) {
                $errors[] = 'Поле "' . $field_label . '" не прошло проверку контрольного числа.';
                $field_errors[$field_code] = 'Некорректный корреспондентский счёт.';
                continue;
            }
        }
    }

    return array(
        'values' => $normalized_values,
        'errors' => $errors,
        'field_errors' => $field_errors,
    );
}

/**
 * Разбирает одну запись field_map_json в пары «имя плейсхолдера DOCX → источник значения».
 *
 * @return array{targets: string[], source_field: string, default_value: string}
 */
function msll_document_parse_field_map_definition(string $source_key, $definition): array
{
    $source_field = trim($source_key);
    $default_value = '';
    $targets = array();

    if (is_string($definition)) {
        $target = trim($definition);
        if ($target !== '') {
            $targets[] = $target;
        }
    } elseif (is_array($definition)) {
        $has_assoc_keys = isset($definition['merge_field'])
            || isset($definition['docx_field'])
            || isset($definition['pdf_field'])
            || isset($definition['field_name'])
            || isset($definition['name'])
            || isset($definition['source_field'])
            || isset($definition['default_value']);

        if ($has_assoc_keys) {
            $merge_field_name = trim((string) (
                $definition['merge_field']
                ?? $definition['docx_field']
                ?? $definition['pdf_field']
                ?? $definition['field_name']
                ?? $definition['name']
                ?? ''
            ));
            $source_candidate = trim((string) ($definition['source_field'] ?? $source_field));
            if ($source_candidate !== '') {
                $source_field = $source_candidate;
            }
            $default_value = msll_document_normalize_scalar($definition['default_value'] ?? '');
            if ($merge_field_name !== '') {
                $targets[] = $merge_field_name;
            }
        } else {
            foreach ($definition as $target_item) {
                if (!is_string($target_item)) {
                    continue;
                }
                $target = trim($target_item);
                if ($target !== '') {
                    $targets[] = $target;
                }
            }
        }
    }

    return array(
        'targets' => $targets,
        'source_field' => $source_field,
        'default_value' => $default_value,
    );
}

/**
 * form_data (после enrich) → merge_fields для ${...} в DOCX.
 * База: все ключи form_data 1:1; field_map_json накладывает особые правила поверх.
 */
function msll_document_build_merge_field_values(array $field_map, array $form_values): array
{
    $merge_fields = array();

    foreach ($form_values as $field_code => $value) {
        if (is_array($value)) {
            continue;
        }
        $merge_fields[(string) $field_code] = msll_document_normalize_scalar($value);
    }

    foreach ($field_map as $source_key => $definition) {
        $parsed = msll_document_parse_field_map_definition((string) $source_key, $definition);
        if (count($parsed['targets']) === 0) {
            continue;
        }

        $source_field = $parsed['source_field'];
        $resolved_value = array_key_exists($source_field, $form_values)
            ? msll_document_normalize_scalar($form_values[$source_field])
            : $parsed['default_value'];

        foreach ($parsed['targets'] as $target_name) {
            $merge_fields[$target_name] = $resolved_value;
        }
    }

    return $merge_fields;
}

/** @deprecated используйте msll_document_build_merge_field_values */
function msll_document_build_pdf_field_values(array $field_map, array $form_values): array
{
    return msll_document_build_merge_field_values($field_map, $form_values);
}

function msll_document_slugify(string $value): string
{
    $text = trim($value);
    if ($text === '') {
        return 'document';
    }

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($converted) && $converted !== '') {
            $text = $converted;
        }
    }

    $text = preg_replace('/[^A-Za-z0-9]+/', '_', $text);
    $text = trim((string) $text, '_');

    return $text !== '' ? strtolower($text) : 'document';
}

function msll_document_make_filename(array $template, array $form_values): string
{
    $base_name = trim((string) ($template['template_code'] ?? $template['template_name'] ?? 'document'));
    $slug = msll_document_slugify($base_name);
    $inn = msll_document_normalize_scalar($form_values['inn'] ?? '');

    if ($inn !== '') {
        return $slug . '_' . $inn . '.docx';
    }

    return $slug . '.docx';
}

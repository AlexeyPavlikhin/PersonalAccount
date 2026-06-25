<?php

/**
 * HTTP-клиент DaData для раздела «Генерация документов»:
 * - findById/party — организация по ИНН (resolve_company_by_inn.php);
 * - findById/bank — банк по БИК (resolve_bank_by_bik.php);
 * - clean/name — склонение ФИО и пол (нужен DADATA_SECRET_KEY).
 */

require_once __DIR__ . '/document_text_transform.php';

function msll_dadata_get_api_key(): string
{
    if (!defined('DADATA_API_KEY')) {
        return '';
    }

    return trim((string) DADATA_API_KEY);
}

function msll_dadata_get_party_url(): string
{
    if (defined('DADATA_API_URL_PARTY')) {
        return trim((string) DADATA_API_URL_PARTY);
    }

    return 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
}

function msll_dadata_get_bank_url(): string
{
    if (defined('DADATA_API_URL_BANK')) {
        return trim((string) DADATA_API_URL_BANK);
    }

    return 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/bank';
}

function msll_dadata_get_secret_key(): string
{
    if (!defined('DADATA_SECRET_KEY')) {
        return '';
    }

    return trim((string) DADATA_SECRET_KEY);
}

function msll_dadata_get_clean_name_url(): string
{
    if (defined('DADATA_API_URL_CLEAN_NAME')) {
        return trim((string) DADATA_API_URL_CLEAN_NAME);
    }

    return 'https://cleaner.dadata.ru/api/v1/clean/name';
}

/** Общий POST к suggestions.dadata.ru или cleaner.dadata.ru. */
function msll_dadata_request(string $url, array $payload, bool $use_secret = false): array
{
    $api_key = msll_dadata_get_api_key();
    if ($api_key === '') {
        throw new RuntimeException('DADATA_API_KEY is empty');
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($body)) {
        throw new RuntimeException('Failed to encode DaData payload');
    }

    $headers = array(
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json',
        'Authorization: Token ' . $api_key,
    );

    // API стандартизации (clean/name) требует секретный ключ в заголовке X-Secret
    if ($use_secret) {
        $secret_key = msll_dadata_get_secret_key();
        if ($secret_key === '') {
            throw new RuntimeException('DADATA_SECRET_KEY is empty');
        }
        $headers[] = 'X-Secret: ' . $secret_key;
    }

    $raw_response = '';
    $http_code = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
        ));

        $raw_response = curl_exec($ch);
        if ($raw_response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('DaData request failed: ' . $error);
        }

        $http_code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ),
        ));

        $raw_response = @file_get_contents($url, false, $context);
        if ($raw_response === false) {
            throw new RuntimeException('DaData request failed via file_get_contents');
        }

        $http_code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header_line) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string) $header_line, $matches)) {
                    $http_code = (int) $matches[1];
                    break;
                }
            }
        }
    }

    if ($http_code < 200 || $http_code >= 300) {
        throw new RuntimeException('DaData returned HTTP ' . $http_code . ': ' . substr((string) $raw_response, 0, 400));
    }

    $decoded = json_decode((string) $raw_response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid DaData JSON response');
    }

    return $decoded;
}

function msll_dadata_find_party_by_inn_or_ogrn(string $query): array
{
    return msll_dadata_request(msll_dadata_get_party_url(), array(
        'query' => $query,
    ));
}

/** Ответ нормализуется в msll_dadata_normalize_bank_response(). */
function msll_dadata_find_bank_by_bik(string $query): array
{
    return msll_dadata_request(msll_dadata_get_bank_url(), array(
        'query' => $query,
    ));
}

function msll_dadata_first_suggestion(array $response): array
{
    $suggestions = isset($response['suggestions']) && is_array($response['suggestions'])
        ? $response['suggestions']
        : array();

    if (count($suggestions) === 0 || !is_array($suggestions[0])) {
        return array();
    }

    return $suggestions[0];
}

function msll_dadata_first_contact_value($items): string
{
    if (!is_array($items) || count($items) === 0) {
        return '';
    }

    $first_item = $items[0];
    if (is_array($first_item)) {
        foreach (array('value', 'unrestricted_value', 'source') as $key) {
            if (isset($first_item[$key]) && trim((string) $first_item[$key]) !== '') {
                return trim((string) $first_item[$key]);
            }
        }
    }

    return trim((string) $first_item);
}

/**
 * Стандартизация ФИО: пол, падежи (result_genitive и др.).
 * Без DADATA_SECRET_KEY возвращает пустой массив — дальше сработает локальный fallback.
 */
function msll_dadata_clean_name(string $fio): array
{
    $query = trim($fio);
    if ($query === '') {
        return array();
    }

    if (msll_dadata_get_secret_key() === '') {
        return array();
    }

    try {
        $response = msll_dadata_request(msll_dadata_get_clean_name_url(), array($query), true);
    } catch (Throwable $exception) {
        // не блокируем генерацию документа из-за недоступности clean/name
        return array();
    }

    if (!is_array($response) || count($response) === 0 || !is_array($response[0])) {
        return array();
    }

    return $response[0];
}

/** Единая точка: DaData clean/name или локальные правила склонения ФИО. */
function msll_dadata_normalize_signer_fio(string $fio): array
{
    $cleaned = msll_dadata_clean_name($fio);
    if (count($cleaned) > 0) {
        $gender = msll_document_normalize_gender_code((string) ($cleaned['gender'] ?? 'UNKNOWN'));
        $surname = trim((string) ($cleaned['surname'] ?? ''));
        $name = trim((string) ($cleaned['name'] ?? ''));
        $patronymic = trim((string) ($cleaned['patronymic'] ?? ''));

        return array(
            'result' => trim((string) ($cleaned['result'] ?? $fio)),
            'result_genitive' => trim((string) ($cleaned['result_genitive'] ?? '')),
            'gender' => $gender,
            'surname' => $surname,
            'name' => $name,
            'patronymic' => $patronymic,
            'initials' => msll_document_build_initials($name, $patronymic),
            'short' => msll_document_build_signer_short($surname, $name, $patronymic),
        );
    }

    return msll_document_decline_fio_genitive_fallback($fio);
}

/** ИП / физлицо-предприниматель: в ЕГРИП opf часто пустой. */
function msll_dadata_is_ip_party_type(string $party_type): bool
{
    $type = mb_strtoupper(trim($party_type));

    return in_array($type, array('INDIVIDUAL', 'INDIVIDUAL_ENTREPRENEUR'), true);
}

/**
 * П. 20: полное наименование с кратким и полным ОПФ (DaData name.short_with_opf / full_with_opf).
 *
 * @return array{company_name:string,company_name_short_opf:string,company_name_full_opf:string}
 */
function msll_dadata_build_company_name_fields(
    array $name,
    string $opf_short,
    string $opf_full,
    string $party_type,
    string $suggestion_value
): array {
    $short_with_opf = trim((string) ($name['short_with_opf'] ?? ''));
    $full_with_opf = trim((string) ($name['full_with_opf'] ?? ''));
    $fallback = trim($suggestion_value);

    // если *_with_opf нет — собираем из ОПФ и наименования без ОПФ
    if ($short_with_opf === '') {
        $name_part = trim((string) ($name['short'] ?? $name['full'] ?? ''));
        if ($name_part !== '' && $opf_short !== '') {
            $short_with_opf = trim($opf_short . ' ' . $name_part);
        }
    }
    if ($full_with_opf === '') {
        $name_part = trim((string) ($name['full'] ?? $name['short'] ?? ''));
        if ($name_part !== '' && $opf_full !== '') {
            $full_with_opf = trim($opf_full . ' ' . $name_part);
        }
    }

    if ($short_with_opf === '' && $fallback !== '') {
        $short_with_opf = $fallback;
    }
    if ($full_with_opf === '') {
        $full_with_opf = $fallback !== '' ? $fallback : $short_with_opf;
    }
    if ($short_with_opf === '' && $full_with_opf !== '') {
        $short_with_opf = $full_with_opf;
    }

    $company_name = $full_with_opf !== '' ? $full_with_opf : $short_with_opf;

    return array(
        'company_name' => $company_name,
        'company_name_short_opf' => $short_with_opf,
        'company_name_full_opf' => $full_with_opf,
    );
}

/** Плоский массив company для фронта и enrich; ключи совпадают с field_code шаблона. */
function msll_dadata_normalize_company_response(array $response): array
{
    $suggestion = msll_dadata_first_suggestion($response);
    $data = isset($suggestion['data']) && is_array($suggestion['data']) ? $suggestion['data'] : array();
    $name = isset($data['name']) && is_array($data['name']) ? $data['name'] : array();
    $opf = isset($data['opf']) && is_array($data['opf']) ? $data['opf'] : array();
    $address = isset($data['address']) && is_array($data['address']) ? $data['address'] : array();
    $management = isset($data['management']) && is_array($data['management']) ? $data['management'] : array();
    $party_type = mb_strtoupper(trim((string) ($data['type'] ?? '')));
    $signer_name = trim((string) ($management['name'] ?? ''));
    $fio_meta = msll_dadata_normalize_signer_fio($signer_name);
    $opf_short = trim((string) ($opf['short'] ?? ''));
    $opf_full = trim((string) ($opf['full'] ?? ''));
    // у ИП в ЕГРИП opf иногда пустой — подставляем типовые значения
    if (msll_dadata_is_ip_party_type($party_type) && $opf_short === '') {
        $opf_short = 'ИП';
    }
    if (msll_dadata_is_ip_party_type($party_type) && $opf_full === '') {
        $opf_full = 'Индивидуальный предприниматель';
    }

    $company_names = msll_dadata_build_company_name_fields(
        $name,
        $opf_short,
        $opf_full,
        $party_type,
        trim((string) ($suggestion['value'] ?? ''))
    );

    // Производные поля сразу отдаём на фронт при «дозаполнить», финально пересчитываются в enrich при генерации DOCX
    return array(
        'company_name' => $company_names['company_name'],
        'company_name_short_opf' => $company_names['company_name_short_opf'],
        'company_name_full_opf' => $company_names['company_name_full_opf'],
        'opf_short' => $opf_short,
        'opf_full' => $opf_full,
        'inn' => trim((string) ($data['inn'] ?? '')),
        'kpp' => trim((string) ($data['kpp'] ?? '')),
        'ogrn' => trim((string) ($data['ogrn'] ?? '')),
        'address' => trim((string) ($address['unrestricted_value'] ?? $address['value'] ?? '')),
        'signer_name' => $fio_meta['result'] !== '' ? $fio_meta['result'] : $signer_name,
        'signer_position' => trim((string) ($management['post'] ?? '')),
        'signer_basis' => '',
        'email' => msll_dadata_first_contact_value($data['emails'] ?? array()),
        'phone' => msll_dadata_first_contact_value($data['phones'] ?? array()),
        'counterparty_type' => $party_type,
        'counterparty_is_individual' => $party_type === 'INDIVIDUAL' ? '1' : '0',
        'signer_gender' => $fio_meta['gender'],
        'signer_name_genitive' => $fio_meta['result_genitive'],
        'signer_initials' => $fio_meta['initials'],
        'signer_short' => $fio_meta['short'],
        'signer_position_genitive' => msll_document_decline_position_genitive(trim((string) ($management['post'] ?? ''))),
        'party_named_form' => msll_document_party_named_form(
            $fio_meta['gender'],
            $party_type === 'INDIVIDUAL'
        ),
    );
}

/** bank_name, corr_account, bik; swift доступен, но в демо field_map не подключён. */
function msll_dadata_normalize_bank_response(array $response): array
{
    $suggestion = msll_dadata_first_suggestion($response);
    $data = isset($suggestion['data']) && is_array($suggestion['data']) ? $suggestion['data'] : array();
    $name = isset($data['name']) && is_array($data['name']) ? $data['name'] : array();

    return array(
        'bank_name' => trim((string) ($name['payment'] ?? $name['full_with_opf'] ?? $suggestion['value'] ?? '')),
        'corr_account' => trim((string) ($data['correspondent_account'] ?? '')),
        'bik' => trim((string) ($data['bic'] ?? '')),
        'swift' => trim((string) ($data['swift'] ?? '')),
    );
}

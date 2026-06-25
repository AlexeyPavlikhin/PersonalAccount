<?php

/**
 * Производные поля для DOCX: из введённых пользователем значений формируются
 * склонения, части даты и подписи, которых нет в UI, но есть в шаблоне.
 */

require_once __DIR__ . '/document_text_transform.php';
require_once __DIR__ . '/dadata_client.php';
require_once __DIR__ . '/bank_account_validation.php';
require_once __DIR__ . '/document_numbering.php';

/**
 * Дополняет form_data перед маппингом в плейсхолдеры DOCX (generate_document.php).
 * Имена ключей совпадают с плейсхолдерами ${field_code} в DOCX (field_map_json — только особый маппинг).
 */
function msll_document_enrich_form_values(array $form_values): array
{
    $enriched = $form_values;

    // п. 14–16: contract_date → contract_day, contract_month_*, contract_year_*
    $date_parts = msll_document_split_date_parts((string) ($form_values['contract_date'] ?? ''));
    foreach ($date_parts as $key => $value) {
        $enriched[$key] = $value;
    }

    // п. 17–18: именительный/родительный падеж ФИО, инициалы для блока подписей
    $signer_fio = trim((string) ($form_values['signer_name'] ?? ''));
    $fio_meta = msll_dadata_normalize_signer_fio($signer_fio);

    if ($fio_meta['result'] !== '') {
        $enriched['signer_name'] = $fio_meta['result'];
    }

    $enriched['signer_name_genitive'] = $fio_meta['result_genitive'];
    $enriched['signer_initials'] = $fio_meta['initials'];
    $enriched['signer_short'] = $fio_meta['short'];

    $gender = msll_document_normalize_gender_code((string) ($form_values['signer_gender'] ?? $fio_meta['gender']));
    if ($gender === 'UNKNOWN') {
        $gender = msll_document_detect_gender_from_patronymic($fio_meta['patronymic']);
    }
    $enriched['signer_gender'] = $gender;

    // должность в родительном — всегда пересчитываем из актуального signer_position
    $position = trim((string) ($form_values['signer_position'] ?? ''));
    $enriched['signer_position_genitive'] = msll_document_decline_position_genitive($position);

    // п. 19: форма обращения к контрагенту в преамбуле договора
    $is_individual = msll_document_is_individual_counterparty($form_values);
    $enriched['counterparty_is_individual'] = $is_individual ? '1' : '0';
    $enriched['party_named_form'] = msll_document_party_named_form($gender, $is_individual);

    // п. 20: наименование с кратким и полным ОПФ — в шаблоне оба варианта, если заданы в форме
    $company_short = trim((string) ($form_values['company_name_short_opf'] ?? ''));
    $company_full = trim((string) ($form_values['company_name_full_opf'] ?? ''));
    $company_primary = trim((string) ($form_values['company_name'] ?? ''));

    if ($company_full === '' && $company_primary !== '') {
        $company_full = $company_primary;
    }
    if ($company_short === '' && $company_full !== '') {
        $company_short = $company_full;
    }
    if ($company_primary === '' && $company_full !== '') {
        $company_primary = $company_full;
    }

    $enriched['company_name_short_opf'] = $company_short;
    $enriched['company_name_full_opf'] = $company_full;
    $enriched['company_name'] = $company_primary;

    // шаблон «Линки»: дата в колонтитуле ДД.ММ.ГГГГ
    $parsed_date = msll_document_parse_contract_date((string) ($enriched['contract_date'] ?? ''));
    if ($parsed_date instanceof DateTimeImmutable) {
        $enriched['contract_date_display'] = $parsed_date->format('d.m.Y');
    } else {
        $enriched['contract_date_display'] = '';
    }

    // ИНН/КПП одной строкой для блока реквизитов
    $inn_value = msll_digits_only($enriched['inn'] ?? '');
    $kpp_value = msll_digits_only($enriched['kpp'] ?? '');
    if ($inn_value !== '' && $kpp_value !== '') {
        $enriched['inn_kpp'] = $inn_value . '/' . $kpp_value;
    } elseif ($inn_value !== '') {
        $enriched['inn_kpp'] = $inn_value;
    } else {
        $enriched['inn_kpp'] = '';
    }

    // подпись в реквизитах: в DOCX перед плейсхолдером уже стоит «/»
    $enriched['signer_signature'] = trim((string) ($enriched['signer_short'] ?? ''));

    // Устаревшие поля номера (префикс/суффикс) → единый contract_number для старых шаблонов
    $contract_number = trim((string) ($enriched['contract_number'] ?? ''));
    if ($contract_number === '') {
        $legacy_prefix = trim((string) ($form_values['contract_number_prefix'] ?? ''));
        $legacy_suffix = trim((string) ($form_values['contract_number_suffix'] ?? ''));
        if ($legacy_prefix !== '' || $legacy_suffix !== '') {
            $contract_number = $legacy_prefix . $legacy_suffix;
            $enriched['contract_number'] = $contract_number;
        }
    }

    $enriched['counterparty_display'] = msll_document_resolve_counterparty_display_name($enriched);

    // Плановая дата акта для плейсхолдеров и отладочного справочника
    $spec_date_for_act = trim((string) ($enriched['spec_date'] ?? ''));
    if ($spec_date_for_act !== '') {
        $planned = msll_document_calc_planned_act_date($spec_date_for_act);
        if ($planned !== null) {
            $enriched['planned_act_date'] = $planned;
        }
    }

    return $enriched;
}

/** Снимок контрагента для реестра и DOCX: краткое ОПФ, ИП или ФИО. */
function msll_document_resolve_counterparty_display_name(array $form_values): string
{
    $counterparty_type = strtoupper(trim((string) ($form_values['counterparty_type'] ?? '')));
    $is_individual = msll_document_is_individual_counterparty($form_values);

    if ($is_individual || $counterparty_type === 'INDIVIDUAL') {
        $fio = trim((string) ($form_values['signer_name'] ?? ''));
        if ($fio !== '') {
            return $fio;
        }
    }

    if ($counterparty_type === 'INDIVIDUAL_ENTREPRENEUR') {
        $ip_name = trim((string) ($form_values['company_name_short_opf'] ?? ''));
        if ($ip_name !== '') {
            return $ip_name;
        }
        $ip_full = trim((string) ($form_values['company_name'] ?? ''));
        if ($ip_full !== '') {
            return $ip_full;
        }
    }

    $short_opf = trim((string) ($form_values['company_name_short_opf'] ?? ''));
    if ($short_opf !== '') {
        return $short_opf;
    }

    return trim((string) ($form_values['company_name'] ?? ''));
}

/** Тип контрагента приходит из DaData party (data.type); для физлица — INDIVIDUAL. */
function msll_document_is_individual_counterparty(array $form_values): bool
{
    if (!empty($form_values['counterparty_is_individual'])) {
        return in_array((string) $form_values['counterparty_is_individual'], array('1', 'true', 'yes'), true);
    }

    $party_type = mb_strtoupper(trim((string) ($form_values['counterparty_type'] ?? '')));

    return $party_type === 'INDIVIDUAL';
}

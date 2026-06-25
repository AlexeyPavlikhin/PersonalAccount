<?php
/**
 * Генерация DOCX: валидация формы → enrich (склонения, дата) → скачивание шаблона → подстановка плейсхолдеров.
 * POST JSON: { template_id, form_data }.
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/audit_log.php';
require_once __DIR__ . '/../inc/bank_account_validation.php';
require_once __DIR__ . '/../inc/document_field_cache.php';
require_once __DIR__ . '/../inc/document_template_mapper.php';
require_once __DIR__ . '/../inc/document_derived_fields.php';
require_once __DIR__ . '/../inc/docx_form_filler.php';
require_once __DIR__ . '/../inc/docx_table_filler.php';
require_once __DIR__ . '/../inc/document_template_loader.php';
require_once __DIR__ . '/../inc/document_numbering.php';
require_once __DIR__ . '/../inc/document_registry.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

$template_id = isset($data['template_id']) ? (int) $data['template_id'] : 0;
$form_data = isset($data['form_data']) && is_array($data['form_data']) ? $data['form_data'] : array();
// preview — только предпросмотр проекта без кэширования и аудита (требования UI п. 6, 8)
$is_preview_mode = isset($data['mode']) && (string) $data['mode'] === 'preview';

if ($template_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'template_id_required'));
    exit;
}

try {
    $query_template = $connection->prepare("
        SELECT
            template_id,
            template_code,
            template_name,
            template_category,
            template_description,
            template_url,
            field_map_json,
            filter_tags_json,
            cache_key_field,
            cache_fields_json,
            registry_role,
            table_blocks_json,
            is_active,
            sort
        FROM document_templates
        WHERE template_id = :template_id AND is_active = 1
        LIMIT 1
    ");
    $query_template->bindParam(':template_id', $template_id, PDO::PARAM_INT);
    $query_template->execute();
    $template = $query_template->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        echo json_encode(array('status' => 'error', 'message' => 'template_not_found'));
        exit;
    }

    // Нормализованная роль (учитывает template_code, если registry_role в БД не обновлён)
    $template['registry_role'] = msll_document_registry_role($template);

    $query_fields = $connection->prepare("
        SELECT
            field_id,
            field_code,
            field_label,
            field_type,
            placeholder,
            default_value,
            is_required,
            data_source,
            source_field_code,
            sort
        FROM document_template_fields
        WHERE template_id = :template_id
        ORDER BY sort, field_id
    ");
    $query_fields->bindParam(':template_id', $template_id, PDO::PARAM_INT);
    $query_fields->execute();
    $fields = $query_fields->fetchAll(PDO::FETCH_ASSOC);

    $prepared = msll_document_prepare_form_data($fields, $form_data);
    if (count($prepared['errors']) > 0) {
        echo json_encode(array(
            'status' => 'error',
            'message' => implode(' ', $prepared['errors']),
            'validation_errors' => $prepared['errors'],
            'field_errors' => $prepared['field_errors'],
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $form_values_for_generation = $prepared['values'];
    // Поля нумерации могут не быть в document_template_fields, но приходят с клиента
    foreach (array('contract_number', 'contract_date', 'contract_subject_short') as $registry_field_code) {
        if (!array_key_exists($registry_field_code, $form_values_for_generation) && array_key_exists($registry_field_code, $form_data)) {
            $form_values_for_generation[$registry_field_code] = msll_document_normalize_scalar($form_data[$registry_field_code]);
        }
    }

    $registry_role = msll_document_registry_role($template);
    $needs_numbering_transaction = !$is_preview_mode && in_array($registry_role, array('contract', 'specification', 'invoice', 'act'), true);

    if ($needs_numbering_transaction) {
        $connection->beginTransaction();
    }

    try {
        if (!$is_preview_mode) {
            msll_document_apply_numbering_on_generation($connection, $template, $form_values_for_generation);
        }

        // Склонения, части даты и party_named_form — не вводятся вручную, считаются перед подстановкой в DOCX
        $normalized_form_data = msll_document_enrich_form_values($form_values_for_generation);
        $field_map = msll_document_decode_json_map($template['field_map_json'] ?? '');
        $merge_fields = msll_document_build_merge_field_values($field_map, $normalized_form_data);

        $binary_docx_template = msll_document_fetch_binary(trim((string) ($template['template_url'] ?? '')));
        $table_blocks = msll_document_decode_json_map($template['table_blocks_json'] ?? '');
        $table_row_data = msll_document_extract_table_row_data($normalized_form_data, $table_blocks);
        $filled_docx = msll_docx_fill_template($binary_docx_template, $merge_fields, $table_blocks, $table_row_data);
        $download_filename = msll_document_make_filename($template, $normalized_form_data);

        if (!$is_preview_mode) {
            msll_document_save_registry_on_generation($connection, $template, $normalized_form_data);
        }

        if ($needs_numbering_transaction) {
            $connection->commit();
        }
    } catch (Throwable $registry_exception) {
        if ($needs_numbering_transaction && $connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $registry_exception;
    }

    if (!$is_preview_mode) {
        // требования п. 21–25: кэш полей по ключу шаблона (без привязки к template_id в таблице кэша)
        msll_document_save_field_cache_on_generation($connection, $template, $normalized_form_data);

        msll_audit_write(
            $connection,
            isset($_SESSION['current_user_login']) ? (string) $_SESSION['current_user_login'] : 'system',
            'generate_document',
            json_encode(array(
                'template_id' => (int) ($template['template_id'] ?? 0),
                'template_code' => trim((string) ($template['template_code'] ?? '')),
                'inn' => msll_digits_only($normalized_form_data['inn'] ?? ''),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    if ($is_preview_mode) {
        // DOCX в base64 — клиент рендерит через docx-preview (визуально как в Word)
        $preview_payload = msll_document_prepare_generation_payload($template, $filled_docx, $download_filename);
        $preview_payload['form_data'] = $normalized_form_data;
        unset($preview_payload['download_filename']);
        echo json_encode($preview_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $payload = msll_document_prepare_generation_payload($template, $filled_docx, $download_filename);
    $payload['form_data'] = $normalized_form_data;
    $payload['registry_role'] = msll_document_registry_role($template);
    $payload['registry_saved'] = msll_document_registry_role($template) !== 'none';

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    $error_message = $e->getMessage();
    if (stripos($error_message, 'document_issued_') !== false || stripos($error_message, 'document_number_counters') !== false) {
        $error_message .= ' (проверьте, что применён database/migration_issued_documents.sql)';
    }
    echo json_encode(array('status' => 'error', 'message' => $error_message));
}

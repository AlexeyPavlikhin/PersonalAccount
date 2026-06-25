<?php
/**
 * Конфигурация одного шаблона: метаданные, field_map, список полей формы (document_template_fields).
 * Вызывается при выборе шаблона в pg_documents.js.
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/document_template_mapper.php';
require_once __DIR__ . '/../inc/document_field_cache.php';
require_once __DIR__ . '/../inc/document_numbering.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
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
            sort,
            created_at,
            updated_at
        FROM document_templates
        WHERE template_id = :template_id
        LIMIT 1
    ");
    $query_template->bindParam(':template_id', $template_id, PDO::PARAM_INT);
    $query_template->execute();
    $template = $query_template->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        echo json_encode(array('status' => 'error', 'message' => 'template_not_found'));
        exit;
    }

    $template['registry_role'] = msll_document_registry_role($template);

    $query_fields = $connection->prepare("
        SELECT
            field_id,
            template_id,
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

    foreach ($fields as &$field) {
        $field['field_id'] = (int) ($field['field_id'] ?? 0);
        $field['template_id'] = (int) ($field['template_id'] ?? 0);
        $field['field_code'] = trim((string) ($field['field_code'] ?? ''));
        $field['field_label'] = trim((string) ($field['field_label'] ?? ''));
        $field['field_type'] = trim((string) ($field['field_type'] ?? 'text'));
        $field['placeholder'] = trim((string) ($field['placeholder'] ?? ''));
        $field['default_value'] = trim((string) ($field['default_value'] ?? ''));
        $field['is_required'] = (int) ($field['is_required'] ?? 0);
        $field['data_source'] = trim((string) ($field['data_source'] ?? 'manual'));
        $field['source_field_code'] = trim((string) ($field['source_field_code'] ?? ''));
        $field['sort'] = (int) ($field['sort'] ?? 0);
    }
    unset($field);

    $template['template_id'] = (int) ($template['template_id'] ?? 0);
    $template['template_code'] = trim((string) ($template['template_code'] ?? ''));
    $template['template_name'] = trim((string) ($template['template_name'] ?? ''));
    $template['template_category'] = trim((string) ($template['template_category'] ?? ''));
    $template['template_description'] = trim((string) ($template['template_description'] ?? ''));
    $template['template_url'] = trim((string) ($template['template_url'] ?? ''));
    $template['field_map'] = msll_document_decode_json_map($template['field_map_json'] ?? '');
    $template['filter_tags'] = msll_document_decode_json_map($template['filter_tags_json'] ?? '');
    $template['cache_key_field'] = trim((string) ($template['cache_key_field'] ?? ''));
    $template['cache_fields'] = msll_document_decode_cache_fields($template['cache_fields_json'] ?? '');
    $template['registry_role'] = trim((string) ($template['registry_role'] ?? 'none'));
    $template['table_blocks'] = msll_document_decode_json_map($template['table_blocks_json'] ?? '');
    $template['is_active'] = (int) ($template['is_active'] ?? 0);
    $template['sort'] = (int) ($template['sort'] ?? 0);
    unset($template['field_map_json'], $template['filter_tags_json'], $template['cache_fields_json'], $template['table_blocks_json']);

    echo json_encode(array(
        'status' => 'ok',
        'template' => $template,
        'fields' => $fields,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

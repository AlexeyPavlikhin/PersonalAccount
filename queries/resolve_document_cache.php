<?php
/**
 * Восстановление кэшируемых полей формы по ключу шаблона (без привязки к template_id в данных кэша).
 * GET template_id=…&cache_key_value=…  →  { status, cache_key_field, fields: { field_code: { options, latest } } }.
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bank_account_validation.php';
require_once __DIR__ . '/../inc/document_field_cache.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
$cache_key_value_raw = isset($_GET['cache_key_value']) ? trim((string) $_GET['cache_key_value']) : '';

if ($template_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'template_id_required'));
    exit;
}

try {
    $query_template = $connection->prepare("
        SELECT template_id, cache_key_field, cache_fields_json
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

    $cache_key_field = trim((string) ($template['cache_key_field'] ?? ''));
    $cache_field_codes = msll_document_decode_cache_fields($template['cache_fields_json'] ?? '');

    if ($cache_key_field === '' || count($cache_field_codes) === 0) {
        echo json_encode(array(
            'status' => 'ok',
            'cache_key_field' => $cache_key_field,
            'cache_fields' => $cache_field_codes,
            'fields' => array(),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $cache_key_value = msll_document_normalize_cache_key_value($cache_key_field, $cache_key_value_raw);
    if ($cache_key_field === 'inn' && !msll_is_valid_inn($cache_key_value)) {
        echo json_encode(array('status' => 'error', 'message' => 'invalid_cache_key_value'));
        exit;
    }

    if ($cache_key_value === '') {
        echo json_encode(array('status' => 'error', 'message' => 'cache_key_value_required'));
        exit;
    }

    $fields = msll_document_collect_cache_restore_options(
        $connection,
        $cache_key_field,
        $cache_key_value,
        $cache_field_codes
    );

    echo json_encode(array(
        'status' => 'ok',
        'cache_key_field' => $cache_key_field,
        'cache_key_value' => $cache_key_value,
        'cache_fields' => $cache_field_codes,
        'fields' => $fields,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

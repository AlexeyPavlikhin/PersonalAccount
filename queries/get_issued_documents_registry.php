<?php
/**
 * Реестр оформленных договоров и спецификаций для UI (п. 10–12).
 * GET: contract_number — необязательный фильтр (LIKE).
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/document_registry.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$contract_number_filter = isset($_GET['contract_number']) ? trim((string) $_GET['contract_number']) : '';

try {
    $rows = msll_document_registry_fetch_rows($connection, $contract_number_filter);
    echo json_encode(array(
        'status' => 'ok',
        'rows' => $rows,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

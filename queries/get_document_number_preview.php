<?php
/**
 * Превью следующих номеров для readonly-полей формы (без инкремента счётчиков).
 * GET: template_id, contract_date, contract_number (для spec).
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/document_numbering.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
$contract_date = isset($_GET['contract_date']) ? trim((string) $_GET['contract_date']) : '';
$contract_number = isset($_GET['contract_number']) ? trim((string) $_GET['contract_number']) : '';

if ($template_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'template_id_required'));
    exit;
}

try {
    $query = $connection->prepare("
        SELECT template_code, registry_role
        FROM document_templates
        WHERE template_id = :template_id AND is_active = 1
        LIMIT 1
    ");
    $query->bindParam(':template_id', $template_id, PDO::PARAM_INT);
    $query->execute();
    $template = $query->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        echo json_encode(array('status' => 'error', 'message' => 'template_not_found'));
        exit;
    }

    $template['registry_role'] = msll_document_registry_role($template);
    $role = $template['registry_role'];
    $preview = array('registry_role' => $role);

    if ($role === 'contract') {
        $preview['contract_number'] = msll_document_preview_contract_number($connection, $contract_date);
    }
    if ($role === 'specification') {
        if ($contract_number !== '') {
            $preview['spec_number'] = msll_document_preview_spec_number($connection, $contract_number);
        }
        $preview['invoice_number'] = msll_document_preview_invoice_number($connection);
    }
    if ($role === 'invoice') {
        $preview['invoice_number'] = msll_document_preview_invoice_number($connection);
    }

    echo json_encode(array('status' => 'ok', 'preview' => $preview), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

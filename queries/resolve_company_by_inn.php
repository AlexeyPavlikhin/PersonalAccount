<?php
/**
 * Дозаполнение формы: организация по ИНН (DaData party). Кэшируемые поля — resolve_document_cache.php.
 * GET inn=…  →  { status, company, stored_profile }.
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/dadata_client.php';
if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$inn = isset($_GET['inn']) ? preg_replace('/\D+/', '', (string) $_GET['inn']) : '';
if ($inn === '' || !preg_match('/^\d{10,12}$/', $inn)) {
    echo json_encode(array('status' => 'error', 'message' => 'invalid_inn'));
    exit;
}

try {
    $response = msll_dadata_find_party_by_inn_or_ogrn($inn);
    $company = msll_dadata_normalize_company_response($response);

    if (($company['inn'] ?? '') === '') {
        echo json_encode(array('status' => 'error', 'message' => 'company_not_found'));
        exit;
    }

    echo json_encode(array(
        'status' => 'ok',
        'company' => $company,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

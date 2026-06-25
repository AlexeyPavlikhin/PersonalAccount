<?php
/**
 * Дозаполнение формы: реквизиты банка по БИК (DaData findById/bank).
 * GET bik=…  →  { status, bank }.
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/dadata_client.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$bik = isset($_GET['bik']) ? preg_replace('/\D+/', '', (string) $_GET['bik']) : '';
if ($bik === '' || !preg_match('/^\d{9}$/', $bik)) {
    echo json_encode(array('status' => 'error', 'message' => 'invalid_bik'));
    exit;
}

try {
    $response = msll_dadata_find_bank_by_bik($bik);
    $bank = msll_dadata_normalize_bank_response($response);

    if (($bank['bik'] ?? '') === '') {
        echo json_encode(array('status' => 'error', 'message' => 'bank_not_found'));
        exit;
    }

    echo json_encode(array(
        'status' => 'ok',
        'bank' => $bank,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

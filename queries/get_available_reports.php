<?php
session_start();
include('../config.php');
require_once __DIR__ . '/../inc/report_runner.php';

header('Content-Type: application/json; charset=UTF-8');

$response = array('ok' => 0, 'reports' => array());

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode($response);
    exit;
}

try {
    $reports = report_get_available_for_user($connection, intval($_SESSION['current_user_id']));
    $response['ok'] = 1;
    $response['reports'] = $reports;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

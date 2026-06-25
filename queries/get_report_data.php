<?php
session_start();
include('../config.php');
require_once __DIR__ . '/../inc/report_runner.php';

header('Content-Type: application/json; charset=UTF-8');

$response = array(
    'ok' => 0,
    'rows' => array(),
    'columns' => array(),
    'report' => null
);

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode($response);
    exit;
}

$report_code = isset($_GET['report_code']) ? trim($_GET['report_code']) : '';
if ($report_code === '') {
    $response['error'] = 'report_code_required';
    echo json_encode($response);
    exit;
}

$sort_field = isset($_GET['sort_field']) ? trim($_GET['sort_field']) : '';
$sort_direction = isset($_GET['sort_direction']) ? trim($_GET['sort_direction']) : 'DESC';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page_size = isset($_GET['page_size']) ? intval($_GET['page_size']) : 50;
$pagination_params = report_normalize_pagination($page, $page_size);

$filters = array();
if (isset($_GET['filters'])) {
    $decoded_filters = json_decode($_GET['filters'], true);
    if (is_array($decoded_filters)) {
        $filters = $decoded_filters;
    }
}

try {
    $report = report_load_by_code($connection, $report_code);
    if (!$report) {
        $response['error'] = 'report_not_found';
        echo json_encode($response);
        exit;
    }

    $user_id = intval($_SESSION['current_user_id']);
    if (!report_user_can_view($connection, $user_id, intval($report['report_id']))) {
        $response['error'] = 'access_denied';
        echo json_encode($response);
        exit;
    }

    $resolved_sort = report_resolve_sort($report, $sort_field, $sort_direction);

    $total_rows = report_fetch_count($connection, $report, $filters);
    $pagination = report_build_pagination_meta(
        $total_rows,
        $pagination_params['page'],
        $pagination_params['page_size']
    );

    $rows = report_fetch_data(
        $connection,
        $report,
        $resolved_sort['field'],
        $resolved_sort['direction'],
        $filters,
        $pagination['page'],
        $pagination['page_size']
    );

    $response['ok'] = 1;
    $response['rows'] = $rows;
    $response['pagination'] = $pagination;
    $response['sort'] = $resolved_sort;
    $response['columns'] = $report['columns'];
    $response['report'] = report_build_api_meta($report);
} catch (InvalidArgumentException $e) {
    $response['error'] = $e->getMessage();
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

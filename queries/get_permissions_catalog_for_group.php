<?php
session_start();
include('../config.php');

if (!isset($_SESSION['current_user_id'])) {
    exit;
}

$user_group = isset($_GET['user_group']) ? trim($_GET['user_group']) : '';
if ($user_group === '') {
    echo json_encode(array('error' => 'user_group is required'));
    exit;
}

try {
    $query_catalog_permissions = $connection->prepare("
        SELECT 
            sprp.permition_id,
            sprp.permition_name,
            sprp.menu_item_name,
            sprp.permition_group,
            sprp.sort
        FROM spr_permitions sprp
        WHERE sprp.permition_group = :user_group
        ORDER BY sprp.permition_group, sprp.sort, sprp.permition_name
    ");
    $query_catalog_permissions->bindParam(':user_group', $user_group, PDO::PARAM_STR);
    $query_catalog_permissions->execute();
    $all_permissions = $query_catalog_permissions->fetchAll(PDO::FETCH_ASSOC);

    $query_catalog_courses = $connection->prepare("
        SELECT
            scn.id AS course_id,
            scn.course_name,
            scn.period_in_days,
            DATE_FORMAT(scn.start_date, '%Y-%m-%d') AS start_date
        FROM spr_courses_name scn
        ORDER BY scn.course_name
    ");
    $query_catalog_courses->execute();
    $all_courses = $query_catalog_courses->fetchAll(PDO::FETCH_ASSOC);

    $all_reports = array();
    try {
        $query_catalog_reports = $connection->prepare("
            SELECT
                sr.report_id,
                sr.report_code,
                sr.report_name,
                sr.report_description,
                sr.sort
            FROM spr_reports sr
            WHERE sr.is_active = 1
            ORDER BY sr.sort, sr.report_name
        ");
        $query_catalog_reports->execute();
        $all_reports = $query_catalog_reports->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $reports_exception) {
        $all_reports = array();
    }

    $response = array(
        'user_group' => $user_group,
        'all_permissions' => $all_permissions,
        'assigned_permissions' => array(),
        'all_courses' => $all_courses,
        'assigned_courses' => array(),
        'all_reports' => $all_reports,
        'assigned_reports' => array()
    );

    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
?>


<?php
session_start();
include('../config.php');

if (!isset($_SESSION['current_user_id'])) {
    exit;
}

$user_login = isset($_GET['user_login']) ? trim($_GET['user_login']) : '';
if ($user_login === '') {
    echo json_encode(array('error' => 'user_login is required'));
    exit;
}

try {
    $query_user = $connection->prepare("SELECT id, login, user_group FROM users WHERE login = :user_login LIMIT 1");
    $query_user->bindParam(':user_login', $user_login, PDO::PARAM_STR);
    $query_user->execute();
    $user = $query_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(array('error' => 'user_not_found'));
        exit;
    }

    $user_id = intval($user['id']);
    $user_group = isset($user['user_group']) ? trim($user['user_group']) : '';

    if ($user_group === '') {
        echo json_encode(array('error' => 'user_group_is_empty'));
        exit;
    }

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

    $query_assigned_permissions = $connection->prepare("
        SELECT
            up.user_permition_id,
            up.permition_id,
            up.deadline,
            sprp.permition_name,
            sprp.menu_item_name,
            sprp.permition_group,
            sprp.sort
        FROM users_permitions up
        INNER JOIN spr_permitions sprp ON sprp.permition_id = up.permition_id
        WHERE up.user_id = :user_id AND sprp.permition_group = :user_group
        ORDER BY sprp.permition_group, sprp.sort, sprp.permition_name
    ");
    $query_assigned_permissions->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $query_assigned_permissions->bindParam(':user_group', $user_group, PDO::PARAM_STR);
    $query_assigned_permissions->execute();
    $assigned_permissions = $query_assigned_permissions->fetchAll(PDO::FETCH_ASSOC);

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

    $query_assigned_courses = $connection->prepare("
        SELECT
            upc.id,
            upc.course_id,
            DATE_FORMAT(upc.available_until, '%Y-%m-%d') AS available_until,
            scn.course_name
        FROM users_premited_courses upc
        INNER JOIN spr_courses_name scn ON scn.id = upc.course_id
        WHERE upc.user_id = :user_id
        ORDER BY scn.course_name
    ");
    $query_assigned_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $query_assigned_courses->execute();
    $assigned_courses = $query_assigned_courses->fetchAll(PDO::FETCH_ASSOC);

    $response = array(
        'user_id' => $user_id,
        'user_login' => $user['login'],
        'all_permissions' => $all_permissions,
        'assigned_permissions' => $assigned_permissions,
        'all_courses' => $all_courses,
        'assigned_courses' => $assigned_courses
    );

    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
?>

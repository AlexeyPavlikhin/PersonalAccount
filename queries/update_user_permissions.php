<?php
session_start();
include('../config.php');

require_once __DIR__ . '/../libs/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!isset($_SESSION['current_user_id'])) {
    exit;
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!is_array($data) || !isset($data['user_login'])) {
    echo json_encode(array('status' => 'error', 'message' => 'invalid_payload'));
    exit;
}

$user_login = trim($data['user_login']);
$assigned_permissions = isset($data['assigned_permissions']) && is_array($data['assigned_permissions']) ? $data['assigned_permissions'] : array();
$assigned_courses = isset($data['assigned_courses']) && is_array($data['assigned_courses']) ? $data['assigned_courses'] : array();

if ($user_login === '') {
    echo json_encode(array('status' => 'error', 'message' => 'user_login_required'));
    exit;
}

try {
    $connection->beginTransaction();

    $query_user = $connection->prepare("SELECT id, user_group, username, email FROM users WHERE login = :user_login LIMIT 1");
    $query_user->bindParam(':user_login', $user_login, PDO::PARAM_STR);
    $query_user->execute();
    $user = $query_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $connection->rollBack();
        echo json_encode(array('status' => 'error', 'message' => 'user_not_found'));
        exit;
    }

    $user_id = intval($user['id']);
    $user_group = isset($user['user_group']) ? trim($user['user_group']) : '';
    if ($user_group === '') {
        $connection->rollBack();
        echo json_encode(array('status' => 'error', 'message' => 'user_group_is_empty'));
        exit;
    }

    $query_allowed_permissions = $connection->prepare("
        SELECT permition_id, permition_name, permition_group
        FROM spr_permitions
        WHERE permition_group = :user_group
    ");
    $query_allowed_permissions->bindParam(':user_group', $user_group, PDO::PARAM_STR);
    $query_allowed_permissions->execute();
    $allowed_permissions = $query_allowed_permissions->fetchAll(PDO::FETCH_ASSOC);

    $allowed_permission_map = array();
    $course_permission_id = 0;
    foreach ($allowed_permissions as $permission_row) {
        $perm_id = intval($permission_row['permition_id']);
        if ($perm_id <= 0) {
            continue;
        }
        $allowed_permission_map[$perm_id] = array(
            'permition_name' => $permission_row['permition_name'],
            'permition_group' => $permission_row['permition_group']
        );
        if ($permission_row['permition_name'] === 'courses') {
            $course_permission_id = $perm_id;
        }
    }

    $normalize_deadline = function ($in_value) {
        $value = trim((string)$in_value);
        if ($value === '') {
            return '2099-12-31 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $value)) {
            return substr($value, 0, 10) . ' 00:00:00';
        }

        return '2099-12-31 00:00:00';
    };

    $normalized_permissions = array();
    foreach ($assigned_permissions as $permission_item) {
        $permition_id = isset($permission_item['permition_id']) ? intval($permission_item['permition_id']) : 0;
        if ($permition_id <= 0 || !isset($allowed_permission_map[$permition_id])) {
            continue;
        }
        $normalized_permissions[$permition_id] = array(
            'permition_id' => $permition_id,
            'deadline' => $normalize_deadline(isset($permission_item['deadline']) ? $permission_item['deadline'] : '')
        );
    }

    $has_courses_permission = $course_permission_id > 0 && isset($normalized_permissions[$course_permission_id]);
    if (!$has_courses_permission) {
        foreach ($normalized_permissions as $perm_id => $permission_row) {
            if ($allowed_permission_map[$perm_id]['permition_group'] === 'Доступ к учебным курсам') {
                unset($normalized_permissions[$perm_id]);
            }
        }
    }

    // Аудит: снимок полномочий в БД до DELETE/INSERT (сроки — через тот же $normalize_deadline, что и при сохранении)
    $stmt_prev_perm = $connection->prepare("
        SELECT up.permition_id, up.deadline, sprp.permition_name, sprp.menu_item_name
        FROM users_permitions up
        INNER JOIN spr_permitions sprp ON sprp.permition_id = up.permition_id
        WHERE up.user_id = :user_id AND sprp.permition_group = :user_group
    ");
    $stmt_prev_perm->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_prev_perm->bindParam(':user_group', $user_group, PDO::PARAM_STR);
    $stmt_prev_perm->execute();
    $prev_perm_deadline = array();
    $prev_perm_label = array();
    while ($prow = $stmt_prev_perm->fetch(PDO::FETCH_ASSOC)) {
        $pid = intval($prow['permition_id']);
        if ($pid <= 0) {
            continue;
        }
        $prev_perm_deadline[$pid] = $normalize_deadline(isset($prow['deadline']) ? $prow['deadline'] : '');
        $plabel = trim(isset($prow['menu_item_name']) ? $prow['menu_item_name'] : '');
        if ($plabel === '') {
            $plabel = trim(isset($prow['permition_name']) ? $prow['permition_name'] : '');
        }
        $prev_perm_label[$pid] = $plabel !== '' ? $plabel : ('permition_id='.$pid);
    }

    // Дальше — перезапись users_permitions по данным из запроса
    $delete_permissions = $connection->prepare("DELETE FROM users_permitions WHERE user_id = :user_id");
    $delete_permissions->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $delete_permissions->execute();

    if (count($normalized_permissions) > 0) {
        $insert_permission = $connection->prepare("
            INSERT INTO users_permitions (user_id, permition_id, deadline)
            VALUES (:user_id, :permition_id, :deadline)
        ");

        foreach ($normalized_permissions as $permission_item) {
            $permition_id = $permission_item['permition_id'];
            $deadline = $permission_item['deadline'];
            $insert_permission->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_permission->bindParam(':permition_id', $permition_id, PDO::PARAM_INT);
            $insert_permission->bindParam(':deadline', $deadline, PDO::PARAM_STR);
            $insert_permission->execute();
        }
    }

    // Аудит + уведомления: полный снимок курсов до очистки users_premited_courses; $previous_course_id_set — для списка новых курсов (письма)
    $previous_course_id_set = array();
    $prev_courses_state = array();
    $stmt_prev_courses_full = $connection->prepare("
        SELECT upc.course_id,
               DATE_FORMAT(upc.available_until, '%Y-%m-%d') AS available_until,
               scn.course_name
        FROM users_premited_courses upc
        INNER JOIN spr_courses_name scn ON scn.id = upc.course_id
        WHERE upc.user_id = :user_id
    ");
    $stmt_prev_courses_full->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_prev_courses_full->execute();
    while ($crow = $stmt_prev_courses_full->fetch(PDO::FETCH_ASSOC)) {
        $cid = intval($crow['course_id']);
        if ($cid <= 0) {
            continue;
        }
        $previous_course_id_set[$cid] = true;
        $prev_courses_state[$cid] = array(
            'available_until' => trim(isset($crow['available_until']) ? $crow['available_until'] : ''),
            'course_name' => trim(isset($crow['course_name']) ? $crow['course_name'] : '')
        );
    }

    $courses_to_save_ids = array();
    if ($has_courses_permission && count($assigned_courses) > 0) {
        foreach ($assigned_courses as $course_item) {
            $cid = isset($course_item['course_id']) ? intval($course_item['course_id']) : 0;
            if ($cid > 0) {
                $courses_to_save_ids[$cid] = true;
            }
        }
    }
    $newly_added_course_ids = array();
    foreach (array_keys($courses_to_save_ids) as $cid) {
        if (!isset($previous_course_id_set[$cid])) {
            $newly_added_course_ids[] = $cid;
        }
    }
    $newly_added_course_ids = array_values(array_unique($newly_added_course_ids));

    $delete_courses = $connection->prepare("DELETE FROM users_premited_courses WHERE user_id = :user_id");
    $delete_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $delete_courses->execute();

    if ($has_courses_permission && count($assigned_courses) > 0) {
        $insert_course = $connection->prepare("
            INSERT INTO users_premited_courses (course_id, user_id, available_until)
            VALUES (:course_id, :user_id, :available_until)
        ");

        foreach ($assigned_courses as $course_item) {
            $course_id = isset($course_item['course_id']) ? intval($course_item['course_id']) : 0;
            if ($course_id <= 0) {
                continue;
            }

            $available_until = date('Y-m-d', strtotime('+30 day'));
            if (isset($course_item['available_until']) && trim($course_item['available_until']) !== '') {
                $available_until_raw = trim($course_item['available_until']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $available_until_raw)) {
                    $available_until = $available_until_raw;
                }
            }

            $insert_course->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $insert_course->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_course->bindParam(':available_until', $available_until, PDO::PARAM_STR);
            $insert_course->execute();
        }
    }

    // Синхронизируем clients*/sales через процедуру после сохранения назначенных курсов.
    if ($has_courses_permission && count($assigned_courses) > 0) {
        $target_user_email = isset($user['email']) ? trim($user['email']) : '';
        $target_user_name = isset($user['username']) ? trim($user['username']) : '';
        $target_user_phone = '';
        $target_user_telegram = '';

        $name_parts = preg_split('/\s+/', $target_user_name);
        $last_name = isset($name_parts[0]) ? trim($name_parts[0]) : '';
        $first_name = isset($name_parts[1]) ? trim($name_parts[1]) : '';
        $patronymic = isset($name_parts[2]) ? trim($name_parts[2]) : '';
        if ($first_name === '' && $last_name !== '') {
            $first_name = $last_name;
            $last_name = '';
        }

        $query_contacts = $connection->prepare("
            SELECT
                (
                    SELECT cp.phone
                    FROM clients c_phone
                    INNER JOIN clients_phone cp ON cp.client_id = c_phone.client_id
                    WHERE c_phone.user_id = :user_id_phone
                    ORDER BY cp.phone_id DESC
                    LIMIT 1
                ) AS phone,
                (
                    SELECT ct.telegram
                    FROM clients c_tg
                    INNER JOIN clients_telegram ct ON ct.client_id = c_tg.client_id
                    WHERE c_tg.user_id = :user_id_tg
                    ORDER BY ct.telegram_id DESC
                    LIMIT 1
                ) AS telegram
        ");
        $query_contacts->bindParam(':user_id_phone', $user_id, PDO::PARAM_INT);
        $query_contacts->bindParam(':user_id_tg', $user_id, PDO::PARAM_INT);
        $query_contacts->execute();
        $contacts_row = $query_contacts->fetch(PDO::FETCH_ASSOC);
        if ($contacts_row) {
            if (isset($contacts_row['phone'])) {
                $target_user_phone = trim((string)$contacts_row['phone']);
            }
            if (isset($contacts_row['telegram'])) {
                $target_user_telegram = trim((string)$contacts_row['telegram']);
            }
        }

        $query_course_name = $connection->prepare("
            SELECT course_name
            FROM spr_courses_name
            WHERE id = :course_id
            LIMIT 1
        ");
        $query_sync = $connection->prepare("
            CALL insert_new_string(
                :last_name,
                :first_name,
                :patronymic,
                :email,
                :phone,
                :telegram,
                '',
                '',
                :product_name,
                'Купил',
                '',
                'NOW',
                :user_id
            )
        ");

        foreach ($assigned_courses as $course_item) {
            $course_id = isset($course_item['course_id']) ? intval($course_item['course_id']) : 0;
            if ($course_id <= 0) {
                continue;
            }

            $query_course_name->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $query_course_name->execute();
            $course_row = $query_course_name->fetch(PDO::FETCH_ASSOC);
            if (!$course_row || !isset($course_row['course_name'])) {
                continue;
            }

            $product_name = trim($course_row['course_name']);
            if ($product_name === '') {
                continue;
            }

            $query_sync->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $query_sync->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $query_sync->bindParam(':patronymic', $patronymic, PDO::PARAM_STR);
            $query_sync->bindParam(':email', $target_user_email, PDO::PARAM_STR);
            $query_sync->bindParam(':phone', $target_user_phone, PDO::PARAM_STR);
            $query_sync->bindParam(':telegram', $target_user_telegram, PDO::PARAM_STR);
            $query_sync->bindParam(':product_name', $product_name, PDO::PARAM_STR);
            $query_sync->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $query_sync->execute();
            $query_sync->fetchAll();
            $query_sync->closeCursor();
        }
    }

    $connection->commit();

    // --- Журнал audit: сравнение «до» и «после» по фактическим данным в БД после commit ---
    // Даты в event_data — в формате ДД.ММ.ГГГГ; «бесконечный» дедлайн полномочия в БД — 2099-12-31
    $format_date_for_audit = function ($ymd_or_datetime) {
        $s = trim((string)$ymd_or_datetime);
        if ($s === '') {
            return '';
        }
        $d = strlen($s) >= 10 ? substr($s, 0, 10) : $s;
        if ($d === '2099-12-31') {
            return 'без ограничения по сроку';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
            return $m[3].'.'.$m[2].'.'.$m[1];
        }
        return $d;
    };

    // Текущее состояние полномочий после сохранения (источник истины для «после»)
    $stmt_new_perm = $connection->prepare("
        SELECT up.permition_id, up.deadline, sprp.permition_name, sprp.menu_item_name
        FROM users_permitions up
        INNER JOIN spr_permitions sprp ON sprp.permition_id = up.permition_id
        WHERE up.user_id = :user_id AND sprp.permition_group = :user_group
    ");
    $stmt_new_perm->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_new_perm->bindParam(':user_group', $user_group, PDO::PARAM_STR);
    $stmt_new_perm->execute();
    $new_perm_deadline = array();
    $new_perm_label = array();
    while ($nrow = $stmt_new_perm->fetch(PDO::FETCH_ASSOC)) {
        $pid = intval($nrow['permition_id']);
        if ($pid <= 0) {
            continue;
        }
        $new_perm_deadline[$pid] = $normalize_deadline(isset($nrow['deadline']) ? $nrow['deadline'] : '');
        $nlabel = trim(isset($nrow['menu_item_name']) ? $nrow['menu_item_name'] : '');
        if ($nlabel === '') {
            $nlabel = trim(isset($nrow['permition_name']) ? $nrow['permition_name'] : '');
        }
        $new_perm_label[$pid] = $nlabel !== '' ? $nlabel : ('permition_id='.$pid);
    }

    // Текущее состояние доступов к курсам после сохранения
    $new_courses_state = array();
    $stmt_new_courses = $connection->prepare("
        SELECT upc.course_id,
               DATE_FORMAT(upc.available_until, '%Y-%m-%d') AS available_until,
               scn.course_name
        FROM users_premited_courses upc
        INNER JOIN spr_courses_name scn ON scn.id = upc.course_id
        WHERE upc.user_id = :user_id
    ");
    $stmt_new_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_new_courses->execute();
    while ($nrow = $stmt_new_courses->fetch(PDO::FETCH_ASSOC)) {
        $cid = intval($nrow['course_id']);
        if ($cid <= 0) {
            continue;
        }
        $new_courses_state[$cid] = array(
            'available_until' => trim(isset($nrow['available_until']) ? $nrow['available_until'] : ''),
            'course_name' => trim(isset($nrow['course_name']) ? $nrow['course_name'] : '')
        );
    }

    // Разница по полномочиям: добавление / снятие / смена срока
    $perm_audit_lines = array();
    $perm_ids_union = array_unique(array_merge(array_keys($prev_perm_deadline), array_keys($new_perm_deadline)));
    sort($perm_ids_union);
    foreach ($perm_ids_union as $perm_id) {
        $in_prev = isset($prev_perm_deadline[$perm_id]);
        $in_new = isset($new_perm_deadline[$perm_id]);
        $label_prev = isset($prev_perm_label[$perm_id]) ? $prev_perm_label[$perm_id] : (isset($new_perm_label[$perm_id]) ? $new_perm_label[$perm_id] : ('permition_id='.$perm_id));
        if (!$in_prev && $in_new) {
            $perm_audit_lines[] = 'Добавлено полномочие: '.$label_prev.', срок: '.$format_date_for_audit($new_perm_deadline[$perm_id]);
        } elseif ($in_prev && !$in_new) {
            $perm_audit_lines[] = 'Снято полномочие: '.$label_prev.' (был срок: '.$format_date_for_audit($prev_perm_deadline[$perm_id]).')';
        } elseif ($in_prev && $in_new && $prev_perm_deadline[$perm_id] !== $new_perm_deadline[$perm_id]) {
            $perm_audit_lines[] = 'Изменён срок полномочия «'.$label_prev.'»: было '.$format_date_for_audit($prev_perm_deadline[$perm_id]).', стало '.$format_date_for_audit($new_perm_deadline[$perm_id]);
        }
    }

    // Разница по курсам: выдача / отзыв / смена даты доступа
    $course_audit_lines = array();
    $course_ids_union = array_unique(array_merge(array_keys($prev_courses_state), array_keys($new_courses_state)));
    sort($course_ids_union);
    foreach ($course_ids_union as $course_id) {
        $in_prev_c = isset($prev_courses_state[$course_id]);
        $in_new_c = isset($new_courses_state[$course_id]);
        $cname = '';
        if ($in_prev_c && $prev_courses_state[$course_id]['course_name'] !== '') {
            $cname = $prev_courses_state[$course_id]['course_name'];
        } elseif ($in_new_c && $new_courses_state[$course_id]['course_name'] !== '') {
            $cname = $new_courses_state[$course_id]['course_name'];
        } else {
            $cname = 'course_id='.$course_id;
        }
        if (!$in_prev_c && $in_new_c) {
            $course_audit_lines[] = 'Добавлен доступ к курсу «'.$cname.'» до '.$format_date_for_audit($new_courses_state[$course_id]['available_until']);
        } elseif ($in_prev_c && !$in_new_c) {
            $course_audit_lines[] = 'Снят доступ к курсу «'.$cname.'» (дата доступа была '.$format_date_for_audit($prev_courses_state[$course_id]['available_until']).')';
        } elseif ($in_prev_c && $in_new_c && $prev_courses_state[$course_id]['available_until'] !== $new_courses_state[$course_id]['available_until']) {
            $course_audit_lines[] = 'Изменена дата доступа к курсу «'.$cname.'»: было '.$format_date_for_audit($prev_courses_state[$course_id]['available_until']).', стало '.$format_date_for_audit($new_courses_state[$course_id]['available_until']);
        }
    }

    // Запись только при реальных отличиях; user_login в audit — администратор из сессии (кто выполнил действие)
    if ((count($perm_audit_lines) > 0 || count($course_audit_lines) > 0) && isset($_SESSION['current_user_login'])) {
        $target_username = isset($user['username']) ? trim($user['username']) : '';
        $audit_event_type = "Изменение полномочий пользователя администратором";
        $audit_event_data = "Login изменяемого пользователя: ".$user_login."\n";
        $audit_event_data .= "Имя: ".$target_username."\n";
        if (count($perm_audit_lines) > 0) {
            $audit_event_data .= "\nПолномочия:\n".implode("\n", $perm_audit_lines)."\n";
        }
        if (count($course_audit_lines) > 0) {
            $audit_event_data .= "\nДоступ к учебным курсам:\n".implode("\n", $course_audit_lines)."\n";
        }
        try {
            // operation_type и структура event_data согласованы с queries/update_user.php
            $sql_audit =
            "INSERT
                INTO audit
                (
                    user_login,
                    operation_type,
                    event_data
                ) VALUES (
                    '".$_SESSION['current_user_login']."',
                    '".$audit_event_type."',
                    '".str_replace('"', '\\"', str_replace("'", "\\'", $audit_event_data))."'
                )
            ";
            $query_audit = $connection->prepare($sql_audit);
            $query_audit->execute();
        } catch (PDOException $e) {
        }
    }

    $to_email = isset($user['email']) ? trim($user['email']) : '';
    $user_display_name = isset($user['username']) ? trim($user['username']) : '';
    if ($has_courses_permission && count($newly_added_course_ids) > 0 && $to_email !== '') {
        $audit_ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $user_login;
        $email_from = EML_EMAIL_FROM;
        $pass_from = EML_PASSWORD;
        $name_from = EML_NAME_FROM;

        foreach ($newly_added_course_ids as $new_course_id) {
            $q_course_name = $connection->prepare("SELECT course_name FROM spr_courses_name WHERE id = :course_id LIMIT 1");
            $q_course_name->bindParam(':course_id', $new_course_id, PDO::PARAM_INT);
            $q_course_name->execute();
            $course_row = $q_course_name->fetch(PDO::FETCH_ASSOC);
            if (!$course_row || !isset($course_row['course_name']) || trim($course_row['course_name']) === '') {
                continue;
            }
            $user_product = trim($course_row['course_name']);

            $mail = new PHPMailer(true);
            try {
                $mail->Host = EML_HOST;
                $mail->Port = EML_PORT;
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->SMTPAuth = true;
                $mail->SMTPDebug = 0;
                $mail->SMTPSecure = 'ssl';
                $mail->SMTPAutoTLS = false;
                $mail->Username = $email_from;
                $mail->Password = $pass_from;
                $mail->setFrom($email_from, $name_from);
                $mail->addAddress($to_email);

                $subject = "Доступ к образовательным материалам Лаборатории права Майи Саблиной";
                $body = "<p>".$user_display_name.", добрый день!</p>
                            <p>Доступ к образовательным материалам &laquo;<b>".$user_product."</b>&raquo; открыт!</p>
                            <p>Ознакомиться с материалами вы можете через личный кабинет <a href='https://msll-ip.ru/' target='_blank'>https://msll-ip.ru/</a>.</p>
                            <p>Приятного просмотра!</p>
                            <br/>
                            <br/>
                            <br/>
                            С заботой,<br/>
                            команда Лаборатории права Майи Саблиной<br/>
                            +7 (995) 787-95-77<br/>
                            info@msablina.ru<br/>
                            <a href='http://www.msablina.ru/' target='_blank'>www.msablina.ru</a><br/>
                            ";
                $mail->Subject = $subject;
                $mail->msgHTML($body);
                $mail->send();

                $event_data = "e-mail: ".$to_email."\nСуть письма: Уведомление о добавлении продкута ".$user_product;
                write_log_update_user_permissions($audit_ref, "Отправлено письмо", $event_data);
            } catch (\Exception $e) {
                // отправка не удалась — в аудит по факту отправки не пишем
            }
        }
    }

    echo json_encode(array('status' => 'ok'));
} catch (PDOException $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

function write_log_update_user_permissions($in_user_login, $in_operation_type, $in_event_data) {
    include(__DIR__ . '/../config.php');
    try {
        $login_esc = str_replace("'", "''", (string)$in_user_login);
        $type_esc = str_replace("'", "''", (string)$in_operation_type);
        $data_esc = str_replace("'", "''", (string)$in_event_data);
        $sql_audit =
        "INSERT
            INTO audit
            (
                user_login,
                operation_type,
                event_data
            ) VALUES (
                '".$login_esc."',
                '".$type_esc."',
                '".$data_esc."'
            )
        ";
        $query = $connection->prepare($sql_audit);
        $query->execute();
    } catch (PDOException $e) {
    }

    return 0;
}
?>

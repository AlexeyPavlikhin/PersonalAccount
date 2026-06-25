<?php
/**
 * Сохранение записей о согласиях клиентов (п. 2.1 требований).
 */

/**
 * Безопасное чтение строкового поля из массива входящего запроса.
 */
function client_consent_pick_string($in_array, $in_key) {
    if (!is_array($in_array) || !array_key_exists($in_key, $in_array)) {
        return '';
    }
    $value = $in_array[$in_key];
    if ($value === null) {
        return '';
    }
    if (is_array($value) || is_object($value)) {
        return '';
    }
    return trim((string)$value);
}

/**
 * Нормализация телефона: только цифры; лидирующая «8» при длине >= 11 заменяется на «7».
 */
function client_consent_normalize_phone($in_phone) {
    $phone = preg_replace('/\D+/', '', (string)$in_phone);
    if ($phone !== '' && strlen($phone) >= 11 && $phone[0] === '8') {
        $phone = '7' . substr($phone, 1);
    }
    return $phone;
}

/**
 * Нормализация ника Telegram: ровно один символ «@» в начале строки.
 */
function client_consent_normalize_telegram_nick($in_nick) {
    $nick = trim((string)$in_nick);
    if ($nick === '') {
        return '';
    }
    // Удаляем все «@», затем добавляем один ведущий символ
    $nick = str_replace('@', '', $nick);
    return '@' . $nick;
}

/**
 * Поиск id пользователя ЛК по e-mail или логину.
 */
function client_consent_resolve_user_id($in_connection, $in_email, $in_login) {
    $email = trim((string)$in_email);
    $login = trim((string)$in_login);
    if ($email === '' && $login === '') {
        return null;
    }

    $sql = 'SELECT MIN(u.id) AS u_id FROM users u
            WHERE (:email <> \'\' AND (LOWER(u.email) = LOWER(:email) OR LOWER(u.login) = LOWER(:email)))
               OR (:login <> \'\' AND (LOWER(u.login) = LOWER(:login) OR LOWER(u.email) = LOWER(:login)))';
    $query = $in_connection->prepare($sql);
    $query->bindValue(':email', $email, PDO::PARAM_STR);
    $query->bindValue(':login', $login, PDO::PARAM_STR);
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row || !isset($row['u_id']) || $row['u_id'] === null || $row['u_id'] === '') {
        return null;
    }
    return intval($row['u_id']);
}

/**
 * Сохраняет факт согласия в таблицу client_consents.
 *
 * @return int|null id новой записи или null при ошибке
 */
function client_consent_save_from_payment_event(
    $in_connection,
    $in_payload,
    $in_client_first_name,
    $in_client_last_name,
    $in_client_patronymic,
    $in_user_login,
    $in_user_email,
    $in_info_source = ''
) {
    $user_id = client_consent_resolve_user_id($in_connection, $in_user_email, $in_user_login);

    $phone = client_consent_normalize_phone(client_consent_pick_string($in_payload, 'Phone'));
    $telegram_nick = client_consent_normalize_telegram_nick(client_consent_pick_string($in_payload, 'Name_3'));
    $agree_pers_data = client_consent_pick_string($in_payload, 'AgreePersData');
    $agree_mailings = client_consent_pick_string($in_payload, 'AgreeMailings');
    $info_source = client_consent_pick_string($in_payload, 'InfoSource');
    if ($info_source === '' && trim((string)$in_info_source) !== '') {
        $info_source = trim((string)$in_info_source);
    }
    $client_ip_address = client_consent_pick_string($in_payload, 'ClientIpAddress');

    $sql = 'INSERT INTO client_consents (
                recorded_at,
                user_id,
                client_last_name,
                client_first_name,
                client_patronymic,
                user_login,
                user_email,
                phone,
                telegram_nick,
                agree_pers_data,
                agree_mailings,
                info_source,
                client_ip_address
            ) VALUES (
                NOW(),
                :user_id,
                :client_last_name,
                :client_first_name,
                :client_patronymic,
                :user_login,
                :user_email,
                :phone,
                :telegram_nick,
                :agree_pers_data,
                :agree_mailings,
                :info_source,
                :client_ip_address
            )';

    $query = $in_connection->prepare($sql);
    if ($user_id === null) {
        $query->bindValue(':user_id', null, PDO::PARAM_NULL);
    } else {
        $query->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    }
    $query->bindValue(':client_last_name', (string)$in_client_last_name, PDO::PARAM_STR);
    $query->bindValue(':client_first_name', (string)$in_client_first_name, PDO::PARAM_STR);
    $query->bindValue(':client_patronymic', (string)$in_client_patronymic, PDO::PARAM_STR);
    $query->bindValue(':user_login', (string)$in_user_login, PDO::PARAM_STR);
    $query->bindValue(':user_email', (string)$in_user_email, PDO::PARAM_STR);
    $query->bindValue(':phone', $phone, PDO::PARAM_STR);
    $query->bindValue(':telegram_nick', $telegram_nick, PDO::PARAM_STR);
    $query->bindValue(':agree_pers_data', $agree_pers_data, PDO::PARAM_STR);
    $query->bindValue(':agree_mailings', $agree_mailings, PDO::PARAM_STR);
    $query->bindValue(':info_source', $info_source, PDO::PARAM_STR);
    $query->bindValue(':client_ip_address', $client_ip_address, PDO::PARAM_STR);
    $query->execute();

    $new_id = intval($in_connection->lastInsertId());
    return $new_id > 0 ? $new_id : null;
}

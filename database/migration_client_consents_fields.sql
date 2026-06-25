-- Рефакторинг таблицы client_consents: id, раздельные поля ФИО

-- Переименование consent_id -> id (если таблица уже создана по старой схеме)
SET @has_consent_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'client_consents'
    AND COLUMN_NAME = 'consent_id'
);
SET @sql_rename_pk := IF(
  @has_consent_id > 0,
  'ALTER TABLE `client_consents` CHANGE COLUMN `consent_id` `id` int NOT NULL AUTO_INCREMENT',
  'SELECT 1'
);
PREPARE stmt_rename_pk FROM @sql_rename_pk;
EXECUTE stmt_rename_pk;
DEALLOCATE PREPARE stmt_rename_pk;

-- Добавление полей ФИО (если ещё нет)
SET @has_client_last_name := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'client_consents'
    AND COLUMN_NAME = 'client_last_name'
);
SET @sql_add_names := IF(
  @has_client_last_name = 0,
  'ALTER TABLE `client_consents`
     ADD COLUMN `client_last_name` varchar(255) NOT NULL DEFAULT \'\' AFTER `user_id`,
     ADD COLUMN `client_first_name` varchar(255) NOT NULL DEFAULT \'\' AFTER `client_last_name`,
     ADD COLUMN `client_patronymic` varchar(255) NOT NULL DEFAULT \'\' AFTER `client_first_name`',
  'SELECT 1'
);
PREPARE stmt_add_names FROM @sql_add_names;
EXECUTE stmt_add_names;
DEALLOCATE PREPARE stmt_add_names;

-- Перенос данных из user_name (формат «Фамилия Имя») в новые поля
SET @has_user_name := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'client_consents'
    AND COLUMN_NAME = 'user_name'
);
SET @sql_migrate_names := IF(
  @has_user_name > 0,
  'UPDATE `client_consents`
      SET `client_last_name` = TRIM(SUBSTRING_INDEX(`user_name`, \' \', 1)),
          `client_first_name` = TRIM(IF(LOCATE(\' \', `user_name`) > 0, SUBSTRING(`user_name`, LOCATE(\' \', `user_name`) + 1), \'\')),
          `client_patronymic` = \'\'
    WHERE `user_name` <> \'\'',
  'SELECT 1'
);
PREPARE stmt_migrate_names FROM @sql_migrate_names;
EXECUTE stmt_migrate_names;
DEALLOCATE PREPARE stmt_migrate_names;

-- Удаление устаревшего поля user_name
SET @sql_drop_user_name := IF(
  @has_user_name > 0,
  'ALTER TABLE `client_consents` DROP COLUMN `user_name`',
  'SELECT 1'
);
PREPARE stmt_drop_user_name FROM @sql_drop_user_name;
EXECUTE stmt_drop_user_name;
DEALLOCATE PREPARE stmt_drop_user_name;

-- Обновление отчёта «Согласия на маркетинговые рассылки»
UPDATE `spr_reports`
SET
  `data_sql` = 'SELECT id, recorded_at, client_last_name, client_first_name, client_patronymic, user_login, user_email, phone, telegram_nick, agree_pers_data, agree_mailings, info_source, client_ip_address FROM client_consents',
  `columns_json` = '[{"field":"recorded_at","label":"Дата и время","sortable":true,"filterable":true,"type":"datetime","width_percent":9},{"field":"client_last_name","label":"Фамилия","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"client_first_name","label":"Имя","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"client_patronymic","label":"Отчество","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"user_login","label":"Логин","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"user_email","label":"E-mail","sortable":true,"filterable":true,"type":"text","width_percent":11},{"field":"phone","label":"Телефон","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"telegram_nick","label":"Ник в Telegram","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"agree_pers_data","label":"Согласие на обработку ПД","sortable":true,"filterable":true,"type":"text","width_percent":9},{"field":"agree_mailings","label":"Согласие на рассылки","sortable":true,"filterable":true,"type":"text","width_percent":9},{"field":"info_source","label":"Источник информации","sortable":true,"filterable":true,"type":"text","width_percent":10},{"field":"client_ip_address","label":"IP клиента","sortable":true,"filterable":true,"type":"text","width_percent":7}]'
WHERE `report_code` = 'marketing_consents';

-- Фиксация согласий на маркетинговые рассылки и раздел «Отчетность»

CREATE TABLE IF NOT EXISTS `client_consents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  `client_last_name` varchar(255) NOT NULL DEFAULT '',
  `client_first_name` varchar(255) NOT NULL DEFAULT '',
  `client_patronymic` varchar(255) NOT NULL DEFAULT '',
  `user_login` varchar(255) NOT NULL DEFAULT '',
  `user_email` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(64) NOT NULL DEFAULT '',
  `telegram_nick` varchar(255) NOT NULL DEFAULT '',
  `agree_pers_data` varchar(64) NOT NULL DEFAULT '',
  `agree_mailings` varchar(64) NOT NULL DEFAULT '',
  `info_source` varchar(512) NOT NULL DEFAULT '',
  `client_ip_address` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_client_consents_recorded_at` (`recorded_at`),
  KEY `idx_client_consents_user_email` (`user_email`),
  KEY `idx_client_consents_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `spr_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `report_code` varchar(128) NOT NULL,
  `report_name` varchar(256) NOT NULL,
  `report_description` text,
  `data_sql` text NOT NULL,
  `columns_json` mediumtext NOT NULL,
  `default_sort_field` varchar(128) NOT NULL DEFAULT '',
  `default_sort_direction` varchar(4) NOT NULL DEFAULT 'DESC',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `ux_spr_reports_code` (`report_code`),
  KEY `idx_spr_reports_active_sort` (`is_active`, `sort`, `report_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `users_permitted_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `report_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_users_permitted_reports` (`user_id`, `report_id`),
  KEY `idx_users_permitted_reports_report` (`report_id`),
  CONSTRAINT `users_permitted_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `users_permitted_reports_ibfk_2` FOREIGN KEY (`report_id`) REFERENCES `spr_reports` (`report_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `spr_permitions` (`permition_name`, `sort`, `permition_group`, `menu_item_name`)
SELECT 'reports', 50, 'operator', 'Отчетность'
WHERE NOT EXISTS (
  SELECT 1 FROM `spr_permitions` WHERE `permition_name` = 'reports'
);

INSERT INTO `spr_reports` (
  `report_code`,
  `report_name`,
  `report_description`,
  `data_sql`,
  `columns_json`,
  `default_sort_field`,
  `default_sort_direction`,
  `is_active`,
  `sort`
)
SELECT
  'marketing_consents',
  'Согласия на маркетинговые рассылки',
  'Журнал согласий клиентов на обработку персональных данных и маркетинговые рассылки, полученных через внешние сайты.',
  'SELECT id, recorded_at, client_last_name, client_first_name, client_patronymic, user_login, user_email, phone, telegram_nick, agree_pers_data, agree_mailings, info_source, client_ip_address FROM client_consents',
  '[{"field":"recorded_at","label":"Дата и время","sortable":true,"filterable":true,"type":"datetime","width_percent":9},{"field":"client_last_name","label":"Фамилия","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"client_first_name","label":"Имя","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"client_patronymic","label":"Отчество","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"user_login","label":"Логин","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"user_email","label":"E-mail","sortable":true,"filterable":true,"type":"text","width_percent":11},{"field":"phone","label":"Телефон","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"telegram_nick","label":"Ник в Telegram","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"agree_pers_data","label":"Согласие на обработку ПД","sortable":true,"filterable":true,"type":"text","width_percent":9},{"field":"agree_mailings","label":"Согласие на рассылки","sortable":true,"filterable":true,"type":"text","width_percent":9},{"field":"info_source","label":"Источник информации","sortable":true,"filterable":true,"type":"text","width_percent":10},{"field":"client_ip_address","label":"IP клиента","sortable":true,"filterable":true,"type":"text","width_percent":7}]',
  'recorded_at',
  'DESC',
  1,
  10
WHERE NOT EXISTS (
  SELECT 1 FROM `spr_reports` WHERE `report_code` = 'marketing_consents'
);

-- Обновление конфигурации для уже созданных записей (п. 3.9, 4.1)
UPDATE `spr_reports`
SET
  `data_sql` = 'SELECT id, recorded_at, client_last_name, client_first_name, client_patronymic, user_login, user_email, phone, telegram_nick, agree_pers_data, agree_mailings, info_source, client_ip_address FROM client_consents',
  `columns_json` = '[{"field":"recorded_at","label":"Дата и время","sortable":true,"filterable":true,"type":"datetime","width_percent":9},{"field":"client_last_name","label":"Фамилия","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"client_first_name","label":"Имя","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"client_patronymic","label":"Отчество","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"user_login","label":"Логин","sortable":true,"filterable":true,"type":"text","width_percent":7},{"field":"user_email","label":"E-mail","sortable":true,"filterable":true,"type":"text","width_percent":11},{"field":"phone","label":"Телефон","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"telegram_nick","label":"Ник в Telegram","sortable":true,"filterable":true,"type":"text","width_percent":8},{"field":"agree_pers_data","label":"Согласие на обработку ПД","sortable":true,"filterable":true,"type":"text","width_percent":9},{"field":"agree_mailings","label":"Согласие на рассылки","sortable":true,"filterable":true,"type":"text","width_percent":9},{"field":"info_source","label":"Источник информации","sortable":true,"filterable":true,"type":"text","width_percent":10},{"field":"client_ip_address","label":"IP клиента","sortable":true,"filterable":true,"type":"text","width_percent":7}]',
  `default_sort_field` = 'recorded_at',
  `default_sort_direction` = 'DESC'
WHERE `report_code` = 'marketing_consents';

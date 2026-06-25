CREATE TABLE IF NOT EXISTS `document_templates` (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `template_code` varchar(128) NOT NULL,
  `template_name` varchar(256) NOT NULL,
  `template_category` varchar(256) DEFAULT NULL,
  `template_description` text,
  `template_url` varchar(1024) NOT NULL,
  `field_map_json` mediumtext NOT NULL,
  `filter_tags_json` mediumtext,
  `cache_key_field` varchar(128) DEFAULT 'inn',
  `cache_fields_json` mediumtext,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `ux_document_templates_code` (`template_code`),
  KEY `idx_document_templates_active_sort` (`is_active`, `sort`, `template_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `document_template_fields` (
  `field_id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `field_code` varchar(128) NOT NULL,
  `field_label` varchar(256) NOT NULL,
  `field_type` varchar(64) NOT NULL DEFAULT 'text',
  `placeholder` varchar(512) DEFAULT NULL,
  `default_value` varchar(512) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `data_source` varchar(64) NOT NULL DEFAULT 'manual',
  `source_field_code` varchar(128) DEFAULT NULL,
  `sort` int NOT NULL DEFAULT '100',
  PRIMARY KEY (`field_id`),
  UNIQUE KEY `ux_document_template_fields_code` (`template_id`, `field_code`),
  KEY `idx_document_template_fields_sort` (`template_id`, `sort`),
  CONSTRAINT `document_template_fields_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `document_templates` (`template_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `document_templates`
  ADD COLUMN IF NOT EXISTS `cache_key_field` varchar(128) DEFAULT 'inn' AFTER `filter_tags_json`,
  ADD COLUMN IF NOT EXISTS `cache_fields_json` mediumtext AFTER `cache_key_field`;

-- Кэш полей формы без привязки к id шаблона (требования п. 21–25)
CREATE TABLE IF NOT EXISTS `document_field_cache` (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `cache_key_field` varchar(128) NOT NULL,
  `cache_key_value` varchar(256) NOT NULL,
  `cached_data_json` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_id`),
  KEY `idx_document_field_cache_key` (`cache_key_field`, `cache_key_value`),
  KEY `idx_document_field_cache_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Перенос данных из устаревших таблиц (если уже были созданы ранее)
INSERT INTO `document_field_cache` (`cache_key_field`, `cache_key_value`, `cached_data_json`, `updated_at`)
SELECT
  'inn',
  ca.inn,
  JSON_OBJECT(
    'checking_account', ca.checking_account,
    'email', IFNULL(ca.email, ''),
    'phone', IFNULL(ca.phone, '')
  ),
  ca.updated_at
FROM `counterparty_accounts` ca
WHERE ca.inn <> '' AND ca.checking_account <> ''
  AND NOT EXISTS (
    SELECT 1 FROM `document_field_cache` dfc
    WHERE dfc.cache_key_field = 'inn' AND dfc.cache_key_value = ca.inn
      AND JSON_EXTRACT(dfc.cached_data_json, '$.checking_account') = ca.checking_account
  );

INSERT INTO `document_field_cache` (`cache_key_field`, `cache_key_value`, `cached_data_json`, `updated_at`)
SELECT
  'inn',
  ctb.inn,
  JSON_OBJECT('bik', ctb.bik),
  ctb.updated_at
FROM `counterparty_template_bik` ctb
WHERE ctb.inn <> '' AND ctb.bik <> ''
  AND NOT EXISTS (
    SELECT 1 FROM `document_field_cache` dfc
    WHERE dfc.cache_key_field = 'inn' AND dfc.cache_key_value = ctb.inn
      AND JSON_EXTRACT(dfc.cached_data_json, '$.bik') = ctb.bik
  );

DROP TABLE IF EXISTS `counterparty_template_bik`;
DROP TABLE IF EXISTS `counterparty_accounts`;

-- Настройка кэшируемых полей для типовых шаблонов
UPDATE `document_templates`
SET
  `cache_key_field` = 'inn',
  `cache_fields_json` = '["bik","checking_account","email","phone"]'
WHERE `template_code` IN ('legal_services_agreement_demo', 'legal_services_agreement_linki', 'all_fields_debug');

UPDATE `document_templates`
SET `registry_role` = 'contract'
WHERE `template_code` IN ('legal_services_agreement_demo', 'legal_services_agreement_linki');

UPDATE `document_templates`
SET `registry_role` = 'none'
WHERE `template_code` = 'all_fields_debug';

INSERT INTO `spr_permitions` (`permition_name`, `sort`, `permition_group`, `menu_item_name`)
SELECT 'documents', 45, 'operator', 'Генерация документов'
WHERE NOT EXISTS (
  SELECT 1
  FROM `spr_permitions`
  WHERE `permition_name` = 'documents'
);

INSERT INTO `document_templates` (
  `template_code`,
  `template_name`,
  `template_category`,
  `template_description`,
  `template_url`,
  `field_map_json`,
  `filter_tags_json`,
  `is_active`,
  `sort`
)
SELECT
  'legal_services_agreement_demo',
  'Договор оказания юридических услуг (демо)',
  'Договоры',
  'Демонстрационный шаблон договора с плейсхолдерами ${field_code} для раздела генерации документов.',
  'http://msll-dev/requirements/docs_temlpates/demo_legal_services_agreement.docx',
  '{}',
  '["договор","юридические услуги","demo"]',
  1,
  10
WHERE NOT EXISTS (
  SELECT 1
  FROM `document_templates`
  WHERE `template_code` = 'legal_services_agreement_demo'
);

-- field_map_json: только особый маппинг; {} — все ключи form_data/enrich подставляются как ${field_code}
UPDATE `document_templates`
SET `field_map_json` = '{}'
WHERE `template_code` IN ('legal_services_agreement_demo', 'legal_services_agreement_linki', 'all_fields_debug');

UPDATE `document_templates`
SET
  `template_description` = 'Демонстрационный шаблон договора с плейсхолдерами ${field_code} для раздела генерации документов.',
  `template_url` = 'http://msll-dev/requirements/docs_temlpates/demo_legal_services_agreement.docx'
WHERE `template_code` = 'legal_services_agreement_demo';

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'opf_short', 'ОПФ (краткое)', 'text', 'Заполняется по DaData', NULL, 0, 'dadata_party', 'opf_short', 32
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'opf_short' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'opf_full', 'ОПФ (полное)', 'text', 'Заполняется по DaData', NULL, 0, 'dadata_party', 'opf_full', 33
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'opf_full' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'contract_date', 'Дата составления договора', 'date', 'Текущая дата подставляется автоматически', NULL, 1, 'manual', 'contract_date', 5
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'contract_date' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'inn', 'ИНН организации', 'text', 'Введите ИНН организации', NULL, 1, 'manual', 'inn', 10
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'inn' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'bik', 'БИК банка', 'text', 'Введите БИК банка', NULL, 1, 'manual', 'bik', 20
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'bik' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'company_name_short_opf', 'Наименование (краткое ОПФ)', 'text', 'Напр.: ООО «Ромашка»', NULL, 0, 'dadata_party', 'company_name_short_opf', 28
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'company_name_short_opf' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'company_name_full_opf', 'Наименование (полное ОПФ)', 'text', 'Напр.: Общество с ограниченной ответственностью «Ромашка»', NULL, 0, 'dadata_party', 'company_name_full_opf', 29
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'company_name_full_opf' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'company_name', 'Наименование организации (основное)', 'text', 'Полное наименование с полным ОПФ; заполняется по DaData', NULL, 1, 'dadata_party', 'company_name', 30
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'company_name' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'signer_name', 'ФИО подписанта', 'text', 'Заполняется по DaData, можно скорректировать', NULL, 1, 'dadata_party', 'signer_name', 40
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'signer_name' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'signer_position', 'Должность подписанта', 'text', 'Заполняется по DaData, можно скорректировать', NULL, 1, 'dadata_party', 'signer_position', 50
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'signer_position' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'signer_basis', 'Основание полномочий', 'text', 'Например: Устава', 'Устава', 1, 'manual', 'signer_basis', 60
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'signer_basis' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'address', 'Адрес', 'textarea', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'address', 70
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'address' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'ogrn', 'ОГРН', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'ogrn', 80
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'ogrn' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'kpp', 'КПП', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'kpp', 90
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'kpp' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'checking_account', 'Расчётный счёт', 'text', 'Введите расчётный счёт', NULL, 1, 'document_cache', 'checking_account', 100
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'checking_account' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'bank_name', 'Банк', 'text', 'Заполняется по DaData Bank', NULL, 1, 'dadata_bank', 'bank_name', 110
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'bank_name' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'corr_account', 'Корреспондентский счёт', 'text', 'Заполняется по DaData Bank', NULL, 1, 'dadata_bank', 'corr_account', 120
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'corr_account' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'email', 'E-mail', 'email', 'Контактный e-mail организации', NULL, 0, 'dadata_party', 'email', 130
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'email' AND `template_id` = `document_templates`.`template_id`
  );

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'phone', 'Телефон', 'tel', 'Контактный телефон организации', NULL, 0, 'dadata_party', 'phone', 140
FROM `document_templates`
WHERE `template_code` = 'legal_services_agreement_demo'
  AND NOT EXISTS (
    SELECT 1 FROM `document_template_fields`
    WHERE `field_code` = 'phone' AND `template_id` = `document_templates`.`template_id`
  );

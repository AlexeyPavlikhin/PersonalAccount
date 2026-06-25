-- Реестр оформленных договоров, спецификаций и счетов (требования п. 10–12, 77–81).
-- Расширение document_templates: registry_role, table_blocks_json.

-- MySQL не поддерживает ADD COLUMN IF NOT EXISTS; добавляем колонки идемпотентно через PREPARE.
-- Имя prepared statement без «@»; текст запроса — в пользовательской переменной @msll_sql.
SET @msll_sql := IF(
  (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'document_templates'
      AND COLUMN_NAME = 'registry_role'
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `document_templates` ADD COLUMN `registry_role` varchar(32) NOT NULL DEFAULT ''none'' AFTER `cache_fields_json`'
);
PREPARE msll_stmt FROM @msll_sql;
EXECUTE msll_stmt;
DEALLOCATE PREPARE msll_stmt;

SET @msll_sql := IF(
  (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'document_templates'
      AND COLUMN_NAME = 'table_blocks_json'
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `document_templates` ADD COLUMN `table_blocks_json` mediumtext AFTER `registry_role`'
);
PREPARE msll_stmt FROM @msll_sql;
EXECUTE msll_stmt;
DEALLOCATE PREPARE msll_stmt;

CREATE TABLE IF NOT EXISTS `document_number_counters` (
  `counter_code` varchar(64) NOT NULL,
  `last_value` int NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`counter_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `document_number_counters` (`counter_code`, `last_value`)
SELECT 'contract_seq', 0
WHERE NOT EXISTS (SELECT 1 FROM `document_number_counters` WHERE `counter_code` = 'contract_seq');

INSERT INTO `document_number_counters` (`counter_code`, `last_value`)
SELECT 'invoice_seq', 0
WHERE NOT EXISTS (SELECT 1 FROM `document_number_counters` WHERE `counter_code` = 'invoice_seq');

CREATE TABLE IF NOT EXISTS `document_issued_contracts` (
  `contract_id` int NOT NULL AUTO_INCREMENT,
  `contract_number` varchar(32) NOT NULL,
  `contract_seq` int NOT NULL DEFAULT '0',
  `contract_date` date DEFAULT NULL,
  `subject_short` varchar(512) DEFAULT NULL,
  `counterparty_display` varchar(512) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contract_id`),
  UNIQUE KEY `ux_document_issued_contracts_number` (`contract_number`),
  KEY `idx_document_issued_contracts_date` (`contract_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `document_issued_specifications` (
  `spec_id` int NOT NULL AUTO_INCREMENT,
  `contract_id` int NOT NULL,
  `spec_number` int NOT NULL DEFAULT '0',
  `spec_date` date DEFAULT NULL,
  `invoice_number` int DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `planned_act_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`spec_id`),
  KEY `idx_document_issued_specs_contract` (`contract_id`),
  KEY `idx_document_issued_specs_planned_act` (`planned_act_date`),
  UNIQUE KEY `ux_document_issued_specs_contract_num` (`contract_id`, `spec_number`),
  CONSTRAINT `document_issued_specifications_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `document_issued_contracts` (`contract_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Роли шаблонов для автозаписи в реестр при «Сформировать DOCX»
UPDATE `document_templates`
SET `registry_role` = 'contract'
WHERE `template_code` IN ('legal_services_agreement_demo', 'legal_services_agreement_linki');

UPDATE `document_templates`
SET `registry_role` = 'none'
WHERE `template_code` = 'all_fields_debug';

-- Поле номера договора: редактируемое в форме (превью через API)
UPDATE `document_template_fields` f
INNER JOIN `document_templates` t ON f.template_id = t.template_id
SET f.data_source = 'manual', f.field_label = 'Номер договора'
WHERE f.field_code = 'contract_number' AND f.data_source = 'computed_number';

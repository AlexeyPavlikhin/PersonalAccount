-- Тестовый шаблон: таблица всех field_code → значение (all_fields_debug.docx).
-- Перед применением: python tools/build_all_fields_debug_docx.py

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
  'all_fields_debug',
  'Справочник полей (тест)',
  'Служебные',
  'Таблица «имя поля — значение» для проверки подстановки всех field_code после enrich.',
  'http://msll-dev/requirements/docs_temlpates/all_fields_debug.docx',
  '{}',
  '["тест","отладка","поля"]',
  1,
  5
WHERE NOT EXISTS (
  SELECT 1 FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
);

UPDATE `document_templates`
SET
  `template_name` = 'Справочник полей (тест)',
  `template_description` = 'Таблица «имя поля — значение» для проверки подстановки всех field_code после enrich.',
  `template_url` = 'http://msll-dev/requirements/docs_temlpates/all_fields_debug.docx',
  `field_map_json` = '{}',
  `filter_tags_json` = '["тест","отладка","поля"]',
  `registry_role` = 'none',
  `table_blocks_json` = '{"demo_services":{"marker_row":"${#demo_services}","columns":["demo_service_name","demo_service_price"]}}',
  `is_active` = 1,
  `sort` = 5
WHERE `template_code` = 'all_fields_debug';

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'contract_number', 'Номер договора', 'text', 'Превью / ручной ввод', NULL, 0, 'manual', 'contract_number', 1
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'contract_number');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'contract_subject_short', 'Краткое описание предмета', 'text', 'Для реестра и DOCX', NULL, 0, 'manual', 'contract_subject_short', 2
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'contract_subject_short');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'demo_services', 'Демо табличного блока', 'table', 'Строки услуг для проверки ${#demo_services}', NULL, 0, 'manual', 'demo_services', 3
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'demo_services');

-- Поля формы — как в демо-шаблоне (достаточно для дозаполнения и enrich)
INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'contract_date', 'Дата составления договора', 'date', 'Текущая дата подставляется автоматически', NULL, 1, 'manual', 'contract_date', 5
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'contract_date');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'inn', 'ИНН организации', 'text', 'Введите ИНН организации', NULL, 1, 'manual', 'inn', 10
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'inn');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'bik', 'БИК банка', 'text', 'Введите БИК банка', NULL, 1, 'manual', 'bik', 20
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'bik');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'opf_short', 'ОПФ (краткое)', 'text', 'Заполняется по DaData', NULL, 0, 'dadata_party', 'opf_short', 32
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'opf_short');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'opf_full', 'ОПФ (полное)', 'text', 'Заполняется по DaData', NULL, 0, 'dadata_party', 'opf_full', 33
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'opf_full');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'company_name_short_opf', 'Наименование (краткое ОПФ)', 'text', 'Напр.: ООО «Ромашка»', NULL, 0, 'dadata_party', 'company_name_short_opf', 28
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'company_name_short_opf');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'company_name_full_opf', 'Наименование (полное ОПФ)', 'text', 'Полное наименование с ОПФ', NULL, 0, 'dadata_party', 'company_name_full_opf', 29
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'company_name_full_opf');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'company_name', 'Наименование организации (основное)', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'company_name', 30
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'company_name');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'signer_name', 'ФИО подписанта', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'signer_name', 40
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'signer_name');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'signer_position', 'Должность подписанта', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'signer_position', 50
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'signer_position');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'signer_basis', 'Основание полномочий', 'text', 'Например: Устава', 'Устава', 1, 'manual', 'signer_basis', 60
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'signer_basis');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'address', 'Адрес', 'textarea', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'address', 70
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'address');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'ogrn', 'ОГРН', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'ogrn', 80
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'ogrn');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'kpp', 'КПП', 'text', 'Заполняется по DaData', NULL, 1, 'dadata_party', 'kpp', 90
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'kpp');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'checking_account', 'Расчётный счёт', 'text', 'Введите расчётный счёт', NULL, 1, 'document_cache', 'checking_account', 100
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'checking_account');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'bank_name', 'Банк', 'text', 'Заполняется по DaData Bank', NULL, 1, 'dadata_bank', 'bank_name', 110
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'bank_name');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'corr_account', 'Корреспондентский счёт', 'text', 'Заполняется по DaData Bank', NULL, 1, 'dadata_bank', 'corr_account', 120
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'corr_account');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'email', 'E-mail', 'email', 'Контактный e-mail', NULL, 0, 'dadata_party', 'email', 130
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'email');

INSERT INTO `document_template_fields` (`template_id`, `field_code`, `field_label`, `field_type`, `placeholder`, `default_value`, `is_required`, `data_source`, `source_field_code`, `sort`)
SELECT `template_id`, 'phone', 'Телефон', 'tel', 'Контактный телефон', NULL, 0, 'dadata_party', 'phone', 140
FROM `document_templates` WHERE `template_code` = 'all_fields_debug'
  AND NOT EXISTS (SELECT 1 FROM `document_template_fields` f JOIN `document_templates` t ON f.template_id = t.template_id WHERE t.template_code = 'all_fields_debug' AND f.field_code = 'phone');

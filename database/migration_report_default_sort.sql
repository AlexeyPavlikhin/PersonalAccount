-- Дефолтная сортировка отчётов в конфигурации spr_reports (вариант A, без hardcode в PHP)

SET @has_default_sort_field := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'spr_reports'
    AND COLUMN_NAME = 'default_sort_field'
);
SET @sql_add_default_sort := IF(
  @has_default_sort_field = 0,
  'ALTER TABLE `spr_reports`
     ADD COLUMN `default_sort_field` varchar(128) NOT NULL DEFAULT \'\' AFTER `columns_json`,
     ADD COLUMN `default_sort_direction` varchar(4) NOT NULL DEFAULT \'DESC\' AFTER `default_sort_field`',
  'SELECT 1'
);
PREPARE stmt_add_default_sort FROM @sql_add_default_sort;
EXECUTE stmt_add_default_sort;
DEALLOCATE PREPARE stmt_add_default_sort;

UPDATE `spr_reports`
SET
  `default_sort_field` = 'recorded_at',
  `default_sort_direction` = 'DESC'
WHERE `report_code` = 'marketing_consents';

UPDATE `spr_reports`
SET
  `default_sort_field` = 'event_datetime',
  `default_sort_direction` = 'DESC'
WHERE `report_code` = 'user_actions_audit';

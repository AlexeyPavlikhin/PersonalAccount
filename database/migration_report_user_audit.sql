-- Отчёт «Аудит действий пользователей» (таблица audit, без поля id)

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
  'user_actions_audit',
  'Аудит действий пользователей',
  'Журнал действий пользователей системы: входы, изменения данных, операции с документами и другие события.',
  'SELECT event_datetime, user_login, operation_type, event_data FROM audit',
  '[{"field":"event_datetime","label":"Дата и время","sortable":true,"filterable":true,"type":"datetime","width_percent":12},{"field":"user_login","label":"Логин","sortable":true,"filterable":true,"type":"text","width_percent":15},{"field":"operation_type","label":"Тип операции","sortable":true,"filterable":true,"type":"text","width_percent":20},{"field":"event_data","label":"Данные события","sortable":true,"filterable":true,"type":"text","width_percent":53}]',
  'event_datetime',
  'DESC',
  1,
  20
WHERE NOT EXISTS (
  SELECT 1 FROM `spr_reports` WHERE `report_code` = 'user_actions_audit'
);

-- Обновление конфигурации для уже созданных записей
UPDATE `spr_reports`
SET
  `report_name` = 'Аудит действий пользователей',
  `report_description` = 'Журнал действий пользователей системы: входы, изменения данных, операции с документами и другие события.',
  `data_sql` = 'SELECT event_datetime, user_login, operation_type, event_data FROM audit',
  `columns_json` = '[{"field":"event_datetime","label":"Дата и время","sortable":true,"filterable":true,"type":"datetime","width_percent":12},{"field":"user_login","label":"Логин","sortable":true,"filterable":true,"type":"text","width_percent":15},{"field":"operation_type","label":"Тип операции","sortable":true,"filterable":true,"type":"text","width_percent":20},{"field":"event_data","label":"Данные события","sortable":true,"filterable":true,"type":"text","width_percent":53}]',
  `default_sort_field` = 'event_datetime',
  `default_sort_direction` = 'DESC',
  `is_active` = 1,
  `sort` = 20
WHERE `report_code` = 'user_actions_audit';

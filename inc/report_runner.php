<?php
/**
 * Выполнение настроенных отчётов из spr_reports с проверкой прав доступа.
 */

/**
 * Нормализует описание колонок отчёта из columns_json.
 * width_percent — ширина колонки в процентах от ширины экрана (п. 3.9).
 */
function report_normalize_columns($in_columns) {
    $result = array();
    if (!is_array($in_columns)) {
        return $result;
    }

    foreach ($in_columns as $column_item) {
        if (!is_array($column_item)) {
            continue;
        }
        // visible=false — колонка не отображается в таблице
        if (array_key_exists('visible', $column_item) && !$column_item['visible']) {
            continue;
        }

        $field = isset($column_item['field']) ? trim((string)$column_item['field']) : '';
        if ($field === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
            continue;
        }

        $normalized = $column_item;
        $normalized['field'] = $field;

        if (array_key_exists('width_percent', $column_item)) {
            $width_percent = floatval($column_item['width_percent']);
            if ($width_percent < 0) {
                $width_percent = 0;
            }
            if ($width_percent > 100) {
                $width_percent = 100;
            }
            $normalized['width_percent'] = $width_percent;
        }

        $result[] = $normalized;
    }

    return $result;
}

/**
 * Проверяет, есть ли у пользователя доступ к разделу «Отчетность».
 */
function report_user_has_reports_section($in_connection, $in_user_id) {
    $sql = "SELECT COUNT(*) AS cnt
            FROM users_permitions up
            INNER JOIN spr_permitions sprp ON sprp.permition_id = up.permition_id
            WHERE up.user_id = :user_id AND sprp.permition_name = 'reports'";
    $query = $in_connection->prepare($sql);
    $query->bindValue(':user_id', intval($in_user_id), PDO::PARAM_INT);
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);
    return $row && intval($row['cnt']) > 0;
}

/**
 * Список отчётов, доступных пользователю.
 */
function report_get_available_for_user($in_connection, $in_user_id) {
    if (!report_user_has_reports_section($in_connection, $in_user_id)) {
        return array();
    }

    $sql = "SELECT
                sr.report_id,
                sr.report_code,
                sr.report_name,
                sr.report_description,
                sr.columns_json,
                sr.sort
            FROM spr_reports sr
            INNER JOIN users_permitted_reports upr ON upr.report_id = sr.report_id
            WHERE sr.is_active = 1 AND upr.user_id = :user_id
            ORDER BY sr.sort, sr.report_name";
    $query = $in_connection->prepare($sql);
    $query->bindValue(':user_id', intval($in_user_id), PDO::PARAM_INT);
    $query->execute();
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $columns = json_decode(isset($row['columns_json']) ? $row['columns_json'] : '[]', true);
        $row['columns'] = report_normalize_columns($columns);
        unset($row['columns_json']);
    }
    unset($row);

    return $rows;
}

/**
 * Загружает конфигурацию отчёта по коду.
 */
function report_load_by_code($in_connection, $in_report_code) {
    $report_code = trim((string)$in_report_code);
    if ($report_code === '') {
        return null;
    }

    $sql = "SELECT report_id, report_code, report_name, report_description, data_sql, columns_json,
                   default_sort_field, default_sort_direction, is_active
            FROM spr_reports
            WHERE report_code = :report_code AND is_active = 1
            LIMIT 1";
    $query = $in_connection->prepare($sql);
    $query->bindValue(':report_code', $report_code, PDO::PARAM_STR);
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $columns = json_decode(isset($row['columns_json']) ? $row['columns_json'] : '[]', true);
    $row['columns'] = report_normalize_columns($columns);
    unset($row['columns_json']);
    return $row;
}

/**
 * Проверяет доступ пользователя к конкретному отчёту.
 */
function report_user_can_view($in_connection, $in_user_id, $in_report_id) {
    if (!report_user_has_reports_section($in_connection, $in_user_id)) {
        return false;
    }

    $sql = "SELECT COUNT(*) AS cnt
            FROM users_permitted_reports
            WHERE user_id = :user_id AND report_id = :report_id";
    $query = $in_connection->prepare($sql);
    $query->bindValue(':user_id', intval($in_user_id), PDO::PARAM_INT);
    $query->bindValue(':report_id', intval($in_report_id), PDO::PARAM_INT);
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);
    return $row && intval($row['cnt']) > 0;
}

/**
 * Белый список полей отчёта из columns_json.
 */
function report_get_allowed_fields($in_columns) {
    $allowed = array();
    if (!is_array($in_columns)) {
        return $allowed;
    }
    foreach ($in_columns as $column_item) {
        if (!is_array($column_item)) {
            continue;
        }
        $field = isset($column_item['field']) ? trim((string)$column_item['field']) : '';
        if ($field !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
            $allowed[] = $field;
        }
    }
    return array_values(array_unique($allowed));
}

/**
 * Определяет фактическую сортировку: явная от клиента или default из spr_reports.
 *
 * @return array{field:string,direction:string}
 */
function report_resolve_sort($in_report, $in_sort_field, $in_sort_direction) {
    $allowed_fields = report_get_allowed_fields(isset($in_report['columns']) ? $in_report['columns'] : array());

    $sort_field = trim((string)$in_sort_field);
    $sort_direction = strtoupper(trim((string)$in_sort_direction)) === 'ASC' ? 'ASC' : 'DESC';
    if ($sort_field !== '' && in_array($sort_field, $allowed_fields, true)) {
        return array(
            'field' => $sort_field,
            'direction' => $sort_direction,
        );
    }

    $default_field = trim(isset($in_report['default_sort_field']) ? (string)$in_report['default_sort_field'] : '');
    $default_direction = strtoupper(trim(isset($in_report['default_sort_direction']) ? (string)$in_report['default_sort_direction'] : 'DESC'));
    if ($default_direction !== 'ASC') {
        $default_direction = 'DESC';
    }
    if ($default_field !== '' && in_array($default_field, $allowed_fields, true)) {
        return array(
            'field' => $default_field,
            'direction' => $default_direction,
        );
    }

    return array(
        'field' => '',
        'direction' => 'DESC',
    );
}

/**
 * Краткое описание отчёта для ответа API.
 */
function report_build_api_meta($in_report) {
    return array(
        'report_id' => intval($in_report['report_id']),
        'report_code' => $in_report['report_code'],
        'report_name' => $in_report['report_name'],
        'report_description' => isset($in_report['report_description']) ? $in_report['report_description'] : '',
        'default_sort_field' => trim(isset($in_report['default_sort_field']) ? (string)$in_report['default_sort_field'] : ''),
        'default_sort_direction' => strtoupper(trim(isset($in_report['default_sort_direction']) ? (string)$in_report['default_sort_direction'] : 'DESC')) === 'ASC' ? 'ASC' : 'DESC',
    );
}

/**
 * Нормализация параметров пейджинации (п. 3.10).
 *
 * @return array{page:int,page_size:int}
 */
function report_normalize_pagination($in_page, $in_page_size) {
    $allowed_sizes = array(25, 50, 100);
    $page = intval($in_page);
    if ($page < 1) {
        $page = 1;
    }

    $page_size = intval($in_page_size);
    if (!in_array($page_size, $allowed_sizes, true)) {
        if ($page_size < 1) {
            $page_size = 50;
        } elseif ($page_size > 100) {
            $page_size = 100;
        } else {
            $page_size = 50;
        }
    }

    return array(
        'page' => $page,
        'page_size' => $page_size,
    );
}

/**
 * Метаданные пейджинации для ответа API.
 */
function report_build_pagination_meta($in_total_rows, $in_page, $in_page_size) {
    $total_rows = max(0, intval($in_total_rows));
    $page_size = max(1, intval($in_page_size));
    $total_pages = $total_rows > 0 ? (int)ceil($total_rows / $page_size) : 0;

    $page = max(1, intval($in_page));
    if ($total_pages > 0 && $page > $total_pages) {
        $page = $total_pages;
    }

    $row_from = 0;
    $row_to = 0;
    if ($total_rows > 0) {
        $row_from = ($page - 1) * $page_size + 1;
        $row_to = min($page * $page_size, $total_rows);
    }

    return array(
        'page' => $page,
        'page_size' => $page_size,
        'total_rows' => $total_rows,
        'total_pages' => $total_pages,
        'row_from' => $row_from,
        'row_to' => $row_to,
    );
}

/**
 * Собирает общие части SQL отчёта: WHERE и ORDER BY.
 *
 * @return array{filtered_sql:string,order_sql:string,params:array,allowed_fields:array}
 */
function report_build_data_query($in_report, $in_sort_field, $in_sort_direction, $in_filters) {
    $base_sql = trim(isset($in_report['data_sql']) ? $in_report['data_sql'] : '');
    if ($base_sql === '') {
        throw new InvalidArgumentException('report_sql_empty');
    }
    if (!preg_match('/^\s*SELECT\s+/i', $base_sql)) {
        throw new InvalidArgumentException('report_sql_not_select');
    }
    if (strpos($base_sql, ';') !== false) {
        throw new InvalidArgumentException('report_sql_semicolon');
    }

    $allowed_fields = report_get_allowed_fields(isset($in_report['columns']) ? $in_report['columns'] : array());
    if (count($allowed_fields) === 0) {
        throw new InvalidArgumentException('report_columns_empty');
    }

    $where_parts = array();
    $params = array();
    if (is_array($in_filters)) {
        foreach ($in_filters as $field_name => $filter_value) {
            $field_name = trim((string)$field_name);
            if (!in_array($field_name, $allowed_fields, true)) {
                continue;
            }
            $filter_value = trim((string)$filter_value);
            if ($filter_value === '') {
                continue;
            }
            $param_name = ':filter_' . $field_name;
            $where_parts[] = '`' . $field_name . '` LIKE ' . $param_name;
            $params[$param_name] = '%' . $filter_value . '%';
        }
    }

    $filtered_sql = $base_sql;
    if (count($where_parts) > 0) {
        if (stripos($filtered_sql, ' WHERE ') !== false) {
            $filtered_sql .= ' AND ' . implode(' AND ', $where_parts);
        } else {
            $filtered_sql .= ' WHERE ' . implode(' AND ', $where_parts);
        }
    }

    $resolved_sort = report_resolve_sort(
        $in_report,
        trim((string)$in_sort_field),
        $in_sort_direction
    );
    $order_sql = '';
    if ($resolved_sort['field'] !== '') {
        $order_sql = ' ORDER BY `' . $resolved_sort['field'] . '` ' . $resolved_sort['direction'];
    }

    return array(
        'filtered_sql' => $filtered_sql,
        'order_sql' => $order_sql,
        'params' => $params,
        'allowed_fields' => $allowed_fields,
    );
}

/**
 * Возвращает общее число строк отчёта с учётом фильтров.
 */
function report_fetch_count($in_connection, $in_report, $in_filters) {
    // ORDER BY для COUNT не нужен — передаём пустую сортировку
    $query_parts = report_build_data_query($in_report, '', '', $in_filters);
    $sql = 'SELECT COUNT(*) AS cnt FROM (' . $query_parts['filtered_sql'] . ') AS report_count_sq';

    $query = $in_connection->prepare($sql);
    foreach ($query_parts['params'] as $param_name => $param_value) {
        $query->bindValue($param_name, $param_value, PDO::PARAM_STR);
    }
    $query->execute();
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row || !isset($row['cnt'])) {
        return 0;
    }
    return intval($row['cnt']);
}

/**
 * Выполняет SQL отчёта с фильтрацией, сортировкой и пейджинацией.
 */
function report_fetch_data($in_connection, $in_report, $in_sort_field, $in_sort_direction, $in_filters, $in_page = 1, $in_page_size = 50) {
    $pagination = report_normalize_pagination($in_page, $in_page_size);
    $page = $pagination['page'];
    $page_size = $pagination['page_size'];
    $offset = ($page - 1) * $page_size;

    $query_parts = report_build_data_query($in_report, $in_sort_field, $in_sort_direction, $in_filters);
    $sql = $query_parts['filtered_sql'] . $query_parts['order_sql'];
    $sql .= ' LIMIT :page_size OFFSET :page_offset';

    $query = $in_connection->prepare($sql);
    foreach ($query_parts['params'] as $param_name => $param_value) {
        $query->bindValue($param_name, $param_value, PDO::PARAM_STR);
    }
    $query->bindValue(':page_size', $page_size, PDO::PARAM_INT);
    $query->bindValue(':page_offset', $offset, PDO::PARAM_INT);
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

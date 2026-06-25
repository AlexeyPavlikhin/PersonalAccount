<?php

/**
 * Заполнение табличных блоков DOCX: маркерная строка ${#block} и клонирование w:tr.
 */

require_once __DIR__ . '/document_template_mapper.php';

/**
 * Извлекает из form_data массивы строк для блоков из table_blocks_json.
 *
 * @return array<string, array<int, array<string, string>>>
 */
function msll_document_extract_table_row_data(array $form_values, array $table_blocks): array
{
    $result = array();

    foreach ($table_blocks as $block_key => $config) {
        $block_code = trim((string) $block_key);
        if ($block_code === '') {
            continue;
        }

        $raw_value = $form_values[$block_code] ?? array();
        if (is_string($raw_value)) {
            $decoded = json_decode($raw_value, true);
            $raw_value = is_array($decoded) ? $decoded : array();
        }

        if (!is_array($raw_value)) {
            continue;
        }

        $rows = array();
        foreach ($raw_value as $row_item) {
            if (!is_array($row_item)) {
                continue;
            }
            $normalized_row = array();
            foreach ($row_item as $column_key => $column_value) {
                $normalized_row[(string) $column_key] = msll_document_normalize_scalar($column_value);
            }
            if (count($normalized_row) > 0) {
                $rows[] = $normalized_row;
            }
        }

        if (count($rows) > 0) {
            $result[$block_code] = $rows;
        }
    }

    return $result;
}

/** Подставляет табличные блоки в XML части документа Word. */
function msll_docx_apply_table_blocks_to_xml(string $xml, array $table_blocks, array $table_row_data): string
{
    if (count($table_blocks) === 0 || count($table_row_data) === 0) {
        return $xml;
    }

    if (!function_exists('msll_docx_normalize_split_placeholders')) {
        require_once __DIR__ . '/docx_form_filler.php';
    }
    $xml = msll_docx_normalize_split_placeholders($xml);

    foreach ($table_blocks as $block_key => $config) {
        $block_code = trim((string) $block_key);
        if ($block_code === '' || !isset($table_row_data[$block_code])) {
            continue;
        }

        $rows = $table_row_data[$block_code];
        if (!is_array($rows) || count($rows) === 0) {
            continue;
        }

        $marker = '${#' . $block_code . '}';
        if (is_array($config) && !empty($config['marker_row'])) {
            $marker = trim((string) $config['marker_row']);
        }

        $columns = array();
        if (is_array($config) && !empty($config['columns']) && is_array($config['columns'])) {
            foreach ($config['columns'] as $column_name) {
                $column_name = trim((string) $column_name);
                if ($column_name !== '') {
                    $columns[] = $column_name;
                }
            }
        }

        $xml = msll_docx_expand_table_block_row($xml, $marker, $rows, $columns);
    }

    return $xml;
}

/**
 * Находит строку таблицы с маркером и размножает её по данным.
 *
 * @param array<int, array<string, string>> $rows
 * @param array<int, string> $columns
 */
function msll_docx_expand_table_block_row(string $xml, string $marker, array $rows, array $columns): string
{
    if ($marker === '' || count($rows) === 0) {
        return $xml;
    }

    if (!preg_match('/<w:tr\b.*?' . preg_quote($marker, '/') . '.*?<\/w:tr>/s', $xml, $match)) {
        return $xml;
    }

    $template_row_xml = (string) $match[0];
    $generated_rows = array();

    foreach ($rows as $row_data) {
        $row_xml = $template_row_xml;
        $row_xml = str_replace($marker, '', $row_xml);

        $column_keys = count($columns) > 0 ? $columns : array_keys($row_data);
        foreach ($column_keys as $column_code) {
            $placeholder = '${' . $column_code . '}';
            $cell_value = msll_document_normalize_scalar($row_data[$column_code] ?? '');
            $row_xml = str_replace(
                $placeholder,
                msll_docx_escape_xml_text($cell_value),
                $row_xml
            );
        }

        $generated_rows[] = $row_xml;
    }

    $replacement = implode('', $generated_rows);

    return str_replace($template_row_xml, $replacement, $xml);
}

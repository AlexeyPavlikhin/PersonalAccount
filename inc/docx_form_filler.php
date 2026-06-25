<?php

/**
 * Заполнение DOCX-шаблона плейсхолдерами ${field_code}.
 * Шаблон — ZIP (OOXML); подстановка выполняется в word/*.xml на сервере.
 */

require_once __DIR__ . '/document_template_mapper.php';

/** Экранирование значения для вставки в XML Word. */
function msll_docx_escape_xml_text(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Собирает разорванные Word-тегами плейсхолдеры вида ${name} в один фрагмент текста.
 * Нужно, если редактор разбил ${company_name} на несколько w:t.
 */
function msll_docx_normalize_split_placeholders(string $xml): string
{
    $pattern = '/\$\{([^}]*)\}/s';
    $offset = 0;

    while (preg_match($pattern, $xml, $matches, PREG_OFFSET_CAPTURE, $offset)) {
        $full_match = $matches[0][0];
        $field_name = $matches[1][0];
        $match_start = (int) $matches[0][1];
        $match_end = $match_start + strlen($full_match);

        if (strpos($full_match, '<') === false) {
            $offset = $match_end;
            continue;
        }

        $plain_placeholder = '${' . preg_replace('/<[^>]+>/', '', $field_name) . '}';
        $xml = substr($xml, 0, $match_start) . $plain_placeholder . substr($xml, $match_end);
        $offset = $match_start + strlen($plain_placeholder);
    }

    return $xml;
}

/** Подставляет значения в один XML-фрагмент документа. */
function msll_docx_apply_replacements_to_xml(string $xml, array $replacements): string
{
    $xml = msll_docx_normalize_split_placeholders($xml);

    foreach ($replacements as $field_name => $value) {
        $placeholder = '${' . (string) $field_name . '}';
        $xml = str_replace(
            $placeholder,
            msll_docx_escape_xml_text(msll_document_normalize_scalar($value)),
            $xml
        );
    }

    return $xml;
}

/** Список XML-частей DOCX, где могут встречаться плейсхолдеры. */
function msll_docx_collect_xml_parts(ZipArchive $zip): array
{
    $parts = array();

    for ($index = 0; $index < $zip->numFiles; $index += 1) {
        $entry_name = (string) $zip->getNameIndex($index);
        if ($entry_name === '') {
            continue;
        }

        if (preg_match('#^word/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$#', $entry_name)) {
            $parts[] = $entry_name;
        }
    }

    return $parts;
}

/**
 * Заполняет бинарный DOCX значениями полей и возвращает готовый документ.
 *
 * @param array<string, string> $merge_fields ключ плейсхолдера (без ${}) => значение
 * @param array<string, mixed> $table_blocks конфиг из table_blocks_json
 * @param array<string, array<int, array<string, string>>> $table_row_data строки табличных блоков
 */
function msll_docx_fill_template(
    string $binary_docx,
    array $merge_fields,
    array $table_blocks = array(),
    array $table_row_data = array()
): string
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP extension ZipArchive is required for DOCX generation');
    }

    $temp_input = tempnam(sys_get_temp_dir(), 'docx_in_');
    $temp_output = tempnam(sys_get_temp_dir(), 'docx_out_');

    if ($temp_input === false || $temp_output === false) {
        throw new RuntimeException('Unable to create temporary files for DOCX processing');
    }

    file_put_contents($temp_input, $binary_docx);

    $zip = new ZipArchive();
    if ($zip->open($temp_input) !== true) {
        @unlink($temp_input);
        @unlink($temp_output);
        throw new RuntimeException('Invalid DOCX archive');
    }

    $xml_parts = msll_docx_collect_xml_parts($zip);

    foreach ($xml_parts as $part_name) {
        $xml_content = $zip->getFromName($part_name);
        if (!is_string($xml_content) || $xml_content === '') {
            continue;
        }

        $processed_xml = msll_docx_apply_replacements_to_xml($xml_content, $merge_fields);
        if (count($table_blocks) > 0 && count($table_row_data) > 0) {
            require_once __DIR__ . '/docx_table_filler.php';
            $processed_xml = msll_docx_apply_table_blocks_to_xml($processed_xml, $table_blocks, $table_row_data);
        }
        $zip->deleteName($part_name);
        $zip->addFromString($part_name, $processed_xml);
    }

    $zip->close();

    if (!copy($temp_input, $temp_output)) {
        @unlink($temp_input);
        @unlink($temp_output);
        throw new RuntimeException('Unable to copy processed DOCX');
    }

    $filled_binary = file_get_contents($temp_output);
    @unlink($temp_input);
    @unlink($temp_output);

    if (!is_string($filled_binary) || $filled_binary === '') {
        throw new RuntimeException('DOCX processing returned empty result');
    }

    return $filled_binary;
}

/**
 * Извлекает читаемый текст из DOCX для предпросмотра «проекта документа» в UI.
 */
function msll_docx_extract_plain_text(string $binary_docx): string
{
    if (!class_exists('ZipArchive')) {
        return '';
    }

    $temp_input = tempnam(sys_get_temp_dir(), 'docx_preview_');
    if ($temp_input === false) {
        return '';
    }

    file_put_contents($temp_input, $binary_docx);

    $zip = new ZipArchive();
    if ($zip->open($temp_input) !== true) {
        @unlink($temp_input);
        return '';
    }

    $xml_content = $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($temp_input);

    if (!is_string($xml_content) || $xml_content === '') {
        return '';
    }

    // Абзацы Word — перевод строки; содержимое ячеек таблиц сохраняем в общем потоке w:t
    $xml_content = preg_replace('/<\/w:p>/', "\n", $xml_content);
    $xml_content = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml_content);
    $xml_content = preg_replace('/<w:br[^>]*\/>/', "\n", $xml_content);

    $text_parts = array();
    if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $xml_content, $matches)) {
        foreach ($matches[1] as $text_fragment) {
            $decoded = html_entity_decode((string) $text_fragment, ENT_QUOTES | ENT_XML1, 'UTF-8');
            if ($decoded !== '') {
                $text_parts[] = $decoded;
            }
        }
    }

    $plain_text = trim(preg_replace("/\n{3,}/", "\n\n", implode('', $text_parts)));
    return $plain_text;
}

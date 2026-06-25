<?php
/**
 * Загрузка в БД шаблона «Договор … ООО Линки» (SQL из database/load_document_template_linki.sql).
 *
 * Запуск из корня проекта:
 *   php tools/load_document_template_linki.php
 * или с указанием config:
 *   php tools/load_document_template_linki.php ../dev_config/config.php
 */

$config_path = isset($argv[1]) ? (string) $argv[1] : dirname(__DIR__) . '/config.php';
if (!is_file($config_path)) {
    fwrite(STDERR, "Config not found: {$config_path}\n");
    exit(1);
}

require $config_path;

$sql_path = dirname(__DIR__) . '/database/load_document_template_linki.sql';
if (!is_file($sql_path)) {
    fwrite(STDERR, "SQL file not found: {$sql_path}\n");
    exit(1);
}

$sql = file_get_contents($sql_path);
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "SQL file is empty\n");
    exit(1);
}

// Выполняем по выражениям (без DELIMITER); комментарии и пустые строки пропускаем.
$statements = array();
$buffer = '';

foreach (preg_split('/\R/', $sql) as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || strpos($trimmed, '--') === 0) {
        continue;
    }

    $buffer .= $line . "\n";
    if (substr(rtrim($line), -1) === ';') {
        $statements[] = trim($buffer);
        $buffer = '';
    }
}

if (trim($buffer) !== '') {
    $statements[] = trim($buffer);
}

try {
    $executed = 0;
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $connection->exec($statement);
        $executed += 1;
    }

    $check = $connection->query("
        SELECT template_id, template_code, template_name
        FROM document_templates
        WHERE template_code = 'legal_services_agreement_linki'
        LIMIT 1
    ");
    $row = $check ? $check->fetch(PDO::FETCH_ASSOC) : false;

    if (!$row) {
        fwrite(STDERR, "Template legal_services_agreement_linki was not found after load.\n");
        exit(1);
    }

    $fields_count = $connection->query("
        SELECT COUNT(*) AS cnt
        FROM document_template_fields
        WHERE template_id = " . (int) $row['template_id']
    )->fetch(PDO::FETCH_ASSOC);

    echo "OK: loaded template_id={$row['template_id']}, fields={$fields_count['cnt']}, statements={$executed}\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Load failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

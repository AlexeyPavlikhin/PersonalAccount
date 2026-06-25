<?php
/**
 * Список активных шаблонов для левой панели pg_documents.js (фильтр по названию и категории).
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/document_template_mapper.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

try {
    $query = $connection->prepare("
        SELECT
            template_id,
            template_code,
            template_name,
            template_category,
            template_description,
            template_url,
            field_map_json,
            filter_tags_json,
            is_active,
            sort,
            created_at,
            updated_at
        FROM document_templates
        WHERE is_active = 1
        ORDER BY sort, template_name, template_id
    ");
    $query->execute();
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    $templates = array();
    $categories = array();

    foreach ($rows as $row) {
        $tags = msll_document_decode_json_map($row['filter_tags_json'] ?? '');
        $category = trim((string) ($row['template_category'] ?? ''));
        if ($category !== '') {
            $categories[$category] = true;
        }

        $templates[] = array(
            'template_id' => (int) ($row['template_id'] ?? 0),
            'template_code' => trim((string) ($row['template_code'] ?? '')),
            'template_name' => trim((string) ($row['template_name'] ?? '')),
            'template_category' => $category,
            'template_description' => trim((string) ($row['template_description'] ?? '')),
            'template_url' => trim((string) ($row['template_url'] ?? '')),
            'filter_tags' => array_values($tags),
            'is_active' => (int) ($row['is_active'] ?? 0),
            'sort' => (int) ($row['sort'] ?? 0),
        );
    }

    $available_categories = array_keys($categories);
    sort($available_categories);

    echo json_encode(array(
        'status' => 'ok',
        'templates' => $templates,
        'available_categories' => $available_categories,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

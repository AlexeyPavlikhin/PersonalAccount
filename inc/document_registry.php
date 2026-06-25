<?php

/**
 * Реестр оформленных договоров и спецификаций (требования п. 77–78, 10–12).
 */

require_once __DIR__ . '/document_numbering.php';
require_once __DIR__ . '/document_derived_fields.php';

/** Привязка nullable DATE к PDO (bindParam с null ненадёжен). */
function msll_document_bind_nullable_date(PDOStatement $statement, string $param_name, ?string $date_value): void
{
    if ($date_value === null) {
        $statement->bindValue($param_name, null, PDO::PARAM_NULL);
        return;
    }

    $statement->bindValue($param_name, $date_value, PDO::PARAM_STR);
}

/** Нормализует дату для колонки DATE в MySQL. */
function msll_document_normalize_sql_date(string $value): ?string
{
    $parsed = msll_document_parse_contract_date($value);
    if (!$parsed instanceof DateTimeImmutable) {
        return null;
    }

    return $parsed->format('Y-m-d');
}

/**
 * Сохраняет запись в реестр после enrich (только финальная генерация, внутри транзакции).
 */
function msll_document_save_registry_on_generation(PDO $connection, array $template, array $form_values): void
{
    $role = msll_document_registry_role($template);
    if ($role === 'none') {
        return;
    }

    if ($role === 'contract') {
        msll_document_registry_upsert_contract($connection, $form_values);

        return;
    }

    if ($role === 'specification') {
        msll_document_registry_insert_specification($connection, $form_values, true);

        return;
    }

    if ($role === 'invoice') {
        msll_document_registry_apply_invoice($connection, $form_values);

        return;
    }

    if ($role === 'act') {
        msll_document_registry_apply_act($connection, $form_values);
    }
}

function msll_document_registry_upsert_contract(PDO $connection, array $form_values): void
{
    $contract_number = trim((string) ($form_values['contract_number'] ?? ''));
    if ($contract_number === '') {
        throw new RuntimeException('contract_number_required');
    }

    $contract_date = msll_document_normalize_sql_date((string) ($form_values['contract_date'] ?? ''));
    $subject_short = trim((string) ($form_values['contract_subject_short'] ?? ''));
    $counterparty_display = msll_document_resolve_counterparty_display_name($form_values);
    $contract_seq = (int) ($form_values['_allocated_contract_seq'] ?? 0);

    if ($contract_seq <= 0 && preg_match('/^(\d+)-\d{2}\/\d{2}$/', $contract_number, $matches)) {
        $contract_seq = (int) $matches[1];
    }

    $existing_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    if ($existing_id > 0) {
        $update = $connection->prepare("
            UPDATE document_issued_contracts
            SET
                contract_seq = :contract_seq,
                contract_date = :contract_date,
                subject_short = :subject_short,
                counterparty_display = :counterparty_display
            WHERE contract_id = :contract_id
        ");
        $update->bindValue(':contract_seq', $contract_seq, PDO::PARAM_INT);
        msll_document_bind_nullable_date($update, ':contract_date', $contract_date);
        $update->bindValue(':subject_short', $subject_short, PDO::PARAM_STR);
        $update->bindParam(':counterparty_display', $counterparty_display, PDO::PARAM_STR);
        $update->bindParam(':contract_id', $existing_id, PDO::PARAM_INT);
        $update->execute();

        return;
    }

    $insert = $connection->prepare("
        INSERT INTO document_issued_contracts (
            contract_number,
            contract_seq,
            contract_date,
            subject_short,
            counterparty_display
        ) VALUES (
            :contract_number,
            :contract_seq,
            :contract_date,
            :subject_short,
            :counterparty_display
        )
    ");
    $insert->bindValue(':contract_number', $contract_number, PDO::PARAM_STR);
    $insert->bindValue(':contract_seq', $contract_seq, PDO::PARAM_INT);
    msll_document_bind_nullable_date($insert, ':contract_date', $contract_date);
    $insert->bindValue(':subject_short', $subject_short, PDO::PARAM_STR);
    $insert->bindValue(':counterparty_display', $counterparty_display, PDO::PARAM_STR);
    $insert->execute();
}

function msll_document_registry_insert_specification(PDO $connection, array $form_values, bool $with_invoice): void
{
    $contract_number = trim((string) ($form_values['contract_number'] ?? ''));
    if ($contract_number === '') {
        throw new RuntimeException('contract_number_required');
    }

    $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    if ($contract_id <= 0) {
        msll_document_registry_upsert_contract($connection, $form_values);
        $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    }

    if ($contract_id <= 0) {
        throw new RuntimeException('contract_not_found_in_registry');
    }

    $spec_number = (int) ($form_values['spec_number'] ?? 0);
    if ($spec_number <= 0) {
        throw new RuntimeException('spec_number_required');
    }

    $spec_date = msll_document_normalize_sql_date((string) ($form_values['spec_date'] ?? ''));
    $planned_act_date = msll_document_calc_planned_act_date((string) ($form_values['spec_date'] ?? ''));

    $invoice_number = null;
    $invoice_date = null;
    if ($with_invoice) {
        $invoice_raw = trim((string) ($form_values['invoice_number'] ?? ''));
        if ($invoice_raw !== '' && ctype_digit($invoice_raw)) {
            $invoice_number = (int) $invoice_raw;
        }
        $invoice_date = msll_document_normalize_sql_date((string) ($form_values['invoice_date'] ?? ''));
    }

    $insert = $connection->prepare("
        INSERT INTO document_issued_specifications (
            contract_id,
            spec_number,
            spec_date,
            invoice_number,
            invoice_date,
            planned_act_date
        ) VALUES (
            :contract_id,
            :spec_number,
            :spec_date,
            :invoice_number,
            :invoice_date,
            :planned_act_date
        )
        ON DUPLICATE KEY UPDATE
            spec_date = VALUES(spec_date),
            invoice_number = COALESCE(VALUES(invoice_number), invoice_number),
            invoice_date = COALESCE(VALUES(invoice_date), invoice_date),
            planned_act_date = VALUES(planned_act_date)
    ");
    $insert->bindValue(':contract_id', $contract_id, PDO::PARAM_INT);
    $insert->bindValue(':spec_number', $spec_number, PDO::PARAM_INT);
    msll_document_bind_nullable_date($insert, ':spec_date', $spec_date);
    if ($invoice_number === null) {
        $insert->bindValue(':invoice_number', null, PDO::PARAM_NULL);
    } else {
        $insert->bindValue(':invoice_number', $invoice_number, PDO::PARAM_INT);
    }
    msll_document_bind_nullable_date($insert, ':invoice_date', $invoice_date);
    msll_document_bind_nullable_date($insert, ':planned_act_date', $planned_act_date);
    $insert->execute();
}

function msll_document_registry_apply_invoice(PDO $connection, array $form_values): void
{
    $contract_number = trim((string) ($form_values['contract_number'] ?? ''));
    if ($contract_number === '') {
        throw new RuntimeException('contract_number_required');
    }

    $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    if ($contract_id <= 0) {
        throw new RuntimeException('contract_not_found_in_registry');
    }

    $invoice_number = (int) ($form_values['invoice_number'] ?? 0);
    $invoice_date = msll_document_normalize_sql_date((string) ($form_values['invoice_date'] ?? ''));

    $select = $connection->prepare("
        SELECT spec_id
        FROM document_issued_specifications
        WHERE contract_id = :contract_id
        ORDER BY spec_id DESC
        LIMIT 1
    ");
    $select->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['spec_id'])) {
        $spec_id = (int) $row['spec_id'];
        $update = $connection->prepare("
            UPDATE document_issued_specifications
            SET invoice_number = :invoice_number, invoice_date = :invoice_date
            WHERE spec_id = :spec_id
        ");
        $update->bindParam(':invoice_number', $invoice_number, PDO::PARAM_INT);
        $update->bindParam(':invoice_date', $invoice_date);
        $update->bindParam(':spec_id', $spec_id, PDO::PARAM_INT);
        $update->execute();

        return;
    }

    // Счёт без спецификации: строка с пустой spec_date, planned_act_date не задаём
    $insert = $connection->prepare("
        INSERT INTO document_issued_specifications (
            contract_id,
            spec_number,
            spec_date,
            invoice_number,
            invoice_date,
            planned_act_date
        ) VALUES (
            :contract_id,
            1,
            NULL,
            :invoice_number,
            :invoice_date,
            NULL
        )
    ");
    $insert->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
    $insert->bindParam(':invoice_number', $invoice_number, PDO::PARAM_INT);
    $insert->bindParam(':invoice_date', $invoice_date);
    $insert->execute();
}

function msll_document_registry_apply_act(PDO $connection, array $form_values): void
{
    $contract_number = trim((string) ($form_values['contract_number'] ?? ''));
    if ($contract_number === '') {
        return;
    }

    $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    if ($contract_id <= 0) {
        return;
    }

    $planned_override = msll_document_normalize_sql_date((string) ($form_values['planned_act_date'] ?? ''));
    if ($planned_override === null) {
        return;
    }

    $select = $connection->prepare("
        SELECT spec_id
        FROM document_issued_specifications
        WHERE contract_id = :contract_id
        ORDER BY spec_id DESC
        LIMIT 1
    ");
    $select->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['spec_id'])) {
        return;
    }

    $spec_id = (int) $row['spec_id'];
    $update = $connection->prepare("
        UPDATE document_issued_specifications
        SET planned_act_date = :planned_act_date
        WHERE spec_id = :spec_id
    ");
    $update->bindParam(':planned_act_date', $planned_override);
    $update->bindParam(':spec_id', $spec_id, PDO::PARAM_INT);
    $update->execute();
}

/**
 * Список строк реестра для UI (одна строка на спецификацию).
 *
 * @return array<int, array<string, mixed>>
 */
function msll_document_registry_fetch_rows(PDO $connection, string $contract_number_filter = ''): array
{
    $sql = "
        SELECT
            s.spec_id,
            c.contract_id,
            c.contract_number,
            c.contract_date,
            c.subject_short,
            c.counterparty_display,
            s.spec_number,
            s.spec_date,
            s.invoice_number,
            s.invoice_date,
            s.planned_act_date
        FROM document_issued_contracts c
        LEFT JOIN document_issued_specifications s ON s.contract_id = c.contract_id
    ";
    $params = array();

    if (trim($contract_number_filter) !== '') {
        $sql .= " WHERE c.contract_number LIKE :contract_number_filter";
        $params[':contract_number_filter'] = '%' . trim($contract_number_filter) . '%';
    }

    $sql .= "
        ORDER BY
            CASE WHEN s.planned_act_date IS NULL THEN 1 ELSE 0 END,
            s.planned_act_date ASC,
            s.spec_date ASC,
            c.contract_number ASC,
            s.spec_number ASC
    ";

    $query = $connection->prepare($sql);
    foreach ($params as $key => $value) {
        $query->bindValue($key, $value, PDO::PARAM_STR);
    }
    $query->execute();
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    $result = array();
    foreach ($rows as $row) {
        if (empty($row['spec_id'])) {
            $result[] = array(
                'spec_id' => null,
                'contract_id' => (int) ($row['contract_id'] ?? 0),
                'contract_number' => (string) ($row['contract_number'] ?? ''),
                'contract_date' => $row['contract_date'] ?? null,
                'subject_short' => (string) ($row['subject_short'] ?? ''),
                'counterparty_display' => (string) ($row['counterparty_display'] ?? ''),
                'spec_number' => null,
                'spec_date' => null,
                'invoice_number' => null,
                'invoice_date' => null,
                'planned_act_date' => null,
            );
            continue;
        }

        $result[] = array(
            'spec_id' => (int) ($row['spec_id'] ?? 0),
            'contract_id' => (int) ($row['contract_id'] ?? 0),
            'contract_number' => (string) ($row['contract_number'] ?? ''),
            'contract_date' => $row['contract_date'] ?? null,
            'subject_short' => (string) ($row['subject_short'] ?? ''),
            'counterparty_display' => (string) ($row['counterparty_display'] ?? ''),
            'spec_number' => $row['spec_number'] !== null ? (int) $row['spec_number'] : null,
            'spec_date' => $row['spec_date'] ?? null,
            'invoice_number' => $row['invoice_number'] !== null ? (int) $row['invoice_number'] : null,
            'invoice_date' => $row['invoice_date'] ?? null,
            'planned_act_date' => $row['planned_act_date'] ?? null,
        );
    }

    return $result;
}

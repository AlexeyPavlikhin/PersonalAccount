<?php

/**
 * Автонумерация договоров, спецификаций и счетов (требования п. 79–81).
 * Номера выделяются в транзакции при финальной генерации DOCX.
 */

require_once __DIR__ . '/document_text_transform.php';

/** Шаблоны договоров, если в БД ещё не задан registry_role. */
function msll_document_contract_template_codes(): array
{
    return array('legal_services_agreement_demo', 'legal_services_agreement_linki');
}

/**
 * Роль шаблона для записи в реестр (колонка registry_role или код шаблона).
 */
function msll_document_registry_role(array $template): string
{
    $role = strtolower(trim((string) ($template['registry_role'] ?? '')));
    $allowed = array('contract', 'specification', 'invoice', 'act', 'none');

    if (in_array($role, $allowed, true) && $role !== 'none') {
        return $role;
    }

    $template_code = trim((string) ($template['template_code'] ?? ''));
    if (in_array($template_code, msll_document_contract_template_codes(), true)) {
        return 'contract';
    }

    return 'none';
}

/**
 * Подтягивает счётчик contract_seq до MAX(contract_seq) из реестра (если счётчики обходили).
 */
function msll_document_sync_contract_seq_counter(PDO $connection): void
{
    $max_from_registry = 0;

    try {
        $select_max = $connection->query('
            SELECT COALESCE(MAX(contract_seq), 0) AS max_seq
            FROM document_issued_contracts
        ');
        if ($select_max !== false) {
            $max_from_registry = (int) ($select_max->fetchColumn() ?: 0);
        }
    } catch (Throwable $e) {
        // таблица реестра может ещё не существовать
    }

    // last_value — зарезервировано в MySQL 8 (оконная функция LAST_VALUE), нужны обратные кавычки
    $insert = $connection->prepare("
        INSERT INTO `document_number_counters` (`counter_code`, `last_value`)
        VALUES ('contract_seq', 0)
        ON DUPLICATE KEY UPDATE `counter_code` = `counter_code`
    ");
    $insert->execute();

    $update = $connection->prepare("
        UPDATE `document_number_counters`
        SET `last_value` = GREATEST(`last_value`, :max_seq)
        WHERE `counter_code` = 'contract_seq'
    ");
    $update->bindValue(':max_seq', $max_from_registry, PDO::PARAM_INT);
    $update->execute();
}

/** Атомарный инкремент счётчика (вызывать внутри открытой транзакции). */
function msll_document_increment_counter(PDO $connection, string $counter_code): int
{
    $counter_code = trim($counter_code);
    if ($counter_code === '') {
        throw new InvalidArgumentException('counter_code_required');
    }

    if ($counter_code === 'contract_seq') {
        msll_document_sync_contract_seq_counter($connection);
    }

    $insert = $connection->prepare("
        INSERT INTO `document_number_counters` (`counter_code`, `last_value`)
        VALUES (:counter_code, 0)
        ON DUPLICATE KEY UPDATE `counter_code` = `counter_code`
    ");
    $insert->bindParam(':counter_code', $counter_code, PDO::PARAM_STR);
    $insert->execute();

    $select = $connection->prepare("
        SELECT `last_value`
        FROM `document_number_counters`
        WHERE `counter_code` = :counter_code
        FOR UPDATE
    ");
    $select->bindParam(':counter_code', $counter_code, PDO::PARAM_STR);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);
    $last_value = (int) ($row['last_value'] ?? 0);
    $new_value = $last_value + 1;

    $update = $connection->prepare("
        UPDATE `document_number_counters`
        SET `last_value` = :new_value
        WHERE `counter_code` = :counter_code
    ");
    $update->bindParam(':new_value', $new_value, PDO::PARAM_INT);
    $update->bindParam(':counter_code', $counter_code, PDO::PARAM_STR);
    $update->execute();

    return $new_value;
}

/** Формат номера договора: XX-MM/YY. */
function msll_document_format_contract_number(int $contract_seq, DateTimeImmutable $contract_date): string
{
    $month = $contract_date->format('m');
    $year_yy = $contract_date->format('y');

    return sprintf('%02d-%s/%s', $contract_seq, $month, $year_yy);
}

/**
 * Выделяет номер договора: при $commit_counter=true инкрементирует document_number_counters.
 *
 * @return array{contract_number: string, contract_seq: int}
 */
function msll_document_allocate_contract_number(PDO $connection, string $contract_date_iso, bool $commit_counter): array
{
    msll_document_sync_contract_seq_counter($connection);

    if ($commit_counter) {
        $contract_seq = msll_document_increment_counter($connection, 'contract_seq');
    } else {
        $select = $connection->prepare("
            SELECT `last_value`
            FROM `document_number_counters`
            WHERE `counter_code` = 'contract_seq'
            LIMIT 1
        ");
        $select->execute();
        $row = $select->fetch(PDO::FETCH_ASSOC);
        $contract_seq = (int) ($row['last_value'] ?? 0) + 1;
    }

    $parsed = msll_document_parse_contract_date($contract_date_iso);
    if (!$parsed instanceof DateTimeImmutable) {
        $parsed = new DateTimeImmutable('today');
    }

    return array(
        'contract_number' => msll_document_format_contract_number($contract_seq, $parsed),
        'contract_seq' => $contract_seq,
    );
}

/** Превью следующего номера договора без инкремента (для UI). */
function msll_document_preview_contract_number(PDO $connection, string $contract_date_iso): string
{
    $allocated = msll_document_allocate_contract_number($connection, $contract_date_iso, false);

    return $allocated['contract_number'];
}

/** Нужно ли выдать новый номер при генерации (превью в форме ещё не зафиксировано в реестре). */
function msll_document_should_allocate_contract_number(PDO $connection, string $contract_number): bool
{
    $contract_number = trim($contract_number);
    if ($contract_number === '') {
        return true;
    }

    return msll_document_find_contract_id_by_number($connection, $contract_number) <= 0;
}

/** Превью следующего сквозного номера счёта. */
function msll_document_preview_invoice_number(PDO $connection): int
{
    $select = $connection->prepare("
        SELECT `last_value`
        FROM `document_number_counters`
        WHERE `counter_code` = 'invoice_seq'
        LIMIT 1
    ");
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['last_value'] ?? 0) + 1;
}

/** Следующий номер спецификации в рамках договора (по contract_number). */
function msll_document_preview_spec_number(PDO $connection, string $contract_number): int
{
    $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    if ($contract_id <= 0) {
        return 1;
    }

    $select = $connection->prepare("
        SELECT COALESCE(MAX(spec_number), 0) AS max_spec
        FROM document_issued_specifications
        WHERE contract_id = :contract_id
    ");
    $select->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['max_spec'] ?? 0) + 1;
}

function msll_document_find_contract_id_by_number(PDO $connection, string $contract_number): int
{
    $contract_number = trim($contract_number);
    if ($contract_number === '') {
        return 0;
    }

    $select = $connection->prepare("
        SELECT contract_id
        FROM document_issued_contracts
        WHERE contract_number = :contract_number
        LIMIT 1
    ");
    $select->bindParam(':contract_number', $contract_number, PDO::PARAM_STR);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['contract_id'] ?? 0);
}

/**
 * Выделяет номера по registry_role и дополняет form_values перед enrich.
 * Вызывать только внутри транзакции при финальной генерации.
 */
function msll_document_apply_numbering_on_generation(PDO $connection, array $template, array &$form_values): void
{
    $role = msll_document_registry_role($template);

    if ($role === 'contract') {
        $existing_number = trim((string) ($form_values['contract_number'] ?? ''));
        if (!msll_document_should_allocate_contract_number($connection, $existing_number)) {
            return;
        }

        $allocated = msll_document_allocate_contract_number(
            $connection,
            (string) ($form_values['contract_date'] ?? ''),
            true
        );
        $form_values['contract_number'] = $allocated['contract_number'];
        $form_values['_allocated_contract_seq'] = (string) $allocated['contract_seq'];

        return;
    }

    if ($role === 'specification') {
        $contract_number = trim((string) ($form_values['contract_number'] ?? ''));
        if ($contract_number === '') {
            throw new RuntimeException('contract_number_required_for_specification');
        }

        if (trim((string) ($form_values['spec_number'] ?? '')) === '') {
            $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
            $spec_number = 1;
            if ($contract_id > 0) {
                $select = $connection->prepare("
                    SELECT COALESCE(MAX(spec_number), 0) AS max_spec
                    FROM document_issued_specifications
                    WHERE contract_id = :contract_id
                    FOR UPDATE
                ");
                $select->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
                $select->execute();
                $row = $select->fetch(PDO::FETCH_ASSOC);
                $spec_number = (int) ($row['max_spec'] ?? 0) + 1;
            }
            $form_values['spec_number'] = (string) $spec_number;
        }

        msll_document_ensure_spec_and_invoice_dates($form_values);

        if (trim((string) ($form_values['invoice_number'] ?? '')) === '') {
            $invoice_number = msll_document_increment_counter($connection, 'invoice_seq');
            $form_values['invoice_number'] = (string) $invoice_number;
        }

        return;
    }

    if ($role === 'invoice') {
        $contract_number = trim((string) ($form_values['contract_number'] ?? ''));
        if ($contract_number === '') {
            throw new RuntimeException('contract_number_required_for_invoice');
        }

        if (trim((string) ($form_values['invoice_number'] ?? '')) === '') {
            $invoice_number = msll_document_increment_counter($connection, 'invoice_seq');
            $form_values['invoice_number'] = (string) $invoice_number;
        }

        msll_document_ensure_spec_and_invoice_dates($form_values, true);

        return;
    }
}

/** Подставляет даты спецификации/счёта по умолчанию, если не заданы. */
function msll_document_ensure_spec_and_invoice_dates(array &$form_values, bool $invoice_only = false): void
{
    $today = msll_document_today_iso();

    if (!$invoice_only && trim((string) ($form_values['spec_date'] ?? '')) === '') {
        $fallback = trim((string) ($form_values['contract_date'] ?? ''));
        $form_values['spec_date'] = $fallback !== '' ? $fallback : $today;
    }

    if (trim((string) ($form_values['invoice_date'] ?? '')) === '') {
        $fallback = trim((string) ($form_values['spec_date'] ?? $form_values['contract_date'] ?? ''));
        $form_values['invoice_date'] = $fallback !== '' ? $fallback : $today;
    }
}

/** Плановая дата акта: spec_date + 14 календарных дней. */
function msll_document_calc_planned_act_date(string $spec_date_iso): ?string
{
    $parsed = msll_document_parse_contract_date($spec_date_iso);
    if (!$parsed instanceof DateTimeImmutable) {
        return null;
    }

    return $parsed->modify('+14 days')->format('Y-m-d');
}

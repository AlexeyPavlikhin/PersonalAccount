<?php
/**
 * Ручное сохранение строки реестра (п. 12).
 * POST JSON: { spec_id?, contract_id?, contract_number, contract_date, subject_short,
 *   counterparty_display, spec_number?, spec_date?, invoice_number?, invoice_date? }
 */
session_start();
include('../config.php');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/document_registry.php';
require_once __DIR__ . '/../inc/document_numbering.php';

if (!isset($_SESSION['current_user_id'])) {
    echo json_encode(array('status' => 'error', 'message' => 'empty_session'));
    exit;
}

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
if (!is_array($data)) {
    echo json_encode(array('status' => 'error', 'message' => 'invalid_json'));
    exit;
}

try {
    $connection->beginTransaction();

    $contract_number = trim((string) ($data['contract_number'] ?? ''));
    if ($contract_number === '') {
        throw new RuntimeException('contract_number_required');
    }

    $contract_id = (int) ($data['contract_id'] ?? 0);
    $contract_date = msll_document_normalize_sql_date((string) ($data['contract_date'] ?? ''));
    $subject_short = trim((string) ($data['subject_short'] ?? ''));
    $counterparty_display = trim((string) ($data['counterparty_display'] ?? ''));
    $contract_seq = 0;
    if (preg_match('/^(\d+)-\d{2}\/\d{2}$/', $contract_number, $matches)) {
        $contract_seq = (int) $matches[1];
    }

    if ($contract_id <= 0) {
        $contract_id = msll_document_find_contract_id_by_number($connection, $contract_number);
    }

    if ($contract_id > 0) {
        $update_contract = $connection->prepare("
            UPDATE document_issued_contracts
            SET
                contract_number = :contract_number,
                contract_seq = :contract_seq,
                contract_date = :contract_date,
                subject_short = :subject_short,
                counterparty_display = :counterparty_display
            WHERE contract_id = :contract_id
        ");
        $update_contract->bindParam(':contract_number', $contract_number, PDO::PARAM_STR);
        $update_contract->bindParam(':contract_seq', $contract_seq, PDO::PARAM_INT);
        $update_contract->bindParam(':contract_date', $contract_date);
        $update_contract->bindParam(':subject_short', $subject_short, PDO::PARAM_STR);
        $update_contract->bindParam(':counterparty_display', $counterparty_display, PDO::PARAM_STR);
        $update_contract->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
        $update_contract->execute();
    } else {
        $insert_contract = $connection->prepare("
            INSERT INTO document_issued_contracts (
                contract_number, contract_seq, contract_date, subject_short, counterparty_display
            ) VALUES (
                :contract_number, :contract_seq, :contract_date, :subject_short, :counterparty_display
            )
        ");
        $insert_contract->bindParam(':contract_number', $contract_number, PDO::PARAM_STR);
        $insert_contract->bindParam(':contract_seq', $contract_seq, PDO::PARAM_INT);
        $insert_contract->bindParam(':contract_date', $contract_date);
        $insert_contract->bindParam(':subject_short', $subject_short, PDO::PARAM_STR);
        $insert_contract->bindParam(':counterparty_display', $counterparty_display, PDO::PARAM_STR);
        $insert_contract->execute();
        $contract_id = (int) $connection->lastInsertId();
    }

    $spec_id = (int) ($data['spec_id'] ?? 0);
    $spec_number_raw = $data['spec_number'] ?? null;
    $has_spec_data = $spec_number_raw !== null && $spec_number_raw !== '';

    if ($has_spec_data || $spec_id > 0) {
        $spec_number = (int) $spec_number_raw;
        $spec_date = msll_document_normalize_sql_date((string) ($data['spec_date'] ?? ''));
        // Ручное значение из UI; если пусто — расчёт от даты спецификации
        $planned_act_date = msll_document_normalize_sql_date((string) ($data['planned_act_date'] ?? ''));
        if ($planned_act_date === null && $spec_date !== null) {
            $planned_act_date = msll_document_calc_planned_act_date((string) ($data['spec_date'] ?? ''));
        }

        $invoice_number = null;
        $invoice_raw = $data['invoice_number'] ?? null;
        if ($invoice_raw !== null && $invoice_raw !== '' && ctype_digit((string) $invoice_raw)) {
            $invoice_number = (int) $invoice_raw;
        }
        $invoice_date = msll_document_normalize_sql_date((string) ($data['invoice_date'] ?? ''));

        if ($spec_id > 0) {
            $update_spec = $connection->prepare("
                UPDATE document_issued_specifications
                SET
                    contract_id = :contract_id,
                    spec_number = :spec_number,
                    spec_date = :spec_date,
                    invoice_number = :invoice_number,
                    invoice_date = :invoice_date,
                    planned_act_date = :planned_act_date
                WHERE spec_id = :spec_id
            ");
            $update_spec->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
            $update_spec->bindParam(':spec_number', $spec_number, PDO::PARAM_INT);
            $update_spec->bindParam(':spec_date', $spec_date);
            $update_spec->bindParam(':invoice_number', $invoice_number, $invoice_number === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $update_spec->bindParam(':invoice_date', $invoice_date);
            $update_spec->bindParam(':planned_act_date', $planned_act_date);
            $update_spec->bindParam(':spec_id', $spec_id, PDO::PARAM_INT);
            $update_spec->execute();
        } else {
            $insert_spec = $connection->prepare("
                INSERT INTO document_issued_specifications (
                    contract_id, spec_number, spec_date, invoice_number, invoice_date, planned_act_date
                ) VALUES (
                    :contract_id, :spec_number, :spec_date, :invoice_number, :invoice_date, :planned_act_date
                )
            ");
            $insert_spec->bindParam(':contract_id', $contract_id, PDO::PARAM_INT);
            $insert_spec->bindParam(':spec_number', $spec_number, PDO::PARAM_INT);
            $insert_spec->bindParam(':spec_date', $spec_date);
            $insert_spec->bindParam(':invoice_number', $invoice_number, $invoice_number === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insert_spec->bindParam(':invoice_date', $invoice_date);
            $insert_spec->bindParam(':planned_act_date', $planned_act_date);
            $insert_spec->execute();
            $spec_id = (int) $connection->lastInsertId();
        }
    }

    $connection->commit();

    echo json_encode(array(
        'status' => 'ok',
        'contract_id' => $contract_id,
        'spec_id' => $spec_id > 0 ? $spec_id : null,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}

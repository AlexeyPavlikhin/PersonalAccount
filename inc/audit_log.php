<?php

/**
 * Запись в таблицу audit (параметризованный INSERT).
 * Работает при любом PDO::ATTR_ERRMODE у соединения: временно включаем исключения на время execute.
 */

function msll_audit_write(PDO $connection, string $user_login, string $operation_type, string $event_data): void
{
    $prevErr = PDO::ERRMODE_SILENT;
    try {
        $prevErr = $connection->getAttribute(PDO::ATTR_ERRMODE);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $connection->prepare(
            'INSERT INTO audit (user_login, operation_type, event_data) VALUES (:login, :op, :data)'
        );
        $stmt->execute([
            'login' => substr($user_login, 0, 254),
            'op' => substr($operation_type, 0, 254),
            'data' => $event_data,
        ]);
    } catch (PDOException $e) {
        error_log('msll_audit_write: ' . $e->getMessage());
    } finally {
        try {
            $connection->setAttribute(PDO::ATTR_ERRMODE, $prevErr);
        } catch (\Throwable $t) {
        }
    }
}

function msll_audit_write2($in_user_login, $in_operation_type, $in_event_data) {
    include('config.php');
    try {
        $sql_audit = 
        "INSERT 
            INTO audit 
            (
                user_login, 
                operation_type, 
                event_data
            ) VALUES (
                '".substr($in_user_login, 0, 254)."', 
                '".substr($in_operation_type, 0, 254)."', 
                '".$in_event_data."'
            )
        ";
        $query = $connection->prepare($sql_audit);
        $query->execute();
    } catch(PDOException $e) {
        //echo $e->getMessage()." ".$sql_audit;
    }     
        
    return 0;
}

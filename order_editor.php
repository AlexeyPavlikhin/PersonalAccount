<?php
    session_start();
    include('config.php');
    
        $order_id = $_SESSION['detail_order_id'];
        $query = $connection->prepare("SELECT o.*, DATE_FORMAT(o.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time', u.username FROM orders o, users u WHERE o.order_id =:order_id and o.operator_id=u.id order by o.id desc");
        $query->bindParam("order_id", $order_id, PDO::PARAM_STR);
        $query->execute();
        $current_record="";
        //$result = $query->fetch(PDO::FETCH_ASSOC);

        echo "<p class='success'>Детальная информация о заказе номер ".$order_id."</p>";
        echo "<form class='table' method='POST' action=''>";
        echo "  <table><tr><th>Номер заказа</th><th>Дата изменения</th><th>Статус (для клиента)</th><th>Комментарий (для сотрудников)</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["formated_row_creation_time"] . "</td>";
            echo "<td>" . $row["username"] . "</td>";
            echo "<td>" . $row["order_status"] . "</td>";            
            echo "<td>" . $row["order_description"] . "</td>";
            echo "</tr>";
            $client_id = $row["client_id"];

        }
        echo "</table>";
        echo "<br>";
        echo "<table>";
        echo "<tr>";
        echo "<td> Статус (для клиента) </td>";
        echo "<td> <textarea class='ta_editor' name='ta_status' rows='5' cols='33'></textarea>  </td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td> Комментарий (для сотрудников) </td>";
        echo "<td> <textarea class='ta_editor' name='ta_comment' rows='5' cols='33'></textarea>  </td>";
        echo "</tr>";
        echo "</table>";

        echo "<br>";
        echo "<tr>";
        echo "<td> <button type='submit' name='close_form' value='close_form'>Закрыть без сохранения</button> </td>";
        echo "<td> <button type='submit' name='change' value='change'>Сохранить изменения</button> </td>";
        echo "</tr>";
        echo "</form>";
        

        if (isset($_POST['close_form'])) {
            header('Location: order_details.php');
        }

        if (isset($_POST['change'])) {
            $query = $connection->prepare("INSERT INTO orders(row_creation_time, order_id,client_id,order_status,order_description,operator_id) VALUES (SYSDATE(),:order_id,:client_id,:order_status,:order_description,:operator_id)");
            $query->bindParam("order_id", $order_id, PDO::PARAM_INT);
            $query->bindParam("client_id", $client_id, PDO::PARAM_INT);
            $query->bindParam("order_status", $_POST['ta_status'], PDO::PARAM_STR);
            $query->bindParam("order_description", $_POST['ta_comment'], PDO::PARAM_STR);
            $query->bindParam("operator_id", $_SESSION['user_id'], PDO::PARAM_INT);
            $result = $query->execute();
            if ($result) {
                header('Location: order_details.php');
            } else {
                echo '<p class="error">Неверные данные!</p>';
            }

            
        }

?>

<link rel="stylesheet" href="styles.css">

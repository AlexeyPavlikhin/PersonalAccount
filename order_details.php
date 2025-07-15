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
        echo "<form class='table' method='GET' action=''>";
        echo "  <table class='db_data'><tr class='db_data'><th class='db_data'>Номер заказа</th><th class='db_data'>Дата изменения</th><th class='db_data'>Статус (для клиента)</th><th class='db_data'>Комментарий (для сотрудников)</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td class='db_data'>" . $row["formated_row_creation_time"] . "</td>";
            echo "<td class='db_data'>" . $row["username"] . "</td>";
            echo "<td class='db_data'>" . $row["order_status"] . "</td>";            
            echo "<td class='db_data'>" . $row["order_description"] . "</td>";
            echo "</tr>";
        }
        echo "  </table>";

        echo "<br>";
        echo "<tr>";
        echo "<td> <button type='submit' name='close_form' value='close_form'>Закрыть</button> </td>";
		echo "<td> <button type='submit' name='change' value='change'>Изменить статус</button> </td>";                
        echo "</tr>";
        echo "</form>";
        
        
        if (isset($_GET['close_form'])) {
            header('Location: lk.php');
        }

        if (isset($_GET['change'])) {
            header('Location: order_editor.php');
        }

?>

<link rel="stylesheet" href="styles.css">
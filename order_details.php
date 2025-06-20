<?php
    session_start();
    include('config.php');
    echo $_SESSION['detail_order_id'];

        $order_id = $_SESSION['detail_order_id'];
        $query = $connection->prepare("SELECT t2.* FROM orders t2 WHERE t2.id t2.order_id = '6'");
        //$query->bindParam("order_id", $order_id, PDO::PARAM_STR);
        $query->execute();
        $current_record="";
        //$result = $query->fetch(PDO::FETCH_ASSOC);

        
        echo "<form class='table' method='GET' action=''>";
        echo "  <table><tr><th>Номер заказа</th><th>дата изменения</th><th>статус</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["order_id"] . "</td>";
            echo "<td>" . $row["row_creation_time"] . "</td>";
            echo "<td>" . $row["order_description"] . "</td>";
            echo "<td> <button type='submit' name='detail' value='".$row["id"]."'>Подробно</button> </td>";
            echo "</tr>";
        }
        echo "  </table>";
        
        echo "</form>";
        
        
        if (isset($_GET['detail'])) {
            $_SESSION['detail_order_id'] = $_GET['detail'];
            header('Location: order_details.php');
            
            


        }


?>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="styles.css">


<!--<div style="float: left; width: 50%; position: absolute ;">-->

<?php
    session_start();
    include('config.php');
    //echo $_SESSION['user_id'];
    //echo "<header class='gTFIBn'> aaa </header>";
    echo "<a href='login.php' class='right'>Выход</a><br/>";
    echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
    
    if ($_SESSION['user_group']!="client") {
        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT t2.*, DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t GROUP by t.order_id) ORDER BY t2.order_id");
        //$query->bindParam("user_id", $user_id, PDO::PARAM_STR);
        $query->execute();
        $current_record="";
        //$result = $query->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='menu'>";
        echo "    <ul>";
        echo "        <li><a href='lk.php'>Управление заказами</a></li>";
        echo "        <li><a href='uc.php'>Управление пользователями</a></li>";
        echo "        <li><a href='sales.php'>Управление продажами</a></li>";
        echo "    </ul>";
        echo "</div>";
        
        echo "<table>";
        echo "    <tr>";
        echo "        <td>";
        echo "<form class='table' method='GET' action=''>";
        echo "  <table class='db_data'><tr class='db_data'><th class='db_data'>Номер заказа</th><th class='db_data'>Дата изменения</th><th class='db_data'>Статус (для клиента)</th><th class='db_data'>Комментарий (для сотрудников)</th><th class='db_data'>Подробно</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td class='db_data'>" . $row["order_id"] . "</td>";
            echo "<td class='db_data'>" . $row["formated_row_creation_time"] . "</td>";
            echo "<td class='db_data'>" . $row["order_status"] . "</td>";
            echo "<td class='db_data'>" . $row["order_description"] . "</td>";
        //echo "<td> <button type='submit' name='detail' value='".$row["order_id"]."'>Подробно</button> </td>";
            echo "<td class='db_data'> <a href='lk.php?action=detail&id=".$row["order_id"]."'>Подробно</a> </td>";
                        
            echo "</tr>";
        }
        echo "  </table>";
        
        echo "</form>";
        
        
        if (isset($_GET['detail'])) {
            $_SESSION['detail_order_id'] = $_GET['detail'];
            header('Location: order_details.php');
        }

        if (isset($_GET['action']) && $_GET['action'] == 'detail') {
            $_SESSION['detail_order_id'] = $_GET['id'];
            header('Location: order_details.php');
        }


    } else {
        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT t2.*, DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t WHERE t.client_id =:user_id GROUP by t.order_id) ORDER BY t2.order_id");
        $query->bindParam("user_id", $user_id, PDO::PARAM_STR);
        $query->execute();

        echo "</div>";
        echo "<table>";
        echo "    <tr>";
        echo "        <td>";
        echo "<form class='table'>";
        echo "  <table class='db_data'><tr class='db_data'><th class='db_data'>Номер заказа</th><th class='db_data'>дата изменения</th><th class='db_data'>статус</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr class='db_data'>";
            echo "<td class='db_data'>" . $row["order_id"] . "</td>";
            echo "<td class='db_data'>" . $row["formated_row_creation_time"] . "</td>";
            echo "<td class='db_data'>" . $row["order_status"] . "</td>";
            echo "</tr class='db_data'>";
        }
        echo "  </table>";
        echo "</form>";
    }
?>

<!--</div>-->

</td>
<td width=300>
    <!--<div style="width: 100%; position: relative; padding-left: 50%">-->
    <!--<div style="width: 100%;>-->


        <div >
            <img src="Mayya_1.png" alt="" width=100% min-width=100%>
        </div>
    <!--</div>-->
 
</td>
</tr>
</table>
<link rel="stylesheet" href="styles.css">
<div class="menu">
    <ul>
        <li><a href="#">Главная</a></li>
        <li><a href="#">О компании</a></li>
        <li><a href="#">Покупателям</a></li>
        <li>
            <a class="menu-caret" href="#">Акции</a>
            <ul>
                <li><a href="#">Акция 1</a></li>
                <li><a href="#">Акция 2</a></li>
                <li><a href="#">Акция 3</a></li>
                <li><a href="#">Акция 4</a></li>
                <li><a href="#">Акция 5</a></li>
            </ul>
        </li>
        <li><a href="#">Новости</a></li>
        <li><a href="#">Контакты</a></li>
    </ul>
</div>
<!--<div style="float: left; width: 50%; position: absolute ;">-->
<table class='aaa'>
    <tr class='aaa'>
        <td class='aaa'>
<?php
    session_start();
    include('config.php');
    //echo $_SESSION['user_id'];
    echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
    if ($_SESSION['user_group']!="client") {
        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT t2.*, DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t GROUP by t.order_id) ORDER BY t2.order_id");
        //$query->bindParam("user_id", $user_id, PDO::PARAM_STR);
        $query->execute();
        $current_record="";
        //$result = $query->fetch(PDO::FETCH_ASSOC);

        
        echo "<form class='table' method='GET' action=''>";
        echo "  <table><tr><th>Номер заказа</th><th>Дата изменения</th><th>Статус (для клиента)</th><th>Комментарий (для сотрудников)</th><th>Подробно</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["order_id"] . "</td>";
            echo "<td>" . $row["formated_row_creation_time"] . "</td>";
            echo "<td>" . $row["order_status"] . "</td>";
            echo "<td>" . $row["order_description"] . "</td>";
        //echo "<td> <button type='submit' name='detail' value='".$row["order_id"]."'>Подробно</button> </td>";
            echo "<td> <a href='lk.php?action=detail&id=".$row["order_id"]."'>Подробно</a> </td>";
                        
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

        echo "<form class='table'>";
        echo "  <table><tr><th>Номер заказа</th><th>дата изменения</th><th>статус</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["order_id"] . "</td>";
            echo "<td>" . $row["formated_row_creation_time"] . "</td>";
            echo "<td>" . $row["order_status"] . "</td>";
            echo "</tr>";
        }
        echo "  </table>";
        echo "</form>";
    }
?>

<!--</div>-->

</td>
<td class='aaa'>
    <!--<div style="width: 100%; position: relative; padding-left: 50%">-->
    <!--<div style="width: 100%;>-->
        <img src="Mayya_1.png" alt="" width="500">
    <!--</div>-->
    aaa
</td>
</tr>
</table>
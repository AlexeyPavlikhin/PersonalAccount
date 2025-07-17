<link rel="stylesheet" href="styles.css">

<!--<div style="float: left; width: 50%; position: absolute ;">-->

<?php
    //error_reporting(0);
    session_start();
    include('config.php');
    //echo $_SESSION['user_id'];
    //echo "<header class='gTFIBn'> aaa </header>";
    //echo "<a href='login.php' class='right'>Выход</a><br/>";
    echo "<div class='menu-bar'>";
    echo "  <ul>";
    echo "      <li class='right'>";
    echo "          ".$_SESSION['user_name'];
    echo "          <ul>";
    echo "              <li><a href='#'>Профиль</a></li>";
    echo "              <li><a href='login.php'>Выход</a></li>";
    echo "          </ul>";
    echo "        </li>";
    echo "    </ul>";
    echo "</div>";


    //echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
    
    if ($_SESSION['user_group']!="client") {
        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT t2.*, DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t GROUP by t.order_id) ORDER BY t2.order_id");
        //$query->bindParam("user_id", $user_id, PDO::PARAM_STR);
        $query->execute();
        $current_record="";
        //$result = $query->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='menu'>";
        echo "    <ul>";
        echo "        <li><a class='menu_button' href='lk.php'><div class='menu_button_text'>Управление заказами</div></a></li>";
        echo "        <li><a class='menu_button' href='uc.php'><div class='menu_button_text'>Управление пользователями</div></a></li>";
        echo "        <li><a class='menu_button' href='sales.php'><div class='menu_button_text'>Управление продажами</div></a></li>";
        echo "    </ul>";
        echo "</div>";
        
        echo "<form method='GET' action=''>";
        echo "  <table class='db_data'>";
        echo "      <tr>";
        echo "          <th class='db_data' width='5%'>№ заказа</th>";
        echo "          <th class='db_data' width='10%'>Дата изменения</th>";
        echo "          <th class='db_data' width='25%'>Статус (для клиента)</th>";
        echo "          <th class='db_data' width='50%'>Комментарий (для сотрудников)</th>";
        echo "          <th class='db_data' width='10%'>Подробно</th>";
        echo "      </tr>";
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
        
        /*
        if (isset($_GET['detail'])) {
            $_SESSION['detail_order_id'] = $_GET['detail'];
            header('Location: order_details.php');
        }
        */

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


<link href="./css/styles.css" rel="stylesheet">
<link href="./css/jost.css" rel="stylesheet">
<title>Личный кабинет: Управление заказами</title>
<link rel="icon" type="image/png" sizes="32x32" href="./pictures/Iogo-1.png" media="(prefers-color-scheme: light)">
<link rel="icon" type="image/png" sizes="32x32" href="-./pictures/Iogo-2.png" media="(prefers-color-scheme: dark)">
<link rel="icon" type="image/svg+xml" sizes="any" href="./pictures/Iogo-4.svg">
<link rel="apple-touch-icon" type="image/png" href="./pictures/Iogo-3.png">
<link rel="icon" type="image/png" sizes="192x192" href="./pictures/Iogo-3.png">

<?php
    //error_reporting(0);
    ob_start();
    session_start();
    include('config.php');

    echo "<html>";
    echo "<header class='my_header'>";
    echo "  <div class='logo'> </div>";
    echo "  <div class='my_header_polygon'></div>";
    echo "</header>";

    echo "<header class='my_header2'>";
    echo "<div class='menu-bar'>";
    echo "  <ul>";
    echo "      <li class='right'>";
    echo "          ".$_SESSION['current_user_name'];
    echo "          <ul>";
    echo "              <!--li><a href='#'>Профиль</a></li-->";
    echo "              <li><a href='login.php'>Выход</a></li>";
    echo "          </ul>";
    echo "        </li>";
    echo "    </ul>";
    echo "</div>";
    echo "</header>";
    echo "<br/><br/>";
    echo "<main>";

    if(!isset($_SESSION['current_user_id'])){
        header('Location: login.php');
        exit;
    } else {
        if ($_SESSION['current_user_group']!="client") {
            $user_id = $_SESSION['current_user_id'];
            $query = $connection->prepare("SELECT t2.*, DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t GROUP by t.order_id) ORDER BY t2.order_id");
            //$query->bindParam("user_id", $user_id, PDO::PARAM_STR);
            $query->execute();
            $current_record="";
            //$result = $query->fetch(PDO::FETCH_ASSOC);
            
            echo "<div class='menu'>";
            echo "    <ul>";
            echo "        <li><a class='menu_button_atcive' href='lk.php'><div class='menu_button_text_active'>Управление заказами</div></a></li>";
            echo "        <li><a class='menu_button' href='uc.php'><div class='menu_button_text'>Управление пользователями</div></a></li>";
            echo "        <li><a class='menu_button' href='sales.php'><div class='menu_button_text'>Управление продажами</div></a></li>";
            echo "    </ul>";
            echo "</div>";
            
            echo "<form method='GET' action=''>";
            echo "  <table class='msll_table'>";
            echo "      <tr>";
            echo "          <th width='5%'>№ заказа</th>";
            echo "          <th width='10%'>Дата изменения</th>";
            echo "          <th width='25%'>Статус (для клиента)</th>";
            echo "          <th width='50%'>Комментарий (для сотрудников)</th>";
            echo "          <th width='10%'>Подробно</th>";
            echo "      </tr>";
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row["order_id"] . "</td>";
                echo "<td>" . $row["formated_row_creation_time"] . "</td>";
                echo "<td>" . $row["order_status"] . "</td>";
                echo "<td>" . $row["order_description"] . "</td>";
                echo "<td> <a href='lk.php?action=detail&id=".$row["order_id"]."'>Подробно</a> </td>";
                            
                echo "</tr>";
            }
            echo "  </table>";
            
            echo "</form>";

            echo "<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>";

            if (isset($_GET['action']) && $_GET['action'] == 'detail') {
                $_SESSION['detail_order_id'] = $_GET['id'];
                header('Location: order_details.php');
                ob_get_flush();
            }


        } else {
            $user_id = $_SESSION['current_user_id'];
            $query = $connection->prepare("SELECT t2.*, DATE_FORMAT(t2.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time' FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t WHERE t.client_id =:user_id GROUP by t.order_id) ORDER BY t2.order_id");
            $query->bindParam("user_id", $user_id, PDO::PARAM_STR);
            $query->execute();

            echo "<form class='table'>";
            echo "  <table class='msll_table'>";
            echo "  <tr>";
            echo "      <th width='5%'>Номер заказа</th>";
            echo "      <th width='10%'>дата изменения</th>";
            echo "      <th width='85%'>статус</th>";
            echo "  </tr>";
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
    }
    echo "<br/><br/>";
    echo "</main>";
    echo "<footer class='msll_footer'>";
    echo "  <div class='msll_footer_polygon_dark_gray'></div>";
    echo "  <div class='msll_footer_polygon_light_gray'></div>";
    echo "  <div class='msll_footer_polygon_red'></div>";
    echo "</footer>";
    echo "</html>";


?>

<!--</div>-->


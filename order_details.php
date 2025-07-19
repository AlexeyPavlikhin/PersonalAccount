<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

<?php
    //error_reporting(0);
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
    echo "          ".$_SESSION['user_name'];
    echo "          <ul>";
    echo "              <li><a href='#'>Профиль</a></li>";
    echo "              <li><a href='login.php'>Выход</a></li>";
    echo "          </ul>";
    echo "        </li>";
    echo "    </ul>";
    echo "</div>";
    echo "</header>";
    echo "<br/><br/>";
    echo "<main>";

    $order_id = $_SESSION['detail_order_id'];
    $query = $connection->prepare("SELECT o.*, DATE_FORMAT(o.row_creation_time, '%d.%m.%Y %H:%i:%s') as 'formated_row_creation_time', u.username FROM orders o, users u WHERE o.order_id =:order_id and o.operator_id=u.id order by o.id desc");
    $query->bindParam("order_id", $order_id, PDO::PARAM_STR);
    $query->execute();
    $current_record="";
    //$result = $query->fetch(PDO::FETCH_ASSOC);

    echo "<p class='success'>Детальная информация о заказе номер ".$order_id."</p>";
    echo "<form class='table' method='GET' action=''>";
    echo "  <table class='msll_table'>";
    echo "      <tr>";
    echo "          <th width='15%'>Дата изменения</th>";
    echo "          <th width='15%'>Оператор</th>";
    echo "          <th width='35%'>Статус (для клиента)</th>";
    echo "          <th width='35%'>Комментарий (для сотрудников)</th>";
    echo "      </tr>";

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row["formated_row_creation_time"] . "</td>";
        echo "<td>" . $row["username"] . "</td>";
        echo "<td>" . $row["order_status"] . "</td>";            
        echo "<td>" . $row["order_description"] . "</td>";
        echo "</tr>";
    }
    echo "  </table>";

    echo "<br>";
    echo "<tr>";
    echo "<td> <button class='msll_button' type='submit' name='close_form' value='close_form'>Закрыть</button> </td>";
    echo "<td> <button class='msll_button' type='submit' name='change' value='change'>Изменить статус</button> </td>";                
    echo "</tr>";
    echo "</form>";
    
    
    if (isset($_GET['close_form'])) {
        header('Location: lk.php');
    }

    if (isset($_GET['change'])) {
        header('Location: order_editor.php');
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


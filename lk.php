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
            echo "<td> <button type='submit' name='detail' value='".$row["order_id"]."'>Подробно</button> </td>";
            echo "</tr>";
        }
        echo "  </table>";
        
        echo "</form>";
        
        
        if (isset($_GET['detail'])) {
            $_SESSION['detail_order_id'] = $_GET['detail'];
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

<link rel="stylesheet" href="styles.css">

<nav class="t450__menu">
    <ul role="list" class="t450__list t-menu__list">
        <li class="t450__list_item"> <a class="t-menu__link-item " href="/" data-menu-submenu-hook=""
                data-menu-item-number="1">
                Главная
            </a> </li>
        <li class="t450__list_item"> <a class="t-menu__link-item " href="/uslugi" data-menu-submenu-hook=""
                data-menu-item-number="2">
                Услуги
            </a> </li>
        <li class="t450__list_item"> <a class="t-menu__link-item " href="/kursy-i-vebinary" data-menu-submenu-hook=""
                data-menu-item-number="3">
                Курсы и&nbsp;вебинары
            </a> </li>
        <li class="t450__list_item"> <a class="t-menu__link-item " href="/articles" data-menu-submenu-hook=""
                data-menu-item-number="4">
                Статьи
            </a> </li>
        <li class="t450__list_item"> <a class="t-menu__link-item " href="/events" data-menu-submenu-hook=""
                data-menu-item-number="5">
                Мероприятия
            </a> </li>
        <li class="t450__list_item"> <a class="t-menu__link-item " href="/kontaktyi" data-menu-submenu-hook=""
                data-menu-item-number="6">
                Контакты и вакансии
            </a> </li>
    </ul>
</nav>

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
<div > 
    <img class="tn-atom__img"
            src="https://static.tildacdn.com/tild3830-3137-4438-b536-396362626530/Mayya_1.png" alt=""
            imgfield="tn_img_1694086384364"> </div>
</div>
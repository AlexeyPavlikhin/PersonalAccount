<link rel="stylesheet" href="styles.css">

<?php
    //error_reporting(0);
    session_start();
    include('config.php');
    
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

    if ($_SESSION['user_group']!= null and $_SESSION['user_group']!= 'client' ) {
//        echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
        echo "<div class='menu'>";
        echo "    <ul>";
        echo "        <li><a class='menu_button' href='lk.php'><div class='menu_button_text'>Управление заказами</div></a></li>";
        echo "        <li><a class='menu_button' href='uc.php'><div class='menu_button_text'>Управление пользователями</div></a></li>";
        echo "        <li><a class='menu_button_atcive' href='sales.php'><div class='menu_button_text_active'>Управление продажами</div></a></li>";
        echo "    </ul>";
        echo "</div>";

        echo "<p class='success'> Доступ открыт!</p>";


    } else {
        echo "<p class='error'> Доступ закрыт!</p>";
    }

?>


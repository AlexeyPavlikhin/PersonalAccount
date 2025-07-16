<link rel="stylesheet" href="styles.css">


<?php
    //error_reporting(0);
    session_start();
    include('config.php');
    
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
    //echo "<a href='login.php' class='right'>Выход</a><br/>";
        if ($_SESSION['user_group']!= null and $_SESSION['user_group']!= 'client' ) {
//        echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
        echo "<div class='menu'>";
        echo "    <ul>";
        echo "        <li><a href='lk.php'>Управление заказами</a></li>";
        echo "        <li><a href='uc.php'>Управление пользователями</a></li>";
        echo "        <li><a href='sales.php'>Управление продажами</a></li>";
        echo "    </ul>";
        echo "</div>";

        echo "<p class='success'> Доступ открыт!</p>";


    } else {
        echo "<p class='error'> Доступ закрыт!</p>";
    }

?>


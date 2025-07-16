<link rel="stylesheet" href="styles.css">


<?php
    //error_reporting(0);
    session_start();
    include('config.php');
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
    if ($_SESSION['user_group']!= null and $_SESSION['user_group']!= 'client' ) {
        //echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
        echo "<div class='menu'>";
        echo "    <ul>";
        echo "        <li><a href='lk.php'>Управление заказами</a></li>";
        echo "        <li><a href='uc.php'>Управление пользователями</a></li>";
        echo "        <li><a href='sales.php'>Управление продажами</a></li>";
        echo "    </ul>";
        echo "</div>";

        

        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT * FROM users u order by u.login");
        $query->execute();
        $current_record="";
        
        
        echo "<form class='table' method='GET' action=''>";
        echo "  <table class='db_data'>";
        echo "      <tr class='db_data'>";
        echo "          <th class='db_data'>Login</th>";
        echo "          <th class='db_data'>Имя пользователя</th>";
        echo "          <th class='db_data'>E-mail</th>";
        echo "          <th class='db_data'>Группа пользователей</th>";
        echo "          <th class='db_data'><div class='menu'><a href='create_new_user.php'>Новый пользователь</a></div></th>";
        echo "      </tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td class='db_data'>" . $row["login"] . "</td>";
            echo "<td class='db_data'>" . $row["username"] . "</td>";
            echo "<td class='db_data'>" . $row["email"] . "</td>";
            echo "<td class='db_data'>" . $row["user_group"] . "</td>";
            echo "<td class='db_data'> <a href='uc.php?action=edit_user&id=".$row["id"]."'>Изменить</a> </td>";
            echo "</tr>";
        }
        echo "  </table>";
                
        echo "</form>";

        if (isset($_GET['action'])) {
            $_SESSION['edit_user_id'] = $_GET['id'];
            header('Location: edit_user.php');
        }

    } else {
        echo "<p class='error'> Доступ закрыт!</p>";
    }

?>


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
    echo "<main>";
    echo "<br/><br/>";
    

    if ($_SESSION['user_group']!= null and $_SESSION['user_group']!= 'client' ) {
        
        //echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
        echo "<div class='menu'>";
        echo "    <ul>";
        echo "        <li><a class='menu_button' href='lk.php'><div class='menu_button_text'>Управление заказами</div></a></li>";
        echo "        <li><a class='menu_button_atcive' href='uc.php'><div class='menu_button_text_active'>Управление пользователями</div></a></li>";
        echo "        <li><a class='menu_button' href='sales.php'><div class='menu_button_text'>Управление продажами</div></a></li>";
        echo "    </ul>";
        echo "</div>";

        

        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT * FROM users u order by u.login");
        $query->execute();
        $current_record="";
        
        
        echo "<form class='table' method='GET' action=''>";
        echo "  <table class='msll_table'>";
        echo "      <tr>";
        echo "          <th>Login</th>";
        echo "          <th>Имя пользователя</th>";
        echo "          <th>E-mail</th>";
        echo "          <th>Группа пользователей</th>";
        echo "          <th><div class='menu'><a href='uc.php?create_user'>Новый пользователь</a></div></th>";
        //echo "          <th><div class='menu'><a href='create_new_user.php'>Новый пользователь</a></div></th>";
        echo "      </tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["login"] . "</td>";
            echo "<td>" . $row["username"] . "</td>";
            echo "<td>" . $row["email"] . "</td>";
            echo "<td>" . $row["user_group"] . "</td>";
            echo "<td> <a href='uc.php?action=edit_user&id=".$row["id"]."'>Изменить</a> </td>";
            echo "</tr>";
        }
        echo "  </table>";
                
        echo "</form>";
        

        if (isset($_GET['action'])) {
            $_SESSION['edit_user_id'] = $_GET['id'];

            $_SESSION['save_username']="";
            $_SESSION['save_email']="";
            $_SESSION['save_user_group_value']="";
            $_SESSION['save_user_group_display']="";

            $_SESSION['is_email_dublicat']="no_display";

            header('Location: edit_user.php');
        }

        if (isset($_GET['create_user'])) {
            
            $_SESSION['save_login']="";
            $_SESSION['save_username']="";
            $_SESSION['save_email']="";
            $_SESSION['save_user_group_value']="";
            $_SESSION['save_user_group_display']="------ Выберите группу -----";

            $_SESSION['is_login_dublicat']="no_display";
            $_SESSION['is_email_dublicat']="no_display";

            header('Location: create_new_user.php');
        }

    } else {
        echo "<p class='error'> Доступ закрыт!</p>";
    }
    echo "<br/><br/></main>";
    echo "<footer class='msll_footer'>";
    echo "  <div class='msll_footer_polygon_dark_gray'></div>";
    echo "  <div class='msll_footer_polygon_light_gray'></div>";
    echo "  <div class='msll_footer_polygon_red'></div>";
    echo "</footer>";
    echo "</html>";
?>


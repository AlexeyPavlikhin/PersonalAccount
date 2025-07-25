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

    if ($_SESSION['user_group']!= null and $_SESSION['user_group']!= 'client' ) {
        

        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT * FROM users u where u.id='".$_SESSION['edit_user_id']."'");
        $query->execute();
        $current_record="";
        
        $in_Login = "";

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

            $in_Login=$row['login'];

            if ($_SESSION['save_username']==""){
                 $_SESSION['save_username'] = $row["username"];
            };
            if ($_SESSION['save_email']==""){
                 $_SESSION['save_email'] = $row["email"];
            };
            if ($_SESSION['save_user_group_value']==""){
                 $_SESSION['save_user_group_value'] = $row["user_group"];
            };
            if ($_SESSION['save_user_group_display']==""){
                 $_SESSION['save_user_group_display'] = $row["user_group"];
            };
        }
         $_SESSION['log']= $_SESSION['log']." отрисовка";

        echo "<form class='change_users' method='post' action='' name='signup-form'>";
        echo "  <div class='form-element'>";
        echo "      <h1>Редактирование пользователя</h1>";
        echo "  </div>";

        echo "    <div class='form-element'>";
        echo "        <label>Login</label>";
        echo "        <input type='text' name='Login' pattern='[a-zA-Z0-9]+' required value='".$in_Login."' disabled />";
        echo "    </div>";

        echo "    <div class='form-element'>";
        echo "        <label>Имя пользователя</label>";
        echo "        <input type='text' name='username' required value='".$_SESSION['save_username']."' />";
            
        echo "    </div>";
        echo "    <div class='form-element'>";
        echo "        <label>E-mail</label>";
        echo "        <input type='email' name='email' required value='".$_SESSION['save_email']."' />";
        echo "      <div class='".$_SESSION['is_email_dublicat']."'><p class='error'>Пользователь с таким email уже зарегистрирован!</p></div>";
        echo "    </div>";

        echo "    <div class='form-element'>";
        echo "        <label>Группа</label>";
        echo "        <select name='user_group' required   >";
        echo "            <option value='".$_SESSION['save_user_group_value']."'>".$_SESSION['save_user_group_display']."</option>";
        echo "            <option value='client'>client</option>";
        echo "            <option value='operator'>operator</option>";
        echo "        </select>";
        echo "    </div>";
        
        echo "<button class='msll_button' type='submit' name='btn_update' value='btn_update'>Обновить</button>";
        echo "<button class='msll_button' type='submit' name='btn_cancel' value='btn_cancel' formnovalidate>Отменить</button>";
        echo "<a class='menu_button_atcive' href='uc.php'><div class='menu_button_text_active'>Изменить пароль</div></a><br/>";
        echo "<a class='menu_button_atcive' href='uc.php'><div class='menu_button_text_active'>Удалить пользователя</div></a>";

        echo "</form>";

        $ready_for_update=true;

        if (isset($_POST['btn_cancel'])) {
            header('Location: uc.php');
        }

        if (isset($_POST['btn_update'])) {

            $_SESSION['save_username'] = $_POST['username'];
            $_SESSION['save_email'] = $_POST['email'];
            $_SESSION['save_user_group_value'] = $_POST['user_group']; 
            $_SESSION['save_user_group_display'] = $_POST['user_group']; 

            $_SESSION['is_email_dublicat']="no_display";


            //$login = $_POST['Login'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $user_group = $_POST['user_group'];
            
            //проверяем e-mail (Нет ли дублей)
            $query = $connection->prepare("SELECT * FROM users WHERE email=:email and login<>:login");
            $query->bindParam("email", $email, PDO::PARAM_STR);
            $query->bindParam("login", $in_Login, PDO::PARAM_STR);
            $query->execute();
            
            
            if ($query->rowCount() > 0) {
                //Пользователь с таким email уже зарегистрирован;
                $ready_for_update=false;
                $_SESSION['is_email_dublicat']="display";
            }

            if ($ready_for_update) {
                $query = $connection->prepare("UPDATE users u SET u.email = :email, u.username = :username, u.user_group = :user_group WHERE u.login = :login;");
                $query->bindParam("login", $in_Login, PDO::PARAM_STR);
                $query->bindParam("username", $_SESSION['save_username'], PDO::PARAM_STR);
                $query->bindParam("email", $_SESSION['save_email'], PDO::PARAM_STR);
                $query->bindParam("user_group", $_SESSION['save_user_group_value'], PDO::PARAM_STR);
                $result = $query->execute();
                if ($result) {
                    echo '<p class="success">Регистрация прошла успешно!</p>';
                    header('Location: uc.php');
                } else {
                    echo '<p class="error">Неверные данные!</p>';
                }
            
                
            } else {
                header('Location: edit_user.php');
            }
        }


    } else {
        echo "<p class='error'> Доступ закрыт!</p>";
    }
    echo "<br/><br/><br/><br/>";
    echo "</main>";
    echo "<footer class='msll_footer'>";
    echo "  <div class='msll_footer_polygon_dark_gray'></div>";
    echo "  <div class='msll_footer_polygon_light_gray'></div>";
    echo "  <div class='msll_footer_polygon_red'></div>";
    echo "</footer>";
    echo "</html>";

    
?>

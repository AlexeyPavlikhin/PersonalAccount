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
        $in_username = "";
        $in_email = "";
        $in_user_group = "";

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $in_Login = $row["login"];
            $in_username = $row["username"];
            $in_email = $row["email"];
            $in_user_group = $row["user_group"];
        }
         echo $in_user_group;


        echo "<form class='change_users' method='post' action='' name='signup-form'>";
        echo "    <div class='form-element'>";
        echo "        <label>Login</label>";
        echo "        <input type='text' name='login' pattern='[a-zA-Z0-9]+' required value='".$in_Login."' disabled />";
        echo "    </div>";
        echo "    <div class='form-element'>";
        echo "        <label>Имя пользователя</label>";
        echo "        <input type='text' name='username' required value='".$in_username."' />";
            
        echo "    </div>";
        echo "    <div class='form-element'>";
        echo "        <label>E-mail</label>";
        echo "        <input type='email' name='email' required value='".$in_email."' />";
        echo "    </div>";

        echo "    <div class='form-element'>";
        echo "        <label>Группа</label>";
        echo "        <select name='user_group' required   >";
        echo "            <option value='".$in_user_group."'>".$in_user_group."</option>";
        echo "            <option value='client'>client</option>";
        echo "            <option value='operator'>operator</option>";
        echo "        </select>";
        echo "    </div>";
        
        echo "    <div class='form-element'>";
        echo "    <input type='checkbox' name='scales' checked />";
        echo "    <label>Изменить пароль</label><br/>";
        echo "    <label>Пароль</label>";
        echo "    <input type='password' name='password' required />";
        echo "    <input type='checkbox' name='scales'/>";
        echo "    <label>Показать пароль</label><br/>";
        echo "    </div>";

        echo "<button class='msll_button' type='submit' name='btn_update' value='btn_update'>Обновить</button>";
        echo "<button class='msll_button' type='submit' name='btn_cancel' value='btn_cancel' formnovalidate>Отменить</button>";
        echo "</form>";


        //echo "<form class='table' method='GET' action=''>";
        
        
        //echo "</form>";
        if (isset($_POST['btn_cancel'])) {
            header('Location: uc.php');
        }

        if (isset($_POST['btn_update'])) {
            $login = $_POST['login'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password_hash = password_hash("11234567", PASSWORD_BCRYPT);
            $user_group = $_POST['user_group'];
            
            //проверяем не заводили ли пользователей с таким логинами и e-mail
            $ready_for_creation=true;
            
            //проверяем login
            $query = $connection->prepare("SELECT * FROM users WHERE login=:login");
            $query->bindParam("login", $login, PDO::PARAM_STR);
            $query->execute();
            
            if ($query->rowCount() > 0) {
                echo '<p class="error">Пользователь с таким login уже зарегистрирован!</p>';
                $ready_for_creation=false;
            }

            //проверяем e-mail
            $query = $connection->prepare("SELECT * FROM users WHERE email=:email");
            $query->bindParam("email", $email, PDO::PARAM_STR);
            $query->execute();
            
            if ($query->rowCount() > 0) {
                echo '<p class="error">Пользователь с таким email уже зарегистрирован!</p>';
                $ready_for_creation=false;
            }

            if ($ready_for_creation) {
                $query = $connection->prepare("INSERT INTO users(login,username,password,email,user_group) VALUES (:login,:username,:password_hash,:email,:user_group)");
                $query->bindParam("login", $login, PDO::PARAM_STR);
                $query->bindParam("username", $username, PDO::PARAM_STR);
                $query->bindParam("password_hash", $password_hash, PDO::PARAM_STR);
                $query->bindParam("email", $email, PDO::PARAM_STR);
                $query->bindParam("user_group", $user_group, PDO::PARAM_STR);
                $result = $query->execute();
                if ($result) {
                    echo '<p class="success">Регистрация прошла успешно!</p>';
                } else {
                    echo '<p class="error">Неверные данные!</p>';
                }
            }


            header('Location: uc.php');
        }


    } else {
        echo "<p class='error'> Доступ закрыт!</p>";
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

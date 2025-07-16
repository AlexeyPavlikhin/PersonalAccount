<link rel="stylesheet" href="styles.css">


<?php
    //error_reporting(0);
    session_start();
    include('config.php');
    //echo "<a href='login.php' class='right'>Выход</a><br/>";
    //echo $_SESSION['edit_user_id'];
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


        echo "<form method='post' action='' name='signup-form'>";
        echo "    <div class='form-element'>";
        echo "        <label>Login</label>";
        echo "        <input type='text' name='login' pattern='[a-zA-Z0-9]+' required value='".$in_Login."' />";
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

        echo "<button type='submit' name='btn_register' value='btn_register'>Создать</button>";
        echo "<button type='submit' name='btn_cancel' value='btn_cancel' formnovalidate>Отменить</button>";
        echo "</form>";


        //echo "<form class='table' method='GET' action=''>";
        
        
        //echo "</form>";
        if (isset($_POST['btn_cancel'])) {
            header('Location: uc.php');
        }

        if (isset($_POST['btn_register'])) {
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

?>

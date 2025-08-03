<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

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
    echo "              <li><a href='#'>Профиль</a></li>";
    echo "              <li><a href='login.php'>Выход</a></li>";
    echo "          </ul>";
    echo "        </li>";
    echo "    </ul>";
    echo "</div>";
    echo "</header>";
    echo "<br/><br/>";
    echo "<main>";

    if ($_SESSION['current_user_group']!= null and $_SESSION['current_user_group']!= 'client' ) {
        echo "<form class='change_users' method='post' action='' name='signup-form'>";
        echo "  <div class='form-element'>";
        echo "      <h1>Создание нового пользователя</h1>";
        echo "  </div>";
    
        echo "  <div class='form-element'>";
        echo "      <label>Login</label>";
        echo "      <input type='text' name='login' pattern='[a-zA-Z0-9]+' required value='".$_SESSION['save_login']."' />";
        echo "      <div class='".$_SESSION['is_login_dublicat']."'><p class='error'>Пользователь с таким login уже зарегистрирован!</p></div>";
        echo "  </div>";
        echo "  <div class='form-element'>";
        echo "      <label>Имя пользователя</label>";
        echo "      <input type='text' name='username' required value='".$_SESSION['save_username']."' />";
        echo "  </div>";
        echo "  <div class='form-element'>";
        echo "      <label>E-mail</label>";
        echo "      <input type='email' name='email' required value='".$_SESSION['save_email']."' />";
        echo "      <div class='".$_SESSION['is_email_dublicat']."'><p class='error'>Пользователь с таким email уже зарегистрирован!</p></div>";
        echo "  </div>";
        echo "  <div class='form-element'>";
        echo "      <label>Группа</label>";
        echo "      <select name='user_group' required>";
        echo "          <option value='".$_SESSION['save_user_group_value']."'>".$_SESSION['save_user_group_display']."</option>";
        //echo "          <option value=''>------ Выберите группу -----</option>";
        echo "          <option value='client'>client</option>";
        echo "          <option value='operator'>operator</option>";
        echo "      </select>";
        echo "  </div>";
        echo "  <button class='msll_button' type='submit' name='btn_register' value='btn_register'>Создать</button>";
        echo "  <button class='msll_button' type='submit' name='btn_cancel' value='btn_cancel' formnovalidate>Отменить</button>";
        echo "</form>";




        if (isset($_POST['btn_cancel'])) {
            header('Location: uc.php');
            ob_get_flush();
        }

        if (isset($_POST['btn_register'])) {
            $login = $_POST['login'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password_hash = password_hash("11234567", PASSWORD_BCRYPT);
            $user_group = $_POST['user_group'];
            
            $_SESSION['save_login'] = $_POST['login'];
            $_SESSION['save_username'] = $_POST['username'];
            $_SESSION['save_email'] = $_POST['email'];
            $_SESSION['save_user_group_value'] = $_POST['user_group']; 
            $_SESSION['save_user_group_display'] = $_POST['user_group']; 

            $_SESSION['is_login_dublicat']="no_display";
            $_SESSION['is_email_dublicat']="no_display";



            //проверяем не заводили ли пользователей с таким логинами и e-mail
            $ready_for_creation=true;
            
            //проверяем login
            $query = $connection->prepare("SELECT * FROM users WHERE login=:login");
            $query->bindParam("login", $login, PDO::PARAM_STR);
            $query->execute();
            
            if ($query->rowCount() > 0) {
                //echo '<p class="error">Пользователь с таким login уже зарегистрирован!</p>';
                $ready_for_creation=false;
                $_SESSION['is_login_dublicat']="display";
            }

            //проверяем e-mail
            $query = $connection->prepare("SELECT * FROM users WHERE email=:email");
            $query->bindParam("email", $email, PDO::PARAM_STR);
            $query->execute();
            
            if ($query->rowCount() > 0) {
                //echo '<p class="error">Пользователь с таким email уже зарегистрирован!</p>';
                $ready_for_creation=false;
                $_SESSION['is_email_dublicat']="display";
                
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
                    header('Location: uc.php');
                } else {
                    echo '<p class="error">Неверные данные!</p>';
                    
                }
            } else {
                header('Location: create_new_user.php');
            }
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
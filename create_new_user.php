<link rel="stylesheet" href="styles.css">


<?php
    //error_reporting(0);
    session_start();
    include('config.php');
    echo "<a href='login.php' class='right'>Выход</a><br/>";
    if ($_SESSION['user_group']!= null and $_SESSION['user_group']!= 'client' ) {
        echo  'Уважаемый ',  $_SESSION['user_name'], ', добро пожаловать в личный кабинет!';
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


<form method="post" action="" name="signup-form">
    <div class="form-element">
        <label>Login</label>
        <input type="text" name="login" pattern="[a-zA-Z0-9]+" required />
    </div>
    <div class="form-element">
        <label>Имя пользователя</label>
        <input type="text" name="username" required />
    
    </div>
    <div class="form-element">
        <label>E-mail</label>
        <input type="email" name="email" required />
    </div>

    <div class="form-element">
        <label>Группа</label>
        <select name="user_group" required>
            <option value="">------ Выберите группу -----</option>
            <option value="client">client</option>
            <option value="operator">operator</option>
        </select>
    </div>

<button type="submit" name="btn_register" value="btn_register">Создать</button>
<button type="submit" name="btn_cancel" value="btn_cancel" formnovalidate>Отменить</button>
</form>

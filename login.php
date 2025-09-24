<?php
    session_start();
    include('config.php');
    if (isset($_GET['btn_login'])) {
        $usr_login = $_GET['usr_login'];
        $usr_password = $_GET['usr_password'];
        $query = $connection->prepare("SELECT * FROM users WHERE login=:usr_login");
        $query->bindParam("usr_login", $usr_login, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            echo '<p class="error"> Неверные имя пользователя!</p>';
        } else {
            if (password_verify($usr_password, $result['password'])) {
                $_SESSION['current_user_id'] = $result['id'];
                $_SESSION['current_user_login'] = $result['login'];
                $_SESSION['current_user_group'] = $result['user_group'];
                $_SESSION['current_user_name'] = $result['username'];
                header('Location: lk.php');
            } else {
                echo '<p class="error"> Неверные пароль или имя пользователя!</p>';
            }
        }
    }
?>

<html>
    <head> 
      <title>Личный кабинет: Авторизация</title>
      <link rel="icon" type="image/png" sizes="32x32" href="./pictures/Iogo-1.png" media="(prefers-color-scheme: light)">
      <link rel="icon" type="image/png" sizes="32x32" href="-./pictures/Iogo-2.png" media="(prefers-color-scheme: dark)">
      <link rel="icon" type="image/svg+xml" sizes="any" href="./pictures/Iogo-4.svg">
      <link rel="apple-touch-icon" type="image/png" href="./pictures/Iogo-3.png">
      <link rel="icon" type="image/png" sizes="192x192" href="./pictures/Iogo-3.png">

      <!--
      <link rel="icon" type="image/png" sizes="32x32" href="https://static.tildacdn.com/tild3162-3235-4463-a131-323537306264/Iogo-1.png" media="(prefers-color-scheme: light)">
      <link rel="icon" type="image/png" sizes="32x32" href="https://static.tildacdn.com/tild3033-6130-4161-b163-663165306565/Iogo-2.png" media="(prefers-color-scheme: dark)">
      <link rel="icon" type="image/svg+xml" sizes="any" href="https://static.tildacdn.com/tild3132-6565-4736-a237-326261313366/Iogo-4.svg">
      <link rel="apple-touch-icon" type="image/png" href="https://static.tildacdn.com/tild6530-3732-4831-b536-393362383539/Iogo-3.png">
      <link rel="icon" type="image/png" sizes="192x192" href="https://static.tildacdn.com/tild6530-3732-4831-b536-393362383539/Iogo-3.png">
        -->


      <link href="./css/styles.css" rel="stylesheet">
      <link href="./css/jost.css" rel="stylesheet">

      <!--<link rel="stylesheet" href="styles2.css">-->
    </head> 
    <body>
      <form method="get" action="" name="signin-form">
        <div class="form_login">
          <div>
            <label>Логин</label>
            <input class="msll_filter" type="text" name="usr_login" pattern="[a-zA-Z0-9]+" required />
          </div>
          <br/>
          <div>
            <label>Пароль</label>
            <input class="msll_filter" type="password" name="usr_password" required />
          </div>
          <button type="submit" class="msll_button" name="btn_login" value="login">Авторизоваться</button>
        </div>
      </form>
    </body>
</html>      
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
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_group'] = $result['user_group'];
                $_SESSION['user_name'] = $result['username'];
                echo '<p class="success">Поздравляем, вы прошли авторизацию!</p>';
                header('Location: lk.php');
            } else {
                echo '<p class="error"> Неверные пароль или имя пользователя!</p>';
            }
        }
    }
?>

<link rel="stylesheet" href="styles2.css">
<form method="get" action="" name="signin-form">
  <div class="form-element">
    <label>Login</label>
    <input type="text" name="usr_login" pattern="[a-zA-Z0-9]+" required />
  </div>
  <div class="form-element">
    <label>Password</label>
    <input type="password" name="usr_password" required />
  </div>
  <button type="submit" name="btn_login" value="login">Log In</button>
</form>
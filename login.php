<?php
    session_start();
    include('config.php');
    require_once __DIR__ . '/inc/device_type.php';
    require_once __DIR__ . '/inc/favicon_href.php';

    $dt = DeviceType();

    if (isset($_POST['btn_login'])) {
        $usr_login = trim($_POST['usr_login']);
        $usr_password = trim($_POST['usr_password']);
        if ($usr_login != ""){
          $query = $connection->prepare("SELECT * FROM users WHERE login=:usr_login");
          $query->bindParam("usr_login", $usr_login, PDO::PARAM_STR);
          $query->execute();
          $result = $query->fetch(PDO::FETCH_ASSOC);
          if (!$result) {
              write_log($usr_login, "Авторизация. ".$dt, "Ошибка: Неверный login <".$_SERVER['HTTP_USER_AGENT'].">");
              if ($dt == "mobile"){
                echo '<p class="error_mobile"> Неверные login или пароль!</p>';
              } else {
                echo '<p class="error"> Неверные login или пароль!</p>';
              }
              
          } else {
              if (password_verify($usr_password, $result['password'])) {
                  $_SESSION['current_user_id'] = $result['id'];
                  $_SESSION['current_user_login'] = $result['login'];
                  $_SESSION['current_user_group'] = $result['user_group'];
                  $_SESSION['current_user_name'] = $result['username'];
                  write_log($usr_login, "Авторизация. ".$dt, "Успех <".$_SERVER['HTTP_USER_AGENT'].">");
                  header('Location: ./');
              } else {
                  write_log($usr_login, "Авторизация. ".$dt, "Ошибка: Неверный пароль <".$_SERVER['HTTP_USER_AGENT'].">");
                  if ($dt == "mobile"){
                    echo '<p class="error_mobile"> Неверные login или пароль!</p>';
                  } else {
                    echo '<p class="error"> Неверные login или пароль!</p>';
                  }
              }
          }
        }
    }

    function write_log($in_user_login, $in_operation_type, $in_event_data) {
        include('config.php');
        try {
            $sql_audit = 
            "INSERT 
                INTO audit 
                (
                    user_login, 
                    operation_type, 
                    event_data
                ) VALUES (
                    '".$in_user_login."', 
                    '".$in_operation_type."', 
                    '".$in_event_data."'
                )
            ";
            $query = $connection->prepare($sql_audit);
            $query->execute();
        } catch(PDOException $e) {
            //echo $e->getMessage()." ".$sql_audit;
        }     
            
        return 0;
    }

?>

<html>
    <head> 
      <title>Личный кабинет: Авторизация</title>
      <link rel="icon" type="image/png" sizes="any" href="<?= htmlspecialchars(msll_site_root_href('pictures/logo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(msll_site_root_href('pictures/logo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(msll_favicon_href(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

      <!--
      <link rel="icon" type="image/png" sizes="32x32" href="https://static.tildacdn.com/tild3162-3235-4463-a131-323537306264/Iogo-1.png" media="(prefers-color-scheme: light)">
      <link rel="icon" type="image/png" sizes="32x32" href="https://static.tildacdn.com/tild3033-6130-4161-b163-663165306565/Iogo-2.png" media="(prefers-color-scheme: dark)">
      <link rel="icon" type="image/svg+xml" sizes="any" href="https://static.tildacdn.com/tild3132-6565-4736-a237-326261313366/Iogo-4.svg">
      <link rel="apple-touch-icon" type="image/png" href="https://static.tildacdn.com/tild6530-3732-4831-b536-393362383539/Iogo-3.png">
      <link rel="icon" type="image/png" sizes="192x192" href="https://static.tildacdn.com/tild6530-3732-4831-b536-393362383539/Iogo-3.png">
        -->


      <link href="./css/styles.css?v=<?=$ASSET_VER?>" rel="stylesheet">
      <link href="./css/jost.css?v=<?=$ASSET_VER?>" rel="stylesheet">

      <!--<link rel="stylesheet" href="styles2.css">-->
    </head> 
    <body>
      <form method="post" action="" name="signin-form">
        <div class="form_login_<?php echo $dt; ?>">
          <img class="login_logo_<?php echo $dt; ?>" src="./pictures/logo.png?v=<?=$ASSET_VER?>" alt="Лаборатория права Майи Саблиной">
          <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok') { ?>
            <p class="<?php echo $dt === 'mobile' ? 'success_mobile' : 'success'; ?>">Пароль успешно изменён. Войдите с новым паролем.</p>
          <?php } ?>
          <div>
            <label class="form_login_label_<?php echo $dt; ?>">Логин</label>
            <!--input class="msll_filter" type="text" name="usr_login" pattern="[a-zA-Z0-9]+" required /-->
            <input class="msll_filter_<?php echo $dt; ?>"  type="text" name="usr_login" required />
          </div>
          <br/>
          <div>
            <label class="form_login_label_<?php echo $dt; ?>">Пароль</label>
            <input class="msll_filter_<?php echo $dt; ?>" type="password" name="usr_password" required />
          </div>
          <p class="login_forgot_row_<?php echo $dt; ?>"><a class="login_forgot_link" href="forgot_password.php">Забыли пароль?</a></p>
          <button type="submit" class="msll_button_<?php echo $dt; ?>" name="btn_login" value="login">Авторизоваться</button>
        </div>
      </form>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const form = document.querySelector('form[name="signin-form"]');
          if (!form) return;

          form.addEventListener('submit', function () {
            const loginInput = form.querySelector('input[name="usr_login"]');
            const passwordInput = form.querySelector('input[name="usr_password"]');

            if (loginInput) loginInput.value = loginInput.value.trim();
            if (passwordInput) passwordInput.value = passwordInput.value.trim();
          });
        });
      </script>
    </body>
</html>      
<?php
    session_start();
    include('config.php');

    if (isset($_POST['btn_login'])) {
        $usr_login = $_POST['usr_login'];
        $usr_password = $_POST['usr_password'];
        if ($usr_login != ""){
          $query = $connection->prepare("SELECT * FROM users WHERE login=:usr_login");
          $query->bindParam("usr_login", $usr_login, PDO::PARAM_STR);
          $query->execute();
          $result = $query->fetch(PDO::FETCH_ASSOC);
          if (!$result) {
              write_log($usr_login, "Авторизация. ".DeviceType(), "Ошибка: Неверный login <".$_SERVER['HTTP_USER_AGENT'].">");
              if (DeviceType() == "mobile"){
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
                  write_log($usr_login, "Авторизация. ".DeviceType(), "Успех <".$_SERVER['HTTP_USER_AGENT'].">");
                  header('Location: ./');
              } else {
                  write_log($usr_login, "Авторизация. ".DeviceType(), "Ошибка: Неверный пароль <".$_SERVER['HTTP_USER_AGENT'].">");
                  if (DeviceType() == "mobile"){
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

    function DeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Регулярные выражения для мобильных устройств
        $patterns = [
            '/Android/i',
            '/webOS/i',
            '/iPhone/i',
            '/iPad/i',
            '/iPod/i',
            '/BlackBerry/i',
            '/Windows Phone/i',
            '/Opera Mini/i',
            '/IEMobile/i',
            '/Mobile/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return "mobile";
            }
        }
        
        return "desktop";
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


      <link href="./css/styles.css?v=1.0.1" rel="stylesheet">
      <link href="./css/jost.css" rel="stylesheet">

      <!--<link rel="stylesheet" href="styles2.css">-->
      <!-- <-?php echo DeviceType();?> -->

    </head> 
    <body>
      <form method="post" action="" name="signin-form">
        <div class="form_login_<?php echo DeviceType();?>">
            <!--< ?php echo DeviceType();?> -->
          <img class="login_logo_<?php echo DeviceType();?>" src="./pictures/logo.png" alt="Лаборатория права Майи Саблиной">  
          <div>
            <label class="form_login_label_<?php echo DeviceType();?>">Логин</label>
            <!--input class="msll_filter" type="text" name="usr_login" pattern="[a-zA-Z0-9]+" required /-->
            <input class="msll_filter_<?php echo DeviceType();?>"  type="text" name="usr_login" required />
          </div>
          <br/>
          <div>
            <label class="form_login_label_<?php echo DeviceType();?>">Пароль</label>
            <input class="msll_filter_<?php echo DeviceType();?>" type="password" name="usr_password" required />
          </div>
          <button type="submit" class="msll_button_<?php echo DeviceType();?>" name="btn_login" value="login">Авторизоваться</button>
        </div>
      </form>
    </body>
</html>      
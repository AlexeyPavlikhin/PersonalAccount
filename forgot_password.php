<?php
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/inc/device_type.php';
    require_once __DIR__ . '/inc/favicon_href.php';
    require_once __DIR__ . '/inc/audit_log.php';

    $dt = DeviceType();

    require_once __DIR__ . '/libs/PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/libs/PHPMailer-master/src/SMTP.php';
    require_once __DIR__ . '/libs/PHPMailer-master/src/Exception.php';

    use PHPMailer\PHPMailer\PHPMailer;

    $feedback_error = '';
    $feedback_success = '';

    if (isset($_POST['btn_forgot'])) {
        $ident = trim((string) ($_POST['login_or_email'] ?? ''));
        if ($ident === '') {
            $feedback_error = 'Введите логин или адрес e-mail.';
        } else {
            try {
                $stmt = $connection->prepare(
                    'SELECT id, username, login, email FROM users WHERE login = :ident OR LOWER(TRIM(email)) = LOWER(:ident_em)'
                );
                $stmt->execute(['ident' => $ident, 'ident_em' => $ident]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $user = false;
                $feedback_error = 'Временная ошибка сервера. Попробуйте позже.';
            }

            if ($feedback_error === '' && !$user) {
                $feedback_error = 'Учётная запись с таким логином или адресом e-mail не найдена.';
            } elseif ($feedback_error === '' && $user) {
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);

                try {
                    // Время жизни задаём через БД (DATE_ADD NOW()), чтобы совпадало с проверкой password_reset_expires > NOW()
                    // и не ломалось при расхождении часового пояса PHP и MySQL.
                    $upd = $connection->prepare(
                        'UPDATE users SET password_reset_token_hash = :h, password_reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id'
                    );
                    $upd->execute([
                        'h' => $token_hash,
                        'id' => $user['id'],
                    ]);
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    msll_audit_write(
                        $connection,
                        $user['login'],
                        'Восстановление пароля. Запрос временного токена. ' . $dt,
                        'Токен сохранён, срок 1 ч. <' . $ua . '>'
                    );
                } catch (PDOException $e) {
                    $feedback_error = 'Не удалось сохранить запрос. Попробуйте позже.';
                }

                if ($feedback_error === '') {
                    $base = rtrim(APP_PUBLIC_BASE_URL, '/');
                    $reset_url = $base . '/reset_password.php?t=' . rawurlencode($token);

                    $sent = send_password_reset_mail($user['username'], $user['login'], $user['email'], $reset_url);

                    if ($sent) {
                        $feedback_success = 'На адрес электронной почты, указанный в учётной записи, отправлены инструкции по восстановлению пароля.';
                    } else {
                        try {
                            $clr = $connection->prepare(
                                'UPDATE users SET password_reset_token_hash = NULL, password_reset_expires = NULL WHERE id = :id'
                            );
                            $clr->execute(['id' => $user['id']]);
                        } catch (PDOException $e) {
                            // ignore
                        }
                        $feedback_error = 'Не удалось отправить письмо. Попробуйте позже или обратитесь в поддержку.';
                    }
                }
            }
        }
    }

    /**
     * @return bool true если SMTP отправил письмо
     */
    function send_password_reset_mail($username, $login, $to_email, $reset_url) {
        $email = EML_EMAIL_FROM;
        $pass = EML_PASSWORD;
        $name = EML_NAME_FROM;

        $subject = 'Восстановление пароля — Лаборатория права Майи Саблиной';
        $body = '<p>Здравствуйте, ' . htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</p>'
            . '<p>Вы запросили восстановление пароля для личного кабинета.</p>'
            . '<p>Перейдите по ссылке, чтобы задать новый пароль (ссылка действительна 1 час):<br/>'
            . '<a href="' . htmlspecialchars($reset_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($reset_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>'
            . '<p>Логин: ' . htmlspecialchars($login, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<p>Если вы не запрашивали восстановление, проигнорируйте это письмо.</p>'
            . '<p>С заботой,<br/>команда Лаборатории права Майи Саблиной<br/>'
            . '+7 (995) 787-95-77<br/>info@msablina.ru<br/>'
            . '<a href="http://www.msablina.ru/" target="_blank">www.msablina.ru</a></p>';

        $mail = new PHPMailer(true);

        try {
            $mail->Host = EML_HOST;
            $mail->Port = (int) EML_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPDebug = 0;
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPAutoTLS = false;
            $mail->Username = $email;
            $mail->Password = $pass;
            $mail->setFrom($email, $name);
            $mail->addAddress($to_email);
            $mail->Subject = $subject;
            $mail->msgHTML($body);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
?>
<html>
    <head>
      <link href="./css/styles.css?v=<?=$ASSET_VER?>" rel="stylesheet">
      <link href="./css/jost.css?v=<?=$ASSET_VER?>" rel="stylesheet">
      <title>Личный кабинет: Восстановление пароля</title>
      <link rel="icon" type="image/png" sizes="any" href="<?= htmlspecialchars(msll_site_root_href('pictures/logo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(msll_site_root_href('pictures/logo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(msll_favicon_href(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </head>
    <body>
      <form method="post" action="" name="forgot-form" autocomplete="off">
        <div class="form_login_<?php echo $dt; ?>">
          <img class="login_logo_<?php echo $dt; ?>" src="./pictures/logo.png?v=<?=$ASSET_VER?>" alt="Лаборатория права Майи Саблиной">

          <?php if ($feedback_success !== '') { ?>
            <p class="<?php echo $dt === 'mobile' ? 'success_mobile' : 'success'; ?>"><?php echo htmlspecialchars($feedback_success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
          <?php } ?>
          <?php if ($feedback_error !== '') { ?>
            <p class="<?php echo $dt === 'mobile' ? 'error_mobile' : 'error'; ?>"><?php echo htmlspecialchars($feedback_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
          <?php } ?>

          <?php if ($feedback_success === '') { ?>
          <div>
            <label class="form_login_label_<?php echo $dt; ?>">Логин или e-mail</label>
            <input class="msll_filter_<?php echo $dt; ?>" type="text" name="login_or_email" required
              value="<?php echo isset($_POST['login_or_email']) ? htmlspecialchars(trim((string) $_POST['login_or_email']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ''; ?>" />
          </div>
          <button type="submit" class="msll_button_<?php echo $dt; ?>" name="btn_forgot" value="1">Отправить ссылку</button>
          <?php } ?>

          <p class="login_aux_links_<?php echo $dt; ?>"><a href="login.php">Вернуться ко входу</a></p>
        </div>
      </form>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const form = document.querySelector('form[name="forgot-form"]');
          if (!form) return;
          const inp = form.querySelector('input[name="login_or_email"]');
          if (inp) {
            form.addEventListener('submit', function () {
              inp.value = inp.value.trim();
            });
          }
        });
      </script>
    </body>
</html>

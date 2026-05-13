<?php
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/inc/device_type.php';
    require_once __DIR__ . '/inc/favicon_href.php';
    require_once __DIR__ . '/inc/audit_log.php';

    $dt = DeviceType();

    $token_raw = isset($_POST['t']) ? trim((string) $_POST['t']) : '';
    if ($token_raw === '' && isset($_GET['t'])) {
        $token_raw = trim((string) $_GET['t']);
    }
    // Токен из bin2hex — только [0-9a-f]. Часть клиентов поднимает регистр в URL; SHA-256 от «ABC» ≠ от «abc» — без strtolower проверка не сработает.
    if ($token_raw !== '') {
        $token_raw = strtolower($token_raw);
    }

    $token_hash = $token_raw !== '' ? hash('sha256', $token_raw) : '';

    function load_user_by_reset_token(PDO $connection, $token_hash) {
        if ($token_hash === '') {
            return null;
        }
        try {
            $stmt = $connection->prepare(
                'SELECT id, login FROM users WHERE password_reset_token_hash = :h AND password_reset_expires > NOW() LIMIT 1'
            );
            $stmt->execute(['h' => $token_hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    $user = $token_hash !== '' ? load_user_by_reset_token($connection, $token_hash) : null;

    $fatal_invalid = false;
    $form_error = '';

    if (isset($_POST['btn_reset'])) {
        $user = $token_hash !== '' ? load_user_by_reset_token($connection, $token_hash) : null;
        if (!$user) {
            $fatal_invalid = true;
            $form_error = 'Ссылка недействительна или срок её действия истёк. Запросите восстановление пароля снова.';
        } else {
            $pw = trim((string) ($_POST['new_password'] ?? ''));
            $pw2 = trim((string) ($_POST['new_password_confirm'] ?? ''));
            if (strlen($pw) < 8) {
                $form_error = 'Пароль должен быть не короче 8 символов.';
            } elseif ($pw !== $pw2) {
                $form_error = 'Введённые пароли не совпадают.';
            } else {
                try {
                    $hash = password_hash($pw, PASSWORD_BCRYPT);
                    $upd = $connection->prepare(
                        'UPDATE users SET password = :p, password_reset_token_hash = NULL, password_reset_expires = NULL WHERE id = :id'
                    );
                    $upd->execute(['p' => $hash, 'id' => $user['id']]);
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    msll_audit_write(
                        $connection,
                        $user['login'],
                        'Восстановление пароля. Смена пароля по временному токену. ' . $dt,
                        'Пароль обновлён по ссылке из письма <' . $ua . '>'
                    );
                    header('Location: login.php?reset=ok');
                    exit;
                } catch (PDOException $e) {
                    $form_error = 'Не удалось сохранить пароль. Попробуйте позже.';
                }
            }
        }
    } elseif ($token_raw === '') {
        $fatal_invalid = true;
    } elseif (!$user) {
        $fatal_invalid = true;
    }

    $show_form = !$fatal_invalid && $user !== null;
?>
<html>
    <head>
      <link href="./css/styles.css?v=<?=$ASSET_VER?>" rel="stylesheet">
      <link href="./css/jost.css?v=<?=$ASSET_VER?>" rel="stylesheet">
      <title>Личный кабинет: Новый пароль</title>
      <link rel="icon" type="image/png" sizes="any" href="<?= htmlspecialchars(msll_site_root_href('pictures/logo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(msll_site_root_href('pictures/logo.png'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(msll_favicon_href(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    </head>
    <body>
      <form method="post" action="" name="reset-form" autocomplete="off">
        <div class="form_login_<?php echo $dt; ?>">
          <img class="login_logo_<?php echo $dt; ?>" src="./pictures/logo.png?v=<?=$ASSET_VER?>" alt="Лаборатория права Майи Саблиной">

          <?php if ($fatal_invalid) { ?>
            <?php if ($form_error !== '') { ?>
              <p class="<?php echo $dt === 'mobile' ? 'error_mobile' : 'error'; ?>"><?php echo htmlspecialchars($form_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
            <?php } else { ?>
              <p class="<?php echo $dt === 'mobile' ? 'error_mobile' : 'error'; ?>">Ссылка недействительна или срок её действия истёк.</p>
            <?php } ?>
            <p class="login_aux_links_<?php echo $dt; ?>"><a href="forgot_password.php">Запросить восстановление снова</a></p>
            <p class="login_aux_links_<?php echo $dt; ?>"><a href="login.php">Вернуться ко входу</a></p>
          <?php } elseif ($show_form) { ?>

            <?php if ($form_error !== '') { ?>
              <p class="<?php echo $dt === 'mobile' ? 'error_mobile' : 'error'; ?>"><?php echo htmlspecialchars($form_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
            <?php } ?>

            <input type="hidden" name="t" value="<?php echo htmlspecialchars($token_raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" />

            <div>
              <label class="form_login_label_<?php echo $dt; ?>">Новый пароль</label>
              <input class="msll_filter_<?php echo $dt; ?>" type="password" name="new_password" required minlength="8" autocomplete="new-password" />
            </div>
            <br/>
            <div>
              <label class="form_login_label_<?php echo $dt; ?>">Повтор пароля</label>
              <input class="msll_filter_<?php echo $dt; ?>" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password" />
            </div>
            <button type="submit" class="msll_button_<?php echo $dt; ?>" name="btn_reset" value="1">Сохранить пароль</button>
            <p class="login_aux_links_<?php echo $dt; ?>"><a href="login.php">Вернуться ко входу</a></p>

          <?php } ?>
        </div>
      </form>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const form = document.querySelector('form[name="reset-form"]');
          if (!form) return;
          form.addEventListener('submit', function () {
            const a = form.querySelector('input[name="new_password"]');
            const b = form.querySelector('input[name="new_password_confirm"]');
            if (a) a.value = a.value.trim();
            if (b) b.value = b.value.trim();
          });
        });
      </script>
    </body>
</html>

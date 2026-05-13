-- Одноразовые поля для восстановления пароля (см. forgot_password.php / reset_password.php)
ALTER TABLE `users`
  ADD COLUMN `password_reset_token_hash` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL AFTER `password`,
  ADD COLUMN `password_reset_expires` datetime DEFAULT NULL AFTER `password_reset_token_hash`;

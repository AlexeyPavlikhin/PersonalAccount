<?php

/**
 * Проверка реквизитов контрагента для раздела «Генерация документов» (требование п. 13).
 * Алгоритм контрольного числа расчётного и корреспондентского счёта — по правилам ЦБ РФ.
 */

function msll_digits_only($value): string
{
    return preg_replace('/\D+/', '', (string) $value);
}

function msll_is_valid_inn($value): bool
{
    return preg_match('/^\d{10,12}$/', msll_digits_only($value)) === 1;
}

function msll_is_valid_bik($value): bool
{
    return preg_match('/^\d{9}$/', msll_digits_only($value)) === 1;
}

/** Пустой e-mail допустим — поле необязательное в шаблоне. */
function msll_is_valid_email_optional($value): bool
{
    $email = trim((string) $value);
    if ($email === '') {
        return true;
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Контрольное число: 23 цифры (seed + 20-значный счёт), веса 7, 1, 3 по циклу.
 *
 * @param string $seed    3 цифры: для р/с — последние цифры БИК, для к/с — «0» + 5–6-я цифры БИК
 */
function msll_validate_account_checksum(string $seed, string $account): bool
{
    if (!preg_match('/^\d{3}$/', $seed) || !preg_match('/^\d{20}$/', $account)) {
        return false;
    }

    $control = $seed . $account;
    if (strlen($control) !== 23) {
        return false;
    }

    $coefficients = array(7, 1, 3);
    $sum = 0;
    for ($i = 0; $i < 23; $i++) {
        $sum += ((int) $control[$i]) * $coefficients[$i % 3];
    }

    return ($sum % 10) === 0;
}

function msll_is_valid_checking_account($account, $bik): bool
{
    $digits_account = msll_digits_only($account);
    $digits_bik = msll_digits_only($bik);

    if (!preg_match('/^\d{20}$/', $digits_account) || !preg_match('/^\d{9}$/', $digits_bik)) {
        return false;
    }

    return msll_validate_account_checksum(substr($digits_bik, -3), $digits_account);
}

function msll_is_valid_corr_account($account, $bik): bool
{
    $digits_account = msll_digits_only($account);
    $digits_bik = msll_digits_only($bik);

    if (!preg_match('/^\d{20}$/', $digits_account) || !preg_match('/^\d{9}$/', $digits_bik)) {
        return false;
    }

    return msll_validate_account_checksum('0' . substr($digits_bik, 4, 2), $digits_account);
}

<?php

/**
 * Преобразования текста для генерации договоров (требования п. 14–19):
 * разбор даты, склонение ФИО/должности, инициалы, форма «именуемый/именуемая».
 */

function msll_document_today_iso(): string
{
    return (new DateTimeImmutable('today'))->format('Y-m-d');
}

function msll_document_parse_contract_date(string $value): ?DateTimeImmutable
{
    $normalized = trim($value);
    // пустая дата в enrich трактуется как «сегодня»
    if ($normalized === '') {
        return new DateTimeImmutable('today');
    }

    $formats = array('Y-m-d', 'd.m.Y', 'd/m/Y');
    foreach ($formats as $format) {
        $parsed = DateTimeImmutable::createFromFormat($format, $normalized);
        if ($parsed instanceof DateTimeImmutable) {
            $errors = DateTimeImmutable::getLastErrors();
            if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                return $parsed;
            }
        }
    }

    $timestamp = strtotime($normalized);
    if ($timestamp !== false) {
        return (new DateTimeImmutable())->setTimestamp($timestamp);
    }

    return null;
}

function msll_document_russian_month_name(int $month, bool $genitive = true): string
{
    $months_nominative = array(
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь',
    );

    $months_genitive = array(
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    );

    if ($month < 1 || $month > 12) {
        return '';
    }

    return $genitive
        ? $months_genitive[$month]
        : $months_nominative[$month];
}

/** Разбивает дату договора на части для плейсхолдеров DOCX (день, месяц числом/прописью, год YY/YYYY). */
function msll_document_split_date_parts(string $date_value): array
{
    $parsed = msll_document_parse_contract_date($date_value);
    if (!$parsed instanceof DateTimeImmutable) {
        $parsed = new DateTimeImmutable('today');
    }

    $day = (int) $parsed->format('j');
    $month = (int) $parsed->format('n');
    $year = (int) $parsed->format('Y');

    return array(
        'contract_date' => $parsed->format('Y-m-d'),
        'contract_day' => (string) $day,
        'contract_month_num' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
        'contract_month_words' => msll_document_russian_month_name($month, true),
        'contract_year_yyyy' => (string) $year,
        'contract_year_yy' => substr((string) $year, -2),
    );
}

function msll_document_parse_fio_parts(string $fio): array
{
    $parts = preg_split('/\s+/u', trim($fio), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts)) {
        $parts = array();
    }

    $surname = $parts[0] ?? '';
    $name = $parts[1] ?? '';
    $patronymic = '';
    if (count($parts) > 2) {
        $patronymic = implode(' ', array_slice($parts, 2));
    }

    return array(
        'surname' => $surname,
        'name' => $name,
        'patronymic' => $patronymic,
    );
}

/** Запасной способ определить пол, если DaData clean/name недоступен. */
function msll_document_detect_gender_from_patronymic(string $patronymic): string
{
    $value = mb_strtolower(trim($patronymic));
    if ($value === '') {
        return 'UNKNOWN';
    }

    if (preg_match('/(овна|евна|ична|инична)$/u', $value)) {
        return 'FEMALE';
    }

    if (preg_match('/(ович|евич|ич)$/u', $value)) {
        return 'MALE';
    }

    return 'UNKNOWN';
}

function msll_document_normalize_gender_code(string $gender): string
{
    $value = mb_strtoupper(trim($gender));
    if ($value === 'M' || $value === 'MALE' || $value === 'М') {
        return 'MALE';
    }
    if ($value === 'F' || $value === 'FEMALE' || $value === 'Ж') {
        return 'FEMALE';
    }

    return 'UNKNOWN';
}

/** «именуемый/именуемая» только для физлица; для юрлица — нейтральное «именуемое». */
function msll_document_party_named_form(string $gender, bool $is_individual): string
{
    if (!$is_individual) {
        return 'именуемое';
    }

    $gender = msll_document_normalize_gender_code($gender);
    if ($gender === 'FEMALE') {
        return 'именуемая';
    }

    return 'именуемый';
}

function msll_document_build_initials(string $name, string $patronymic): string
{
    $initials = array();
    foreach (array($name, $patronymic) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $initials[] = mb_strtoupper(mb_substr($part, 0, 1)) . '.';
    }

    return implode('', $initials);
}

function msll_document_build_signer_short(string $surname, string $name, string $patronymic): string
{
    $surname = trim($surname);
    $initials = msll_document_build_initials($name, $patronymic);
    if ($surname === '') {
        return trim($name . ' ' . $patronymic);
    }
    if ($initials === '') {
        return $surname;
    }

    return $surname . ' ' . $initials;
}

function msll_document_decline_name_genitive(string $name, string $gender): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }

    $lower = mb_strtolower($name);
    $gender = msll_document_normalize_gender_code($gender);

    if ($gender === 'FEMALE') {
        $rules = array(
            '/ия$/u' => 'ии',
            '/ья$/u' => 'ьи',
            '/а$/u' => 'ы',
            '/я$/u' => 'и',
        );
    } else {
        $rules = array(
            '/ий$/u' => 'ия',
            '/ей$/u' => 'ея',
            '/й$/u' => 'я',
            '/ь$/u' => 'я',
            '/а$/u' => 'ы',
        );
    }

    foreach ($rules as $pattern => $replacement) {
        if (preg_match($pattern, $lower)) {
            $stem = preg_replace($pattern, '', $lower);
            return mb_convert_case($stem . $replacement, MB_CASE_TITLE, 'UTF-8');
        }
    }

    return $name;
}

function msll_document_decline_patronymic_genitive(string $patronymic, string $gender): string
{
    $patronymic = trim($patronymic);
    if ($patronymic === '') {
        return '';
    }

    $lower = mb_strtolower($patronymic);
    $gender = msll_document_normalize_gender_code($gender);

    if ($gender === 'FEMALE') {
        if (preg_match('/на$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -1) . 'ны', MB_CASE_TITLE, 'UTF-8');
        }
    } else {
        if (preg_match('/ич$/u', $lower)) {
            return mb_convert_case($lower . 'а', MB_CASE_TITLE, 'UTF-8');
        }
    }

    return $patronymic;
}

function msll_document_decline_surname_genitive(string $surname, string $gender): string
{
    $surname = trim($surname);
    if ($surname === '') {
        return '';
    }

    $lower = mb_strtolower($surname);
    $gender = msll_document_normalize_gender_code($gender);

    if ($gender === 'FEMALE') {
        if (preg_match('/ова$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -1) . 'ой', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/ева$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -1) . 'ой', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/ая$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -2) . 'ой', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/яя$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -2) . 'ей', MB_CASE_TITLE, 'UTF-8');
        }
    } else {
        if (preg_match('/ов$/u', $lower)) {
            return mb_convert_case($lower . 'а', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/ев$/u', $lower)) {
            return mb_convert_case($lower . 'а', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/ин$/u', $lower)) {
            return mb_convert_case($lower . 'а', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/ый$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -2) . 'ого', MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/ий$/u', $lower)) {
            return mb_convert_case(mb_substr($lower, 0, -2) . 'ого', MB_CASE_TITLE, 'UTF-8');
        }
    }

    return $surname;
}

/**
 * Локальное склонение ФИО в родительный падеж без DaData.
 * Используется, если не задан DADATA_SECRET_KEY или clean/name вернул ошибку.
 */
function msll_document_decline_fio_genitive_fallback(string $fio, string $gender = 'UNKNOWN'): array
{
    $parts = msll_document_parse_fio_parts($fio);
    if ($gender === 'UNKNOWN') {
        $gender = msll_document_detect_gender_from_patronymic($parts['patronymic']);
    }
    if ($gender === 'UNKNOWN') {
        // без отчества выбираем мужской род — типичный случай для руководителей юрлиц
        $gender = 'MALE';
    }

    $surname = msll_document_decline_surname_genitive($parts['surname'], $gender);
    $name = msll_document_decline_name_genitive($parts['name'], $gender);
    $patronymic = msll_document_decline_patronymic_genitive($parts['patronymic'], $gender);

    $genitive_parts = array_filter(array($surname, $name, $patronymic), static function ($value) {
        return trim((string) $value) !== '';
    });

    return array(
        'gender' => $gender,
        'surname' => $parts['surname'],
        'name' => $parts['name'],
        'patronymic' => $parts['patronymic'],
        'result' => trim(implode(' ', $genitive_parts)),
        'result_genitive' => trim(implode(' ', $genitive_parts)),
        'initials' => msll_document_build_initials($parts['name'], $parts['patronymic']),
        'short' => msll_document_build_signer_short($parts['surname'], $parts['name'], $parts['patronymic']),
    );
}

/**
 * Должность из DaData часто в верхнем регистре («ГЕНЕРАЛЬНЫЙ ДИРЕКТОР»).
 * В тексте договора нужен родительный падеж в нижнем регистре («генерального директора»).
 */
function msll_document_decline_position_genitive(string $position): string
{
    $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $position)));
    if ($text === '') {
        return '';
    }

    $replacements = array(
        'генеральный директор' => 'генерального директора',
        'исполнительный директор' => 'исполнительного директора',
        'заместитель генерального директора' => 'заместителя генерального директора',
        'председатель правления' => 'председателя правления',
        'главный бухгалтер' => 'главного бухгалтера',
        'директор' => 'директора',
        'председатель' => 'председателя',
        'бухгалтер' => 'бухгалтера',
        'управляющий' => 'управляющего',
        'руководитель' => 'руководителя',
    );

    // сначала более длинные фразы, чтобы «генеральный директор» не превратился только в «директора»
    uksort($replacements, static function ($left, $right) {
        return mb_strlen($right) <=> mb_strlen($left);
    });

    foreach ($replacements as $source => $target) {
        if ($text === $source) {
            return $target;
        }
        if (mb_strpos($text, $source) !== false) {
            return str_replace($source, $target, $text);
        }
    }

    return $text;
}

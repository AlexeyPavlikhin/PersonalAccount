<?php

/**
 * Загрузка DOCX-шаблона по URL и подготовка ответа для клиента.
 * Заполнение плейсхолдеров — inc/docx_form_filler.php.
 */

/** Скачивает шаблон по URL из document_templates.template_url (обычно статика на том же хосте). */
function msll_document_fetch_binary(string $url): string
{
    $normalized_url = trim($url);
    if ($normalized_url === '') {
        throw new RuntimeException('Template URL is empty');
    }

    if (!preg_match('#^https?://#i', $normalized_url)) {
        throw new RuntimeException('Only http/https template URLs are supported');
    }

    $raw_response = '';
    $http_code = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($normalized_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));

        $raw_response = curl_exec($ch);
        if ($raw_response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Template download failed: ' . $error);
        }

        $http_code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'timeout' => 30,
                'ignore_errors' => true,
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        ));

        $raw_response = @file_get_contents($normalized_url, false, $context);
        if ($raw_response === false) {
            throw new RuntimeException('Template download failed via file_get_contents');
        }

        $http_code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header_line) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string) $header_line, $matches)) {
                    $http_code = (int) $matches[1];
                    break;
                }
            }
        }
    }

    if ($http_code < 200 || $http_code >= 300) {
        throw new RuntimeException('Template server returned HTTP ' . $http_code);
    }

    if (!is_string($raw_response) || $raw_response === '') {
        throw new RuntimeException('Template download returned empty body');
    }

    return $raw_response;
}

/**
 * JSON для generate_document.php: готовый DOCX в base64.
 */
function msll_document_prepare_generation_payload(array $template, string $binary_docx, string $download_filename): array
{
    return array(
        'status' => 'ok',
        'template_id' => isset($template['template_id']) ? (int) $template['template_id'] : 0,
        'template_code' => trim((string) ($template['template_code'] ?? '')),
        'download_filename' => $download_filename,
        'document_docx_base64' => base64_encode($binary_docx),
        'content_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    );
}

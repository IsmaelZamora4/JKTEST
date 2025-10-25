<?php
// Auto-prepend file: start output buffering and replace or insert <title>
// This file is intended to be auto-prepended for every request.

// Avoid running twice
if (!defined('PREPEND_TITLE_STARTED')) {
    define('PREPEND_TITLE_STARTED', true);

    // Quick server-agnostic redirect: if the request URL contains a `.php` file
    // and it's a GET request, redirect to the same URL without the extension.
    // This is done before output buffering so it works on servers that ignore
    // .htaccess (e.g., nginx). We exclude API/izipay paths and non-GET methods.
    if (php_sapi_name() !== 'cli') {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (strtoupper($method) === 'GET' && preg_match('/(^|\/)([^\/\?]+)\.php($|\?)/i', $requestUri)) {
            // Exclude specific directories that should keep .php (api, izipay, etc.)
            if (!preg_match('#^/api/#i', $requestUri) && !preg_match('#^/izipay/#i', $requestUri)) {
                $new = preg_replace('#\.php(?=$|[?])#i', '', $requestUri);

                if ($new !== $requestUri) {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
                    if ($host) {
                        header('Location: ' . $scheme . '://' . $host . $new, true, 301);
                        // Stop execution immediately to ensure the redirect is returned.
                        exit;
                    }
                }
            }
        }
    }

    // Start output buffering
    ob_start();

    register_shutdown_function(function () {
        $html = '';
        // Get and clean buffer
        try {
            $html = ob_get_clean() ?: '';
        } catch (Throwable $e) {
            // nothing
        }

        if (!is_string($html) || $html === '') {
            // nothing to do
            echo $html;
            return;
        }

        // Only operate on probable HTML responses
        if (stripos($html, '<html') === false && stripos($html, '<head') === false && stripos($html, '<title') === false) {
            echo $html;
            return;
        }

        $newTitle = 'JK Grupo Textil';

        // If there is a <title> tag, replace its contents
        if (preg_match('/<title\b[^>]*>.*?<\/title>/is', $html)) {
            $html = preg_replace('/<title\b[^>]*>.*?<\/title>/is', '<title>' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>', $html, 1);
        } else {
            // No title present: insert before </head> if possible
            if (stripos($html, '</head') !== false) {
                $html = preg_replace('/<\/head>/i', '<title>' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</title>\n</head>", $html, 1);
            } else {
                // Fallback: prepend title at start
                $html = '<title>' . htmlspecialchars($newTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>' . PHP_EOL . $html;
            }
        }

        echo $html;
    });
}

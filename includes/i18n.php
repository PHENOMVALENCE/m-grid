<?php

declare(strict_types=1);

/**
 * UI language (Swahili-first). Uses $_SESSION['preferred_language'] ('sw'|'en').
 * Guest default: Swahili. Logged-in users sync from DB on login.
 */

/** @var array<string, array<string, string>>|null */
$mgrid_i18n_php_cache = null;

function mgrid_normalize_ui_lang(?string $lang): string
{
    return ($lang === 'en' || $lang === 'sw') ? $lang : 'sw';
}

function mgrid_ui_lang(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return 'sw';
    }

    return mgrid_normalize_ui_lang((string) ($_SESSION['preferred_language'] ?? 'sw'));
}

/**
 * Ensure session has a valid UI language (default Swahili).
 */
function mgrid_i18n_bootstrap(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $cur = $_SESSION['preferred_language'] ?? null;
    if (!is_string($cur) || !in_array($cur, ['en', 'sw'], true)) {
        $_SESSION['preferred_language'] = 'sw';
    }
}

/**
 * @return array<string, string>
 */
function mgrid_i18n_php_strings(string $lang): array
{
    global $mgrid_i18n_php_cache;
    $lang = mgrid_normalize_ui_lang($lang);
    if ($mgrid_i18n_php_cache === null) {
        $en = require __DIR__ . '/../lang/php_en.php';
        $sw = require __DIR__ . '/../lang/php_sw.php';
        if (!is_array($en)) {
            $en = [];
        }
        if (!is_array($sw)) {
            $sw = [];
        }
        $mgrid_i18n_php_cache = ['en' => $en, 'sw' => $sw];
    }

    return $mgrid_i18n_php_cache[$lang] ?? $mgrid_i18n_php_cache['sw'];
}

/**
 * Translate PHP-visible strings (errors, flashes, labels not covered by data-i18n).
 *
 * @param array<string, string> $replace Placeholders :key in string
 */
function __(string $key, array $replace = []): string
{
    $lang = mgrid_ui_lang();
    $table = mgrid_i18n_php_strings($lang);
    $s = $table[$key] ?? '';
    if ($s === '') {
        $alt = mgrid_i18n_php_strings($lang === 'sw' ? 'en' : 'sw');
        $s = $alt[$key] ?? '';
    }
    if ($s === '') {
        $s = $key;
    }
    foreach ($replace as $k => $v) {
        $s = str_replace(':' . $k, (string) $v, $s);
    }

    return $s;
}

/** Browser title: translated page name + brand (e.g. "Nyumbani yangu — M GRID"). */
function mgrid_title(string $titleKey, array $replace = []): string
{
    return __($titleKey, $replace) . ' — ' . __('site.brand');
}

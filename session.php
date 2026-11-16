<?php
declare(strict_types=1);

/**
 * Sessão persistente por até 30 dias, inclusive após fechar/reabrir o navegador.
 * A sessão continua sendo invalidada pelo botão "Sair".
 */
$lifetime = 60 * 60 * 24 * 30;
ini_set('session.gc_maxlifetime', (string)$lifetime);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

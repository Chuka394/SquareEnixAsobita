<?php

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

if (!function_exists('bd')) {
    if (!isset($GLOBALS['_bd'])) {
        $host = 'localhost';
        $nbd = 'SquareEnix';
        $usu = 'postgres';
        $pass = 'Jesus0309$'; 
        $prt = '5432';
        try {
            $GLOBALS['_bd'] = new PDO(
                "pgsql:host=$host;port=$prt;dbname=$nbd;options='--client_encoding=UTF8'",
                $usu, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die('Error de conexión: ' . $e->getMessage());
        }
    }
    function bd(): PDO { return $GLOBALS['_bd']; }
}

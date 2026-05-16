<?php
require_once __DIR__ . '/apis.php';

// Paypal
function paypalObtenerToken() {
    $ch = curl_init(PAYPAL_BASE_URL . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['access_token'] ?? null;
}

function paypalCrearOrden($total, $moneda = 'MXN') {
    $token = paypalObtenerToken();
    $ch = curl_init(PAYPAL_BASE_URL . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => ['currency_code' => $moneda, 'value' => number_format($total, 2, '.', '')]
            ]]
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res;
}

function paypalCapturarOrden($orderId) {
    $token = paypalObtenerToken();
    $ch = curl_init(PAYPAL_BASE_URL . "/v2/checkout/orders/$orderId/capture");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res;
}

// Resend — Correo de verificación

function enviarCodigoVerificacion($correo, $codigo) {
    $html = "
    <div style='font-family:sans-serif;max-width:500px;margin:auto;background:#0d1b2a;color:#fff;padding:40px;border-radius:12px;'>
        <h1 style='color:#00bcd4;'>Square Enix Store</h1>
        <p style='color:#aaa;'>Código de verificación de cuenta</p>
        <div style='background:#1a2a3a;border-radius:8px;padding:24px;text-align:center;margin:24px 0;'>
            <span style='font-size:36px;font-weight:bold;letter-spacing:12px;color:#00bcd4;'>$codigo</span>
        </div>
        <p style='color:#aaa;font-size:13px;'>Expira en 15 minutos.</p>
    </div>";

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'from' => RESEND_FROM,
            'to' => $correo,
            'subject' => 'Código de verificación - Square Enix Store',
            'html' => $html,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . RESEND_API_KEY],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function generarCodigo() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Subida de manera local de los archivos

function subirArchivoLocal($archivoTmp, $nombreOriginal, $carpeta = 'comunidad') {
    $rutaBase = RUTA_SUBIDAS . $carpeta . '/';
    if (!is_dir($rutaBase)) mkdir($rutaBase, 0775, true);

    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    $nombre = uniqid('img_', true) . '.' . $ext;
    $destino = $rutaBase . $nombre;

    if (!move_uploaded_file($archivoTmp, $destino)) return null;
    return URL_SUBIDAS . $carpeta . '/' . $nombre;
}

// Funciones de seguridad
function limpiar($valor): string {
    return htmlspecialchars(trim((string)$valor), ENT_QUOTES, 'UTF-8');
}

function entero($valor, int $min = 0): int {
    $n = filter_var($valor, FILTER_VALIDATE_INT);
    return ($n !== false && $n >= $min) ? $n : 0;
}

function tokenCSRF(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function validarCSRF(?string $token): bool {
    return !empty($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
}

function esImagenValida(array $archivo): bool {
    if (!is_uploaded_file($archivo['tmp_name'])) return false;
    if ($archivo['error'] !== UPLOAD_ERR_OK) return false;
    if ($archivo['size'] > 8 * 1024 * 1024) return false; 
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false) return false;
    $mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($info['mime'], $mimePermitidos, true);
}

<?php
// Subida de imagenes local por que no la armamos
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';

header('Content-Type: application/json');
$base = '/squareenix';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autenticado']); exit;
}
$uid = $_SESSION['usuario']['idusuario'];
$tipo = $_POST['tipo'] ?? '';
$archivo = $_FILES['archivo'] ?? null;

if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No se recibió archivo o hubo un error.']); exit;
}

// Validar el tipo de imagen
$tiposMime = ['image/jpeg','image/png','image/gif','image/webp'];
$mimeReal = mime_content_type($archivo['tmp_name']);
if (!in_array($mimeReal, $tiposMime)) {
    echo json_encode(['error' => 'Solo se permiten imágenes JPG, PNG, GIF o WebP.']); exit;
}

if ($tipo === 'avatar') {
 // Eliminar avatar anterior si ya se coloca uno nuevo
    if (!empty($_SESSION['usuario']['imagenurl'])) {
        $urlAnterior = $_SESSION['usuario']['imagenurl'];
        if (str_contains($urlAnterior, '/recursos/subidas/')) {
            $rutaAnterior = __DIR__ . '/..' . str_replace($base, '', $urlAnterior);
            if (file_exists($rutaAnterior)) unlink($rutaAnterior);
        }
    }
    $url = subirArchivoLocal($archivo['tmp_name'], $archivo['name'], 'avatares');
    if (!$url) { echo json_encode(['error' => 'Error al guardar el archivo.']); exit; }

 // Guardar en la base de dateishon y la sesion
    $pdo->prepare("UPDATE Usuario SET ImagenUrl=? WHERE IdUsuario=?")->execute([$url, $uid]);
    $_SESSION['usuario']['imagenurl'] = $url;
    echo json_encode(['success' => true, 'url' => $url]);

} elseif ($tipo === 'comunidad') {
    $url = subirArchivoLocal($archivo['tmp_name'], $archivo['name'], 'comunidad');
    if (!$url) { echo json_encode(['error' => 'Error al guardar el archivo.']); exit; }
    echo json_encode(['success' => true, 'url' => $url]);

} elseif ($tipo === 'juego') {
    if (!in_array($_SESSION['usuario']['rol'], ['admin','soporte'])) {
        echo json_encode(['error' => 'Sin permisos.']); exit;
    }
    $url = subirArchivoLocal($archivo['tmp_name'], $archivo['name'], 'juegos');
    if (!$url) { echo json_encode(['error' => 'Error al guardar el archivo.']); exit; }

 // Registrar la imagen en la base de datos
    $idJuego = entero($_POST['id_juego'] ?? 0, 1);
    if ($idJuego) {
        $pdo->prepare("INSERT INTO Imagenes (IdVideoJuego, UrlImagen) VALUES (?,?)")->execute([$idJuego, $url]);
    }
    echo json_encode(['success' => true, 'url' => $url]);

} else {
    echo json_encode(['error' => 'Tipo de subida no reconocido.']);
}

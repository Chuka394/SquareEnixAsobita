<?php
// para publicar en publicidad ademas pedimos imagen obligatoria
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';

$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { header("Location: $base/autenticacion/login.php"); exit; }

$uid = (int)$_SESSION['usuario']['idusuario'];
$idJuego = entero($_POST['id_juego'] ?? 0, 1);
$com = limpiar($_POST['comentario'] ?? '');

if (!$idJuego) {
    $_SESSION['msg_comunidad'] = 'Juego inválido.';
    header("Location: $base/biblioteca.php"); exit;
}

// aqui mero la pedimos
if (empty($_FILES['imagen']['tmp_name']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['msg_comunidad'] = 'Debes subir una imagen para compartir con la comunidad.';
    header("Location: $base/biblioteca.php?juego=$idJuego"); exit;
}

// Validar que sea imagen real luego nos meten virus
if (!esImagenValida($_FILES['imagen'])) {
    $_SESSION['msg_comunidad'] = 'El archivo no es una imagen válida (solo JPG, PNG, GIF, WebP — máx 8MB).';
    header("Location: $base/biblioteca.php?juego=$idJuego"); exit;
}

// Verificar que el usuario tenga el juego
$s = $pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");
$s->execute([$uid, $idJuego]);
if (!$s->fetch()) {
    $_SESSION['msg_comunidad'] = 'Solo puedes publicar de juegos que tengas en tu biblioteca.';
    header("Location: $base/biblioteca.php?juego=$idJuego"); exit;
}

// Subir imagen
$url = subirArchivoLocal($_FILES['imagen']['tmp_name'], $_FILES['imagen']['name'], 'comunidad');
if (!$url) {
    $_SESSION['msg_comunidad'] = 'Error al guardar la imagen en el servidor.';
    header("Location: $base/biblioteca.php?juego=$idJuego"); exit;
}

$pdo->prepare("INSERT INTO ComunidadVideoJuegos (Comentario, Imagen, IdUsuario, IdVideoJuego) VALUES (?,?,?,?)")
    ->execute([$com ?: null, $url, $uid, $idJuego]);

$_SESSION['msg_comunidad'] = '¡Publicación añadida a la comunidad!';
header("Location: $base/biblioteca.php?juego=$idJuego");
exit;

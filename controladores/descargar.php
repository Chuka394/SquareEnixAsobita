<?php
// Descargar el instalador de un juego que ya fue comprado
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { header("Location: $base/autenticacion/login.php"); exit; }
$uid = $_SESSION['usuario']['idusuario'];
$idJuego = entero($_GET['id'] ?? 0, 1);
if (!$idJuego) { header("Location: $base/biblioteca.php"); exit; }

// Verificar que el usuario tiene el juego
$s = $pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");
$s->execute([$uid,$idJuego]);
if (!$s->fetch()) { header("Location: $base/biblioteca.php"); exit; }

// Obtener datos del juego
$s = $pdo->prepare("SELECT Titulo, InstaladorUrl FROM VideoJuegos WHERE IdVideoJuego=?");
$s->execute([$idJuego]); $juego = $s->fetch();
if (!$juego) { header("Location: $base/biblioteca.php"); exit; }

$nombreArchivo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $juego['titulo']) . '_Setup.exe';

// Si tiene URL de instalador real ya que se redirija directamente
if (!empty($juego['instaladorurl'])) {
    header("Location: " . $juego['instaladorurl']); exit;
}

$rutaLocal = __DIR__ . '/../recursos/subidas/instaladores/' . $idJuego . '.exe';
if (file_exists($rutaLocal)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . filesize($rutaLocal));
    header('Cache-Control: no-cache');
    readfile($rutaLocal);
    exit;
}

// Aqui pusieramos el instalador real pero pues nose puede por terminos legales, luego nos demandan
$contenido = "Este archivo es un instalador de demostración para:\r\n" .
             $juego['titulo'] . "\r\n\r\n" .
             "En un entorno real, aquí se encontraría el instalador del juego.\r\n" .
             "Sube el .exe real a: recursos/subidas/instaladores/" . $idJuego . ".exe\r\n" .
             "O establece InstaladorUrl en la tabla VideoJuegos.\r\n";

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . strlen($contenido));
header('Cache-Control: no-cache');
echo $contenido;
exit;

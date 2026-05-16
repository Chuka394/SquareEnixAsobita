<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';

$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { header("Location: $base/autenticacion/login.php"); exit; }

$uid = (int)$_SESSION['usuario']['idusuario'];
$idJ = entero($_POST['id_juego'] ?? 0, 1);
if (!$idJ) { header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? "$base/index.php")); exit; }

$s = $pdo->prepare("SELECT 1 FROM Favorito WHERE IdUsuario=? AND IdVideoJuego=?");
$s->execute([$uid, $idJ]);

if ($s->fetch()) {
    $pdo->prepare("DELETE FROM Favorito WHERE IdUsuario=? AND IdVideoJuego=?")->execute([$uid, $idJ]);
} else {
    $pdo->prepare("INSERT INTO Favorito (IdVideoJuego, IdUsuario) VALUES (?,?)")->execute([$idJ, $uid]);
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? "$base/index.php")); exit;

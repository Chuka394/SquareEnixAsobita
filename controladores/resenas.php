<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
$base='/squareenix';
if(!isset($_SESSION['usuario'])){header("Location: $base/autenticacion/login.php");exit;}
$uid=$_SESSION['usuario']['idusuario'];
$idJ = entero($_POST['id_juego'] ?? 0, 1);
$titulo = limpiar($_POST['titulo'] ?? '');
$com = limpiar($_POST['comentario'] ?? '');
$cal = entero($_POST['calificacion'] ?? 0);
if(!$idJ||!$com||$cal<1||$cal>5){$_SESSION['msg_resena']='Completa comentario y calificación.';header("Location: $base/juego.php?id=$idJ");exit;}
$s=$pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");$s->execute([$uid,$idJ]);
if(!$s->fetch()){$_SESSION['msg_resena']='Solo puedes reseñar juegos comprados.';header("Location: $base/juego.php?id=$idJ");exit;}
$s=$pdo->prepare("SELECT 1 FROM Reseñas WHERE IdUsuario=? AND IdVideoJuego=?");$s->execute([$uid,$idJ]);
if($s->fetch()){$_SESSION['msg_resena']='Ya tienes una reseña para este juego.';header("Location: $base/juego.php?id=$idJ");exit;}
$pdo->prepare("INSERT INTO Reseñas (Titulo,Comentario,Calificacion,IdUsuario,IdVideoJuego) VALUES (?,?,?,?,?)")->execute([$titulo?:null,$com,$cal,$uid,$idJ]);
$_SESSION['msg_resena']='¡Reseña publicada!';header("Location: $base/juego.php?id=$idJ");exit;

<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
header('Content-Type: application/json');
if(!isset($_SESSION['usuario'])){echo json_encode(['error'=>'No autenticado']);exit;}
$uid=$_SESSION['usuario']['idusuario'];
$accion=$_POST['accion']??$_GET['accion']??'';
if($accion==='marcar_todas'){$pdo->prepare("UPDATE Notificaciones SET EsVista=TRUE WHERE IdUsuario=?")->execute([$uid]);echo json_encode(['success'=>true]);}
else{echo json_encode(['error'=>'Acción inválida']);}

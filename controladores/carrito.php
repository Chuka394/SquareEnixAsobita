<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
$base = '/squareenix';
$esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
function resp($d,$ajax){ if($ajax){header('Content-Type: application/json');echo json_encode($d);}else{header('Location: '.($_SERVER['HTTP_REFERER']??'/squareenix/index.php'));}exit;}
if(!isset($_SESSION['usuario'])) resp(['error'=>'No autenticado'],$esAjax);
$uid=$_SESSION['usuario']['idusuario'];
$accion=$_POST['accion']??'';
$idJ = entero($_POST["id_juego"] ?? 0, 1);
$s=$pdo->prepare("SELECT IdCarrito FROM Carrito WHERE IdUsuario=?");$s->execute([$uid]);$car=$s->fetch();
if(!$car){$pdo->prepare("INSERT INTO Carrito (IdUsuario) VALUES (?)")->execute([$uid]);$s->execute([$uid]);$car=$s->fetch();}
$cid=$car['idcarrito'];
if($accion==='agregar'){
    $s=$pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");$s->execute([$uid,$idJ]);
    if($s->fetch()) resp(['error'=>'Ya en tu biblioteca'],$esAjax);
    $s=$pdo->prepare("SELECT 1 FROM DetalleCarrito WHERE IdCarrito=? AND IdVideoJuego=?");$s->execute([$cid,$idJ]);
    if(!$s->fetch()) $pdo->prepare("INSERT INTO DetalleCarrito (Cantidad,IdCarrito,IdVideoJuego) VALUES (1,?,?)")->execute([$cid,$idJ]);
    $t=$pdo->prepare("SELECT COUNT(*) FROM DetalleCarrito WHERE IdCarrito=?");$t->execute([$cid]);
    resp(['success'=>true,'total'=>$t->fetchColumn()],$esAjax);
}
if($accion==='quitar'){
    $pdo->prepare("DELETE FROM DetalleCarrito WHERE IdCarrito=? AND IdVideoJuego=?")->execute([$cid,$idJ]);
    $t=$pdo->prepare("SELECT COUNT(*) FROM DetalleCarrito WHERE IdCarrito=?");$t->execute([$cid]);
    resp(['success'=>true,'total'=>$t->fetchColumn()],$esAjax);
}
if($accion==='obtener'){
    header('Content-Type: application/json');
    $s=$pdo->prepare("SELECT v.IdVideoJuego,v.Titulo,v.Precio,(SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego LIMIT 1) AS Imagen FROM DetalleCarrito dc JOIN VideoJuegos v ON dc.IdVideoJuego=v.IdVideoJuego WHERE dc.IdCarrito=?");$s->execute([$cid]);
    $articulos=$s->fetchAll();echo json_encode(['items'=>$articulos,'total'=>array_sum(array_column($articulos,'precio'))]);exit;
}
resp(['error'=>'Acción inválida'],$esAjax);

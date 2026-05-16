<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
header('Content-Type: application/json');
$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { echo json_encode(['error'=>'No autenticado']); exit; }
$uid = $_SESSION['usuario']['idusuario'];
$accion = $_POST['accion'] ?? '';
$idJuego = entero($_POST['id_juego'] ?? 0, 1);

if ($accion === 'crear_orden') {
    $s = $pdo->prepare("SELECT Titulo,Precio FROM VideoJuegos WHERE IdVideoJuego=? AND Estado!='descontinuado'");
    $s->execute([$idJuego]); $juego = $s->fetch();
    if (!$juego) { echo json_encode(['error'=>'Juego no encontrado']); exit; }
    $r = paypalCrearOrden((float)$juego['precio'], 'MXN');
    if (!isset($r['id'])) { echo json_encode(['error'=>'Error PayPal: '.($r['message']??json_encode($r))]); exit; }
    $s = $pdo->prepare("INSERT INTO Orden (EstadoPago,IdUsuario) VALUES ('pendiente',?) RETURNING IdOrden");
    $s->execute([$uid]); $oid = $s->fetchColumn();
    $pdo->prepare("INSERT INTO DetalleOrden (Cantidad,PrecioUnitario,IdOrden,IdVideoJuego) VALUES (1,?,?,?)")->execute([$juego['precio'],$oid,$idJuego]);
    $pdo->prepare("INSERT INTO Transacciones (Estado,IdOrden) VALUES ('pendiente',?)")->execute([$oid]);
    $_SESSION['pp_oid']=$oid; $_SESSION['pp_jid']=$idJuego;
    echo json_encode(['id'=>$r['id']]); exit;
}

if ($accion === 'capturar_orden') {
    $ppId = $_POST['paypal_order_id'] ?? '';
    $r = paypalCapturarOrden($ppId);
    if (($r['status']??'') !== 'COMPLETED') {
        echo json_encode(['error'=>'Pago no completado: '.($r['message']??$r['details'][0]['description']??json_encode($r))]); exit;
    }
    $oid=$_SESSION['pp_oid']??0; $idJuego=$_SESSION['pp_jid']??0;
    if (!$oid||!$idJuego) { echo json_encode(['error'=>'Sesión perdida']); exit; }
    $pdo->prepare("UPDATE Orden SET EstadoPago='completado' WHERE IdOrden=?")->execute([$oid]);
    $pdo->prepare("UPDATE Transacciones SET Estado='completado' WHERE IdOrden=?")->execute([$oid]);
    $s=$pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");$s->execute([$uid,$idJuego]);
    if(!$s->fetch()) $pdo->prepare("INSERT INTO Biblioteca (IdVideoJuego,IdUsuario) VALUES (?,?)")->execute([$idJuego,$uid]);
    $s=$pdo->prepare("SELECT IdCarrito FROM Carrito WHERE IdUsuario=?");$s->execute([$uid]);$car=$s->fetch();
    if($car) $pdo->prepare("DELETE FROM DetalleCarrito WHERE IdCarrito=? AND IdVideoJuego=?")->execute([$car['idcarrito'],$idJuego]);
    $s=$pdo->prepare("SELECT Titulo FROM VideoJuegos WHERE IdVideoJuego=?");$s->execute([$idJuego]);$t=$s->fetchColumn();
    $pdo->prepare("INSERT INTO Notificaciones (Titulo,Mensaje,IdUsuario) VALUES (?,?,?)")->execute(['Compra exitosa',"\"$t\" ya está en tu biblioteca.",$uid]);
    unset($_SESSION['pp_oid'],$_SESSION['pp_jid']);
    echo json_encode(['success'=>true]); exit;
}

// Carrito completo con opcion de pagar si el cliente quiere wu
if ($accion === 'crear_orden_carrito') {
 // Obtener carrito del usuario
    $s = $pdo->prepare("SELECT IdCarrito FROM Carrito WHERE IdUsuario=?");
    $s->execute([$uid]); $car = $s->fetch();
    if (!$car) { echo json_encode(['error' => 'Carrito vacío']); exit; }
    $cid = $car['idcarrito'];

    $s = $pdo->prepare("
        SELECT v.IdVideoJuego, v.Titulo, v.Precio
        FROM DetalleCarrito dc
        JOIN VideoJuegos v ON dc.IdVideoJuego = v.IdVideoJuego
        WHERE dc.IdCarrito = ? AND v.Estado != 'descontinuado'
    ");
    $s->execute([$cid]);
    $articulos = $s->fetchAll();

    if (empty($articulos)) { echo json_encode(['error' => 'Carrito vacío']); exit; }

    $total = array_sum(array_column($articulos, 'precio'));

 // Crear orden en el paypal
    $r = paypalCrearOrden((float)$total, 'MXN');
    if (!isset($r['id'])) {
        echo json_encode(['error' => 'Error PayPal: ' . ($r['message'] ?? json_encode($r))]); exit;
    }

 // Crear orden en la base de datos
    $s = $pdo->prepare("INSERT INTO Orden (EstadoPago, IdUsuario) VALUES ('pendiente', ?) RETURNING IdOrden");
    $s->execute([$uid]); $oid = $s->fetchColumn();

 // Insertar detalles rapiditsimo
    foreach ($articulos as $articulo) {
        $pdo->prepare("INSERT INTO DetalleOrden (Cantidad, PrecioUnitario, IdOrden, IdVideoJuego) VALUES (1, ?, ?, ?)")
            ->execute([$articulo['precio'], $oid, $articulo['idvideojuego']]);
    }
    $pdo->prepare("INSERT INTO Transacciones (Estado, IdOrden) VALUES ('pendiente', ?)")->execute([$oid]);

    $_SESSION['pp_oid_car'] = $oid;
    $_SESSION['pp_items']   = array_column($articulos, 'idvideojuego');
    echo json_encode(['id' => $r['id']]); exit;
}

if ($accion === 'capturar_orden_carrito') {
    $ppId = $_POST['paypal_order_id'] ?? '';
    $r = paypalCapturarOrden($ppId);
    if (($r['status'] ?? '') !== 'COMPLETED') {
        echo json_encode(['error' => 'Pago no completado: ' . ($r['message'] ?? json_encode($r))]); exit;
    }

    $oid = $_SESSION['pp_oid_car'] ?? 0;
    $articulos = $_SESSION['pp_items'] ?? [];

    if (!$oid || empty($articulos)) { echo json_encode(['error' => 'Sesión perdida']); exit; }

    $pdo->prepare("UPDATE Orden SET EstadoPago='completado' WHERE IdOrden=?")->execute([$oid]);
    $pdo->prepare("UPDATE Transacciones SET Estado='completado' WHERE IdOrden=?")->execute([$oid]);

 // Agregar a biblioteca y limpiar el carrito para mas despues
    $titulos = [];
    foreach ($articulos as $idJ) {
        $s = $pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");
        $s->execute([$uid, $idJ]);
        if (!$s->fetch()) {
            $pdo->prepare("INSERT INTO Biblioteca (IdVideoJuego, IdUsuario) VALUES (?,?)")->execute([$idJ, $uid]);
        }
        $s = $pdo->prepare("SELECT Titulo FROM VideoJuegos WHERE IdVideoJuego=?");
        $s->execute([$idJ]); $titulos[] = $s->fetchColumn();
    }

    $s = $pdo->prepare("SELECT IdCarrito FROM Carrito WHERE IdUsuario=?");
    $s->execute([$uid]); $car = $s->fetch();
    if ($car) {
        foreach ($articulos as $idJ) {
            $pdo->prepare("DELETE FROM DetalleCarrito WHERE IdCarrito=? AND IdVideoJuego=?")
                ->execute([$car['idcarrito'], $idJ]);
        }
    }

 // notificacion de la compra realizada 
    $listaJuegos = implode(', ', $titulos);
    $pdo->prepare("INSERT INTO Notificaciones (Titulo, Mensaje, IdUsuario) VALUES (?,?,?)")
        ->execute(['Compra exitosa', count($titulos) . " juego(s) agregado(s) a tu biblioteca: $listaJuegos", $uid]);

    unset($_SESSION['pp_oid_car'], $_SESSION['pp_items']);
    echo json_encode(['success' => true, 'cantidad' => count($titulos)]); exit;
}

echo json_encode(['error'=>'Acción inválida']);

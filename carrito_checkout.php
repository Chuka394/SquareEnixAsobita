<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
require_once 'configuracion/apis.php';

$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { header("Location: $base/autenticacion/login.php"); exit; }

$uid = (int)$_SESSION['usuario']['idusuario'];

// Obtener carrito del usuario
$s = $pdo->prepare("SELECT IdCarrito FROM Carrito WHERE IdUsuario=?");
$s->execute([$uid]); $car = $s->fetch();
if (!$car) { header("Location: $base/index.php"); exit; }

$s = $pdo->prepare("
    SELECT v.IdVideoJuego, v.Titulo, v.Precio,
           (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Logo
    FROM DetalleCarrito dc
    JOIN VideoJuegos v ON dc.IdVideoJuego = v.IdVideoJuego
    WHERE dc.IdCarrito = ? AND v.Estado != 'descontinuado'
");
$s->execute([$car['idcarrito']]);
$articulos = $s->fetchAll();

if (empty($articulos)) { header("Location: $base/index.php"); exit; }
$total = array_sum(array_column($articulos, 'precio'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pagar carrito – Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/parciales/navbar.php'; ?>

<div class="container" style="max-width:640px;padding:32px 16px;">
    <h1 style="font-family:'Rajdhani',sans-serif;margin-bottom:20px;">Confirmar compra del carrito</h1>

    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px;">
        <?php foreach ($articulos as $articulo): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-dim);">
            <img src="<?= htmlspecialchars($articulo['logo'] ?? $base.'/recursos/imagenes/placeholder.svg') ?>"
                 style="width:60px;height:38px;object-fit:cover;border-radius:4px;">
            <div style="flex:1;">
                <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($articulo['titulo']) ?></div>
            </div>
            <div style="font-weight:600;color:var(--accent);">Mex$ <?= number_format($articulo['precio'],2) ?></div>
        </div>
        <?php endforeach; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:14px;border-top:2px solid var(--accent);">
            <span style="font-size:16px;font-weight:600;">Total (<?= count($articulos) ?> juego<?= count($articulos)!=1?'s':'' ?>)</span>
            <span style="font-size:22px;font-weight:700;color:var(--accent);">Mex$ <?= number_format($total,2) ?></span>
        </div>
    </div>

    <p style="font-size:12px;color:var(--text-dim);text-align:center;margin-bottom:18px;">Pago seguro con PayPal Sandbox</p>
    <div id="paypal-button-container"></div>

    <a href="<?= $base ?>/index.php" class="btn-secondary btn-block" style="margin-top:14px;justify-content:center;">← Cancelar y volver</a>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=MXN"></script>
<script>
paypal.Buttons({
    createOrder: function() {
        const fd = new FormData();
        fd.append('accion', 'crear_orden_carrito');
        return fetch('<?= $base ?>/controladores/paypal.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(d => { if (d.error) throw new Error(d.error); return d.id; });
    },
    onApprove: function(data) {
        const fd = new FormData();
        fd.append('accion', 'capturar_orden_carrito');
        fd.append('paypal_order_id', data.orderID);
        return fetch('<?= $base ?>/controladores/paypal.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    sessionStorage.setItem('compra_completa', JSON.stringify({
                        tipo: 'carrito',
                        cantidad: res.cantidad
                    }));
                    window.location.href = '<?= $base ?>/biblioteca.php?compra=ok&tipo=carrito&cant=' + res.cantidad;
                } else {
                    alert('Error: ' + (res.error || 'Intenta de nuevo'));
                }
            });
    },
    onError: function(err) { alert('Error con PayPal. Intenta de nuevo.'); }
}).render('#paypal-button-container');
</script>
</body>
</html>

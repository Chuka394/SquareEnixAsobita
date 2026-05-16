<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
require_once 'configuracion/apis.php';
$base = '/squareenix';
$id = (int)($_GET['id']??0);
if (!$id) { header("Location: $base/index.php"); exit; }

$s = $pdo->prepare("SELECT v.*,STRING_AGG(DISTINCT c.NombreCategoria,'|') AS categorias FROM VideoJuegos v LEFT JOIN CategoriaVideoJuego cv ON cv.IdVideoJuego=v.IdVideoJuego LEFT JOIN Categoria c ON c.IdCategoria=cv.IdCategoria WHERE v.IdVideoJuego=? GROUP BY v.IdVideoJuego");
$s->execute([$id]); $juego = $s->fetch();
if (!$juego) { header("Location: $base/index.php"); exit; }

$s = $pdo->prepare("SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=? ORDER BY IdImagen"); $s->execute([$id]); $imagenes = $s->fetchAll(PDO::FETCH_COLUMN);
$s = $pdo->prepare("SELECT r.*,u.NombreUsuario,u.ImagenUrl FROM Reseñas r JOIN Usuario u ON r.IdUsuario=u.IdUsuario WHERE r.IdVideoJuego=? ORDER BY r.FechaCreacion DESC"); $s->execute([$id]); $resenas = $s->fetchAll();
$s = $pdo->prepare("SELECT ROUND(AVG(Calificacion),1) AS prom,COUNT(*) AS total FROM Reseñas WHERE IdVideoJuego=?"); $s->execute([$id]); $calif = $s->fetch();

$usuario = $_SESSION['usuario']??null; $ya_compro=false; $es_fav=false;
if ($usuario) {
    $s=$pdo->prepare("SELECT 1 FROM Biblioteca WHERE IdUsuario=? AND IdVideoJuego=?");$s->execute([$usuario['idusuario'],$id]);$ya_compro=(bool)$s->fetch();
    $s=$pdo->prepare("SELECT 1 FROM Favorito WHERE IdUsuario=? AND IdVideoJuego=?");$s->execute([$usuario['idusuario'],$id]);$es_fav=(bool)$s->fetch();
}
$msg_resena = $_SESSION['msg_resena'] ?? ''; unset($_SESSION['msg_resena']);
$imgPortada = $imagenes[0] ?? $base.'/recursos/imagenes/placeholder.svg';
$imgCapturas = array_slice($imagenes, 1); 
$imgGalInicio = $imgCapturas[0] ?? $imgPortada; 
$etiquetas = array_filter(explode('|', $juego['categorias'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($juego['titulo']) ?> – Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/navbar.php'; ?>

<div class="juego-topbar">
    <h1><?= htmlspecialchars($juego['titulo']) ?></h1>
    <?php if ($usuario): ?>
    <form method="POST" action="<?= $base ?>/controladores/favoritos.php" style="margin:0">
        <input type="hidden" name="id_juego" value="<?= $id ?>">
        <button type="submit" class="btn-fav <?= $es_fav?'active':'' ?>">
            Añadir a favoritos
            <svg viewBox="0 0 24 24" fill="<?= $es_fav?'var(--warning)':'none' ?>" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="juego-body">
    <div class="juego-gallery">
        <img id="main-img" class="juego-main-img"
             src="<?= htmlspecialchars($imgGalInicio) ?>"
             alt="<?= htmlspecialchars($juego['titulo']) ?>">
        <?php if (!empty($imgCapturas)): ?>
        <div class="juego-thumbs">
            <?php foreach ($imgCapturas as $k => $captura): ?>
            <img class="<?= $k === 0 ? 'active' : '' ?>"
                 src="<?= htmlspecialchars($captura) ?>" alt=""
                 onclick="cambiarImagen(this)">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="juego-info">
        <div style="text-align:center;margin-bottom:14px;">
            <img src="<?= htmlspecialchars($imgPortada) ?>"
                 alt="<?= htmlspecialchars($juego['titulo']) ?>"
                 style="max-width:100%;max-height:110px;object-fit:contain;border-radius:6px;">
        </div>

        <p class="juego-desc"><?= nl2br(htmlspecialchars($juego['descripcion'] ?? 'Sin descripción disponible.')) ?></p>

        <div class="juego-meta">
            <?php if ($juego['fechalanzamiento']): ?>
            <div class="juego-meta-row">
                <span class="lbl">Fecha de lanzamiento:</span>
                <span class="val"><?= date('d M Y',strtotime($juego['fechalanzamiento'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($juego['desarrollador']): ?>
            <div class="juego-meta-row">
                <span class="lbl">Desarrollador:</span>
                <span class="val"><?= htmlspecialchars($juego['desarrollador']) ?></span>
            </div>
            <div class="juego-meta-row">
                <span class="lbl">Editor:</span>
                <span class="val">Square Enix</span>
            </div>
            <?php endif; ?>
            <?php if ($juego['peso']): ?>
            <div class="juego-meta-row">
                <span class="lbl">Espacio necesario:</span>
                <span class="val"><?= htmlspecialchars($juego['peso']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Categorías -->
        <?php if (!empty($etiquetas)): ?>
        <div class="juego-categorias" style="margin-top:12px;">
            <div class="lbl" style="margin-bottom:6px;">Categorias</div>
            <div class="game-tags">
                <?php foreach ($etiquetas as $t): ?>
                <a href="<?= $base ?>/categorias.php?cat=<?= urlencode(trim($t)) ?>" class="tag">
                    <?= htmlspecialchars(trim($t)) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($calif['total']>0): ?>
        <div style="margin-top:16px;display:flex;align-items:center;gap:8px;">
            <span style="color:var(--warning);font-size:16px;">
                <?= str_repeat('★',(int)round($calif['prom'])) ?><?= str_repeat('☆',5-(int)round($calif['prom'])) ?>
            </span>
            <span style="font-size:12px;color:var(--text-muted);">
                <?= $calif['prom'] ?> de 5 &nbsp;·&nbsp; <?= $calif['total'] ?> reseña<?= $calif['total']!=1?'s':'' ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="juego-compra-bar">
    <?php if ($ya_compro): ?>
        <span class="juego-compra-title">En tu biblioteca</span>
        <a href="<?= $base ?>/controladores/descargar.php?id=<?= $id ?>" class="btn-primary">⬇ Instalar</a>
    <?php else: ?>
        <span class="juego-compra-title">Comprar <?= htmlspecialchars($juego['titulo']) ?></span>
        <span class="juego-compra-price">Mex$ <?= number_format($juego['precio'],2) ?></span>
        <?php if ($usuario): ?>
        <button class="btn-primary" onclick="abrirModalCompra()">Comprar ahora</button>
        <form method="POST" action="<?= $base ?>/controladores/carrito.php" style="margin:0">
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="id_juego" value="<?= $id ?>">
            <button type="submit" class="btn-secondary">Guardar en el carrito</button>
        </form>
        <?php else: ?>
        <a href="<?= $base ?>/autenticacion/login.php" class="btn-primary">Inicia sesion para comprar</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="comentario-section">
    <?php if ($msg_resena): ?><div class="form-alert success"><?= htmlspecialchars($msg_resena) ?></div><?php endif; ?>
    <?php if ($usuario): ?>
    <div style="margin-bottom:32px;">
        <h3>Añadir un comentario</h3>
        <?php if (!$ya_compro): ?>
        <div class="form-alert info" style="margin-top:12px;">Solo puedes comentar juegos que hayas comprado.</div>
        <?php else: ?>
        <form method="POST" action="<?= $base ?>/controladores/resena.php" style="margin-top:14px;">
            <input type="hidden" name="id_juego" value="<?= $id ?>">
            <div style="display:grid;gap:10px;">
                <input type="text" name="titulo" class="form-control" placeholder="Título (opcional)">
                <div class="stars-input-row">
                    <?php for ($i=1;$i<=5;$i++): ?><button type="button" class="star-btn" data-val="<?= $i ?>">★</button><?php endfor; ?>
                    <input type="hidden" name="calificacion" id="cal-input" value="0">
                </div>
                <textarea name="comentario" class="form-control" placeholder="Danos tu opinión...."></textarea>
                <button type="submit" class="btn-primary" style="width:fit-content;">Publicar reseña</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($resenas)): ?>
    <h3 style="margin-bottom:16px;">Reseñas de la comunidad</h3>
    <?php foreach ($resenas as $r): ?>
    <div class="review-card">
        <div class="review-header">
            <img class="review-avatar" src="<?= htmlspecialchars($r['imagenurl']??$base.'/recursos/imagenes/avatar-defecto.svg') ?>" alt="">
            <div><div class="review-user"><?= htmlspecialchars($r['nombreusuario']) ?></div>
                <div class="review-stars"><?= str_repeat('★',$r['calificacion']) ?><?= str_repeat('☆',5-$r['calificacion']) ?></div></div>
            <div class="review-date"><?= date('d/m/Y',strtotime($r['fechacreacion'])) ?></div>
        </div>
        <?php if ($r['titulo']): ?><div class="review-title"><?= htmlspecialchars($r['titulo']) ?></div><?php endif; ?>
        <div class="review-body"><?= nl2br(htmlspecialchars($r['comentario']??'')) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($usuario && !$ya_compro): ?>
<div class="modal-overlay" id="modal-compra" onclick="if(event.target===this)cerrarModalCompra()">
    <div class="modal-box">
        <button type="button" class="modal-close-x" onclick="cerrarModalCompra()" title="Cerrar / cambiar método de pago">✕</button>
        <h2 class="modal-title">Confirmar compra</h2>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
            <img src="<?= htmlspecialchars($imgPortada) ?>" style="width:80px;height:50px;object-fit:contain;border-radius:6px;" alt="">
            <div><div style="font-weight:600;font-size:15px;"><?= htmlspecialchars($juego['titulo']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);">Square Enix</div></div>
        </div>
        <div class="modal-total">Total: <span>Mex$ <?= number_format($juego['precio'],2) ?></span></div>
        <p style="font-size:12px;color:var(--text-dim);text-align:center;margin-bottom:20px;">Pago con PayPal Sandbox</p>
        <div id="paypal-button-container"></div>
        <button class="btn-secondary btn-block" style="margin-top:10px;" onclick="cerrarModalCompra()">← Cambiar método de pago / Cancelar</button>
    </div>
</div>
<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=MXN"></script>
<script>
paypal.Buttons({
    createOrder: function() {
        const fd=new FormData(); fd.append('accion','crear_orden'); fd.append('id_juego','<?= $id ?>');
        return fetch('<?= $base ?>/controladores/paypal.php',{method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{if(d.error)throw new Error(d.error);return d.id;});
    },
    onApprove: function(data) {
        const fd=new FormData(); fd.append('accion','capturar_orden'); fd.append('paypal_order_id',data.orderID);
        return fetch('<?= $base ?>/controladores/paypal.php',{method:'POST',body:fd})
            .then(r=>r.json()).then(res=>{
                if(res.success) {
                    cerrarModalCompra();
                    mostrarModalExito();
                } else alert('Error: '+(res.error||'Intenta de nuevo'));
            });
    },
    onError: function(err){alert('Error con PayPal. Intenta de nuevo.');}
}).render('#paypal-button-container');
</script>
<?php endif; ?>

<?php if ($usuario && !$ya_compro): ?>
<div class="modal-overlay" id="modal-exito" onclick="if(event.target===this)cerrarModalExito()">
    <div class="modal-box" style="text-align:center;max-width:420px;">
        <button type="button" class="modal-close-x" onclick="cerrarModalExito()">✕</button>

        <!-- Icono de éxito -->
        <div style="margin:0 auto 16px;width:64px;height:64px;background:rgba(74,222,128,0.15);
                    border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;">
            ✅
        </div>

        <h2 style="font-family:'Rajdhani',sans-serif;font-size:24px;color:var(--success);margin-bottom:8px;">
            Compra exitosa
        </h2>

        <!-- Logo/imagen del juego -->
        <?php if (!empty($imgPortada) && $imgPortada !== $base.'/recursos/imagenes/placeholder.svg'): ?>
        <img src="<?= htmlspecialchars($imgPortada) ?>"
             alt="<?= htmlspecialchars($juego['titulo']) ?>"
             style="max-width:200px;max-height:120px;object-fit:contain;margin:14px auto;border-radius:6px;display:block;">
        <?php endif; ?>

        <p style="font-size:16px;color:var(--text-white);font-weight:600;margin-bottom:6px;">
            <?= htmlspecialchars($juego['titulo']) ?>
        </p>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
            El juego ya está disponible en tu biblioteca para descargar.
        </p>

        <div style="display:flex;gap:10px;justify-content:center;">
            <a href="<?= $base ?>/biblioteca.php" class="btn-primary">Ir a biblioteca</a>
            <button class="btn-secondary" onclick="cerrarModalExito()">Cerrar</button>
        </div>
    </div>
</div>
<script>
function mostrarModalExito() { document.getElementById('modal-exito').classList.add('show'); }
function cerrarModalExito() {
    document.getElementById('modal-exito').classList.remove('show');
    window.location.href = '<?= $base ?>/biblioteca.php?compra=ok';
}
</script>
<?php endif; ?>

<?php include 'vistas/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
<script>function cambiarImagen(miniatura) {
    document.getElementById('main-img').src = miniatura.src;
    document.querySelectorAll('.juego-thumbs img').forEach(t => t.classList.remove('active'));
    miniatura.classList.add('active');
}</script>
</body>
</html>

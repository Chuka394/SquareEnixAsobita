<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { header("Location: $base/autenticacion/login.php"); exit; }
$usuario = $_SESSION['usuario'];

$s = $pdo->prepare("SELECT v.IdVideoJuego,v.Titulo,v.Descripcion,v.Peso,v.InstaladorUrl,(SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen FROM Biblioteca b JOIN VideoJuegos v ON b.IdVideoJuego=v.IdVideoJuego WHERE b.IdUsuario=? ORDER BY b.FechaCompra DESC");
$s->execute([$usuario['idusuario']]); $juegos_bib = $s->fetchAll();

$juego_id = (int)($_GET['juego'] ?? ($juegos_bib[0]['idvideojuego'] ?? 0));
$juego_sel = null; $posts = [];

if ($juego_id) {
    $s = $pdo->prepare("SELECT v.*,STRING_AGG(DISTINCT c.NombreCategoria,'|') AS categorias FROM VideoJuegos v LEFT JOIN CategoriaVideoJuego cv ON cv.IdVideoJuego=v.IdVideoJuego LEFT JOIN Categoria c ON c.IdCategoria=cv.IdCategoria WHERE v.IdVideoJuego=? GROUP BY v.IdVideoJuego");
    $s->execute([$juego_id]); $juego_sel = $s->fetch();
    $s = $pdo->prepare("SELECT p.*,u.NombreUsuario,u.ImagenUrl FROM ComunidadVideoJuegos p JOIN Usuario u ON p.IdUsuario=u.IdUsuario WHERE p.IdVideoJuego=? ORDER BY p.FechaSubida DESC LIMIT 12");
    $s->execute([$juego_id]); $posts = $s->fetchAll();
}
$mensaje = $_SESSION['msg_comunidad']??''; unset($_SESSION['msg_comunidad']);
$img_hero = null;
if ($juego_id) { $s=$pdo->prepare("SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=? ORDER BY IdImagen LIMIT 1");$s->execute([$juego_id]);$img_hero=$s->fetchColumn(); }
$img_hero = $img_hero ?: $base.'/recursos/imagenes/placeholder.svg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Biblioteca – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/navbar.php'; ?>
<?php if (isset($_GET['compra'])): ?><div class="form-alert success" style="margin:12px 20px;">Compra exitosa tu juego ya esta disponible en tu biblioteca.</div><?php endif; ?>

<div class="biblioteca-wrap">
    <aside class="bib-sidebar">
        <div class="bib-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-dim);flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="inputBusqBib" placeholder="Buscar" oninput="filtrarBib(this.value)">
        </div>
        <div class="bib-game-list" id="lstJuegosBib">
            <?php if (empty($juegos_bib)): ?>
            <div style="padding:16px;font-size:12px;color:var(--text-dim);">No tienes juegos aun</div>
            <?php else: foreach ($juegos_bib as $j): ?>
            <a href="?juego=<?= $j['idvideojuego'] ?>" class="bib-game-item <?= $j['idvideojuego']==$juego_id?'active':'' ?>" data-titulo="<?= strtolower(htmlspecialchars($j['titulo'])) ?>">
                <img src="<?= htmlspecialchars($j['imagen']??$base.'/recursos/imagenes/placeholder.svg') ?>" alt="">
                <span><?= htmlspecialchars($j['titulo']) ?></span>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </aside>

    <div class="bib-main">
        <?php if ($juego_sel): ?>
        <div class="bib-hero" style="background:linear-gradient(135deg, var(--bg-card), var(--bg-secondary));">
            <img src="<?= htmlspecialchars($img_hero) ?>" alt=""
                 style="object-fit:contain;width:100%;height:100%;filter:none;opacity:0.95;background:linear-gradient(135deg, var(--bg-card), var(--bg-secondary));">
            <div class="bib-hero-overlay">
                <div class="bib-hero-title"><?= htmlspecialchars($juego_sel['titulo']) ?></div>
                <div class="bib-hero-actions">
                    <a href="<?= $base ?>/controladores/descargar.php?id=<?= $juego_id ?>" class="btn-instalar">⬇ INSTALAR</a>
                    <div class="bib-meta-links">
                        <?php if ($juego_sel['peso']): ?>
                        <div class="bib-meta-link"><span>Espacio necesario</span><strong><?= htmlspecialchars($juego_sel['peso']) ?></strong></div>
                        <?php endif; ?>
                        <a href="<?= $base ?>/juego.php?id=<?= $juego_id ?>" class="bib-meta-link"><span>Pagina de la tienda</span></a>
                        <a href="<?= $base ?>/soporte.php" class="bib-meta-link"><span>Soporte</span></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bib-comunidad">
            <?php if ($mensaje): ?><div class="form-alert success" style="margin-bottom:14px;"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
            <div class="bib-com-header">
                <div class="bib-com-title">Contenido de la comunidad</div>
                <button class="btn-compartir" onclick="toggleFormCompartir()">Compartir con la comunidad</button>
            </div>

            <div id="formCompartir" style="display:none;background:var(--bg-card);border:1px solid var(--border-dim);border-radius:8px;padding:20px;margin-bottom:20px;">
                <form method="POST" action="<?= $base ?>/controladores/comunidad.php" enctype="multipart/form-data">
                    <input type="hidden" name="id_juego" value="<?= $juego_id ?>">
                    <div class="formGrupo">
                        <label style="font-size:12px;color:var(--text-muted);">Comentario</label>
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Comparte tu experiencia..."></textarea>
                    </div>
                    <div class="formGrupo">
                        <label style="font-size:12px;color:var(--text-muted);">Imagen <span style="color:var(--danger);">*</span></label>
                        <input type="file" name="imagen" accept="image/*" class="form-control" required>
                        <small style="color:var(--text-dim);">Obligatorio: JPG, PNG, GIF o WebP</small>
                    </div>
                    <button type="submit" class="btn-primary">Publicar</button>
                    <button type="button" class="btn-secondary" onclick="toggleFormCompartir()" style="margin-left:8px;">Cancelar</button>
                </form>
            </div>

            <?php if (empty($posts)): ?>
            <div class="empty-msg" style="padding:24px 0;"><p>Se el primero en compartir.</p></div>
            <?php else: ?>
            <div class="comunidad-grid">
                <?php foreach ($posts as $p): ?>
                <div class="com-post">
                    <?php if ($p['imagen']): ?><img src="<?= htmlspecialchars($p['imagen']) ?>" alt=""><?php endif; ?>
                    <div class="com-post-body">
                        <div class="com-post-user">Compartido por <strong><?= htmlspecialchars($p['nombreusuario']) ?></strong></div>
                        <?php if ($p['comentario']): ?><div class="com-post-text"><?= nl2br(htmlspecialchars($p['comentario'])) ?></div><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div style="margin-top:16px;">
                <a href="<?= $base ?>/comunidad.php" style="font-size:13px;color:var(--accent);">Ver toda la comunidad →</a>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-msg" style="margin-top:60px;"><p>Selecciona un juego de tu biblioteca.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php include 'vistas/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
<script>
function filtrarBib(v){document.querySelectorAll('#lstJuegosBib .bib-game-item').forEach(i=>i.style.display=i.dataset.titulo.includes(v.toLowerCase())?'':'none');}
function toggleFormCompartir(){const f=document.getElementById('formCompartir');if(f)f.style.display=f.style.display==='none'?'block':'none';}
</script>
</body>
</html>

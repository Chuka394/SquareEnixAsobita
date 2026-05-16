<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';

$pagina_num = max(1, (int)($_GET['pagina']??1));
$porPagina = 12;
$offset = ($pagina_num - 1) * $porPagina;

// Total de publicaciones
$total_posts = $pdo->query("SELECT COUNT(*) FROM ComunidadVideoJuegos")->fetchColumn();
$total_pags = max(1, ceil($total_posts / $porPagina));

// Publicaciones con informacion del usuario y del juego
$s = $pdo->prepare("
    SELECT p.IdPublicacion, p.Comentario, p.Imagen, p.FechaSubida,
           u.NombreUsuario, u.ImagenUrl AS AvatarUsuario,
           v.IdVideoJuego, v.Titulo AS TituloJuego,
           (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS ImagenJuego
    FROM ComunidadVideoJuegos p
    JOIN Usuario u ON p.IdUsuario = u.IdUsuario
    JOIN VideoJuegos v ON p.IdVideoJuego = v.IdVideoJuego
    ORDER BY p.FechaSubida DESC
    LIMIT ? OFFSET ?
");
$s->execute([$porPagina, $offset]);
$posts = $s->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Comunidad – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <style>
    .com-page-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; }
    .com-card { background:var(--bg-card); border:1px solid var(--border-dim); border-radius:10px; overflow:hidden; transition:transform .2s,border-color .2s; }
    .com-card:hover { transform:translateY(-3px); border-color:var(--border); }
    .com-card-img { width:100%; height:180px; object-fit:cover; }
    .com-card-img-placeholder { width:100%; height:180px; background:var(--bg-secondary); display:flex; align-items:center; justify-content:center; color:var(--text-dim); font-size:13px; }
    .com-card-body { padding:14px; }
    .com-card-user { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .com-card-avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; }
    .com-card-meta { font-size:12px; }
    .com-card-meta strong { color:var(--text-white); display:block; }
    .com-card-meta span { color:var(--accent); font-size:11px; }
    .com-card-text { font-size:13px; color:var(--text-muted); line-height:1.6; }
    .com-juego-link { display:inline-block; margin-top:8px; font-size:11px; color:var(--accent); }
    .paginacion { display:flex; gap:6px; justify-content:center; margin-top:28px; }
    .pag-btn { padding:6px 14px; border:1px solid var(--border-dim); border-radius:6px; color:var(--text-muted); font-size:13px; transition:all .2s; }
    .pag-btn:hover, .pag-btn.active { border-color:var(--accent); color:var(--accent); background:rgba(0,188,212,.08); }
    </style>
</head>
<body>
<?php include 'vistas/parciales/navbar.php'; ?>

<div class="container" style="padding-top:28px;padding-bottom:40px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <h1 class="section-title" style="margin-bottom:0;">Comunidad</h1>
        <span style="font-size:13px;color:var(--text-muted);"><?= $total_posts ?> publicaciones</span>
    </div>

    <?php if (empty($posts)): ?>
    <div class="empty-msg">
        <p>Aun no hay publicaciones en la comunidad.</p>
        <p style="margin-top:8px;font-size:13px;">Ve a tu <a href="<?= $base ?>/biblioteca.php" style="color:var(--accent);">biblioteca</a> y comparte contenido de tus juegos.</p>
    </div>
    <?php else: ?>
    <div class="com-page-grid">
        <?php foreach ($posts as $p): ?>
        <div class="com-card">
            <?php if ($p['imagen']): ?>
                <img class="com-card-img" src="<?= htmlspecialchars($p['imagen']) ?>" alt="">
            <?php else: ?>
                <div class="com-card-img-placeholder">Sin imagen</div>
            <?php endif; ?>
            <div class="com-card-body">
                <div class="com-card-user">
                    <img class="com-card-avatar"
                         src="<?= htmlspecialchars($p['avatarusuario'] ?? $base.'/recursos/imagenes/avatar-defecto.svg') ?>" alt="">
                    <div class="com-card-meta">
                        <strong><?= htmlspecialchars($p['nombreusuario']) ?></strong>
                        <span><?= date('d/m/Y', strtotime($p['fechasubida'])) ?></span>
                    </div>
                </div>
                <?php if ($p['comentario']): ?>
                <p class="com-card-text"><?= nl2br(htmlspecialchars($p['comentario'])) ?></p>
                <?php endif; ?>
                <a href="<?= $base ?>/juego.php?id=<?= $p['idvideojuego'] ?>" class="com-juego-link">
                    🎮 <?= htmlspecialchars($p['titulojuego']) ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pags > 1): ?>
    <div class="paginacion">
        <?php if ($pagina_num > 1): ?>
        <a href="?pagina=<?= $pagina_num-1 ?>" class="pag-btn">← Anterior</a>
        <?php endif; ?>
        <?php for ($p=max(1,$pagina_num-2); $p<=min($total_pags,$pagina_num+2); $p++): ?>
        <a href="?pagina=<?= $p ?>" class="pag-btn <?= $p===$pagina_num?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($pagina_num < $total_pags): ?>
        <a href="?pagina=<?= $pagina_num+1 ?>" class="pag-btn">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'vistas/parciales/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
</body>
</html>

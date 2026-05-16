<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';
$q = trim($_GET['q'] ?? '');
$juegos = [];

if ($q) {
    $like = '%'.$q.'%';
    $s = $pdo->prepare("
        SELECT v.IdVideoJuego, v.Titulo, v.Precio, v.Desarrollador,
               (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
        FROM VideoJuegos v
        WHERE v.Estado != 'descontinuado' AND (LOWER(v.Titulo) LIKE LOWER(?) OR LOWER(v.Descripcion) LIKE LOWER(?) OR LOWER(v.Desarrollador) LIKE LOWER(?))
        GROUP BY v.IdVideoJuego, v.Titulo, v.Precio, v.Desarrollador
        ORDER BY v.Titulo LIMIT 60
    ");
    $s->execute([$like,$like,$like]); $juegos = $s->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/parciales/navbar.php'; ?>
<div class="container" style="padding-top:28px;padding-bottom:40px;">
    <?php if (!$q): ?>
        <div class="empty-msg"><p>Ingresa un termino de búsqueda en la barra superior.</p></div>
    <?php else: ?>
        <h1 class="section-title">
            Resultados para "<?= htmlspecialchars($q) ?>"
            <span style="font-size:14px;color:var(--text-muted);font-family:'Inter',sans-serif;font-weight:400;">— <?= count($juegos) ?> resultado(s)</span>
        </h1>
        <?php if (empty($juegos)): ?>
            <div class="empty-msg"><p>No encontramos juegos que coincidan.</p></div>
        <?php else: ?>
        <div class="games-grid">
            <?php foreach ($juegos as $j): ?>
            <a href="<?= $base ?>/juego.php?id=<?= $j['idvideojuego'] ?>" class="game-card">
                <img class="game-card-img" src="<?= htmlspecialchars($j['imagen'] ?? $base.'/recursos/imagenes/placeholder.svg') ?>" alt="">
                <div class="game-card-body">
                    <div class="game-card-title"><?= htmlspecialchars($j['titulo']) ?></div>
                    <div class="game-card-dev"><?= htmlspecialchars($j['desarrollador'] ?? '') ?></div>
                    <div class="game-card-price">Mex$ <?= number_format($j['precio'],2) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php include 'vistas/parciales/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
</body>
</html>

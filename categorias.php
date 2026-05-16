<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';
$lista_cats = $pdo->query("SELECT IdCategoria, NombreCategoria FROM Categoria ORDER BY NombreCategoria")->fetchAll();
$cat_actual = $_GET['cat'] ?? ($lista_cats[0]['nombrecategoria'] ?? '');

$juegos_cat = [];
if ($cat_actual) {
    $s = $pdo->prepare("
        SELECT v.IdVideoJuego, v.Titulo, v.Precio, v.Desarrollador,
               (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
        FROM VideoJuegos v
        JOIN CategoriaVideoJuego cv ON cv.IdVideoJuego = v.IdVideoJuego
        JOIN Categoria c            ON c.IdCategoria   = cv.IdCategoria
        WHERE c.NombreCategoria = ? AND v.Estado != 'descontinuado'
        ORDER BY v.Titulo
    ");
    $s->execute([$cat_actual]);
    $juegos_cat = $s->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/navbar.php'; ?>

<div class="container" style="padding-top:28px;padding-bottom:40px;">
    <h1 class="section-title">Categorias</h1>

    <div class="cat-pills">
        <?php foreach ($lista_cats as $cat_item): ?>
        <a href="?cat=<?= urlencode($cat_item['nombrecategoria']) ?>"
           class="cat-pill <?= $cat_item['nombrecategoria'] === $cat_actual ? 'active' : '' ?>">
            <?= htmlspecialchars($cat_item['nombrecategoria']) ?>
        </a>
        <?php endforeach; ?>
        <?php if (empty($lista_cats)): ?>
        <p style="color:var(--text-dim);font-size:13px;">No hay categorias registradas aun.</p>
        <?php endif; ?>
    </div>

    <?php if ($cat_actual): ?>
    <h2 class="section-title" style="font-size:16px;color:var(--accent);margin-top:24px;">
        <?= htmlspecialchars($cat_actual) ?>
        <span style="font-size:13px;color:var(--text-muted);font-family:'Inter',sans-serif;font-weight:400;">
            — <?= count($juegos_cat) ?> juego<?= count($juegos_cat) != 1 ? 's' : '' ?>
        </span>
    </h2>
// Para mostrar los juegos de la categorias que escojas
    <?php if (empty($juegos_cat)): ?>
        <div class="empty-msg"><p>No hay juegos en esta categoria todavía.</p></div>
    <?php else: ?>
    <div class="games-grid">
        <?php foreach ($juegos_cat as $jcat): ?>
        <a href="<?= $base ?>/juego.php?id=<?= $jcat['idvideojuego'] ?>" class="game-card">
            <img class="game-card-img"
                 src="<?= htmlspecialchars($jcat['imagen'] ?? $base.'/recursos/imagenes/placeholder.svg') ?>"
                 alt="<?= htmlspecialchars($jcat['titulo']) ?>">
            <div class="game-card-body">
                <div class="game-card-title"><?= htmlspecialchars($jcat['titulo']) ?></div>
                <div class="game-card-dev"><?= htmlspecialchars($jcat['desarrollador'] ?? '') ?></div>
                <div class="game-card-price">Mex$ <?= number_format($jcat['precio'], 2) ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'vistas/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
</body>
</html>

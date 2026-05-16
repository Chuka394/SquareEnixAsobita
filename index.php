<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';

$juegos_carrusel = $pdo->query("
    SELECT v.IdVideoJuego,v.Titulo,v.Descripcion,v.Precio,v.Desarrollador,
           STRING_AGG(DISTINCT c.NombreCategoria,'|') AS categorias
    FROM VideoJuegos v
    LEFT JOIN CategoriaVideoJuego cv ON cv.IdVideoJuego=v.IdVideoJuego
    LEFT JOIN Categoria c ON c.IdCategoria=cv.IdCategoria
    WHERE v.Estado!='descontinuado'
    GROUP BY v.IdVideoJuego ORDER BY v.IdVideoJuego DESC LIMIT 8
")->fetchAll();

$imgs = [];
foreach ($juegos_carrusel as $j) {
    $s = $pdo->prepare("SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=? ORDER BY IdImagen");
    $s->execute([$j['idvideojuego']]); $imgs[$j['idvideojuego']] = $s->fetchAll(PDO::FETCH_COLUMN);
}

$todos = $pdo->query("
    SELECT v.IdVideoJuego,v.Titulo,v.Precio,v.Desarrollador,
           (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
    FROM VideoJuegos v WHERE v.Estado!='descontinuado' ORDER BY v.IdVideoJuego DESC
")->fetchAll();

$cats_grid = $pdo->query("SELECT NombreCategoria FROM Categoria ORDER BY NombreCategoria LIMIT 9")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/navbar.php'; ?>

<?php if (!empty($juegos_carrusel)): ?>
<div class="hero-carousel" style="position:relative;">
    <div class="carousel-track" id="carousel-track">
        <?php foreach ($juegos_carrusel as $j):
            $ji = $imgs[$j['idvideojuego']] ?? [];
            $logo = $ji[0] ?? $base.'/recursos/imagenes/placeholder.svg';
            $capturas = array_slice($ji, 1);
            $imgGrande = $capturas[0] ?? $logo;
            $etiquetas = array_filter(explode('|', $j['categorias']??''));
        ?>
        <div class="carousel-slide">
            <a class="carousel-img-col" href="<?= $base ?>/juego.php?id=<?= $j['idvideojuego'] ?>">
                <img src="<?= htmlspecialchars($imgGrande) ?>" alt="<?= htmlspecialchars($j['titulo']) ?>">
            </a>
            <div class="carousel-info">
                <h2><?= htmlspecialchars($j['titulo']) ?></h2>
                <img class="game-cover" src="<?= htmlspecialchars($logo) ?>" alt="Logo">
                <p class="game-desc"><?= htmlspecialchars(substr($j['descripcion']??'',0,200)) ?><?= strlen($j['descripcion']??'')>200?'…':'' ?></p>
                <?php if (!empty($etiquetas)): ?>
                <div class="game-tags">
                    <?php foreach ($etiquetas as $t): ?>
                    <a href="<?= $base ?>/categorias.php?cat=<?= urlencode(trim($t)) ?>" class="tag"><?= htmlspecialchars(trim($t)) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="game-price">Mex$ <?= number_format($j['precio'],2) ?></div>
                <?php if (!empty($capturas)): ?>
                <div class="carousel-thumbs">
                    <?php foreach (array_slice($capturas, 0, 4) as $k => $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="<?= $k===0?'active':'' ?>"
                         onclick="cambiarImgSlide(this,'<?= htmlspecialchars($img) ?>')" alt="">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-nav prev" onclick="moverCarrusel(-1)">&#8249;</button>
    <button class="carousel-nav next" onclick="moverCarrusel(1)">&#8250;</button>
</div>
<?php endif; ?>

<div class="container">
    <?php if (!empty($cats_grid)): ?>
    <div class="section">
        <h2 class="section-title">Explorar por categoria</h2>
        <div class="categorias-grid">
            <?php foreach ($cats_grid as $cat): ?>
            <a href="<?= $base ?>/categorias.php?cat=<?= urlencode($cat) ?>" class="categoria-card">
                <img src="../SquareEnix/recursos/imagenes/Banner.webp" alt="">
                <div class="cat-label"><?= htmlspecialchars($cat) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <hr class="divider">
    <?php endif; ?>

    <div class="section">
        <h2 class="section-title">Todos los juegos</h2>
        <?php if (empty($todos)): ?>
        <div class="empty-msg"><p>No hay juegos disponibles aun</p></div>
        <?php else: ?>
        <div class="games-grid">
            <?php foreach ($todos as $j): ?>
            <a href="<?= $base ?>/juego.php?id=<?= $j['idvideojuego'] ?>" class="game-card">
                <img class="game-card-img" src="<?= htmlspecialchars($j['imagen']??$base.'/recursos/imagenes/placeholder.svg') ?>" alt="<?= htmlspecialchars($j['titulo']) ?>">
                <div class="game-card-body">
                    <div class="game-card-title"><?= htmlspecialchars($j['titulo']) ?></div>
                    <div class="game-card-dev"><?= htmlspecialchars($j['desarrollador']??'') ?></div>
                    <div class="game-card-price">Mex$ <?= number_format($j['precio'],2) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'vistas/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
<script>
function cambiarImgSlide(thumb, src) {
    thumb.closest('.carousel-slide').querySelector('.carousel-img-col img').src = src;
    thumb.closest('.carousel-thumbs').querySelectorAll('img').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}
</script>
</body>
</html>

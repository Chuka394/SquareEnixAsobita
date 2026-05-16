<?php
session_start();
require_once 'configuracion/bd.php';
$base = '/squareenix';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quienes somos – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/parciales/navbar.php'; ?>

<div class="container" style="max-width:780px;padding:32px 16px 60px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:32px;color:var(--accent);margin-bottom:24px;">Quiénes somos</h1>

    <div class="adminFormCard" style="margin-bottom:20px;">
        <h2 style="font-family:'Rajdhani',sans-serif;font-size:20px;margin-bottom:12px;color:var(--text-white);">🎮 Nuestra historia</h2>
        <p style="color:var(--text-muted);line-height:1.7;">
            Square Enix Store nació como un proyecto académico con el objetivo de crear una experiencia
            digital completa para los amantes de los videojuegos. Nuestra plataforma simula una tienda
            online donde puedes explorar, comprar y disfrutar de los mejores títulos de Square Enix.
        </p>
    </div>

    <div class="adminFormCard" style="margin-bottom:20px;">
        <h2 style="font-family:'Rajdhani',sans-serif;font-size:20px;margin-bottom:12px;color:var(--text-white);">🎯 Nuestra misión</h2>
        <p style="color:var(--text-muted);line-height:1.7;">
            Ofrecer una plataforma intuitiva y completa donde los jugadores puedan descubrir nuevos
            mundos, gestionar su biblioteca personal y compartir sus experiencias con una comunidad
            apasionada por los videojuegos.
        </p>
    </div>

    <div class="adminFormCard" style="margin-bottom:20px;">
        <h2 style="font-family:'Rajdhani',sans-serif;font-size:20px;margin-bottom:12px;color:var(--text-white);">✨ Qué ofrecemos</h2>
        <ul style="color:var(--text-muted);line-height:1.9;list-style:none;padding:0;">
            <li>Catálogo de videojuegos con detalles, capturas y reseñas</li>
            <li>Sistema de carrito y compras seguras con PayPal</li>
            <li>Biblioteca personal con tus juegos comprados</li>
            <li>Comunidad para compartir experiencias entre jugadores</li>
            <li>Sistema de favoritos y recomendaciones personalizadas</li>
            <li>Soporte técnico para cualquier duda o problema</li>
        </ul>
    </div>

    <div class="adminFormCard">
        <h2 style="font-family:'Rajdhani',sans-serif;font-size:20px;margin-bottom:12px;color:var(--text-white);">👥 El equipo</h2>
        <p style="color:var(--text-muted);line-height:1.7;">
            Este proyecto fue desarrollado como parte de un trabajo escolar enfocado en el desarrollo
            web full-stack, utilizando PHP, PostgreSQL, HTML, CSS y JavaScript. Cada función fue
            diseñada pensando en la experiencia del usuario.
        </p>
    </div>
</div>

<?php include 'vistas/parciales/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
</body>
</html>

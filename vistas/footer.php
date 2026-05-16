<?php $base = $base ?? '/squareenix'; ?>
<footer class="footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <div class="footer-logo">SQUARE ENIX</div>
            <p>Tu tienda oficial de videojuegos Square Enix.</p>
        </div>
        <div class="footer-col">
            <h4>Tienda</h4>
            <a href="<?= $base ?>/index.php">Inicio</a>
            <a href="<?= $base ?>/categorias.php">Categorias</a>
            <a href="<?= $base ?>/buscar.php">Buscar</a>
        </div>
        <div class="footer-col">
            <h4>Cuenta</h4>
            <a href="<?= $base ?>/biblioteca.php">Mi biblioteca</a>
            <a href="<?= $base ?>/perfil.php">Mi perfil</a>
            <a href="<?= $base ?>/comunidad.php">Comunidad</a>
        </div>
        <div class="footer-col">
            <h4>Ayuda</h4>
            <a href="<?= $base ?>/soporte.php">Soporte y contacto</a>
            <a href="<?= $base ?>/quienes_somos.php">Quiénes somos</a>
            <a href="<?= $base ?>/preguntasFrecuentes.php">Preguntas frecuentes</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> Square Enix Store</span>
        <span>Pagos con PayPal Sandbox</span>
    </div>
</footer>

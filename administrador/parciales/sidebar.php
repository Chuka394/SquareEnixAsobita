<?php
$base = '/squareenix';
$pagina = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="adminSidebar">
    <div class="adminBrand">
        <span style="color:var(--accent);font-weight:700;letter-spacing:2px;font-size:12px;">SQUARE ENIX</span>
        <small style="display:block;color:var(--text-dim);margin-top:2px;font-size:11px;">Panel Administrativo</small>
    </div>
    <nav class="adminNav">
        <a href="<?= $base ?>/administrador/index.php" class="adminNavLink <?= $pagina==='index' ?'active':'' ?>">Dashboard</a>
        <a href="<?= $base ?>/administrador/juegos.php" class="adminNavLink <?= $pagina==='juegos' ?'active':'' ?>">Videojuegos</a>
        <a href="<?= $base ?>/administrador/usuarios.php" class="adminNavLink <?= $pagina==='usuarios' ?'active':'' ?>">Usuarios</a>
        <a href="<?= $base ?>/administrador/tickets.php" class="adminNavLink <?= $pagina==='tickets' ?'active':'' ?>">Tickets Soporte</a>
        <a href="<?= $base ?>/administrador/reportes.php" class="adminNavLink <?= $pagina==='reportes' ?'active':'' ?>">Reporte</a>
        <?php if (($_SESSION['usuario']['rol']??'')==='admin'): ?>
        <a href="<?= $base ?>/administrador/crear_admin.php" class="adminNavLink <?= $pagina==='crear_admin'?'active':'' ?>">Crear Administrador</a>
        <?php endif; ?>
    </nav>
    <div class="adminSidebarFooter">
        <div class="adminUserInfo">
            <img class="adminAvatar"
                 src="<?= htmlspecialchars($_SESSION['usuario']['imagenurl']??$base.'/recursos/imagenes/Kirby.png') ?>" alt="">
            <div>
                <strong style="font-size:13px;color:var(--text-white);display:block;"><?= htmlspecialchars($_SESSION['usuario']['nombreusuario']??'') ?></strong>
                <small style="color:var(--accent);font-size:11px;"><?= ucfirst($_SESSION['usuario']['rol']??'') ?></small>
            </div>
        </div>
        <a href="<?= $base ?>/autenticacion/logout.php" class="btnSalirAdmin">Cerrar sesion</a>
    </div>
</aside>

<?php
if (!isset($_SESSION)) session_start();

// Conexion que este disponible siempre
require_once __DIR__ . '/../../configuracion/bd.php';
$pdo = bd();

$usuario = $_SESSION['usuario'] ?? null;
$paginaAct = basename($_SERVER['PHP_SELF'], '.php');
$base = '/squareenix';
$cntNotif = 0;
$cntCarrito = 0;
$lstFavoritos = [];
$lstNotificaciones = [];
$lstCarrito = [];
$lstRecomend = [];

if ($usuario) {
    $uid = $usuario['idusuario'];

    $s = $pdo->prepare("SELECT COUNT(*) FROM Notificaciones WHERE IdUsuario=? AND EsVista=FALSE");
    $s->execute([$uid]); $cntNotif = (int)$s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM DetalleCarrito dc JOIN Carrito c ON dc.IdCarrito=c.IdCarrito WHERE c.IdUsuario=?");
    $s->execute([$uid]); $cntCarrito = (int)$s->fetchColumn();

    $s = $pdo->prepare("SELECT * FROM Notificaciones WHERE IdUsuario=? ORDER BY IdNotificacion DESC LIMIT 5");
    $s->execute([$uid]); $lstNotificaciones = $s->fetchAll();

    $s = $pdo->prepare("
        SELECT v.IdVideoJuego, v.Titulo, v.Precio,
               (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
        FROM Favorito f JOIN VideoJuegos v ON f.IdVideoJuego=v.IdVideoJuego
        WHERE f.IdUsuario=? ORDER BY f.IdFavorito DESC LIMIT 5");
    $s->execute([$uid]); $lstFavoritos = $s->fetchAll();

    $s = $pdo->prepare("
        SELECT v.IdVideoJuego, v.Titulo, v.Precio,
               (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
        FROM DetalleCarrito dc
        JOIN Carrito c ON dc.IdCarrito=c.IdCarrito
        JOIN VideoJuegos v ON dc.IdVideoJuego=v.IdVideoJuego
        WHERE c.IdUsuario=? LIMIT 5");
    $s->execute([$uid]); $lstCarrito = $s->fetchAll();

    $semilla = (int)date('Ymd');
    $s = $pdo->prepare("
        SELECT v.IdVideoJuego, v.Titulo, v.Precio,
               (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
        FROM VideoJuegos v WHERE v.Estado != 'descontinuado'
        ORDER BY HASHTEXT(CAST(? AS TEXT)||CAST(v.IdVideoJuego AS TEXT)) LIMIT 3");
    $s->execute([$semilla]); $lstRecomend = $s->fetchAll();
}

// Categoria para la barrita
$lstCatsNav = $pdo->query("SELECT NombreCategoria FROM Categoria ORDER BY NombreCategoria")->fetchAll(PDO::FETCH_COLUMN);

$imgPlaceholder = $base . '/recursos/imagenes/placeholder.svg';
$imgAvatarDef = $base . '/recursos/imagenes/avatar-defecto.svg';
?>
//el navbar que esta primero
<nav class="navbar-top">
    <a href="<?= $base ?>/index.php" class="navbar-logo">SQUARE ENIX</a>

    <div class="navbar-sections">
        <a href="<?= $base ?>/index.php"      class="<?= $paginaAct==='index' ?'active':'' ?>">TIENDA</a>
        <a href="<?= $base ?>/biblioteca.php" class="<?= $paginaAct==='biblioteca'?'active':'' ?>">BIBLIOTECA</a>
        <a href="<?= $base ?>/comunidad.php"  class="<?= $paginaAct==='comunidad' ?'active':'' ?>">COMUNIDAD</a>
    </div>

    <div class="navbar-right">
        <?php if ($usuario): ?>
        <!-- Favoritos -->
        <div style="position:relative;">
            <div class="nav-icon-wrap" onclick="togglePanel('panelFavoritos')" title="Lista de favoritos">
                <span style="font-size:12px;color:var(--text-muted)">Lista de favoritos</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php if (!empty($lstFavoritos)): ?><span class="nav-badge" data-panel="panelFavoritos"></span><?php endif; ?>
            </div>
            <div class="panel-flotante" id="panelFavoritos">
                <div class="panel-header">Mis favoritos</div>
                <?php if (empty($lstFavoritos)): ?>
                    <div class="panel-empty">Sin favoritos aún</div>
                <?php else: foreach ($lstFavoritos as $fav): ?>
                <a class="panel-item" href="<?= $base ?>/juego.php?id=<?= $fav['idvideojuego'] ?>">
                    <img src="<?= htmlspecialchars($fav['imagen'] ?? $imgPlaceholder) ?>" alt="">
                    <div class="panel-item-info">
                        <div class="panel-item-title"><?= htmlspecialchars($fav['titulo']) ?></div>
                        <div class="panel-item-sub">Mex$ <?= number_format($fav['precio'],2) ?></div>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

//Notificaciones
        <div style="position:relative;">
            <div class="nav-icon-wrap" onclick="togglePanel('panelNotif')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?php if ($cntNotif > 0): ?><span class="nav-badge" data-panel="panelNotif"></span><?php endif; ?>
            </div>
            <div class="panel-flotante" id="panelNotif">
                <div class="panel-header">
                    Notificaciones
                    <a href="#" onclick="marcarTodasVistas(event)" style="font-size:11px;color:var(--text-dim)">Marcar leídas</a>
                </div>
                <?php if (empty($lstNotificaciones)): ?>
                    <div class="panel-empty">Sin notificaciones</div>
                <?php else: foreach ($lstNotificaciones as $notif): ?>
                <div class="panel-item" style="<?= !$notif['esvista'] ? 'background:rgba(0,188,212,0.06)' : '' ?>">
                    <div class="panel-item-info">
                        <div class="panel-item-title"><?= htmlspecialchars($notif['titulo']) ?></div>
                        <div class="panel-item-sub"><?= htmlspecialchars($notif['mensaje'] ?? '') ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
//Usuario
        <a href="<?= $base ?>/perfil.php" class="nav-user">
            <img src="<?= htmlspecialchars($usuario['imagenurl'] ?? $imgAvatarDef) ?>" alt="avatar">
            <?= htmlspecialchars($usuario['nombreusuario']) ?>
        </a>

        <?php else: ?>
        <a href="<?= $base ?>/autenticacion/login.php" class="btn-primary" style="padding:7px 20px;font-size:13px">Iniciar sesión</a>
        <?php endif; ?>
    </div>
</nav>

// EL segundo navbar
<div class="navbar-sub">
    <div class="subnav-left">

        <div class="subnav-dropdown">
            <button class="subnav-btn" onclick="toggleDropdown('ddNavegar')">
                Navegar
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="subnav-menu" id="ddNavegar">
                <a href="<?= $base ?>/index.php">Tienda</a>
                <a href="<?= $base ?>/biblioteca.php">Mi Biblioteca</a>
                <a href="<?= $base ?>/comunidad.php">Comunidad</a>
                <a href="<?= $base ?>/categorias.php">Categorías</a>
                <a href="<?= $base ?>/perfil.php">Mi perfil</a>
                <a href="<?= $base ?>/soporte.php">Soporte</a>
            </div>
        </div>
//Para las recomendaciones
        <div class="subnav-dropdown">
            <button class="subnav-btn" onclick="toggleDropdown('ddRecomend')">
                Recomendaciones
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="subnav-menu" id="ddRecomend" style="min-width:260px;">
                <?php if (empty($lstRecomend)): ?>
                    <div style="padding:12px 18px;color:var(--text-dim);font-size:13px">Inicia sesión para ver recomendaciones</div>
                <?php else: foreach ($lstRecomend as $rec): ?>
                <a href="<?= $base ?>/juego.php?id=<?= $rec['idvideojuego'] ?>" style="display:flex;align-items:center;gap:10px;">
                    <img src="<?= htmlspecialchars($rec['imagen'] ?? $imgPlaceholder) ?>"
                         style="width:48px;height:30px;object-fit:cover;border-radius:3px;flex-shrink:0" alt="">
                    <span><?= htmlspecialchars($rec['titulo']) ?></span>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="subnav-dropdown">
            <button class="subnav-btn" onclick="toggleDropdown('ddCats')">
                Categorías
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="subnav-menu subnav-menu-grid" id="ddCats">
                <?php foreach ($lstCatsNav as $catNombre): ?>
                <a href="<?= $base ?>/categorias.php?cat=<?= urlencode($catNombre) ?>"><?= htmlspecialchars($catNombre) ?></a>
                <?php endforeach; ?>
                <?php if (empty($lstCatsNav)): ?>
                <div style="padding:12px 18px;color:var(--text-dim);font-size:13px;grid-column:1/-1">Sin categorias</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
//Carrito
    <?php if ($usuario): ?>
    <div style="position:relative;margin-left:8px;">
        <div class="subnav-cart" onclick="togglePanel('panelCarrito')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <?php if ($cntCarrito > 0): ?><span class="nav-badge" data-panel="panelCarrito" style="top:10px;right:12px;"></span><?php endif; ?>
        </div>
        <div class="panel-flotante" id="panelCarrito" style="right:0;top:48px;">
            <div class="panel-header">
                Carrito <span style="font-size:11px;color:var(--text-dim)"><?= $cntCarrito ?> juegos</span>
            </div>
            <?php if (empty($lstCarrito)): ?>
                <div class="panel-empty">Tu carrito esta vacio</div>
            <?php else:
                $totalCarrito = 0;
                foreach ($lstCarrito as $itemCar):
                    $totalCarrito += $itemCar['precio']; ?>
                <a class="panel-item" href="<?= $base ?>/juego.php?id=<?= $itemCar['idvideojuego'] ?>">
                    <img src="<?= htmlspecialchars($itemCar['imagen'] ?? $imgPlaceholder) ?>" alt="">
                    <div class="panel-item-info">
                        <div class="panel-item-title"><?= htmlspecialchars($itemCar['titulo']) ?></div>
                        <div class="panel-item-sub">Mex$ <?= number_format($itemCar['precio'],2) ?></div>
                    </div>
                    <form method="POST" action="<?= $base ?>/controladores/carrito.php" onclick="event.stopPropagation()">
                        <input type="hidden" name="accion" value="quitar">
                        <input type="hidden" name="id_juego" value="<?= $itemCar['idvideojuego'] ?>">
                        <button type="submit" style="background:none;border:none;color:var(--text-dim);font-size:14px;cursor:pointer;">✕</button>
                    </form>
                </a>
                <?php endforeach; ?>
                <div class="panel-footer" style="display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:11px;color:var(--text-dim);">Total:</span>
                        <span style="font-size:14px;font-weight:700;color:var(--accent);">Mex$ <?= number_format($totalCarrito,2) ?></span>
                    </div>
                    <button class="btn-primary btn-block" style="font-size:12px;padding:8px;"
                            onclick="comprarCarritoCompleto(<?= $totalCarrito ?>); event.stopPropagation();">
                        Comprar todo (<?= number_format($totalCarrito,2) ?> MXN)
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
//Buscador
    <div class="subnav-search">
        <form method="GET" action="<?= $base ?>/buscar.php">
            <input type="text" name="q" placeholder="Buscar en la tienda"
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </form>
    </div>
</div>

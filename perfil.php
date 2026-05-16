<?php
session_start();
require_once 'configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';
if (!isset($_SESSION['usuario'])) { header("Location: $base/autenticacion/login.php"); exit; }
$yo = $_SESSION['usuario'];

$resenas = $pdo->prepare("SELECT COUNT(*) FROM Reseñas WHERE IdUsuario=?"); $resenas->execute([$yo['idusuario']]); $n_res = $resenas->fetchColumn();
$posts = $pdo->prepare("SELECT COUNT(*) FROM ComunidadVideoJuegos WHERE IdUsuario=?"); $posts->execute([$yo['idusuario']]); $n_posts = $posts->fetchColumn();
$juegos = $pdo->prepare("SELECT COUNT(*) FROM Biblioteca WHERE IdUsuario=?"); $juegos->execute([$yo['idusuario']]); $n_juegos = $juegos->fetchColumn();
$favs = $pdo->prepare("SELECT COUNT(*) FROM Favorito WHERE IdUsuario=?"); $favs->execute([$yo['idusuario']]); $n_favs = $favs->fetchColumn();

$s = $pdo->prepare("SELECT v.Titulo,b.FechaCompra,(SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Img FROM Biblioteca b JOIN VideoJuegos v ON b.IdVideoJuego=v.IdVideoJuego WHERE b.IdUsuario=? ORDER BY b.FechaCompra DESC LIMIT 5");
$s->execute([$yo['idusuario']]); $compras = $s->fetchAll();

$s = $pdo->prepare("SELECT r.Titulo,r.Calificacion,r.FechaCreacion,v.Titulo AS Juego FROM Reseñas r JOIN VideoJuegos v ON r.IdVideoJuego=v.IdVideoJuego WHERE r.IdUsuario=? ORDER BY r.FechaCreacion DESC LIMIT 5");
$s->execute([$yo['idusuario']]); $mis_res = $s->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi perfil – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/navbar.php'; ?>

<div class="perfil-hero">
    <div class="container">
        <div style="display:flex;align-items:flex-end;gap:20px;">
            <div class="perfil-avatar-wrap" onclick="document.getElementById('inp-avatar').click()" title="Cambiar foto">
                <img class="perfil-avatar" id="preview-av"
                     src="<?= htmlspecialchars($yo['imagenurl'] ?? $base.'/recursos/imagenes/avatar-defecto.svg') ?>" alt="avatar">
                <div class="perfil-avatar-edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/>
                    </svg>
                </div>
            </div>
            <input type="file" id="inp-avatar" accept="image/*" style="display:none" onchange="actualizarAvatar(this)">
            <div>
                <div style="font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;"><?= htmlspecialchars($yo['nombreusuario']) ?></div>
                <div style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($yo['correo']) ?></div>
                <span class="badge badge-info" style="margin-top:6px;"><?= ucfirst($yo['rol']) ?></span>
            </div>
        </div>
        <div class="perfil-stats">
            <div class="perfil-stat"><div class="num"><?= $n_juegos ?></div><div class="lbl">Juegos</div></div>
            <div class="perfil-stat"><div class="num"><?= $n_res ?></div><div class="lbl">Reseñas</div></div>
            <div class="perfil-stat"><div class="num"><?= $n_posts ?></div><div class="lbl">Posts comunidad</div></div>
            <div class="perfil-stat"><div class="num"><?= $n_favs ?></div><div class="lbl">Favoritos</div></div>
        </div>
    </div>
</div>

<div class="container" style="padding-top:32px;padding-bottom:40px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;">
        <div>
            <h2 class="section-title">Últimas compras</h2>
            <?php if (empty($compras)): ?><div class="empty-msg"><p>Sin compras aun</p></div>
            <?php else: foreach ($compras as $c): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bg-card);border:1px solid var(--border-dim);border-radius:8px;margin-bottom:8px;">
                <img src="<?= htmlspecialchars($c['img'] ?? $base.'/recursos/imagenes/placeholder.svg') ?>"
                     style="width:54px;height:34px;object-fit:cover;border-radius:4px;">
                <div>
                    <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($c['titulo']) ?></div>
                    <div style="font-size:11px;color:var(--text-dim);"><?= date('d/m/Y', strtotime($c['fechacompra'])) ?></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div>
            <h2 class="section-title">Mis reseñas</h2>
            <?php if (empty($mis_res)): ?><div class="empty-msg"><p>Sin reseñas aun</p></div>
            <?php else: foreach ($mis_res as $r): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border-dim);border-radius:8px;padding:12px;margin-bottom:8px;">
                <div style="font-size:12px;color:var(--accent);"><?= htmlspecialchars($r['juego']) ?></div>
                <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($r['titulo'] ?? '') ?></div>
                <div style="color:var(--warning);font-size:12px;"><?= str_repeat('★',$r['calificacion']) ?><?= str_repeat('☆',5-$r['calificacion']) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <hr class="divider">
    <div style="display:flex;gap:12px;">
        <a href="<?= $base ?>/autenticacion/logout.php" class="btn-secondary">Cerrar sesión</a>
        <?php if (in_array($yo['rol'],['admin','soporte'])): ?>
        <a href="<?= $base ?>/administrador/index.php" class="btn-primary">Panel de administrador</a>
        <?php endif; ?>
    </div>
</div>

<?php include 'vistas/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
<script src="<?= $base ?>/"></script>
<script>
async function actualizarAvatar(input) {
    if (!input.files.length) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById('preview-av').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
    const fd = new FormData();
    fd.append('tipo', 'avatar');
    fd.append('archivo', input.files[0]);
    const res = await fetch('/squareenix/controladores/SubirImagen.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
        document.querySelectorAll('.nav-user img, .perfil-avatar').forEach(img => img.src = data.url);
        const d = document.createElement('div');
        d.className = 'form-alert success'; d.textContent = 'Foto de perfil actualizada.';
        d.style.cssText = 'margin:12px 0;';
        document.querySelector('.container').prepend(d);
        setTimeout(() => d.remove(), 3000);
    } else { alert(data.error || 'Error al subir imagen'); }
}
</script>
</body>
</html>

<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['admin','soporte'])) {
    header("Location: $base/autenticacion/login.php"); exit;
}

$usuarios = $pdo->query("SELECT COUNT(*) FROM Usuario")->fetchColumn();
$juegos = $pdo->query("SELECT COUNT(*) FROM VideoJuegos WHERE Estado != 'descontinuado'")->fetchColumn();
$ordenes = $pdo->query("SELECT COUNT(*) FROM Orden WHERE EstadoPago='completado'")->fetchColumn();
$ingresos = $pdo->query("SELECT COALESCE(SUM(PrecioUnitario*Cantidad),0) FROM DetalleOrden do2 JOIN Orden o ON do2.IdOrden=o.IdOrden WHERE o.EstadoPago='completado'")->fetchColumn();
$tickets = $pdo->query("SELECT COUNT(*) FROM TicketSoporte WHERE Estado='abierto'")->fetchColumn();

$top_ventas = $pdo->query("SELECT v.Titulo, COUNT(*) AS ventas FROM DetalleOrden do2 JOIN Orden o ON do2.IdOrden=o.IdOrden JOIN VideoJuegos v ON do2.IdVideoJuego=v.IdVideoJuego WHERE o.EstadoPago='completado' GROUP BY v.Titulo ORDER BY ventas DESC LIMIT 5")->fetchAll();
$top_favoritos= $pdo->query("SELECT v.Titulo, COUNT(*) AS n FROM Favorito f JOIN VideoJuegos v ON f.IdVideoJuego=v.IdVideoJuego GROUP BY v.Titulo ORDER BY n DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de control – Administrador Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/administrador.css">
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>
<main class="adminMain">
    <div class="adminHeader"><h1>Panel de control</h1></div>

    <div class="adminStatsGrid">
        <div class="adminStatCard"><span class="statNum"><?= $usuarios ?></span><div class="statLabel">Usuarios registrados</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $juegos ?></span><div class="statLabel">Juegos activos</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $ordenes ?></span><div class="statLabel">Ordenes completadas</div></div>
        <div class="adminStatCard"><span class="statNum">$<?= number_format($ingresos, 0) ?></span><div class="statLabel">Ingresos totales (MXN)</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $tickets ?></span><div class="statLabel">Tickets abiertos</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:8px;">
        <div class="adminFormCard">
            <h3>Top ventas</h3>
            <?php if (empty($top_ventas)): ?>
                <p style="color:var(--text-dim);font-size:13px;">Sin ventas aun</p>
            <?php else: ?>
            <table class="adminTabla">
                <thead><tr><th>Juego</th><th>Ventas</th></tr></thead>
                <tbody>
                    <?php foreach ($top_ventas as $t): ?>
                    <tr><td><?= htmlspecialchars($t['titulo']) ?></td><td><?= $t['ventas'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
// Para los top favoritos
        <div class="adminFormCard">
            <h3>Top favoritos</h3>
            <?php if (empty($top_favoritos)): ?>
                <p style="color:var(--text-dim);font-size:13px;">Sin favoritos aun.</p>
            <?php else: ?>
            <table class="adminTabla">
                <thead><tr><th>Juego</th><th>Favoritos</th></tr></thead>
                <tbody>
                    <?php foreach ($top_favoritos as $t): ?>
                    <tr><td><?= htmlspecialchars($t['titulo']) ?></td><td><?= $t['n'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>

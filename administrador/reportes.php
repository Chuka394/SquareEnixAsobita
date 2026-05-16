<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
$base = '/squareenix';
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header("Location: $base/autenticacion/login.php"); exit;
}

// Informacion sobre el reporte

$total_usuarios = $pdo->query("SELECT COUNT(*) FROM Usuario WHERE Rol != 'baneado'")->fetchColumn();
$total_baneados = $pdo->query("SELECT COUNT(*) FROM Usuario WHERE Rol = 'baneado'")->fetchColumn();
$total_juegos = $pdo->query("SELECT COUNT(*) FROM VideoJuegos WHERE Estado != 'descontinuado'")->fetchColumn();
$total_ventas = $pdo->query("SELECT COUNT(*) FROM Orden WHERE EstadoPago='completado'")->fetchColumn();
$total_ingresos = $pdo->query("SELECT COALESCE(SUM(do2.PrecioUnitario*do2.Cantidad),0) FROM DetalleOrden do2 JOIN Orden o ON do2.IdOrden=o.IdOrden WHERE o.EstadoPago='completado'")->fetchColumn();
$total_resenas = $pdo->query("SELECT COUNT(*) FROM Reseñas")->fetchColumn();
$total_tickets = $pdo->query("SELECT COUNT(*) FROM TicketSoporte WHERE Estado='abierto'")->fetchColumn();
$total_posts_com = $pdo->query("SELECT COUNT(*) FROM ComunidadVideoJuegos")->fetchColumn();

// El top 10 de los juegos mas vendidos
$top_ventas = $pdo->query("
    SELECT v.Titulo, COUNT(*) AS ventas, SUM(do2.PrecioUnitario) AS ingresos
    FROM DetalleOrden do2
    JOIN Orden o ON do2.IdOrden=o.IdOrden
    JOIN VideoJuegos v ON do2.IdVideoJuego=v.IdVideoJuego
    WHERE o.EstadoPago='completado'
    GROUP BY v.Titulo ORDER BY ventas DESC LIMIT 10
")->fetchAll();

// Top 10 mas favoriteados
$top_favoritos = $pdo->query("
    SELECT v.Titulo, COUNT(*) AS n
    FROM Favorito f JOIN VideoJuegos v ON f.IdVideoJuego=v.IdVideoJuego
    GROUP BY v.Titulo ORDER BY n DESC LIMIT 10
")->fetchAll();

// Top 10 mas agregados al carrito
$top_carrito = $pdo->query("
    SELECT v.Titulo, COUNT(*) AS n
    FROM DetalleCarrito dc JOIN VideoJuegos v ON dc.IdVideoJuego=v.IdVideoJuego
    GROUP BY v.Titulo ORDER BY n DESC LIMIT 10
")->fetchAll();

// Top 5 mejores calificados
$top_calificados = $pdo->query("
    SELECT v.Titulo, ROUND(AVG(r.Calificacion),1) AS promedio, COUNT(*) AS num_resenas
    FROM Reseñas r JOIN VideoJuegos v ON r.IdVideoJuego=v.IdVideoJuego
    GROUP BY v.Titulo HAVING COUNT(*) >= 1 ORDER BY promedio DESC, num_resenas DESC LIMIT 5
")->fetchAll();

// Nuevos usuarios por mes de 1 año
$nuevos_usuarios = $pdo->query("
    SELECT TO_CHAR(Fecha_registro,'YYYY-MM') AS mes, COUNT(*) AS n
    FROM Usuario GROUP BY mes ORDER BY mes DESC LIMIT 12
")->fetchAll();

// Ventas por mes
$ventas_mes = $pdo->query("
    SELECT TO_CHAR(o.FechaCreacion,'YYYY-MM') AS mes, COUNT(*) AS ventas, SUM(do2.PrecioUnitario) AS ingresos
    FROM Orden o JOIN DetalleOrden do2 ON do2.IdOrden=o.IdOrden
    WHERE o.EstadoPago='completado'
    GROUP BY mes ORDER BY mes DESC LIMIT 12
")->fetchAll();

// Tickets por estado
$tickets_estado = $pdo->query("
    SELECT Estado, COUNT(*) AS n FROM TicketSoporte GROUP BY Estado ORDER BY n DESC
")->fetchAll();

// Juegos con mas publicaciones en comunidad
$top_comunidad = $pdo->query("
    SELECT v.Titulo, COUNT(*) AS n
    FROM ComunidadVideoJuegos c JOIN VideoJuegos v ON c.IdVideoJuego=v.IdVideoJuego
    GROUP BY v.Titulo ORDER BY n DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes – Admin Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/administrador.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>

<main class="adminMain">
    <div class="adminHeader">
        <h1>Reportes del sistema</h1>
        <button class="btnAdminPrimario" onclick="generarPDF()">⬇ Exportar PDF completo</button>
    </div>

    <div class="adminStatsGrid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));margin-bottom:28px;">
        <div class="adminStatCard"><span class="statNum"><?= $total_usuarios ?></span><div class="statLabel">Usuarios activos</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $total_baneados ?></span><div class="statLabel">Usuarios baneados</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $total_juegos ?></span><div class="statLabel">Juegos activos</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $total_ventas ?></span><div class="statLabel">Ventas completadas</div></div>
        <div class="adminStatCard"><span class="statNum">$<?= number_format($total_ingresos,0) ?></span><div class="statLabel">Ingresos totales (MXN)</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $total_resenas ?></span><div class="statLabel">Reseñas publicadas</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $total_tickets ?></span><div class="statLabel">Tickets abiertos</div></div>
        <div class="adminStatCard"><span class="statNum"><?= $total_posts_com ?></span><div class="statLabel">Posts comunidad</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- Top ventas -->
        <div class="adminFormCard" id="sec-ventas">
            <h3>Juegos más vendidos</h3>
            <?php if (empty($top_ventas)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin ventas aun.</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>#</th><th>Juego</th><th>Ventas</th><th>Ingresos</th></tr></thead>
                <tbody>
                    <?php foreach ($top_ventas as $k=>$v): ?>
                    <tr><td><?= $k+1 ?></td><td><?= htmlspecialchars($v['titulo']) ?></td>
                        <td><?= $v['ventas'] ?></td><td>$<?= number_format($v['ingresos'],2) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
// top de los favoritos
        <div class="adminFormCard" id="sec-favoritos">
            <h3>Juegos más favoriteados</h3>
            <?php if (empty($top_favoritos)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin favoritos.</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>#</th><th>Juego</th><th>Favoritos</th></tr></thead>
                <tbody>
                    <?php foreach ($top_favoritos as $k=>$f): ?>
                    <tr><td><?= $k+1 ?></td><td><?= htmlspecialchars($f['titulo']) ?></td><td><?= $f['n'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
// top de lo añadido al carrito
        <div class="adminFormCard" id="sec-carrito">
            <h3>Mas añadidos al carrito</h3>
            <?php if (empty($top_carrito)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin datos.</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>#</th><th>Juego</th><th>Veces en carrito</th></tr></thead>
                <tbody>
                    <?php foreach ($top_carrito as $k=>$c): ?>
                    <tr><td><?= $k+1 ?></td><td><?= htmlspecialchars($c['titulo']) ?></td><td><?= $c['n'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
// Mejores calificados
        <div class="adminFormCard" id="sec-calificados">
            <h3>Mejor calificados</h3>
            <?php if (empty($top_calificados)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin reseñas aun</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>Juego</th><th>Promedio</th><th>Reseñas</th></tr></thead>
                <tbody>
                    <?php foreach ($top_calificados as $r): ?>
                    <tr><td><?= htmlspecialchars($r['titulo']) ?></td>
                        <td><span style="color:var(--warning);"><?= str_repeat('★',(int)round($r['promedio'])) ?></span> <?= $r['promedio'] ?></td>
                        <td><?= $r['num_resenas'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
// Ventan acumuldas por mes
        <div class="adminFormCard" id="sec-ventas-mes">
            <h3>📅 Ventas por mes</h3>
            <?php if (empty($ventas_mes)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin datos.</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>Mes</th><th>Ventas</th><th>Ingresos</th></tr></thead>
                <tbody>
                    <?php foreach ($ventas_mes as $v): ?>
                    <tr><td><?= $v['mes'] ?></td><td><?= $v['ventas'] ?></td><td>$<?= number_format($v['ingresos'],2) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
// Los nuevos usuarios que se unen
        <div class="adminFormCard" id="sec-usuarios-mes">
            <h3>Nuevos usuarios por mes</h3>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>Mes</th><th>Registros</th></tr></thead>
                <tbody>
                    <?php foreach ($nuevos_usuarios as $u): ?>
                    <tr><td><?= $u['mes'] ?></td><td><?= $u['n'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($nuevos_usuarios)): ?>
                    <tr><td colspan="2" style="color:var(--text-dim);">Sin datos</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

// Los reportes por ticket
        <div class="adminFormCard" id="sec-tickets">
            <h3>Tickets por estado</h3>
            <?php if (empty($tickets_estado)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin tickets</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
                <tbody>
                    <?php foreach ($tickets_estado as $t): ?>
                    <tr><td><span class="badge badge-<?= $t['estado']==='abierto'?'info':($t['estado']==='resuelto'?'success':'warning') ?>"><?= ucfirst(str_replace('_',' ',$t['estado'])) ?></span></td>
                        <td><?= $t['n'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

// La comunidad mas activa
        <div class="adminFormCard" id="sec-comunidad">
            <h3>Juegos con más actividad en comunidad</h3>
            <?php if (empty($top_comunidad)): ?><p style="color:var(--text-dim);font-size:13px;margin-top:8px;">Sin publicaciones</p>
            <?php else: ?>
            <table class="adminTabla" style="margin-top:10px;">
                <thead><tr><th>#</th><th>Juego</th><th>Posts</th></tr></thead>
                <tbody>
                    <?php foreach ($top_comunidad as $k=>$c): ?>
                    <tr><td><?= $k+1 ?></td><td><?= htmlspecialchars($c['titulo']) ?></td><td><?= $c['n'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
const { jsPDF } = window.jspdf;

function tbl(doc, head, body, y, title) {
    doc.setFontSize(12); doc.setFont('helvetica','bold');
    doc.setTextColor(0,188,212); doc.text(title, 14, y);
    doc.autoTable({
        startY: y + 4,
        head: [head],
        body: body.length ? body : [head.map(()=>'-')],
        styles: { fontSize: 9, textColor: [30,30,30] },
        headStyles: { fillColor: [0,188,212], textColor: [0,0,0], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [245,245,245] },
    });
    return doc.lastAutoTable.finalY + 8;
}

function generarPDF() {
    const doc = new jsPDF(); const fecha = new Date().toLocaleDateString('es-MX');

 
    doc.setFillColor(13,27,46); doc.rect(0,0,210,28,'F');
    doc.setTextColor(0,188,212); doc.setFontSize(16); doc.setFont('helvetica','bold');
    doc.text('SQUARE ENIX STORE — Reporte General', 14, 12);
    doc.setTextColor(180,180,180); doc.setFontSize(9); doc.setFont('helvetica','normal');
    doc.text('Generado: ' + fecha, 14, 20);


    doc.setFontSize(10); doc.setTextColor(0,0,0); doc.setFont('helvetica','bold');
    doc.text('Resumen general:', 14, 36);
    doc.setFont('helvetica','normal'); doc.setFontSize(9);
    const kpis = [
        ['Usuarios activos','<?= $total_usuarios ?>'],['Usuarios baneados','<?= $total_baneados ?>'],
        ['Juegos activos','<?= $total_juegos ?>'],['Ventas completadas','<?= $total_ventas ?>'],
        ['Ingresos totales','$<?= number_format($total_ingresos,2) ?> MXN'],
        ['Reseñas publicadas','<?= $total_resenas ?>'],['Tickets abiertos','<?= $total_tickets ?>'],
        ['Posts comunidad','<?= $total_posts_com ?>'],
    ];
    kpis.forEach(([k,v],i) => {
        const col = i<4 ? 0 : 1, row = i%4;
        doc.setTextColor(100,100,100); doc.text(k+':', 14 + col*96, 44 + row*6);
        doc.setTextColor(0,0,0); doc.text(v, 90 + col*96, 44 + row*6);
    });

    let y = 72;
    y = tbl(doc,['#','Juego','Ventas','Ingresos'],
        <?= json_encode(array_map(fn($v,$k)=>[(string)($k+1),$v['titulo'],(string)$v['ventas'],'$'.number_format($v['ingresos'],2)], $top_ventas, array_keys($top_ventas))) ?>,
        y, 'Juegos más vendidos');

    y = tbl(doc,['#','Juego','Favoritos'],
        <?= json_encode(array_map(fn($f,$k)=>[(string)($k+1),$f['titulo'],(string)$f['n']], $top_favoritos, array_keys($top_favoritos))) ?>,
        y, 'Más favoriteados');

    if (y > 240) { doc.addPage(); y = 16; }

    y = tbl(doc,['#','Juego','Veces en carrito'],
        <?= json_encode(array_map(fn($c,$k)=>[(string)($k+1),$c['titulo'],(string)$c['n']], $top_carrito, array_keys($top_carrito))) ?>,
        y, 'Más añadidos al carrito');

    y = tbl(doc,['Juego','Promedio','Reseñas'],
        <?= json_encode(array_map(fn($r)=>[$r['titulo'],(string)$r['promedio'],(string)$r['num_resenas']], $top_calificados)) ?>,
        y, 'Mejor calificados');

    if (y > 220) { doc.addPage(); y = 16; }

    y = tbl(doc,['Mes','Ventas','Ingresos'],
        <?= json_encode(array_map(fn($v)=>[$v['mes'],(string)$v['ventas'],'$'.number_format($v['ingresos'],2)], $ventas_mes)) ?>,
        y, 'Ventas por mes');

    y = tbl(doc,['Mes','Nuevos usuarios'],
        <?= json_encode(array_map(fn($u)=>[$u['mes'],(string)$u['n']], $nuevos_usuarios)) ?>,
        y, 'Nuevos usuarios por mes');

    if (y > 230) { doc.addPage(); y = 16; }

    y = tbl(doc,['Estado','Cantidad'],
        <?= json_encode(array_map(fn($t)=>[ucfirst(str_replace('_',' ',$t['estado'])),(string)$t['n']], $tickets_estado)) ?>,
        y, 'Tickets por estado');

    tbl(doc,['#','Juego','Posts'],
        <?= json_encode(array_map(fn($c,$k)=>[(string)($k+1),$c['titulo'],(string)$c['n']], $top_comunidad, array_keys($top_comunidad))) ?>,
        y, 'Actividad en comunidad');

    doc.save('reporte_squareenix_' + fecha.replace(/\//g,'-') + '.pdf');
}
</script>
</body>
</html>

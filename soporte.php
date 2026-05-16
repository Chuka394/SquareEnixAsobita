<?php
session_start();
require_once 'configuracion/bd.php';
require_once 'configuracion/funciones.php';
$pdo = bd();
$base = '/squareenix';
$usuario = $_SESSION['usuario'] ?? null;
$mensaje = ''; $error = '';

// Juegos para el select
$juegos_list = $pdo->query("SELECT IdVideoJuego, Titulo FROM VideoJuegos WHERE Estado != 'descontinuado' ORDER BY Titulo")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$usuario) { $error = 'Debes iniciar sesión para enviar un ticket.'; }
    else {
        $asunto = limpiar($_POST['asunto'] ?? '');
        $descripcion = limpiar($_POST['descripcion'] ?? '');
        $tipo = $_POST['tipo'] ?? 'consulta';
        $juego_id = entero($_POST['id_juego'] ?? 0) ?: null;

        if (!$asunto || !$descripcion) { $error = 'El asunto y la descripción son obligatorios.'; }
        else {
            $s = $pdo->prepare("INSERT INTO TicketSoporte (Asunto,Descripcion,TipoSolicitud,Estado,IdUsuario,IdVideoJuego) VALUES (?,?,?,'abierto',?,?) RETURNING IdTicket");
            $s->execute([$asunto, $descripcion, $tipo, $usuario['idusuario'], $juego_id]);
            $idTicket = $s->fetchColumn();

            $pdo->prepare("INSERT INTO Notificaciones (Titulo,Mensaje,IdUsuario,IdTicket) VALUES ('Ticket recibido',?,?,?)")
                ->execute(["Tu ticket \"$asunto\" fue recibido. Te responderemos pronto.", $usuario['idusuario'], $idTicket]);

            $mensaje = '¡Tu reporte fue enviado! Te responderemos lo antes posible.';
        }
    }
}

// Tickets del usuario
$mis_tickets = [];
if ($usuario) {
    $s = $pdo->prepare("SELECT t.*, v.Titulo AS JuegoTitulo FROM TicketSoporte t LEFT JOIN VideoJuegos v ON t.IdVideoJuego=v.IdVideoJuego WHERE t.IdUsuario=? ORDER BY t.IdTicket DESC");
    $s->execute([$usuario['idusuario']]); $mis_tickets = $s->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Soporte – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body>
<?php include 'vistas/parciales/navbar.php'; ?>
<div class="contacto-layout">
    <h1 class="section-title">Soporte y Contacto</h1>

    <?php if ($mensaje): ?><div class="form-alert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="form-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!$usuario): ?>
        <div class="form-alert info"><a href="<?= $base ?>/autenticacion/login.php" style="color:var(--accent);">Inicia sesion</a> para enviar un ticket de soporte.</div>
    <?php else: ?>
    <div style="background:var(--bg-card);border:1px solid var(--border-dim);border-radius:12px;padding:28px;margin-bottom:32px;">
        <h2 style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:600;margin-bottom:18px;">Nuevo ticket</h2>
        <form method="POST">
            <div class="formGrupo">
                <label>Tipo de solicitud</label>
                <select name="tipo" class="form-control">
                    <option value="consulta">Consulta general</option>
                    <option value="problema_tecnico">Problema técnico</option>
                    <option value="reembolso">Solicitud de reembolso</option>
                    <option value="reporte_bug">Reporte de bug</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="formGrupo">
                <label>Asunto</label>
                <input type="text" name="asunto" class="form-control" required placeholder="Describe brevemente el problema">
            </div>
            <div class="formGrupo">
                <label>Juego relacionado</label>
                <select name="id_juego" class="form-control">
                    <option value="">— Sin juego específico —</option>
                    <?php foreach ($juegos_list as $j): ?>
                    <option value="<?= $j['idvideojuego'] ?>"><?= htmlspecialchars($j['titulo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="formGrupo">
                <label>Descripcion</label>
                <textarea name="descripcion" class="form-control" rows="5" required placeholder="Describe el problema con el mayor detalle posible..."></textarea>
            </div>
            <button type="submit" class="btn-primary">Enviar ticket</button>
        </form>
    </div>

    <?php if (!empty($mis_tickets)): ?>
    <h2 style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:600;margin-bottom:16px;">Mis tickets</h2>
    <?php foreach ($mis_tickets as $t): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border-dim);border-radius:8px;padding:16px;margin-bottom:10px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
            <span style="font-weight:600;font-size:14px;"><?= htmlspecialchars($t['asunto']) ?></span>
            <span class="badge badge-<?= $t['estado']==='abierto'?'info':($t['estado']==='resuelto'?'success':'warning') ?>">
                <?= ucfirst(str_replace('_',' ',$t['estado'])) ?>
            </span>
            <span style="font-size:11px;color:var(--text-dim);margin-left:auto;">#<?= $t['idticket'] ?></span>
        </div>
        <?php if ($t['juegotitulo']): ?>
        <div style="font-size:12px;color:var(--accent);margin-bottom:4px;">Juego: <?= htmlspecialchars($t['juegotitulo']) ?></div>
        <?php endif; ?>
        <p style="font-size:13px;color:var(--text-muted);"><?= nl2br(htmlspecialchars($t['descripcion'] ?? '')) ?></p>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php include 'vistas/parciales/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
</body>
</html>

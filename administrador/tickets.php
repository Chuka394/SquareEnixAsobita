<?php
session_start();
require_once '../configuracion/bd.php';
require_once '../configuracion/funciones.php';
$pdo = bd();
$base = '/squareenix';
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['admin','soporte'])) {
    header("Location: $base/autenticacion/login.php"); exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = entero($_POST['ticket_id'] ?? 0, 1);
    $resp = limpiar($_POST['respuesta'] ?? '');
    $estado = $_POST['estado'] ?? 'en_proceso';
    if ($resp && $tid) {
        $pdo->prepare("UPDATE TicketSoporte SET Estado=? WHERE IdTicket=?")->execute([$estado,$tid]);
        $s = $pdo->prepare("SELECT IdUsuario, Asunto FROM TicketSoporte WHERE IdTicket=?");
        $s->execute([$tid]); $t = $s->fetch();
        if ($t) {
            $pdo->prepare("INSERT INTO Notificaciones (Titulo,Mensaje,IdUsuario,IdTicket) VALUES ('Respuesta a tu ticket',?,?,?)")
                ->execute([$resp, $t['idusuario'], $tid]);
        }
        $mensaje = 'Respuesta enviada.';
    }
}

$filtro = $_GET['estado'] ?? 'todos';
$sql = "SELECT t.*, u.NombreUsuario, u.Correo, v.Titulo AS JuegoTitulo FROM TicketSoporte t JOIN Usuario u ON t.IdUsuario=u.IdUsuario LEFT JOIN VideoJuegos v ON t.IdVideoJuego=v.IdVideoJuego";
if ($filtro !== 'todos') $sql .= " WHERE t.Estado=" . $pdo->quote($filtro);
$sql .= " ORDER BY t.IdTicket DESC";
$tickets = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tickets – Administradr Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/administrador.css">
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>
<main class="adminMain">
    <div class="adminHeader">
        <h1>Tickets de soporte</h1>
        <span class="adminCount"><?= count($tickets) ?> tickets</span>
    </div>
    <?php if ($mensaje): ?><div class="adminAlert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

    <!-- Filtros -->
    <div class="adminFiltros" style="margin-bottom:20px;">
        <?php foreach (['todos','abierto','en_proceso','resuelto'] as $e): ?>
        <a href="?estado=<?= $e ?>" class="cat-pill <?= $filtro===$e?'active':'' ?>"><?= ucfirst(str_replace('_',' ',$e)) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="empty-msg"><p>No hay tickets</p></div>
    <?php else: ?>
    <?php foreach ($tickets as $t): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border-dim);border-radius:10px;margin-bottom:14px;overflow:hidden;">
        <div style="padding:14px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--border-dim);">
            <div style="flex:1;">
                <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($t['asunto']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                    <?= htmlspecialchars($t['nombreusuario']) ?> — <?= htmlspecialchars($t['correo']) ?>
                    <?php if ($t['juegotitulo']): ?> | <?= htmlspecialchars($t['juegotitulo']) ?><?php endif; ?>
                </div>
            </div>
            <span class="badge badge-<?= $t['estado']==='abierto'?'info':($t['estado']==='resuelto'?'success':'warning') ?>">
                <?= ucfirst(str_replace('_',' ',$t['estado'])) ?>
            </span>
            <span style="font-size:11px;color:var(--text-dim);">#<?= $t['idticket'] ?></span>
        </div>
        <div style="padding:12px 18px;">
            <p style="font-size:13px;color:var(--text-muted);"><?= nl2br(htmlspecialchars($t['descripcion'] ?? '')) ?></p>
        </div>
        <details style="padding:0 18px 14px;">
            <summary style="font-size:13px;font-weight:500;cursor:pointer;color:var(--accent);margin-bottom:10px;">Responder / cambiar estado</summary>
            <form method="POST">
                <input type="hidden" name="ticket_id" value="<?= $t['idticket'] ?>">
                <div style="display:grid;grid-template-columns:1fr auto;gap:10px;align-items:flex-end;">
                    <textarea name="respuesta" class="form-control" rows="2" placeholder="Tu respuesta al usuario..." required></textarea>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <select name="estado" class="form-control" style="font-size:12px;padding:8px;">
                            <option value="en_proceso">En proceso</option>
                            <option value="resuelto">Resuelto</option>
                            <option value="abierto">Abierto</option>
                        </select>
                        <button type="submit" class="btnAdminPrimario" style="font-size:12px;padding:8px;">Enviar</button>
                    </div>
                </div>
            </form>
        </details>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>

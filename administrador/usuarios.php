<?php
session_start();
require_once '../configuracion/bd.php';
require_once '../configuracion/funciones.php';
$pdo = bd();
$base = '/squareenix';
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['admin','soporte'])) {
    header("Location: $base/autenticacion/login.php"); exit;
}

$mensaje = ''; $error = '';

// post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['usuario']['rol'] === 'admin') {
    $idTarget = entero($_POST['id_usuario'] ?? 0, 1);
    $accionPost = $_POST['accion_usuario'] ?? '';

    if ($idTarget && $idTarget !== $_SESSION['usuario']['idusuario']) {
        if ($accionPost === 'cambiar_rol') {
            $nuevoRol = $_POST['nuevo_rol'] ?? '';
            if (in_array($nuevoRol, ['cliente','soporte','admin'])) {
                $pdo->prepare("UPDATE Usuario SET Rol=? WHERE IdUsuario=?")->execute([$nuevoRol,$idTarget]);
                $mensaje = 'Rol actualizado correctamente.';
            }
        } elseif ($accionPost === 'banear') {
            $pdo->prepare("UPDATE Usuario SET Rol='baneado' WHERE IdUsuario=?")->execute([$idTarget]);
            $mensaje = 'Usuario baneado. Ya no podrá iniciar sesión.';
        } elseif ($accionPost === 'desbanear') {
            $pdo->prepare("UPDATE Usuario SET Rol='cliente' WHERE IdUsuario=?")->execute([$idTarget]);
            $mensaje = 'Usuario desbaneado. Rol restaurado a cliente.';
        }
    } else {
        $error = 'No puedes modificar tu propia cuenta';
    }
}

// Filtros
$busqueda = limpiar($_GET['buscar'] ?? '');
$filtroRol = $_GET['rol'] ?? '';
$valores = [];
$sql = "SELECT IdUsuario,NombreUsuario,Correo,Rol,Fecha_registro,Verificado FROM Usuario WHERE 1=1";
if ($busqueda) {
    $sql .= " AND (NombreUsuario ILIKE ? OR Correo ILIKE ?)";
    $valores[] = "%$busqueda%"; $valores[] = "%$busqueda%";
}
if ($filtroRol) {
    $sql .= " AND Rol=?"; $valores[] = $filtroRol;
}
$sql .= " ORDER BY Fecha_registro DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($valores); $usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios – Administrador Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/administrador.css">
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>

<main class="adminMain">
    <div class="adminHeader">
        <h1>Gestión de Usuarios</h1>
        <span class="adminCount"><?= count($usuarios) ?>usuarios</span>
    </div>

    <?php if ($mensaje):   ?><div class="adminAlert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="adminAlert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- fIltros -->
    <form method="GET" class="adminFiltros">
        <input type="text" name="buscar" placeholder="Buscar usuario o correo..." value="<?= htmlspecialchars($busqueda) ?>" class="adminInput" style="max-width:260px;">
        <select name="rol" class="adminInput" style="max-width:160px;">
            <option value="">Todos los roles</option>
            <option value="cliente"  <?= $filtroRol==='cliente' ?'selected':'' ?>>Cliente</option>
            <option value="soporte"  <?= $filtroRol==='soporte' ?'selected':'' ?>>Soporte</option>
            <option value="admin"    <?= $filtroRol==='admin' ?'selected':'' ?>>Admin</option>
            <option value="baneado"  <?= $filtroRol==='baneado' ?'selected':'' ?>>Baneados</option>
        </select>
        <button type="submit" class="btnAdminPrimario">Filtrar</button>
        <?php if ($busqueda || $filtroRol): ?>
        <a href="usuarios.php" class="btnAdminOutline">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="adminTablaWrap">
        <table class="adminTabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Registrado</th>
                    <th>Verificado</th>
                    <?php if ($_SESSION['usuario']['rol']==='admin'): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--text-dim);">Sin usuarios encontrados.</td></tr>
                <?php else: foreach ($usuarios as $u): ?>
                <tr style="<?= $u['rol']==='baneado'?'opacity:0.55':'' ?>">
                    <td><?= $u['idusuario'] ?></td>
                    <td style="font-weight:500;"><?= htmlspecialchars($u['nombreusuario']) ?></td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($u['correo']) ?></td>
                    <td>
                        <span class="rolBadge rol-<?= $u['rol'] ?>">
                            <?= $u['rol']==='baneado' ? 'Baneado' : ucfirst($u['rol']) ?>
                        </span>
                    </td>
                    <td style="font-size:12px;"><?= $u['fecha_registro'] ?></td>
                    <td><?= $u['verificado'] ? '✅' : '❌' ?></td>
                    <?php if ($_SESSION['usuario']['rol']==='admin'): ?>
                    <td>
                        <?php if ($u['idusuario'] != $_SESSION['usuario']['idusuario']): ?>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                            <?php if ($u['rol'] !== 'baneado'): ?>
                            <!-- Cambiar rol -->
                            <form method="POST" style="display:flex;gap:4px;align-items:center;">
                                <input type="hidden" name="accion_usuario" value="cambiar_rol">
                                <input type="hidden" name="id_usuario" value="<?= $u['idusuario'] ?>">
                                <select name="nuevo_rol" class="adminInputSm">
                                    <option value="cliente" <?= $u['rol']==='cliente'?'selected':'' ?>>Cliente</option>
                                    <option value="soporte" <?= $u['rol']==='soporte'?'selected':'' ?>>Soporte</option>
                                    <option value="admin"   <?= $u['rol']==='admin' ?'selected':'' ?>>Admin</option>
                                </select>
                                <button type="submit" class="btnAdminSm">Guardar</button>
                            </form>
                            <!-- Banear -->
                            <form method="POST" onsubmit="return confirm('¿Banear a <?= htmlspecialchars($u['nombreusuario']) ?>? Ya no podrá iniciar sesión.')">
                                <input type="hidden" name="accion_usuario" value="banear">
                                <input type="hidden" name="id_usuario" value="<?= $u['idusuario'] ?>">
                                <button type="submit" style="background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.3);padding:5px 10px;border-radius:6px;font-size:12px;cursor:pointer;">
                                    Banear
                                </button>
                            </form>
                            <?php else: ?>
                            <!--Desbanear -->
                            <form method="POST" onsubmit="return confirm('¿Desbanear a este usuario?')">
                                <input type="hidden" name="accion_usuario" value="desbanear">
                                <input type="hidden" name="id_usuario" value="<?= $u['idusuario'] ?>">
                                <button type="submit" style="background:rgba(76,175,130,0.15);color:#4caf82;border:1px solid rgba(76,175,130,0.3);padding:5px 10px;border-radius:6px;font-size:12px;cursor:pointer;">
                                    Desbanear
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="font-size:12px;color:var(--text-dim);">Tu cuenta</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

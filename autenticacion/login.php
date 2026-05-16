<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';

$base = '/squareenix';
if (isset($_SESSION['usuario'])) { header("Location: $base/index.php"); exit; }

$error = ''; $exito = ''; $tab = $_GET['tab'] ?? 'login';
$mostrar_verificacion = false; $correo_verificar = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

 // Registro del cliente
    if ($accion === 'registro') {
        $nombreUsu = limpiar($_POST['nombre_usuario'] ?? '');
        $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $tab = 'registro';

        if (!$nombreUsu || !$correo || !$password) { $error = 'Todos los campos son obligatorios.'; }
        elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) { $error = 'Correo inválido.'; }
        elseif (strlen($password) < 6) { $error = 'Mínimo 6 caracteres en la contraseña.'; }
        else {
            $s = $pdo->prepare("SELECT 1 FROM Usuario WHERE NombreUsuario=? OR Correo=?");
            $s->execute([$nombreUsu, $correo]);
            if ($s->fetch()) { $error = 'El usuario o correo ya está registrado.'; }
            else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $codigo = generarCodigo();
                $pdo->prepare("INSERT INTO Usuario (NombreUsuario,Correo,ContraseñaHash,Rol,Nombre,CodigoVerificacion,Verificado) VALUES (?,?,?,'cliente',?,?,FALSE)")
                    ->execute([$nombreUsu, $correo, $hash, $nombreUsu, $codigo]);
                enviarCodigoVerificacion($correo, $codigo);
                $mostrar_verificacion = true; $correo_verificar = $correo;
                $exito = 'Cuenta creada. Revisa tu correo e ingresa el código.'; $tab = 'registro';
            }
        }
    }

 // Registro del administrador, con codigo
    if ($accion === 'registro_admin') {
        $nombreUsu = trim($_POST['nombre_usuario'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $nombre = limpiar($_POST['nombre'] ?? '');
        $codigo_adm = trim($_POST['codigo_admin'] ?? '');
        $tab = 'admin';

        $CODIGO_SECRETO = 'SQEX2024ADMIN';

        if ($codigo_adm !== $CODIGO_SECRETO) { $error = 'Código de administrador incorrecto.'; }
        elseif (!$nombreUsu || !$correo || !$password || !$nombre) { $error = 'Completa todos los campos.'; }
        else {
            $s = $pdo->prepare("SELECT 1 FROM Usuario WHERE NombreUsuario=? OR Correo=?");
            $s->execute([$nombreUsu, $correo]);
            if ($s->fetch()) { $error = 'El usuario o correo ya existe.'; }
            else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO Usuario (NombreUsuario,Correo,ContraseñaHash,Rol,Nombre,Verificado) VALUES (?,?,?,'admin',?,TRUE)")
                    ->execute([$nombreUsu, $correo, $hash, $nombre]);
                $exito = "¡Cuenta de administrador \"$nombreUsu\" creada ya puedes iniciar sesion"; $tab = 'login';
            }
        }
    }

 // Verificar el codigo
    if ($accion === 'verificar') {
        $correo = trim($_POST['correo_ver'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $s = $pdo->prepare("SELECT IdUsuario,CodigoVerificacion FROM Usuario WHERE Correo=? AND Verificado=FALSE");
        $s->execute([$correo]); $u = $s->fetch();
        if (!$u) { $error = 'Correo no encontrado o ya verificado.'; $tab = 'registro'; }
        elseif ($u['codigoverificacion'] !== $codigo) {
            $error = 'Código incorrecto.'; $mostrar_verificacion = true; $correo_verificar = $correo; $tab = 'registro';
        } else {
            $pdo->prepare("UPDATE Usuario SET Verificado=TRUE,CodigoVerificacion=NULL WHERE Correo=?")->execute([$correo]);
            $exito = '¡Cuenta verificada! Ya puedes iniciar sesión.'; $tab = 'login';
        }
    }

 // Login
    if ($accion === 'login') {
        $entrada = limpiar($_POST['nombre_usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $s = $pdo->prepare("SELECT * FROM Usuario WHERE NombreUsuario=? OR Correo=?");
        $s->execute([$entrada, $entrada]); $u = $s->fetch();
        if (!$u || !password_verify($password, $u['contraseñahash'])) {
            $error = 'Usuario/correo o contraseña incorrectos.';
        } elseif ($u['rol'] === 'baneado') {
            $error = 'Tu cuenta ha sido suspendida. Contacta al soporte.';
        } elseif (!$u['verificado'] && !in_array($u['rol'], ['admin','soporte'])) {
            $error = 'Verifica tu correo primero.';
            $mostrar_verificacion = true; $correo_verificar = $u['correo']; $tab = 'registro';
        } else {
            $_SESSION['usuario'] = [
                'idusuario' => $u['idusuario'],
                'nombreusuario' => $u['nombreusuario'],
                'correo' => $u['correo'],
                'rol' => $u['rol'],
                'imagenurl' => $u['imagenurl'] ?? null,
                'nombre' => $u['nombre'] ?? $u['nombreusuario'],
            ];
            header("Location: " . (in_array($u['rol'],['admin','soporte']) ? "$base/administrador/index.php" : "$base/index.php"));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
</head>
<body class="auth-page">
<div class="auth-box" style="width:460px;">
    <div class="auth-logo">SQUARE ENIX</div>
    <p style="text-align:center;font-size:12px;color:var(--text-dim);margin-bottom:20px;">Tienda oficial de videojuegos</p>

    <div class="auth-tabs">
        <div class="auth-tab <?= $tab==='login' ?'active':'' ?>" onclick="switchTab('login')">Iniciar sesion</div>
        <div class="auth-tab <?= $tab==='registro'?'active':'' ?>" onclick="switchTab('registro')">Registrarse</div>
        <div class="auth-tab <?= $tab==='admin' ?'active':'' ?>" onclick="switchTab('admin')" title="Crear cuenta admin">Admin</div>
    </div>

    <?php if ($error): ?><div class="form-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="form-alert success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>

<!-- Panel para el inicio -->
    <div id="panel-login" style="display:<?= $tab==='login'?'block':'none' ?>">
        <form method="POST">
            <input type="hidden" name="accion" value="login">
            <div class="formGrupo"><label>Usuario o correo</label>
                <input type="text" name="nombre_usuario" class="form-control" required autofocus></div>
            <div class="formGrupo"><label>Contraseña</label>
                <input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn-primary btn-block" style="margin-top:6px;">Entrar</button>
        </form>
    </div>

<!-- Panel del registro de cliente -->
    <div id="panel-registro" style="display:<?= $tab==='registro'?'block':'none' ?>">
        <?php if (!$mostrar_verificacion): ?>
        <form method="POST">
            <input type="hidden" name="accion" value="registro">
            <div class="formGrupo"><label>Nombre de usuario</label>
                <input type="text" name="nombre_usuario" class="form-control" required></div>
            <div class="formGrupo"><label>Correo electronico</label>
                <input type="email" name="correo" class="form-control" required></div>
            <div class="formGrupo"><label>Contraseña mínimo 6 caracteres</label>
                <input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn-primary btn-block" style="margin-top:6px;">Crear cuenta</button>
        </form>
        <?php else: ?>
        <div class="form-alert info">Codigo enviado a <strong><?= htmlspecialchars($correo_verificar) ?></strong></div>
        <form method="POST">
            <input type="hidden" name="accion" value="verificar">
            <input type="hidden" name="correo_ver" value="<?= htmlspecialchars($correo_verificar) ?>">
            <div class="formGrupo"><label>Codigo de 6 dígitos</label>
                <input type="text" name="codigo" class="form-control" maxlength="6"
                       style="letter-spacing:8px;font-size:20px;text-align:center;" required></div>
            <button type="submit" class="btn-primary btn-block">Verificar</button>
        </form>
        <?php endif; ?>
    </div>

<!-- Panel de registro del buen admin -->
    <div id="panel-admin" style="display:<?= $tab==='admin'?'block':'none' ?>">
        <div class="form-alert info" style="margin-bottom:14px;">
            Necesitas el <strong>codigo secreto de administrador</strong> para crear esta cuenta.
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="registro_admin">
            <div class="formGrupo"><label>Nombre completo</label>
                <input type="text" name="nombre" class="form-control" required></div>
            <div class="formGrupo"><label>Nombre de usuario</label>
                <input type="text" name="nombre_usuario" class="form-control" required></div>
            <div class="formGrupo"><label>Correo electronico</label>
                <input type="email" name="correo" class="form-control" required></div>
            <div class="formGrupo"><label>Contraseña</label>
                <input type="password" name="password" class="form-control" required></div>
            <div class="formGrupo"><label>Codigo secreto de administrador</label>
                <input type="password" name="codigo_admin" class="form-control" required placeholder="Código proporcionado por el sistema"></div>
            <button type="submit" class="btn-primary btn-block" style="margin-top:6px;">Crear administrador</button>
        </form>
        <p style="font-size:11px;color:var(--text-dim);margin-top:10px;text-align:center;">
            El código actual es <strong>*************</strong>
        </p>
    </div>

    <p style="text-align:center;font-size:12px;color:var(--text-dim);margin-top:20px;">
        <a href="<?= $base ?>/index.php" style="color:var(--accent);">← Volver a la tienda</a>
    </p>
</div>
<script>
function switchTab(tab) {
    ['login','registro','admin'].forEach(t => {
        document.getElementById('panel-'+t).style.display = t===tab?'block':'none';
        document.querySelectorAll('.auth-tab').forEach((el,i) => {
            el.classList.toggle('active', ['login','registro','admin'][i]===tab);
        });
    });
}
</script>
</body>
</html>

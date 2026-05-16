<?php

session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /squareenix/autenticacion/login.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apePat = trim($_POST['ape_pat'] ?? '');
    $apeMat = trim($_POST['ape_mat'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'soporte';

    if (!$nombre || !$apePat || !$usuario || !$correo || !$password) {
        $error = 'Todos los campos obligatorios deben llenarse.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico invalido.';
    } elseif (!in_array($rol, ['admin', 'soporte'])) {
        $error = 'Rol no valido.';
    } else {
        $stmt = $pdo->prepare("SELECT IdUsuario FROM Usuario WHERE NombreUsuario = ? OR Correo = ?");
        $stmt->execute([$usuario, $correo]);
        if ($stmt->fetch()) {
            $error = 'El nombre de usuario o correo ya esta registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $codigo = generarCodigo();

            $pdo->prepare("
                INSERT INTO Usuario (NombreUsuario, Correo, ContraseñaHash, Rol, Nombre, ApePat, ApeMat, CodigoVerificacion, Verificado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ")->execute([$usuario, $correo, $hash, $rol, $nombre, $apePat, $apeMat ?: null, $codigo]);

            enviarCodigoVerificacion($correo, $password); 

            $exito = "Cuenta de $rol creada exitosamente para $usuario.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Administrador de Square Enix</title>
    <link rel="stylesheet" href="/squareenix/recursos/css/estilo.css">
    <link rel="stylesheet" href="/squareenix/recursos/css/administrador.css">
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>

<main class="adminMain">
    <div class="adminHeader">
        <h1>Crear Cuenta Administrativa</h1>
    </div>

    <?php if ($error): ?>
        <div class="adminAlert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($exito): ?>
        <div class="adminAlert success"><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <div class="adminFormCard">
        <form method="POST">
            <div class="formGrid">
                <div class="formGrupo">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="adminInput" required
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Apellido Paterno *</label>
                    <input type="text" name="ape_pat" class="adminInput" required
                           value="<?= htmlspecialchars($_POST['ape_pat'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Apellido Materno</label>
                    <input type="text" name="ape_mat" class="adminInput"
                           value="<?= htmlspecialchars($_POST['ape_mat'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Nombre de usuario</label>
                    <input type="text" name="usuario" class="adminInput" required
                           value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo" class="adminInput" required
                           value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="adminInput" required>
                </div>
                <div class="formGrupo">
                    <label>Rol</label>
                    <select name="rol" class="adminInput">
                        <option value="soporte">Soporte Tecnico</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btnAdminPrimario">Crear cuenta</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>

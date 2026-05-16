<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
$base = '/squareenix';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['admin','soporte'])) {
    header("Location: $base/autenticacion/login.php"); exit;
}

$mensaje = ''; $error = '';

// Procesar el formulari
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $estado = $_POST['estado'] ?? 'activo';
    $desarrollador = trim($_POST['desarrollador'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $fecha = $_POST['fecha_lanzamiento'] ?? null;
    $categorias = $_POST['categorias'] ?? [];
    if ($accion === 'eliminar') {
        $jid = entero($_POST['juego_id'] ?? 0, 1);
        if ($jid) {
            $pdo->prepare("UPDATE VideoJuegos SET Estado='descontinuado' WHERE IdVideoJuego=?")->execute([$jid]);
            $mensaje = 'Juego descontinuado del catálogo.';
        }
    }
 // Ahora si agregar si requiere título y precio
    elseif ($accion === 'agregar') {
        if (!$titulo || $precio <= 0) {
            $error = 'El título y el precio son obligatorios para agregar un juego.';
        } else {
        $s = $pdo->prepare("INSERT INTO VideoJuegos (Titulo,Descripcion,Precio,FechaLanzamiento,Desarrollador,Estado,Peso)
                            VALUES (?,?,?,?,?,?,?) RETURNING IdVideoJuego");
        $s->execute([$titulo, $descripcion, $precio, $fecha ?: null, $desarrollador, $estado, $peso]);
        $jid = $s->fetchColumn();
        if (!empty($_FILES['img_logo']['tmp_name']) && $_FILES['img_logo']['error'] === 0) {
            $urlLogo = subirArchivoLocal($_FILES['img_logo']['tmp_name'], $_FILES['img_logo']['name'], 'juegos');
            if ($urlLogo) $pdo->prepare("INSERT INTO Imagenes (IdVideoJuego,UrlImagen) VALUES (?,?)")->execute([$jid, $urlLogo]);
        }
        if (!empty($_FILES['img_capturas']['tmp_name'])) {
            $archivos = $_FILES['img_capturas'];
            $total = count($archivos['tmp_name']);
            $subidas = 0;
            for ($i = 0; $i < $total && $subidas < 5; $i++) {
                if ($archivos['error'][$i] !== 0) continue;
                $url = subirArchivoLocal($archivos['tmp_name'][$i], $archivos['name'][$i], 'juegos');
                if ($url) {
                    $pdo->prepare("INSERT INTO Imagenes (IdVideoJuego,UrlImagen) VALUES (?,?)")->execute([$jid, $url]);
                    $subidas++;
                }
            }
        }

// Instalador del juego
        if (!empty($_FILES['instalador_archivo']['tmp_name']) && $_FILES['instalador_archivo']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['instalador_archivo']['name'], PATHINFO_EXTENSION));
            $rutaExe = __DIR__ . '/../recursos/subidas/instaladores/' . $jid . '.' . $ext;
            move_uploaded_file($_FILES['instalador_archivo']['tmp_name'], $rutaExe);
        }

 // Categorias
        foreach ($categorias as $nomCat) {
            $s = $pdo->prepare("SELECT IdCategoria FROM Categoria WHERE NombreCategoria=?");
            $s->execute([trim($nomCat)]); $cat = $s->fetch();
            if (!$cat) {
                $s2 = $pdo->prepare("INSERT INTO Categoria (NombreCategoria) VALUES (?) RETURNING IdCategoria");
                $s2->execute([trim($nomCat)]); $catId = $s2->fetchColumn();
            } else { $catId = $cat['idcategoria']; }
            $pdo->prepare("INSERT INTO CategoriaVideoJuego (IdCategoria,IdVideoJuego) VALUES (?,?) ON CONFLICT DO NOTHING")
                ->execute([$catId, $jid]);
        }

        $mensaje = "Juego \"$titulo\" agregado correctamente.";

        }
    }
}

// Lista de juegos
$juegos = $pdo->query("
    SELECT v.IdVideoJuego, v.Titulo, v.Precio, v.Estado, v.Desarrollador,
           (SELECT UrlImagen FROM Imagenes WHERE IdVideoJuego=v.IdVideoJuego ORDER BY IdImagen LIMIT 1) AS Imagen
    FROM VideoJuegos v ORDER BY v.IdVideoJuego DESC
")->fetchAll();

$cats_all = $pdo->query("SELECT NombreCategoria FROM Categoria ORDER BY NombreCategoria")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Videojuegos – Admin Square Enix</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/administrador.css">
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>

<main class="adminMain">
    <div class="adminHeader">
        <h1>Videojuegos</h1>
        <button class="btnAdminPrimario" onclick="document.getElementById('frmAgregar').style.display = document.getElementById('frmAgregar').style.display==='none'?'block':'none'">
            + Agregar juego
        </button>
    </div>

    <?php if ($mensaje):   ?><div class="adminAlert success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="adminAlert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

// Formulario para agregar
    <div id="frmAgregar" class="adminFormCard" style="display:none;margin-bottom:24px;">
        <h3 style="margin-bottom:18px;">Agregar nuevo juego</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="agregar">
            <div class="formGrid">

                <div class="formGrupo">
                    <label>Título</label>
                    <input type="text" name="titulo" class="adminInput" required>
                </div>

                <div class="formGrupo">
                    <label>Precio (MXN)</label>
                    <input type="number" name="precio" step="0.01" class="adminInput" required>
                </div>

                <div class="formGrupo formCompleto">
                    <label>Descripcion</label>
                    <textarea name="descripcion" class="adminInput" rows="3"></textarea>
                </div>

                <div class="formGrupo">
                    <label>Desarrollador</label>
                    <input type="text" name="desarrollador" class="adminInput">
                </div>

                <div class="formGrupo">
                    <label>Fecha de lanzamiento</label>
                    <input type="date" name="fecha_lanzamiento" class="adminInput">
                </div>

                <div class="formGrupo">
                    <label>Peso en GB</label>
                    <input type="text" name="peso" class="adminInput">
                </div>

                <div class="formGrupo">
                    <label>Estado</label>
                    <select name="estado" class="adminInput">
                        <option value="activo">Activo</option>
                        <option value="proximamente">Proximamente</option>
                    </select>
                </div>

// Imagenes
                <div class="formGrupo" style="border:1px solid var(--border);border-radius:8px;padding:14px;background:rgba(0,188,212,0.04);">
                    <label style="color:var(--accent);font-weight:600;">Logo / Portada <span style="font-size:11px;color:var(--text-dim);font-weight:400;">1 imagen — aparece en listado y en el panel derecho</span></label>
                    <input type="file" name="img_logo" accept="image/*" class="adminInput" style="margin-top:8px;">
                    <small style="color:var(--text-dim);">Sube el logo o imagen de portada del juego, esta imagen aparecerá como miniatura en toda la tienda.</small>
                </div>

                <div class="formGrupo" style="border:1px solid var(--border);border-radius:8px;padding:14px;background:rgba(0,188,212,0.04);">
                    <label style="color:var(--accent);font-weight:600;">Capturas de pantalla <span style="font-size:11px;color:var(--text-dim);font-weight:400;">maximo 5 aparecen en la galería del juego</span></label>
                    <input type="file" name="img_capturas[]" accept="image/*" multiple class="adminInput" style="margin-top:8px;"
                           onchange="validarCapturas(this)">
                    <small style="color:var(--text-dim);">Selecciona hasta 5 capturas de pantalla del juego</small>
                    <div id="msgCapturas" style="font-size:12px;margin-top:4px;"></div>
                </div>

                <div class="formGrupo">
                    <label>Instalador .exe</label>
                    <input type="file" name="instalador_archivo" accept=".exe,.zip" class="adminInput">
                    <small style="color:var(--text-dim);">Archivo que el usuario descargará al hacer clic en Instalar.</small>
                </div>

//Categorias
                <div class="formGrupo formCompleto">
                    <label>Categorías</label>
                    <div class="categoriasCheckGrid">
                        <?php foreach ($cats_all as $cat): ?>
                        <label class="checkLabel">
                            <input type="checkbox" name="categorias[]" value="<?= htmlspecialchars($cat) ?>">
                            <?= htmlspecialchars($cat) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
            <button type="submit" class="btnAdminPrimario" style="margin-top:16px;">Guardar juego</button>
        </form>
    </div>

//Tabla de los videojuegos
    <div class="adminTablaWrap">
        <table class="adminTabla">
            <thead>
                <tr><th>Logo</th><th>Título</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($juegos as $j): ?>
                <tr>
                    <td>
                        <img src="<?= htmlspecialchars($j['imagen'] ?? $base.'/recursos/imagenes/placeholder.svg') ?>"
                             style="width:54px;height:34px;object-fit:contain;border-radius:4px;background:var(--bg-secondary);">
                    </td>
                    <td style="font-weight:500;"><?= htmlspecialchars($j['titulo']) ?></td>
                    <td>$<?= number_format($j['precio'],2) ?></td>
                    <td>
                        <span class="badge badge-<?= $j['estado']==='activo'?'success':($j['estado']==='proximamente'?'warning':'danger') ?>">
                            <?= ucfirst($j['estado']) ?>
                        </span>
                    </td>
                    <td style="display:flex;gap:8px;">
                        <a href="<?= $base ?>/administrador/editar_juego.php?id=<?= $j['idvideojuego'] ?>" class="btnAdminSm">Editar</a>
                        <form method="POST" onsubmit="return confirm('¿Descontinuar este juego?')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="juego_id" value="<?= $j['idvideojuego'] ?>">
                            <button type="submit" class="btnAdminPeligro">Quitar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($juegos)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-dim);">Sin juegos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function validarCapturas(input) {
    const msg = document.getElementById('msgCapturas');
    if (input.files.length > 5) {
        msg.textContent = '⚠ Solo se permiten máximo 5 capturas. Se tomarán las primeras 5.';
        msg.style.color = 'var(--warning)';
    } else {
        msg.textContent = `✓ ${input.files.length} captura(s) seleccionada(s).`;
        msg.style.color = 'var(--success)';
    }
}
</script>
</body>
</html>

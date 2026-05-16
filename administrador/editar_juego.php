<?php
session_start();
require_once '../configuracion/bd.php';
$pdo = bd();
require_once '../configuracion/funciones.php';
$base = '/squareenix';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['admin','soporte'])) {
    header("Location: $base/autenticacion/login.php"); exit;
}

$idJuego = (int)($_GET['id'] ?? 0);
if (!$idJuego) { header("Location: juegos.php"); exit; }

// Cargar los datos
function cargarJuego($pdo, $id) {
    $s = $pdo->prepare("SELECT * FROM VideoJuegos WHERE IdVideoJuego=?");
    $s->execute([$id]); return $s->fetch();
}
function cargarImagenes($pdo, $id) {
    $s = $pdo->prepare("SELECT IdImagen, UrlImagen FROM Imagenes WHERE IdVideoJuego=? ORDER BY IdImagen");
    $s->execute([$id]); return $s->fetchAll();
}
function cargarCatsJuego($pdo, $id) {
    $s = $pdo->prepare("SELECT IdCategoria FROM CategoriaVideoJuego WHERE IdVideoJuego=?");
    $s->execute([$id]); return array_column($s->fetchAll(), 'idcategoria');
}

$juego = cargarJuego($pdo, $idJuego);
if (!$juego) { header("Location: juegos.php"); exit; }

$todasImagenes = cargarImagenes($pdo, $idJuego);
$imgLogo = !empty($todasImagenes) ? $todasImagenes[0] : null;
$imgCapturas = array_slice($todasImagenes, 1);
$catsJuego = cargarCatsJuego($pdo, $idJuego);
$todasCats = $pdo->query("SELECT * FROM Categoria ORDER BY NombreCategoria")->fetchAll();

$error = ''; $exito = '';
$accion = $_POST['accion'] ?? '';

//Para poder eliminar las imagenes
if (isset($_GET['eliminar_img'])) {
    $idImg = entero($_GET['eliminar_img'] ?? 0, 1);
    $s = $pdo->prepare("SELECT UrlImagen FROM Imagenes WHERE IdImagen=? AND IdVideoJuego=?");
    $s->execute([$idImg, $idJuego]); $fila = $s->fetch();
    if ($fila) {
        $rutaFisica = str_replace('/', DIRECTORY_SEPARATOR,
            $_SERVER['DOCUMENT_ROOT'] . str_replace('/squareenix', '', $fila['urlimagen']));
        if (file_exists($rutaFisica)) @unlink($rutaFisica);
        $pdo->prepare("DELETE FROM Imagenes WHERE IdImagen=?")->execute([$idImg]);
    }
    header("Location: editar_juego.php?id=$idJuego&ok=eliminada"); exit;
}

// Guardar los datos de los juegos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar_datos') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $fecha = $_POST['fecha_lanzamiento'] ?? null;
    $desarrollador = trim($_POST['desarrollador'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';
    $peso = trim($_POST['peso'] ?? '');
    $categorias = $_POST['categorias'] ?? [];
    $campos = []; $valores = [];
    if ($titulo !== '') { $campos[] = 'Titulo=?'; $valores[] = $titulo; }
    if ($descripcion !== '') { $campos[] = 'Descripcion=?'; $valores[] = $descripcion; }
    if ($precio > 0) { $campos[] = 'Precio=?'; $valores[] = $precio; }
    if (!empty($fecha)) { $campos[] = 'FechaLanzamiento=?'; $valores[] = $fecha; }
    if ($desarrollador !== '') { $campos[] = 'Desarrollador=?'; $valores[] = $desarrollador; }
    if (in_array($estado, ['activo','proximamente','descontinuado'])) {
        $campos[] = 'Estado=?'; $valores[] = $estado;
    }
    if ($peso !== '') { $campos[] = 'Peso=?'; $valores[] = $peso; }

    if (!empty($campos)) {
        $valores[] = $idJuego;
        $sql = "UPDATE VideoJuegos SET " . implode(',', $campos) . " WHERE IdVideoJuego=?";
        $pdo->prepare($sql)->execute($valores);
    }

        $pdo->prepare("DELETE FROM CategoriaVideoJuego WHERE IdVideoJuego=?")->execute([$idJuego]);
        foreach ($categorias as $cid) {
            $pdo->prepare("INSERT INTO CategoriaVideoJuego (IdCategoria,IdVideoJuego) VALUES (?,?) ON CONFLICT DO NOTHING")
                ->execute([(int)$cid, $idJuego]);
        }

        if (!empty($_FILES['instalador_archivo']['tmp_name']) && $_FILES['instalador_archivo']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['instalador_archivo']['name'], PATHINFO_EXTENSION));
            $rutaExe = __DIR__ . '/../recursos/subidas/instaladores/' . $idJuego . '.' . $ext;
            move_uploaded_file($_FILES['instalador_archivo']['tmp_name'], $rutaExe);
        }

    $exito = '✅ Datos del juego actualizados.';
    $juego = cargarJuego($pdo, $idJuego);
    $catsJuego = cargarCatsJuego($pdo, $idJuego);
}

// Para reemplazar el logo del juego
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'subir_logo') {
    if (empty($_FILES['img_logo']['tmp_name']) || $_FILES['img_logo']['error'] !== 0) {
        $error = 'No se recibió ninguna imagen de logo.';
    } else {
        if ($imgLogo) {
            $s = $pdo->prepare("SELECT UrlImagen FROM Imagenes WHERE IdImagen=?");
            $s->execute([$imgLogo['idimagen']]); $f = $s->fetch();
            if ($f) {
                $ruta = str_replace('/', DIRECTORY_SEPARATOR,
                    $_SERVER['DOCUMENT_ROOT'] . str_replace('/squareenix','',$f['urlimagen']));
                if (file_exists($ruta)) @unlink($ruta);
                $pdo->prepare("DELETE FROM Imagenes WHERE IdImagen=?")->execute([$imgLogo['idimagen']]);
            }
        }
        $urlsCapturas = array_column($imgCapturas, 'urlimagen');
        $pdo->prepare("DELETE FROM Imagenes WHERE IdVideoJuego=?")->execute([$idJuego]);

        $urlLogo = subirArchivoLocal($_FILES['img_logo']['tmp_name'], $_FILES['img_logo']['name'], 'juegos');
        if ($urlLogo) {
            $pdo->prepare("INSERT INTO Imagenes (IdVideoJuego,UrlImagen) VALUES (?,?)")->execute([$idJuego, $urlLogo]);
            foreach ($urlsCapturas as $urlCap) {
                $pdo->prepare("INSERT INTO Imagenes (IdVideoJuego,UrlImagen) VALUES (?,?)")->execute([$idJuego, $urlCap]);
            }
            $exito = 'Logo actualizado correctamente.';
        } else {
            $error = 'Error al querer guardar el archivo verifica que la carpeta este disponible.';
        }

        $todasImagenes = cargarImagenes($pdo, $idJuego);
        $imgLogo = !empty($todasImagenes) ? $todasImagenes[0] : null;
        $imgCapturas = array_slice($todasImagenes, 1);
    }
}

// Para poder subir las capturas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'subir_capturas') {
    $capturasActuales = count($imgCapturas);
    $disponibles = 5 - $capturasActuales;

    if ($disponibles <= 0) {
        $error = 'Ya tienes 5 capturas. Elimina alguna antes de agregar nuevas.';
    } elseif (empty($_FILES['img_capturas']['tmp_name']) || !is_array($_FILES['img_capturas']['tmp_name'])) {
        $error = 'No se recibieron imágenes.';
    } else {
        $archivos = $_FILES['img_capturas'];
        $total = count($archivos['tmp_name']);
        $subidas = 0;

        for ($i = 0; $i < $total && $subidas < $disponibles; $i++) {
            if ($archivos['error'][$i] !== 0) continue;
            $url = subirArchivoLocal($archivos['tmp_name'][$i], $archivos['name'][$i], 'juegos');
            if ($url) {
                $pdo->prepare("INSERT INTO Imagenes (IdVideoJuego,UrlImagen) VALUES (?,?)")->execute([$idJuego, $url]);
                $subidas++;
            }
        }

        if ($subidas > 0) {
            $exito = "✅ $subidas captura(s) agregada(s) correctamente.";
        } else {
            $error = 'No se pudo guardar ninguna imagen. Verifica que la carpeta recursos/subidas/juegos/ existe.';
        }

        $todasImagenes = cargarImagenes($pdo, $idJuego);
        $imgLogo = !empty($todasImagenes) ? $todasImagenes[0] : null;
        $imgCapturas = array_slice($todasImagenes, 1);
    }
}

$capturaCount = count($imgCapturas);
$disponibles = 5 - $capturaCount;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar juego – Admin</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/administrador.css">
</head>
<body class="adminBody">
<?php include 'parciales/sidebar.php'; ?>

<main class="adminMain">
    <div class="adminHeader">
        <h1>Editar: <?= htmlspecialchars($juego['titulo']) ?></h1>
        <a href="juegos.php" class="btnAdminOutline">← Volver</a>
    </div>

    <?php if ($error):   ?><div class="adminAlert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="adminAlert success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>
    <?php if (isset($_GET['ok'])): ?>
        <div class="adminAlert success">
            <?= $_GET['ok']==='eliminada' ? 'Imagen eliminada.' : 'Operación exitosa.' ?>
        </div>
    <?php endif; ?>

<!-- Formulario para los datos del videojugeos -->
    <div class="adminFormCard" style="margin-bottom:16px;">
        <h3 style="margin-bottom:16px;">Datos del juego</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar_datos">
            <div class="formGrid">
                <div class="formGrupo">
                    <label>Título</label>
                    <input type="text" name="titulo" class="adminInput" required value="<?= htmlspecialchars($juego['titulo']) ?>">
                </div>
                <div class="formGrupo">
                    <label>Precio (MXN)</label>
                    <input type="number" name="precio" step="0.01" min="0" class="adminInput" required value="<?= $juego['precio'] ?>">
                </div>
                <div class="formGrupo formCompleto">
                    <label>Descripcion</label>
                    <textarea name="descripcion" class="adminInput" rows="4"><?= htmlspecialchars($juego['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="formGrupo">
                    <label>Desarrollador</label>
                    <input type="text" name="desarrollador" class="adminInput" value="<?= htmlspecialchars($juego['desarrollador'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Fecha de lanzamiento</label>
                    <input type="date" name="fecha_lanzamiento" class="adminInput" value="<?= $juego['fechalanzamiento'] ?? '' ?>">
                </div>
                <div class="formGrupo">
                    <label>Peso GB</label>
                    <input type="text" name="peso" class="adminInput" value="<?= htmlspecialchars($juego['peso'] ?? '') ?>">
                </div>
                <div class="formGrupo">
                    <label>Estado</label>
                    <select name="estado" class="adminInput">
                        <option value="activo"        <?= ($juego['estado']??'')==='activo' ?'selected':'' ?>>Activo</option>
                        <option value="proximamente"  <?= ($juego['estado']??'')==='proximamente' ?'selected':'' ?>>Proximamente</option>
                        <option value="descontinuado" <?= ($juego['estado']??'')==='descontinuado'?'selected':'' ?>>Descontinuado</option>
                    </select>
                </div>
                <div class="formGrupo">
                    <label>Instalador</label>
                    <input type="file" name="instalador_archivo" accept=".exe,.zip" class="adminInput">
                    <?php $exeLocal = glob(__DIR__ . '/../recursos/subidas/instaladores/' . $idJuego . '.*'); ?>
                    <?php if (!empty($exeLocal)): ?>
                    <small style="color:var(--success);display:block;margin-top:4px;">✅ Ya existe: <?= basename($exeLocal[0]) ?></small>
                    <?php endif; ?>
                </div>
                <div class="formGrupo formCompleto">
                    <label>Categorias</label>
                    <div class="categoriasCheckGrid">
                        <?php foreach ($todasCats as $cat): ?>
                        <label class="checkLabel">
                            <input type="checkbox" name="categorias[]" value="<?= $cat['idcategoria'] ?>"
                                   <?= in_array($cat['idcategoria'], $catsJuego) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($cat['nombrecategoria']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button type="submit" class="btnAdminPrimario" style="margin-top:20px;">Guardar datos</button>
        </form>
    </div>

<!-- Formulario para el logo -->
    <div class="adminFormCard" style="margin-bottom:16px;">
        <h3 style="margin-bottom:14px;">Logo
            <small style="font-weight:400;color:var(--text-dim);">— miniatura en listados y panel derecho del juego</small>
        </h3>
        <div style="display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap;">
            <div style="flex-shrink:0;">
                <?php if ($imgLogo): ?>
                <div style="position:relative;display:inline-block;">
                    <img src="<?= htmlspecialchars($imgLogo['urlimagen']) ?>"
                         style="width:180px;height:110px;object-fit:contain;border-radius:8px;
                                background:var(--bg-secondary);border:2px solid var(--accent);" alt="Logo actual">
                    <div style="font-size:11px;color:var(--accent);text-align:center;margin-top:4px;">Logo actual</div>
                    <a href="?id=<?= $idJuego ?>&eliminar_img=<?= $imgLogo['idimagen'] ?>"
                       onclick="return confirm('¿Eliminar el logo?')"
                       title="Eliminar logo"
                       style="position:absolute;top:-8px;right:-8px;background:#ef4444;color:#fff;
                              border-radius:50%;width:24px;height:24px;display:flex;align-items:center;
                              justify-content:center;font-size:13px;text-decoration:none;font-weight:700;">✕</a>
                </div>
                <?php else: ?>
                <div style="width:180px;height:110px;border:2px dashed var(--border-dim);border-radius:8px;
                            display:flex;flex-direction:column;align-items:center;justify-content:center;
                            color:var(--text-dim);font-size:12px;gap:6px;">
                    <span style="font-size:24px;">🖼</span>
                    Sin logo aún
                </div>
                <?php endif; ?>
            </div>

<!-- Ya para poder subir el logo -->
            <form method="POST" enctype="multipart/form-data" style="flex:1;min-width:220px;">
                <input type="hidden" name="accion" value="subir_logo">
                <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:8px;">
                    <?= $imgLogo ? '🔄 Reemplazar logo:' : '⬆ Subir logo:' ?>
                </label>
                <input type="file" name="img_logo" accept="image/*" class="adminInput" required>
                <small style="color:var(--text-dim);display:block;margin-top:6px;">
                    Imagen para el logo del videojuego
                </small>
                <button type="submit" class="btnAdminPrimario" style="margin-top:12px;">
                    <?= $imgLogo ? '🔄 Reemplazar logo' : '⬆ Subir logo' ?>
                </button>
            </form>
        </div>
    </div>

<!-- Formulario para las capturas -->
    <div class="adminFormCard">
        <h3 style="margin-bottom:14px;">Capturas de pantalla
            <small style="font-weight:400;color:var(--text-dim);">— galería grande del juego (<?= $capturaCount ?>/5)</small>
        </h3>

<!-- Capturas actuales -->
        <?php if (!empty($imgCapturas)): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-bottom:20px;">
            <?php foreach ($imgCapturas as $cap): ?>
            <div style="position:relative;">
                <img src="<?= htmlspecialchars($cap['urlimagen']) ?>"
                     style="width:100%;height:90px;object-fit:cover;border-radius:6px;
                            border:1px solid var(--border-dim);display:block;" alt="">
                <a href="?id=<?= $idJuego ?>&eliminar_img=<?= $cap['idimagen'] ?>"
                   onclick="return confirm('¿Eliminar esta captura?')"
                   style="position:absolute;top:-8px;right:-8px;background:#ef4444;color:#fff;
                          border-radius:50%;width:24px;height:24px;display:flex;align-items:center;
                          justify-content:center;font-size:13px;text-decoration:none;font-weight:700;">✕</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--text-dim);font-size:13px;margin-bottom:16px;">Sin capturas de pantalla aun</p>
        <?php endif; ?>

<!-- Para agregar capturas -->
        <?php if ($disponibles > 0): ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="subir_capturas">
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:8px;">
                Agregar capturas (puedes añadir hasta <strong><?= $disponibles ?></strong> más):
            </label>
            <input type="file" name="img_capturas[]" accept="image/*" multiple class="adminInput"
                   onchange="validarCaps(this, <?= $disponibles ?>)">
            <div id="msgCaps" style="font-size:12px;margin-top:6px;color:var(--text-dim);"></div>
            <small style="color:var(--text-dim);display:block;margin-top:4px;">
                Puedes seleccionar varias imagenes a la vez (máx. <?= $disponibles ?>).
            </small>
            <button type="submit" class="btnAdminPrimario" style="margin-top:12px;">⬆ Subir capturas</button>
        </form>
        <?php else: ?>
        <div class="form-alert info" style="margin:0;">
            Ya tienes 5 capturas, elimina alguna para poder agregar nuevas
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function validarCaps(input, max) {
    const msg = document.getElementById('msgCaps');
    if (input.files.length > max) {
        msg.textContent = `⚠ Solo se tomarán las primeras ${max} imagen(es).`;
        msg.style.color = 'var(--warning)';
    } else if (input.files.length > 0) {
        msg.textContent = `✓ ${input.files.length} imagen(es) lista(s) para subir.`;
        msg.style.color = 'var(--success)';
    }
}
</script>
</body>
</html>

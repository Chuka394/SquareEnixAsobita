<?php
session_start();
require_once 'configuracion/bd.php';
$base = '/squareenix';

$faqs = [
    ['p' => '¿Cómo compro un juego?',
     'r' => 'Selecciona el juego que quieras, presiona "Comprar ahora" y completa el pago con PayPal. El juego se añadirá automáticamente a tu biblioteca al finalizar la compra.'],
    ['p' => '¿Puedo comprar varios juegos a la vez?',
     'r' => 'Sí. Añade los juegos al carrito desde la página de cada juego, abre el carrito en el navbar superior y presiona "Comprar todo" para pagar todos en una sola transacción.'],
    ['p' => '¿Cómo descargo un juego que ya compré?',
     'r' => 'Ve a tu Biblioteca, selecciona el juego deseado de la lista lateral y presiona el botón "INSTALAR". Se descargará el instalador del juego con el nombre correspondiente.'],
    ['p' => '¿Qué métodos de pago aceptan?',
     'r' => 'Aceptamos pagos a través de PayPal y tarjetas de crédito/débito procesadas por PayPal. Todo se hace de forma segura en su plataforma.'],
    ['p' => '¿Puedo cancelar una compra?',
     'r' => 'Como esta es una tienda de proyecto escolar usando PayPal Sandbox, no se procesan pagos reales. Para devoluciones, contacta al soporte.'],
    ['p' => '¿Cómo publico contenido en la comunidad?',
     'r' => 'Necesitas tener el juego en tu biblioteca. Ve a tu biblioteca, selecciona un juego y presiona "Compartir con la comunidad". Debes incluir una imagen obligatoriamente.'],
    ['p' => '¿Cómo cambio mi foto de perfil?',
     'r' => 'Ve a tu perfil haciendo clic en tu nombre de usuario en la esquina superior derecha, luego pasa el mouse sobre tu avatar actual y selecciona una nueva imagen.'],
    ['p' => '¿Olvidé mi contraseña, qué hago?',
     'r' => 'Actualmente la recuperación de contraseña por correo está en desarrollo. Contacta al soporte si necesitas ayuda con tu cuenta.'],
    ['p' => '¿Cómo califico o reseño un juego?',
     'r' => 'Para reseñar un juego primero debes tenerlo en tu biblioteca. Luego entra a la página del juego y baja hasta "Añadir un comentario".'],
    ['p' => '¿Mi información personal está segura?',
     'r' => 'Sí. Las contraseñas se almacenan cifradas con bcrypt y todas las consultas a la base de datos usan parámetros preparados contra inyección SQL.'],
    ['p' => '¿Cómo se contacta al soporte?',
     'r' => 'Desde el menú "Navegar" del navbar selecciona "Soporte" o usa el enlace del pie de página. Llena el formulario y un administrador te responderá.'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Preguntas frecuentes – Square Enix Store</title>
    <link rel="stylesheet" href="<?= $base ?>/recursos/css/estilo.css">
    <style>
        .faq-item { background: var(--bg-card); border: 1px solid var(--border-dim); border-radius: 8px; margin-bottom: 10px; overflow: hidden; transition: border-color 0.2s; }
        .faq-item:hover { border-color: var(--accent); }
        .faq-question { padding: 14px 18px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 500; color: var(--text-white); font-size: 14px; }
        .faq-question::after { content: "+"; color: var(--accent); font-size: 22px; font-weight: 700; transition: transform 0.2s; }
        .faq-item.open .faq-question::after { transform: rotate(45deg); }
        .faq-answer { padding: 0 18px; max-height: 0; overflow: hidden; transition: max-height 0.3s, padding 0.3s; color: var(--text-muted); font-size: 13px; line-height: 1.7; }
        .faq-item.open .faq-answer { padding: 0 18px 16px; max-height: 300px; }
    </style>
</head>
<body>
<?php include 'vistas/parciales/navbar.php'; ?>

<div class="container" style="max-width:780px;padding:32px 16px 60px;">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:32px;color:var(--accent);margin-bottom:8px;">Preguntas frecuentes</h1>
    <p style="color:var(--text-muted);margin-bottom:24px;font-size:14px;">
        Encuentra respuestas a las dudas más comunes sobre nuestra plataforma.
    </p>

    <?php foreach ($faqs as $i => $faq): ?>
    <div class="faq-item" onclick="this.classList.toggle('open')">
        <div class="faq-question"><?= htmlspecialchars($faq['p']) ?></div>
        <div class="faq-answer"><?= htmlspecialchars($faq['r']) ?></div>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:30px;padding:20px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;text-align:center;">
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:10px;">¿No encuentras lo que buscas?</p>
        <a href="<?= $base ?>/soporte.php" class="btn-primary">Contactar al soporte</a>
    </div>
</div>

<?php include 'vistas/parciales/footer.php'; ?>
<script src="<?= $base ?>/recursos/js/principal.js"></script>
</body>
</html>

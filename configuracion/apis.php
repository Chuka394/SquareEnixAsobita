<?php
// configuracion de apis

// PayPal Sandbox
define('PAYPAL_CLIENT_ID', 'AZVtIH_n052kQWWYGa9QlX3eQX2ZF2Cc21aE_W221XVdrvehh0oNbfvJ3RtU6-h_ZaUgLnZoekKoygO_');
define('PAYPAL_SECRET',    'ED_KL5YA3rERQwYCh4L-CLud83kj6ZV4gmdGPHRXYp7y7TDfF55XZeAgbZPkCvCOU3e49xH6yO-sy8bQ');
define('PAYPAL_BASE_URL',  'https://api-m.sandbox.paypal.com');

// Resend 
define('RESEND_API_KEY', 're_CuDEkjkB_77Vaoa7d9FdUaZ9ARWboSnS9');
define('RESEND_FROM',    'onboarding@resend.dev');

// Rutas de subidas locales - Como no se pudo usar las apis de subir todo en la nube toco de manera local :(
define('RUTA_SUBIDAS',    __DIR__ . '/../recursos/subidas/');
define('URL_SUBIDAS',     '/squareenix/recursos/subidas/');

<?php
session_start();
session_destroy();
header('Location: /squareenix/index.php');
exit;

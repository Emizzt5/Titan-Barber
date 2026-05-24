<?php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
session_unset();
session_destroy();
header("Content-Type: application/json; charset=UTF-8");
echo json_encode(["mensaje" => "Sesión cerrada exitosamente"]);
?>

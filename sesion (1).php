<?php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        "autenticado" => true,
        "usuario_id" => $_SESSION['usuario_id'],
        "nombre" => $_SESSION['usuario_nombre']
    ]);
} else {
    echo json_encode(["autenticado" => false]);
}
?>

<?php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']), // True if HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["mensaje" => "No autorizado. Inicia sesión primero."]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "barberia_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error de conexión a la base de datos"]);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT id, nombre, barbero, servicio, producto, fecha, estado, forma_pago FROM reservas WHERE usuario_id = ? ORDER BY fecha DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$reservas = [];
while ($row = $result->fetch_assoc()) {
    foreach ($row as $key => $value) {
        if (is_string($value)) {
            $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    $reservas[] = $row;
}

echo json_encode(["reservas" => $reservas]);

$stmt->close();
$conn->close();
?>

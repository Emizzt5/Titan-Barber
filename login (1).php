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

$json = file_get_contents("php://input");
$data = json_decode($json);

if($data && isset($data->email) && isset($data->password)) {
    $conn = new mysqli("localhost", "root", "", "barberia_db");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["mensaje" => "Error de conexión"]);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($data->password, $row['password'])) {
            session_regenerate_id(true); // Mitiga ataques de fijación de sesión
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario_nombre'] = $row['nombre'];
            http_response_code(200);
            echo json_encode(["mensaje" => "Inicio de sesión exitoso", "usuario" => $row['nombre']]);
        } else {
            http_response_code(401);
            echo json_encode(["mensaje" => "Contraseña incorrecta"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["mensaje" => "Usuario no encontrado"]);
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "Faltan datos obligatorios"]);
}
?>

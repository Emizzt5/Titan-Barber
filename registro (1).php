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

$json = file_get_contents("php://input");
$data = json_decode($json);

if($data && isset($data->nombre) && isset($data->email) && isset($data->password)) {
    $conn = new mysqli("localhost", "root", "", "barberia_db");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["mensaje" => "Error de conexión"]);
        exit();
    }
    
    $check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check_email->bind_param("s", $data->email);
    $check_email->execute();
    $check_email->store_result();
    
    if ($check_email->num_rows > 0) {
        http_response_code(400);
        echo json_encode(["mensaje" => "Este email ya está registrado"]);
        exit();
    }
    $check_email->close();
    
    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $data->nombre, $data->email, $hashed_password);
    
    if ($stmt->execute()) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $stmt->insert_id;
        $_SESSION['usuario_nombre'] = $data->nombre;
        http_response_code(200);
        echo json_encode(["mensaje" => "Registro exitoso", "usuario" => $data->nombre]);
    } else {
        http_response_code(500);
        echo json_encode(["mensaje" => "Error en el registro"]);
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "Faltan datos obligatorios"]);
}
?>

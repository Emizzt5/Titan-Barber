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

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["mensaje" => "No autorizado"]);
    exit();
}

$json = file_get_contents("php://input");
$data = json_decode($json);

if($data && isset($data->reserva_id) && isset($data->accion)) {
    $conn = new mysqli("localhost", "root", "", "barberia_db");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["mensaje" => "Error de conexión"]);
        exit();
    }
    
    $reserva_id = $data->reserva_id;
    $usuario_id = $_SESSION['usuario_id'];
    
    // Check time limit
    $stmt = $conn->prepare("SELECT fecha FROM reservas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $reserva_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $fecha_cita = strtotime($row['fecha']);
        $ahora = time();
        $horas_diferencia = ($fecha_cita - $ahora) / 3600;
        
        if ($horas_diferencia < 24) {
            http_response_code(400);
            echo json_encode(["mensaje" => "No se puede modificar o cancelar citas con menos de 24 horas de anticipación."]);
            exit();
        }
        
        if ($data->accion === 'cancelar') {
            $update = $conn->prepare("UPDATE reservas SET estado = 'Cancelada' WHERE id = ?");
            $update->bind_param("i", $reserva_id);
            $update->execute();
            echo json_encode(["mensaje" => "Cita cancelada con éxito"]);
        } else if ($data->accion === 'modificar' && isset($data->nueva_fecha)) {
            $update = $conn->prepare("UPDATE reservas SET estado = 'Modificada', fecha = ? WHERE id = ?");
            $update->bind_param("si", $data->nueva_fecha, $reserva_id);
            $update->execute();
            echo json_encode(["mensaje" => "Cita reprogramada con éxito"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["mensaje" => "Reserva no encontrada"]);
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(["mensaje" => "Faltan datos"]);
}
?>

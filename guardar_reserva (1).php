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

// Verifica que el request venga por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "barberia_db");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["mensaje" => "Error de conexión a la base de datos"]);
        exit();
    }

    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $barbero = $_POST['barbero'] ?? '';
    $servicio = $_POST['servicio'] ?? '';
    $producto = $_POST['producto'] ?? '';
    $forma_pago = $_POST['forma_pago'] ?? '';
    $fecha = $_POST['fecha'] ?? '';

    // El usuario_id puede ser nulo si es un invitado
    $usuario_id = $_SESSION['usuario_id'] ?? null;

    if ($nombre && $telefono && $barbero && $servicio && $producto && $forma_pago && $fecha) {
        
        $comprobante_path = null;
        
        // Procesar comprobante si viene archivo
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $tmp_name = $_FILES['comprobante']['tmp_name'];
            $file_extension = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
            
            // Validar extensión permitida
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            
            // Validar tipo MIME real
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
            
            if (in_array($file_extension, $allowed_extensions) && in_array($mime_type, $allowed_mimes)) {
                // Generar nombre de archivo único
                $filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $comprobante_path = $destination;
                }
            } else {
                http_response_code(400);
                echo json_encode(["mensaje" => "Tipo de archivo no permitido. Solo se aceptan JPG, PNG y PDF."]);
                $conn->close();
                exit();
            }
        }

        $stmt = $conn->prepare("INSERT INTO reservas (usuario_id, nombre, telefono, barbero, servicio, producto, forma_pago, comprobante_pago, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $usuario_id, $nombre, $telefono, $barbero, $servicio, $producto, $forma_pago, $comprobante_path, $fecha);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["mensaje" => "¡La reserva se ha guardado exitosamente!"]);
        } else {
            http_response_code(500);
            echo json_encode(["mensaje" => "Tuvimos un error guardando la reserva"]);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(["mensaje" => "Faltan datos que son obligatorios"]);
    }
    $conn->close();
}
?>

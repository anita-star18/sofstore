<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido.');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    jsonResponse(false, 'Debes ingresar correo y contraseña.');
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        jsonResponse(false, 'Credenciales inválidas.');
    }

    $storedPassword = $usuario['password'] ?? '';
    $valid = false;
    if (password_verify($password, $storedPassword) || hash_equals($storedPassword, $password)) {
        $valid = true;
    }

    if (!$valid) {
        jsonResponse(false, 'Credenciales inválidas.');
    }

    if (($usuario['rol'] ?? '') !== 'admin') {
        jsonResponse(false, 'Este usuario no tiene permisos de administrador.');
    }

    $_SESSION['rol'] = 'admin';
    $_SESSION['admin_id'] = $usuario['id'];
    $_SESSION['admin_nombre'] = $usuario['nombre'] ?? 'Administrador';

    jsonResponse(true, 'Inicio de sesión exitoso.', ['redirect' => BASE_URL . 'index.php?view=admin_orders']);
} catch (Exception $e) {
    jsonResponse(false, 'Error al iniciar sesión: ' . $e->getMessage());
}

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido.');
}

if (!isAdminSession()) {
    jsonResponse(false, 'No autorizado.');
}

$pedido_id = intval($_POST['pedido_id'] ?? 0);
$estado = intval($_POST['estado'] ?? 1);

if (!$pedido_id) {
    jsonResponse(false, 'Pedido no válido.');
}

if (!in_array($estado, [1, 2, 3, 4], true)) {
    $estado = 1;
}

try {
    $db = (new Database())->getConnection();

    $current = $db->prepare('SELECT estado FROM pedidos WHERE id = ? LIMIT 1');
    $current->execute([$pedido_id]);
    $pedidoActual = $current->fetchColumn();
    $pedidoActual = $pedidoActual !== false ? (int) $pedidoActual : 1;

    if ($estado === 4) {
        $detalles = $db->prepare('SELECT producto_id, cantidad FROM detalle_pedidos WHERE pedido_id = ?');
        $detalles->execute([$pedido_id]);
        foreach ($detalles->fetchAll() as $detalle) {
            $stockCol = null;
            $cols = $db->query('SHOW COLUMNS FROM productos')->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('stock_actual', $cols, true)) {
                $stockCol = 'stock_actual';
            } elseif (in_array('stock', $cols, true)) {
                $stockCol = 'stock';
            }
            if ($stockCol) {
                $stmtStock = $db->prepare("UPDATE productos SET $stockCol = $stockCol + ? WHERE id = ?");
                $stmtStock->execute([$detalle['cantidad'], $detalle['producto_id']]);
            }
        }
    }

    $db->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')->execute([$estado, $pedido_id]);
    jsonResponse(true, 'Estado actualizado correctamente.');
} catch (Exception $e) {
    jsonResponse(false, 'Error al actualizar el estado: ' . $e->getMessage());
}

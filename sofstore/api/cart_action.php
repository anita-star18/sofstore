<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $cantidad = max(1, intval($_POST['cantidad'] ?? 1));

    if (!$producto_id) {
        jsonResponse(false, 'Producto no válido.');
    }

    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $prod = $stmt->fetch();

        if (!$prod) {
            jsonResponse(false, 'El producto no existe.');
        }

        $precio = normalizeProductPrice($prod);
        $stock = normalizeProductStock($prod);

        $cantidad_actual = $_SESSION['cart'][$producto_id]['cantidad'] ?? 0;
        $cantidad_total = $cantidad_actual + $cantidad;

        if ($stock <= 0) {
            jsonResponse(false, 'Este producto está agotado en este momento.');
        }

        if ($cantidad_total > $stock) {
            $disponible = max(0, $stock - $cantidad_actual);
            jsonResponse(false, "Stock insuficiente. Solo puedes agregar $disponible unidades más de este producto.");
        }

        if (isset($_SESSION['cart'][$producto_id])) {
            $_SESSION['cart'][$producto_id]['cantidad'] = $cantidad_total;
        } else {
            $_SESSION['cart'][$producto_id] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => $precio,
                'cantidad' => $cantidad,
                'imagen' => $prod['imagen'] ?? ''
            ];
        }

        $total_items = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_items += $item['cantidad'];
        }
        $_SESSION['cart_count'] = $total_items;

        jsonResponse(true, 'Producto añadido al carrito.', ['cart_count' => $total_items]);
    } catch (Exception $e) {
        jsonResponse(false, 'Error al agregar al carrito: ' . $e->getMessage());
    }
} elseif ($action === 'update') {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 1);

    if (!$producto_id) {
        jsonResponse(false, 'Producto no válido.');
    }

    if (!isset($_SESSION['cart'][$producto_id])) {
        jsonResponse(false, 'El producto no está en el carrito.');
    }

    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $prod = $stmt->fetch();

        if (!$prod) {
            jsonResponse(false, 'El producto ya no existe en inventario.');
        }

        $stock = normalizeProductStock($prod);

        if ($cantidad <= 0) {
            unset($_SESSION['cart'][$producto_id]);
        } else {
            if ($cantidad > $stock) {
                jsonResponse(false, "Stock insuficiente. Solo quedan $stock unidades disponibles.");
            }
            $_SESSION['cart'][$producto_id]['cantidad'] = $cantidad;
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error al actualizar el carrito: ' . $e->getMessage());
    }

    $total_items = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_items += $item['cantidad'];
    }
    $_SESSION['cart_count'] = $total_items;

    jsonResponse(true, 'Carrito actualizado.', ['cart_count' => $total_items]);
} elseif ($action === 'remove') {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    if (isset($_SESSION['cart'][$producto_id])) {
        unset($_SESSION['cart'][$producto_id]);
    }

    $total_items = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_items += $item['cantidad'];
    }
    $_SESSION['cart_count'] = $total_items;

    jsonResponse(true, 'Producto eliminado del carrito.', ['cart_count' => $total_items]);
} elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['cart_count'] = 0;
    jsonResponse(true, 'Carrito vaciado.');
}

jsonResponse(false, 'Acción no válida.');
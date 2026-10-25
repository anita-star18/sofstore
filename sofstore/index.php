<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$view = $_GET['view'] ?? 'catalog';
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php?view=admin_login');
    exit;
}

if (strpos($view, 'admin') === 0 && $view !== 'admin_login' && !isAdminSession()) {
    header('Location: ' . BASE_URL . 'index.php?view=admin_login');
    exit;
}

switch ($view) {
    case 'catalog':
        require_once __DIR__ . '/views/client/catalog.php';
        break;

    case 'cart':
        require_once __DIR__ . '/views/client/cart.php';
        break;

    case 'checkout':
        require_once __DIR__ . '/views/client/checkout.php';
        break;

    case 'my_orders':
    case 'orders':
        require_once __DIR__ . '/views/client/my_orders.php';
        break;

    case 'profile':
        require_once __DIR__ . '/views/client/profile.php';
        break;

    case 'admin_login':
        require_once __DIR__ . '/views/admin/login.php';
        break;

    case 'admin_orders':
        require_once __DIR__ . '/views/admin/orders.php';
        break;

    case 'admin_inventory':
        require_once __DIR__ . '/views/admin/inventory.php';
        break;

    case 'admin_stats':
        require_once __DIR__ . '/views/admin/stats.php';
        break;

    case 'admin_profile':
        require_once __DIR__ . '/views/admin/profile.php';
        break;

    default:
        require_once __DIR__ . '/views/client/catalog.php';
        break;
}
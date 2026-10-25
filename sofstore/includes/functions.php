<?php
if (!function_exists('formatCOP')) {
    function formatCOP($amount) {
        return '$' . number_format(floatval($amount), 0, ',', '.') . ' COP';
    }
}

if (!function_exists('normalizeProductPrice')) {
    function normalizeProductPrice($product) {
        if (!is_array($product)) {
            return 0;
        }

        foreach (['precio_dia', 'precio_base', 'precio', 'precio_venta', 'precio_unitario', 'valor'] as $col) {
            if (isset($product[$col]) && $product[$col] !== null && $product[$col] !== '') {
                $value = floatval($product[$col]);
                if ($value > 0) {
                    return $value;
                }
            }
        }

        return 0;
    }
}

if (!function_exists('normalizeProductStock')) {
    function normalizeProductStock($product) {
        if (!is_array($product)) {
            return 0;
        }

        foreach (['stock_actual', 'stock', 'cantidad'] as $col) {
            if (isset($product[$col]) && $product[$col] !== null && $product[$col] !== '') {
                return max(0, intval($product[$col]));
            }
        }

        return 0;
    }
}

if (!function_exists('normalizePaymentMethodValue')) {
    function normalizePaymentMethodValue($value) {
        if ($value === null || $value === '') {
            return 1;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $normalized = strtoupper(trim((string) $value));
        if (strpos($normalized, 'NEQUI') !== false) {
            return 2;
        }

        return 1;
    }
}

if (!function_exists('formatPaymentMethod')) {
    function formatPaymentMethod($value) {
        $paymentValue = normalizePaymentMethodValue($value);
        return $paymentValue === 2 ? 'Nequi' : 'Efectivo';
    }
}

if (!function_exists('normalizeOrderStatusValue')) {
    function normalizeOrderStatusValue($value) {
        if ($value === null || $value === '') {
            return 1;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $normalized = strtoupper(trim((string) $value));
        $map = [
            'PENDIENTE' => 1,
            'LISTO' => 2,
            'ENTREGADO' => 3,
            'RECHAZADO' => 4,
            'CANCELADO' => 4,
            'EN_PREPARACION' => 2,
            'PREPARACION' => 2,
        ];

        return $map[$normalized] ?? 1;
    }
}

if (!function_exists('getOrderStatusMeta')) {
    function getOrderStatusMeta($status) {
        $raw = normalizeOrderStatusValue($status);

        switch ((int) $raw) {
            case 1:
                return ['value' => 1, 'label' => 'Pendiente', 'color' => '#F59E0B'];
            case 2:
                return ['value' => 2, 'label' => 'Listo', 'color' => '#3B82F6'];
            case 3:
                return ['value' => 3, 'label' => 'Entregado', 'color' => '#10B981'];
            case 4:
                return ['value' => 4, 'label' => 'Rechazado', 'color' => '#EF4444'];
            default:
                return ['value' => 1, 'label' => 'Pendiente', 'color' => '#F59E0B'];
        }
    }
}

if (!function_exists('isAdminSession')) {
    function isAdminSession() {
        return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($success, $message = '', $data = []) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => (bool)$success,
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }
}
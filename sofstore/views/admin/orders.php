<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$db = (new Database())->getConnection();
$ver_historial = isset($_GET['historial']) && $_GET['historial'] == 1;
$estado_in = $ver_historial ? [3, 4] : [1, 2];
$placeholders = implode(',', array_fill(0, count($estado_in), '?'));
$stmt = $db->prepare("SELECT * FROM pedidos WHERE estado IN ($placeholders) ORDER BY id DESC");
$stmt->execute($estado_in);
$pedidos = $stmt->fetchAll();

foreach ($pedidos as &$p) {
    $stmtDet = $db->prepare("SELECT d.*, prod.nombre FROM detalle_pedidos d JOIN productos prod ON d.producto_id = prod.id WHERE d.pedido_id = ?");
    $stmtDet->execute([$p['id']]);
    $p['detalles'] = $stmtDet->fetchAll();
}
unset($p);
?>

<div class="day-products-banner">
    PANEL DE ADMINISTRACIÓN - PEDIDOS
</div>

<div style="padding: 16px; max-width: 1100px; margin: 0 auto;">
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>index.php?view=admin_orders" class="btn btn-primary" style="flex: 1; min-width: 180px; text-align: center;">📦 Pedidos abiertos</a>
        <a href="<?= BASE_URL ?>index.php?view=admin_orders&historial=1" class="btn btn-teal" style="flex: 1; min-width: 180px; text-align: center;">🕘 Historial</a>
    </div>

    <?php if ($ver_historial): ?>
        <div style="margin-bottom: 14px;">
            <a href="<?= BASE_URL ?>index.php?view=admin_orders" style="font-weight: bold; color: #054770; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                ← Volver a pedidos abiertos
            </a>
        </div>
    <?php endif; ?>

    <div id="toast-msg" style="display: none; position: fixed; top: 20px; right: 20px; background: #10B981; color: white; padding: 12px 20px; border-radius: var(--radius-sm); font-weight: bold; box-shadow: var(--shadow-sm); z-index: 2000;">
        ¡Estado actualizado correctamente!
    </div>

    <?php if (empty($pedidos)): ?>
        <div style="background: white; padding: 25px; text-align: center; border-radius: var(--radius-md); color: var(--grey-text);">
            No hay pedidos <?= $ver_historial ? 'en historial' : 'abiertos' ?>.
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($pedidos as $p):
                $estado_meta = getOrderStatusMeta($p['estado'] ?? 1);
                $metodo = formatPaymentMethod($p['metodo_pago'] ?? 1);
                $currentEstado = (int) ($p['estado'] ?? 1);
                $nextLabel = $currentEstado === 1 ? 'Listo' : 'Entregado';
                $nextValue = $currentEstado === 1 ? 2 : 3;
                $nextColor = $currentEstado === 1 ? '#2563eb' : '#16a34a';
            ?>
                <div style="background: white; padding: 18px; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 5px solid <?= $estado_meta['color'] ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem; color: var(--dark-blue);">
                                <?= htmlspecialchars($p['codigo_pedido']) ?> — <?= htmlspecialchars($p['nombre_estudiante']) ?>
                            </h3>
                            <small style="color: var(--grey-text);">Fecha: <?= htmlspecialchars($p['fecha_pedido'] ?? '') ?></small>
                        </div>
                        <span style="background: <?= $estado_meta['color'] ?>; color: white; padding: 5px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800;">
                            <?= htmlspecialchars($estado_meta['label']) ?>
                        </span>
                    </div>

                    <div style="background: #f8fafc; border-radius: 10px; padding: 10px 12px; margin-bottom: 12px;">
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.9rem;">
                            <?php foreach ($p['detalles'] as $item): ?>
                                <li style="display: flex; justify-content: space-between; gap: 8px; padding: 4px 0;">
                                    <span><strong><?= $item['cantidad'] ?>x</strong> <?= htmlspecialchars($item['nombre']) ?></span>
                                    <span><?= formatCOP($item['precio_unitario'] * $item['cantidad']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; font-size: 0.9rem; color: #475569;">
                        <div><strong>💳 Pago:</strong> <?= htmlspecialchars($metodo) ?></div>
                        <div><strong>Total:</strong> <span style="color: #054770; font-weight: 800; font-size: 1.05rem;"><?= formatCOP($p['monto_total']) ?></span></div>
                    </div>

                    <?php if (!empty($p['comprobante_nequi'])): ?>
                        <div style="margin-bottom: 12px;">
                            <a href="<?= BASE_URL ?>uploads/comprobantes/<?= htmlspecialchars($p['comprobante_nequi']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; color: #1d4ed8; font-weight: 700; text-decoration: underline;">
                                📸 Ver comprobante de Nequi
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!$ver_historial): ?>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php if ($currentEstado < 3): ?>
                                <button type="button" onclick="confirmAdvance(<?= $p['id'] ?>, <?= $nextValue ?>, '<?= htmlspecialchars($nextLabel) ?>')" style="background: <?= $nextColor ?>; color: white; border: none; border-radius: 8px; padding: 9px 14px; font-weight: 700; cursor: pointer;">
                                    <?= htmlspecialchars($nextLabel) ?>
                                </button>
                            <?php endif; ?>
                            <button type="button" onclick="cancelOrder(<?= $p['id'] ?>)" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 9px 14px; font-weight: 700; cursor: pointer;">
                                Cancelar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmAdvance(id, nextStatus, label) {
    const msg = nextStatus === 2
        ? '¿Deseas marcar este pedido como listo?'
        : '¿Confirmas que este pedido fue entregado?';
    if (confirm(msg)) {
        updateStatus(id, nextStatus, label);
    }
}

function cancelOrder(id) {
    if (confirm('¿Seguro que deseas rechazar este pedido? Esta acción devolverá el stock al inventario.')) {
        updateStatus(id, 4, 'Rechazado');
    }
}

function updateStatus(id, estado, label) {
    const formData = new FormData();
    formData.append('pedido_id', id);
    formData.append('estado', estado);

    fetch('<?= BASE_URL ?>api/update_order_status.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const toast = document.getElementById('toast-msg');
            toast.textContent = label ? 'Pedido marcado como ' + label : 'Estado actualizado correctamente.';
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
                location.reload();
            }, 1200);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error al conectar con el servidor.'));
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
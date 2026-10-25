<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$db = (new Database())->getConnection();
$productos = $db->query("SELECT * FROM productos ORDER BY id DESC")->fetchAll();
?>

<div class="day-products-banner">
    PANEL DE ADMINISTRACIÓN - INVENTARIO
</div>

<div style="padding: 16px; max-width: 1100px; margin: 0 auto;">
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>index.php?view=admin_orders" class="btn btn-primary" style="flex: 1; min-width: 180px; text-align: center;">📦 Ver Pedidos</a>
        <a href="<?= BASE_URL ?>index.php?view=admin_inventory" class="btn btn-teal" style="flex: 1; min-width: 180px; text-align: center;">🏷️ Gestionar Inventario</a>
    </div>

    <div style="margin-bottom: 16px; text-align: right;">
        <button onclick="toggleForm()" class="btn btn-teal" style="font-weight: bold;">
            ➕ Registrar Nuevo Producto
        </button>
    </div>

    <div id="newProductFormCard" style="display: none; background: white; padding: 20px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 20px; border: 2px solid #22c55e;">
        <h3 style="margin-top: 0; color: var(--dark-blue);">Nuevo Producto</h3>
        <form id="createProductForm" onsubmit="submitNewProduct(event)">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 4px;">Nombre:</label>
                    <input type="text" id="new_nombre" required placeholder="Ej. Empanada de Carne" style="width: 100%; padding: 8px; border: 1px solid #CCC; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 4px;">Precio ($ COP):</label>
                    <input type="number" id="new_precio" required step="50" min="0" placeholder="2500" style="width: 100%; padding: 8px; border: 1px solid #CCC; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 4px;">Stock:</label>
                    <input type="number" id="new_stock" value="10" min="0" style="width: 100%; padding: 8px; border: 1px solid #CCC; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 4px;">Stock bajo alerta:</label>
                    <input type="number" id="new_stock_alerta" value="5" min="0" style="width: 100%; padding: 8px; border: 1px solid #CCC; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 4px;">Descripción:</label>
                    <textarea id="new_descripcion" required rows="3" placeholder="Describe el producto..." style="width: 100%; padding: 8px; border: 1px solid #CCC; border-radius: 4px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 4px;">Imagen:</label>
                    <input type="file" id="new_imagen" accept="image/*" style="width: 100%; font-size: 0.85rem;">
                </div>
            </div>

            <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="new_publicado" checked style="width: 18px; height: 18px;">
                <label for="new_publicado" style="font-weight: bold; font-size: 0.9rem; cursor: pointer;">Publicar en el catálogo de hoy</label>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-teal">Guardar Producto</button>
                <button type="button" onclick="toggleForm()" class="btn" style="background: #64748B; color: white;">Cancelar</button>
            </div>
        </form>
    </div>

    <input type="text" id="searchProduct" onkeyup="filterProducts()" placeholder="🔍 Buscar producto por nombre..." style="width: 100%; padding: 10px; margin-bottom: 16px; border: 1px solid #CCC; border-radius: var(--radius-sm); box-sizing: border-box;">

    <div id="toast-inv" style="display: none; position: fixed; top: 20px; right: 20px; background: #10B981; color: white; padding: 12px 20px; border-radius: var(--radius-sm); font-weight: bold; box-shadow: var(--shadow-sm); z-index: 2000;">
        ¡Acción completada con éxito!
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
        <?php foreach ($productos as $prod):
            $val_precio = normalizeProductPrice($prod);
            $val_stock = normalizeProductStock($prod);
            $img_name = !empty($prod['imagen']) ? basename($prod['imagen']) : '';
            $img_url = null;
            if (!empty($img_name)) {
                $paths = [
                    __DIR__ . '/../../uploads/products/' . $img_name => BASE_URL . 'uploads/products/' . $img_name,
                    __DIR__ . '/../../assets/images/' . $img_name => BASE_URL . 'assets/images/' . $img_name,
                    __DIR__ . '/../../uploads/' . $img_name => BASE_URL . 'uploads/' . $img_name,
                ];
                foreach ($paths as $fsPath => $webPath) {
                    if (file_exists($fsPath)) {
                        $img_url = $webPath;
                        break;
                    }
                }
            }
        ?>
            <div class="product-card" data-name="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>" style="background: <?= $val_stock <= 0 ? '#fff1f2' : '#ffffff' ?>; border: 1px solid <?= $val_stock <= 0 ? '#fecdd3' : '#e2e8f0' ?>; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 14px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                    <span style="padding: 4px 8px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; background: <?= $val_stock <= 0 ? '#fecdd3' : '#dcfce7' ?>; color: <?= $val_stock <= 0 ? '#b91c1c' : '#166534' ?>;">
                        <?= $val_stock <= 0 ? 'Agotado' : 'Disponible' ?>
                    </span>
                    <span style="font-size: 0.72rem; color: #64748B; font-weight: 700;">#<?= $prod['id'] ?></span>
                </div>

                <div style="height: 120px; background: #f8fafc; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <?php if ($img_url): ?>
                        <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="font-size: 2.4rem;">🍽️</span>
                    <?php endif; ?>
                </div>

                <div style="font-size: 0.95rem; font-weight: 800; color: #1e293b; min-height: 42px;">
                    <?= htmlspecialchars($prod['nombre']) ?>
                </div>

                <div style="display: grid; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #475569; font-weight: 700;">Precio</label>
                    <input type="number" id="precio_<?= $prod['id'] ?>" value="<?= $val_precio ?>" step="50" min="0" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; text-align: center; font-weight: bold; color: var(--medium-blue); box-sizing: border-box;">
                </div>

                <div style="display: grid; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #475569; font-weight: 700;">Stock</label>
                    <input type="number" id="stock_<?= $prod['id'] ?>" value="<?= $val_stock ?>" min="0" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; text-align: center; font-weight: bold; box-sizing: border-box;">
                </div>

                <div style="display: grid; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #475569; font-weight: 700;">Descripción</label>
                    <textarea id="desc_<?= $prod['id'] ?>" rows="3" style="width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; resize: vertical;"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></textarea>
                </div>

                <div style="display: grid; gap: 8px;">
                    <label style="font-size: 0.75rem; color: #475569; font-weight: 700;">Stock bajo alerta</label>
                    <input type="number" id="alert_<?= $prod['id'] ?>" value="<?= intval($prod['stock_bajo_alerta'] ?? 5) ?>" min="0" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; text-align: center; font-weight: bold; box-sizing: border-box;">
                </div>

                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 700; color: #1e293b;">
                    <input type="checkbox" id="pub_<?= $prod['id'] ?>" <?= !empty($prod['publicado_hoy']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    Publicado hoy
                </label>

                <div style="display: flex; gap: 8px; justify-content: space-between; margin-top: auto;">
                    <button onclick="saveInventory(<?= $prod['id'] ?>)" class="btn btn-teal" style="flex: 1; padding: 7px 8px; font-size: 0.75rem; min-width: 0;">Guardar</button>
                    <button onclick="deleteProduct(<?= $prod['id'] ?>, '<?= htmlspecialchars(addslashes($prod['nombre'])) ?>')" class="btn" style="flex: 1; padding: 7px 8px; font-size: 0.75rem; background: #ef4444; color: white; min-width: 0;">Eliminar</button>
                </div>

                <?php if ($val_stock <= 0): ?>
                    <button onclick="habilitarStock(<?= $prod['id'] ?>)" class="btn" style="width: 100%; padding: 8px; font-size: 0.75rem; background: #f87171; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700;">
                        Habilitar stock
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleForm() {
    const card = document.getElementById('newProductFormCard');
    card.style.display = card.style.display === 'none' ? 'block' : 'none';
}

function submitNewProduct(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('nombre', document.getElementById('new_nombre').value.trim());
    formData.append('descripcion', document.getElementById('new_descripcion').value.trim());
    formData.append('precio', document.getElementById('new_precio').value);
    formData.append('stock_actual', document.getElementById('new_stock').value);
    formData.append('stock_bajo_alerta', document.getElementById('new_stock_alerta').value || 5);
    if (document.getElementById('new_publicado').checked) formData.append('publicado_hoy', '1');
    const imgFile = document.getElementById('new_imagen').files[0];
    if (imgFile) formData.append('imagen', imgFile);

    fetch('<?= BASE_URL ?>api/create_product.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('¡Producto registrado con éxito!');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error al conectar con el servidor.'));
}

function filterProducts() {
    const input = document.getElementById('searchProduct').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        card.style.display = name.includes(input) ? 'flex' : 'none';
    });
}

function saveInventory(id) {
    const precio = document.getElementById('precio_' + id).value;
    const stock = document.getElementById('stock_' + id).value;
    const descripcion = document.getElementById('desc_' + id)?.value || '';
    const stockAlerta = document.getElementById('alert_' + id)?.value || 5;
    const pub = document.getElementById('pub_' + id).checked ? 1 : 0;
    const imgInput = document.getElementById('img_' + id);
    const formData = new FormData();
    formData.append('producto_id', id);
    formData.append('descripcion', descripcion);
    formData.append('precio', precio);
    formData.append('stock_actual', stock);
    formData.append('stock_bajo_alerta', stockAlerta);
    formData.append('publicado_hoy', pub);
    if (imgInput && imgInput.files[0]) formData.append('imagen', imgInput.files[0]);

    fetch('<?= BASE_URL ?>api/update_inventory.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('¡Producto actualizado correctamente!');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error al conectar con el servidor.'));
}

function habilitarStock(id) {
    const stockInput = document.getElementById('stock_' + id);
    if (!stockInput) return;
    stockInput.value = 1;
    saveInventory(id);
}

function deleteProduct(id, nombre) {
    if (confirm('¿Estás seguro de que deseas eliminar el producto "' + nombre + '"?')) {
        const formData = new FormData();
        formData.append('producto_id', id);
        fetch('<?= BASE_URL ?>api/delete_product.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Producto eliminado.');
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => alert('Error al conectar con el servidor.'));
    }
}

function showToast(msg) {
    const toast = document.getElementById('toast-inv');
    toast.textContent = msg;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 2000);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
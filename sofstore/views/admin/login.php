<?php
require_once __DIR__ . '/../../includes/header.php';

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
}

if (isAdminSession()) {
    header('Location: ' . BASE_URL . 'index.php?view=admin_orders');
    exit;
}
?>

<div style="max-width: 420px; margin: 60px auto 20px; background: white; border-radius: 18px; box-shadow: 0 8px 28px rgba(15,23,42,0.08); padding: 28px 24px; border: 1px solid #e2e8f0;">
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 2rem; margin-bottom: 8px;">🔐</div>
        <h2 style="margin: 0; color: #054770;">Acceso administrador</h2>
    </div>

    <form id="adminLoginForm">
        <div style="margin-bottom: 14px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #1e293b;">Correo electrónico</label>
            <input type="email" id="admin_email" required placeholder="admin@sofstore.com" style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; font-size: 0.95rem;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #1e293b;">Contraseña</label>
            <input type="password" id="admin_password" required placeholder="••••••••" style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; font-size: 0.95rem;">
        </div>

        <button type="submit" style="width: 100%; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; border-radius: 12px; padding: 12px; font-size: 1rem; font-weight: 800; cursor: pointer; box-shadow: 0 10px 20px rgba(34,197,94,0.25); transition: transform 0.2s ease, box-shadow 0.2s ease;">
            Iniciar sesión
        </button>
    </form>

    <div style="margin-top: 16px; text-align: center;">
        <a href="<?= BASE_URL ?>index.php?view=catalog" style="color: #054770; font-weight: 700; text-decoration: none;">Volver al catálogo</a>
    </div>
</div>

<script>
const form = document.getElementById('adminLoginForm');
form.addEventListener('submit', function (e) {
    e.preventDefault();
    const email = document.getElementById('admin_email').value.trim();
    const password = document.getElementById('admin_password').value;

    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    fetch('<?= BASE_URL ?>api/admin_login.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.data?.redirect || '<?= BASE_URL ?>index.php?view=admin_orders';
        } else {
            alert(data.message || 'Credenciales inválidas.');
        }
    })
    .catch(() => alert('Error al iniciar sesión.'));
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

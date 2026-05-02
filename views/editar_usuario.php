<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['rol_id'] != 1) {
    header("Location: /choco_tumac/index.php?view=dashboard&error=" . urlencode("Acceso no permitido.")); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Usuario – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>
<?php require __DIR__ . '/layout/navbar.php'; ?>

<div class="container mt-4" style="max-width:600px;">
    <div class="page-header">
        <h2>Editar Usuario</h2>
        <a href="/choco_tumac/index.php?view=dashboard" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>

    <div class="card p-4">
        <form method="POST" action="/choco_tumac/controllers/UsuarioController.php?action=actualizar" data-validate>
            <input type="hidden" name="id"         value="<?= $usuario['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Nombre completo</label>
                <input class="form-control" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required minlength="2">
                <div class="invalid-feedback">El nombre es obligatorio.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Correo electrónico</label>
                <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Teléfono</label>
                <input class="form-control" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" placeholder="3001234567">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Rol</label>
                <select class="form-select" name="rol_id" required>
                    <option value="1" <?= $usuario['rol_id'] == 1 ? 'selected' : '' ?>>Administrador</option>
                    <option value="2" <?= $usuario['rol_id'] == 2 ? 'selected' : '' ?>>Gerente</option>
                    <option value="3" <?= $usuario['rol_id'] == 3 ? 'selected' : '' ?>>Empleado</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/choco_tumac/index.php?view=dashboard" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/choco_tumac/public/js/app.js"></script>
</body>
</html>

<?php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['rol_id'], [1, 3])) {
    header("Location: /choco_tumac/index.php?view=clientes&error=" . urlencode("Acceso no permitido.")); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Cliente – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>
<?php require __DIR__ . '/layout/navbar.php'; ?>

<div class="container mt-4" style="max-width:760px;">
    <div class="page-header">
        <h2>Editar Cliente</h2>
        <a href="/choco_tumac/index.php?view=clientes" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>

    <div class="card p-4">
        <form method="POST" action="/choco_tumac/controllers/ClienteController.php?action=actualizar" data-validate>
            <input type="hidden" name="id"         value="<?= $cliente['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input class="form-control" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_doc" id="tipo_doc_edit" required>
                        <?php foreach (['CC','NIT','CE','Pasaporte'] as $t): ?>
                        <option value="<?= $t ?>" <?= $cliente['tipo_doc'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input class="form-control" name="num_doc" value="<?= htmlspecialchars($cliente['num_doc']) ?>"
                           required pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Obligatorio.</div>
                </div>
                <div class="col-md-2" id="div_dv_edit" style="display:<?= $cliente['tipo_doc'] === 'NIT' ? '' : 'none' ?>">
                    <label class="form-label fw-semibold">DV</label>
                    <input class="form-control" name="digito_ver" value="<?= htmlspecialchars($cliente['digito_ver'] ?? '') ?>"
                           maxlength="1" pattern="[0-9]">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input class="form-control" name="telefono" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>" placeholder="3001234567">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input class="form-control" name="direccion" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ciudad</label>
                    <input class="form-control" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>" placeholder="Ej: Tumaco">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Departamento</label>
                    <input class="form-control" name="departamento" value="<?= htmlspecialchars($cliente['departamento'] ?? '') ?>" placeholder="Ej: Nariño">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/choco_tumac/index.php?view=clientes" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/choco_tumac/public/js/app.js"></script>
<script>
document.getElementById('tipo_doc_edit')?.addEventListener('change', function () {
    document.getElementById('div_dv_edit').style.display = this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>

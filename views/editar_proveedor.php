<?php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['rol_id'], [1, 3])) {
    header("Location: /choco_tumac/index.php?view=proveedores&error=" . urlencode("Acceso no permitido.")); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Proveedor – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>
<?php require __DIR__ . '/layout/navbar.php'; ?>

<div class="container mt-4" style="max-width:800px;">
    <div class="page-header">
        <h2>Editar Proveedor</h2>
        <a href="/choco_tumac/index.php?view=proveedores" class="btn btn-sm btn-outline-secondary">← Volver</a>
    </div>

    <div class="card p-4">
        <form method="POST" action="/choco_tumac/controllers/ProveedorController.php?action=actualizar" data-validate>
            <input type="hidden" name="id"         value="<?= $proveedor['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nombre / Razón social <span class="text-danger">*</span></label>
                    <input class="form-control" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tipo doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_doc" id="tipo_doc_prov_edit" required>
                        <?php foreach (['CC','NIT','CE','Pasaporte'] as $t): ?>
                        <option value="<?= $t ?>" <?= $proveedor['tipo_doc'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">N° documento <span class="text-danger">*</span></label>
                    <input class="form-control" name="num_doc" value="<?= htmlspecialchars($proveedor['num_doc']) ?>"
                           required pattern="[0-9\-]+" title="Solo números y guiones">
                    <div class="invalid-feedback">Obligatorio.</div>
                </div>
                <div class="col-md-1" id="div_dv_prov_edit" style="display:<?= $proveedor['tipo_doc'] === 'NIT' ? '' : 'none' ?>">
                    <label class="form-label fw-semibold">DV</label>
                    <input class="form-control" name="digito_ver" value="<?= htmlspecialchars($proveedor['digito_ver'] ?? '') ?>"
                           maxlength="1" pattern="[0-9]">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tipo proveedor <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_proveedor" required>
                        <?php foreach (['Agricultor','Intermediario','Cooperativa','Empresa'] as $tp): ?>
                        <option value="<?= $tp ?>" <?= $proveedor['tipo_proveedor'] === $tp ? 'selected' : '' ?>><?= $tp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Persona de contacto</label>
                    <input class="form-control" name="persona_contacto" value="<?= htmlspecialchars($proveedor['persona_contacto'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input class="form-control" name="telefono" value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input class="form-control" name="direccion" value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ciudad</label>
                    <input class="form-control" name="ciudad" value="<?= htmlspecialchars($proveedor['ciudad'] ?? '') ?>" placeholder="Ej: Tumaco">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Departamento</label>
                    <input class="form-control" name="departamento" value="<?= htmlspecialchars($proveedor['departamento'] ?? '') ?>" placeholder="Ej: Nariño">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-ct-primary px-4">Guardar cambios</button>
                <a href="/choco_tumac/index.php?view=proveedores" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/choco_tumac/public/js/app.js"></script>
<script>
document.getElementById('tipo_doc_prov_edit')?.addEventListener('change', function () {
    document.getElementById('div_dv_prov_edit').style.display = this.value === 'NIT' ? '' : 'none';
});
</script>
</body>
</html>

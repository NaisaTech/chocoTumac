<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: /choco_tumac/index.php"); exit(); }

require 'views/layout/navbar.php';
require_once 'models/Usuario.php';
$model    = new Usuario();
$usuarios = $model->obtener();
$rol      = $_SESSION['user']['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard – Chocolate Tumaco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/choco_tumac/public/css/styles.css">
</head>
<body>

<div class="container mt-4">

    <div class="page-header">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['user']['nombre']) ?> </h2>
    </div>

    <?php if ($rol != 1): ?>
        <div class="alert alert-info">
            <?php if ($rol == 2): ?>
                Tienes acceso de <strong>solo lectura</strong>. Puedes visualizar clientes y proveedores.
            <?php elseif ($rol == 3): ?>
                Puedes gestionar clientes y proveedores. Para administrar usuarios, contacta al administrador.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php
        $msgs = [
            'creado'      => ['success', '✓ Usuario creado correctamente.'],
            'actualizado' => ['success', '✓ Usuario actualizado correctamente.'],
            'eliminado'   => ['warning', '✓ Usuario eliminado del sistema.'],
        ];
        if (isset($msgs[$_GET['msg']])):
            [$tipo, $texto] = $msgs[$_GET['msg']];
        ?>
        <div class="alert alert-<?= $tipo ?> alert-auto alert-dismissible" role="alert">
            <?= $texto ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-auto alert-dismissible" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($rol == 1): ?>

    <!-- FORMULARIO CREAR USUARIO -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Crear Nuevo Usuario</h5>
        <form method="POST" action="/choco_tumac/controllers/UsuarioController.php?action=crear" data-validate>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="row g-2 mb-2">
                <div class="col-md">
                    <label class="form-label small fw-semibold">Nombre</label>
                    <input class="form-control" name="nombre" placeholder="Nombre completo" required minlength="2">
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>
                <div class="col-md">
                    <label class="form-label small fw-semibold">Correo electrónico</label>
                    <input class="form-control" type="email" name="email" placeholder="correo@ejemplo.com" required>
                    <div class="invalid-feedback">Ingresa un correo válido.</div>
                </div>
                <div class="col-md">
                    <label class="form-label small fw-semibold">Teléfono</label>
                    <input class="form-control" name="telefono" placeholder="3001234567">
                </div>
                <div class="col-md">
                    <label class="form-label small fw-semibold">Contraseña</label>
                    <input class="form-control" type="password" name="password" id="input-password" placeholder="Mínimo 8 caracteres" required>
                    <div id="feedback-password" class="form-text"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Rol</label>
                    <select class="form-select" name="rol_id" required>
                        <option value="1">Administrador</option>
                        <option value="2">Gerente</option>
                        <option value="3" selected>Empleado</option>
                    </select>
                </div>
                <div class="col-md-auto d-flex align-items-end">
                    <button class="btn btn-ct-primary px-4">Crear</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLA USUARIOS -->
    <div class="card p-4">
        <h5 class="mb-3 fw-bold" style="color:#5C3317;">Usuarios Registrados</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Último acceso</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($usuarios as $u): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['telefono'] ?? '—') ?></td>
                    <td>
                        <?php
                        $badgeClass = ['Administrador' => 'badge-admin', 'Gerente' => 'badge-gerente', 'Empleado' => 'badge-empleado'][$u['rol']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($u['rol']) ?></span>
                    </td>
                    <td class="text-muted">
                        <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                    </td>
                    <td class="text-center">
                        <?php if ($u['rol'] !== 'Administrador' || $u['id'] == $_SESSION['user']['id']): ?>
                            <a class="btn btn-warning btn-sm"
                               href="/choco_tumac/controllers/UsuarioController.php?action=editar&id=<?= $u['id'] ?>">
                                Editar
                            </a>
                            <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                            <button class="btn btn-danger btn-sm btn-confirmar-eliminar"
                                    data-url="/choco_tumac/controllers/UsuarioController.php?action=eliminar&id=<?= $u['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                 Eliminar
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">Protegido</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Modal confirmar eliminación -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"> Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalEliminarTexto"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="modalEliminarLink" href="#" class="btn btn-danger">Sí, eliminar</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/choco_tumac/public/js/app.js"></script>
</body>
</html>

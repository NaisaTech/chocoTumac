<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Usuario.php';

/**
 * URL base del proyecto
 */
define("BASE_URL", "/chocoTumac/");

/**
 * Controlador de usuarios
 * 
 * Maneja:
 * - Autenticación (login/logout)
 * - CRUD de usuarios
 * - Gestión de perfil
 * - Seguridad (CSRF, sesiones, permisos)
 */
class UsuarioController {

    /** @var Usuario Modelo de usuario */
    private $model;

    /**
     * Constructor: inicializa el modelo
     */
    public function __construct() {
        $this->model = new Usuario();
    }

    /**
     * Verifica si el usuario actual es administrador
     */
    private function esAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }

    /**
     * Protección CSRF
     */
    private function verificarCSRF() {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("Petición no válida. Recarga la página."));
            exit();
        }
    }

    /**
     * Método principal que ejecuta las acciones
     */
    public function ejecutar() {

        $accion = $_GET['action'] ?? '';

        // Si la acción no es login o logout, verificar que el usuario esté autenticado
        if ($accion !== 'login' && !isset($_SESSION['user'])) {
            header("Location: " . BASE_URL . "index.php?view=login&error=" . urlencode("Tu sesión ha expirado."));
            exit();
        }

        switch ($accion) {


            /*
             * Crear usuario
             */
            case 'crear':
                if (!$this->esAdmin()) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No tienes permisos.")); exit(); }
                $this->verificarCSRF();
                $res = $this->model->crear(
                    $_POST['nombre']   ?? '',
                    $_POST['email']    ?? '',
                    $_POST['password'] ?? '',
                    $_POST['rol_id']   ?? 0,
                    $_POST['telefono'] ?? ''
                );
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&msg=creado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode($res));
                }
                break;
            /**
             * EDITAR USUARIO (vista)
             */
            case 'editar':
                if (!$this->esAdmin()) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No tienes permisos.")); exit(); }
                $usuario = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$usuario) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("Usuario no encontrado.")); exit(); }
                require __DIR__ . '/../views/editar_usuario.php';
                break;

            /*
             * Actualizar usuario
             */
            case 'actualizar':
                if (!$this->esAdmin()) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No tienes permisos.")); exit(); }
                $this->verificarCSRF();
                $usuario = $this->model->obtenerPorId($_POST['id'] ?? 0);
                if (!$usuario) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("Usuario no encontrado.")); exit(); }
                // Si el usuario a actualizar es un admin diferente al actual, no permitirlo
                if ($usuario['rol_id'] == 1 && $usuario['id'] != $_SESSION['user']['id']) {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No puedes modificar la cuenta de otro administrador.")); exit();
                }
                $res = $this->model->actualizar($_POST['id'], $_POST['nombre'] ?? '', $_POST['email'] ?? '', $_POST['rol_id'] ?? 0, $_POST['telefono'] ?? '');
                if ($res === true) {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&msg=actualizado");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode($res));
                }
                break;

            /*
             * Eliminar usuario
             */
            case 'eliminar':
                if (!$this->esAdmin()) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No tienes permisos.")); exit(); }
                $usuario = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$usuario) { header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("Usuario no encontrado.")); exit(); }
                if ($usuario['rol_id'] == 1 && $usuario['id'] != $_SESSION['user']['id']) {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No puedes eliminar a otro administrador.")); exit();
                }
                if ($usuario['id'] == $_SESSION['user']['id']) {
                    header("Location: " . BASE_URL . "index.php?view=dashboard&error=" . urlencode("No puedes eliminar tu propia cuenta.")); exit();
                }
                $this->model->eliminar($_GET['id']);
                header("Location: " . BASE_URL . "index.php?view=dashboard&msg=eliminado");
                break;
            /*
             * Login
             */
            case 'login':
                $user = $this->model->login($_POST['email'] ?? '', $_POST['password'] ?? '');
                if ($user) {
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    header("Location: " . BASE_URL . "index.php?view=dashboard");
                } else {
                    header("Location: " . BASE_URL . "index.php?error=login");
                }
                break;

            /*
             * Logout
             */
            case 'logout':
                session_unset();
                session_destroy();
                header("Location: " . BASE_URL . "index.php");
                break;

            /*
             * Actualizar perfil (usuario común)
             */
            case 'actualizarPerfil':
                $this->verificarCSRF();
                if ((int)$_POST['id'] !== (int)$_SESSION['user']['id']) {
                    header("Location: " . BASE_URL . "index.php?view=perfil&error=" . urlencode("No puedes modificar el perfil de otro usuario.")); exit();
                }
                $res = $this->model->actualizar($_POST['id'], $_POST['nombre'] ?? '', $_POST['email'] ?? '', $_SESSION['user']['rol_id'], $_POST['telefono'] ?? '');
                if ($res === true) {
                    // Actualizar datos en sesión
                    $_SESSION['user']['nombre']   = trim($_POST['nombre']);
                    $_SESSION['user']['email']    = strtolower(trim($_POST['email']));
                    $_SESSION['user']['telefono'] = trim($_POST['telefono']);
                    header("Location: " . BASE_URL . "index.php?view=perfil&msg=ok");
                } else {
                    header("Location: " . BASE_URL . "index.php?view=perfil&error=" . urlencode($res));
                }
                break;
            /*
             * Cambiar contraseña (usuario común)
             */
            case 'cambiarPassword':
                $this->verificarCSRF();
                if ((int)$_POST['id'] !== (int)$_SESSION['user']['id']) {
                    header("Location: " . BASE_URL . "index.php?view=perfil&error=" . urlencode("Acción no permitida.")); exit();
                }
                $user = $this->model->obtenerConPassword($_POST['id']);
                if (!password_verify($_POST['actual'] ?? '', $user['password'])) {
                    header("Location: " . BASE_URL . "index.php?view=perfil&error=" . urlencode("La contraseña actual no es correcta.")); exit();
                }
                $nueva = $_POST['nueva'] ?? '';
                if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $nueva)) {
                    header("Location: " . BASE_URL . "index.php?view=perfil&error=" . urlencode("La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.")); exit();
                }
                $this->model->actualizarPassword($_POST['id'], password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]));
                header("Location: " . BASE_URL . "index.php?view=perfil&msg=pass");
                break;
        }
    }
}

(new UsuarioController())->ejecutar();

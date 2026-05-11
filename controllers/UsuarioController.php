<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/Redirectable.php';

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
    use Redirectable;


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
            $this->redirectError('dashboard', 'Petición no válida. Recarga la página.');
        }
    }

    /**
     * Método principal que ejecuta las acciones
     */
    public function ejecutar() {

        $accion = $_GET['action'] ?? '';

        // Si la acción no es login o logout, verificar que el usuario esté autenticado
        if ($accion !== 'login' && !isset($_SESSION['user'])) {
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }

        switch ($accion) {


            /*
             * Crear usuario
             */
            case 'crear':
                if (!$this->esAdmin()) { $this->redirectError('dashboard', 'No tienes permisos.'); }
                $this->verificarCSRF();
                $res = $this->model->crear(
                    $_POST['nombre']   ?? '',
                    $_POST['email']    ?? '',
                    $_POST['password'] ?? '',
                    $_POST['rol_id']   ?? 0,
                    $_POST['telefono'] ?? ''
                );
                if ($res === true) {
                    $this->redirectOk('dashboard', 'creado');} else {
                    $this->redirectError('dashboard', $res);}
                break;
            /**
             * EDITAR USUARIO (vista)
             */
            case 'editar':
                if (!$this->esAdmin()) { $this->redirectError('dashboard', 'No tienes permisos.'); }
                $usuario = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$usuario) { $this->redirectError('dashboard', 'Usuario no encontrado.'); }
                require_once __DIR__ . '/../views/editar_usuario.php';
                break;

            /*
             * Actualizar usuario
             */
            case 'actualizar':
                if (!$this->esAdmin()) { $this->redirectError('dashboard', 'No tienes permisos.'); }
                $this->verificarCSRF();
                $usuario = $this->model->obtenerPorId($_POST['id'] ?? 0);
                if (!$usuario) { $this->redirectError('dashboard', 'Usuario no encontrado.'); }
                // Si el usuario a actualizar es un admin diferente al actual, no permitirlo
                if ($usuario['rol_id'] == 1 && $usuario['id'] != $_SESSION['user']['id']) {
                    $this->redirectError('dashboard', 'No puedes modificar la cuenta de otro administrador.');
                }
                $res = $this->model->actualizar($_POST['id'], $_POST['nombre'] ?? '', $_POST['email'] ?? '', $_POST['rol_id'] ?? 0, $_POST['telefono'] ?? '');
                if ($res === true) {
                    $this->redirectOk('dashboard', 'actualizado');} else {
                    $this->redirectError('dashboard', $res);}
                break;

            /*
             * Eliminar usuario
             */
            case 'eliminar':
                if (!$this->esAdmin()) { $this->redirectError('dashboard', 'No tienes permisos.'); }
                $usuario = $this->model->obtenerPorId($_GET['id'] ?? 0);
                if (!$usuario) { $this->redirectError('dashboard', 'Usuario no encontrado.'); }
                if ($usuario['rol_id'] == 1 && $usuario['id'] != $_SESSION['user']['id']) {
                    $this->redirectError('dashboard', 'No puedes eliminar a otro administrador.');
                }
                if ($usuario['id'] == $_SESSION['user']['id']) {
                    $this->redirectError('dashboard', 'No puedes eliminar tu propia cuenta.');
                }
                $this->model->eliminar($_GET['id']);
                $this->redirectOk('dashboard', 'eliminado');break;
            /*
             * Login
             */
            case 'login':
                $user = $this->model->login($_POST['email'] ?? '', $_POST['password'] ?? '');
                if ($user) {
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    $this->redirect("index.php?view=dashboard");} else {
                    $this->redirect("index.php?error=login");}
                break;

            /*
             * Logout
             */
            case 'logout':
                session_unset();
                session_destroy();
                $this->redirect("index.php");
                break;

            /*
             * Actualizar perfil (usuario común)
             */
            case 'actualizarPerfil':
                $this->verificarCSRF();
                if ((int)$_POST['id'] !== (int)$_SESSION['user']['id']) {
                    $this->redirectError('perfil', 'No puedes modificar el perfil de otro usuario.');
                }
                $res = $this->model->actualizar($_POST['id'], $_POST['nombre'] ?? '', $_POST['email'] ?? '', $_SESSION['user']['rol_id'], $_POST['telefono'] ?? '');
                if ($res === true) {
                    // Actualizar datos en sesión
                    $_SESSION['user']['nombre']   = trim($_POST['nombre']);
                    $_SESSION['user']['email']    = strtolower(trim($_POST['email']));
                    $_SESSION['user']['telefono'] = trim($_POST['telefono']);
                    $this->redirectOk('perfil', 'ok');} else {
                    $this->redirectError('perfil', $res);}
                break;
            /*
             * Cambiar contraseña (usuario común)
             */
            case 'cambiarPassword':
                $this->verificarCSRF();
                if ((int)$_POST['id'] !== (int)$_SESSION['user']['id']) {
                    $this->redirectError('perfil', 'Acción no permitida.');
                }
                $user = $this->model->obtenerConPassword($_POST['id']);
                if (!password_verify($_POST['actual'] ?? '', $user['password'])) {
                    $this->redirectError('perfil', 'La contraseña actual no es correcta.');
                }
                $nueva = $_POST['nueva'] ?? '';
                if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $nueva)) {
                    $this->redirectError('perfil', 'La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.');
                }
                $this->model->actualizarPassword($_POST['id'], password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]));
                $this->redirectOk('perfil', 'pass');break;
        }
    }
}

(new UsuarioController())->ejecutar();
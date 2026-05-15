<?php
/**
 * Controlador: Usuario – ChocoTumac
 *
 * Maneja autenticación (login/logout), CRUD de usuarios,
 * gestión de perfil y cambio de contraseña.
 *
 * Seguridad:
 *   - Verifica sesión activa en todas las acciones protegidas
 *   - Verifica token CSRF en todas las acciones POST
 *   - Restringe CRUD al rol Administrador (rol_id = 1)
 *
 * @package ChocoTumac
 */

ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/Redirectable.php';

define('BASE_URL', '/chocoTumac/');

/** Mensaje reutilizable para acceso denegado (php:S1192) */
define('MSG_SIN_PERMISOS', 'No tienes permisos.');

class UsuarioController
{
    use Redirectable;

    /** @var Usuario Modelo de usuario */
    private Usuario $model;

    public function __construct()
    {
        $this->model = new Usuario();
    }

    // ── Helpers de seguridad ─────────────────────────────────────────

    /**
     * Verifica si el usuario autenticado es Administrador (rol_id = 1).
     */
    private function esAdmin(): bool
    {
        return isset($_SESSION['user']) && $_SESSION['user']['rol_id'] == 1;
    }

    /**
     * Verifica que el usuario sea admin; redirige al dashboard si no lo es.
     */
    private function requerirAdmin(): void
    {
        if (!$this->esAdmin()) {
            $this->redirectError('dashboard', MSG_SIN_PERMISOS);
        }
    }

    /**
     * Valida el token CSRF. Redirige con error si no coincide.
     */
    private function verificarCSRF(): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->redirectError('dashboard', 'Petición no válida. Recarga la página.');
        }
    }

    /**
     * Obtiene un usuario por ID; redirige con error si no existe.
     *
     * @param int    $id   ID del usuario a buscar.
     * @param string $dest Vista de destino en caso de error.
     * @return array Datos del usuario encontrado.
     */
    private function obtenerUsuarioOError(int $id, string $dest = 'dashboard'): array
    {
        $usuario = $this->model->obtenerPorId($id);
        if (!$usuario) {
            $this->redirectError($dest, 'Usuario no encontrado.');
        }
        return $usuario;
    }

    /**
     * Verifica que el usuario no sea otro administrador distinto al actual.
     * Redirige con error si intenta operar sobre otro admin.
     *
     * @param array  $usuario Datos del usuario objetivo.
     * @param string $msg     Mensaje de error a mostrar.
     */
    private function protegerOtroAdmin(array $usuario, string $msg): void
    {
        if ($usuario['rol_id'] == 1 && $usuario['id'] != $_SESSION['user']['id']) {
            $this->redirectError('dashboard', $msg);
        }
    }

    // ── Punto de entrada ─────────────────────────────────────────────

    /**
     * Lee 'action' de la URL y delega a cada método privado.
     * Complejidad cognitiva: 3 (if + switch + default).
     */
    public function ejecutar(): void
    {
        $accion = $_GET['action'] ?? '';

        if ($accion !== 'login' && !isset($_SESSION['user'])) {
            $this->redirectError('login', 'Tu sesión ha expirado.');
        }

        switch ($accion) {
            case 'crear':           $this->crear();           break;
            case 'editar':          $this->editar();          break;
            case 'actualizar':      $this->actualizar();      break;
            case 'eliminar':        $this->eliminar();        break;
            case 'login':           $this->login();           break;
            case 'logout':          $this->logout();          break;
            case 'actualizarPerfil':$this->actualizarPerfil();break;
            case 'cambiarPassword': $this->cambiarPassword(); break;
            default:
                $this->redirectError('dashboard', 'Acción no reconocida.');
                break;
        }
    }

    // ── Acciones CRUD ────────────────────────────────────────────────

    /**
     * Crea un nuevo usuario. Solo administradores.
     */
    private function crear(): void
    {
        $this->requerirAdmin();
        $this->verificarCSRF();

        $res = $this->model->crear(
            $_POST['nombre']   ?? '',
            $_POST['email']    ?? '',
            $_POST['password'] ?? '',
            (int)($_POST['rol_id']   ?? 0),
            $_POST['telefono'] ?? ''
        );

        $res === true
            ? $this->redirectOk('dashboard', 'creado')
            : $this->redirectError('dashboard', $res);
    }

    /**
     * Carga la vista de edición de un usuario. Solo administradores.
     */
    private function editar(): void
    {
        $this->requerirAdmin();
        $usuario = $this->obtenerUsuarioOError((int)($_GET['id'] ?? 0));
        require_once __DIR__ . '/../views/editar_usuario.php';
    }

    /**
     * Actualiza los datos de un usuario. Solo administradores.
     * No permite modificar la cuenta de otro administrador.
     */
    private function actualizar(): void
    {
        $this->requerirAdmin();
        $this->verificarCSRF();

        $usuario = $this->obtenerUsuarioOError((int)($_POST['id'] ?? 0));
        $this->protegerOtroAdmin($usuario, 'No puedes modificar la cuenta de otro administrador.');

        $res = $this->model->actualizar(
            (int)$_POST['id'],
            $_POST['nombre']   ?? '',
            $_POST['email']    ?? '',
            (int)($_POST['rol_id']   ?? 0),
            $_POST['telefono'] ?? ''
        );

        $res === true
            ? $this->redirectOk('dashboard', 'actualizado')
            : $this->redirectError('dashboard', $res);
    }

    /**
     * Elimina un usuario. Solo administradores.
     * No permite eliminar otro admin ni la propia cuenta.
     */
    private function eliminar(): void
    {
        $this->requerirAdmin();

        $usuario = $this->obtenerUsuarioOError((int)($_GET['id'] ?? 0));
        $this->protegerOtroAdmin($usuario, 'No puedes eliminar a otro administrador.');

        if ($usuario['id'] == $_SESSION['user']['id']) {
            $this->redirectError('dashboard', 'No puedes eliminar tu propia cuenta.');
        }

        $this->model->eliminar((int)$_GET['id']);
        $this->redirectOk('dashboard', 'eliminado');
    }

    // ── Autenticación ────────────────────────────────────────────────

    /**
     * Autentica al usuario con email y password.
     */
    private function login(): void
    {
        $user = $this->model->login($_POST['email'] ?? '', $_POST['password'] ?? '');

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            $this->redirect('index.php?view=dashboard');
        } else {
            $this->redirect('index.php?error=login');
        }
    }

    /**
     * Cierra la sesión activa y redirige al login.
     */
    private function logout(): void
    {
        session_unset();
        session_destroy();
        $this->redirect('index.php');
    }

    // ── Perfil ───────────────────────────────────────────────────────

    /**
     * Actualiza nombre, email y teléfono del perfil del usuario actual.
     */
    private function actualizarPerfil(): void
    {
        $this->verificarCSRF();

        if ((int)$_POST['id'] !== (int)$_SESSION['user']['id']) {
            $this->redirectError('perfil', 'No puedes modificar el perfil de otro usuario.');
        }

        $res = $this->model->actualizar(
            (int)$_POST['id'],
            $_POST['nombre']   ?? '',
            $_POST['email']    ?? '',
            (int)$_SESSION['user']['rol_id'],
            $_POST['telefono'] ?? ''
        );

        if ($res === true) {
            $_SESSION['user']['nombre']   = trim($_POST['nombre']);
            $_SESSION['user']['email']    = strtolower(trim($_POST['email']));
            $_SESSION['user']['telefono'] = trim($_POST['telefono']);
            $this->redirectOk('perfil', 'ok');
        } else {
            $this->redirectError('perfil', $res);
        }
    }

    /**
     * Cambia la contraseña del usuario actual tras verificar la actual.
     */
    private function cambiarPassword(): void
    {
        $this->verificarCSRF();

        if ((int)$_POST['id'] !== (int)$_SESSION['user']['id']) {
            $this->redirectError('perfil', 'Acción no permitida.');
        }

        $user = $this->model->obtenerConPassword((int)$_POST['id']);

        if (!password_verify($_POST['actual'] ?? '', $user['password'])) {
            $this->redirectError('perfil', 'La contraseña actual no es correcta.');
        }

        $nueva = $_POST['nueva'] ?? '';
        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $nueva)) {
            $this->redirectError('perfil', 'La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.');
        }

        $this->model->actualizarPassword(
            (int)$_POST['id'],
            password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12])
        );

        $this->redirectOk('perfil', 'pass');
    }
}

(new UsuarioController())->ejecutar();
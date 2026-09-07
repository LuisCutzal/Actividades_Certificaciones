<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\PermisosTable;
use Application\Model\RolCarreraTable;
use Application\Model\UserTable;
use Laminas\Session\Container;
use Application\Model\User;
use Application\Model\RolTable;
use Application\Model\TipoTable;
use Application\Model\RolesPermisosTable;

class AuthService
{
    private UserTable $userTable; // Para consultar usuarios en la BD
    private Container $session; // Contenedor de sesión "user"
    private PermisosTable $permisosTable; // Para consultar permisos de roles
    private RolCarreraTable $rolCarreraTable; // Para consultar carreras asociadas a roles
    private RolTable $rolTable; // Para consultar información de roles
    private TipoTable $tipoTable; // Para consultar información de tipos
    private RolesPermisosTable $rolesPermisosTable; // Para consultar permisos de roles
    public function __construct(UserTable $userTable, PermisosTable $permisosTable, RolCarreraTable $rolCarreraTable, RolTable $rolTable, TipoTable $tipoTable, RolesPermisosTable $rolesPermisosTable)
    {
        // Recibir la dependencia UserTable (inyectada por la Factory)
        $this->userTable = $userTable;
        // Recibir la dependencia PermisosTable (inyectada por la Factory)
        $this->permisosTable = $permisosTable;
        // Recibir la dependencia RolCarreraTable (inyectada por la Factory)
        $this->rolCarreraTable = $rolCarreraTable;
        // Recibir la dependencia RolTable (inyectada por la Factory)
        $this->rolTable = $rolTable;
        // Recibir la dependencia TipoTable (inyectada por la Factory)
        $this->tipoTable = $tipoTable;
        // Recibir la dependencia RolesPermisosTable (inyectada por la Factory)
        $this->rolesPermisosTable = $rolesPermisosTable;
        // Crear contenedor de sesión donde se guardarán los datos del usuario

        $this->session = new Container('user');
    }

    //Verifica usuario y contraseña, y luego crea la sesión.
    public function login(string $login, string $password): bool
    {
        // Obtener usuario desde la BD por username, correo, carnet, cui, registro personal (registro de empleado)
        //$user = $this->userTable->getUserByUsername($username);
        $user = $this->userTable->getUserByLogin($login);
        // Si no existe el usuario, login falla
        if (!$user) {
            return false;
        }
        // Verificar contraseña usando bcrypt (password_hash)
        if (!password_verify($password, $user->password)) {
            return false;
        }

        // 2. Verificar si está activo
        if ((int)$user->estado === 0) {
            // Usuario inactivo → no puede iniciar sesión
            return false;
        }
        // var_dump($user->extension); // Agrega esta línea para depuración
        //         exit();
        if (!isset($user->extension)) {
            throw new \Exception('El usuario no tiene una extensión asignada.');
        }

        $permisos = $this->permisosTable->getPermisosByRol($user->rol_id);
        // var_dump($permisos);
        // exit;
        $RolesPermisosTable = $this->rolesPermisosTable->getPermisosByRolId($user->rol_id);

        $carreras = $this->rolCarreraTable->getCarrerasByRol($user->rol_id);

        $rol = $this->rolTable->getRolConTipo($user->rol_id, $this->tipoTable);

        // Guardar datos de sesión mínimos
        $this->session->userId     = $user->id;
        $this->session->nombre   = $user->nombre;
        $this->session->apellido   = $user->apellido;
        $this->session->rol_id     = $user->rol_id;
        //$this->session->rol_nombre = $rol['nombre'] ?? '';
        //$this->session->sidebar = $rol['sidebar'];
        $this->session->extension  = $user->extension;

        $this->session->tipo_id    = $rol['tipo_id'] ?? null;
        $this->session->permisos   = $permisos ?? [];
        $this->session->carreras   = $carreras ?? [];

        $this->session->codigo_Carrera    = $user->codigo_Carrera ?? null;
        $this->session->cui = $user->cui ?? null;

        // Opcional: guardar datos completos
        $this->session->user_data = [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'email' => $user->correo,
            'rol_id' => $user->rol_id,
            'extension' => $user->extension,
            'codigo_Carrera' => $user->codigo_Carrera ?? null,
            'carreras' => $carreras,
            'permisos' => $permisos,
            'roles_permisos' => $RolesPermisosTable,
            'cui' => $this->session->cui ?? null,
        ];

        return true;
    }

    /**
     * Destruye toda la sesión del usuario.
     */
    public function logout(): void
    {
        $this->session->getManager()->destroy();  // Destruye la sesión actual completamente
    }

    /**
     *  Se comprueba simplemente si existe el userId en la sesión.
     */
    public function isLogged(): bool
    {
        return isset($this->session->userId);
    }

    /**
     * Obtener usuario actual
     *  Retorna un objeto User a partir de los datos guardados en sesión.
     */
    public function getCurrentUser(): ?User
    {
        if (!isset($this->session->userId)) {
            return null;
        }

        $user = new User();
        $user->exchangeArray([
            'id' => $this->session->userId,
            'nombre' => $this->session->nombre,
            'apellido' => $this->session->apellido,
            'cui' => $this->session->cui ?? null,
            'correo' => $this->session->correo ?? null,
            'rol_id' => $this->session->rol_id,
            'tipo_id' => $this->session->tipo_id ?? null,
            'extension' => $this->session->extension,
            'codigo_Carrera' => $this->session->codigo_Carrera ?? null,
        ]);

        return $user;
    }

    /**
     * Verificar rol
     *  Comprueba si el usuario está logueado y si su rol coincide.
     */
    public function hasRole(string $rol_id): bool
    {
        return $this->isLogged() && $this->session->rol_id === $rol_id;
    }

    /**
     * Forgot password
     * Buscar usuario por correo para enviar email de recuperación.
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userTable->getUserByCorreo($email);
    }
    //Llama al método del modelo para guardar la contraseña en la BD.
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->userTable->updatePassword($userId, $hashedPassword);
    }

    public function getUserIfExists(string $login): ?User
    {
        return $this->userTable->getUserByLogin($login);
    }
}

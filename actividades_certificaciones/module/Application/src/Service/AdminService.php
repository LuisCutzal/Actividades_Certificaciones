<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Service\UserService;
use Application\Model\PermisosTable;
use Application\Model\RolTable;
use Application\Model\RolesPermisosTable;
use Application\Model\RolCarreraTable;
use Application\Model\CarreraTable;
use Application\Model\TipoTable;
use Application\Model\User;

class AdminService
{
    private UserService $userService;
    private PermisosTable $permisosTable;
    private RolTable $rolTable;
    private RolesPermisosTable $rolesPermisosTable;
    private RolCarreraTable $rolCarreraTable;
    private CarreraTable $carreraTable;
    private TipoTable $tipoTable;

    public function __construct(UserService $userService, PermisosTable $permisosTable, RolTable $rolTable, RolesPermisosTable $rolesPermisosTable, RolCarreraTable $rolCarreraTable, CarreraTable $carreraTable, TipoTable $tipoTable)
    {
        $this->userService = $userService;
        $this->permisosTable = $permisosTable;
        $this->rolTable = $rolTable;
        $this->rolesPermisosTable = $rolesPermisosTable;
        $this->rolCarreraTable = $rolCarreraTable;
        $this->carreraTable = $carreraTable;
        $this->tipoTable = $tipoTable;
    }

    /* Crea un usuario nuevo por un administrador   */
    public function createUserAsAdmin(array $data): int
    {
        // Validaciones específicas solo para admins
        return $this->userService->createUser($data);
    }

    public function obtenerUsuarios(): array
    {
        // Validaciones específicas solo para admins
        return $this->userService->listarUsuarios();
    }

    public function buscarUsuarios(string $query): array
    {
        return $this->userService->buscarUsuario($query);
    }

    public function obtenerUsuarioPorId(int $id)
    {
        return $this->userService->obtenerUsuarioPorId($id);
    }

    public function editUserAsAdmin(array $data): int
    {
        return $this->userService->editUser($data);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userService->getUserById($id);
    }

    public function eliminarUsuarioAsAdmin(int $id): void
    {
        $this->userService->eliminarUsuario($id);
    }

    public function activarUsuarioAsAdmin(int $id): void
    {
        $this->userService->activarUsuario($id);
    }

    //para los roles

    public function getPermisos()
    {
        return $this->permisosTable->fetchAll();
    }
    public function getCarreras()
    {
        return $this->carreraTable->fetchByIds([1, 2]); //esto es para verificar que carreras son las que debe buscar
        //en este caso son solo arquitectura y diseño grafico, si se necesitan mas carreras
        //solo es necesario agregar los ids de las carreras
    }

    public function obtenerRoles()
    {
        return $this->rolTable->getRol();
    }


    public function buscarRoles(string $query): array
    {
        return $this->rolTable->buscarRol($query);
    }

    public function crearRol(
        string $nombre,
        array $permisos,
        int $tipoId,
        int $extension,
        array $carreras = []
    ) {

        $permisos = array_map('intval', $permisos);

        $rolId = $this->rolTable->crearRol([
            'nombre' => $nombre,
            'tipo_id' => $tipoId,
            'extension' => $extension
        ]);

        foreach ($permisos as $permisoId) {

            $this->rolesPermisosTable->asignarPermiso([
                'rol_id' => $rolId,
                'permiso_id' => $permisoId
            ]);
        }

        foreach ($carreras as $carreraId) {

            $this->rolCarreraTable->insert([
                'rol_id' => $rolId,
                'carrera_id' => (int)$carreraId
            ]);
        }
    }


    public function obtenerRolPorId(int $id)
    {
        return $this->rolTable->getRolById($id);
    }

    public function modificarRol(int $id, string $nombre, int $extension, array $permisos, array $carreras)
    {
        // actualizar nombre
        $this->rolTable->updateRol($id, [
            'nombre' => $nombre,
            'extension' => $extension
        ]);

        // borrar permisos actuales
        $this->rolesPermisosTable->deleteByRol($id);

        // insertar de nuevo los permisos (ya sea nuevos o anteriores)
        foreach ($permisos as $permisoId) {

            $this->rolesPermisosTable->asignarPermiso([
                'rol_id' => $id,
                'permiso_id' => $permisoId
            ]);
        }
        // BORRAR carreras actuales
        $this->rolCarreraTable
            ->deleteByRol($id);

        // INSERTAR de nuevo las carreras (ya sea nuevos o anteriores)
        foreach ($carreras as $carreraId) {

            $this->rolCarreraTable->insert([
                'rol_id' => $id,
                'carrera_id' => $carreraId
            ]);
        }
    }

    public function getTipos()
    {
        return $this->tipoTable->fetchAll();
    }

    public function getPermisosPorRol(int $rolId): array
    {
        return $this->rolesPermisosTable
            ->getPermisosByRolId($rolId);
    }

    public function getCarrerasPorRol(int $rolId): array
    {
        return $this->rolCarreraTable
            ->getCarrerasByRol($rolId);
    }
}

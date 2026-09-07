<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Service\UserService;
use Application\Model\PermisosTable;
use Application\Model\RolTable;
use Application\Model\RolesPermisosTable;
use Application\Model\RolCarreraTable;
use Application\Model\CarreraTable;
use Application\Model\TipoTable;
use Application\Model\User;

use Application\Service\AdminService;

class AdminServiceTest extends TestCase
{
    private UserService $userService;
    private PermisosTable $permisosTable;
    private RolTable $rolTable;
    private RolesPermisosTable $rolesPermisosTable;
    private RolCarreraTable $rolCarreraTable;
    private CarreraTable $carreraTable;
    private TipoTable $tipoTable;
    private User $user;

    private AdminService $adminService;

    protected function setUp(): void
    {

        $this->userService = $this->createMock(UserService::class);
        $this->permisosTable = $this->createMock(PermisosTable::class);
        $this->rolTable = $this->createMock(RolTable::class);
        $this->rolesPermisosTable = $this->createMock(RolesPermisosTable::class);
        $this->rolCarreraTable = $this->createMock(RolCarreraTable::class);
        $this->carreraTable = $this->createMock(CarreraTable::class);
        $this->tipoTable = $this->createMock(TipoTable::class);
        $this->user = $this->createMock(User::class);

        $this->adminService = new AdminService(
            $this->userService,
            $this->permisosTable,
            $this->rolTable,
            $this->rolesPermisosTable,
            $this->rolCarreraTable,
            $this->carreraTable,
            $this->tipoTable,
        );
    }

    private function makeUserData(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Luis',
            'apellido' => 'Cutzal',
            'correo' => 'luis@example.com',
            'password' => 'password123',
            'cui' => 123456789111,
            'carnet' => 201700841,
            'carrera' => 1,
            'rol' => 4,
            'tipo_id' => 4,
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //createUserAsAdmin
    public function testCreateUserAsAdmin(): void
    {
        $usuario = $this->makeUserData();

        // id que devolvera el servicio interno
        $expectedId = 15;

        // verifica que se llame createUser con los datos recibidos
        $this->userService->expects($this->once())
            ->method('createUser')
            ->with($usuario)
            ->willReturn($expectedId);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->createUserAsAdmin($usuario);

        // verifica que se devuelva el mismo id generado
        $this->assertSame($expectedId, $result);
    }

    //obtenerUsuarios

    public function testObtenerUsuarios(): void
    {
        $usuarios = [
            $this->makeUserData(),
            $this->makeUserData([
                'nombre' => 'Juan',
                'correo' => 'juan@example.com',
                'carnet' => 201700842,
                'cui' => 123456789112,
                'rol' => 1,
                'tipo_id' => 4,
                'carrera' => 1,
            ]),
        ];

        // verifica que se obtenga el listado de usuarios
        $this->userService->expects($this->once())
            ->method('listarUsuarios')
            ->willReturn($usuarios);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->obtenerUsuarios();

        // verifica que se devuelva el listado obtenido
        $this->assertSame($usuarios, $result);
    }

    //buscarUsuarios

    public function testBuscarUsuarios(): void
    {
        $query = 'Luis';

        $usuarios = [
            $this->makeUserData(),
        ];

        // verifica que la busqueda se realice con el texto recibido
        $this->userService->expects($this->once())
            ->method('buscarUsuario')
            ->with($query)
            ->willReturn($usuarios);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->buscarUsuarios($query);

        // verifica que se devuelva el resultado de la busqueda
        $this->assertSame($usuarios, $result);
    }

    //editUserAsAdmin

    public function testEditUserAsAdmin(): void
    {
        $usuario = $this->makeUserData();

        // id que devolvera el servicio interno
        $expectedId = 15;

        // verifica que se llame editUser con los datos recibidos
        $this->userService->expects($this->once())
            ->method('editUser')
            ->with($usuario)
            ->willReturn($expectedId);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->editUserAsAdmin($usuario);

        // verifica que se devuelva el resultado obtenido
        $this->assertSame($expectedId, $result);
    }

    //getUserById

    public function testGetUserById(): void
    {
        $id = 1;
        // verifica que se consulte el usuario utilizando el id recibido
        $this->userService->expects($this->once())
            ->method('getUserById')
            ->with($id)
            ->willReturn($this->user);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->getUserById($id);

        // verifica que se devuelva el usuario obtenido
        $this->assertSame($this->user, $result);
    }

    //eliminarUsuarioAsAdmin

    public function testEliminarUsuarioAsAdmin(): void
    {
        $id = 1;

        // verifica que se elimine el usuario utilizando el id recibido
        $this->userService->expects($this->once())
            ->method('eliminarUsuario')
            ->with($id);

        // ejecuta el metodo bajo prueba
        $this->adminService->eliminarUsuarioAsAdmin($id);
    }

    //activarUsuarioAsAdmin

    public function testActivarUsuarioAsAdmin(): void
    {
        $id = 1;

        // verifica que se active el usuario utilizando el id recibido
        $this->userService->expects($this->once())
            ->method('activarUsuario')
            ->with($id);

        // ejecuta el metodo bajo prueba
        $this->adminService->activarUsuarioAsAdmin($id);
    }

    private function makePermisoData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'nombre' => 'crear_actividad',
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //getPermisos
    public function testGetPermisos(): void
    {
        $permisos = $this->makePermisoData(
            (['id' => 2, 'nombre' => 'crear_usuario']) //permisos de ejemplo
        );

        // verifica que se obtengan todos los permisos
        $this->permisosTable->expects($this->once())
            ->method('fetchAll')
            ->willReturn($permisos);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->getPermisos();

        // verifica que se devuelva el listado obtenido
        $this->assertSame($permisos, $result);
    }

    private function makeCarreraData(array $overrides = []): array
    {
        return array_merge([
            'carrera' => 1,
            'nombre' => 'Licenciatura en Arquitecutura',
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //getCarreras

    public function testGetCarreras(): void
    {
        $carreras = $this->makeCarreraData(
            (['carrera' => 3, 'nombre' => 'Licendicatura en Diseño Grafico']) //carrera de ejemplo
        );

        // verifica que se consulten las carreras permitidas
        $this->carreraTable->expects($this->once())
            ->method('fetchByIds')
            ->with([1, 3])
            ->willReturn($carreras);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->getCarreras();

        // verifica que se devuelva el listado obtenido
        $this->assertSame($carreras, $result);
    }

    private function makeRolesData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'nombre' => 'Administrador',
            'tipo_id' => 1,
            'extension' => 1,
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //obtenerRoles

    public function testObtenerRoles(): void
    {

        $roles = $this->makeRolesData(
            (['id' => 2, 'nombre' => 'Personal administrativo', 'tipo_id' => 2, 'extension' => 1])
        );

        // verifica que se obtenga el listado de roles
        $this->rolTable->expects($this->once())
            ->method('getRol')
            ->willReturn($roles);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->obtenerRoles();

        // verifica que se devuelva el listado obtenido
        $this->assertSame($roles, $result);
    }

    //buscarRoles

    public function testBuscarRoles(): void
    {
        $query = 'Admin';

        $roles = $this->makeRolesData();

        // verifica que la busqueda se realice con el texto recibido
        $this->rolTable->expects($this->once())
            ->method('buscarRol')
            ->with($query)
            ->willReturn($roles);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->buscarRoles($query);

        // verifica que se devuelva el resultado de la busqueda
        $this->assertSame($roles, $result);
    }

    //obtenerRolPorId

    public function testObtenerRolPorId(): void
    {
        $id = 1;

        $rol = $this->makeRolesData();

        // verifica que se consulte el rol utilizando el id recibido
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with($id)
            ->willReturn($rol);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->obtenerRolPorId($id);

        // verifica que se devuelva el rol obtenido
        $this->assertSame($rol, $result);
    }

    private function makeTipoData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'nombre' => 'Administrador',
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //getTipos
    public function testGetTipos(): void
    {
        $tipos = $this->makeTipoData(
            (['id' => 2, 'nombre' => 'Personal'])
        );

        // verifica que se obtengan todos los tipos
        $this->tipoTable->expects($this->once())
            ->method('fetchAll')
            ->willReturn($tipos);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->getTipos();

        // verifica que se devuelva el listado obtenido
        $this->assertSame($tipos, $result);
    }

    public function testGetPermisosPorRol(): void
    {
        $rolId = 1;

        $permisos = $this->makePermisoData(
            (['id' => 2, 'nombre' => 'crear_usuario']) //permisos de ejemplo
        );

        // verifica que se consulten los permisos del rol recibido
        $this->rolesPermisosTable->expects($this->once())
            ->method('getPermisosByRolId')
            ->with($rolId)
            ->willReturn($permisos);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->getPermisosPorRol($rolId);

        // verifica que se devuelvan los permisos obtenidos
        $this->assertSame($permisos, $result);
    }

    //getCarrerasPorRol

    public function testGetCarrerasPorRol(): void
    {
        $rolId = 1;

        $carreras = $this->makeCarreraData(
            (['carrera' => 3, 'nombre' => 'Licendicatura en Diseño Grafico']) //carrera de ejemplo
        );

        // verifica que se consulten las carreras del rol recibido
        $this->rolCarreraTable->expects($this->once())
            ->method('getCarrerasByRol')
            ->with($rolId)
            ->willReturn($carreras);

        // ejecuta el metodo bajo prueba
        $result = $this->adminService->getCarrerasPorRol($rolId);

        // verifica que se devuelvan las carreras obtenidas
        $this->assertSame($carreras, $result);
    }

    //crearRol
    public function testCrearRol(): void
    {
        $nombre = 'Administrador';

        $permisos = [1, 2];

        $tipoId = 1;

        $extension = 0;

        $carreras = [1, 3];

        $rolId = 10;

        // verifica que se cree el rol con los datos recibidos
        $this->rolTable->expects($this->once())
            ->method('crearRol')
            ->with([
                'nombre' => $nombre,
                'tipo_id' => $tipoId,
                'extension' => $extension,
            ])
            ->willReturn($rolId);

        $permisosAsignados = [];

        // guarda cada permiso asignado para verificarlo despues
        $this->rolesPermisosTable->expects($this->exactly(2))
            ->method('asignarPermiso')
            ->willReturnCallback(
                function (array $data) use (&$permisosAsignados): int {
                    $permisosAsignados[] = $data;

                    return 1;
                }
            );

        $carrerasAsignadas = [];

        // guarda cada carrera asignada para verificarla despues
        $this->rolCarreraTable->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(
                function (array $data) use (&$carrerasAsignadas): int {
                    $carrerasAsignadas[] = $data;

                    return 1;
                }
            );

        // ejecuta el metodo bajo prueba
        $this->adminService->crearRol(
            $nombre,
            $permisos,
            $tipoId,
            $extension,
            $carreras
        );

        // verifica los permisos asociados al rol creado
        $this->assertSame([
            [
                'rol_id' => $rolId,
                'permiso_id' => 1,
            ],
            [
                'rol_id' => $rolId,
                'permiso_id' => 2,
            ],
        ], $permisosAsignados);

        // verifica las carreras asociadas al rol creado
        $this->assertSame([
            [
                'rol_id' => $rolId,
                'carrera_id' => 1,
            ],
            [
                'rol_id' => $rolId,
                'carrera_id' => 3,
            ],
        ], $carrerasAsignadas);
    }

    public function testCrearRolConviertePermisosAEnteros(): void
    {
        $nombre = 'Administrador';

        // permisos como strings (caso real de entrada)
        $permisos = ['1', '2'];

        $tipoId = 1;

        $extension = 0;

        $carreras = [];

        $rolId = 10;

        $this->rolTable->expects($this->once())
            ->method('crearRol')
            ->willReturn($rolId);

        $permisosRecibidos = [];

        // captura lo que realmente se envia a asignarPermiso
        $this->rolesPermisosTable->expects($this->exactly(2))
            ->method('asignarPermiso')
            ->willReturnCallback(
                function (array $data) use (&$permisosRecibidos): int {
                    $permisosRecibidos[] = $data['permiso_id'];

                    return 1;
                }
            );

        $this->rolCarreraTable->expects($this->never())
            ->method('insert');

        // ejecuta el metodo bajo prueba
        $this->adminService->crearRol(
            $nombre,
            $permisos,
            $tipoId,
            $extension,
            $carreras
        );

        // verifica que los permisos fueron convertidos a enteros
        $this->assertSame([1, 2], $permisosRecibidos);
    }

    //modificarRol

    public function testModificarRol(): void
    {
        $id = 1;
        $nombre = 'Administrador';
        $extension = 0;

        $permisos = [1, 2];
        $carreras = [1, 3];

        // verifica que se actualicen los datos del rol
        $this->rolTable->expects($this->once())
            ->method('updateRol')
            ->with($id, [
                'nombre' => $nombre,
                'extension' => $extension,
            ]);

        // elimina permisos existentes del rol
        $this->rolesPermisosTable->expects($this->once())
            ->method('deleteByRol')
            ->with($id);

        $permisosRecibidos = [];

        // inserta nuevamente los permisos
        $this->rolesPermisosTable->expects($this->exactly(2))
            ->method('asignarPermiso')
            ->willReturnCallback(
                function (array $data) use (&$permisosRecibidos): int {
                    $permisosRecibidos[] = $data;

                    return 1;
                }
            );

        // elimina carreras existentes del rol
        $this->rolCarreraTable->expects($this->once())
            ->method('deleteByRol')
            ->with($id);

        $carrerasRecibidas = [];

        // inserta nuevamente las carreras
        $this->rolCarreraTable->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(
                function (array $data) use (&$carrerasRecibidas): int {
                    $carrerasRecibidas[] = $data;

                    return 1;
                }
            );

        // ejecuta el metodo bajo prueba
        $this->adminService->modificarRol(
            $id,
            $nombre,
            $extension,
            $permisos,
            $carreras
        );

        // verifica permisos reconstruidos
        $this->assertSame([
            [
                'rol_id' => $id,
                'permiso_id' => 1,
            ],
            [
                'rol_id' => $id,
                'permiso_id' => 2,
            ],
        ], $permisosRecibidos);

        // verifica carreras reconstruidas
        $this->assertSame([
            [
                'rol_id' => $id,
                'carrera_id' => 1,
            ],
            [
                'rol_id' => $id,
                'carrera_id' => 3,
            ],
        ], $carrerasRecibidas);
    }

    public function testModificarRolSinPermisos(): void
    {
        $id = 1;
        $nombre = 'Administrador';
        $extension = 0;

        $permisos = []; // no tiene permiso
        $carreras = [1, 3];

        // actualiza el rol
        $this->rolTable->expects($this->once())
            ->method('updateRol');

        // elimina permisos anteriores
        $this->rolesPermisosTable->expects($this->once())
            ->method('deleteByRol')
            ->with($id);

        // no debe intentar insertar permisos nuevos
        $this->rolesPermisosTable->expects($this->never())
            ->method('asignarPermiso');

        // elimina carreras anteriores
        $this->rolCarreraTable->expects($this->once())
            ->method('deleteByRol')
            ->with($id);

        $carrerasRecibidas = [];

        // inserta carreras nuevas normalmente
        $this->rolCarreraTable->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(
                function (array $data) use (&$carrerasRecibidas): int {
                    $carrerasRecibidas[] = $data;

                    return 1;
                }
            );

        // ejecuta el metodo bajo prueba
        $this->adminService->modificarRol(
            $id,
            $nombre,
            $extension,
            $permisos,
            $carreras
        );

        // valida carreras reconstruidas
        $this->assertSame([
            [
                'rol_id' => $id,
                'carrera_id' => 1,
            ],
            [
                'rol_id' => $id,
                'carrera_id' => 3,
            ],
        ], $carrerasRecibidas);
    }

    public function testModificarRolSinCarreras(): void
    {
        $id = 1;
        $nombre = 'Administrador';
        $extension = 0;

        $permisos = [1, 2];
        $carreras = []; // caso sin carreras

        // actualiza el rol
        $this->rolTable->expects($this->once())
            ->method('updateRol');

        // elimina permisos anteriores
        $this->rolesPermisosTable->expects($this->once())
            ->method('deleteByRol')
            ->with($id);

        // inserta nuevamente permisos
        $this->rolesPermisosTable->expects($this->exactly(2))
            ->method('asignarPermiso')
            ->willReturnCallback(
                function (array $data): int {
                    return 1;
                }
            );

        // elimina carreras anteriores
        $this->rolCarreraTable->expects($this->once())
            ->method('deleteByRol')
            ->with($id);

        // no debe insertar nuevas carreras
        $this->rolCarreraTable->expects($this->never())
            ->method('insert');

        // ejecuta el metodo bajo prueba
        $this->adminService->modificarRol(
            $id,
            $nombre,
            $extension,
            $permisos,
            $carreras
        );
    }
}

/*

vendor/bin/phpunit module/Application/test/Service/AdminServiceTest.php

*/
<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Model\UserTable;
use Application\Model\User;
use Laminas\Session\Container;
use Application\Model\RolTable;
use Application\Model\RolCarreraTable;
use Application\Model\StudentTable;
use Application\Model\InscripcionTable;
use Application\Service\AuditService;
use Application\Model\ExtensionTable;
use Application\Model\Student;

use Application\Service\UserService;


class UserServiceTest extends TestCase
{
    private UserTable $userTable;
    private User $user;
    private RolTable $rolTable;
    private RolCarreraTable $rolCarreraTable;
    private StudentTable $studentTable;
    private InscripcionTable $inscripcionTable;
    private AuditService $auditService;
    private ExtensionTable $extensionTable;
    private UserService $userService;
    private Student $student;

    protected function setUp(): void
    {
        $this->userTable = $this->createMock(UserTable::class);
        $this->user = $this->createMock(User::class);
        $this->rolTable = $this->createMock(RolTable::class);
        $this->rolCarreraTable = $this->createMock(RolCarreraTable::class);
        $this->studentTable = $this->createMock(StudentTable::class);
        $this->inscripcionTable = $this->createMock(InscripcionTable::class);
        $this->auditService = $this->createMock(AuditService::class);
        $this->extensionTable = $this->createMock(ExtensionTable::class);
        $this->student = $this->createMock(Student::class);

        $this->userService = new UserService(
            $this->userTable,
            $this->rolTable,
            $this->studentTable,
            $this->inscripcionTable,
            $this->auditService,
            $this->extensionTable,
            $this->rolCarreraTable,
        );
    }

    private function makeUserData(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Luis',
            'apellido' => 'Cutzal',
            'password' => 'Password123',
            'rol_id' => 2,
            'correo' => 'cutzalluis@gmail.com',
            'cui' => '1234567890123',
            'carnet' => '201700841',
            'registro_personal' => '',
            'tipo_id' => 2,
        ], $overrides); // para sobreescribir datos o agregar nuevos datos si es necesario
    }

    //createUser

    public function testCreateUserExitoso(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // crea un estudiante academico valido
        //$estudiante = new \stdClass();
        $this->student->dpi = "1234567890123";
        //$estudiante->dpi = '1234567890123';

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el registro personal no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->willReturn(null);

        // simula que el estudiante existe en el sistema academico
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with('201700841')
            ->willReturn($this->student);

        // simula que el estudiante tiene una carrera activa
        $this->inscripcionTable
            ->expects($this->once())
            ->method('obtenerCarreraActual')
            ->with(201700841)
            ->willReturn([
                [
                    'fecha_cierre' => null
                ]
            ]);

        // simula que el rol tiene un tipo valido
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(2);

        // simula la extension asociada al rol
        $this->rolTable
            ->expects($this->once())
            ->method('getExtensionByRolId')
            ->with(2)
            ->willReturn(1);

        // verifica que el usuario sea almacenado
        $this->userTable
            ->expects($this->once())
            ->method('saveUser')
            ->with(
                $this->isInstanceOf(User::class)
            )
            ->willReturn(15);

        // ejecuta la funcion que se esta probando
        $resultado = $this->userService->createUser($data);

        // verifica que se retorne el id generado
        $this->assertEquals(15, $resultado);
    }

    public function testCreateUserLanzaExcepcionCuandoRolNoExiste(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // crea un estudiante academico valido
        $this->student->dpi = '1234567890123';

        // simula que el correo no existe
        $this->userTable
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el registro personal no existe
        $this->userTable
            ->method('getUserByRegistroPersonal')
            ->willReturn(null);

        // simula que el estudiante existe en el sistema academico
        $this->studentTable
            ->method('getByCarnet')
            ->willReturn($this->student);

        // simula que el estudiante tiene una carrera activa
        $this->inscripcionTable
            ->method('obtenerCarreraActual')
            ->willReturn([
                [
                    'fecha_cierre' => null
                ]
            ]);

        // simula que el rol no existe
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(null);

        // verifica el mensaje esperado
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Rol inválido.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserSinCarnetNiRegistro(): void
    {
        // define los datos dejando vacio el carnet y el registro personal
        $data = $this->makeUserData([
            'carnet' => '',
            'registroPersonal' => '',
        ]);

        // simula que el correo no existe
        $this->userTable
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el registro personal no existe
        $this->userTable
            ->method('getUserByRegistroPersonal')
            ->willReturn(null);

        // simula que el rol es valido
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->willReturn(2);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Debe ingresar al menos un Carnet o un Registro Personal.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCorreoDuplicado(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // crea un usuario existente con el mismo correo
        $usuarioExistente = new User();

        // simula que el correo ya esta registrado
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->with('cutzalluis@gmail.com')
            ->willReturn($usuarioExistente);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El correo ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCuiDuplicado(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // crea un usuario existente con el mismo cui
        $usuarioExistente = new User();

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui ya esta registrado
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->with('1234567890123')
            ->willReturn($usuarioExistente);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El CUI: 1234567890123 ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCarnetDuplicado(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // crea un usuario existente con el mismo carnet
        $usuarioExistente = new User();

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet ya esta registrado
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->with('201700841')
            ->willReturn($usuarioExistente);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El carnet: 201700841 ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCarnetNoExiste(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe en usuarios
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el carnet no existe en el sistema academico
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with('201700841')
            ->willReturn(null);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El carnet 201700841 no existe en el sistema académico.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCuiNoCoincide(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // simula un estudiante cuyo dpi no coincide con el cui ingresado
        $this->student->dpi = '9999999999999';

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el estudiante existe en el sistema academico
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with('201700841')
            ->willReturn($this->student);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El CUI ingresado no corresponde al carnet 201700841.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserRegistroDuplicado(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData([
            'carnet' => '',
            'registro_personal' => '123456789',
        ]);

        // crea un usuario existente con el mismo registro personal
        $usuarioExistente = new User();

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el registro personal ya existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->with('123456789')
            ->willReturn($usuarioExistente);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El registro personal: 123456789 ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCarnetYRegistro(): void
    {
        // define los datos con carnet y registro personal
        $data = $this->makeUserData([
            'registro_personal' => '12345',
        ]);

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el estudiante existe
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->willReturn($this->student);

        // simula que el estudiante tiene carrera activa
        $this->inscripcionTable
            ->expects($this->once())
            ->method('obtenerCarreraActual')
            ->willReturn([
                [
                    'fecha_cierre' => null
                ]
            ]);

        // simula que el registro personal no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->with('12345')
            ->willReturn(null);


        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(2);
        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'No puede ingresar Carnet y Registro Personal al mismo tiempo.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserSinCarreraActiva(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el estudiante existe en el sistema academico
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with('201700841')
            ->willReturn($this->student);

        // simula que el estudiante no tiene carrera activa
        $this->inscripcionTable
            ->expects($this->once())
            ->method('obtenerCarreraActual')
            ->with(201700841)
            ->willReturn([]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        // ajustar segun el mensaje real del servicio
        $this->expectExceptionMessage(
            'El estudiante con carnet 201700841 no tiene inscripción activa.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserRolAsociacionSinCarrera(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el estudiante existe
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->willReturn($this->student);

        // simula una inscripcion activa
        $this->inscripcionTable
            ->expects($this->once())
            ->method('obtenerCarreraActual')
            ->willReturn([
                [
                    'fecha_cierre' => null
                ]
            ]);

        // simula que el rol es de tipo asociacion
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(4);

        // simula que el rol no tiene carrera asociada
        $this->rolCarreraTable
            ->expects($this->once())
            ->method('getCarreraIdByRolId')
            ->with(2)
            ->willReturn(null);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El rol no tiene carrera asociada.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserNombreVacio(): void
    {
        // define los datos con nombre vacio
        $data = $this->makeUserData([
            'nombre' => '',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El nombre es obligatorio.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserNombreInvalido(): void
    {
        // define los datos con caracteres invalidos
        $data = $this->makeUserData([
            'nombre' => 'Luis123',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El nombre solo puede contener letras.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserApellidoVacio(): void
    {
        // define los datos con apellido vacio
        $data = $this->makeUserData([
            'apellido' => '',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El apellido es obligatorio.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserApellidoInvalido(): void
    {
        // define los datos con caracteres invalidos
        $data = $this->makeUserData([
            'apellido' => 'Cutzal123',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El apellido solo puede contener letras.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCorreoInvalido(): void
    {
        // define los datos con correo invalido
        $data = $this->makeUserData([
            'correo' => 'correo-invalido',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El correo electrónico no tiene un formato válido.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCorreoDominioInvalido(): void
    {
        // define los datos con dominio invalido
        $data = $this->makeUserData([
            'correo' => 'luis@correo',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El correo electrónico no tiene un formato válido.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCuiNoNumerico(): void
    {
        // define los datos con cui invalido
        $data = $this->makeUserData([
            'cui' => 'abc123',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El CUI es obligatorio y solo debe contener números.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCarnetInvalido(): void
    {
        // define los datos con carnet invalido
        $data = $this->makeUserData([
            'carnet' => 'ABC123',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El carnet solo puede contener números.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserRegistroInvalido(): void
    {
        // define los datos con registro personal invalido
        $data = $this->makeUserData([
            'carnet' => '',
            'registro_personal' => 'ABC123',
        ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El registro personal solo puede contener números.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserCarrerasCerradas(): void
    {
        // define los datos de entrada para crear el usuario
        $data = $this->makeUserData();

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el estudiante existe
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->willReturn($this->student);

        // simula que todas las carreras estan cerradas
        $this->inscripcionTable
            ->expects($this->once())
            ->method('obtenerCarreraActual')
            ->willReturn([
                [
                    'fecha_cierre' => '2024-01-01'
                ],
                [
                    'fecha_cierre' => '2025-01-01'
                ]
            ]);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El estudiante ya cerró todas sus carreras.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->createUser($data);
    }

    public function testCreateUserSoloRegistroPersonal(): void
    {
        // define los datos usando registro personal
        $data = $this->makeUserData([
            'carnet' => '',
            'registro_personal' => '12345',
        ]);

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el registro no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->willReturn(null);

        // simula rol valido
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->willReturn(2);

        // simula extension valida
        $this->rolTable
            ->expects($this->once())
            ->method('getExtensionByRolId')
            ->willReturn(1);

        // verifica almacenamiento
        $this->userTable
            ->expects($this->once())
            ->method('saveUser')
            ->willReturn(15);

        // ejecuta la funcion
        $resultado = $this->userService->createUser($data);

        // verifica resultado
        $this->assertEquals(15, $resultado);
    }

    //editUser

    public function testEditUserUsuarioNoExiste(): void
    {
        // define los datos para editar
        $data = [
            'id' => 1,
        ];

        // simula que el usuario no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn(null);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Usuario no encontrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserPasswordVacia(): void
    {
        // crea un usuario existente
        $usuario = new User();

        $usuario->id = 1;
        $usuario->rol_id = 2;

        // define los datos de entrada
        $data = [
            'id' => 1,
            'password' => '',
            'password2' => '',
        ];

        // crea una sesion real para el servicio
        $session = new Container('user');

        // simula un usuario normal
        $session->rol_id = 2;

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula el tipo del rol
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(2);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Debes ingresar una contraseña.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserPasswordsNoCoinciden(): void
    {
        // crea un usuario existente
        $usuario = new User();

        $usuario->id = 1;
        $usuario->rol_id = 2;

        // define los datos de entrada
        $data = [
            'id' => 1,
            'password' => 'Password123',
            'password2' => 'Password456',
        ];

        // crea una sesion real para el servicio
        $session = new Container('user');

        // simula un usuario normal
        $session->rol_id = 2;

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula el tipo del rol
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(2);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Las contraseñas no coinciden.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    private function makeUser(array $overrides = []): User
    {
        $user = new User();

        $user->exchangeArray(array_merge([
            'id' => 1,
            'nombre' => 'Luis',
            'apellido' => 'Cutzal',
            'password' => 'hash_anterior',
            'rol_id' => 2,
            'estado' => 1,
            'correo' => 'cutzalluis@gmail.com',
            'cui' => 1234567890123,
            'carnet' => 201700841,
            'registro_personal' => null,
            'extension' => 1,
        ], $overrides));

        return $user;
    }

    public function testEditUserPasswordExitosa(): void
    {
        // crea un usuario existente
        $usuario = $this->makeUser([
            'rol_id' => 4,
        ]);

        // define los datos de entrada
        $data = [
            'id' => 1,
            'password' => 'Password123',
            'password2' => 'Password123',
        ];

        // crea una sesion real para el servicio
        $session = new Container('user');

        // simula un usuario normal
        $session->rol_id = 2;

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula el tipo del rol
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(2)
            ->willReturn(2);

        // verifica que el usuario sea actualizado
        $this->userTable
            ->expects($this->once())
            ->method('editUser')
            ->with(
                $this->isInstanceOf(User::class)
            )
            ->willReturn(1);

        // ejecuta la funcion que se esta probando
        $resultado = $this->userService->editUser($data);

        // verifica el resultado
        $this->assertEquals(1, $resultado);
    }


    public function testEditUserAdminPasswordsNoCoinciden(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser();

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
            'password' => 'Password123',
            'password2' => 'Password456',
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->willReturn(1);

        // evita duplicados
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->willReturn(null);

        // valida carnet y cui
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->willReturn($this->student);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Las contraseñas no coinciden.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminExitoso(): void
    {
        // crea el usuario existente
        $usuario = $this->makeUser();

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos a actualizar
        $data = $this->makeUserData([
            'id' => 1,
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'password' => 'Password123',
            'password2' => 'Password123',
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->exactly(2))
            ->method('getTipoIdByRolId')
            ->willReturn(1);

        // validaciones de usuario
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->willReturn(null);

        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->willReturn($this->student);

        // extension asociada al rol
        $this->rolTable
            ->expects($this->once())
            ->method('getExtensionByRolId')
            ->willReturn(1);

        // verifica que se actualice el usuario
        $this->userTable
            ->expects($this->once())
            ->method('editUser')
            ->with(
                $this->isInstanceOf(User::class)
            )
            ->willReturn(1);

        // ejecuta la funcion
        $resultado = $this->userService->editUser($data);

        // verifica el resultado
        $this->assertEquals(1, $resultado);
    }

    public function testEditUserNoExiste(): void
    {
        // datos de entrada
        $data = [
            'id' => 999,
        ];

        // simula que el usuario no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(999)
            ->willReturn(null);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'Usuario no encontrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminCorreoDuplicado(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser();

        // crea otro usuario con el mismo correo
        $usuarioDuplicado = $this->makeUser([
            'id' => 2,
        ]);

        // simula un estudiante valido
        $this->student->dpi = '1234567890123';

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(1)
            ->willReturn(1);

        // simula que el correo ya pertenece a otro usuario
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->with('cutzalluis@gmail.com')
            ->willReturn($usuarioDuplicado);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El correo ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminCuiDuplicado(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser();

        // crea otro usuario con el mismo cui
        $usuarioDuplicado = $this->makeUser([
            'id' => 2,
        ]);

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(1)
            ->willReturn(1);

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui ya pertenece a otro usuario
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->with('1234567890123')
            ->willReturn($usuarioDuplicado);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El CUI: 1234567890123 ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminCarnetDuplicado(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser();

        // crea otro usuario con el mismo carnet
        $usuarioDuplicado = $this->makeUser([
            'id' => 2,
        ]);

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(1)
            ->willReturn(1);

        // simula que el correo no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        // simula que el cui no existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        // simula que el carnet ya existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->with('201700841')
            ->willReturn($usuarioDuplicado);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El carnet: 201700841 ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminRegistroDuplicado(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser([
            'carnet' => null,
            'registro_personal' => '12345',
        ]);

        // crea otro usuario con el mismo registro personal
        $usuarioDuplicado = $this->makeUser([
            'id' => 2,
            'carnet' => null,
            'registro_personal' => '12345',
        ]);

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
            'carnet' => '',
            'registro_personal' => '12345',
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(1)
            ->willReturn(1);

        // validaciones previas
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el registro personal ya existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserByRegistroPersonal')
            ->with('12345')
            ->willReturn($usuarioDuplicado);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El registro personal: 12345 ya está registrado.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminCarnetNoExiste(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser();

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(1)
            ->willReturn(1);

        // validaciones previas
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el carnet no existe
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with('201700841')
            ->willReturn(null);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El carnet 201700841 no existe en el sistema académico.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testEditUserAdminCuiNoCoincide(): void
    {
        // crea el usuario que sera editado
        $usuario = $this->makeUser();

        // simula un estudiante con otro cui
        $this->student->dpi = '9999999999999';

        // simula una sesion de administrador
        $session = new Container('user');
        $session->rol_id = 1;

        // datos de entrada
        $data = $this->makeUserData([
            'id' => 1,
        ]);

        // simula que el usuario existe
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->willReturn($usuario);

        // simula que el usuario logueado es administrador
        $this->rolTable
            ->expects($this->once())
            ->method('getTipoIdByRolId')
            ->with(1)
            ->willReturn(1);

        // validaciones previas
        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCui')
            ->willReturn(null);

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCarnet')
            ->willReturn(null);

        // simula que el carnet existe
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->willReturn($this->student);

        // verifica la excepcion esperada
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            'El CUI ingresado no corresponde al carnet 201700841.'
        );

        // ejecuta la funcion que se esta probando
        $this->userService->editUser($data);
    }

    public function testListarUsuarios(): void
    {
        // define el resultado esperado
        $usuarios = [
            $this->makeUser(['id' => 1]),
            $this->makeUser(['id' => 2]),
        ];

        // simula la consulta
        $this->userTable
            ->expects($this->once())
            ->method('getAllUsers')
            ->willReturn($usuarios);

        // ejecuta la funcion
        $resultado = $this->userService->listarUsuarios();

        // verifica el resultado
        $this->assertSame($usuarios, $resultado);
    }

    public function testBuscarUsuario(): void
    {
        // define el criterio de busqueda
        $busqueda = 'Luis';

        // define el resultado esperado
        $usuarios = [
            $this->makeUser(['nombre' => 'Luis']),
        ];

        // simula la consulta
        $this->userTable
            ->expects($this->once())
            ->method('searchUser')
            ->with('Luis')
            ->willReturn($usuarios);

        // ejecuta la funcion
        $resultado = $this->userService->buscarUsuario($busqueda);

        // verifica el resultado
        $this->assertSame($usuarios, $resultado);
    }

    public function testObtenerUsuarioPorId(): void
    {
        // crea un usuario de prueba
        $usuario = $this->makeUser();

        // simula la consulta
        $this->userTable
            ->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn($usuario);

        // ejecuta la funcion
        $resultado = $this->userService->obtenerUsuarioPorId(1);

        // verifica el resultado
        $this->assertSame($usuario, $resultado);
    }

    public function testEliminarUsuario(): void
    {
        // verifica que deleteUser sea llamado con el id correcto
        $this->userTable
            ->expects($this->once())
            ->method('deleteUser')
            ->with(1);

        // ejecuta la funcion que se esta probando
        $this->userService->eliminarUsuario(1);
    }

    public function testActivarUsuario(): void
    {
        // verifica que enableUser sea llamado con el id correcto
        $this->userTable
            ->expects($this->once())
            ->method('enableUser')
            ->with(1);

        // ejecuta la funcion que se esta probando
        $this->userService->activarUsuario(1);
    }
    
}

/*

vendor/bin/phpunit module/Application/test/Service/UserServiceTest.php


php vendor/bin/phpunit --filter testCreateUserRolAsociacionSinCarrera


*/
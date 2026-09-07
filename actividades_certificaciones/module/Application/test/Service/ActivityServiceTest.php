<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Model\ActivityTable;
use Application\Model\Activity;
use Laminas\Session\Container;
use Application\Service\MailService;
use Application\Model\UserTable;
use Application\Model\RolTable;
use Application\Model\CarreraTable;
use Application\Model\AttendanceListTable;
use Application\Model\AttendanceListStudentTable;

use Application\Model\User;
use Application\Service\ActivityService;

class ActivityServiceTest extends TestCase
{
    private ActivityTable $activityTable;
    private MailService $mailService;
    private UserTable $userTable;
    private RolTable $rolTable;
    private CarreraTable $carreraTable;
    private AttendanceListTable $attendanceListTable;
    private AttendanceListStudentTable $attendanceListStudentTable;

    private ActivityService $activityService;

    protected function setUp(): void
    {

        $this->activityTable = $this->createMock(ActivityTable::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->userTable = $this->createMock(UserTable::class);
        $this->rolTable = $this->createMock(RolTable::class);
        $this->attendanceListTable = $this->createMock(AttendanceListTable::class);
        $this->attendanceListStudentTable = $this->createMock(AttendanceListStudentTable::class);
        $this->carreraTable = $this->createMock(CarreraTable::class);

        $this->activityService = new ActivityService(
            $this->activityTable,
            $this->mailService,
            $this->userTable,
            $this->rolTable,
            $this->attendanceListTable,
            $this->attendanceListStudentTable,
            $this->carreraTable,
        );
    }

    //createActivity
    public function testCrearActividadSinSesion(): void
    {
        // Se espera una excepción porque no existe
        // información del usuario en sesión.
        $this->expectException(\Exception::class);

        // Verifica el mensaje esperado.
        $this->expectExceptionMessage(
            'No se pudo obtener el usuario logueado.'
        );

        // Ejecuta el método.
        $this->activityService->createActivity([]);
    }

    private function crearSesion(
        int $userId,
        int $rolId,
        int $tipoId
    ): void {
        $session = new Container('user');

        $session->userId = $userId;
        $session->rol_id = $rolId;
        $session->tipo_id = $tipoId;
    }

    public function testCrearActividadSinOrganizador(): void
    {
        // Simula un usuario autenticado de tipo personal.

        $this->crearSesion(1, 10, 3); //puede ser tambien 2 ya que asi esta la validacion

        // Espera la excepción definida por el servicio.
        $this->expectException(\Exception::class);

        // Verifica el mensaje exacto.
        $this->expectExceptionMessage(
            'Debe seleccionar organizador.'
        );

        // Ejecuta el método sin enviar organizador.
        $this->activityService->createActivity([
            'carrera' => 1,
            'nombre' => 'Actividad de prueba',
            'fecha' => '2025-01-01',
            'credito' => 2,
            'tipo_participacion' => 'individual',
            'extension' => 1,
        ]);
    }

    public function testCrearActividadSinCarreraPersonal(): void
    {
        // Simula un usuario autenticado de tipo personal.
        $this->crearSesion(
            1,  // usuario
            10, // rol
            2   // personal
        );

        // Espera la excepción definida por el servicio.
        $this->expectException(\Exception::class);

        // Verifica el mensaje exacto.
        $this->expectExceptionMessage(
            'Debe seleccionar carrera.'
        );

        // Ejecuta el método sin enviar carrera.
        $this->activityService->createActivity([
            'organizador' => 5,
            'nombre' => 'Actividad de prueba',
            'fecha' => '2025-01-01',
            'credito' => 2,
            'tipo_participacion' => 'individual',
            'extension' => 1,
        ]);
    }

    public function testCrearActividadSinCarreraAsociacion(): void
    {
        // Simula una asociación autenticada.
        $this->crearSesion(
            1,  // usuario
            20, // rol (asociación)
            4   // tipo asociación
        );

        // Espera la excepción definida por el servicio.
        $this->expectException(\Exception::class);

        // Verifica el mensaje exacto.
        $this->expectExceptionMessage(
            'No se pudo determinar la carrera.'
        );

        // Ejecuta el método sin enviar carrera.
        $this->activityService->createActivity([
            'nombre' => 'Actividad de prueba',
            'fecha' => '2025-01-01',
            'credito' => 2,
            'tipo_participacion' => 'individual',
            'extension' => 1,
        ]);
    }

    public function testCrearActividadSinNombre(): void
    //funciona con nombre, fecha, credito, tipo_particiácion o extension es la misma logica
    //con el mismo tipoId=[2,3,4]
    {
        // Simula un usuario autenticado de tipo personal.
        $this->crearSesion(
            1,  // usuario
            10, // rol
            4   // personal
        );

        // Simula que la carrera existe.
        // No nos interesa qué retorna, únicamente que la validación pase.
        $this->carreraTable->expects($this->once())
            ->method('getCarrera')
            ->with(1);

        // Espera la excepción definida por el servicio.
        $this->expectException(\Exception::class);

        // Verifica el mensaje exacto.
        $this->expectExceptionMessage(
            'El campo nombre es obligatorio.'
            //este mensaje cambiaria dependiendo de la condicion que se esta evaluando
            //si es fecha, credito, tipo_participacion o extension
        );

        // Ejecuta el método sin enviar nombre.
        $this->activityService->createActivity([
            'organizador' => 5,
            'carrera' => 1,
            //nombre no existe
            'fecha' => '2025-01-01',
            'credito' => 5,
            'tipo_participacion' => 'individual',
            'extension' => 1,
            //para fecha, credito, tipo_participacion o extension, si no vienen es lo mismo que cuando no viene el nombre
        ]);
    }

    //ahora evaluaremos que todo funciona
    public function testCrearActividadGuarda(): void
    {
        // Simula un usuario autenticado de tipo personal.
        $this->crearSesion(
            1,  // id del usuario
            10, // id del rol
            2   // tipo personal, pero puede ser tambien tipoId = [3,4]
        );

        // Simula que la carrera existe.
        $this->carreraTable->expects($this->once())
            ->method('getCarrera')
            ->with(1);

        // Verifica que saveActivity sea ejecutado una vez.
        $this->activityTable->expects($this->once())
            ->method('saveActivity')
            ->with(
                $this->isInstanceOf(Activity::class)
            )
            ->willReturn(1);

        // Ejecuta el método con datos válidos.
        $this->activityService->createActivity([
            'organizador' => 5,
            'carrera' => 1,
            'nombre' => 'Actividad de prueba',
            'fecha' => '2025-01-01',
            'credito' => 2,
            'tipo_participacion' => 'individual',
            'extension' => 1,
        ]);
    }
    public function testCrearActividadRetornaId(): void
    {
        // Simula un usuario autenticado de tipo personal.
        $this->crearSesion(
            1,  // id del usuario
            10, // id del rol
            2   // tipo personal
        );

        // Simula que la carrera existe.
        $this->carreraTable->expects($this->once())
            ->method('getCarrera')
            ->with(1);

        // Simula que la actividad fue guardada y retorna el id 25.
        $this->activityTable->expects($this->once())
            ->method('saveActivity')
            ->with(
                $this->isInstanceOf(Activity::class)
            )
            ->willReturn(25);

        // Ejecuta el método con datos válidos.
        $resultado = $this->activityService->createActivity([
            'organizador' => 5,
            'carrera' => 1,
            'nombre' => 'Actividad de prueba',
            'fecha' => '2025-01-01',
            'credito' => 2,
            'tipo_participacion' => 'individual',
            'extension' => 1,
        ]);

        // Verifica que el service retorne exactamente
        // el mismo id recibido desde saveActivity().
        $this->assertSame(
            25,
            $resultado
        );
    }

    //createActivitySimple

    public function testCrearSimpleSinTipoParticipacion(): void
    {
        // Simula un usuario autenticado de tipo personal.
        $this->crearSesion(
            1,  // id del usuario
            10, // id del rol
            4   // tipo asociacion, puede ser tambien para tipoId=[2,3]
        );

        // Simula que la carrera existe.
        $this->carreraTable->expects($this->once())
            ->method('getCarrera')
            ->with(1);

        // Verifica que la actividad sea guardada.
        $this->activityTable->expects($this->once())
            ->method('saveActivity')
            ->with(
                $this->isInstanceOf(Activity::class)
            )
            ->willReturn(1);

        // Ejecuta el método sin enviar tipo_participacion.
        $resultado = $this->activityService->createActivitySimple([
            'organizador' => 5,
            'carrera' => 1,
            'nombre' => 'Actividad simple',
            'fecha' => '2025-01-01',
            'credito' => 2,
            // Tipo de participación omitido.
            'extension' => 1,
        ]);

        // Verifica que la actividad fue creada.
        $this->assertSame(
            1,
            $resultado
        );
    }

    public function testCrearSimpleRetornaId(): void
    {
        // Simula un usuario autenticado de tipo personal.
        $this->crearSesion(
            1,  // id del usuario
            10, // id del rol
            2   // tipo personal
        );

        // Simula que la carrera existe.
        $this->carreraTable->expects($this->once())
            ->method('getCarrera')
            ->with(1);

        // Simula que el repositorio guarda la actividad y retorna un ID.
        $this->activityTable->expects($this->once())
            ->method('saveActivity')
            ->with(
                $this->isInstanceOf(Activity::class)
            )
            ->willReturn(42);

        // Ejecuta el método createActivitySimple.
        $resultado = $this->activityService->createActivitySimple([
            'organizador' => 5,
            'carrera' => 1,
            'nombre' => 'Actividad simple',
            'fecha' => '2025-01-01',
            'credito' => 3,
            'extension' => 1,
        ]);

        // Verifica que el service retorne exactamente
        // el ID devuelto por saveActivity().
        $this->assertSame(
            42,
            $resultado
        );
    }

    public function testListarSinPermisos(): void
    {
        // simula un tipo de usuario no válido o no configurado.
        $tipoId = 99;

        // no necesitamos sesión porque el método no la usa directamente.
        $userCarreras = [1, 2];
        $userExtension = 1;
        $rolId = 5;

        // ejecuta el método listarActividades.
        $resultado = $this->activityService->listarActividades(
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        );

        // verifica que no se consulta la base de datos
        // y se retorna un array vacio.
        $this->assertSame(
            [],
            $resultado
        );
    }

    //listarActividades
    public function testListarAdmin(): void
    {
        // simula usuario admin
        $tipoId = 1;

        // datos de entrada para el metodo
        $userCarreras = [1, 2];
        $userExtension = 1;
        $rolId = 5;

        // se espera que se llame al metodo del table
        $this->activityTable->expects($this->once())
            ->method('getActividadesPorTipos')
            ->with(
                // tipos permitidos para admin
                [1, 2, 3, 4],
                $tipoId,
                $userCarreras,
                $userExtension,
                $rolId
            )
            ->willReturn([
                // respuesta simulada del repositorio
                ['id' => 1, 'nombre' => 'actividad 1']
            ]);

        // ejecucion del metodo
        $resultado = $this->activityService->listarActividades(
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        );

        // validacion del resultado
        $this->assertSame(
            [
                ['id' => 1, 'nombre' => 'actividad 1']
            ],
            $resultado
        );
    }

    public function testListarSecretaria(): void
    {
        // simula usuario secretaria
        $tipoId = 3;

        // datos de entrada del metodo
        $userCarreras = [1, 2];
        $userExtension = 1;
        $rolId = 5;

        // se espera llamada al table con tipos de secretaria
        $this->activityTable->expects($this->once())
            ->method('getActividadesPorTipos')
            ->with(
                // tipos permitidos para secretaria
                [2, 3, 4],
                $tipoId,
                $userCarreras,
                $userExtension,
                $rolId
            )
            ->willReturn([
                // respuesta simulada del repositorio
                ['id' => 2, 'nombre' => 'actividad secretaria']
            ]);

        // ejecucion del metodo
        $resultado = $this->activityService->listarActividades(
            $tipoId,
            $userCarreras,
            $userExtension,
            $rolId
        );

        // validacion del resultado
        $this->assertSame(
            [
                ['id' => 2, 'nombre' => 'actividad secretaria']
            ],
            $resultado
        );
    }

    //buscarActividad

    public function testBuscarAdmin(): void
    {
        // simula usuario admin
        $tipoId = 1;

        // texto de busqueda
        $query = 'evento';

        // datos de usuario
        $rolId = 5;
        $userCarreras = [1, 2];
        $userExtension = 1;

        // se espera llamada al metodo del table con parametros correctos
        $this->activityTable->expects($this->once())
            ->method('searchActivity')
            ->with(
                // texto de busqueda
                $query,

                // tipos permitidos para admin
                [1, 2, 3, 4],
                $tipoId,
                $rolId,
                $userCarreras,
                $userExtension
            )
            ->willReturn([
                // respuesta simulada del repositorio
                ['id' => 10, 'nombre' => 'evento admin']
            ]);

        // ejecucion del metodo
        $resultado = $this->activityService->buscarActividad(
            $query,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension
        );

        // validacion del resultado
        $this->assertSame(
            [
                ['id' => 10, 'nombre' => 'evento admin']
            ],
            $resultado
        );
    }

    public function testBuscarAsociacion(): void
    {
        // simula usuario asociacion
        $tipoId = 4;

        // texto de busqueda
        $query = 'actividad';

        // datos de usuario
        $rolId = 7;
        $userCarreras = [2];
        $userExtension = 1;

        // se espera llamada al metodo del table con filtro de asociacion
        $this->activityTable->expects($this->once())
            ->method('searchActivity')
            ->with(
                // texto de busqueda
                $query,
                // tipos permitidos para asociacion
                [4],
                $tipoId,
                $rolId,
                $userCarreras,
                $userExtension
            )
            ->willReturn([
                // respuesta simulada del repositorio
                ['id' => 20, 'nombre' => 'actividad asociacion']
            ]);

        // ejecucion del metodo
        $resultado = $this->activityService->buscarActividad(
            $query,
            $tipoId,
            $rolId,
            $userCarreras,
            $userExtension
        );

        // validacion del resultado
        $this->assertSame(
            [
                ['id' => 20, 'nombre' => 'actividad asociacion']
            ],
            $resultado
        );
    }


    private function limpiarSesion(): void
    {
        $_SESSION = [];

        $session = new Container('user');
        $session->getManager()->getStorage()->clear();
    }

    //editActivitySimple

    public function testEditarSimpleSinSesion(): void
    {
        // limpia cualquier sesion previa de phpunit o laminas
        $this->limpiarSesion();

        // se espera excepcion por falta de usuario logueado
        $this->expectException(\Exception::class);

        // mensaje esperado
        $this->expectExceptionMessage(
            'No se pudo obtener el usuario logueado.'
        );

        // ejecucion del metodo
        $this->activityService->editActivitySimple([
            'id' => 1,
            'nombre' => 'actividad editada',
            'organizador' => 5,
            'fecha' => '2025-01-01',
            'carrera' => 1,
            'credito' => 2
        ]);
    }

    public function testEditarSimpleSinNombre(): void
    //es el mismo que el de crear actividad, funciona igual para nombre, fecha y creditos
    {
        // limpia la sesion antes de ejecutar el test
        $this->limpiarSesion();

        // simula usuario logueado en sesion
        $session = new Container('user');
        $session->userId = 1;

        // se espera excepcion por campo obligatorio
        $this->expectException(\Exception::class);

        // mensaje exacto de la validacion
        $this->expectExceptionMessage(
            'El campo nombre es obligatorio.'
        );
        //solo debe de cambiar el mensaje en lugar de nombre a fecha o credito para que funcione

        // ejecucion del metodo sin nombre
        $this->activityService->editActivitySimple([
            'id' => 1,
            // nombre omitido
            'organizador' => 5,
            'fecha' => '2025-01-01',
            'carrera' => 1,
            'credito' => 2
            //puede faltar fecha y credito, la logica es igual para esos datos
        ]);
    }

    //ahora todos los datos son validos

    public function testEditarSimpleGuarda(): void
    {
        // limpia la sesion antes de ejecutar el test
        $this->limpiarSesion();

        // simula usuario logueado
        $session = new Container('user');
        $session->userId = 1;

        // se espera que el repositorio reciba un objeto activity
        $this->activityTable->expects($this->once())
            ->method('editActivity')
            ->with(
                $this->isInstanceOf(Activity::class)
            )
            ->willReturn(1);

        // ejecuta el metodo con datos validos
        $this->activityService->editActivitySimple([
            'id' => 1,
            'nombre' => 'actividad editada',
            'organizador' => 5,
            'fecha' => '2025-01-01',
            'carrera' => 1,
            'credito' => 2
        ]);
    }

    public function testEditarSimpleRetornaId(): void
    {
        // limpia la sesion antes de ejecutar el test
        $this->limpiarSesion();

        // simula usuario logueado
        $session = new Container('user');
        $session->userId = 1;

        // se espera llamada al repositorio
        $this->activityTable->expects($this->once())
            ->method('editActivity')
            ->with(
                $this->isInstanceOf(Activity::class)
            )
            ->willReturn(25);

        // ejecuta el metodo con datos validos
        $resultado = $this->activityService->editActivitySimple([
            'id' => 1,
            'nombre' => 'actividad editada',
            'organizador' => 5,
            'fecha' => '2025-01-01',
            'carrera' => 1,
            'credito' => 2
        ]);

        // verifica que el service retorne el mismo valor
        $this->assertSame(
            25,
            $resultado
        );
    }

    //definirTipoParticipacion

    public function testDefinirTipoParticipacion(): void
    {
        // crea actividad simulada
        $actividad = new Activity();

        // asigna un valor inicial diferente
        $actividad->tipo_participacion = 'individual';

        // se espera que la actividad sea obtenida por id
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // se espera que la actividad modificada sea guardada
        $this->activityTable->expects($this->once())
            ->method('saveActivity')
            ->with(
                $this->callback(
                    function ($activity) {

                        // verifica que el objeto sea Activity
                        $this->assertInstanceOf(
                            Activity::class,
                            $activity
                        );

                        // verifica que el tipo fue actualizado
                        $this->assertSame(
                            'lista',
                            $activity->tipo_participacion
                        );

                        return true;
                    }
                )
            )
            ->willReturn(1);

        // ejecuta el metodo
        $resultado = $this->activityService->definirTipoParticipacion(
            1,
            'lista'
        );

        // verifica el valor retornado
        $this->assertSame(
            1,
            $resultado
        );
    }

    //ActualizarActividad

    public function testActualizarActividadInexistente(): void
    {
        // simula que la actividad no existe
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn(null);

        // se espera excepcion
        $this->expectException(\Exception::class);

        // se valida el mensaje
        $this->expectExceptionMessage(
            'La actividad no existe.'
        ); //estos tipos de mensaje deben de ser igual a los del service

        // ejecuta el metodo
        $this->activityService->ActualizarActividad(
            1,
            10
        );
    }

    public function testActualizarSinUsuario(): void
    {
        // crea una actividad simulada
        $actividad = new Activity();

        // asigna el usuario responsable
        $actividad->usuario_id = 5;

        // simula que la actividad existe
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // simula que el usuario no existe
        $this->userTable->expects($this->once())
            ->method('getUserById')
            ->with(5)
            ->willReturn(null);

        // se espera excepcion
        $this->expectException(\Exception::class);

        // se valida el mensaje exacto
        $this->expectExceptionMessage(
            'Usuario responsable no encontrado.'
        );

        // ejecuta el metodo
        $this->activityService->ActualizarActividad(
            1,
            10
        );
    }

    public function testActualizarSinRol(): void
    {
        // crea una actividad simulada
        $actividad = new Activity();

        // asigna el usuario responsable
        $actividad->usuario_id = 5;

        // simula que la actividad existe
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = new User();

        // simula que el usuario responsable existe
        $this->userTable->expects($this->exactly(2))
            ->method('getUserById')
            ->willReturnCallback(
                function ($id) use ($usuario) {

                    // retorna usuario responsable
                    if ($id === 5) {
                        return $usuario;
                    }

                    // crea usuario aprobador
                    $aprobador = new User();

                    // asigna rol al aprobador
                    $aprobador->rol_id = 99;

                    return $aprobador;
                }
            );

        // simula que el rol no existe
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with(99)
            ->willReturn(null);

        // se espera excepcion
        $this->expectException(\Exception::class);

        // se valida el mensaje exacto
        $this->expectExceptionMessage(
            'Rol del aprobador no encontrado.'
        );

        // ejecuta el metodo
        $this->activityService->ActualizarActividad(
            1,
            10
        );
    }

    //todos los datos validos

    private function crearUsuario(
        int $rolId = 1,
        string $correo = 'usuario@correo.com',
        string $nombre = 'juan',
        string $apellido = 'perez'
    ): User {
        // crea usuario reutilizable para los tests
        $usuario = new User();

        // asigna datos basicos
        $usuario->rol_id = $rolId;
        $usuario->correo = $correo;
        $usuario->nombre = $nombre;
        $usuario->apellido = $apellido;

        return $usuario;
    }

    private function crearActividad(
        int $usuarioId = 5,
        string $nombre = 'actividad prueba'
    ): Activity {
        // crea actividad reutilizable para los tests
        $actividad = new Activity();

        // asigna usuario responsable
        $actividad->usuario_id = $usuarioId;

        // asigna nombre de actividad
        $actividad->nombre = $nombre;

        return $actividad;
    }

    public function testActualizarGuarda(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = $this->crearUsuario();

        // crea usuario aprobador
        $aprobador = $this->crearUsuario(3);

        // simula las dos consultas a usuarios
        $this->mockUsuariosRevision(
            $usuario,
            $aprobador
        );

        // crea rol simulado
        $rol = new \stdClass();

        // nombre utilizado en el correo
        $rol->nombre = 'secretaria';

        // simula rol existente
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with(3)
            ->willReturn($rol);

        // verifica que se actualice la actividad
        $this->activityTable->expects($this->once())
            ->method('updateActivity')
            ->with(
                1,
                10
            );

        // evita enviar correo real durante el test
        $this->mailService->expects($this->once())
            ->method('send');

        // ejecuta el metodo
        $this->activityService->ActualizarActividad(
            1,
            10
        );
    }

    public function testActualizarEnviaCorreo(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = $this->crearUsuario();

        // crea usuario aprobador
        $aprobador = $this->crearUsuario(3);

        // simula consultas de usuarios
        $this->mockUsuariosRevision(
            $usuario,
            $aprobador
        );

        // crea rol simulado
        $rol = new \stdClass();

        // nombre mostrado en el correo
        $rol->nombre = 'secretaria';

        // simula rol existente
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->willReturn($rol);

        // permite que la actualizacion ocurra
        $this->activityTable->expects($this->once())
            ->method('updateActivity')
            ->with(1, 10);

        // verifica los datos enviados al correo
        $this->mailService->expects($this->once())
            ->method('send')
            ->with(
                'usuario@correo.com',
                'Actividad aprobada',
                $this->callback(
                    function ($mensaje) {

                        // verifica nombre de actividad
                        $this->assertStringContainsString(
                            'actividad prueba',
                            $mensaje
                        );

                        // verifica nombre del rol
                        $this->assertStringContainsString(
                            'secretaria',
                            $mensaje
                        );

                        return true;
                    }
                )
            );

        // ejecuta el metodo
        $this->activityService->ActualizarActividad(
            1,
            10
        );
    }

    //RechazarActividad

    public function testRechazarActividadInexistente(): void
    {
        // simula que la actividad no existe
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn(null);

        // se espera excepcion
        $this->expectException(\Exception::class);

        // se valida el mensaje exacto
        $this->expectExceptionMessage(
            'La actividad no existe.'
        );

        // ejecuta el metodo
        $this->activityService->RechazarActividad(
            1,
            'motivo de prueba',
            10
        );
    }

    public function testRechazarSinUsuario(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // simula que el usuario responsable no existe
        $this->userTable->expects($this->once())
            ->method('getUserById')
            ->with(5)
            ->willReturn(null);

        // se espera excepcion
        $this->expectException(\Exception::class);

        // se valida el mensaje exacto
        $this->expectExceptionMessage(
            'Usuario responsable no encontrado.'
        );

        // ejecuta el metodo
        $this->activityService->RechazarActividad(
            1,
            'motivo de prueba',
            10
        );
    }

    public function testRechazarSinRol(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = $this->crearUsuario();

        // crea usuario rechazador
        $rechazador = $this->crearUsuario(99);

        // simula las consultas de usuarios
        $this->mockUsuariosRevision(
            $usuario,
            $rechazador
        );

        // simula que el rol no existe
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with(99)
            ->willReturn(null);

        // se espera excepcion
        $this->expectException(\Exception::class);

        // se valida el mensaje exacto
        $this->expectExceptionMessage(
            'Rol del aprobador no encontrado.'
        );

        // ejecuta el metodo
        $this->activityService->RechazarActividad(
            1,
            'motivo de prueba',
            10
        );
    }

    public function testRechazarGuarda(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = $this->crearUsuario();

        // crea usuario rechazador
        $rechazador = $this->crearUsuario(3);

        // simula las consultas de usuarios
        $this->mockUsuariosRevision(
            $usuario,
            $rechazador
        );

        // crea rol simulado
        $rol = new \stdClass();

        // nombre utilizado en el correo
        $rol->nombre = 'secretaria';

        // simula rol existente
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with(3)
            ->willReturn($rol);

        // verifica que se rechace la actividad
        $this->activityTable->expects($this->once())
            ->method('rechazoActivity')
            ->with(
                1,
                'motivo de prueba',
                10
            );

        // permite que se rechace la lista
        $this->attendanceListTable->expects($this->once())
            ->method('rejectByActividad');

        // evita envio real de correo
        $this->mailService->expects($this->once())
            ->method('send');

        // ejecuta el metodo
        $this->activityService->RechazarActividad(
            1,
            'motivo de prueba',
            10
        );
    }

    private function mockUsuariosRevision(
        User $usuario,
        User $revisor
    ): void {
        // simula las consultas de usuarios
        $this->userTable->expects($this->exactly(2))
            ->method('getUserById')
            ->willReturnCallback(
                function ($id) use ($usuario, $revisor) {

                    // retorna usuario responsable
                    if ($id === 5) {
                        return $usuario;
                    }

                    // retorna usuario revisor
                    return $revisor;
                }
            );
    }

    public function testRechazarLista(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = $this->crearUsuario();

        // crea usuario rechazador
        $rechazador = $this->crearUsuario(3);

        // configura consultas de usuarios
        $this->mockUsuariosRevision(
            $usuario,
            $rechazador
        );

        // crea rol simulado
        $rol = new \stdClass();

        // nombre utilizado en el correo
        $rol->nombre = 'secretaria';

        // simula rol existente
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with(3)
            ->willReturn($rol);

        // permite rechazo de actividad
        $this->activityTable->expects($this->once())
            ->method('rechazoActivity');

        // verifica rechazo de lista
        $this->attendanceListTable->expects($this->once())
            ->method('rejectByActividad')
            ->with(
                1
            );

        // evita correo real
        $this->mailService->expects($this->once())
            ->method('send');

        // ejecuta el metodo
        $this->activityService->RechazarActividad(
            1,
            'motivo de prueba',
            10
        );
    }

    public function testRechazarEnviaCorreo(): void
    {
        // crea actividad simulada
        $actividad = $this->crearActividad();

        // simula actividad existente
        $this->activityTable->expects($this->once())
            ->method('getActivityById')
            ->with(1)
            ->willReturn($actividad);

        // crea usuario responsable
        $usuario = $this->crearUsuario();

        // crea usuario rechazador
        $rechazador = $this->crearUsuario(3);

        // configura consultas de usuarios
        $this->mockUsuariosRevision(
            $usuario,
            $rechazador
        );

        // crea rol simulado
        $rol = new \stdClass();

        // nombre mostrado en el correo
        $rol->nombre = 'secretaria';

        // simula rol existente
        $this->rolTable->expects($this->once())
            ->method('getRolById')
            ->with(3)
            ->willReturn($rol);

        // permite rechazo de actividad
        $this->activityTable->expects($this->once())
            ->method('rechazoActivity');

        // permite rechazo de lista
        $this->attendanceListTable->expects($this->once())
            ->method('rejectByActividad');

        // verifica datos enviados al correo
        $this->mailService->expects($this->once())
            ->method('send')
            ->with(
                'usuario@correo.com',
                'Actividad rechazada',
                $this->callback(
                    function ($mensaje) {

                        // verifica nombre de actividad
                        $this->assertStringContainsString(
                            'actividad prueba',
                            $mensaje
                        );

                        // verifica nombre del rol
                        $this->assertStringContainsString(
                            'secretaria',
                            $mensaje
                        );

                        // verifica motivo de rechazo
                        $this->assertStringContainsString(
                            'motivo de prueba',
                            $mensaje
                        );

                        return true;
                    }
                )
            );

        // ejecuta el metodo
        $this->activityService->RechazarActividad(
            1,
            'motivo de prueba',
            10
        );
    }

    //obtenerActividadesPendientes

    public function testPendientes(): void
    {
        // carreras del usuario
        $carreras = [1, 3];

        // resultado esperado
        $resultado = ['actividad1'];

        // verifica que se envien los permisos correctos
        $this->activityTable->expects($this->once())
            ->method('getActividadesPendientesPorTipos')
            ->with(
                [1, 2, 3, 4],
                1,
                10,
                $carreras,
                1
            )
            ->willReturn($resultado);

        // ejecuta el metodo
        $actual = $this->activityService
            ->obtenerActividadesPendientes(
                1,
                10,
                $carreras,
                1
            );

        // verifica el resultado retornado
        $this->assertSame(
            $resultado,
            $actual
        );
    }

    //obtenerActividadesAprobadas

    public function testAprobadas(): void
    {
        // carreras del usuario
        $carreras = [1, 3];

        // resultado esperado
        $resultado = ['actividad aprobada'];

        // verifica parametros enviados al table
        $this->activityTable->expects($this->once())
            ->method('getActividadesPorEstadoYTipos')
            ->with(
                1,
                [1, 2, 3, 4],
                1,
                10,
                $carreras,
                1
            )
            ->willReturn($resultado);

        // ejecuta el metodo
        $actual = $this->activityService
            ->obtenerActividadesAprobadas(
                1,
                10,
                $carreras,
                1
            );

        // verifica resultado retornado
        $this->assertSame(
            $resultado,
            $actual
        );
    }

    //obtenerActividadesRechazadas

    public function testRechazadas(): void
    {
        // carreras del usuario
        $carreras = [1, 3];

        // resultado esperado
        $resultado = ['actividad rechazada'];

        // verifica parametros enviados al table
        $this->activityTable->expects($this->once())
            ->method('getActividadesPorEstado')
            ->with(
                2,
                1,
                $carreras,
                1,
                ''
            )
            ->willReturn($resultado);

        // ejecuta el metodo
        $actual = $this->activityService
            ->obtenerActividadesRechazadas(
                1,
                $carreras,
                1
            );

        // verifica resultado retornado
        $this->assertSame(
            $resultado,
            $actual
        );
    }

    //obtenerTodasLasActividades

    public function testTodas(): void
    {
        // carreras del usuario
        $carreras = [1, 2];

        // resultado esperado
        $resultado = ['actividad1', 'actividad2'];

        // verifica parametros enviados al table
        $this->activityTable->expects($this->once())
            ->method('fetchAllPorRoles')
            ->with(
                [1, 2, 3, 4],
                1,
                10,
                $carreras,
                1
            )
            ->willReturn($resultado);

        // ejecuta el metodo
        $actual = $this->activityService
            ->obtenerTodasLasActividades(
                1,
                10,
                $carreras,
                1
            );

        // verifica resultado retornado
        $this->assertSame(
            $resultado,
            $actual
        );
    }

    //getActividadesPorCarrera

    public function testActividadesPorCarrera(): void
    {
        // usuario utilizado para la consulta
        $user = [];

        // datos retornados por el table
        $actividades = [
            [
                'carrera' => 1,
                'total' => 10
            ]
        ];

        // mapa de carreras
        $carrerasMap = [
            1 => 'Arquitectura'
        ];

        // simula consulta de actividades
        $this->activityTable->expects($this->once())
            ->method('getActividadesPorCarrera')
            ->with(
                $user,
                []
            )
            ->willReturn($actividades);

        // simula catalogo de carreras
        $this->carreraTable->expects($this->once())
            ->method('getCarrerasMap')
            ->willReturn($carrerasMap);

        // ejecuta el metodo
        $resultado = $this->activityService
            ->getActividadesPorCarrera($user);

        // verifica nombre agregado
        $this->assertSame(
            'Arquitectura',
            $resultado[0]['nombre']
        );
    }

    public function testActividadesPorCarreraDesconocida(): void
    {
        // usuario utilizado para la consulta
        $user = [];

        // datos retornados por el table
        $actividades = [
            [
                'carrera' => 99,
                'total' => 10
            ]
        ];

        // mapa sin la carrera consultada
        $carrerasMap = [
            1 => 'Ingenieria en sistemas'
        ];

        // simula consulta de actividades
        $this->activityTable->expects($this->once())
            ->method('getActividadesPorCarrera')
            ->with(
                $user,
                []
            )
            ->willReturn($actividades);

        // simula catalogo de carreras
        $this->carreraTable->expects($this->once())
            ->method('getCarrerasMap')
            ->willReturn($carrerasMap);

        // ejecuta el metodo
        $resultado = $this->activityService
            ->getActividadesPorCarrera($user);

        // verifica nombre por defecto
        $this->assertSame(
            'Carrera desconocida',
            $resultado[0]['nombre']
        );
    }
}


/*

vendor/bin/phpunit module/Application/test/Service/ActivityServiceTest.php


php vendor/bin/phpunit --filter testRechazarLista


*/
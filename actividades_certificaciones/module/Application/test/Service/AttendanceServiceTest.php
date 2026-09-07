<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Model\AttendanceListTable;
use Application\Model\AttendanceListStudentTable;
use Application\Model\StudentAttendanceTable;
use Application\Model\StudentTable;
use Application\Model\CarreraEstudianteTable;
use Application\Model\InscripcionTable;
use Application\Service\EstudianteService;
use Application\Service\ActivityService;

use Application\Service\AttendanceService;
use Application\Model\AttendanceList;
use Application\Model\Student;


use Laminas\Session\Container;

use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceServiceTest extends TestCase
{
    private AttendanceListTable $attendanceListTable;
    private AttendanceListStudentTable $attendanceListStudentTable;
    private StudentAttendanceTable $studentAttendanceTable;
    private StudentTable $studentTable;
    private CarreraEstudianteTable $carreraEstudianteTable;
    private InscripcionTable $inscripcionTable;
    private EstudianteService $estudianteService;
    private ActivityService $activityService;
    private AttendanceService $attendanceService;

    protected function setUp(): void
    {

        $this->attendanceListTable = $this->createMock(AttendanceListTable::class);
        $this->attendanceListStudentTable = $this->createMock(AttendanceListStudentTable::class);
        $this->studentAttendanceTable = $this->createMock(StudentAttendanceTable::class);
        $this->studentTable = $this->createMock(StudentTable::class);
        $this->carreraEstudianteTable = $this->createMock(CarreraEstudianteTable::class);
        $this->inscripcionTable = $this->createMock(InscripcionTable::class);
        $this->estudianteService = $this->createMock(EstudianteService::class);
        $this->activityService = $this->createMock(ActivityService::class);

        $this->attendanceService = new AttendanceService(
            $this->attendanceListTable,
            $this->attendanceListStudentTable,
            $this->studentAttendanceTable,
            $this->studentTable,
            $this->activityService,
            $this->carreraEstudianteTable,
            $this->inscripcionTable,
            $this->estudianteService,
        );
    }

    //registrarEstudianteEnActividad

    public function testRegistrarEstudianteNoExiste(): void
    {
        // caso: el estudiante no existe en el sistema

        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(false); // simulamos que no existe el estudiante

        $this->expectException(\Exception::class); // esperamos excepcion

        $this->expectExceptionMessage(
            "El carnet 201700841 no existe en el sistema de estudiantes."
        ); // validamos mensaje de error

        $this->attendanceService->registrarEstudianteEnActividad(
            1,      // id actividad
            201700841, // carnet
            1,      // carrera actividad
            1,      // extension
            1,      // lista id
            false   // historico
        ); // ejecutamos la funcion
    }

    public function testRegistrarEstudianteSinCarrera(): void
    {
        // funcion: registrarEstudianteEnActividad
        // caso: estudiante existe pero no tiene carreras

        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(true); // estudiante existe

        $this->carreraEstudianteTable
            ->method('getCarrerasPorEstudiante')
            ->willReturn([]); // sin carreras

        $this->expectException(\Exception::class); // esperamos error

        $this->expectExceptionMessage(
            "El estudiante 201700841 no tiene carrera asignada"
        ); // mensaje esperado

        $this->attendanceService->registrarEstudianteEnActividad(
            1,
            201700841,
            1,
            1,
            1,
            false
        );
    }

    public function testRegistrarEstudianteCarreraNoCoincide(): void
    {
        // funcion: registrarEstudianteEnActividad
        // caso: carrera no pertenece al estudiante

        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(true);

        $this->carreraEstudianteTable
            ->method('getCarrerasPorEstudiante')
            ->willReturn([2]); // carrera distinta

        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            "El estudiante 201700841 pertenece a otra carrera"
        );

        $this->attendanceService->registrarEstudianteEnActividad(
            1,
            201700841,
            1,
            1,
            1,
            false
        );
    }

    public function testRegistrarEstudianteNoInscrito(): void
    {
        // funcion: registrarEstudianteEnActividad
        // caso: estudiante no inscrito en la carrera

        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(true);

        $this->carreraEstudianteTable
            ->method('getCarrerasPorEstudiante')
            ->willReturn([1]);

        $this->inscripcionTable
            ->method('estudianteInscrito')
            ->willReturn(false);

        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            "El estudiante 201700841 no está inscrito este año en la carrera seleccionada."
        );

        $this->attendanceService->registrarEstudianteEnActividad(
            1,
            201700841,
            1,
            1,
            1,
            false
        );
    }

    public function testRegistrarEstudianteOk(): void
    {
        // funcion: registrarEstudianteEnActividad
        // caso: flujo completo exitoso

        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(true);

        $this->carreraEstudianteTable
            ->method('getCarrerasPorEstudiante')
            ->willReturn([1]);

        $this->inscripcionTable
            ->method('estudianteInscrito')
            ->willReturn(true);

        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('insert')
            ->with([
                'id_attendance_list' => 1,
                'carnet' => 201700841
            ]);

        // forzamos session userId
        $ref = new \ReflectionClass($this->attendanceService);
        $prop = $ref->getProperty('session');
        $prop->setAccessible(true);

        $session = new Container('user');
        $session->userId = 10;

        $prop->setValue($this->attendanceService, $session);

        $this->attendanceService->registrarEstudianteEnActividad(
            1,
            201700841,
            1,
            1,
            1,
            false
        );
    }

    //registrarEstudiantesMasivo

    public function testMasivoOk(): void
    {
        // funcion: registrarEstudiantesMasivo
        // caso: flujo correcto sin errores

        $carnets = [
            ['carnet' => 201700841],
            ['carnet' => 201700842]
        ];

        // importante: estudiante existe
        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(true);

        // importante: carrera valida
        $this->carreraEstudianteTable
            ->method('getCarrerasPorEstudiante')
            ->willReturn([1]);

        // importante: inscrito
        $this->inscripcionTable
            ->method('estudianteInscrito')
            ->willReturn(true);

        // evitamos insert real
        $this->attendanceListStudentTable
            ->method('insert')
            ->willReturn(true);

        $this->attendanceService->registrarEstudiantesMasivo(
            1,
            $carnets,
            1,
            1,
            1,
            false
        );

        $this->assertTrue(true);
    }

    public function testMasivoErrorIndividual(): void
    {
        // funcion: registrarEstudiantesMasivo
        // caso: un estudiante falla, otro se procesa

        $carnets = [
            ['carnet' => 201700841],
            ['carnet' => 201700842]
        ];

        // estudiante existe siempre
        $this->estudianteService
            ->method('existePorCarnet')
            ->willReturn(true);

        // carrera valida siempre
        $this->carreraEstudianteTable
            ->method('getCarrerasPorEstudiante')
            ->willReturn([1]);

        // inscrito siempre
        $this->inscripcionTable
            ->method('estudianteInscrito')
            ->willReturn(true);

        // simulamos que el insert falle SOLO en el segundo
        $this->attendanceListStudentTable
            ->method('insert')
            ->willReturnCallback(function ($data) {

                if ($data['carnet'] === 201700842) {
                    throw new \Exception("error simulado");
                }

                return true;
            });

        try {
            $this->attendanceService->registrarEstudiantesMasivo(
                1,
                $carnets,
                1,
                1,
                1,
                false
            );

            $this->fail("deberia lanzar excepcion");
        } catch (\Exception $e) {

            $this->assertStringContainsString(
                "no cumplen los requisitos",
                $e->getMessage()
            );
        }
    }

    //aprobarLista

    public function testAprobarListaNoExiste(): void
    {
        // caso: la lista no existe

        // simulamos que no existe la lista
        $this->attendanceListTable
            ->method('find')
            ->willReturn(null);

        $this->expectException(\Exception::class);

        $this->expectExceptionMessage("La lista no existe.");

        $this->attendanceService->aprobarLista(1);
    }

    private function makeListaMock(int $estado = 0, int $actividadId = 10): AttendanceList
    {
        $listaMock = $this->createMock(AttendanceList::class);
        $listaMock->estado = $estado;
        $listaMock->id_actividad = $actividadId;

        return $listaMock;
    }

    public function testAprobarListaEstadoInvalido(): void
    {
        // caso: la lista existe pero su estado no es 0

        // simulamos que la lista existe
        $this->attendanceListTable
            ->method('find')
            ->willReturn($this->makeListaMock(1));

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // mensaje esperado
        $this->expectExceptionMessage("Solo se pueden aprobar listas pendientes.");

        // ejecutamos metodo
        $this->attendanceService->aprobarLista(1);
    }

    public function testAprobarListaSinEstudiantes(): void
    {
        // caso: lista valida pero sin estudiantes asociados

        // simulamos que la lista existe
        $this->attendanceListTable
            ->method('find')
            ->willReturn($this->makeListaMock(0));

        // simulamos que no hay estudiantes asociados
        $this->attendanceListStudentTable
            ->method('getByList')
            ->willReturn([]); // lista vacia

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // validamos mensaje exacto
        $this->expectExceptionMessage("No hay estudiantes asociados a esta lista.");

        // ejecutamos metodo
        $this->attendanceService->aprobarLista(1);
    }

    public function testAprobarListaSinDuplicados(): void
    {
        // caso: inserta asistencia solo si no existe duplicado

        // id de lista
        $listaId = 1;

        // simulamos que la lista existe
        $this->attendanceListTable
            ->method('find')
            ->willReturn($this->makeListaMock(0));

        // simulamos estudiantes en la lista
        $this->attendanceListStudentTable
            ->method('getByList')
            ->willReturn([
                ['carnet' => 201700841],
                ['carnet' => 201700842]
            ]);

        // simulamos que NO existen duplicados (siempre false)
        $this->studentAttendanceTable
            ->method('exists')
            ->willReturn(false);

        // verificamos que se inserten los registros de asistencia
        $this->studentAttendanceTable
            ->expects($this->exactly(2))
            ->method('insert')
            ->with($this->anything());

        // simulamos update de estado
        $this->attendanceListTable
            ->method('updateStatus')
            ->with($listaId, 1);

        // simulamos update de fecha de aprobacion
        $this->attendanceListTable
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {
                    // validamos que exista la clave aprobado
                    return isset($data['aprobado']);
                }),
                ['id' => $listaId]
            );

        // ejecutamos metodo
        $this->attendanceService->aprobarLista($listaId);
    }

    public function testAprobarListaOk(): void
    {
        // caso: flujo completo exitoso

        // id de lista a aprobar
        $listaId = 1;

        // simulamos que la lista existe
        $this->attendanceListTable
            ->method('find')
            ->willReturn($this->makeListaMock(0));

        // simulamos estudiantes asociados
        $this->attendanceListStudentTable
            ->method('getByList')
            ->willReturn([
                ['carnet' => 201700841],
                ['carnet' => 201700842]
            ]);

        // simulamos que no existen duplicados
        $this->studentAttendanceTable
            ->method('exists')
            ->willReturn(false);

        // validamos inserciones de asistencia
        $this->studentAttendanceTable
            ->expects($this->exactly(2))
            ->method('insert')
            ->with($this->anything());

        // validamos cambio de estado
        $this->attendanceListTable
            ->expects($this->once())
            ->method('updateStatus')
            ->with($listaId, 1);

        // validamos actualización de fecha
        $this->attendanceListTable
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {
                    // validamos que exista la fecha de aprobacion
                    return isset($data['aprobado']);
                }),
                ['id' => $listaId]
            );

        // ejecutamos metodo
        $this->attendanceService->aprobarLista($listaId);

        // validacion final simple
        $this->assertTrue(true);
    }

    //crearListaVacia

    public function testCrearListaVaciaSinUsuario(): void
    {
        // caso: no existe usuario autenticado

        // obtenemos propiedad privada session
        $ref = new \ReflectionClass($this->attendanceService);

        // obtenemos atributo session
        $prop = $ref->getProperty('session');

        // permitimos acceso
        $prop->setAccessible(true);

        // creamos contenedor vacio
        $session = new Container('user');

        // eliminamos userId
        unset($session->userId);

        // reemplazamos session interna
        $prop->setValue($this->attendanceService, $session);

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // validamos mensaje
        $this->expectExceptionMessage(
            'No hay usuario autenticado.'
        );

        // ejecutamos metodo
        $this->attendanceService->crearListaVacia(1);
    }

    public function testCrearListaVaciaOk(): void
    {
        // caso: inserta correctamente una lista vacia

        // obtenemos propiedad privada session
        $ref = new \ReflectionClass($this->attendanceService);

        // obtenemos atributo session
        $prop = $ref->getProperty('session');

        // permitimos acceso
        $prop->setAccessible(true);

        // creamos contenedor de sesion
        $session = new Container('user');

        // simulamos usuario autenticado
        $session->userId = 10;

        // reemplazamos session interna
        $prop->setValue($this->attendanceService, $session);

        // simulamos id retornado por insert
        $this->attendanceListTable
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function ($data) {

                // validamos campos importantes
                return $data['id_actividad'] === 1
                    && $data['id_usuario'] === 10
                    && $data['estado'] === 0
                    && array_key_exists('creado', $data)
                    && $data['aprobado'] === null;
            }))
            ->willReturn(25);

        // ejecutamos metodo
        $resultado = $this->attendanceService->crearListaVacia(1);

        // validamos id retornado
        $this->assertEquals(25, $resultado);
    }

    //obtenerDetalleLista

    public function testDetalleVacio(): void
    {
        // caso: la lista no tiene estudiantes

        // simulamos consulta vacia
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('getCarnetsPorLista')
            ->with($this->equalTo(1))
            ->willReturn([]);

        // ejecutamos metodo
        $resultado = $this->attendanceService->obtenerDetalleLista(1);

        // validamos resultado vacio
        $this->assertEquals([], $resultado);
    }

    public function testDetalleOk(): void
    {
        // caso: combina carnets con nombres

        // simulamos carnets encontrados en la lista
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('getCarnetsPorLista')
            ->with($this->equalTo(1))
            ->willReturn([
                ['carnet' => 201700841],
                ['carnet' => 201700842]
            ]);

        // simulamos informacion de estudiantes
        $this->studentTable
            ->expects($this->once())
            ->method('getPorCarnets')
            ->with(
                [201700841, 201700842]
            )
            ->willReturn([
                [
                    'carnet' => 201700841,
                    'nombre' => 'Luis'
                ],
                [
                    'carnet' => 201700842,
                    'nombre' => 'Antonio'
                ]
            ]);

        // ejecutamos metodo
        $resultado = $this->attendanceService->obtenerDetalleLista(1);

        // validamos resultado esperado
        $this->assertEquals(
            [
                [
                    'carnet' => 201700841,
                    'nombre' => 'Luis'
                ],
                [
                    'carnet' => 201700842,
                    'nombre' => 'Antonio'
                ]
            ],
            $resultado
        );
    }

    public function testDetalleNoEncontrado(): void
    {
        // caso: estudiante no encontrado en la consulta de nombres

        // simulamos carnet existente en la lista
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('getCarnetsPorLista')
            ->with($this->equalTo(1))
            ->willReturn([
                ['carnet' => 201700841]
            ]);

        // simulamos que no se encontro informacion del estudiante
        $this->studentTable
            ->expects($this->once())
            ->method('getPorCarnets')
            ->with(
                [201700841]
            )
            ->willReturn([]);

        // ejecutamos metodo
        $resultado = $this->attendanceService->obtenerDetalleLista(1);

        // validamos valor por defecto
        $this->assertEquals(
            [
                [
                    'carnet' => 201700841,
                    'nombre' => 'No encontrado'
                ]
            ],
            $resultado
        );
    }

    //registrarEstudianteIndividual

    public function testIndividualSinActividad(): void
    {
        // caso: la actividad no existe

        // simulamos actividad inexistente
        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadPorId')
            ->with(1)
            ->willReturn(null);

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // validamos mensaje
        $this->expectExceptionMessage(
            'La actividad no existe.'
        );

        // ejecutamos metodo
        $this->attendanceService->registrarEstudianteIndividual(
            1,
            201700841
        );
    }

    private function makeStudentMock(int $carnet): Student
    {
        $student = $this->createMock(Student::class);

        $student->carnet = $carnet;

        return $student;
    }

    public function testIndividualSinUsuario(): void
    {
        // caso: no existe usuario autenticado

        // simulamos actividad valida
        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadPorId')
            ->with(1)
            ->willReturn(['id' => 1]);

        // simulamos estudiante existente
        $estudiante = $this->makeStudentMock(201700841);

        // simulamos consulta de estudiante
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with(201700841)
            ->willReturn($estudiante);

        // obtenemos propiedad session
        $ref = new \ReflectionClass($this->attendanceService);

        // obtenemos atributo session
        $prop = $ref->getProperty('session');

        // permitimos acceso
        $prop->setAccessible(true);

        // creamos sesion vacia
        $session = new Container('user');

        // eliminamos userId
        unset($session->userId);

        // reemplazamos session interna
        $prop->setValue($this->attendanceService, $session);

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // validamos mensaje
        $this->expectExceptionMessage(
            'No hay usuario autenticado.'
        );

        // ejecutamos metodo
        $this->attendanceService->registrarEstudianteIndividual(
            1,
            201700841
        );
    }

    public function testIndividualCreaEstudiante(): void
    {
        // caso: estudiante no existe y se crea correctamente

        // simulamos actividad valida
        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadPorId')
            ->with(1)
            ->willReturn(['id' => 1]);

        // simulamos estudiante inexistente
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with(201700841)
            ->willReturn(null);

        // validamos creacion del estudiante
        $this->studentTable
            ->expects($this->once())
            ->method('insert')
            ->with([
                'carnet' => 201700841,
                'nombre' => 'Desconocido',
                'activo' => 1
            ])
            ->willReturn(201700841);

        // simulamos usuario autenticado
        $ref = new \ReflectionClass($this->attendanceService);

        // obtenemos propiedad session
        $prop = $ref->getProperty('session');

        // permitimos acceso
        $prop->setAccessible(true);

        // creamos sesion
        $session = new Container('user');

        // asignamos usuario
        $session->userId = 10;

        // reemplazamos sesion interna
        $prop->setValue($this->attendanceService, $session);

        // validamos creacion de lista
        $this->attendanceListTable
            ->expects($this->once())
            ->method('insert')
            ->willReturn(50);

        // validamos relacion lista estudiante
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('insert')
            ->with([
                'id_attendance_list' => 50,
                'carnet' => 201700841
            ]);

        // ejecutamos metodo
        $this->attendanceService->registrarEstudianteIndividual(
            1,
            201700841
        );

        // validacion final
        $this->assertTrue(true);
    }

    public function testIndividualOk(): void
    {
        // funcion: registrarEstudianteIndividual
        // caso: estudiante ya existe

        // simulamos actividad valida
        $this->activityService
            ->expects($this->once())
            ->method('obtenerActividadPorId')
            ->with(1)
            ->willReturn(['id' => 1]);

        // simulamos estudiante existente
        $estudiante = $this->makeStudentMock(201700841);

        // retornamos estudiante encontrado
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with(201700841)
            ->willReturn($estudiante);

        // verificamos que no se cree estudiante nuevo
        $this->studentTable
            ->expects($this->never())
            ->method('insert');

        // obtenemos propiedad session
        $ref = new \ReflectionClass($this->attendanceService);

        // obtenemos atributo session
        $prop = $ref->getProperty('session');

        // permitimos acceso
        $prop->setAccessible(true);

        // creamos sesion
        $session = new Container('user');

        // simulamos usuario autenticado
        $session->userId = 10;

        // reemplazamos sesion interna
        $prop->setValue($this->attendanceService, $session);

        // simulamos creacion de lista
        $this->attendanceListTable
            ->expects($this->once())
            ->method('insert')
            ->willReturn(50);

        // validamos relacion lista estudiante
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('insert')
            ->with([
                'id_attendance_list' => 50,
                'carnet' => 201700841
            ]);

        // ejecutamos metodo
        $this->attendanceService->registrarEstudianteIndividual(
            1,
            201700841
        );

        // validacion final
        $this->assertTrue(true);
    }

    //eliminarLista

    public function testEliminarLista(): void
    {
        // caso: elimina estudiantes y lista

        // validamos eliminacion de asistencias
        $this->studentAttendanceTable
            ->expects($this->once())
            ->method('delete')
            ->with([
                'id_attendance_list' => 5
            ]);

        // validamos eliminacion de lista
        $this->attendanceListTable
            ->expects($this->once())
            ->method('delete')
            ->with([
                'id' => 5
            ]);

        // ejecutamos metodo
        $this->attendanceService->eliminarLista(5);

        // validacion final
        $this->assertTrue(true);
    }

    //obtenerListasPorActividad

    public function testObtenerListas(): void
    {
        // caso: retorna listas de una actividad

        // datos simulados
        $listas = [
            [
                'id' => 1,
                'nombre' => "actividad1"
            ],
            [
                'id' => 2,
                'nombre' => "actividad2"
            ]
        ];

        // simulamos consulta
        $this->attendanceListTable
            ->expects($this->once())
            ->method('getByActivity')
            ->with(10)
            ->willReturn($listas);

        // ejecutamos metodo
        $resultado = $this->attendanceService
            ->obtenerListasPorActividad(10);

        // validamos resultado
        $this->assertEquals(
            $listas,
            $resultado
        );
    }

    //obtenerLista

    public function testObtenerLista(): void
    {
        // caso: retorna estudiantes de una lista

        // datos simulados
        $estudiantes = [
            [
                'carnet' => 201700841,
                'nombre' => 'Luis'
            ],
            [
                'carnet' => 201700842,
                'nombre' => 'Antonio'
            ]
        ];

        // simulamos consulta
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('getByList')
            ->with(5)
            ->willReturn($estudiantes);

        // ejecutamos metodo
        $resultado = $this->attendanceService
            ->obtenerLista(5);

        // validamos resultado
        $this->assertEquals(
            $estudiantes,
            $resultado
        );
    }

    //obtenerListaPorActividad

    public function testObtenerListaPorActividad(): void
    {
        // caso: retorna lista de una actividad

        // creamos lista simulada
        $lista = $this->makeListaMock(
            0,
            10
        );

        // simulamos consulta
        $this->attendanceListTable
            ->expects($this->once())
            ->method('fetchByActivity')
            ->with(10)
            ->willReturn($lista);

        // ejecutamos metodo
        $resultado = $this->attendanceService
            ->obtenerListaPorActividad(10);

        // validamos resultado
        $this->assertSame(
            $lista,
            $resultado
        );
    }

    //obtenerEstudiantePorActividad

    public function testEstudianteActividadSinLista(): void
    {
        // caso: actividad sin listas

        // simulamos que no existen listas
        $this->attendanceListTable
            ->expects($this->once())
            ->method('getByActivity')
            ->with(10)
            ->willReturn([]);

        // no deberia consultar estudiantes
        $this->attendanceListStudentTable
            ->expects($this->never())
            ->method('getDetalleLista');

        // ejecutamos metodo
        $resultado = $this->attendanceService
            ->obtenerEstudiantePorActividad(10);

        // validamos retorno
        $this->assertNull(
            $resultado
        );
    }

    //obtenerEstudiantePorActividad

    public function testEstudianteActividadSinEstudiantes(): void
    {
        // caso: existe lista pero no hay estudiantes

        // simulamos lista encontrada
        $this->attendanceListTable
            ->expects($this->once())
            ->method('getByActivity')
            ->with(10)
            ->willReturn([
                [
                    'id' => 5
                ]
            ]);

        // simulamos lista sin estudiantes
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('getDetalleLista')
            ->with(5)
            ->willReturn([]);

        // ejecutamos metodo
        $resultado = $this->attendanceService
            ->obtenerEstudiantePorActividad(10);

        // validamos resultado
        $this->assertNull(
            $resultado
        );
    }

    public function testEstudianteActividadOk(): void
    {
        // caso: retorna el primer estudiante

        // simulamos lista encontrada
        $this->attendanceListTable
            ->expects($this->once())
            ->method('getByActivity')
            ->with(10)
            ->willReturn([
                [
                    'id' => 5
                ]
            ]);

        // estudiante esperado
        $estudiante = [
            'carnet' => 201700841,
            'nombre' => 'Luis'
        ];

        // simulamos estudiantes asociados
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('getDetalleLista')
            ->with(5)
            ->willReturn([
                $estudiante,
                [
                    'carnet' => 201700842,
                    'nombre' => 'Antonio'
                ]
            ]);

        // ejecutamos metodo
        $resultado = $this->attendanceService
            ->obtenerEstudiantePorActividad(10);

        // validamos retorno
        $this->assertEquals(
            $estudiante,
            $resultado
        );
    }

    //reemplazarLista

    public function testReemplazarLista(): void
    {
        // creamos servicio parcial
        $service = $this->getMockBuilder(AttendanceService::class)
            ->setConstructorArgs([
                $this->attendanceListTable,
                $this->attendanceListStudentTable,
                $this->studentAttendanceTable,
                $this->studentTable,
                $this->activityService,
                $this->carreraEstudianteTable,
                $this->inscripcionTable,
                $this->estudianteService
            ])
            ->onlyMethods([
                'obtenerListaPorActividad',
                'crearLista'
            ])
            ->getMock();

        // creamos lista simulada
        $lista = $this->makeListaMock();

        // agregamos id de la lista
        $lista->id = 5;

        // simulamos lista existente
        $service->expects($this->once())
            ->method('obtenerListaPorActividad')
            ->with(10)
            ->willReturn($lista);

        // validamos cambio de estado
        $this->attendanceListTable
            ->expects($this->once())
            ->method('update')
            ->with(
                ['estado' => 2],
                ['id' => 5]
            );

        // validamos creacion de nueva lista
        $service->expects($this->once())
            ->method('crearLista')
            ->with(
                10,
                ['name' => 'archivo.xlsx']
            );

        // ejecutamos metodo
        $service->reemplazarLista(
            10,
            ['name' => 'archivo.xlsx']
        );

        // validacion final
        $this->assertTrue(true);
    }

    //actualizarEstudianteIndividual

    public function testActualizarSinLista(): void
    {
        // caso: no existe lista para la actividad

        // simulamos que no existe lista
        $this->attendanceListTable
            ->expects($this->once())
            ->method('fetchByActividad')
            ->with(10)
            ->willReturn(null);

        // no deberia consultar estudiante asociado
        $this->attendanceListStudentTable
            ->expects($this->never())
            ->method('fetchByLista');

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // validamos mensaje
        $this->expectExceptionMessage(
            'No hay lista registrada para esta actividad.'
        );

        // ejecutamos metodo
        $this->attendanceService
            ->actualizarEstudianteIndividual(
                10,
                201700841
            );
    }

    public function testActualizarSinEstudiante(): void
    {
        // caso: lista existe pero no tiene estudiante asociado

        // simulamos lista encontrada
        $this->attendanceListTable
            ->expects($this->once())
            ->method('fetchByActividad')
            ->with(10)
            ->willReturn([
                'id' => 5
            ]);

        // simulamos que no existe estudiante asociado
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('fetchByLista')
            ->with(5)
            ->willReturn(null);

        // no deberia consultar estudiante nuevo
        $this->studentTable
            ->expects($this->never())
            ->method('getByCarnet');

        // esperamos excepcion
        $this->expectException(\Exception::class);

        // validamos mensaje
        $this->expectExceptionMessage(
            'No hay estudiante asociado a esta actividad.'
        );

        // ejecutamos metodo
        $this->attendanceService
            ->actualizarEstudianteIndividual(
                10,
                201700841
            );
    }

    public function testActualizarOk(): void
    {
        // caso: actualiza relacion con estudiante existente

        // simulamos lista encontrada
        $this->attendanceListTable
            ->expects($this->once())
            ->method('fetchByActividad')
            ->with(10)
            ->willReturn([
                'id' => 5
            ]);

        // simulamos estudiante asociado
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('fetchByLista')
            ->with(5)
            ->willReturn([
                'id' => 99,
                'carnet' => 201700840
            ]);

        // creamos estudiante existente
        $estudiante = $this->makeStudentMock(
            201700841
        );

        // simulamos busqueda por carnet
        $this->studentTable
            ->expects($this->once())
            ->method('getByCarnet')
            ->with(201700841)
            ->willReturn($estudiante);

        // no deberia crear estudiante nuevo
        $this->studentTable
            ->expects($this->never())
            ->method('insert');

        // validamos actualizacion
        $this->attendanceListStudentTable
            ->expects($this->once())
            ->method('update')
            ->with(
                [
                    'carnet' => 201700841
                ],
                [
                    'id' => 99
                ]
            );

        // ejecutamos metodo
        $this->attendanceService
            ->actualizarEstudianteIndividual(
                10,
                201700841
            );

        // validacion final
        $this->assertTrue(true);
    }
}

/*

vendor/bin/phpunit module/Application/test/Service/AttendanceServiceTest.php

*/
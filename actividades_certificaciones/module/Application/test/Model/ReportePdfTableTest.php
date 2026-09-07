<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\ReportePdfTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;

use Laminas\Db\Adapter\Adapter;


class ReportePdfTableTest extends TestCase
{
    private ReportePdfTable $table;
    private TableGateway $tableGateway;
    private string $schema;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);

        $this->schema = 'satu_test';

        $this->table = new ReportePdfTable(
            $this->tableGateway,
            $this->schema
        );
    }

    //insert
    public function testInsertCallsTableGatewayInsert(): void
    {
        $data = [
            'tipo' => 'CONSTANCIA',
            'correlativo' => 'EST-2025-0001',
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($data);

        $this->tableGateway
            ->method('getLastInsertValue')
            ->willReturn(1);

        $this->table->insert($data);
    }

    public function testInsertReturnsLastInsertId(): void
    {
        $data = [
            'tipo' => 'CONSTANCIA',
            'correlativo' => 'EST-2025-0001',
        ];

        // Simula el insert en la tabla
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($data);

        // Simula el último ID insertado retornado por la BD
        $this->tableGateway
            ->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn(15);

        // Ejecuta el método
        $result = $this->table->insert($data);

        // Verifica que el método retorne el ID insertado
        $this->assertSame(15, $result);
    }

    public function testBuscarPorCorrelativoReturnsArrayFromSelectWith(): void
    {
        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Mock del objeto Select
        $select = new Select('reporte_pdf');

        // Resultados simulados
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([
            [
                'id' => 1,
                'correlativo' => 'EST-2025-0001',
            ],
            [
                'id' => 2,
                'correlativo' => 'EST-2025-0002',
            ],
        ]);

        // Simula getSql()
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Simula selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->isInstanceOf(\Laminas\Db\Sql\Select::class))
            ->willReturn($resultSet);

        // Ejecuta el método
        $result = $this->table->buscarPorCorrelativo();

        // Verifica que se convierta correctamente a array
        $this->assertIsArray($result);

        // Verifica cantidad de resultados
        $this->assertCount(2, $result);

        // Verifica contenido
        $this->assertSame('EST-2025-0001', $result[0]['correlativo']);
        $this->assertSame('EST-2025-0002', $result[1]['correlativo']);
    }

    //buscarPorCorrelativo

    public function testBuscarPorCorrelativoAppliesLikeFilterWhenQueryExists(): void
    {
        // Valor utilizado para el filtro LIKE
        $query = 'EST';

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para inspeccionar el WHERE
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Verifica el Select enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use ($query) {

                // Obtiene los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre predicates
                foreach ($predicates as $predicate) {

                    // El predicate real está en la posición 1
                    $like = $predicate[1];

                    // Verifica que sea un LIKE
                    if ($like instanceof \Laminas\Db\Sql\Predicate\Like) {

                        // Verifica columna utilizada
                        $this->assertSame(
                            'reporte_pdf.correlativo',
                            $like->getIdentifier()
                        );

                        // Verifica valor LIKE
                        $this->assertSame(
                            "%{$query}%",
                            $like->getLike()
                        );

                        return true;
                    }
                }

                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta método
        $this->table->buscarPorCorrelativo($query);
    }

    public function testBuscarPorCorrelativoQueryIsNull(): void
    {
        // Mock del objeto Sql utilizado internamente
        $sql = $this->createMock(Sql::class);

        // Se usa un Select real para poder inspeccionar
        // los predicates generados en el WHERE
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula el getSql() del TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula la creación del Select
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Verifica el Select enviado al selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre cada predicate existente
                foreach ($predicates as $predicate) {

                    // El predicate real está en la posición 1
                    $currentPredicate = $predicate[1];

                    // Si existe algún LIKE, el test debe fallar
                    // porque cuando $q es null NO debe agregarse
                    // ningún filtro LIKE
                    if ($currentPredicate instanceof \Laminas\Db\Sql\Predicate\Like) {
                        return false;
                    }
                }

                // Si nunca encontró un LIKE, el test pasa
                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método SIN parámetro
        // Esto provoca que $q sea null
        $this->table->buscarPorCorrelativo();
    }

    //buscarPorCorrelativo

    public function testBuscarPorCorrelativoOrdersByFechaGeneradoDesc(): void
    {
        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para inspeccionar posteriormente
        // el ORDER BY construido por el método
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Verifica el Select enviado al selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene el ORDER BY generado
                $order = $select->getRawState('order');

                // Verifica que exista exactamente
                // el orden esperado
                $this->assertSame(
                    ['reporte_pdf.fecha_generado DESC'],
                    $order
                );

                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->buscarPorCorrelativo();
    }

    //Filtra por carnet

    public function testBuscarConstanciasPorCorrelativoFiltersByCarnet(): void
    {
        // Carnet utilizado para el filtro
        $carnet = 2020001;

        // Correlativo de ejemplo
        $correlativo = 'EST';

        // Mock del adapter porque el método crea:
        // new Sql($this->tableGateway->getAdapter())
        $adapter = $this->createMock(
            Adapter::class
        );

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula el adapter retornado por el TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getAdapter')
            ->willReturn($adapter);

        // Verifica el Select enviado al selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use ($carnet) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre predicates buscando el equalTo
                foreach ($predicates as $predicate) {

                    // El predicate real siempre está
                    // en la posición 1
                    $currentPredicate = $predicate[1];

                    // Verifica si es un Operator (=)
                    if (
                        $currentPredicate instanceof \Laminas\Db\Sql\Predicate\Operator
                    ) {

                        // Verifica columna utilizada
                        $this->assertSame(
                            'rp.carnet',
                            $currentPredicate->getLeft()
                        );

                        // Verifica operador =
                        $this->assertSame(
                            '=',
                            $currentPredicate->getOperator()
                        );

                        // Verifica valor enviado
                        $this->assertSame(
                            $carnet,
                            $currentPredicate->getRight()
                        );

                        return true;
                    }
                }

                // Si nunca encontró el filtro,
                // el test falla
                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->buscarConstanciasPorCorrelativo(
            $correlativo,
            $carnet
        );
    }

    public function testBuscarConstanciasPorCorrelativoAppliesLikeFilterWhenCorrelativoIsNotEmpty(): void
    {
        // Correlativo utilizado para el filtro LIKE
        $correlativo = 'EST';

        // Carnet de ejemplo
        $carnet = 2020001;

        // Mock del adapter porque el método internamente crea:
        // new Sql($this->tableGateway->getAdapter())
        $adapter = $this->createMock(
            Adapter::class
        );

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula el adapter retornado por el TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getAdapter')
            ->willReturn($adapter);

        // Verifica el Select construido y enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use ($correlativo) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre todos los predicates existentes
                foreach ($predicates as $predicate) {

                    // El predicate real siempre está
                    // en la posición 1
                    $currentPredicate = $predicate[1];

                    // Verifica si el predicate es un LIKE
                    if (
                        $currentPredicate instanceof \Laminas\Db\Sql\Predicate\Like
                    ) {

                        // Verifica la columna utilizada
                        $this->assertSame(
                            'rp.correlativo',
                            $currentPredicate->getIdentifier()
                        );

                        // Verifica el valor LIKE generado
                        $this->assertSame(
                            "%{$correlativo}%",
                            $currentPredicate->getLike()
                        );

                        return true;
                    }
                }

                // Si nunca encontró un LIKE,
                // el test debe fallar
                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->buscarConstanciasPorCorrelativo(
            $correlativo,
            $carnet
        );
    }

    //no aplica like si correlativo está vacío

    public function testBuscarConstanciasPorCorrelativoDoesNotApplyLikeWhenCorrelativoIsEmpty(): void
    {
        // Correlativo vacío
        $correlativo = '';

        // Carnet de ejemplo
        $carnet = 2020001;

        // Mock del adapter porque el método crea:
        // new Sql($this->tableGateway->getAdapter())
        $adapter = $this->createMock(
            Adapter::class
        );

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula el adapter retornado por el TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getAdapter')
            ->willReturn($adapter);

        // Verifica el Select enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre cada predicate
                foreach ($predicates as $predicate) {

                    // El predicate real está en la posición 1
                    $currentPredicate = $predicate[1];

                    // Si encuentra un LIKE,
                    // el test debe fallar porque
                    // correlativo está vacío
                    if (
                        $currentPredicate instanceof \Laminas\Db\Sql\Predicate\Like
                    ) {
                        return false;
                    }
                }

                // Si nunca encontró LIKE,
                // entonces el test pasa
                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método con correlativo vacío
        $this->table->buscarConstanciasPorCorrelativo(
            $correlativo,
            $carnet
        );
    }

    //orden correcto desc

    public function testBuscarConstanciasPorCorrelativoOrdersByFechaGeneradoDesc(): void
    {
        // Datos de prueba
        $correlativo = 'EST';
        $carnet = 2020001;

        // Mock del adapter porque el método construye manualmente
        $adapter = $this->createMock(
            Adapter::class
        );

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula el adapter retornado por el TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getAdapter')
            ->willReturn($adapter);

        // Intercepta el Select enviado al selectWith()
        // para inspeccionar el ORDER BY generado
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene el ORDER BY interno del Select
                $order = $select->getRawState('order');

                // Verifica que el ORDER BY sea exactamente:
                // rp.fecha_generado DESC
                $this->assertSame(
                    ['rp.fecha_generado DESC'],
                    $order
                );

                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->buscarConstanciasPorCorrelativo(
            $correlativo,
            $carnet
        );
    }

    //buscar por correlativo exacto
    public function testObtenerPorCorrelativoExacto(): void
    {
        // Correlativo utilizado para la búsqueda
        $correlativo = 'EST-2025-0001';

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para poder inspeccionar internamente
        // los predicates generados en el WHERE
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql() del TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Intercepta el Select enviado a selectWith()
        // para inspeccionar el WHERE generado
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use ($correlativo) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre cada predicate existente
                foreach ($predicates as $predicate) {

                    // El predicate real está en la posición 1
                    $currentPredicate = $predicate[1];

                    // Busca el predicate de tipo Operator (=)
                    if (
                        $currentPredicate instanceof \Laminas\Db\Sql\Predicate\Operator
                    ) {

                        // Verifica que la columna utilizada
                        // sea reporte_pdf.correlativo
                        $this->assertSame(
                            'reporte_pdf.correlativo',
                            $currentPredicate->getLeft()
                        );

                        // Verifica que el operador sea "="
                        $this->assertSame(
                            '=',
                            $currentPredicate->getOperator()
                        );

                        // Verifica que el valor comparado
                        // sea exactamente el correlativo enviado
                        $this->assertSame(
                            $correlativo,
                            $currentPredicate->getRight()
                        );

                        return true;
                    }
                }

                // Si nunca encontró el filtro exacto,
                // el test debe fallar
                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->obtenerPorCorrelativo($correlativo);
    }

    //retornar current

    public function testObtenerPorCorrelativoReturnsCurrentRow(): void
    {
        // Correlativo utilizado para la búsqueda
        $correlativo = 'EST-2025-0001';

        // Registro esperado
        $expectedRow = [
            'id' => 1,
            'correlativo' => $correlativo,
            'tipo' => 'CONSTANCIA',
        ];

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real
        $select = new Select('reporte_pdf');

        // ResultSet simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        // Inicializa el ResultSet con datos
        $resultSet->initialize([
            $expectedRow,
        ]);

        // Simula getSql()
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Simula selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->isInstanceOf(Select::class))
            ->willReturn($resultSet);

        // Ejecuta el método
        $result = $this->table->obtenerPorCorrelativo($correlativo);

        // Verifica que retorne únicamente
        // el primer registro del ResultSet de tipo ArrayObject
        $this->assertSame(
            $expectedRow,
            $result->getArrayCopy()
        );
    }

    //obtenerConstanciasPorEstudiante
    public function testObtenerConstanciasPorEstudianteFiltersByCarnet(): void
    {
        // Carnet utilizado para la búsqueda
        $carnet = 2020001;

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para poder inspeccionar internamente
        // los predicates generados en el WHERE
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql() del TableGateway
        $this->tableGateway
            ->expects($this->once())
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->expects($this->once())
            ->method('select')
            ->willReturn($select);

        // Intercepta el Select enviado a selectWith()
        // para inspeccionar el WHERE generado
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) use ($carnet) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Recorre cada predicate existente
                foreach ($predicates as $predicate) {

                    // El predicate real está en la posición 1
                    $currentPredicate = $predicate[1];

                    // Busca el predicate de tipo Operator (=)
                    if (
                        $currentPredicate instanceof \Laminas\Db\Sql\Predicate\Operator
                    ) {

                        // Verifica la columna utilizada
                        $this->assertSame(
                            'reporte_pdf.carnet',
                            $currentPredicate->getLeft()
                        );

                        // Verifica que el operador sea "="
                        $this->assertSame(
                            '=',
                            $currentPredicate->getOperator()
                        );

                        // Verifica el valor comparado
                        $this->assertSame(
                            $carnet,
                            $currentPredicate->getRight()
                        );

                        return true;
                    }
                }

                // Si nunca encontró el filtro,
                // el test debe fallar
                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->obtenerConstanciasPorEstudiante($carnet);
    }

    public function testObtenerConstanciasPorEstudianteFiltersOnlyEstCorrelativos(): void
    {
        // Carnet de ejemplo
        $carnet = 2020001;

        // Mock de Sql
        $sql = $this->createMock(Sql::class);

        // Select real para inspeccionar el WHERE
        $select = new Select('reporte_pdf');

        // ResultSet vacío
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Verifica el Select generado
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Busca un LIKE
                foreach ($predicates as $predicate) {

                    $current = $predicate[1];

                    if (
                        $current instanceof \Laminas\Db\Sql\Predicate\Like
                    ) {

                        // Verifica columna
                        $this->assertSame(
                            'reporte_pdf.correlativo',
                            $current->getIdentifier()
                        );

                        // Verifica valor LIKE
                        $this->assertSame(
                            'EST-%',
                            $current->getLike()
                        );

                        return true;
                    }
                }

                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta método
        $this->table->obtenerConstanciasPorEstudiante($carnet);
    }

    //orden de forma descendnente
    public function testObtenerConstanciasPorEstudianteOrdersByFechaGeneradoDesc(): void
    {
        // Carnet de ejemplo
        $carnet = 2020001;

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para inspeccionar el ORDER BY
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Intercepta el Select enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene el ORDER BY interno
                $order = $select->getRawState('order');

                // Verifica el orden esperado
                $this->assertSame(
                    ['fecha_generado DESC'],
                    $order
                );

                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->obtenerConstanciasPorEstudiante($carnet);
    }

    //gerear el primer correlativo

    public function testGenerarCorrelativoReturnsInitialCorrelativoWhenNoRecordsExist(): void
    {
        // Datos de prueba
        $prefijo = 'EST';
        $anio = 2025;

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real
        $select = new Select('reporte_pdf');

        // ResultSet vacío
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Simula selectWith() sin resultados
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->isInstanceOf(Select::class))
            ->willReturn($resultSet);

        // Ejecuta el método
        $result = $this->table->generarCorrelativo(
            $prefijo,
            $anio
        );

        // Verifica el correlativo inicial esperado
        $this->assertSame(
            'EST-2025-0001',
            $result
        );
    }

    //probamos si funciona incrementar en 1 el test
    public function testGenerarCorrelativoIncrementsLastCorrelativo(): void
    {
        // Datos de prueba
        $prefijo = 'EST';
        $anio = 2025;

        // Último correlativo existente
        $lastRow = [
            'correlativo' => 'EST-2025-0015',
        ];

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real
        $select = new Select('reporte_pdf');

        // ResultSet con un registro existente
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([
            $lastRow,
        ]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Simula selectWith() retornando
        // el último correlativo encontrado
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->willReturn($resultSet);

        // Ejecuta el método
        $result = $this->table->generarCorrelativo(
            $prefijo,
            $anio
        );

        // Verifica que genere el siguiente correlativo
        $this->assertSame(
            'EST-2025-0016',
            $result
        );
    }

    public function testGenerarCorrelativoAppliesCorrectLikeFilter(): void
    {
        // Datos de prueba
        $prefijo = 'EST';
        $anio = 2025;

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para inspeccionar el WHERE
        $select = new Select('reporte_pdf');

        // ResultSet vacío
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Intercepta el Select enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene todos los predicates del WHERE
                $predicates = $select->where->getPredicates();

                // Busca el predicate LIKE
                foreach ($predicates as $predicate) {

                    $current = $predicate[1];

                    if (
                        $current instanceof \Laminas\Db\Sql\Predicate\Like
                    ) {

                        // Verifica columna utilizada
                        $this->assertSame(
                            'correlativo',
                            $current->getIdentifier()
                        );

                        // Verifica patrón LIKE esperado
                        $this->assertSame(
                            'EST-2025-%',
                            $current->getLike()
                        );

                        return true;
                    }
                }

                return false;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->generarCorrelativo(
            $prefijo,
            $anio
        );
    }

    //ordenar de forma descendente
    public function testGenerarCorrelativoOrdersByIdDesc(): void
    {
        // Datos de prueba
        $prefijo = 'EST';
        $anio = 2025;

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real para inspeccionar el ORDER BY
        $select = new Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Intercepta el Select enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene el ORDER BY interno
                $order = $select->getRawState('order');

                // Verifica que el orden sea:
                // id DESC
                $this->assertSame(
                    ['id DESC'],
                    $order
                );

                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->generarCorrelativo(
            $prefijo,
            $anio
        );
    }

    //para buscar un solo resultado
    public function testGenerarCorrelativoLimitsQueryToOneResult(): void
    {
        // Datos de prueba
        $prefijo = 'EST';
        $anio = 2025;

        // Mock del objeto Sql
        $sql = $this->createMock(\Laminas\Db\Sql\Sql::class);

        // Select real para inspeccionar el LIMIT
        $select = new \Laminas\Db\Sql\Select('reporte_pdf');

        // ResultSet vacío simulado
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Intercepta el Select enviado a selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->with($this->callback(function ($select) {

                // Obtiene el LIMIT interno
                $limit = $select->getRawState('limit');

                // Verifica que el límite sea 1
                $this->assertSame(1, $limit);

                return true;
            }))
            ->willReturn($resultSet);

        // Ejecuta el método
        $this->table->generarCorrelativo(
            $prefijo,
            $anio
        );
    }

    //correlativo con numeros muy grandes

    public function testGenerarCorrelativoSupportsLargeNumbers(): void
    {
        // Datos de prueba
        $prefijo = 'EST';
        $anio = 2025;

        // Último correlativo existente
        $lastRow = [
            'correlativo' => 'EST-2025-999999',
        ];

        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real
        $select = new Select('reporte_pdf');

        // ResultSet con el último correlativo
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([
            $lastRow,
        ]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Simula selectWith()
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->willReturn($resultSet);

        // Ejecuta el método
        $result = $this->table->generarCorrelativo(
            $prefijo,
            $anio
        );

        // Verifica el nuevo correlativo generado
        $this->assertSame(
            'EST-2025-1000000',
            $result
        );
    }

    //ahora la busqueda devuelve un array vacio

    public function testBuscarPorCorrelativoReturnsEmptyArrayWhenNoResultsExist(): void
    {
        // Mock del objeto Sql
        $sql = $this->createMock(Sql::class);

        // Select real
        $select = new Select('reporte_pdf');

        // ResultSet vacío
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT);

        $resultSet->initialize([]);

        // Simula getSql()
        $this->tableGateway
            ->method('getSql')
            ->willReturn($sql);

        // Simula sql->select()
        $sql->method('select')
            ->willReturn($select);

        // Simula selectWith() vacío
        $this->tableGateway
            ->expects($this->once())
            ->method('selectWith')
            ->willReturn($resultSet);

        // Ejecuta el método
        $result = $this->table->buscarPorCorrelativo();

        // Verifica que retorne un array vacío
        $this->assertSame([], $result);
    }
}

/*

vendor/bin/phpunit module/Application/test/Model/ReportePdfTableTest.php

*/
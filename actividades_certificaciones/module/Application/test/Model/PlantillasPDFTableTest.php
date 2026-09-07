<?php

declare(strict_types=1);

namespace ApplicationTest\Model;

use PHPUnit\Framework\TestCase;

use Application\Model\PlantillasPDFTable;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;

class PlantillasPDFTableTest extends TestCase
{
    private PlantillasPDFTable $table;
    private TableGateway $tableGateway;

    protected function setUp(): void
    {
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->table = new PlantillasPDFTable($this->tableGateway);
    }

    //getByCarreraExtension
    public function testGetByCarreraExtensionCallsSelectWithCorrectFilters(): void
    {
        $carrera = 1;
        $extension = 2;

        // Mock del ResultSet
        $resultSetMock = $this->createMock(ResultSet::class);

        // Configuramos el TableGateway mock
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with([
                'carrera_id' => $carrera,
                'extension_id' => $extension
            ])
            ->willReturn($resultSetMock);

        // Ejecutamos el método
        $this->table->getByCarreraExtension($carrera, $extension);
    }

    public function testGetByCarreraExtensionReturnsFirstResult(): void
    {
        $carrera = 1;
        $extension = 2;

        // Resultado simulado que debería devolver current()
        $expectedRow = new \ArrayObject([
            'id' => 1,
            'carrera_id' => 1,
            'extension_id' => 2,
            'nombre' => 'Plantilla AEDA'
        ]);

        // Mock del ResultSet
        $resultSetMock = $this->createMock(ResultSet::class);

        $resultSetMock->expects($this->once())
            ->method('current')
            ->willReturn($expectedRow);

        // Mock del TableGateway
        $this->tableGateway->expects($this->once())
            ->method('select')
            ->with([
                'carrera_id' => $carrera,
                'extension_id' => $extension
            ])
            ->willReturn($resultSetMock);

        // Ejecutar método
        $result = $this->table->getByCarreraExtension($carrera, $extension);

        // Assert final
        $this->assertSame($expectedRow, $result);
    }

    public function testSaveWithoutIdCreatesNewTemplate(): void
    {
        // esperamos que insert sea llamado porque no existe id
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with(
                $this->callback(function ($data) {
                    return isset($data['codigo'])
                        && $data['arte_contenido'] === 'contenido'
                        && $data['arte_final'] === 'final'
                        && $data['titulo'] === 'titulo'
                        && $data['texto_firma'] === 'firma'
                        && $data['institucion'] === 'institucion';
                })
            );

        // datos sin id representan una nueva plantilla
        $plantilla = new \ArrayObject([
            'arte_contenido' => 'contenido',
            'arte_final'     => 'final',
            'titulo'         => 'titulo',
            'texto_firma'    => 'firma',
            'institucion'    => 'institucion'
        ]);

        // ejecuta guardado
        $this->table->save($plantilla);
    }

    public function testSaveBuildsCorrectDataArray(): void
    {
        // crea plantilla con todos los datos necesarios
        $plantilla = new \ArrayObject([
            'id' => 5,
            'arte_contenido' => 'contenido arte',
            'arte_final'     => 'arte finalizado',
            'titulo'         => 'Título de prueba',
            'texto_firma'    => 'Firma ejemplo',
            'institucion'    => 'Universidad X',
            'texto_constancia'  => 'Constancia de prueba',
            'punto_acta'        => 'Primero',
            'inciso_acta'       => 'A',
            'numero_acta'       => '1',
            'fecha_acta'        => '2023-01-01',
        ]);

        // define array esperado que debe enviarse a update
        $expectedData = [
            'nombre'         => null,
            'arte_contenido' => 'contenido arte',
            'arte_final'     => 'arte finalizado',
            'titulo'         => 'Título de prueba',
            'texto_firma'    => 'Firma ejemplo',
            'institucion'    => 'Universidad X',
            'texto_constancia'  => 'Constancia de prueba',
            'punto_acta'        => 'Primero',
            'inciso_acta'       => 'A',
            'numero_acta'       => '1',
            'fecha_acta'        => '2023-01-01',
        ];

        // valida que update sea llamado correctamente
        $this->tableGateway->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) use ($expectedData) {
                    return $data == $expectedData;
                }),
                $this->callback(function ($where) {
                    return $where === ['id' => 5];
                })
            );

        // ejecuta save
        $this->table->save($plantilla);
    }

    public function testSaveThrowsExceptionWhenArteFieldsAreEmpty(): void
    {
        // datos con campos obligatorios vacios
        $plantilla = new \ArrayObject([
            'id' => 3,
            'arte_contenido' => '',
            'arte_final'     => '',
            'titulo'         => '',
            'texto_firma'    => '',
            'institucion'    => ''
        ]);

        // valida que se lance excepcion
        $this->expectException(\Exception::class);

        // valida mensaje de excepcion
        $this->expectExceptionMessage(
            'No existe arte contenido para guardar.'
        );

        // ejecuta save
        $this->table->save($plantilla);
    }

    //id 0 o null

    public function testSaveWithIdZeroCreatesNewTemplate(): void
    {
        // espera que se inserte porque id 0 es considerado vacio
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with(
                $this->callback(function ($data) {
                    return isset($data['codigo'])
                        && $data['arte_contenido'] === 'contenido'
                        && $data['arte_final'] === 'final'
                        && $data['titulo'] === 'titulo'
                        && $data['texto_firma'] === 'firma'
                        && $data['institucion'] === 'institucion';
                })
            );

        // id en cero representa una nueva plantilla
        $plantilla = new \ArrayObject([
            'id' => 0,
            'arte_contenido' => 'contenido',
            'arte_final'     => 'final',
            'titulo'         => 'titulo',
            'texto_firma'    => 'firma',
            'institucion'    => 'institucion'
        ]);

        // ejecuta save
        $this->table->save($plantilla);
    }

    //update

    public function testSaveCallsUpdateWithCorrectWhere(): void
    {
        //Datos de entrada simulados

        $plantilla = new \ArrayObject([
            'id' => 10,
            'arte_contenido' => 'contenido',
            'arte_final'     => 'final',
            'titulo'         => 'titulo',
            'texto_firma'    => 'firma',
            'institucion'    => 'institucion'
        ]);

        //Mock del TableGateway (update)

        $this->tableGateway->expects($this->once())
            ->method('update')
            ->with(
                $this->anything(), // No validamos data aquí (ya lo hiciste en otro test)

                // Validamos específicamente el WHERE

                $this->callback(function ($where) {
                    return $where === ['id' => 10];
                })
            );

        //Ejecutamos el método

        $this->table->save($plantilla);
    }

    public function testSaveCallsUpdateExactlyOnce(): void
    {
        // datos de entrada
        $plantilla = new \ArrayObject([
            'id' => 7,
            'arte_contenido' => 'contenido',
            'arte_final'     => 'final',
            'titulo'         => 'titulo',
            'texto_firma'    => 'firma',
            'institucion'    => 'institucion'
        ]);

        // esperamos que update se llame exactamente una vez
        $this->tableGateway->expects($this->once())
            ->method('update')
            ->with(
                $this->anything(), // no validamos data aquí
                $this->anything()  // no validamos where aquí
            );

        // ejecutamos save
        $this->table->save($plantilla);
    }

    public function testSaveWithIdNullCreatesNewTemplate(): void
    {
        // espera que se inserte porque id null representa nueva plantilla
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with(
                $this->callback(function ($data) {
                    return isset($data['codigo'])
                        && $data['arte_contenido'] === 'contenido'
                        && $data['arte_final'] === 'final'
                        && $data['titulo'] === 'titulo'
                        && $data['texto_firma'] === 'firma'
                        && $data['institucion'] === 'institucion';
                })
            );

        // plantilla sin id
        $plantilla = new \ArrayObject([
            'id' => null,
            'arte_contenido' => 'contenido',
            'arte_final'     => 'final',
            'titulo'         => 'titulo',
            'texto_firma'    => 'firma',
            'institucion'    => 'institucion'
        ]);

        // ejecuta save
        $this->table->save($plantilla);
    }
}

/*

php vendor/bin/phpunit module/Application/test/Model/PlantillasPDFTableTest.php

php vendor/bin/phpunit --filter testSaveBuildsCorrectDataArray
 

*/
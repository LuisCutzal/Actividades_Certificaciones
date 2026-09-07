<?php

declare(strict_types=1);

namespace ApplicationTest\Model;


use PHPUnit\Framework\TestCase;
use Application\Model\UserTable;
use Application\Model\RolTable;
use Application\Model\User;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\ResultSet\ResultSet;
use ArrayObject;

class UserTableTest extends TestCase
{
    private TableGateway $tableGateway;
    private RolTable $rolTable;

    private UserTable $table;

    protected function setUp(): void
    {
        $this->tableGateway =
            $this->createMock(
                TableGateway::class
            );

        $this->rolTable =
            $this->createMock(
                RolTable::class
            );

        $this->table = new UserTable(
            $this->tableGateway,
            $this->rolTable
        );
    }

    public function testgetUserById(): void
    {
        $row = new ArrayObject([
            'id' => 1,
            'nombre' => 'luis',
            'apellido' => 'cutzal',
            'correo' => 'testuser@example.com'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSet);

        $user = $this->table->getUserById(1);
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('luis', $user->nombre);
        $this->assertEquals('cutzal', $user->apellido);
        $this->assertEquals('testuser@example.com', $user->correo);
    }

    public function testgetUserByCorreo(): void
    {
        $row = new ArrayObject([
            'id' => 3,
            'nombre' => 'luis',
            'apellido' => 'cutzal',
            'correo' => 'testuser@example.com'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['correo' => 'testuser@example.com'])
            ->willReturn($resultSet);

        $user = $this->table->getUserByCorreo('testuser@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(3, $user->id);
        $this->assertEquals('testuser@example.com', $user->correo);
    }

    public function testgetUserByCui(): void
    {
        $row = new ArrayObject([
            'id' => 4,
            'cui' => '1234567812343'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['cui' => '1234567812343'])
            ->willReturn($resultSet);

        $user = $this->table->getUserByCui('1234567812343');
        $this->assertNotNull($user);
        $this->assertEquals(4, $user->id);
        $this->assertEquals('1234567812343', $user->cui);
    }

    public function testgetUserByCarnet(): void
    {
        $row = new ArrayObject([
            'id' => 5,
            'carnet' => '202100011'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['carnet' => '202100011'])
            ->willReturn($resultSet);

        $user = $this->table->getUserByCarnet('202100011');
        $this->assertNotNull($user);
        $this->assertEquals(5, $user->id);
        $this->assertEquals('202100011', $user->carnet);
    }

    public function testgetUserByRegistroPersonal(): void
    {
        $row = new ArrayObject([
            'id' => 6,
            'registro_personal' => '12345678'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['registro_personal' => '12345678'])
            ->willReturn($resultSet);

        $user = $this->table->getUserByRegistroPersonal('12345678');
        $this->assertNotNull($user);
        $this->assertEquals(6, $user->id);
        $this->assertEquals('12345678', $user->registro_personal);
    }

    public function testgetUserByCuiInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->getUserByCui('12345678'); // longitud incorrecta
    }

    public function testgetUserByCarnetInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->getUserByCarnet('20210001'); // longitud incorrecta
    }

    public function testgetUserByRegistroPersonalInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->getUserByRegistroPersonal('123456798'); // longitud incorrecta
    }

    public function testupdatePassword(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['password' => 'hashed123'],
                ['id' => 1]
            )->willReturn(1);

        $result = $this->table->updatePassword(1, 'hashed123');
        $this->assertTrue($result);
    }

    public function testSaveUserInsert(): void
    {
        $user = $this->createMock(
            User::class
        );
        $user->id = null;
        $user
            ->method('getArrayCopy')
            ->willReturn([
                'nombre' => 'Luis'
            ]);
        $this->tableGateway
            ->expects($this->once())
            ->method('insert');

        $this->tableGateway
            ->method('getLastInsertValue')
            ->willReturn(10);

        $id = $this->table->saveUser($user);

        $this->assertEquals(10, $id);
    }

    public function testSaveUserUpdate(): void
    {
        $user = $this->createMock(
            User::class
        );
        $user->id = 5;
        $user
            ->method('getArrayCopy')
            ->willReturn([
                'nombre' => 'Luis'
            ]);
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['nombre' => 'Luis'],
                ['id' => 5]
            )->willReturn(1);

        $result = $this->table->saveUser($user);
        $this->assertEquals(5, $result);
    }

    public function testDeleteUser(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['estado' => 0],
                ['id' => 3]
            )->willReturn(1);

        $this->table->deleteUser(3);

        $this->assertTrue(true);
    }

    public function testEnableUser(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                ['estado' => 1],
                ['id' => 4]
            )->willReturn(1);

        $this->table->enableUser(4);

        $this->assertTrue(true);
    }

    public function testgetUserByLogin(): void
    {
        $row = new ArrayObject([
            'id' => 7,
            'correo' => 'luis@example.com'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet
            ->method('current')
            ->willReturn($row);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $user = $this->table->getUserByLogin('luis@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(7, $user->id);
        $this->assertEquals('luis@example.com', $user->correo);
    }

    public function testsearchUser(): void
    {
        $row1 = [
            'id' => 8,
            'nombre' => 'luis',
            'apellido' => 'cutzal',
            'correo' => 'luis@example.com',
            'cui' => '123456781235',
            'estado' => 1,
            'rol_id' => 1
        ];

        $row2 = [
            'id' => 9,
            'nombre' => 'maria',
            'apellido' => 'gomez',
            'correo' => 'maria@example.com',
            'cui' => '123456781234',
            'estado' => 1,
            'rol_id' => 2
        ];

        // Crear ResultSet correctamente
        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS));
        $resultSet->initialize([$row1, $row2]);

        // Mock del rol
        $rol1 = (object)['nombre' => 'Administrador'];
        $rol2 = (object)['nombre' => 'Personal'];

        $this->rolTable
            ->expects($this->exactly(2))
            ->method('getRolById')
            ->willReturnOnConsecutiveCalls($rol1, $rol2);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $results = $this->table->searchUser('123456781234');

        $this->assertCount(2, $results);
        $this->assertEquals(8, $results[0]['id']);
        $this->assertEquals(9, $results[1]['id']);
    }

    public function testgetAllUsers(): void
    {
        $row1 = [
            'id' => 8,
            'nombre' => 'luis',
            'apellido' => 'cutzal',
            'correo' => 'luis@example.com',
            'rol_id' => 1,
            'rol_nombre' => 'Administrador',
            'estado' => 1
        ];

        $row2 = [
            'id' => 9,
            'nombre' => 'maria',
            'apellido' => 'gomez',
            'correo' => 'maria@example.com',
            'rol_id' => 2,
            'rol_nombre' => 'Personal',
            'estado' => 1
        ];

        $resultSet = new ResultSet();
        $resultSet->setArrayObjectPrototype(
            new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS)
        );
        $resultSet->initialize([$row1, $row2]);

        $this->rolTable
            ->expects($this->never())
            ->method('getRolById');

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $results = $this->table->getAllUsers();

        $this->assertCount(2, $results);
        $this->assertEquals(8, $results[0]['id']);
        $this->assertEquals('Administrador', $results[0]['rol_nombre']);
    }
}
/*

vendor/bin/phpunit module/Application/test/Model/UserTableTest.php

*/
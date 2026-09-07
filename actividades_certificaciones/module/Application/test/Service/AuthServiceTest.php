<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use PHPUnit\Framework\TestCase;

use Application\Service\AuthService;
use Application\Model\UserTable;
use Application\Model\PermisosTable;
use Application\Model\RolCarreraTable;
use Application\Model\RolTable;
use Application\Model\TipoTable;
use Application\Model\RolesPermisosTable;

use Application\Model\User;

use Laminas\Session\Container;
use Laminas\Session\SessionManager;

class AuthServiceTest extends TestCase
{
    private UserTable $userTable;
    private PermisosTable $permisosTable;
    private RolCarreraTable $rolCarreraTable;
    private RolTable $rolTable;
    private TipoTable $tipoTable;
    private RolesPermisosTable $rolesPermisosTable;

    private AuthService $authService;

    protected function setUp(): void
    {
        $this->userTable = $this->createMock(UserTable::class);
        $this->permisosTable = $this->createMock(PermisosTable::class);
        $this->rolCarreraTable = $this->createMock(RolCarreraTable::class);
        $this->rolTable = $this->createMock(RolTable::class);
        $this->tipoTable = $this->createMock(TipoTable::class);
        $this->rolesPermisosTable = $this->createMock(RolesPermisosTable::class);

        $this->authService = new AuthService(
            $this->userTable,
            $this->permisosTable,
            $this->rolCarreraTable,
            $this->rolTable,
            $this->tipoTable,
            $this->rolesPermisosTable
        );
    }

    public function testLoginFailsIfUserDoesNotExist(): void
    {
        $this->userTable
            ->method('getUserByLogin')
            ->willReturn(null);

        $result = $this->authService->login(
            'no-user',
            '123'
        );

        $this->assertFalse($result);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $user = new User();

        $user->password = password_hash(
            'correct-password',
            PASSWORD_DEFAULT
        );

        $user->estado = 1;
        $user->extension = 1;

        $this->userTable
            ->method('getUserByLogin')
            ->willReturn($user);

        $result = $this->authService->login(
            'user',
            'wrong-password'
        );

        $this->assertFalse($result);
    }

    public function testLoginFailsIfUserInactive(): void
    {
        $user = new User();

        $user->password = password_hash(
            '123',
            PASSWORD_DEFAULT
        );

        $user->estado = 0;
        $user->extension = 1;

        $this->userTable
            ->method('getUserByLogin')
            ->willReturn($user);

        $result = $this->authService->login(
            'user',
            '123'
        );

        $this->assertFalse($result);
    }

    public function testLoginThrowsExceptionIfNoExtension(): void
    {
        $user = new User();

        $user->password = password_hash(
            '123',
            PASSWORD_DEFAULT
        );

        $user->estado = 1;
        $user->extension = null;

        $this->userTable
            ->method('getUserByLogin')
            ->willReturn($user);

        $this->expectException(\Exception::class);

        $this->authService->login(
            'user',
            '123'
        );
    }

    public function testLoginSuccessReturnsTrue(): void
    {
        $user = new User();

        $user->id = 1;
        $user->nombre = 'Luis';
        $user->apellido = 'Perez';
        $user->correo = 'test@mail.com';

        $user->password = password_hash(
            '123456',
            PASSWORD_DEFAULT
        );

        $user->estado = 1; //estado activo
        $user->rol_id = 1; //rol de un administrador
        $user->extension = 1; //extensión asignada

        $this->userTable
            ->method('getUserByLogin')
            ->willReturn($user);

        $this->permisosTable
            ->method('getPermisosByRol')
            ->willReturn([]);

        $this->rolesPermisosTable
            ->method('getPermisosByRolId')
            ->willReturn([]);

        $this->rolCarreraTable
            ->method('getCarrerasByRol')
            ->willReturn([]);

        $this->rolTable
            ->method('getRolConTipo')
            ->willReturn([
                'tipo_id' => 1 //tipo administrador 
            ]);

        $result = $this->authService->login(
            'user',
            '123456'
        );

        $this->assertTrue($result);
    }

    public function testLogoutDestroysSession(): void
    {
        $manager = $this->createMock(
            SessionManager::class
        );

        $manager
            ->expects($this->once())
            ->method('destroy');

        $session = $this->createMock(
            Container::class
        );

        $session
            ->method('getManager')
            ->willReturn($manager);

        // Inyectar sesión mockeada
        $reflection = new \ReflectionClass(
            $this->authService
        );

        $property = $reflection->getProperty('session');

        $property->setAccessible(true);

        $property->setValue(
            $this->authService,
            $session
        );

        // Ejecutar logout
        $this->authService->logout();
    }

    public function testIsLoggedReturnsFalseWhenNoSession(): void
    {
        $session = new Container('user');

        unset($session->userId);

        $result = $this->authService->isLogged();

        $this->assertFalse($result);
    }

    public function testGetCurrentUserReturnsNullIfNotLogged(): void
    {
        $session = new Container('user');

        unset($session->userId);

        $result = $this->authService->getCurrentUser();

        $this->assertNull($result);
    }

    public function testGetCurrentUserReturnsUser(): void
    {
        $session = new Container('user');

        $session->userId = 1;
        $session->nombre = 'Luis';
        $session->apellido = 'Perez';
        $session->rol_id = 2;
        $session->tipo_id = 1;
        $session->extension = '101';
        $session->codigo_Carrera = 'SIS';

        $user = $this->authService->getCurrentUser();

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->assertEquals(
            1,
            $user->id
        );

        $this->assertEquals(
            'Luis',
            $user->nombre
        );
    }

    public function testHasRoleReturnsTrueIfRoleMatches(): void
    {
        $session = new Container('user');

        $session->userId = 1;
        $session->rol_id = 'admin';

        $result = $this->authService->hasRole(
            'admin'
        );

        $this->assertTrue($result);
    }

    public function testHasRoleReturnsFalseIfRoleDoesNotMatch(): void
    {
        $session = new Container('user');

        $session->userId = 1;
        $session->rol_id = 'user';

        $result = $this->authService->hasRole(
            'admin'
        );

        $this->assertFalse($result);
    }

    public function testGetUserByEmailReturnsUser(): void
    {
        $user = new User();

        $this->userTable
            ->expects($this->once())
            ->method('getUserByCorreo')
            ->with('test@mail.com')
            ->willReturn($user);

        $result = $this->authService
            ->getUserByEmail('test@mail.com');

        $this->assertSame(
            $user,
            $result
        );
    }

    public function testUpdatePasswordCallsUserTable(): void
    {
        $this->userTable
            ->expects($this->once())
            ->method('updatePassword')
            ->with(
                5,
                'hashed-password'
            )
            ->willReturn(true);

        $result = $this->authService
            ->updatePassword(
                5,
                'hashed-password'
            );

        $this->assertTrue($result);
    }

    public function testGetUserIfExistsReturnsUser(): void
    {
        $user = new User();

        $this->userTable
            ->expects($this->once())
            ->method('getUserByLogin')
            ->with('admin')
            ->willReturn($user);

        $result = $this->authService
            ->getUserIfExists('admin');

        $this->assertSame(
            $user,
            $result
        );
    }
}


/*

vendor/bin/phpunit module/Application/test/Service/AuthServiceTest.php

*/
<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\AuthService;
use Application\Model\PasswordResetTable;
use Application\Service\MailService;
use Laminas\Session\Container;
use Application\Model\User;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class AuthControllerTest extends AbstractHttpControllerTestCase
{
    private AuthService $authService;
    private PasswordResetTable $passwordResetTable;
    private MailService $mailService;
    private Container $session;

    public function setUp(): void
    {
        $this->authService = $this->createMock(AuthService::class);
        $this->passwordResetTable = $this->createMock(PasswordResetTable::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->session = new Container('user');

        $config = include __DIR__ . '/../../../../config/application.config.php';

        $this->setApplicationConfig($config);

        parent::setUp();

        // Inyectar mocks en el ServiceManager
        $serviceManager = $this->getApplicationServiceLocator();

        $serviceManager->setAllowOverride(true);

        $serviceManager->setService(
            AuthService::class,
            $this->authService
        );

        $serviceManager->setService(
            PasswordResetTable::class,
            $this->passwordResetTable
        );

        $serviceManager->setService(
            MailService::class,
            $this->mailService
        );
    }

    public function testLoginWithEmptyFieldsRedirects(): void
    {
        // Simular POST vacío
        $this->dispatch('/login', 'POST', [
            'login' => '',
            'password' => ''
        ]);

        // Verificar redirección
        $this->assertResponseStatusCode(302);

        // Verificar que redirige a /
        $this->assertRedirectTo('/');
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        // Mock login fallido
        $this->authService
            ->method('login')
            ->willReturn(false);

        $this->authService
            ->method('getUserIfExists')
            ->willReturn(null);

        $this->dispatch('/login', 'POST', [
            'login' => 'usuario',
            'password' => 'incorrecto'
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/');
    }

    public function testLoginSuccessRedirectsToAdministrador(): void
    {
        // Usuario simulado
        $user = new User();
        $user->estado = 1;

        // Mock login exitoso
        $this->authService
            ->method('login')
            ->willReturn(true);

        $this->authService
            ->method('getCurrentUser')
            ->willReturn($user);

        // Simular tipo admin
        $this->session->tipo_id = 1;

        $this->dispatch('/login', 'POST', [
            'login' => 'admin',
            'password' => '1234'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectToRoute('administrador');
    }

    public function testLoginSuccessRedirectsToActividades(): void
    {
        // Usuario simulado
        $user = new User();
        $user->estado = 1;

        // Mock login exitoso
        $this->authService
            ->method('login')
            ->willReturn(true);

        $this->authService
            ->method('getCurrentUser')
            ->willReturn($user);

        // Simular tipo personal adminsitrativo
        $this->session->tipo_id = 2;

        $this->dispatch('/login', 'POST', [
            'login' => 'example@gmail.com',
            'password' => '123'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectToRoute('actividades');
    }

    public function testLogoutRedirectsToHome(): void
    {
        // Simular acceso a logout
        $this->dispatch('/logout', 'GET');

        // Debe redirigir
        $this->assertResponseStatusCode(302);

        // Debe redirigir a la ruta home
        $this->assertRedirectToRoute('home');
    }

    public function testForgotPasswordGetShowsForm(): void
    {
        $this->dispatch('/forgot-password', 'GET');

        $this->assertResponseStatusCode(200); //el tipo de estado 200 indica que la solicitud se ha procesado correctamente

        $this->assertMatchedRouteName('forgot-password');
    }

    public function testForgotPasswordWithEmptyEmailRedirects(): void
    {
        $this->dispatch('/forgot-password', 'POST', [
            'email' => ''
        ]);

        $this->assertResponseStatusCode(302); //el tipo de estado 302 indica que se ha producido una redirección

        $this->assertRedirectTo('/forgot-password');
    }

    public function testForgotPasswordWithUnknownEmail(): void
    {
        $this->authService
            ->method('getUserByEmail')
            ->willReturn(null);

        $this->dispatch('/forgot-password', 'POST', [
            'email' => 'noexiste@test.com'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }

    public function testForgotPasswordWithValidEmailCreatesTokenAndSendsMail(): void
    {
        $user = new User();
        $user->id = 1;
        $user->nombre = 'Luis';
        $user->apellido = 'Perez';

        // Usuario encontrado
        $this->authService
            ->method('getUserByEmail')
            ->willReturn($user);

        // Verificar que se crea token
        $this->passwordResetTable
            ->expects($this->once())
            ->method('createResetToken');

        // Verificar que se envía correo
        $this->mailService
            ->expects($this->once())
            ->method('send');

        $this->dispatch('/forgot-password', 'POST', [
            'email' => 'test@mail.com'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }

    public function testResetPasswordGetWithoutTokenShowsError(): void
    {
        $this->dispatch('/reset-password', 'GET');

        $this->assertResponseStatusCode(200);

        $content = $this->getResponse()->getContent();

        $this->assertStringContainsString(
            'Token faltante.',
            $content
        );
    }

    public function testResetPasswordGetWithInvalidTokenRedirects(): void
    {
        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn(null);

        $this->dispatch(
            '/reset-password?token=invalid',
            'GET'
        );

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }

    public function testResetPasswordGetWithValidTokenShowsForm(): void
    {
        $record = [
            'used' => 0,
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+10 minutes')
            )
        ];

        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn($record);

        $this->dispatch(
            '/reset-password?token=valid',
            'GET'
        );

        $this->assertResponseStatusCode(200);

        $this->assertMatchedRouteName('reset-password');
    }

    public function testResetPasswordGetWithExpiredTokenRedirects(): void
    {
        // Token expirado (tiempo en el pasado)
        $record = [
            'used' => 0,
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('-10 minutes') // ← expirado
            )
        ];

        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn($record);

        $this->dispatch(
            '/reset-password?token=expired',
            'GET'
        );

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }

    public function testResetPasswordGetWithUsedTokenRedirects(): void
    {
        // Token ya usado
        $record = [
            'used' => 1,
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+10 minutes') // aún válido
            )
        ];

        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn($record);

        $this->dispatch(
            '/reset-password?token=usedtoken',
            'GET'
        );

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }

    public function testResetPasswordPostWithEmptyPasswords(): void
    {
        $this->dispatch('/reset-password', 'POST', [
            'token' => 'abc',
            'password' => '',
            'password_confirm' => ''
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo(
            '/reset-password?token=abc'
        );
    }

    public function testResetPasswordPostWithDifferentPasswords(): void
    {
        $this->dispatch('/reset-password', 'POST', [
            'token' => 'abc',
            'password' => '123',
            'password_confirm' => '456'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo(
            '/reset-password?token=abc'
        );
    }

    public function testResetPasswordPostWithInvalidToken(): void
    {
        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn(null);

        $this->dispatch('/reset-password', 'POST', [
            'token' => 'invalid',
            'password' => '123',
            'password_confirm' => '123'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }

    public function testResetPasswordPostValidUpdatesPassword(): void
    {
        $record = [
            'id' => 1,
            'user_id' => 5,
            'used' => 0,
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+10 minutes')
            )
        ];

        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn($record);

        // Debe invalidar tokens viejos
        $this->passwordResetTable
            ->expects($this->once())
            ->method('invalidateOldTokens');

        // Debe marcar como usado
        $this->passwordResetTable
            ->expects($this->once())
            ->method('markAsUsed');

        // Debe actualizar password
        $this->authService
            ->expects($this->once())
            ->method('updatePassword');

        $this->dispatch('/reset-password', 'POST', [
            'token' => 'valid',
            'password' => '123456',
            'password_confirm' => '123456'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/');
    }

    public function testResetPasswordPostWithExpiredTokenRedirects(): void
    {
        $record = [
            'id' => 1,
            'user_id' => 5,
            'used' => 0,
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('-10 minutes')
            )
        ];

        $this->passwordResetTable
            ->method('getByToken')
            ->willReturn($record);

        $this->dispatch('/reset-password', 'POST', [
            'token' => 'expired',
            'password' => '123456',
            'password_confirm' => '123456'
        ]);

        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo('/forgot-password');
    }
}


/*

vendor/bin/phpunit module/Application/test/Controller/AuthControllerTest.php

*/
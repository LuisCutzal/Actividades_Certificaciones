<?php
//Todo lo que tiene que ver con acceso de usuarios está centralizado aquí
declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Application\Service\AuthService;
use Laminas\Session\Container;
use Application\Model\PasswordResetTable;
use Laminas\View\Model\ViewModel;
use Application\Service\MailService;
use Application\Service\AuditService;

/*
  Controlador de autenticación:
  login, logout, forgot-password y reset-password
 */

class AuthController extends AbstractActionController
{
    private AuthService $authService; // Servicio principal de autenticación
    private PasswordResetTable $passwordResetTable; // Modelo para guardar y validar tokens
    private MailService $mailService;
    private Container $session;
    private AuditService $auditService;
    /*
      Constructor con inyección de dependencias.
      Recibe el servicio de autenticación y el modelo de reset.
     */
    public function __construct(AuthService $authService, PasswordResetTable $passwordResetTable, MailService $mailService, Container $session, AuditService $auditService)
    {
        $this->authService = $authService;
        $this->passwordResetTable = $passwordResetTable;
        $this->mailService = $mailService;
        $this->session = $session;
        $this->auditService = $auditService;
    }
    /*
      Maneja el login del usuario
     */
    public function loginAction()
    {
        // Obtenemos la petición actual
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

        // Si es GET → redirigir al home
        if ($request->isGet()) {
            return $this->redirect()->toUrl('/');
        }

        // Si NO es POST → error
        if (!$request->isPost()) {
            $this->flashMessenger()->addErrorMessage('Método no permitido.');
            return $this->redirect()->toUrl('/');
        }

        $post = $request->getPost()->toArray(); // Obtenemos los datos enviados por el formulario

        $login = trim((string) ($post['login'] ?? ''));
        $password = trim((string) ($post['password'] ?? ''));

        if ($login === '' || $password === '') {
            $this->flashMessenger()->addErrorMessage('Debe ingresar usuario y contraseña.');
            return $this->redirect()->toUrl('/');
        }

        // Realiza la verificación de credenciales
        //retorna true o false
        $success = $this->authService->login($login, $password);

        if (!$success) { //si sono invalidas las credenciales o no existe el usuario
            //el log para login fallido

            $this->auditService->log(
                null,
                $login,
                'LOGIN_FAILED',
                'USER',
                null,
                'Intento de inicio de sesión fallido'
            );

            $user = $this->authService->getUserIfExists($login);
            if ($user && (int)$user->estado === 0) {
                $this->flashMessenger()->addErrorMessage('Usuario inactivo. Contacte al administrador.');
            } else {
                $this->flashMessenger()->addErrorMessage('Credenciales inválidas.');
            }
            return $this->redirect()->toUrl('/');
        }

        // Obtener usuario actual desde sesión
        $user = $this->authService->getCurrentUser();

        if (!$user) { //si existe algun error al cargar al usuario
            $this->flashMessenger()->addErrorMessage('Error al cargar usuario.');
            return $this->redirect()->toUrl('/');
        }

        //log para login exitoso

        $this->auditService->log(
            (int)$user->id,
            (string)$user->cui,
            'LOGIN_SUCCESS',
            'USER',
            (int)$user->id,
            'Inicio de sesión exitoso'
        );

        // redirigir según el tipo de usuario (1=admin, 2=personal, 3=secretaria, 4=asociaciones)
        switch ((int) $this->session->tipo_id) {
            case 1:
                return $this->redirect()->toRoute('administrador');
            case 2:
            case 3:
            case 4:
                return $this->redirect()->toRoute('actividades');
            default:
                return $this->redirect()->toRoute('home');
        }
    }

    // cerrar sesión del usuario
    public function logoutAction()
    {
        //ahora comienza el log

        $user = $this->authService->getCurrentUser();

        if ($user) {
            $this->auditService->log(
                (int)$user->id,
                (string)$user->cui,
                'LOGOUT',
                'USER',
                (int)$user->id,
                'Cierre de sesión'
            );
        }

        // destruir contenedor 'user'
        $sessionUser = new Container('user');
        $sessionUser->getManager()->destroy();

        // destruir contenedor 'usuario'
        $sessionUsuario = new Container('usuario');
        $sessionUsuario->getManager()->destroy();

        return $this->redirect()->toRoute('home'); //regresar a home
    }
    // solicitud para enviar un correo con link de restablecimiento
    public function forgotPasswordAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

        // Si es GET → mostrar formulario
        if (!$request->isPost()) {
            return new ViewModel();
        }
        // Obtener email del formulario
        $email = (string) $this->params()->fromPost('email', '');

        //si email esta en blando dara error
        if ($email === '') {
            $this->flashMessenger()->addErrorMessage('Ingresa tu correo.');
            return $this->redirect()->toUrl('/forgot-password');
        }
        // Buscar usuario por email
        $user = $this->authService->getUserByEmail($email);
        if (!$user) {
            $this->flashMessenger()->addErrorMessage(
                'El correo no está registrado.'
            );

            return $this->redirect()->toUrl('/forgot-password');
        }

        //log
        $this->auditService->log(
            (int)$user->id,
            (string)$user->cui,
            'PASSWORD_RESET_REQUEST',
            'USER',
            (int)$user->id,
            'Solicitud de recuperación de contraseña'
        );

        //invalidar tokens anteriores

        $userId = (int)$user->id;
        $this->passwordResetTable->invalidateOldTokens($userId);

        // Generar nuevo token 
        $token = bin2hex(random_bytes(32));
        //definimos cuando va a expirar
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Guardar token en la base de datos
        $this->passwordResetTable->createResetToken((int)$user->id, $token, $expiresAt);

        //$resetLink = sprintf('http://localhost:8080/reset-password?token=%s', $token);
        //url desde .env

        $appUrl = $this->getEvent()
            ->getApplication()
            ->getServiceManager()
            ->get('config')['app']['url'];
        // Construir link final
        $resetLink = sprintf('%s/reset-password?token=%s', $appUrl, $token);

        // Configurar correo
        $subject = 'Recuperación de contraseña';
        $message = "Hola,\n\nHaz clic en este enlace para restablecer tu contraseña:\n\n{$resetLink}\n\nEl enlace expira en 15 minutos.";

        $this->mailService->send(
            $email,
            $subject,
            $message,
            $user->nombre ?? null,
            $user->apellido ?? null,
        );

        $this->flashMessenger()->addSuccessMessage(
            'Se enviaron las instrucciones para restablecer tu contraseña.'
        );
        return $this->redirect()->toUrl('/forgot-password');
    }


    //página y procesamiento del restablecimiento de contraseña
    public function resetPasswordAction()
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $this->getRequest();
        // Si es GET → mostrar el formulario de nueva contraseña
        if (!$request->isPost()) {
            // Mostrar formulario con token desde la url
            $token = (string) $this->params()->fromQuery('token', '');
            if ($token === '') {
                return new ViewModel(['error' => 'Token faltante.']);
            }
            // Buscar token en BD
            $record = $this->passwordResetTable->getByToken($token);
            if (!$record || strtotime($record['expires_at']) < time() || (int)$record['used'] === 1) {
                $this->flashMessenger()->addErrorMessage('Token inválido, expirado o ya usado.');
                return $this->redirect()->toUrl('/forgot-password');
            }

            return new ViewModel(['token' => $token]);
        }

        // POST: procesar nueva contraseña
        $token = (string) $this->params()->fromPost('token', '');
        $password = (string) $this->params()->fromPost('password', '');
        $passwordConfirm = (string) $this->params()->fromPost('password_confirm', '');
        // validar que los campos no esten vacios
        if ($password === '' || $passwordConfirm === '') {
            $this->flashMessenger()->addErrorMessage('Ambos campos de contraseña son obligatorios.');
            return $this->redirect()->toUrl('/reset-password?token=' . urlencode($token));
        }
        // validar contraseñas iguales
        if ($password !== $passwordConfirm) {
            $this->flashMessenger()->addErrorMessage('Las contraseñas no coinciden.');
            return $this->redirect()->toUrl('/reset-password?token=' . urlencode($token));
        }
        // Validar token
        $record = $this->passwordResetTable->getByToken($token);

        if (!$record) {
            $this->flashMessenger()->addErrorMessage('Token inválido.');
            return $this->redirect()->toUrl('/forgot-password');
        }
        // Verificar si el token ya se usó
        if ((int)$record['used'] === 1) {
            $this->flashMessenger()->addErrorMessage('Este enlace ya fue usado.');
            return $this->redirect()->toUrl('/forgot-password');
        }
        // Verificar expiración
        if (strtotime($record['expires_at']) < time()) {
            $this->flashMessenger()->addErrorMessage('El enlace ha expirado.');
            return $this->redirect()->toUrl('/forgot-password');
        }
        // Invalidar tokens viejos
        $this->passwordResetTable->invalidateOldTokens((int)$record['user_id']);

        // Actualizar contraseña en la base de datos
        $this->authService->updatePassword((int)$record['user_id'], password_hash($password, PASSWORD_DEFAULT));

        //log

        $this->auditService->log(
            (int)$record['user_id'],
            null,
            'PASSWORD_CHANGED',
            'USER',
            (int)$record['user_id'],
            'Contraseña restablecida correctamente'
        );
        // Marcar token como usado
        $this->passwordResetTable->markAsUsed((int)$record['id']);
        $this->flashMessenger()->addSuccessMessage('Tu contraseña ha sido restablecida. Ya puedes iniciar sesión.');
        return $this->redirect()->toUrl('/');
    }
}

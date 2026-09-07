<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Application\Model\ExtensionTable;
use Application\Model\RolTable;


/*
 controlador base que centraliza la validación de autenticación y permisos.
 todos los controladores que requieran login deben extender este controlador.
 */
//ahora tambien todos los controladores que quieran agregar una actividad, deben de extender este controlador
class BaseController extends AbstractActionController
{
    
    protected ?array $user = null;
    protected ExtensionTable $extensionTable;
    protected RolTable $rolTable;

    public function __construct(ExtensionTable $extensionTable, RolTable $rolTable)
    {
        $this->extensionTable = $extensionTable;
        $this->rolTable = $rolTable;
    }

    /*esto es nuevo para verificar permisos y tipos */

    /**
     * Verifica si el usuario tiene acceso a una sección y/o permiso específico.
     *
     * @param array|null $tiposPermitidos Lista de tipo_id permitidos para la sección
     * @param string|null $permisoAccion Permiso requerido para realizar una acción específica
     * @return array|Laminas\Http\Response Devuelve los datos del usuario si tiene acceso, o redirige si no
     * @throws \Exception
     */
    protected function checkAuth(?array $tiposPermitidos = null, ?string $permisoAccion = null)
    {
        // Obtener usuario de sesión o plugin Auth
        if ($this->user === null) {
            $authPlugin = $this->authPlugin();
            if (!$authPlugin->requireLogin()) {
                $this->flashMessenger()->addErrorMessage('Debe iniciar sesión para continuar.');
                return $this->redirect()->toRoute('home');
            }
            $this->user = $authPlugin->getUser();
        }

        $user = $this->user;

        // Verificar acceso por tipo (sección)
        if (!empty($tiposPermitidos) && !in_array($user['tipo_id'], $tiposPermitidos)) {
            $this->flashMessenger()->addErrorMessage('No tiene permisos para acceder a esta sección.');
            return $this->redirect()->toRoute('home');
        }

        // Verificar permiso específico (acción)
        if ($permisoAccion !== null && !in_array($permisoAccion, $user['permisos'] ?? [])) {
            $this->flashMessenger()->addErrorMessage('No tiene permiso para realizar esta acción.');
            return $this->redirect()->toRoute('home');
        }

        // Todo correcto → devolver datos del usuario
        return $user;
    }

    //esta funcion es para crear actividades ya que lo utilizan varios roles de usuarios
    //se dejo en este archivo
    protected function handleActivityCreation(callable $createFunction)
    {
        $view = new \Laminas\View\Model\ViewModel();
        $view->setTemplate('application/actividad/add-activity');

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            try {
                $createFunction($data);
                $view->setVariable('successMessage', 'Actividad creada correctamente');
            } catch (\Exception $e) {
                $view->setVariable('errorMessage', $e->getMessage());
            }
        }
        return $view;
    }

    protected function handleUserEdit(
        callable $getUserById,
        callable $updateUser,
        string $redirectUrlIfNoId,
        string $redirectUrlAfterUpdate
    ) {

        $id = (int) $this->params()->fromRoute('id');

        if (!$id) {
            return $this->redirect()->toUrl($redirectUrlIfNoId);
        }

        // Usuario logueado
        $auth = $this->plugin('authPlugin');
        $usuarioLogueado = $auth->getUser();
        $esAdmin = (
            $usuarioLogueado
            && (int)$usuarioLogueado['tipo_id'] === 1
        );

        // Obtener usuario actual
        $usuario = $getUserById($id);

        if (!$usuario) {
            return $this->redirect()->toUrl($redirectUrlIfNoId);
        }

        // cargar roles
        //$roles = $this->rolTable->fetchAll();
        $roles = $this->rolTable->getRol() ?? [];

        $mapaRoles = [];
        foreach ($roles as $rol) {
            $mapaRoles[$rol->id] = $rol->nombre;
        }

        // cargar extensiones
        $extensiones = $this->extensionTable->fetchAll() ?? [];

        $mapaExtensiones = [];
        foreach ($extensiones as $ext) {
            $mapaExtensiones[$ext->extension] = $ext->nombre;
        }

        $request = $this->getRequest();

        if ($request->isPost()) {

            try {

                $data = $request->getPost()->toArray();
                $data['id'] = $id;

                $updateUser($data);

                return $this->redirect()->toUrl($redirectUrlAfterUpdate);
            } catch (\Exception $e) {

                // Mantener datos escritos
                $usuarioTemporal = (object) array_merge(
                    (array) $usuario,
                    $data
                );

                $view = new \Laminas\View\Model\ViewModel([
                    'usuario' => $usuarioTemporal,
                    'errorMessage' => $e->getMessage(),
                    'esAdmin' => $esAdmin,
                    'roles' => $mapaRoles,
                    'extensiones' => $mapaExtensiones,
                ]);

                $view->setTemplate('application/usuario/editar-user');

                return $view;
            }
        }

        $view = new \Laminas\View\Model\ViewModel([
            'usuario' => $usuario,
            'errorMessage' => null,
            'esAdmin' => $esAdmin,
            'roles' => $mapaRoles,
            'extensiones' => $mapaExtensiones,
        ]);

        $view->setTemplate('application/usuario/editar-user');

        return $view;
    }

    protected function authPlugin(): \Application\Controller\Plugin\AuthPlugin
    {
        return $this->plugin('authPlugin');
    }

    protected function flashMessenger()
    {
        return $this->plugin('flashMessenger');
    }

    protected function redirect()
    {
        return $this->plugin('redirect');
    }
}

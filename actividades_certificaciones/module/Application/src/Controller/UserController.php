<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\UserService;
use Application\Service\ActivityService;
use Laminas\View\Model\ViewModel;
use Application\Model\ExtensionTable;
use Application\Model\RolTable;

use Laminas\View\Model\JsonModel;

class UserController extends BaseController
{
    private UserService $userService;
    private ActivityService $activityService;
    public function __construct(UserService $userService, ActivityService $activityService, ExtensionTable $extensionTable, RolTable $rolTable)
    {
        parent::__construct($extensionTable, $rolTable);
        $this->userService = $userService;
        $this->activityService = $activityService;
    }

    public function editarUserAction()
    {
        $auth = $this->plugin('authPlugin');
        $user = $auth->getUser();
        $redirect = '/actividades';

        if ($user && (int)$user['tipo_id'] === 1) {
            $redirect = '/admin/listarUsuario';
        }

        return $this->handleUserEdit(
            fn($id) => $this->userService->obtenerUsuarioPorId($id),
            fn($data) => $this->userService->editUser($data),
            $redirect,
            $redirect
        );
    }

    public function dashboardAction()
    {
        $user = $this->checkAuth([2, 3, 4]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $view = new ViewModel([
            'tipo_id' => $user['tipo_id'],
        ]);

        $view->setTemplate('application/usuario/dashboard');

        return $view;
    }

    public function dashboardDataAction()
    {
        $user = $this->checkAuth([2, 3, 4]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $filters = [

            'year' =>
            $this->params()->fromQuery('year'),

            'monthStart' =>
            $this->params()->fromQuery('monthStart'),

            'monthEnd' =>
            $this->params()->fromQuery('monthEnd'),

            'dateStart' =>
            $this->params()->fromQuery('dateStart'),

            'dateEnd' =>
            $this->params()->fromQuery('dateEnd'),
        ];

        $conteoEstados =
            $this->activityService
            ->getConteoActividadesPorEstado($user);

        $totalActividades =
            $conteoEstados['pendientes']
            + $conteoEstados['aprobadas']
            + $conteoEstados['rechazadas'];

        return new JsonModel([

            'kpis' => [
                'total'      => $totalActividades,
                'aprobadas'  => $conteoEstados['aprobadas'],
                'pendientes' => $conteoEstados['pendientes'],
                'rechazadas' => $conteoEstados['rechazadas'],
            ],

            'actividadesPorMes' =>
            $this->activityService
                ->getActividadesPorMes($user, $filters),

            'aprobadasVsPendientes' =>
            $this->activityService
                ->getAprobadasVsPendientes($user, $filters),

            'topActividades' =>
            $this->activityService
                ->getTopActividades($user, $filters),

            'topUsuarios' =>
            $this->activityService
                ->getTopUsuarios($user, $filters),

            'actividadesPorCarrera' =>
            $this->activityService
                ->getActividadesPorCarrera($user, $filters),
        ]);
    }
}

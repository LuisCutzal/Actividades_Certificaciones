<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
//Controlador que maneja la página de inicio .
class IndexController extends AbstractActionController
{
    // muestra la página inicial del sistema
    public function indexAction() 
    //si yo cierro la ventana y luego vuelvo a abrirla, si ya tengo una sesión iniciada, me redirige a la página correspondiente según mi tipo_id
    {
        $authPlugin = $this->authPlugin();

        //  NO hay sesión → mostrar login
        if (!$authPlugin->isLoggedIn()) {
            return new ViewModel();
        }

        //  Hay sesión → redirigir SOLO una vez
        $user = $authPlugin->getUser();

        switch ((int)$user['tipo_id']) {
            case 1:
                return $this->redirect()->toRoute('administrador');
            case 2:
            case 3:
            case 4:
                return $this->redirect()->toRoute('actividades');
            default:
                return new ViewModel();
        }
    }

}

<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Session\Container;

class UserIdentity extends AbstractHelper
{
    private Container $session;

    public function __construct()
    {
        $this->session = new Container('user'); // usar la misma sesión del login
    }

    public function __invoke()
    {
        return $this->session; // devuelve datos: id, username, rol, etc.
    }
}

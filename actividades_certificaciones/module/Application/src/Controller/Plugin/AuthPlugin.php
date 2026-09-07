<?php
declare(strict_types=1); // Obliga a usar tipos estrictos en PHP

namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Container;

/*
plugin de autenticación usado en controladores.
permite verificar login, roles y obtener datos del usuario.
 */
class AuthPlugin extends AbstractPlugin
{
    /*
    verifica si hay sesión activa
    true si el usuario tiene sesión, false si no.
    */
    public function requireLogin(): bool
    {
        /* comprueba si en la sesión existe el campo 'userId'
        si está definido, el usuario sí ha iniciado sesión
        */
        $session = new Container('user');
        return isset($session->userId);
    }

    /*
    Verifica si el usuario tiene uno de los roles permitidos
    array $allowedRoles -> Lista de roles que pueden acceder
    return bool True si tiene acceso, false si no
    */
    public function requireRole(array $allowedRoles): bool
    {
        // Abre el contenedor de sesión
        $session = new Container('user');

        if (!isset($session->rol_id)) { // Si no existe un rol en la sesión, el usuario NO está autenticado
            return false; // no tiene sesión
        }
        // retorna true si el rol almacenado en sesión está dentro del arreglo $allowedRoles
        return in_array($session->rol_id, $allowedRoles, true);
    }

    /*
      devuelve los datos de usuario almacenados en sesión
      array|null Arreglo con datos del usuario o null si no hay sesión
     */
    public function getUser(): ?array
    {
        // Abre el contenedor 'user' de la sesión
        $session = new Container('user');
        if (!isset($session->userId)) { // Si no hay userId, significa que no hay sesión válida
            return null;
        }
        // Devuelve los valores almacenados en la sesión como un arreglo
        return [
            'id' => $session->userId,
            'nombre' => $session->nombre,
            'apellido' => $session->apellido,
            'cui' => $session->cui,//funcionara para el log
            'rol_id' => $session->rol_id,
            'extension' => $session->extension,
            'tipo_id' => $session->tipo_id ?? null,
            'codigo_Carrera' => $session->codigo_Carrera ?? null,
            'permisos' => $session->permisos ?? [],
            'carreras' => $session->carreras ?? [],
        ];
    }

    public function isLoggedIn(): bool
    {
        $session = new Container('user');
        return isset($session->userId);
    }
}

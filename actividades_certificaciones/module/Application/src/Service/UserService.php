<?php

namespace Application\Service;

use Application\Model\UserTable;
use Application\Model\User;
use Laminas\Session\Container;
use Application\Model\RolTable;
use Application\Model\RolCarreraTable;
use Application\Model\StudentTable;
use Application\Model\InscripcionTable;
use Application\Service\AuditService;
use Application\Model\ExtensionTable;


class UserService
{
    private UserTable $userTable;
    private Container $session;
    private RolTable $rolTable;
    private RolCarreraTable $rolCarreraTable;
    private StudentTable $studentTable;
    private InscripcionTable $inscripcionTable;
    private AuditService $auditService;
    private ExtensionTable $extensionTable;

    public function __construct(UserTable $userTable, RolTable $rolTable, StudentTable $studentTable, InscripcionTable $inscripcionTable, AuditService $auditService, ExtensionTable $extensionTable, RolCarreraTable $rolCarreraTable)
    {
        $this->userTable = $userTable;
        $this->rolTable = $rolTable;
        $this->rolCarreraTable = $rolCarreraTable;
        $this->studentTable = $studentTable;
        $this->inscripcionTable = $inscripcionTable;
        $this->session   = new Container('user');
        $this->auditService = $auditService;
        $this->extensionTable = $extensionTable;
    }

    public function createUser(array $data): int
    {
        // Validación común
        $validated = $this->validateUserData($data);

        // Hash de contraseña
        $hashedPassword = password_hash(
            $data['password'],
            PASSWORD_DEFAULT,
            [
                'cost' => 12
            ]
        );

        $rolId = (int)$data['rol_id'];
        $tipoId = $this->rolTable->getTipoIdByRolId($rolId); //para obtener el tipo del rol

        if (!$tipoId) {
            throw new \Exception('Rol inválido.');
        }

        $carnet = trim($validated['carnet'] ?? '');
        $registroPersonal = trim($validated['registroPersonal'] ?? '');

        // debe existir al menos uno
        if (empty($carnet) && empty($registroPersonal)) {
            throw new \Exception(
                'Debe ingresar al menos un Carnet o un Registro Personal.'
            );
        }

        // no pueden existir ambos al mismo tiempo
        if (!empty($carnet) && !empty($registroPersonal)) {
            throw new \Exception(
                'No puede ingresar Carnet y Registro Personal al mismo tiempo.'
            );
        }

        if ($tipoId == 4) { //es una asociacion
            $carnet = (int)$validated['carnet'];
            $carrera = $this->rolCarreraTable
                ->getCarreraIdByRolId($rolId);

            if (!$carrera) {
                throw new \Exception(
                    'El rol no tiene carrera asociada.'
                );
            }
            //$this->esValido($carnet, $carrera, $rolId);
            $this->validarEstudianteTipoAsociacion(
                (int)$validated['carnet'],
                $rolId,
                $validated['cui']
            );
        }
        $extension = $this->rolTable
            ->getExtensionByRolId($rolId);

        $user = new User();
        $user->exchangeArray([
            'id' => null,
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'password' => $hashedPassword,
            'rol_id' => $rolId,
            'estado' => 1,
            'correo' => $validated['correo'],
            'cui' => $validated['cui'],
            'carnet' => $validated['carnet'] ?: null,
            'registro_personal' => $validated['registroPersonal'] ?: null,

            'extension' => $extension,
        ]);

        return $this->userTable->saveUser($user);
    }

    public function listarUsuarios()
    {
        return $this->userTable->getAllUsers();
    }

    public function buscarUsuario(string $data)
    {
        return $this->userTable->searchUser($data);
    }

    public function obtenerUsuarioPorId(int $id)
    {
        return $this->userTable->getUserById($id);
    }

    public function editUser(array $data): int
    {
        $usuarioActual = $this->userTable->getUserById((int)$data['id']);

        if (!$usuarioActual) {
            throw new \Exception("Usuario no encontrado.");
        }

        $usuarioLogueado = $this->session;
        $tipoActual = $this->rolTable->getTipoIdByRolId($usuarioLogueado->rol_id);

        $esAdmin = ((int)$tipoActual === 1);

        if ($esAdmin) {

            $validated = $this->validateUserData($data, $usuarioActual, true);

            $rolFinal = (int)($data['rol_id'] ?? $usuarioActual->rol_id);

            // password
            $password = $data['password'] ?? '';
            $password2 = $data['password2'] ?? '';

            if ($password || $password2) {
                if ($password !== $password2) {
                    throw new \Exception("Las contraseñas no coinciden.");
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
            } else {
                $hashedPassword = $usuarioActual->password;
            }

            $tipoId = $this->rolTable->getTipoIdByRolId($rolFinal);

            $carnet = (int)($validated['carnet'] ?? $usuarioActual->carnet);
            $cui = $validated['cui'];

            if ($tipoId == 4) { //esto es para cuando el administrador debe de modificar a un usuario tipo asociacion de estudiantes
                $this->validarEstudianteTipoAsociacion(
                    $carnet,
                    $rolFinal,
                    $cui
                );
            }

            $extension = $this->rolTable->getExtensionByRolId($rolFinal);

            $user = new User();
            $user->exchangeArray([
                'id' => $usuarioActual->id,
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'password' => $hashedPassword,
                'rol_id' => $rolFinal,
                'estado' => $usuarioActual->estado,
                'correo' => $validated['correo'],
                'cui' => $validated['cui'],
                'carnet' => $validated['carnet'] ?: null,
                'registro_personal' => $validated['registroPersonal'] ?: null,
                'extension' => $extension,
            ]);

            return $this->userTable->editUser($user);
        }

        $password = $data['password'] ?? '';
        $password2 = $data['password2'] ?? '';

        if ($password === '' && $password2 === '') {
            throw new \Exception("Debes ingresar una contraseña.");
        }

        if ($password !== $password2) {
            throw new \Exception("Las contraseñas no coinciden.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

        $user = new User();
        $user->exchangeArray([
            'id' => $usuarioActual->id,
            'nombre' => $usuarioActual->nombre,
            'apellido' => $usuarioActual->apellido,
            'password' => $hashedPassword,
            'rol_id' => $usuarioActual->rol_id,
            'estado' => $usuarioActual->estado,
            'correo' => $usuarioActual->correo,
            'cui' => $usuarioActual->cui,
            'carnet' => $usuarioActual->carnet,
            'registro_personal' => $usuarioActual->registro_personal,
            'extension' => $usuarioActual->extension,
        ]);

        return $this->userTable->editUser($user);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userTable->getUserById($id);
    }

    public function eliminarUsuario(int $id): void //eliminacion logica
    {
        $this->userTable->deleteUser($id);
    }

    public function activarUsuario(int $id): void
    {
        $this->userTable->enableUser($id);
    }



    private function validateUserData(array $data, ?User $existingUser = null, bool $isAdmin = false): array
    {
        $errors = [];

        // Nombre
        $nombre = trim($data['nombre'] ?? $existingUser->nombre ?? '');
        if ($nombre === '') {
            throw new \Exception("El nombre es obligatorio.");
        }
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/', $nombre)) {
            throw new \Exception("El nombre solo puede contener letras.");
        }

        // Apellido
        $apellido = trim($data['apellido'] ?? $existingUser->apellido ?? '');
        if ($apellido === '') {
            throw new \Exception("El apellido es obligatorio.");
        }
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/', $apellido)) {
            throw new \Exception("El apellido solo puede contener letras.");
        }

        // Correo
        $correo = strtolower(trim($data['correo'] ?? $existingUser->correo ?? ''));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("El correo electrónico no tiene un formato válido.");
        }
        if (!preg_match('/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/', $correo)) {
            throw new \Exception("El correo electrónico no tiene un dominio válido.");
        }

        // Correo duplicado (excluyendo usuario existente)
        $userCorreo = $this->userTable->getUserByCorreo($correo);
        if ($userCorreo && (!$existingUser || $userCorreo->id != $existingUser->id)) {
            throw new \Exception("El correo ya está registrado.");
        }

        // CUI
        $cui = trim($data['cui'] ?? $existingUser->cui ?? '');
        if ($cui === '' || !ctype_digit($cui)) {
            throw new \Exception("El CUI es obligatorio y solo debe contener números.");
        }
        $userCui = $this->userTable->getUserByCui($cui);
        if ($userCui && (!$existingUser || $userCui->id != $existingUser->id)) {
            throw new \Exception("El CUI: $cui ya está registrado.");
        }

        // Carnet (opcional)
        $carnet = trim($data['carnet'] ?? $existingUser->carnet ?? '');
        if ($carnet !== '' && !ctype_digit($carnet)) {
            throw new \Exception("El carnet solo puede contener números.");
        }
        $userCarnet = $this->userTable->getUserByCarnet($carnet);
        if ($userCarnet && (!$existingUser || $userCarnet->id != $existingUser->id)) {
            throw new \Exception("El carnet: $carnet ya está registrado.");
        }

        //validar que el carnet coincida con el cui que se ingresa desde el formulario
        if (!empty($carnet)) {

            // Buscar estudiante en tabla académica
            $estudiante = $this->studentTable
                ->getByCarnet($carnet);

            if (!$estudiante) {
                throw new \Exception(
                    "El carnet $carnet no existe en el sistema académico."
                );
            }

            // Comparar CUI
            if ((string)$estudiante->dpi !== (string)$cui) {
                throw new \Exception(
                    "El CUI ingresado no corresponde al carnet $carnet."
                );
            }
        }

        //validar carnet editable
        $carnetActual = $existingUser->carnet ?? null;

        $this->validarEstudianteEditable(
            $carnet,
            $carnetActual
        );

        // Registro personal (opcional)
        $registroPersonal = trim($data['registro_personal'] ?? $existingUser->registro_personal ?? '');
        if ($registroPersonal !== '' && !ctype_digit($registroPersonal)) {
            throw new \Exception("El registro personal solo puede contener números.");
        }
        $userRegistro = $this->userTable->getUserByRegistroPersonal($registroPersonal);
        if ($userRegistro && (!$existingUser || $userRegistro->id != $existingUser->id)) {
            throw new \Exception("El registro personal: $registroPersonal ya está registrado.");
        }

        //extension
        // $extension = (int)($data['extension'] ?? $existingUser->extension ?? 0);
        // if (!in_array($extension, [1, 2])) {
        //     throw new \Exception('Debe seleccionar una extensión válida.');
        // }

        return compact(
            'nombre',
            'apellido',
            'correo',
            'cui',
            'carnet',
            'registroPersonal',
            //'extension'
        );
    }

    private function validarEstudianteEditable(
        string $carnet,
        ?string $carnetActual
    ): void {

        // Si no ingresó carnet, no validar
        if ($carnet === '') {
            return;
        }

        // Si no cambió el carnet, no validar
        if ($carnet === $carnetActual) {
            return;
        }

        // Obtener carreras activas del estudiante
        $carreras = $this->inscripcionTable->obtenerCarreraActual((int)$carnet);

        if (!$carreras || count($carreras) === 0) {
            throw new \Exception(
                "El estudiante con carnet $carnet no tiene inscripción activa."
            );
        }

        // Verificar que tenga al menos una carrera no cerrada
        foreach ($carreras as $inscripcion) {

            if (empty($inscripcion['fecha_cierre'])) {
                return;
            }
        }

        throw new \Exception(
            "El estudiante ya cerró todas sus carreras."
        );
    }

    private function esValido(int $carnet, int $carrera, int $rolId): bool
    {
        $extension = $this->rolTable
            ->getExtensionByRolId($rolId);

        if (!$extension) {
            throw new \Exception(
                'El rol no tiene extensión asociada.'
            );
        }

        $esValido = $this->studentTable
            ->estudianteValidoAsociacion(
                $carnet,
                $carrera,
                $extension
            );
        if (!$esValido) {
            // echo "<pre>";
            // var_dump([
            //     'carnet' => $carnet,
            //     'carrera' => $carrera,
            //     'extension' => $extension
            // ]);
            // exit;

            throw new \Exception(
                'El estudiante no cumple condiciones:
            - No tiene la extension requerida
            - No está inscrito este año
            - No pertenece a la carrera
            - Ya cerró carrera'
            );
        }
        return true;
    }

    private function validarEstudianteTipoAsociacion(int $carnet, int $rolId, string $cui): void
    {
        //$carrera = $this->rolTable->getCarreraIdByRolId($rolId);
        $carreras = $this->rolCarreraTable->getCarrerasByRol($rolId);

        if (!$carreras) {
            throw new \Exception('El rol no tiene carrera asociada.');
        }

        $estudiante = $this->studentTable->getByCarnet($carnet);

        if (!$estudiante) {
            throw new \Exception("El carnet no existe en sistema académico.");
        }

        if ((string)$estudiante->dpi !== (string)$cui) {
            throw new \Exception("El CUI no corresponde al carnet.");
        }

        $extension = $this->rolTable->getExtensionByRolId($rolId);

        if ($extension === null) {
            throw new \Exception('El rol no tiene extensión asociada.');
        }

        $esValido = false;

        foreach ($carreras as $carrera) {
            if ($this->studentTable->estudianteValidoAsociacion(
                $carnet,
                $carrera,
                $extension
            )) {
                $esValido = true;
                break;
            }
        }

        if (!$esValido) {
            throw new \Exception(
                'El estudiante no cumple condiciones:
            - No tiene la extension requerida
            - No está inscrito este año
            - No pertenece a la carrera
            - Ya cerró carrera'
            );
        }
    }
}

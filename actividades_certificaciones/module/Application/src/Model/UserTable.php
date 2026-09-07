<?php
// clase encargada de realizar consultas a la tabla usuarios en la base de datos
namespace Application\Model;

use Laminas\Db\TableGateway\TableGateway;
use Application\Model\RolTable;
use \InvalidArgumentException;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;

class UserTable
{
    // TableGateway que permite ejecutar consultas a la tabla usuarios
    private TableGateway $tableGateway;
    //recibe el TableGateway configurado en la fábrica.
    private RolTable $rolTable;
    public function __construct(TableGateway $tableGateway, RolTable $rolTable)
    {
        $this->tableGateway = $tableGateway;
        $this->rolTable = $rolTable;
    }
    /**
     * Convierte una fila de la base de datos en un objeto User.
     *
     * @param mixed $row Fila obtenida del TableGateway.
     * @return User|null Usuario si existe, null si no se encontró.
     */
    private function getUserObject($row): ?User
    {
        // Si no existe el registro, retornar null
        if (!$row) {
            return null;
        }
        // Crear un nuevo objeto User
        $user = new User();
        // Llenar el objeto con los datos provenientes del array
        $user->exchangeArray((array) $row);

        return $user;
    }
    //Obtiene un usuario por su ID.
    public function getUserById(int $id): ?User
    {
        // current() obtiene la primera coincidencia
        return $this->getUserObject(
            $this->tableGateway->select(['id' => $id])->current()
        );
    }
    //Obtiene un usuario por su nombre de usuario. se usa en AutService
    // public function getUserByUsername(string $username): ?User
    // {
    //     return $this->getUserObject(
    //         $this->tableGateway->select(['username' => $username])->current()
    //     );
    // }
    //obtiene un usuario por su correo electrónico.
    public function getUserByCorreo(string $correo): ?User
    {
        return $this->getUserObject(
            $this->tableGateway->select(['correo' => $correo])->current()
        );
    }

    private function validateLength(
        string $value,
        int $length,
        string $fieldName
    ): void {
        if (strlen($value) !== $length) {
            throw new InvalidArgumentException(
                "$fieldName debe tener exactamente $length caracteres."
            );
        }
    }

    public function getUserByCui(string $cui): ?User
    {
        $this->validateLength($cui, 13, 'CUI');
        $row = $this->tableGateway->select(['cui' => $cui])->current();
        if (!$row) return null;
        return $this->getUserObject($row);
    }

    public function getUserByCarnet(string $carnet): ?User
    {
        if ($carnet === '') return null; // vacío no cuenta
        $this->validateLength($carnet, 9, 'Carnet');
        $row = $this->tableGateway->select(['carnet' => $carnet])->current();
        if (!$row) return null;
        return $this->getUserObject($row);
    }

    public function getUserByRegistroPersonal(string $registroPersonal): ?User
    {
        if ($registroPersonal === '') return null; // vacío no cuenta
        $this->validateLength($registroPersonal, 8, 'Registro Personal');
        $row = $this->tableGateway->select(['registro_personal' => $registroPersonal])->current();
        if (!$row) return null;
        return $this->getUserObject($row);
    }



    //Actualiza la contraseña de un usuario.
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->tableGateway->update(
            ['password' => $hashedPassword],
            ['id' => $userId]
        ) > 0;
    }
    //Inserta o actualiza un usuario en la base de datos.
    public function saveUser(User $user): int
    {
        // Convierte el objeto User en un array
        $data = $user->getArrayCopy();
        unset($data['tipo_id']);
        // Si el usuario no tiene ID, significa INSERT
        if ($user->id === null) {
            $this->tableGateway->insert($data);
            // Devuelve el ID generado automáticamente por la base de datos
            return (int) $this->tableGateway->getLastInsertValue();
        }
        // Si ya tiene ID, significa UPDATE
        $this->tableGateway->update($data, ['id' => $user->id]);
        return $user->id;
    }
    //Elimina un usuario por su ID.
    public function deleteUser(int $id): void
    {
        // DELETE lógico → estado = 0
        $this->tableGateway->update(
            ['estado' => 0],
            ['id' => $id]
        );
    }

    public function enableUser(int $id): void
    {
        //el estado = 1
        $this->tableGateway->update(
            ['estado' => 1],
            ['id' => $id]
        );
    }

    //para obtener todos los usuarios registrados

    public function getAllUsers(): array
    {
        $resultSet = $this->tableGateway->select(
            function (Select $select) {

                $select->columns([
                    'id',
                    'nombre',
                    'apellido',
                    'correo',
                    'estado',
                    'rol_id'
                ]);

                $select->join(
                    ['r' => 'roles'],
                    'rol_id = r.id',
                    ['rol_nombre' => 'nombre'],
                    Select::JOIN_LEFT
                );
            }
        );

        $users = [];

        foreach ($resultSet as $user) {

            $users[] = [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'correo' => $user->correo,
                'rol_id' => $user->rol_id,
                'rol_nombre' => $user->rol_nombre,
                'estado' => $user->estado,
                'estado_texto' =>
                $user->estado == 1
                    ? 'Activo'
                    : 'Inactivo',
            ];
        }

        return $users;
    }

    public function searchUser(string $query): array
    {
        $resultSet = $this->tableGateway->select(function ($select) use ($query) {
            $select->where
                ->like('carnet', "%$query%")
                ->or
                ->like('cui', "%$query%")
                ->or
                ->like('registro_personal', "%$query%");
        });

        $usuarios = [];

        foreach ($resultSet as $user) {

            $rol = $this->rolTable->getRolById((int)$user->rol_id);

            $usuarios[] = [
                'id'         => $user->id,
                'nombre'   => $user->nombre,
                'apellido'   => $user->apellido,
                'correo'     => $user->correo,
                'estado'     => $user->estado,
                'cui'        => $user->cui,
                'rol_nombre' => $rol ? $rol->nombre : 'Desconocido',
            ];
        }

        return $usuarios;
    }

    public function editUser(User $user): int
    {
        // Convierte el objeto User en un array
        $data = $user->getArrayCopy();

        // Si ya tiene ID, significa UPDATE
        $this->tableGateway->update($data, ['id' => $user->id]);
        return $user->id;
    }

    public function getUserByLogin(string $login): ?User //esto es para el login, se puede usar username, correo, carnet, cui o registro personal
    {
        $result = $this->tableGateway->select(function ($select) use ($login) {
            $select->where
                // ->equalTo('username', $login)
                // ->or
                ->expression('TRIM(correo) = ?', [$login])
                ->or
                ->expression('TRIM(carnet) = ?', [$login])
                ->or
                ->expression('TRIM(cui) = ?', [$login])
                ->or
                ->expression('TRIM(registro_personal) = ?', [$login]);

            $select->limit(1);
        })->current();

        return $this->getUserObject($result);
    }

    public function getRolById(int $id)
    {
        $sql = new Sql($this->tableGateway->getAdapter());

        $select = $sql->select();
        $select->from('roles')
            ->where(['id' => $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute()->current();

        return $result;
    }
}

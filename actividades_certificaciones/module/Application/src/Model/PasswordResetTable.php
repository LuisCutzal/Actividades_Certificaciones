<?php
//clase es la encargada de manejar todas las operaciones relacionadas con la tabla password_resets
declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
/*
 Crear tokens
 consulta tokens
 Marcar tokens como usados
 Invalidar tokens anteriores
*/

class PasswordResetTable
{
    private Adapter $db;
    private TableGatewayInterface $tableGateway;
    /*
     $tableGateway   Gateway para CRUD básico sobre la tabla.
     $db  Adaptador de base de datos para consultas personalizadas.
     */
    public function __construct(TableGatewayInterface $tableGateway, Adapter $db)
    {
        $this->db = $db;
        $this->tableGateway = $tableGateway;
    }
    /*
     * Crea un nuevo token de recuperación para un usuario.
     *
     $userId     ID del usuario dueño del token.
     $token      Token único generado.
     $expiresAt  Fecha y hora de expiración (string compatible con SQL).
     */
    public function createResetToken(int $userId, string $token, string $expiresAt): void
    {
        $this->tableGateway->insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'used' => 0
        ]);
    }
    /*
     Obtiene un token válido (no usado) a partir del código recibido por correo.
     
     string $token  Token enviado por el usuario.
     array|null    Devuelve los datos del token o null si no existe o ya está usado.
     */
    public function getByToken(string $token): ?array
    {
        /** @var \Laminas\Db\ResultSet\ResultSet $rowset */
        $rowset = $this->tableGateway->select(['token' => $token, 'used' => 0]);

        /** @var \ArrayObject|null $row */
        $row = $rowset->current();

        return $row ? (array)$row : null;
    }
    //Marca un token como usado para evitar reutilización.
    public function markAsUsed(int $id): void
    {
        $this->tableGateway->update(['used' => 1], ['id' => $id]);
    }
    //Invalida todos los tokens anteriores del usuario.
    public function invalidateOldTokens(int $userId)
    {
        $this->tableGateway->update(
            ['used' => 1],   // marca todos como usados
            ['user_id' => $userId] // filtro por usuario
        );
    }
}

<?php
// clase encargada de realizar consultas a la tabla usuarios en la base de datos
namespace Application\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;
use ArrayObject;

class PlantillasPDFTable
{
    private TableGatewayInterface $tableGateway;
    //recibe el TableGateway configurado en la fábrica.
    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getByCarreraExtension(int $carrera, int $extension)
    {
        $rowset = $this->tableGateway->select([
            'carrera_id' => $carrera,
            'extension_id' => $extension
        ]);

        return $rowset->current();
    }

    public function save(ArrayObject $plantilla): void
    {
        if (empty($plantilla['arte_contenido'])) {
            throw new \Exception(
                'No existe arte contenido para guardar.'
            );
        }

        if (empty($plantilla['arte_final'])) {
            throw new \Exception(
                'No existe arte final para guardar.'
            );
        }

        $data = [
            'nombre'         => $plantilla['nombre'] ?? null,
            'arte_contenido' => $plantilla['arte_contenido'],
            'arte_final'     => $plantilla['arte_final'],
            'titulo'         => $plantilla['titulo'],
            'texto_firma'    => $plantilla['texto_firma'],
            'institucion'    => $plantilla['institucion'],
            'texto_constancia' => $plantilla['texto_constancia'] ?? '',
            'punto_acta'       => $plantilla['punto_acta'] ?? '',
            'inciso_acta'      => $plantilla['inciso_acta'] ?? '',
            'numero_acta'      => $plantilla['numero_acta'] ?? '',
            'fecha_acta'       => $plantilla['fecha_acta'] ?? '',
        ];

        if (empty($plantilla['id'])) {

            $data['codigo'] = strtoupper(
                substr(uniqid('PDF'), -10)
            );

            $data['carrera_id'] = $plantilla['carrera_id'] ?? null;
            $data['extension_id'] = $plantilla['extension_id'] ?? null;

            $this->tableGateway->insert($data);

            return;
        }

        $this->tableGateway->update(
            $data,
            ['id' => $plantilla['id']]
        );
    }
}

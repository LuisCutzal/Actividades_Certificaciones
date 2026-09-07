<?php

namespace Application\Controller;

use Application\Service\EstudianteService;
use Laminas\View\Model\ViewModel;

use Application\Utils\PaginatorHelper;

class EstudianteController extends BaseController
{
    private EstudianteService $estudianteService;

    public function __construct(EstudianteService $estudianteService)
    {
        $this->estudianteService = $estudianteService;
    }

    public function indexAction()
    {
        $user = $this->checkAuth([2, 4]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $carrerasUsuario = $user['carreras'] ?? [];
        $extension = (int)$user['extension'];

        // Si tiene carreras asignadas → filtrar por ellas
        if (!empty($carrerasUsuario)) {

            $estudiantes =
                $this->estudianteService
                ->listarPorCarreras(
                    $carrerasUsuario,
                    $extension
                );
        } else {

            // Si no tiene carreras
            // ver todos los de su extensión

            $estudiantes =
                $this->estudianteService
                ->listarPorExtension(
                    $extension
                );
        }

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($estudiantes, $page, 10);

        return new ViewModel([
            'estudiantes' => $paginator,
            'query' => null,
            'tipo_id' => $user['tipo_id'],
        ]);
    }


    public function buscarAction() //esto se utiliza para la seccion /estudiantes
    {
        $user = $this->checkAuth([2, 4]);

        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        //$query = trim($this->params()->fromQuery('q'));
        $query = trim($this->params()->fromQuery('q', ''));
        $estudiantes = [];
        $mensaje = null;

        if ($query !== '') {
            $resultado = $this->estudianteService->buscarPorTipo(
                $query,
                (int)$user['tipo_id'],
                $user['carreras'] ?? [],
                (int)$user['extension']
            );

            $estudiantes = $resultado['estudiantes'];
            $mensaje = $resultado['mensaje'];
        }

        return new ViewModel([
            'estudiantes' => $estudiantes,
            'mensaje'     => $mensaje,
            'query'       => $query,
            'tipo_id'      => $user['tipo_id'],
        ]);
    }

    public function verAction()
    {
        $this->checkAuth([2, 4]);

        $buscar = trim($this->params()->fromQuery('buscar', ''));
        $carnet = (int) $this->params()->fromRoute('carnet');

        if (!$carnet) {
            return $this->redirect()->toUrl('/estudiantes');
        }

        $estudiante = $this->estudianteService->obtenerEstudiante($carnet);

        if (!$estudiante) {
            throw new \Exception('Estudiante no encontrado');
        }

        $actividades = $this->estudianteService
            ->obtenerActividadesDelEstudiante($carnet, $buscar);

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($actividades, $page, 10);

        return new ViewModel([
            'estudiante'  => $estudiante,
            'actividades' => $paginator,
            'buscar'      => $buscar,
        ]);
    }
}

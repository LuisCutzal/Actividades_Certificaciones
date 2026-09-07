<?php

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Service\ReporteService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use Application\Model\ReportePdfTable;
use NumberFormatter;

use Application\Service\AuditService;

use Application\Utils\PaginatorHelper;

class ReporteController extends BaseController
{
    private ReporteService $reporteService;
    private ReportePdfTable $reportePdfTable;
    private PhpRenderer $renderer;
    private AuditService $auditService;

    public function __construct(ReporteService $reporteService, ReportePdfTable $reportePdfTable, PhpRenderer $renderer, AuditService $auditService)
    {
        $this->reporteService = $reporteService;
        $this->reportePdfTable = $reportePdfTable;
        $this->renderer = $renderer;
        $this->auditService = $auditService;
    }

    public function actividadesAction() //Reporte de Actividades
    {
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $buscar = trim($this->params()->fromQuery('buscar', ''));

        $tipoId = (int) $user['tipo_id'];
        $userCarreras = $user['carreras'] ?? [];
        $userExtension = (int) ($user['extension'] ?? 0);
        $rolId = (int) $user['rol_id']; // esto es para el filtro de asociaciones, que solo vean lo suyo

        //  Obtener datos (igual que ya lo tienes)
        $resultSet = $buscar !== ''
            ? $this->reporteService->buscarActividades($buscar)
            : $this->reporteService->reporteActividades(
                $tipoId,
                $userCarreras,
                $userExtension,
                $rolId
            );

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($resultSet, $page, 15);

        return new ViewModel([
            'actividades' => $paginator,
            'buscar'      => $buscar,
        ]);
    }

    public function actividadAction() //este es el boton Ver Reporte de la tabla de Reporte de Actividades 
    {
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $id = (int) $this->params()->fromRoute('id');

        $actividad = $this->reporteService->reporteActividad($id);

        if (!$actividad) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'actividad' => $actividad,
        ]);
    }

    public function actividadPdfAction() //esto es el boton  📄 PDF – Detalle de actividad del archivo actividad.phtml
    {
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }
        $id = (int) $this->params()->fromRoute('id');

        $actividad = $this->reporteService->reporteActividad($id);

        if (!$actividad) {
            return $this->notFoundAction();
        }
        $correlativo = $this->reportePdfTable->generarCorrelativo('ACT', (int) date('Y'));
        $this->reportePdfTable->insert([
            'tipo'             => 1,
            'carnet'    => null,
            'id_actividad'     => $actividad->id,
            'generado_por'     => $user['id'],
            'rol_generador'    => $user['rol_id'],
            'estado_actividad' => $actividad->estado ?? null,
            'correlativo'      => $correlativo,
        ]);

        //log de activdad pdf

        $this->auditService->log(
            (int)$user['id'],
            (string)($user['cui'] ?? null),
            'REPORT_ACTIVITY_PDF_GENERATED',
            'REPORT',
            (int)$id,
            'Se generó PDF de actividad',
            [
                'correlativo' => $correlativo
            ]
        );

        //Configurar Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // Renderizar la vista manualmente
        $renderer = new PhpRenderer();
        $resolver = new TemplatePathStack([
            'script_paths' => [
                __DIR__ . '/../../view',
            ],
        ]);

        $renderer->setResolver($resolver);

        $html = $renderer->render(
            'application/reporte/pdf/actividad',
            ['actividad' => $actividad]
        );

        // Generar PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        //Enviar al navegador
        $response = $this->getResponse();
        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'application/pdf')
            ->addHeaderLine(
                'Content-Disposition',
                'inline; filename="reporte_actividad_' . $actividad->id . '.pdf"'
            );

        $response->setContent($dompdf->output());

        return $response;
    }

    public function actividadParticipantesPdfAction() //este es del boton 👥 PDF – Lista de estudiantes del archivo actividad.phtml
    {
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }
        $id = (int) $this->params()->fromRoute('id');

        $rows = $this->reporteService->reporteParticipantesActividad($id);

        if (empty($rows)) {
            $this->flashMessenger()->addWarningMessage(
                'Esta actividad no tiene participantes registrados.'
            );

            return $this->redirect()->toRoute('reportes/actividades');
        }

        // De aquí sacamos TODO
        $actividad     = $rows[0];
        $participantes = $rows;
        $correlativo = $this->reportePdfTable->generarCorrelativo('ACT-PART', (int) date('Y'));
        $this->reportePdfTable->insert([
            'tipo'             => 2,
            'carnet'    => null,
            'id_actividad'     => $actividad->actividad_id,
            'generado_por'     => $user['id'],
            'rol_generador'    => $user['rol_id'],
            'estado_actividad' => $actividad->estado ?? null,
            'correlativo'      => $correlativo,
        ]);

        //log de actividad participantes pdf

        $this->auditService->log(
            (int)$user['id'],
            (string)($user['cui'] ?? null),
            'REPORT_ACTIVITY_PARTICIPANTS_PDF',
            'REPORT',
            (int)$id,
            'Se generó PDF de participantes de actividad',
            [
                'actividad_id' => $actividad->actividad_id,
                'correlativo' => $correlativo
            ]
        );

        /** Renderizar vista */
        $renderer = new PhpRenderer();
        $resolver = new TemplatePathStack([
            'script_paths' => [
                __DIR__ . '/../../view',
            ],
        ]);
        $renderer->setResolver($resolver);

        $html = $renderer->render(
            'application/reporte/pdf/actividad-participantes',
            [
                'actividad'     => $actividad,
                'participantes' => $participantes,
            ]
        );

        /** Dompdf */
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response = $this->getResponse();
        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'application/pdf')
            ->addHeaderLine(
                'Content-Disposition',
                'inline; filename="lista_estudiantes_actividad_' . $id . '.pdf"'
            );

        $response->setContent($dompdf->output());
        return $response;
    }

    //para la auditoria de los estudiantes
    public function auditoriaEstudiantesAction()
    {
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $carnet = $this->params()->fromQuery('carnet');

        //  Obtener datos
        $estudiantes = $this->reporteService->reporteAuditoriaEstudiantes($carnet);

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($estudiantes, $page, 15);
        return new ViewModel([
            'estudiantes' => $paginator,
            'carnet'      => $carnet,
            'query' => [
                'carnet' => $carnet
            ]
        ]);
    }

    //esto es para la asociacion de estudiantes
    public function auditoriaAction() //esto es del boton Auditoria de estudiantes
    //aca solo filtramos por organizacion, entonces el rol 4 solo ve los estudiantes de su organizacion
    {
        $user = $this->checkAuth([4]); // asociaciones
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        $carnet = $this->params()->fromQuery('carnet');


        //$estudiantes = $this->reporteService->fetchAuditoriaEstudiantesByOrganizacion($user['rol_id'], $carnet);

        $estudiantes = $this->reporteService->fetchAuditoriaEstudiantesByOrganizacion($user, $carnet);

        //log 

        // $this->auditService->log(
        //     (int)$user['id'],
        //     (string)($user['cui'] ?? null),
        //     'STUDENT_AUDIT_ORG_VIEWED',
        //     'STUDENT',
        //     null,
        //     'Acceso a auditoría de estudiantes por asociación'
        // );


        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($estudiantes, $page, 15);

        $view = new ViewModel([
            'estudiantes' => $paginator,
            'carnet'      => $carnet,
        ]);
        $view->setTemplate('application/reporte/auditoria');
        return $view;
    }

    public function historialAction()
    {
        $user = $this->checkAuth([1]);
        if ($user instanceof \Laminas\Http\Response) {
            return $user;
        }

        //$correlativo = $this->params()->fromQuery('q');
        $correlativo = trim($this->params()->fromQuery('q', ''));
        //  Obtener datos
        $datos = $this->reportePdfTable->buscarPorCorrelativo($correlativo);

        $page = (int) $this->params()->fromQuery('page', 1);

        $paginator = PaginatorHelper::paginate($datos, $page, 15);

        return new ViewModel([
            'reportes' => $paginator,
            'buscar'   => $correlativo,
            'rol'      => $user['rol_id'],
            'query' => [
                'q' => $correlativo
            ]
        ]);
    }
}

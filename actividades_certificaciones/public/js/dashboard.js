let estadoChart = null;
let mesChart = null;
let topChart = null;
let usuariosChart = null;
let carreraChart = null;


/*
|--------------------------------------------------------------------------
| CARGAR DASHBOARD
|--------------------------------------------------------------------------
*/

async function cargarDashboard() {

    const year =
        document.getElementById('yearFilter').value;

    const monthStart =
        document.getElementById('monthStart').value;

    const monthEnd =
        document.getElementById('monthEnd').value;

    const dateStart =
        document.getElementById('dateStart').value;

    const dateEnd =
        document.getElementById('dateEnd').value;

    const params = new URLSearchParams({
        year,
        monthStart,
        monthEnd,
        dateStart,
        dateEnd
    });



    const currentPath = window.location.pathname;

    let apiUrl = '';

    if (currentPath.startsWith('/admin')) {

        apiUrl = '/admin/dashboard-data';

    } else {

        apiUrl = '/dashboard-data';

    }

    const response = await fetch(
        `${apiUrl}?${params}`
    );

    const data = await response.json();

    renderCharts(data);
}


/*
|--------------------------------------------------------------------------
| RENDER CHARTS
|--------------------------------------------------------------------------
*/

function renderCharts(data) {

    /*
    |--------------------------------------------------------------------------
    | DESTRUIR CHARTS ANTERIORES
    |--------------------------------------------------------------------------
    */

    if (estadoChart) estadoChart.destroy();
    if (mesChart) mesChart.destroy();
    if (topChart) topChart.destroy();
    if (usuariosChart) usuariosChart.destroy();
    if (carreraChart) carreraChart.destroy();


    /*
    |--------------------------------------------------------------------------
    | PIE ESTADOS
    |--------------------------------------------------------------------------
    */

    estadoChart = new Chart(
        document.getElementById('estadoChart'),
        {
            type: 'pie',
            data: {
                labels: [
                    'Pendientes',
                    'Aprobadas',
                    'No realizadas'
                ],
                datasets: [{
                    data: [
                        data.aprobadasVsPendientes.pendientes,
                        data.aprobadasVsPendientes.aprobadas,
                        data.aprobadasVsPendientes.rechazadas
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ACTIVIDADES POR MES
    |--------------------------------------------------------------------------
    */

    mesChart = new Chart(
        document.getElementById('mesChart'),
        {
            type: 'bar',
            data: {
                labels: [
                    'Ene', 'Feb', 'Mar', 'Abr',
                    'May', 'Jun', 'Jul', 'Ago',
                    'Sep', 'Oct', 'Nov', 'Dic'
                ],
                datasets: [{
                    label: 'Actividades',
                    data: Object.values(
                        data.actividadesPorMes
                    )
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | TOP ACTIVIDADES
    |--------------------------------------------------------------------------
    */

    topChart = new Chart(
        document.getElementById('topChart'),
        {
            type: 'bar',
            data: {
                labels:
                    data.topActividades.map(
                        a => a.nombre
                    ),

                datasets: [{
                    label: 'Estudiantes',
                    data:
                        data.topActividades.map(
                            a => a.total_estudiantes
                        )
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | TOP USUARIOS
    |--------------------------------------------------------------------------
    */

    usuariosChart = new Chart(
        document.getElementById('usuariosChart'),
        {
            type: 'bar',
            data: {
                labels:
                    data.topUsuarios.map(
                        u => u.nombre + ' ' + u.apellido
                    ),

                datasets: [{
                    label: 'Actividades',
                    data:
                        data.topUsuarios.map(
                            u => u.total
                        )
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CARRERAS
    |--------------------------------------------------------------------------
    */

    carreraChart = new Chart(
        document.getElementById('carreraChart'),
        {
            type: 'doughnut',
            data: {
                labels:
                    data.actividadesPorCarrera.map(
                        c => c.nombre
                    ),

                datasets: [{
                    data:
                        data.actividadesPorCarrera.map(
                            c => c.total
                        )
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        }
    );

}


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

document
    .getElementById('applyFilters')
    .addEventListener(
        'click',
        cargarDashboard
    );


/*
|--------------------------------------------------------------------------
| CARGA INICIAL
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'DOMContentLoaded',
    cargarDashboard
);
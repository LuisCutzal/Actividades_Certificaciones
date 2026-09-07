document.addEventListener('DOMContentLoaded', () => {

    // BOTÓN VER DETALLE
    document.querySelectorAll('.btn-detalles').forEach(btn => {

        btn.addEventListener('click', function () {

            const d = JSON.parse(this.dataset.detalles);

            document.getElementById('dTipo').textContent = d.tipo;
            document.getElementById('dEstudiante').textContent = d.estudiante;
            document.getElementById('dActividad').textContent = d.actividad;
            document.getElementById('dGeneradoPor').textContent = d.generadoPor;
            document.getElementById('dRol').textContent = d.rol;
            document.getElementById('dCorrelativo').textContent = d.correlativo;

            document.getElementById('overlay').style.display = 'block';
            document.getElementById('detalleCard').classList.add('activo');

        });

    });

    // CERRAR
    document.getElementById('overlay')
        ?.addEventListener('click', cerrarDetalle);

    document.querySelector('.cerrar')
        ?.addEventListener('click', cerrarDetalle);

});

function cerrarDetalle() {

    document.getElementById('overlay').style.display = 'none';

    document.getElementById('detalleCard')
        .classList.remove('activo');

}
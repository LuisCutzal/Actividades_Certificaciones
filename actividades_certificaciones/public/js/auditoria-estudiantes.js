document.addEventListener('DOMContentLoaded', () => {

    // Botón detalles
    document.querySelectorAll('.btn-detalles').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.dataset.detalles);

            document.getElementById('dActividad').textContent = d.actividad;
            document.getElementById('dOrganizador').textContent = d.organizador_nombre;
            document.getElementById('dCarrera').textContent = d.carrera;
            document.getElementById('dTipo').textContent = d.tipo;
            document.getElementById('dCredito').textContent = d.credito;

            document.getElementById('dEstadoLista').textContent = d.estadoLista;
            document.getElementById('dFechaActividad').textContent = d.fechaActividad;
            document.getElementById('dAprobado').textContent = d.aprobado ?? '—';
            document.getElementById('dRechazado').textContent = d.rechazado ?? '—';
            document.getElementById('dFechaResolucion').textContent = d.fechaResolucion ?? '—';

            const bloqueMotivo = document.getElementById('bloqueMotivo');
            if (d.motivoRechazo) {
                bloqueMotivo.style.display = 'block';
                document.getElementById('dMotivoRechazo').textContent = d.motivoRechazo;
            } else {
                bloqueMotivo.style.display = 'none';
            }

            document.getElementById('overlay').style.display = 'block';
            document.getElementById('detalleCard').classList.add('activo');
        });
    });

    // Cerrar detalle
    document.getElementById('overlay')?.addEventListener('click', cerrarDetalle);
    document.querySelector('.cerrar')?.addEventListener('click', cerrarDetalle);
});

function cerrarDetalle() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('detalleCard').classList.remove('activo');
}

function formatearMotivo(obj) {
    if (!obj || typeof obj !== 'object') return '';

    return Object.entries(obj)
        .map(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                return `${key}: —`;
            }
            return `${key}: ${value}`;
        })
        .join('\n');
}

document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.btn-detalles').forEach(btn => {
        btn.addEventListener('click', function () {

            const d = JSON.parse(this.dataset.detalles);

            document.getElementById('jsonData').textContent =
                formatearMotivo(d);

            document.getElementById('overlay').style.display = 'block';
            document.getElementById('detalleCard').classList.add('activo');
        });
    });

    document.getElementById('overlay')?.addEventListener('click', cerrarDetalle);
    document.querySelector('.cerrar')?.addEventListener('click', cerrarDetalle);
});

function cerrarDetalle() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('detalleCard').classList.remove('activo');
}
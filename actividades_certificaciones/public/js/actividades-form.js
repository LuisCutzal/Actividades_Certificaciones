document.addEventListener('DOMContentLoaded', () => {

    const tipoLista = document.getElementById('tipo_lista');
    const tipoIndividual = document.getElementById('tipo_individual');
    const campoLista = document.getElementById('campo-lista');
    const campoIndividual = document.getElementById('campo-individual');

    if (!tipoLista || !tipoIndividual) return;

    function actualizarCampos() {
        if (tipoLista.checked) {
            campoLista.classList.remove('d-none');
            campoIndividual.classList.add('d-none');
            campoLista.querySelector('input').required = true;
            campoIndividual.querySelector('input').required = false;
            campoIndividual.querySelector('input').value = '';
        } else {
            campoLista.classList.add('d-none');
            campoIndividual.classList.remove('d-none');
            campoLista.querySelector('input').required = false;
            campoIndividual.querySelector('input').required = true;
        }
    }

    tipoLista.addEventListener('change', actualizarCampos);
    tipoIndividual.addEventListener('change', actualizarCampos);
    actualizarCampos();
});

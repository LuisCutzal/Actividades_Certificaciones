document.querySelectorAll('.insertar-variable').forEach(function (btn) {

    btn.addEventListener('click', function () {

        const textarea = document.getElementById('texto_constancia');
        const variable = this.dataset.variable;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;

        const texto = textarea.value;

        textarea.value =
            texto.substring(0, start) +
            variable +
            texto.substring(end);

        const nuevaPosicion = start + variable.length;

        textarea.focus();
        textarea.setSelectionRange(nuevaPosicion, nuevaPosicion);
    });
});
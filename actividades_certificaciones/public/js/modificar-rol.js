document.addEventListener("DOMContentLoaded", function () {

    const permisoChecks =
        document.querySelectorAll(".permiso-check");

    const bloqueCarreras =
        document.getElementById("bloqueCarreras");

    function verificarPermiso() {

        let mostrar = false;

        permisoChecks.forEach(function (check) {

            if (check.checked && check.value == 1) {
                mostrar = true;
            }

        });

        bloqueCarreras.style.display =
            mostrar ? "block" : "none";
    }

    permisoChecks.forEach(function (check) {

        check.addEventListener(
            "change",
            verificarPermiso
        );

    });

    // Ejecutar al cargar
    verificarPermiso();

});
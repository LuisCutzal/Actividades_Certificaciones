document.addEventListener("DOMContentLoaded", () => {

    const botones = document.querySelectorAll(".btn-detalles");
    const sidebar = document.getElementById("detailSidebar");
    const overlay = document.getElementById("overlay");
    const cerrar = document.getElementById("btnCerrarSidebar");

    botones.forEach(btn => {

        btn.addEventListener("click", () => {

            const data = JSON.parse(btn.dataset.detalles);

            // llenar datos
            document.getElementById("dEstudiante").textContent = data.estudiante;
            document.getElementById("dCarnet").textContent = data.carnet;
            document.getElementById("dActividad").textContent = data.actividad;
            document.getElementById("dOrganizador").textContent = data.organizador;
            document.getElementById("dCarrera").textContent = data.carrera;
            document.getElementById("dTipo").textContent = data.tipo;
            document.getElementById("dCreditos").textContent = data.creditos;
            document.getElementById("dEstado").textContent = data.estado;
            document.getElementById("dFechaActividad").textContent = data.fechaActividad;
            document.getElementById("dCreado").textContent = data.creado;
            document.getElementById("dAprobado").textContent = data.aprobado;
            document.getElementById("dRechazado").textContent = data.rechazado;

            // motivo rechazo
            if (data.motivo && data.motivo !== "—") {

                document.getElementById("bloqueMotivo").style.display = "block";
                document.getElementById("dMotivo").textContent = data.motivo;

            } else {

                document.getElementById("bloqueMotivo").style.display = "none";

            }

            // mostrar sidebar
            sidebar.classList.add("activo");
            overlay.style.display = "block";

        });

    });

    function cerrarSidebar() {

        sidebar.classList.remove("activo");
        overlay.style.display = "none";

    }

    cerrar.addEventListener("click", cerrarSidebar);
    overlay.addEventListener("click", cerrarSidebar);

});
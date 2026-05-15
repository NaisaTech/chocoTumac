// ChocoTumac – JS global
// app.js v2 – Elimina dependencia de Bootstrap JS para el modal de confirmación

document.addEventListener("DOMContentLoaded", function () {

    // --- Auto-cerrar alertas después de 5 s ---
    // --- Auto-cerrar alertas después de 5 s ---
    document.querySelectorAll(".alert-auto").forEach(function (el) {
        setTimeout(function () {
            el.style.transition = "opacity .5s";
            el.style.opacity = "0";
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // --- Validación contraseña segura en tiempo real ---
    const inputPass = document.getElementById("input-password");
    const feedbackPass = document.getElementById("feedback-password");
    if (inputPass && feedbackPass) {
        inputPass.addEventListener("input", function () {
            const val = inputPass.value;
            const ok = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/.test(val);
            if (val.length === 0) {
                inputPass.classList.remove("is-valid", "is-invalid");
                feedbackPass.textContent = "";
            } else if (ok) {
                inputPass.classList.remove("is-invalid");
                inputPass.classList.add("is-valid");
                feedbackPass.className = "form-text text-success";
                feedbackPass.textContent = "✓ Contraseña segura";
            } else {
                inputPass.classList.remove("is-valid");
                inputPass.classList.add("is-invalid");
                feedbackPass.className = "form-text text-danger";
                feedbackPass.textContent = "Debe tener 8+ caracteres, una mayúscula, una minúscula y un número.";
            }
        });
    }

    // --- Confirmación de eliminación con modal ---
    // CORRECCIÓN: Se reemplazó `new bootstrap.Modal(modal).show()` con
    // manipulación directa de CSS. La causa del fallo era que Bootstrap JS
    // se cargaba únicamente desde CDN externo; si el CDN tardaba o fallaba,
    // `bootstrap` quedaba indefinido y el modal nunca se abría.
    // Esta implementación replica el comportamiento exacto de Bootstrap 5
    // sin depender de que su JS haya cargado.

    // Helper: muestra el modal manipulando clases directamente
    function mostrarModal(modal) {
        // Backdrop
        var backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        backdrop.id = "modalBackdrop";
        document.body.appendChild(backdrop);

        // Mostrar modal
        modal.style.display = "block";
        modal.removeAttribute("aria-hidden");
        modal.setAttribute("aria-modal", "true");
        document.body.classList.add("modal-open");

        // La clase "show" activa la visibilidad en Bootstrap CSS
        requestAnimationFrame(function () {
            modal.classList.add("show");
        });
    }

    // Helper: oculta el modal
    function ocultarModal(modal) {
        modal.classList.remove("show");
        modal.style.display = "none";
        modal.setAttribute("aria-hidden", "true");
        modal.removeAttribute("aria-modal");
        document.body.classList.remove("modal-open");

        var backdrop = document.getElementById("modalBackdrop");
        if (backdrop) { backdrop.remove(); }
    }

    // Botones que abren el modal de confirmación
    document.querySelectorAll(".btn-confirmar-eliminar").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            var url = btn.getAttribute("data-url");
            var nombre = btn.getAttribute("data-nombre") || "este registro";
            var modal = document.getElementById("modalConfirmarEliminar");
            var texto = document.getElementById("modalEliminarTexto");
            var link = document.getElementById("modalEliminarLink");

            if (modal && texto && link) {
                texto.textContent = "¿Estás seguro de que deseas eliminar a " + nombre + "? Esta acción no se puede deshacer.";
                link.setAttribute("href", url);
                mostrarModal(modal);
            }
        });
    });

    // Botones que cierran el modal (data-bs-dismiss="modal" y backdrop)
    document.addEventListener("click", function (e) {
        var modal = document.getElementById("modalConfirmarEliminar");
        if (!modal) { return; }

        // Clic en cualquier elemento con data-bs-dismiss="modal"
        if (e.target.closest("[data-bs-dismiss='modal']")) {
            ocultarModal(modal);
            return;
        }

        // Clic en el backdrop (fuera del modal-dialog)
        if (e.target === modal) {
            ocultarModal(modal);
        }
    });

    // Cerrar con tecla Escape
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            var modal = document.getElementById("modalConfirmarEliminar");
            if (modal && modal.classList.contains("show")) {
                ocultarModal(modal);
            }
        }
    });

    // --- Validación login ---
    const loginForm = document.querySelector("form[action*='action=login']");
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            const email = loginForm.querySelector("[name='email']");
            const pass = loginForm.querySelector("[name='password']");
            let valid = true;
            [email, pass].forEach(function (input) {
                if (!input) { return; }
                if (!input.value.trim()) {
                    input.classList.add("is-invalid");
                    valid = false;
                } else {
                    input.classList.remove("is-invalid");
                }
            });
            if (!valid) { e.preventDefault(); }
        });
        loginForm.querySelectorAll("input").forEach(function (input) {
            input.addEventListener("input", function () {
                input.classList.remove("is-invalid");
            });
        });
    }

    // --- Validación básica frontend en formularios marcados ---
    document.querySelectorAll("form[data-validate]").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    });
});
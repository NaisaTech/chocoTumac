// ChocoTumac – JS global

document.addEventListener("DOMContentLoaded", function () {

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
            const ok  = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/.test(val);
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

    // --- Confirmación de eliminación con modal Bootstrap ---
    document.querySelectorAll(".btn-confirmar-eliminar").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            const url    = btn.getAttribute("data-url");
            const nombre = btn.getAttribute("data-nombre") || "este registro";
            const modal  = document.getElementById("modalConfirmarEliminar");
            const texto  = document.getElementById("modalEliminarTexto");
            const link   = document.getElementById("modalEliminarLink");
            if (modal && texto && link) {
                texto.textContent = "¿Estás seguro de que deseas eliminar a " + nombre + "? Esta acción no se puede deshacer.";
                link.setAttribute("href", url);
                new bootstrap.Modal(modal).show();
            }
        });
    });

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

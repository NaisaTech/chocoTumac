// ChocoTumac – JS global

/*
* Este archivo contiene código JavaScript global para la aplicación ChocoTumac. Se encarga de funcionalidades comunes como el auto-cierre de alertas, validación de contraseña en tiempo real, confirmación de eliminación con modales y validación básica de formularios.
* Se ejecuta una vez que el DOM ha sido completamente cargado.
*/
document.addEventListener("DOMContentLoaded", function () {

    // --- Auto-cerrar alertas después de 5 s ---
    /* Se seleccionan todos los elementos con la clase "alert-auto" y se les asigna un temporizador para que, después de 5 segundos, se inicie una transición de opacidad para ocultarlos y luego se eliminen del DOM. Esto es útil para mensajes de éxito o error que solo necesitan mostrarse temporalmente. */
    document.querySelectorAll(".alert-auto").forEach(function (el) {
        setTimeout(function () {
            el.style.transition = "opacity .5s";
            el.style.opacity = "0";
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // --- Validación contraseña segura en tiempo real ---
    /* Se obtienen los elementos del input de contraseña y su feedback asociado. Si ambos existen, se agrega un listener al evento "input" del campo de contraseña para validar su contenido en tiempo real. La validación requiere que la contraseña tenga al menos 8 caracteres, incluya una letra mayúscula, una letra minúscula y un número. Según el resultado de la validación, se actualizan las clases del input para mostrar si es válido o no, y se muestra un mensaje de feedback adecuado. Si el campo está vacío, se eliminan las clases de validación y el mensaje de feedback. */
    const inputPass = document.getElementById("input-password");
    const feedbackPass = document.getElementById("feedback-password");
    /*
    * Si existen los elementos de contraseña y feedback, se agrega un listener al input de contraseña para validar su contenido en tiempo real. La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número. Se actualiza la clase del input y el mensaje de feedback según la validación. Si el campo está vacío, se eliminan las clases de validación y el mensaje.
    */
    if (inputPass && feedbackPass) {
        // Expresión regular para validar la contraseña: al menos 8 caracteres, una mayúscula, una minúscula y un número.
        inputPass.addEventListener("input", function () {
            const val = inputPass.value;
            const ok = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/.test(val);
            // Si el campo está vacío, se eliminan las clases de validación y el mensaje de feedback.
            if (val.length === 0) {
                inputPass.classList.remove("is-valid", "is-invalid");
                feedbackPass.textContent = "";
            } else if (ok) { // Si la contraseña es válida, se muestra un mensaje de éxito y se marca el input como válido.
                inputPass.classList.remove("is-invalid");
                inputPass.classList.add("is-valid");
                feedbackPass.className = "form-text text-success";
                feedbackPass.textContent = "✓ Contraseña segura";
            } else { // Si la contraseña no es válida, se muestra un mensaje de error y se marca el input como inválido.
                inputPass.classList.remove("is-valid");
                inputPass.classList.add("is-invalid");
                feedbackPass.className = "form-text text-danger";
                feedbackPass.textContent = "Debe tener 8+ caracteres, una mayúscula, una minúscula y un número.";
            }
        });
    }

    // --- Confirmación de eliminación con modal Bootstrap ---
    /* Se seleccionan todos los botones con la clase "btn-confirmar-eliminar" y se les agrega un listener al evento "click". Al hacer clic, se previene la acción por defecto y se obtiene la URL de eliminación y el nombre del registro a eliminar desde los atributos "data-url" y "data-nombre" del botón. Luego, se muestra un modal de confirmación utilizando Bootstrap, donde se actualiza el texto del modal para incluir el nombre del registro y se establece el enlace de confirmación con la URL de eliminación. Esto proporciona una capa adicional de seguridad para evitar eliminaciones accidentales. */
    document.querySelectorAll(".btn-confirmar-eliminar").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault(); // Prevenir la acción por defecto del enlace
            const url = btn.getAttribute("data-url");
            const nombre = btn.getAttribute("data-nombre") || "este registro";
            const modal = document.getElementById("modalConfirmarEliminar");
            const texto = document.getElementById("modalEliminarTexto");
            const link = document.getElementById("modalEliminarLink");
            if (modal && texto && link) { // Verificar que los elementos del modal existan
                texto.textContent = "¿Estás seguro de que deseas eliminar a " + nombre + "? Esta acción no se puede deshacer.";
                link.setAttribute("href", url);
                new bootstrap.Modal(modal).show();
            }
        });
    });

    // --- Validación login: solo bloquea envío si campos vacíos, sin colorear verde ---
    /* El formulario de login usa novalidate para evitar que Bootstrap pinte los campos
       en verde antes de que el servidor responda. Solo bloqueamos el envío si algún
       campo está vacío, sin agregar was-validated (que causaba el flash verde). */
    const loginForm = document.querySelector("form[action*='action=login']");
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            const email = loginForm.querySelector("[name='email']");
            const pass = loginForm.querySelector("[name='password']");
            let valid = true;
            [email, pass].forEach(function (input) {
                if (!input) return;
                if (!input.value.trim()) {
                    input.classList.add("is-invalid");
                    valid = false;
                } else {
                    input.classList.remove("is-invalid");
                }
            });
            if (!valid) e.preventDefault();
            // NUNCA agrega is-valid ni was-validated en el login
        });
        // Limpiar borde rojo al empezar a escribir
        loginForm.querySelectorAll("input").forEach(function (input) {
            input.addEventListener("input", function () {
                input.classList.remove("is-invalid");
            });
        });
    }

    // --- Validación básica frontend en formularios marcados ---
    /* Se seleccionan todos los formularios que tienen el atributo "data-validate" y se les agrega un listener al evento "submit". Al enviar el formulario, se verifica si es válido utilizando el método checkValidity() del formulario. Si el formulario no es válido, se previene el envío y se detiene la propagación del evento. Luego, se agrega la clase "was-validated" al formulario para activar las clases de validación de Bootstrap, lo que permite mostrar los mensajes de error correspondientes. Esto proporciona una validación básica en el frontend antes de enviar los datos al servidor. */
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
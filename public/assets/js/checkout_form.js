document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("checkoutForm");
  if(!form) return; // nothing to do if the checkout form is not present on the page
  const inputs = form.querySelectorAll("input[required], select[required]");

  inputs.forEach((input) => {
    input.addEventListener("blur", function () {
      validateField(this);
    });

    input.addEventListener("input", function () {
      if (this.classList.contains("is-invalid")) {
        validateField(this);
      }
    });

    // Validación especial para selects de ubicación
    if (input.tagName === 'SELECT' && ['state', 'province', 'city'].includes(input.id)) {
      input.addEventListener("change", function () {
        // Validar ubicación cuando cambie cualquier select
        if (window.ubigeoManager) {
          setTimeout(() => {
            window.ubigeoManager.validateLocationFields();
          }, 100);
        }
      });
    }
  });

  function validateField(field) {
    const value = field.value.trim();
    const type = field.type;

    field.classList.remove("is-valid", "is-invalid");

    const existingFeedback = field.parentNode.querySelector(
      ".invalid-feedback, .valid-feedback"
    );
    if (existingFeedback) {
      existingFeedback.remove();
    }

    let isValid = true;
    let message = "";

    if (field.hasAttribute("required") && value === "") {
      isValid = false;
      message = "Este campo es obligatorio";
    } else if (type === "email" && value !== "") {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(value)) {
        isValid = false;
        message = "Ingrese un email válido";
      }
    } else if (type === "tel" && value !== "") {
      const phonePattern = /^[0-9]{9,}$/;
      if (!phonePattern.test(value.replace(/\s/g, ""))) {
        isValid = false;
        message = "Ingrese un teléfono válido (mínimo 9 dígitos)";
      }
    }

    // Para selects de ubicación, usar validación especial
    if (field.tagName === 'SELECT' && ['state', 'province', 'city'].includes(field.id)) {
      // La validación de ubicación se manejará por separado
      return;
    }

    if (isValid && value !== "") {
      field.classList.add("is-valid");
    } else if (!isValid) {
      field.classList.add("is-invalid");

      const feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      feedback.textContent = message;
      field.parentNode.appendChild(feedback);
    }
  }

  const phoneInput = document.getElementById("phoneNumber");
  phoneInput.addEventListener("input", function (e) {
    let value = e.target.value.replace(/\D/g, "");
    let formattedValue = value.replace(/(\d{3})(\d{3})(\d{3})/, "$1 $2 $3");
    if (value.length > 9) {
      formattedValue = value
        .slice(0, 9)
        .replace(/(\d{3})(\d{3})(\d{3})/, "$1 $2 $3");
    }
    e.target.value = formattedValue;
  });

  const submitBtn = document.querySelector(".submit-btn");
  submitBtn.addEventListener("click", function (e) {
    let isFormValid = true;
    
    // Validar campos normales
    inputs.forEach((input) => {
      validateField(input);
      if (
        input.classList.contains("is-invalid") ||
        (input.hasAttribute("required") && input.value.trim() === "")
      ) {
        isFormValid = false;
      }
    });

    // Validar campos de ubicación específicamente
    if (window.ubigeoManager) {
      const locationValidation = window.ubigeoManager.validateLocationFields();
      if (!locationValidation.isValid) {
        isFormValid = false;
        // Mostrar errores de ubicación
        locationValidation.errors.forEach(error => {
          console.error(error);
        });
      }
    }

    if (!isFormValid) {
      e.preventDefault();
      const firstError = form.querySelector(".is-invalid");
      if (firstError) {
        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
        firstError.focus();
      }
    }
  });

  const steps = document.querySelectorAll(".step");
  let currentStep = 0;

  function updateProgress() {
    const filledFields = Array.from(inputs).filter(
      (input) =>
        input.value.trim() !== "" && !input.classList.contains("is-invalid")
    );
    const progress = filledFields.length / inputs.length;

    if (progress > 0.5 && currentStep === 0) {
      // Guard against missing .step elements
      if (steps && steps.length > 1 && steps[1] && steps[1].classList) {
        steps[1].classList.add("active");
        currentStep = 1;
      }
    }
  }

  inputs.forEach((input) => {
    input.addEventListener("input", updateProgress);
  });
});



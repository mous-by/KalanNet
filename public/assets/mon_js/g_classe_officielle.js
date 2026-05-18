const editModal = document.getElementById("editClasseOfficielleModal");
editModal.addEventListener("show.bs.modal", (event) => {
  const button = event.relatedTarget;

  const id = button.getAttribute("data-id");
  const nom = button.getAttribute("data-nom");
  const ordre = button.getAttribute("data-ordre");

  document.getElementById("editIdClasseOfficielle").value = id;
  document.getElementById("editNom").value = nom;

  const selectOrdre = document.getElementById("editOrdre");
  selectOrdre.value = ordre;
});
(() => {
  "use strict";
  const forms = document.querySelectorAll(".needs-validation");
  Array.from(forms).forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false
    );
  });
})();

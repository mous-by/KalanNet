

document.addEventListener("DOMContentLoaded", function () {
  var baseURL = document.getElementById("baseURL").value;
  var modal = document.getElementById("modalEmargement");
  if (modal) {
    modal.addEventListener("show.bs.modal", function (event) {
      var button = event.relatedTarget;
      var emargementId = button.getAttribute("data-id");
      var enseignant = button.getAttribute("data-enseignant");
      var classe = button.getAttribute("data-classe");
      var matiere = button.getAttribute("data-matiere");
      var lecon = button.getAttribute("data-lecon");
      var nombreHeures = button.getAttribute("data-nombre");
      var trimestre = button.getAttribute("data-trimestre");
      var anneeScolaire = button.getAttribute("data-annee");

      // Remplir les champs statiques
      modal.querySelector('select[name="id_enseignant"]').value = enseignant;
      modal.querySelector('select[name="id_classe"]').value = classe;
      modal.querySelector('select[name="id_matiere"]').value = matiere;
      modal.querySelector('input[name="nombre_heure"]').value = nombreHeures;
      modal.querySelector('select[name="id_trimestre"]').value = trimestre;
      modal.querySelector('select[name="id_anneeScolaire"]').value =
        anneeScolaire;

      // Charger les leçons dynamiquement
      var leconSelect = modal.querySelector('select[name="id_lecon"]');
      leconSelect.innerHTML = `<option value="">Chargement des leçons...</option>`;
      fetch(
        `${baseURL}/index.php?url=Enseignants/getLeconsByClasse&id_classe=${encodeURIComponent(
          classe
        )}&id_matiere=${encodeURIComponent(matiere)}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.error) {
            leconSelect.innerHTML = `<option disabled>${data.error}</option>`;
            return;
          }
          let options =
            '<option value="" disabled>Sélectionnez une leçon</option>';
          data.forEach((leconItem) => {
            const selected = leconItem.id_lecon == lecon ? "selected" : "";
            options += `<option value="${leconItem.id_lecon}" ${selected}>${leconItem.titre}</option>`;
          });
          leconSelect.innerHTML = options;
        })
        .catch((err) => {
          console.error("Erreur lors du chargement des leçons (catch):", err);
          leconSelect.innerHTML =
            "<option disabled>Erreur de chargement</option>";
        });

      // Mettre à jour l'action du formulaire
      var form = modal.querySelector("form");
      form.action = baseURL + "/Enseignants/update_emargement/" + emargementId;
    });
  }
});
  
  


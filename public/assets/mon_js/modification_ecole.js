

document.addEventListener("DOMContentLoaded", function () {
  var baseURL = document.getElementById("baseURL").value;
  var modal = document.getElementById("modalCenterEcole");

  // Fonction pour mettre à jour les champs visibles
  function updateFieldsVisibility(typeEcole) {
    // Masquer tous les champs spécifiques
    document.getElementById("modalComplexeFields").style.display = "none";
    document.getElementById("modalFondamentaleFields").style.display = "none";
    document.getElementById("modalSecondaireGeneraleFields").style.display =
      "none";
    document.getElementById("modalSecondaireTechniqueFields").style.display =
      "none";
    document.getElementById("modalCapField").style.display = "none";

    // Afficher les champs correspondants
    if (typeEcole === "Complexe Scolaire") {
      document.getElementById("modalComplexeFields").style.display = "block";
    } else if (
      typeEcole === "Fondamentale I" ||
      typeEcole === "Fondamentale II"
    ) {
      document.getElementById("modalFondamentaleFields").style.display =
        "block";
      document.getElementById("modalCapField").style.display = "block";
    } else if (typeEcole === "Secondaire Generale") {
      document.getElementById("modalSecondaireGeneraleFields").style.display =
        "block";
    } else if (typeEcole === "Secondaire Technique et Professionnel") {
      document.getElementById("modalSecondaireTechniqueFields").style.display =
        "block";
    }
  }

  modal.addEventListener("show.bs.modal", function (event) {
    var button = event.relatedTarget;
    var ecoleId = button.getAttribute("data-idecole");
    var typeEcole = button.getAttribute("data-typeecole");
    var nomEcole = button.getAttribute("data-nomecole") || "";
    var nomComplexe = button.getAttribute("data-nomcomplexe") || "";
    var nomFondamental = button.getAttribute("data-nomfondamental") || "";
    var nomLycee = button.getAttribute("data-nomlycee") || "";
    var nomProfessionnel = button.getAttribute("data-nomprofessionnel") || "";
    var cap = button.getAttribute("data-cap") || "";
    var academie = button.getAttribute("data-academie") || "";
    var notifier_sms = button.getAttribute("data-notifier_sms") || "0";

    // Champs du formulaire
    var inputId = modal.querySelector('input[name="idEcole"]');
    var selectType = modal.querySelector('select[name="typeEcole"]');
    var inputNomComplexe = modal.querySelector('input[name="nomComplexe"]');
    var inputNomFondamental = modal.querySelector(
      'input[name="nomFondamentales"]'
    );
    var inputNomLycee = modal.querySelector(
      'input[name="nomSecondaireGenerale"]'
    );
    var inputNomProfessionnel = modal.querySelector(
      'input[name="nomTechniqueProfessionnelle"]'
    );
    var inputNomEcoleFondamental = modal.querySelector(
      'input[name="nomEcoleFondamental"]'
    );
    var inputNomLyceeSimple = modal.querySelector('input[name="nomLycee"]');
    var inputNomEtablissement = modal.querySelector(
      'input[name="nomEtablissement"]'
    );
    var inputCap = modal.querySelector('input[name="cap"]');
    var inputAcademie = modal.querySelector('input[name="academie"]');
    var checkboxNotifier = modal.querySelector("#modal_notifier_sms");

    // Remplissage des valeurs
    inputId.value = ecoleId;
    selectType.value = typeEcole;
    checkboxNotifier.checked = notifier_sms === "1" || notifier_sms === 1;

    // Initialiser l'affichage des champs
    updateFieldsVisibility(typeEcole);

    // Remplir les champs selon le type
    if (typeEcole === "Complexe Scolaire") {
      inputNomComplexe.value = nomComplexe || nomEcole;
      inputNomFondamental.value = nomFondamental || "";
      inputNomLycee.value = nomLycee || "";
      inputNomProfessionnel.value = nomProfessionnel || "";
      inputCap.value = cap;
      inputAcademie.value = academie;
    } else if (
      typeEcole === "Fondamentale I" ||
      typeEcole === "Fondamentale II"
    ) {
      inputNomEcoleFondamental.value = nomFondamental || nomEcole;
      inputCap.value = cap;
      inputAcademie.value = academie;
    } else if (typeEcole === "Secondaire Generale") {
      inputNomLyceeSimple.value = nomLycee || nomEcole;
      inputAcademie.value = academie;
    } else if (typeEcole === "Secondaire Technique et Professionnel") {
      inputNomEtablissement.value = nomProfessionnel || nomEcole;
      inputAcademie.value = academie;
    }

    // Écouter les changements du select typeEcole
    selectType.addEventListener("change", function () {
      var selectedType = this.value;
      updateFieldsVisibility(selectedType);

      // Gestion spécifique du champ CAP pour Complexe Scolaire
      if (selectedType === "Complexe Scolaire") {
        inputNomFondamental.addEventListener("input", function () {
          document.getElementById("modalCapField").style.display =
            this.value.trim() !== "" ? "block" : "none";
        });
      }
    });

    // Mettre à jour l'action du formulaire
    var form = modal.querySelector("form");
    form.action = baseURL + "/Ecoles/update_ecoles/" + ecoleId;
  });
});
document.addEventListener("DOMContentLoaded", function () {
    const selectTypeEcole = document.getElementById("typeEcole");
    const capField = document.getElementById("capField");
    const complexeFields = document.getElementById("complexeFields");
    const fondamentaleFields = document.getElementById("fondamentaleFields");
    const secondaireGeneraleFields = document.getElementById("secondaireGeneraleFields");
    const secondaireTechniqueFields = document.getElementById("secondaireTechniqueFields");
    const nomFondamentalesInput = document.querySelector('input[name="nomFondamentales"]');

    if (!selectTypeEcole) return;

    function updateFields() {
        const value = selectTypeEcole.value;

        // Masquer tous les champs
        if (complexeFields) complexeFields.style.display = "none";
        if (fondamentaleFields) fondamentaleFields.style.display = "none";
        if (secondaireGeneraleFields) secondaireGeneraleFields.style.display = "none";
        if (secondaireTechniqueFields) secondaireTechniqueFields.style.display = "none";
        if (capField) capField.style.display = "none";

        // Afficher les champs en fonction du choix
        if (value === "Complexe Scolaire" && complexeFields) {
            complexeFields.style.display = "block";
            // Afficher CAP si le champ "Nom fondamentales" n'est pas vide
            if (nomFondamentalesInput && nomFondamentalesInput.value.trim() !== "") {
                capField.style.display = "block";
            }
            // Surveille la saisie dans "Nom fondamentales"
            nomFondamentalesInput && nomFondamentalesInput.addEventListener("input", function () {
                if (this.value.trim() !== "") {
                    capField.style.display = "block";
                } else {
                    capField.style.display = "none";
                }
            });
        } else if (value === "Fondamentale I" || value === "Fondamentale II") {
            if (fondamentaleFields) fondamentaleFields.style.display = "block";
            if (capField) capField.style.display = "block";
        } else if (value === "Secondaire Generale" && secondaireGeneraleFields) {
            secondaireGeneraleFields.style.display = "block";
        } else if (value === "Secondaire Technique et Professionnel" && secondaireTechniqueFields) {
            secondaireTechniqueFields.style.display = "block";
        }
    }

    selectTypeEcole.addEventListener("change", updateFields);

    // Appel initial pour l'état par défaut
    updateFields();
});
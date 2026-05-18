document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.formValidationExamples');
    
    if (form) {
        form.addEventListener('submit', function (event) {
            // Champs à valider
            const nomMatiere = document.getElementById('nom_matiere');
            const classe = document.getElementById('classe');
            const ordreEnseignement = document.getElementById('ordre_enseignement');

            // Réinitialiser les styles d'erreur et les messages précédents pour tous les champs
            [nomMatiere, classe, ordreEnseignement].forEach(field => {
                field.style.borderColor = '';
                const existingError = document.getElementById(field.id + '_error');
                if (existingError) {
                    existingError.remove();
                }
            });

            let isValid = true; // Définir un indicateur global de validation

            // Vérifier si le champ 'nom_matiere' est vide
            if (nomMatiere.value.trim() === '') {
                showError(nomMatiere, 'Le champ matière est obligatoire');
                isValid = false;
            }

            // Vérifier si le champ 'classe' est vide
            if (classe.value.trim() === '') {
                showError(classe, 'Le champ classe est obligatoire');
                isValid = false;
            }

            // Vérifier si le champ 'ordre_enseignement' est vide
            if (ordreEnseignement.value.trim() === '') {
                showError(ordreEnseignement, 'Le champ ordre enseignement est obligatoire');
                isValid = false;
            }

            // Empêcher l'envoi du formulaire si un des champs n'est pas valide
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Ajouter un écouteur d'événements 'input' pour réinitialiser les erreurs
        const fieldsToWatch = ['nom_matiere', 'classe', 'ordre_enseignement'];
        fieldsToWatch.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function () {
                    // Réinitialiser les styles d'erreur et les messages précédents
                    field.style.borderColor = '';
                    const existingError = document.getElementById(field.id + '_error');
                    if (existingError) {
                        existingError.remove();
                    }
                });
            }
        });
    }

    // Fonction pour afficher les erreurs
    function showError(field, message) {
        // Ajouter les styles d'erreur
        field.style.borderColor = '#dc3545'; // Couleur de bordure pour l'erreur

        // Créer et afficher un message d'erreur
        const errorMessage = document.createElement('div');
        errorMessage.id = field.id + '_error';
        errorMessage.style.color = '#dc3545'; // Couleur du texte pour l'erreur
        errorMessage.textContent = message;
        field.parentElement.appendChild(errorMessage);
    }
});

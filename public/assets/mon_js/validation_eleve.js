document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formValidationExamples'); // Correction de la sélection de l'ID

    if (form) {
        const fields = {
            nom_eleve: document.getElementById('nom_eleve'),
            date_naissance: document.getElementById('date_naissance'),
            lieu_naiss: document.getElementById('lieu_naiss'), // Assurez-vous que cet ID existe
            adresse_eleve: document.getElementById('adresse_eleve'),
            matricule: document.getElementById('matricule'),
            genre_eleve: document.querySelector('select[name="genre_eleve"]'), // Utilisation de querySelector pour sélectionner par nom
        };

        form.addEventListener('submit', function (event) {
            let hasError = false;

            // Réinitialiser les styles d'erreur et les messages précédents
            Object.keys(fields).forEach(function (key) {
                const field = fields[key];
                if (field) {
                    field.style.borderColor = '';
                    const existingError = document.getElementById(`${key}_error`);
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });

            // Vérifier si les champs sont vides et afficher les erreurs
            Object.keys(fields).forEach(function (key) {
                const field = fields[key];
                if (field) {
                    let isValid = field.tagName === 'SELECT' ? field.value !== '' : field.value.trim() !== '';

                    if (!isValid) {
                        // Ajouter les styles d'erreur
                        field.style.borderColor = '#dc3545'; // Couleur de bordure pour l'erreur

                        // Créer et afficher un message d'erreur
                        const errorMessage = document.createElement('div');
                        errorMessage.id = `${key}_error`;
                        errorMessage.style.color = '#dc3545'; // Couleur du texte pour l'erreur
                        errorMessage.textContent = `Le champ ${formatLabel(key)} est obligatoire`;
                        field.parentElement.appendChild(errorMessage);

                        // Marquer comme ayant une erreur
                        hasError = true;
                    }
                }
            });

            // Empêcher l'envoi du formulaire si des erreurs sont présentes
            if (hasError) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Ajouter un écouteur d'événements 'input' pour réinitialiser les erreurs
        Object.keys(fields).forEach(function (key) {
            const field = fields[key];
            if (field) {
                field.addEventListener('input', function () {
                    // Réinitialiser les styles d'erreur et les messages précédents
                    field.style.borderColor = '';
                    const existingError = document.getElementById(`${key}_error`);
                    if (existingError) {
                        existingError.remove();
                    }
                });
            }
        });
    }

    // Fonction pour formater les labels des champs
    function formatLabel(key) {
        switch (key) {
            case 'nom_eleve':
                return 'Nom & Prénom';
            case 'date_naissance':
                return 'Date de naissance';
            case 'lieu_naiss':
                return 'Lieu de naissance';
            case 'adresse_eleve':
                return "Adresse de l'élève";
            case 'matricule':
                return 'Matricule';
            case 'genre_eleve':
                return 'Genre de l\'élève';
            default:
                return key.replace(/_/g, ' ');
        }
    }
});

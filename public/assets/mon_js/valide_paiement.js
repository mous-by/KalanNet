document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formValidationExamples'); // Correction de la sélection de l'ID

    if (form) {
        const fields = {
            id_classe: document.getElementById('id_classe'),
            id_annee: document.getElementById('id_annee'),
            id_eleve: document.getElementById('id_eleve'), // Assurez-vous que cet ID existe
            id_trimestre: document.getElementById('id_trimestre'),
            date_paiement: document.getElementById('date_paiement'),
            parent: document.getElementById('parent'),
            telephone: document.getElementById('telephone'),
            // genre_eleve: document.querySelector('select[name="genre_eleve"]'), // Utilisation de querySelector pour sélectionner par nom
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
            case 'id_classe':
                return 'classe';
            case 'id_annee':
                return 'Annee';
            case 'id_eleve':
                return 'L eleve';
            case 'id_trimestre':
                return "trimesstre";
            case 'date_paiement':
                return 'date du paiement';
            case 'parent':
                return 'parent de l\'élève';
                case 'telephone':
                return 'telephone';
            default:
                return key.replace(/_/g, ' ');
        }
    }
});

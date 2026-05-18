document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.form');

    if (form) {
        const fields = {
            nom_prenom: document.getElementById('nom_prenom'),
            genre: document.getElementById('genre'),
            email: document.getElementById('email'),
            telephone: document.getElementById('telephone'),
            date_naissance: document.getElementById('date_naissance'),
            lieu_naissance: document.getElementById('lieu_naissance'),
            diplome: document.getElementById('diplome'),
            salaire: document.getElementById('salaire'),
            type_contrat: document.getElementById('type_contrat')
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
                    let isValid = true;

                    if (field.tagName === 'SELECT') {
                        isValid = field.value !== '';
                    } else {
                        isValid = field.value.trim() !== '';
                    }

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
            case 'nom_prenom':
                return 'Nom & Prénom';
            case 'genre':
                return 'Genre';
            case 'email':
                return 'Email';
            case 'telephone':
                return 'Téléphone';
            case 'date_naissance':
                return 'Date de naissance';
            case 'lieu_naissance':
                return 'Lieu de naissance';
            case 'diplome':
                return 'Diplôme';
            case 'salaire':
                return 'Salaire';
            case 'type_contrat':
                return 'Type de contrat';
            default:
                return key.replace(/_/g, ' ');
        }
    }
});

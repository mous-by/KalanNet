document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.form');
    if (form) {
        const fields = {
            enseignant: document.getElementById('enseignant'),
            classe: document.getElementById('classe'),
            matiere: document.getElementById('matiere'),
            chapitre: document.getElementById('chapitre'),
            titre_cours: document.getElementById('titre_cours'),
            nombre_heure: document.getElementById('nombre_heure'),
            trimestre: document.getElementById('trimestre'),
            annee_scolaire: document.getElementById('annee_scolaire'),
        };

        form.addEventListener('submit', function (event) {
            let hasError = false;

            // Réinitialiser toutes les erreurs
            resetErrors(fields);

            // Validation des champs
            Object.keys(fields).forEach(function (key) {
                if (!isValidField(fields[key])) {
                    displayError(fields[key], key);
                    hasError = true;
                }
            });

            if (hasError) {
                event.preventDefault();
            }
        });

        Object.keys(fields).forEach(function (key) {
            const eventType = fields[key].tagName === 'SELECT' ? 'change' : 'input';
            fields[key]?.addEventListener(eventType, function () {
                resetError(fields[key]);
            });
        });
    }
    function resetErrors(fields) {
        Object.keys(fields).forEach(key => resetError(fields[key]));
    }

    function resetError(field) {
        if (field) {
            field.classList.remove('is-invalid');
            const error = document.getElementById(`${field.id}_error`);
            if (error) {
                error.textContent = '';
            }
        }
    }

    function isValidField(field) {
        if (!field) return true;
        if (field.tagName === 'SELECT') {
            return field.value !== '' && field.value !== 'Sélectionnez un enseignant' && field.value !== 'Sélectionnez une classe';
        }
        return field.value.trim() !== '';
    }

    function displayError(field, key) {
        field.classList.add('is-invalid');
        const errorMessage = document.getElementById(`${field.id}_error`);
        errorMessage.textContent = `Le champ ${formatLabel(key)} est obligatoire`;
    }

    function formatLabel(key) {
        const labels = {
            enseignant: 'Enseignant',
            classe: 'Classe',
            matiere: 'Matière',
            chapitre: 'Chapitre',
            titre_cours: 'Titre du cours',
            nombre_heure: 'Nombre d\'heures',
            trimestre: 'Trimestre',
            annee_scolaire: 'Année scolaire',
        };
        return labels[key] || key.replace(/_/g, ' ');
    }
});


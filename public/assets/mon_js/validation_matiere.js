document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.needs-validatio');
    
    if (form) {
        form.addEventListener('submit', function (event) {
            const nomMatiere = document.getElementById('nom_matiere');

            // Réinitialiser les styles d'erreur et les messages précédents
            nomMatiere.style.borderColor = '';
            const existingError = document.getElementById('nom_matiere_error');
            if (existingError) {
                existingError.remove();
            }

            // Vérifier si le champ est vide
            if (nomMatiere.value.trim() === '') {
                // Ajouter les styles d'erreur
                nomMatiere.style.borderColor = '#dc3545'; // Couleur de bordure pour l'erreur

                // Créer et afficher un message d'erreur
                const errorMessage = document.createElement('div');
                errorMessage.id = 'nom_matiere_error';
                errorMessage.style.color = '#dc3545'; // Couleur du texte pour l'erreur
                errorMessage.textContent = 'Le champ matière est obligatoire';
                nomMatiere.parentElement.appendChild(errorMessage);

                // Empêcher l'envoi du formulaire
                event.preventDefault();
                event.stopPropagation();
            }
        });

        // Ajouter un écouteur d'événements 'input' pour réinitialiser les erreurs
        const nomMatiere = document.getElementById('nom_matiere');
        if (nomMatiere) {
            nomMatiere.addEventListener('input', function () {
                // Réinitialiser les styles d'erreur et les messages précédents
                nomMatiere.style.borderColor = '';
                const existingError = document.getElementById('nom_matiere_error');
                if (existingError) {
                    existingError.remove();
                }
            });
        }
    }
});

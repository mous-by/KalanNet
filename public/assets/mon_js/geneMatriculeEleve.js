document.addEventListener("DOMContentLoaded", function() {
    const genreInput = document.querySelector('select[name="genre_eleve"]');
    const matriculeInput = document.getElementById('matricule');

    function generateMatricule() {
        const genre = genreInput.value.toUpperCase();
        if (!genre) {
            matriculeInput.value = '';
            return;
        }

        // Format identique à PHP : MT + année + 1ère lettre genre + 6 derniers caractères uniqid
        // En JS on simule uniqid par un identifiant aléatoire hex
        const year = new Date().getFullYear();
        const uniqid = Math.random().toString(16).substr(2, 6).toUpperCase();

        const matricule = `MT${year}ML${genre.charAt(0)}${uniqid}`;
        matriculeInput.value = matricule;
    }

    genreInput.addEventListener('change', generateMatricule);

    // Optionnel : si tu as un champ lieu de naissance, tu peux intégrer une partie comme dans l'exemple précédent
});

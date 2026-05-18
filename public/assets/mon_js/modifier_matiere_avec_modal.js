modal.addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var matiereId = button.getAttribute('data-id');
    var nomMatiere = button.getAttribute('data-nom');
    var ordres = button.getAttribute('data-ordres') || '';
    var ordresArray = ordres.split(',').map(s => s.trim());

    // Remplir le champ nom
    var inputNomMatiere = modal.querySelector('input[name="nom_matiere"]');
    inputNomMatiere.value = nomMatiere;

    // Mettre à jour l'action du formulaire
    var form = modal.querySelector('form');
    form.action = baseURL +'/Matieres/update/' + matiereId;

    // Réinitialiser les cases à cocher
    var checkboxes = modal.querySelectorAll('input[name="ordre_enseignement[]"]');
    checkboxes.forEach(cb => {
        cb.checked = ordresArray.includes(cb.value);
    });
});
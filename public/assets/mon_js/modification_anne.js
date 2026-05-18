document.addEventListener('DOMContentLoaded', function() {
    var baseURL = document.getElementById('baseURL').value;
    var modal = document.getElementById('modalCenterAnnee');

    modal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var anneeId = button.getAttribute('data-id_anneeScolaire');
        var nomAnne = button.getAttribute('data-annee');
        var dateDebut = button.getAttribute('data-date_debut');
        var dateFin = button.getAttribute('data-date_fin');

        // Remplir les champs de la modale avec les valeurs
        var inputId = modal.querySelector('input[name="anneScolaireId"]');
        var inputAnne = modal.querySelector('input[name="anneScolaire"]');
        var inputDebut = modal.querySelector('input[name="dateDebut"]'); // Changed to input
        var inputFin = modal.querySelector('input[name="dateFin"]'); // Changed to input

        inputId.value = anneeId;
        inputAnne.value = nomAnne;
        inputDebut.value = dateDebut;
        inputFin.value = dateFin;

        // Mettre à jour l'action du formulaire avec l'ID de la matière
        var form = modal.querySelector('form');
        form.action = baseURL +'/Annees_scolaires/update_annes/' + anneeId;
    });
});

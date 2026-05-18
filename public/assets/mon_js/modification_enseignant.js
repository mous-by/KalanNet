$(document).ready(function() {
    // Fonction pour gérer l'affichage des champs en fonction du type de contrat
    function toggleContractFields() {
        var contractType = $('#type_contrat').val();
        $('#contractFields').hide(); 
        $('#salaire_field').hide(); 
        $('#duree_contrat_field').hide(); 
        $('#nombre_heures_field').hide(); 
        $('#prix_heure_field').hide(); 
        if (contractType === 'CDI') {
            $('#contractFields').show(); 
            $('#salaire_field').show(); 
        } else if (contractType === 'CDD') {
            $('#contractFields').show(); 
            $('#duree_contrat_field').show(); 
            $('#salaire_field').show(); 
        } else if (contractType === 'VCT') {
            $('#contractFields').show(); 
            $('#nombre_heures_field').show(); 
            $('#prix_heure_field').show(); 
        }
    }

    // Appeler la fonction lors de la sélection du type de contrat
    $('#type_contrat').change(toggleContractFields);

    // Appeler la fonction pour gérer l'état initial lors du chargement
    toggleContractFields();
});
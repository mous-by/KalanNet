//  les champs select de la liste eleve
    $(document).ready(function() {
        $('#id_classe, #id_annee').change(function() {
            var id_classe = $('#id_classe').val();
            var id_annee = $('#id_annee').val();
            // alert(id_classe + id_annee)
            // Vérifie si les deux ID sont sélectionnés
            if (id_classe && id_annee) {
                $.ajax({
                    url: "<?= ROOT ?>/Eleves/selectliste_eleve_Ajax",
                    data: {
                        id_classe: id_classe,
                        id_annee: id_annee
                    },
                    method: 'POST',
                    dataType: 'JSON',
                    success: function(data) {
                        console.log(data); // Log la réponse du serveur
                        if (data.tbody) {
                            $('#tableEleve').html(data
                                .tbody); // Remplace le contenu du tableau
                        } else {
                            alert("Aucune donnée reçue.");
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Une erreur est survenue lors du chargement des élèves.");
                        console.error("Erreur Ajax : ", error);
                        console.error("Statut : ", status);
                        console.log("XHR : ", xhr.responseText); // Log la réponse XHR
                    }
                });
            }
        });

        // Suppression des lignes du tableau
        $(document).on('click', '.remove', function(e) {
            e.preventDefault();
            $(this).closest("tr").remove();
        });
    });

// la selection de la liste du pdf 
    document.getElementById('bulkPaymentForm').addEventListener('submit', function(e) {
        const classe = document.getElementById('id_classe').value;
        const annee = document.getElementById('id_annee').value;

        if (!classe || classe === '1') {
            alert('Veuillez sélectionner une classe.');
            e.preventDefault();
            return;
        }

        if (!annee || annee === '2024') {
            alert('Veuillez sélectionner une année scolaire.');
            e.preventDefault();
            return;
        }

        document.getElementById('selectedClasse').value = classe;
        document.getElementById('selectedAnnee').value = annee;
    });


// Importation en excel
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('bulkForm');
        const selectClasse = document.getElementById('id_classe');
        const selectAnnee = document.getElementById('id_annee');
        const hiddenClasse = document.getElementById('Classe');
        const hiddenAnnee = document.getElementById('Annee');

        form.addEventListener('submit', function(e) {
            hiddenClasse.value = selectClasse.value;
            hiddenAnnee.value = selectAnnee.value;

            // Optionnel : empêcher l'envoi si un champ n'est pas sélectionné
            if (!hiddenClasse.value || !hiddenAnnee.value) {
                e.preventDefault();
                alert("Veuillez sélectionner la classe et l'année scolaire.");
            }
        });
    });

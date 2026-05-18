 const ROOT = "<?= ROOT ?>";
        $(document).ready(function() {
            $('#id_classe').change(function() {
                const selectedOption = $(this).find('option:selected');
                const ordre = selectedOption.data('ordre');

                if (ordre === 'fondamentale1') {
                    $('#id_periode').closest('.col-md-2').hide(); // cacher le champ période
                    $('#mois_container').show(); // afficher mois
                } else {
                    $('#id_periode').closest('.col-md-2').show(); // afficher période
                    $('#mois_container').hide(); // cacher mois
                }
            });
        });
   
        $(document).ready(function() {
            $('#id_classe, #id_annee').change(function() {
                var id_classe = $('#id_classe').val();
                var id_annee = $('#id_annee').val();
                var id_matiere = $('#id_matiere').val(); // à ajouter
                var id_trimestre = $('#id_trimestre').val(); // à ajouter
                var id_note = $('#id_note').val();
                // Vérifie si les deux ID sont sélectionnés
                if (id_classe && id_annee) {
                    $.ajax({
                        url: "<?= ROOT ?>/Evaluations/selectEvaluation_Ajax",
                        data: {
                            id_classe: id_classe,
                            id_annee: id_annee
                        },
                        method: 'POST',
                        dataType: 'JSON',
                        success: function(data) {
                            console.log(data); // Log la réponse du serveur
                            if (data.tbody) {
                                $('#evaluation').html(data.tbody); // Remplace le contenu du tableau
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
   
        $(document).ready(function() {
            $('#id_classe').change(function() {
                var id_classe = $(this).val();

                if (id_classe) {
                    $.ajax({
                        url: "<?= ROOT ?>/Evaluations/selectMatiereByClasse",
                        method: 'POST',
                        data: {
                            id_classe: id_classe
                        },
                        dataType: 'JSON',
                        success: function(data) {
                            if (data.matiere) {
                                var options = '<option value="">Sélectionnez une matière</option>';
                                $.each(data.matiere, function(index, value) {
                                    // Utilisez l'ID de la matière comme valeur et affichez le nom
                                    options += '<option value="' + value.id_matiere + '">' + value.nom_matiere + '</option>';
                                });
                                $('#id_matiere').html(options);
                            } else {
                                alert("Aucune matière trouvée.");
                            }
                        },
                        error: function(xhr, status, error) {
                            alert("Une erreur est survenue lors du chargement des matières.");
                            console.error("Erreur Ajax : ", error);
                            console.error("Statut : ", status);
                            console.log("XHR : ", xhr.responseText);
                        }
                    });
                } else {
                    $('#id_matiere').html('<option value="">Sélectionnez une matière</option>');
                }
            });

        });
   
        $(document).ready(function() {
            $('#formValidationExamples').submit(function() {
                // creation des champs
                var libeller = $('.libeller').val();
                var date_evaluation = $('.date_evaluation').val();
                var heure_debut = $('.heure_debut').val();
                var heure_fin = $('.heure_fin').val();
                //creation des tables
                var idEleve = [];

                var id_classe = [];
                var id_matiere = [];
                var id_annee_scolaire = [];
                var id_trimestre = [];
                var id_note = [];
                var id_enseignant = [];
                var note = [];
                // partie fonction
                $('.idEleve').each(function() {
                    idEleve.push($(this).val());
                });

                $('.id_classe').each(function() {
                    id_classe.push($(this).val());
                });
                $('.id_matiere').each(function() {
                    id_matiere.push($(this).val());
                });
                $('.id_annee_scolaire').each(function() {
                    id_annee_scolaire.push($(this).val());
                });
                $('.id_trimestre').each(function() {
                    id_trimestre.push($(this).val());
                });
                $('.id_note').each(function() {
                    id_note.push($(this).val());
                });
                $('.id_enseignant').each(function() {
                    id_enseignants.push($(this).val());
                });
                $('.note').each(function() {
                    note.push($(this).val());
                });
                // Vérification des notes (0-20)
                var notesValides = true;
                $('.note').each(function() {
                    var valeur = parseFloat($(this).val());
                    if (isNaN(valeur) || valeur < 0 || valeur > 20) {
                        notesValides = false;
                        alert("Les notes doivent être entre 0 et 20.");
                        return false; // Arrête la boucle
                    }
                });

                if (!notesValides) return false;

                $.ajax({
                    url: "<?= ROOT ?>/Evaluations/enregistrement_evaluation",
                    data: {
                        libeller: libeller,
                        date_evaluation: date_evaluation,
                        heure_debut: heure_debut,
                        heure_fin: heure_fin,
                        idEleve: idEleve,

                        id_classe: id_classe,
                        id_matiere: id_matiere,
                        id_annee_scolaire: id_annee_scolaire,
                        id_trimestre: id_trimestre,
                        id_note: id_note,
                        id_enseignant: id_enseignant,
                        note: note
                    },
                    dataType: 'JSON',
                    success: function(data) {
                        swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.Classes,
                            showConfirmButton: false,
                            timer: 4000
                        });
                    }
                })

            })
        })
  
        $(document).ready(function() {
            var $noteSelect = $('#id_note');
            var $originalNoteOptions = $noteSelect.html();

            $('#id_classe').change(function() {
                var selectedOrdre = $('#id_classe option:selected').data('ordre');
                $noteSelect.html($originalNoteOptions); // Reset

                if (selectedOrdre === 'fondamentale1') {
                    // Afficher uniquement NT10
                    $noteSelect.find('option').each(function() {
                        if ($(this).data('code') !== 'NT10' && $(this).val() !== '') {
                            $(this).remove();
                        }
                    });
                } else {
                    // Afficher tout sauf NT10
                    $noteSelect.find('option').each(function() {
                        if ($(this).data('code') === 'NT10') {
                            $(this).remove();
                        }
                    });
                }
            });
        });
    
       
        
        // Validation en temps réel pour que l'heure de fin ne soit pas antérieure à l'heure de début avec SweetAlert
        if (typeof Swal === 'undefined') {
            const script = document.createElement('script');
            script.src = "https://cdn.jsdelivr.net/npm/sweetalert2@11";
            script.onload = initValidation;
            document.head.appendChild(script);
        } else {
            initValidation();
        }

        function initValidation() {
            // Heure validation
            const heureDebutInput = document.getElementById('heure_debut');
            const heureFinInput = document.getElementById('heure_fin');

            function verifierHeures() {
                const heureDebut = heureDebutInput.value;
                const heureFin = heureFinInput.value;
                if (heureDebut && heureFin && heureFin < heureDebut) {
                    heureFinInput.value = '';
                    Swal.fire({
                        icon: 'info',
                        title: 'Attention',
                        text: "L'heure de fin ne doit pas être antérieure à l'heure de début.",
                        confirmButtonColor: '#3085d6'
                    });
                }
            }

            heureDebutInput.addEventListener('input', verifierHeures);
            heureFinInput.addEventListener('input', verifierHeures);
            heureDebutInput.addEventListener('blur', verifierHeures);
            heureFinInput.addEventListener('blur', verifierHeures);

            // Libellé validation
            const libelleInput = document.getElementById('libelle');
            libelleInput.addEventListener('blur', function() {
                const pattern = /^[A-Za-zÀ-ÿ\s]+$/;
                if (libelleInput.value && !pattern.test(libelleInput.value)) {
                    libelleInput.value = '';
                    Swal.fire({
                        icon: 'info',
                        title: 'Attention',
                        text: "Le libellé doit contenir uniquement des lettres.",
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
   
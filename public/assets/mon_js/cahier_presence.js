// function addCheckboxListeners() {
//   const allSelect = document.getElementById("allSelect");
//   const bulkActionBtn = document.getElementById("bulkActionBtn");
//   const selectedIdsInput = document.getElementById("selectedIds");

//   function toggleBulkButtons() {
//     const checkedBoxes = document.querySelectorAll(".rowCheckbox:checked");
//     const hasSelection = checkedBoxes.length > 0;

//     if (bulkActionBtn) {
//       bulkActionBtn.style.display = hasSelection ? "inline-block" : "none";
//     }

//     if (selectedIdsInput) {
//       const selectedIds = Array.from(checkedBoxes)
//         .map((cb) => cb.dataset.id)
//         .join(",");
//       selectedIdsInput.value = selectedIds;
//     }
//   }

//   if (allSelect) {
//     allSelect.addEventListener("change", function () {
//       document.querySelectorAll(".rowCheckbox").forEach((cb) => {
//         if (cb.offsetParent !== null) cb.checked = allSelect.checked;
//       });
//       toggleBulkButtons();
//     });
//   }

//   document.querySelectorAll(".rowCheckbox").forEach((cb) => {
//     cb.addEventListener("change", toggleBulkButtons);
//   });
// }

document.addEventListener("DOMContentLoaded", function () {
  // Initialisation de DataTable
  // const table = $("#example").DataTable({
  //   language: {
  //     url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json",
  //   },
  //   columnDefs: [
  //     { orderable: false, targets: [0, 9] },
  //     { searchable: false, targets: [0, 4, 5, 6, 7, 8, 9] },
  //   ],
  // });

  // Gestion des filtres - CORRECTION COMPLÈTE
  // const moisFilter = document.getElementById("moisFilter");
  // const enseignantFilter = document.getElementById("enseignantFilter");
  // const classeFilter = document.getElementById("classeFilter");

  // Fonction pour appliquer tous les filtres
  // function applyFilters() {
  //   table.draw();
  // }

  // Filtre par mois (recherche dans la colonne Date)
  // if (moisFilter) {
  //   moisFilter.addEventListener("change", function () {
  //     const mois = this.value;
  //     table.column(3).search(mois).draw();
  //     // Synchronisation mois filtre/paiement
  //     const moisFilter = document.getElementById("moisFilter");
  //     const moisPaiementInput = document.getElementById("moisPaiementInput");

  //     if (moisFilter && moisPaiementInput) {
  //       // Initialiser la valeur
  //       moisPaiementInput.value = moisFilter.value;

  //       moisFilter.addEventListener("change", function () {
  //         const mois = this.value;
  //         if (!mois) return;

  //         // Mettre à jour le champ caché
  //         moisPaiementInput.value = mois;

  //         // Filtrer le tableau
  //         table.column(3).search(mois).draw();
  //       });
  //     }

  //     // Initialiser les écouteurs de cases à cocher
  //     addCheckboxListeners();
  //   });
  // }

  // // Filtre par enseignant (recherche exacte dans la colonne 1)
  // if (enseignantFilter) {
  //   enseignantFilter.addEventListener("change", function () {
  //     const enseignantId = this.value;
  //     if (enseignantId) {
  //       table
  //         .column(1)
  //         .search("^" + enseignantId + "$", true, false)
  //         .draw();
  //     } else {
  //       table.column(1).search("").draw();
  //     }
  //   });
  // }

  // Filtre par classe (recherche exacte dans la colonne 2)
  // if (classeFilter) {
  //   classeFilter.addEventListener("change", function () {
  //     const classeId = this.value;
  //     if (classeId) {
  //       table
  //         .column(2)
  //         .search("^" + classeId + "$", true, false)
  //         .draw();
  //     } else {
  //       table.column(2).search("").draw();
  //     }
  //   });
  // }

  // // Écouteurs d'événements pour les filtres
  // if (moisFilter) {
  //   moisFilter.addEventListener("change", applyFilters);
  // }

  // if (enseignantFilter) {
  //   enseignantFilter.addEventListener("change", applyFilters);
  // }

  // if (classeFilter) {
  //   classeFilter.addEventListener("change", applyFilters);
  // }

  // Gestion des cases à cocher
  // $("#allSelect").change(function () {
  //   $(".rowCheckbox").prop("checked", this.checked);
  //   toggleBulkActions();
  // });

  // $(".rowCheckbox").change(function () {
  //   if (!$(".rowCheckbox").length) return;
  //   $("#allSelect").prop(
  //     "checked",
  //     $(".rowCheckbox:checked").length === $(".rowCheckbox").length
  //   );
  //   toggleBulkActions();
  // });

  // function toggleBulkActions() {
  //   const hasSelection = $(".rowCheckbox:checked").length > 0;
  //   $("#bulkActionBtn").toggle(hasSelection);

  //   // Mise à jour des IDs sélectionnés
  //   const selectedIds = $(".rowCheckbox:checked")
  //     .map(function () {
  //       return $(this).data("id");
  //     })
  //     .get()
  //     .join(",");
  //   $("#selectedIds").val(selectedIds);
  // }

  // Options pour le select durée
  const dureeOptions = [
    { value: "", label: "-- Sélectionnez --" },
    { value: "0.1667", label: "10 minutes" },
    { value: "0.25", label: "15 minutes" },
    { value: "0.50", label: "30 minutes" },
    { value: "0.75", label: "45 minutes" },
    { value: "1.00", label: "1 heure" },
    { value: "1.25", label: "1h15" },
    { value: "1.50", label: "1h30" },
    { value: "1.75", label: "1h45" },
    { value: "2.00", label: "2 heures" },
    { value: "3.00", label: "3 heures" },
    { value: "4.00", label: "4 heures" },
  ];

  function getDureeSelectHtml(name, selectedValue = "") {
    let html = `<select name="${name}" class="form-control" required>`;
    dureeOptions.forEach((opt) => {
      html += `<option value="${opt.value}"${
        opt.value == selectedValue ? " selected" : ""
      }>${opt.label}</option>`;
    });
    html += `</select>`;
    return html;
  }

  // Gestion des leçons (ajout/suppression)
  // Pour le modal d'ajout
  $("#add-lecon").click(function () {
    const container = $("#lecons-container");
    const newIndex = container.find(".lecon-row").length;

    const newRow = $(`
                            <div class="lecon-row row mb-3 align-items-end">
                                <div class="col-md-5">
                                    <input type="text" name="lecons[${newIndex}][titre]" class="form-control" placeholder="Titre de la leçon" required>
                                </div>
                                <div class="col-md-3">
                                    ${getDureeSelectHtml(
                                      `lecons[${newIndex}][heures]`
                                    )}
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="lecons[${newIndex}][progression]" class="form-control" placeholder="0-100" min="0" max="100" value="0">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger remove-lecon">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `);

    container.append(newRow);

    // Activer le bouton de suppression sur la première ligne s'il y a plusieurs lignes
    if (container.find(".lecon-row").length > 1) {
      container
        .find(".lecon-row")
        .first()
        .find(".remove-lecon")
        .prop("disabled", false);
    }
  });

  $("#lecons-container").on("click", ".remove-lecon", function () {
    $(this).closest(".lecon-row").remove();

    // Recalculer les index et désactiver le bouton de suppression s'il ne reste qu'une ligne
    const rows = $("#lecons-container").find(".lecon-row");
    if (rows.length === 1) {
      rows.first().find(".remove-lecon").prop("disabled", true);
    }
  });

  // Pour le modal d'édition - CORRECTION
  $("#modalEditPresence").on("show.bs.modal", function (event) {
    const button = $(event.relatedTarget);
    const idPresence = button.data("id");

    $("#edit_id_presence").val(idPresence);
    $("#edit_enseignant").val(button.data("enseignant"));
    $("#edit_classe").val(button.data("classe"));
    const dateStr = button.data("date");
    if (dateStr) {
      const dt = new Date(dateStr.replace(" ", "T"));
      const formatted = dt.toISOString().slice(0, 16);
      $("#edit_date_presence").val(formatted);
    }
    // $('#edit_date_presence').val(button.data('date').split(' ')[0]);
    $("#edit_trimestre").val(button.data("trimestre"));
    $("#edit_annee").val(button.data("annee"));

    $.get(
      `${ROOT}/Enseignants/get_lecons/${idPresence}`,
      function (response) {
        const container = $("#edit-lecons-container");
        container.empty();

        // Vérifier le nouveau format de réponse
        if (response.success && Array.isArray(response.data)) {
          response.data.forEach((lecon, index) => {
            container.append(`
                                        <div class="lecon-row row mb-3 align-items-end" data-id="${
                                          lecon.id_lecon_presence
                                        }">
                                            <input type="hidden" name="lecons[${index}][id]" value="${
              lecon.id_lecon_presence
            }">
                                            <div class="col-md-5">
                                                <input type="text" name="lecons[${index}][titre]" class="form-control" 
                                                    value="${
                                                      lecon.titre
                                                    }" required>
                                            </div>
                                            <div class="col-md-3">
                                                ${getDureeSelectHtml(
                                                  `lecons[${index}][heures]`,
                                                  lecon.nombre_heure
                                                )}
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" name="lecons[${index}][progression]" class="form-control" 
                                                    value="${
                                                      lecon.progression
                                                    }" min="0" max="100">
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger remove-edit-lecon">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    `);
          });
        } else {
          container.append(
            '<div class="alert alert-warning">Aucune leçon trouvée</div>'
          );
        }

        if (container.find(".lecon-row").length === 1) {
          container
            .find(".lecon-row")
            .first()
            .find(".remove-edit-lecon")
            .prop("disabled", true);
        }
      }
    ).fail(function () {
      Swal.fire("Erreur!", "Erreur de chargement des leçons", "error");
    });
  });

  // Gestion de la suppression des leçons dans l'édition
  $("#edit-lecons-container").on("click", ".remove-edit-lecon", function () {
    const row = $(this).closest(".lecon-row");
    const leconId = row.data("id");

    if (leconId) {
      Swal.fire({
        title: "Êtes-vous sûr?",
        text: "Vous ne pourrez pas annuler cette action!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, supprimer!",
        cancelButtonText: "Annuler",
      }).then((result) => {
        if (result.isConfirmed) {
          $.post(
            `${ROOT}/Enseignants/delete_lecon/${leconId}`,
            function (response) {
              if (response.success) {
                row.remove();
                Swal.fire("Supprimé!", "La leçon a été supprimée.", "success");

                const rows = $("#edit-lecons-container").find(".lecon-row");
                if (rows.length === 1) {
                  rows
                    .first()
                    .find(".remove-edit-lecon")
                    .prop("disabled", true);
                }
              } else {
                Swal.fire(
                  "Erreur!",
                  response.message || "Erreur lors de la suppression",
                  "error"
                );
              }
            }
          ).fail(function () {
            Swal.fire(
              "Erreur!",
              "Erreur de communication avec le serveur",
              "error"
            );
          });
        }
      });
    } else {
      row.remove();
      const rows = $("#edit-lecons-container").find(".lecon-row");
      if (rows.length === 1) {
        rows.first().find(".remove-edit-lecon").prop("disabled", true);
      }
    }
  });

  $("#add-edit-lecon").click(function () {
    const container = $("#edit-lecons-container");
    const newIndex = container.find(".lecon-row").length;

    container.append(`
        <div class="lecon-row row mb-3 align-items-end">
            <div class="col-md-5">
                <input type="text" name="lecons[${newIndex}][titre]" class="form-control" placeholder="Titre de la leçon" required>
            </div>
            <div class="col-md-3">
                ${getDureeSelectHtml(`lecons[${newIndex}][heures]`)}
            </div>
            <div class="col-md-3">
                <input type="number" name="lecons[${newIndex}][progression]" class="form-control" placeholder="0-100" min="0" max="100" value="0">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger remove-edit-lecon">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `);

    if (container.find(".lecon-row").length > 1) {
      container
        .find(".lecon-row")
        .first()
        .find(".remove-edit-lecon")
        .prop("disabled", false);
    }
  });

  // Gestion de la suppression des présences
  $(".btn-supprimer").click(function (e) {
    e.preventDefault();
    const idPresence = $(this).data("id");

    Swal.fire({
      title: "Êtes-vous sûr?",
      text: "Vous ne pourrez pas annuler cette action!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Oui, supprimer!",
      cancelButtonText: "Annuler",
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = `${ROOT}/Enseignants/delete_presence/${idPresence}`;
      }
    });
  });

  // Gestion de la validation des présences
// $(".btn-valider").click(function (e) {
//     e.preventDefault();
//     const idPresence = $(this).data("id");
//     window.location.href =  `${ROOT}/Enseignants/valider_presence/${idPresence}`;
// });

  $("#modalLeconsPresence").on("show.bs.modal", function (event) {
    const button = $(event.relatedTarget);
    const idPresence = button.data("id");
    const modal = $(this);
    // Stocker l'ID pour la validation
    modal.data("idPresence", idPresence);
    $.get(
      `${ROOT}/Enseignants/get_lecons/${idPresence}`,
      function (lecons) {
        const tbody = $("#lecons-content");
        tbody.empty();

        if (
          lecons.success &&
          Array.isArray(lecons.data) &&
          lecons.data.length > 0
        ) {
          lecons.data.forEach((lecon) => {
            tbody.append(`
                                        <tr>
                                            <td>${lecon.titre || ""}</td>
                                            <td>${lecon.nombre_heure || 0}</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar ${
                                                      lecon.progression == 100
                                                        ? "bg-success"
                                                        : lecon.progression > 0
                                                        ? "bg-primary"
                                                        : "bg-danger"
                                                    }" 
                                                        role="progressbar" style="width: ${
                                                          lecon.progression || 0
                                                        }%;" 
                                                        aria-valuenow="${
                                                          lecon.progression || 0
                                                        }" aria-valuemin="0" aria-valuemax="100">
                                                        ${
                                                          lecon.progression || 0
                                                        }%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    `);
          });
        } else {
          tbody.append('<tr><td colspan="3">Aucune leçon trouvée</td></tr>');
        }
      }
    ).fail(function () {
      Swal.fire("Erreur!", "Erreur de chargement des leçons", "error");
    });
  });

const bulkBtn = document.getElementById("bulkActionBtn");
const form = document.getElementById("bulkPaymentForm");
const typeContratHidden = document.getElementById("typeContratHidden");

// if (bulkBtn) {
//   bulkBtn.addEventListener("click", function (e) {
//     e.preventDefault();

//     // Vérifier qu'au moins un enseignant est sélectionné
//     const selectedIds = $(".rowCheckbox:checked")
//       .map(function () {
//         return $(this).data("id");
//       })
//       .get();

//     if (selectedIds.length === 0) {
//       Swal.fire(
//         "Aucune sélection",
//         "Veuillez sélectionner au moins un enseignant.",
//         "warning"
//       );
//       return;
//     }

//     Swal.fire({
//       title: "Type de paiement",
//       text: "Le paiement est-il pour les enseignants vacataires (VCT) ou contractuels (CDI/CDD) ?",
//       icon: "question",
//       showCancelButton: true,
//       confirmButtonText: "Vacataires (VCT)",
//       cancelButtonText: "Contractuels (CDI/CDD)",
//       reverseButtons: true,
//     }).then((result) => {
//       if (result.isConfirmed) {
//         typeContratHidden.value = "VCT";
//         form.submit();
//       } else if (result.dismiss === Swal.DismissReason.cancel) {
//         typeContratHidden.value = "CDI/CDD";
//         form.submit();
//       }
//     });
//   });
// }
});

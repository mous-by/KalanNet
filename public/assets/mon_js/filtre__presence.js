

document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM chargé - Initialisation filtre présence");

  // Récupération des éléments avec vérification de null
  const mois = document.getElementById("moisFilter");
  const enseignant = document.getElementById("enseignantFilter");
  const classe = document.getElementById("classeFilter");
  const tableBody = document.querySelector("#example tbody");

  // Vérifier que tableBody existe
  if (!tableBody) {
    console.error("Table body not found");
    return;
  }

  // Récupération des données utilisateur
  const userRole =
    document.body.dataset.role || (isEnseignant ? "enseignant" : "admin");
  const userId = document.body.dataset.userid || "0";
  const enseignantId = document.body.dataset.enseignantid;

  console.log("Données utilisateur:", { userRole, userId, enseignantId });

  function afficherMessage(msg) {
    tableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">${msg}</td></tr>`;
  }

  function formatDateTime(dateStr) {
    if (!dateStr) return "";
    try {
      const d = new Date(dateStr);
      return (
        d.toLocaleDateString("fr-FR") +
        " à " +
        d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" })
      );
    } catch (e) {
      console.error("Erreur format date:", e);
      return dateStr;
    }
  }

  function formatHeures(heures) {
    if (!heures) return "0h";
    const heuresNum = parseFloat(heures);
    const heuresInt = Math.floor(heuresNum);
    const minutes = Math.round((heuresNum - heuresInt) * 60);
    if (minutes === 0) return `${heuresInt}h`;
    if (heuresInt === 0) return `${minutes}min`;
    return `${heuresInt}h ${minutes}min`;
  }

  function fetchPresences() {
    console.log("Fetching presences for role:", userRole);

    if (userRole === "enseignant") {
      const moisValue = mois?.value || new Date().getMonth() + 1;
      console.log("Params enseignant:", {
        mois: moisValue,
        id_enseignant: enseignantId,
      });

      const params = {
        mois: moisValue,
        id_enseignant: enseignantId,
        id_classe: null,
      };
      fetchPresencesData(params);
      return;
    }

    if (!mois?.value) {
      afficherMessage("Choisissez un mois pour voir les présences");
      return;
    }

    const params = {
      mois: mois?.value,
      id_enseignant: enseignant?.value || null,
      id_classe: classe?.value || null,
    };
    fetchPresencesData(params);
  }

  function fetchPresencesData(params) {
    console.log("Envoi requête avec params:", params);

    fetch(ROOT + "/Enseignants/ajax_filtre_presence", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(params),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("Données reçues:", data);
        displayPresences(data);
      })
      .catch((error) => {
        console.error("Erreur fetch:", error);
        afficherMessage("Erreur lors du chargement des données");
      });
  }

  function displayPresences(data) {
    console.log("Affichage données:", data);
    tableBody.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
      afficherMessage("Aucune présence trouvée pour ce filtre");
      return;
    }

    data.forEach((presence, index) => {
      const validationHtml =
        userRole === "enseignant"
          ? presence.valide == 1
            ? `<span class="badge bg-success">Validé</span>`
            : `<span class="badge bg-warning text-dark">En attente</span>`
          : presence.valide == 1
          ? `<span class="badge bg-success">Validé</span>`
          : `<span class="badge bg-warning text-dark">En attente</span>
               <button class="btn btn-sm btn-success btn-valider-presence" 
                   data-id="${presence.id_presence}">
               <i class="fa fa-check"></i>
               </button>`;

      const leconsBtn = `
      <button class="btn btn-sm btn-primary btn-voir-lecons" 
              data-id="${presence.id_presence}"
              data-bs-toggle="modal" 
              data-bs-target="#modalLecons">
          <i class="fa-solid fa-book"></i> Voir leçons
      </button>
    `;

      // Pour un enseignant, masquer Modifier/Supprimer si présence validée
      // Pour un admin, toujours afficher Modifier/Supprimer
      const showEditDelete = userRole !== "enseignant" || presence.valide != 1;

      const row = `
      <tr>
        <td>
          ${
            userRole !== "enseignant"
              ? `
          <div class="checkbox">
            <input type="checkbox" class="checkbox-input rowCheckbox" 
                  id="checkbox${presence.id_presence}" 
                  data-id="${presence.id_presence}">
            <label for="checkbox${presence.id_presence}"></label>
          </div>`
              : ""
          }
        </td>
        <td>${presence.nom_prenom_enseignant || ""}</td>
        <td>${presence.nom_classe || ""}</td>
        <td>${formatDateTime(presence.date_presence)}</td>
        <td>${formatHeures(presence.nombre_heure)}</td>
        <td>${validationHtml}</td>
        <td>${leconsBtn}</td>
        <td>${presence.nom_trimestre || ""}</td>
        <td>${
          presence.nom_annee ||
          presence.annee ||
          presence.annee_scolaire ||
          presence.id_anneeScolaire ||
          ""
        }</td>
        <td class="text-center">
          <div>
            <a href="#" role="button" id="dropdownMenuLink${index}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
                   viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                   class="feather feather-more-horizontal">
                <circle cx="12" cy="12" r="1"></circle>
                <circle cx="19" cy="12" r="1"></circle>
                <circle cx="5" cy="12" r="1"></circle>
              </svg>
            </a>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink${index}">
             
              ${
                showEditDelete
                  ? `
              <a class="dropdown-item btn-modifier" href="#" 
                 data-bs-toggle="modal" 
                 data-bs-target="#modalEditPresence"
                 data-id="${presence.id_presence}"
                 data-enseignant="${presence.id_enseignant}"
                 data-classe="${presence.id_classe}"
                 data-date="${presence.date_presence}"
                 data-trimestre="${presence.id_trimestre}"
                 data-annee="${presence.id_anneeScolaire}">
                <i class="fa-solid fa-pen me-1" style="color: #7367F0;"></i> Modifier
              </a>
              <a class="dropdown-item btn-supprimer" href="#" 
                 data-id="${presence.id_presence}">
                <i class="fa-solid fa-trash me-1" style="color: #EA5455;"></i> Supprimer
              </a>
              `
                  : ""
              }
            </div>
          </div>
        </td>
      </tr>
    `;

      tableBody.innerHTML += row;
    });

    addCheckboxListeners();
    setupValidationAjax();
    initModalEvents();
    setupLeconsModal();
    initBootstrapDropdowns();
    setupEditModal();
    setupDeleteButtons();
    setTimeout(setupBulkPaymentButtons, 500);
  }

  function initBootstrapDropdowns() {
    const dropdowns = document.querySelectorAll(".dropdown-toggle");
    dropdowns.forEach((dropdown) => {
      new bootstrap.Dropdown(dropdown);
    });
  }

  function setupEditModal() {
    const modalEdit = document.getElementById("modalEditPresence");
    if (modalEdit) {
      modalEdit.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const idPresence = button.getAttribute("data-id");

        document.getElementById("edit_id_presence").value = idPresence;
        document.getElementById("edit_enseignant").value =
          button.getAttribute("data-enseignant");
        document.getElementById("edit_classe").value =
          button.getAttribute("data-classe");

        const dateStr = button.getAttribute("data-date");
        if (dateStr) {
          const dt = new Date(dateStr.replace(" ", "T"));
          const formatted = dt.toISOString().slice(0, 16);
          document.getElementById("edit_date_presence").value = formatted;
        }

        document.getElementById("edit_trimestre").value =
          button.getAttribute("data-trimestre");
        document.getElementById("edit_annee").value =
          button.getAttribute("data-annee");

        fetch(`${ROOT}/Enseignants/get_lecons/${idPresence}`)
          .then((response) => response.json())
          .then((response) => {
            const container = document.getElementById("edit-lecons-container");
            container.innerHTML = "";

            if (response.success && Array.isArray(response.data)) {
              response.data.forEach((lecon, index) => {
                container.innerHTML += `
                  <div class="lecon-row row mb-3 align-items-end" data-id="${
                    lecon.id_lecon_presence
                  }">
                    <input type="hidden" name="lecons[${index}][id]" value="${
                  lecon.id_lecon_presence
                }">
                    <div class="col-md-5">
                      <input type="text" name="lecons[${index}][titre]" class="form-control" 
                             value="${lecon.titre}" required>
                    </div>
                    <div class="col-md-3">
                      ${getDureeSelectHtml(
                        `lecons[${index}][heures]`,
                        lecon.nombre_heure
                      )}
                    </div>
                    <div class="col-md-3">
                      <input type="number" name="lecons[${index}][progression]" class="form-control" 
                             value="${lecon.progression}" min="0" max="100">
                    </div>
                    <div class="col-md-1">
                      <button type="button" class="btn btn-danger remove-edit-lecon">
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                  </div>
                `;
              });
            } else {
              container.innerHTML =
                '<div class="alert alert-warning">Aucune leçon trouvée</div>';
            }

            const rows = container.querySelectorAll(".lecon-row");
            if (rows.length === 1) {
              rows[0].querySelector(".remove-edit-lecon").disabled = true;
            }
          })
          .catch((error) => {
            console.error("Erreur chargement leçons:", error);
            Swal.fire("Erreur!", "Erreur de chargement des leçons", "error");
          });
      });
    }

    document
      .getElementById("edit-lecons-container")
      ?.addEventListener("click", function (e) {
        if (e.target.closest(".remove-edit-lecon")) {
          const row = e.target.closest(".lecon-row");
          const leconId = row.getAttribute("data-id");

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
                fetch(`${ROOT}/Enseignants/delete_lecon/${leconId}`, {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                })
                  .then((response) => response.json())
                  .then((response) => {
                    if (response.success) {
                      row.remove();
                      Swal.fire(
                        "Supprimé!",
                        "La leçon a été supprimée.",
                        "success"
                      );
                      const rows = document.querySelectorAll(
                        "#edit-lecons-container .lecon-row"
                      );
                      if (rows.length === 1) {
                        rows[0].querySelector(
                          ".remove-edit-lecon"
                        ).disabled = true;
                      }
                    } else {
                      Swal.fire(
                        "Erreur!",
                        response.message || "Erreur lors de la suppression",
                        "error"
                      );
                    }
                  })
                  .catch((error) => {
                    console.error("Erreur suppression:", error);
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
            const rows = document.querySelectorAll(
              "#edit-lecons-container .lecon-row"
            );
            if (rows.length === 1) {
              rows[0].querySelector(".remove-edit-lecon").disabled = true;
            }
          }
        }
      });

    document
      .getElementById("add-edit-lecon")
      ?.addEventListener("click", function () {
        const container = document.getElementById("edit-lecons-container");
        const newIndex = container.querySelectorAll(".lecon-row").length;

        container.innerHTML += `
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
      `;

        const rows = container.querySelectorAll(".lecon-row");
        if (rows.length > 1) {
          rows[0].querySelector(".remove-edit-lecon").disabled = false;
        }
      });
  }

  function setupDeleteButtons() {
    document.addEventListener("click", function (e) {
      if (e.target.closest(".btn-supprimer")) {
        e.preventDefault();
        const button = e.target.closest(".btn-supprimer");
        const idPresence = button.getAttribute("data-id");

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
      }
    });
  }

  function getDureeSelectHtml(name, selectedValue = "") {
    const options = [
      { value: "0.25", text: "15min" },
      { value: "0.50", text: "30min" },
      { value: "0.75", text: "45min" },
      { value: "1.00", text: "1h" },
      { value: "1.50", text: "1h30" },
      { value: "2.00", text: "2h" },
      { value: "2.50", text: "2h30" },
      { value: "3.00", text: "3h" },
      { value: "3.50", text: "3h30" },
      { value: "4.00", text: "4h" },
    ];

    let html = `<select name="${name}" class="form-select">`;
    options.forEach((option) => {
      html += `<option value="${option.value}" ${
        option.value == selectedValue ? "selected" : ""
      }>${option.text}</option>`;
    });
    html += "</select>";
    return html;
  }

  function setupLeconsModal() {
    const modal = document.getElementById("modalLecons");
    if (!modal) {
      console.error("Modal lecons not found");
      return;
    }

    document.addEventListener("click", function (e) {
      if (e.target.closest(".btn-voir-lecons")) {
        e.preventDefault();
        const btn = e.target.closest(".btn-voir-lecons");
        const presenceId = btn.dataset.id;
        const leconsContent = document.getElementById("lecons-content");

        if (leconsContent) {
          leconsContent.innerHTML =
            '<tr><td colspan="3" class="text-center"><div class="spinner-border"></div></td></tr>';
        }

        fetch(ROOT + "/Enseignants/get_lecons/" + presenceId)
          .then((response) => {
            if (!response.ok) throw new Error("HTTP error " + response.status);
            return response.json();
          })
          .then((data) => {
            console.log("Leçons reçues:", data);
            if (leconsContent) {
              if (data.success && data.data && data.data.length > 0) {
                let html = "";
                data.data.forEach((lecon) => {
                  html += `
                    <tr>
                      <td>${lecon.titre || "Sans titre"}</td>
                      <td>${formatHeures(lecon.nombre_heure)}</td>
                      <td>
                        <div class="progress" style="height:20px;">
                          <div class="progress-bar ${
                            lecon.progression == 100
                              ? "bg-success"
                              : lecon.progression > 0
                              ? "bg-primary"
                              : "bg-danger"
                          }" 
                               style="width:${lecon.progression || 0}%">
                            ${lecon.progression || 0}%
                          </div>
                        </div>
                      </td>
                    </tr>
                  `;
                });
                leconsContent.innerHTML = html;
              } else {
                leconsContent.innerHTML =
                  '<tr><td colspan="3" class="text-center text-muted">Aucune leçon trouvée</td></tr>';
              }
            }
          })
          .catch((error) => {
            console.error("Erreur chargement leçons:", error);
            if (leconsContent) {
              leconsContent.innerHTML =
                '<tr><td colspan="3" class="text-center text-danger">Erreur de chargement</td></tr>';
            }
          });
      }
    });
  }

  function initModalEvents() {
    const modal = document.getElementById("modalPresence");
    if (modal) {
      modal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const form = modal.querySelector("form");

        if (form) {
          form.querySelector('[name="id_presence"]').value =
            button.dataset.id || "";
          form.querySelector('[name="id_enseignant"]').value =
            button.dataset.enseignant || "";
          form.querySelector('[name="id_classe"]').value =
            button.dataset.classe || "";

          if (button.dataset.date) {
            const date = new Date(button.dataset.date);
            const formattedDate = date.toISOString().slice(0, 16);
            form.querySelector('[name="date_presence"]').value = formattedDate;
          }

          form.querySelector('[name="id_trimestre"]').value =
            button.dataset.trimestre || "";
          form.querySelector('[name="id_anneeScolaire"]').value =
            button.dataset.annee || "";
        }
      });
    }
  }

  function addCheckboxListeners() {
    const allSelect = document.getElementById("allSelect");
    const bulkActionBtn = document.getElementById("bulkActionBtn");
    const etatPaiementBtn = document.getElementById("etatPaiementBtn");
    const selectedIdsInput = document.getElementById("selectedIds");
    const selectedPresenceInput = document.getElementById("selectedPresence");

    if (
      !allSelect ||
      !bulkActionBtn ||
      !etatPaiementBtn ||
      !selectedIdsInput ||
      !selectedPresenceInput
    ) {
      console.error("Éléments de sélection non trouvés:", {
        allSelect: !!allSelect,
        bulkActionBtn: !!bulkActionBtn,
        etatPaiementBtn: !!etatPaiementBtn,
        selectedIdsInput: !!selectedIdsInput,
        selectedPresenceInput: !!selectedPresenceInput,
      });
      return;
    }

    function toggleBulkButtons() {
      const checkboxes = document.querySelectorAll(".rowCheckbox");
      const checkedBoxes = Array.from(checkboxes).filter((cb) => cb.checked);
      const ids = checkedBoxes.map((cb) => cb.dataset.id);

      // Afficher ou masquer les boutons
      bulkActionBtn.style.display = ids.length > 0 ? "inline-block" : "none";
      etatPaiementBtn.style.display = ids.length > 0 ? "inline-block" : "none";

      // Mettre à jour les inputs cachés
      selectedIdsInput.value = ids.join(",");
      selectedPresenceInput.value = ids.join(",");

      // Mettre à jour la checkbox "Tout sélectionner"
      if (ids.length === 0) {
        allSelect.checked = false;
        allSelect.indeterminate = false;
      } else if (ids.length === checkboxes.length) {
        allSelect.checked = true;
        allSelect.indeterminate = false;
      } else {
        allSelect.checked = false;
        allSelect.indeterminate = true;
      }

      console.log("IDs sélectionnés:", ids);
    }

    // Événement pour "Tout sélectionner"
    allSelect.addEventListener("change", function () {
      const checkboxes = document.querySelectorAll(".rowCheckbox");
      const isChecked = this.checked;

      checkboxes.forEach(function (cb) {
        cb.checked = isChecked;
      });

      toggleBulkButtons();
    });

    // Délégation d'événements pour les checkbox dynamiques
    document.addEventListener("change", function (e) {
      if (e.target && e.target.classList.contains("rowCheckbox")) {
        toggleBulkButtons();
      }
    });

    // Initialiser l'état des boutons
    toggleBulkButtons();
  }

  
  // Gestion des boutons de paiement en masse
  function setupBulkPaymentButtons() {
    const bulkBtn = document.getElementById("bulkActionBtn");
    const etatBtn = document.getElementById("etatPaiementBtn");
    const form = document.getElementById("bulkPaymentForm");
    const typeContratHidden = document.getElementById("typeContratHidden");
    const moisPaiementHidden = document.getElementById("moisPaiementHidden");
    const moisFilter = document.getElementById("moisFilter");

    if (bulkBtn && form && typeContratHidden && moisPaiementHidden) {
      bulkBtn.addEventListener("click", function (e) {
        e.preventDefault();

        // Récupérer les IDs depuis l'input hidden
        const selectedIds = document.getElementById("selectedIds").value;

        if (!selectedIds) {
          Swal.fire(
            "Aucune sélection",
            "Veuillez sélectionner au moins une présence.",
            "warning"
          );
          return;
        }

        // Utiliser la valeur du filtre mois ou le mois courant
        const moisNumero = moisFilter
          ? moisFilter.value
          : new Date().getMonth() + 1;
        const annee = new Date().getFullYear();
        const moisFormate = String(moisNumero).padStart(2, "0");
        const moisComplet = `${annee}-${moisFormate}`;

        moisPaiementHidden.value = moisComplet;

        Swal.fire({
          title: "Type de paiement",
          text: "Le paiement est-il pour les enseignants vacataires (VCT) ou contractuels (CDI/CDD) ?",
          icon: "question",
          showCancelButton: true,
          confirmButtonText: "Vacataires (VCT)",
          cancelButtonText: "Contractuels (CDI/CDD)",
          reverseButtons: true,
        }).then((result) => {
          if (result.isConfirmed) {
            typeContratHidden.value = "VCT";
            form.submit();
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            // CORRECTION : Utiliser une valeur sans slash
            typeContratHidden.value = "CDI_CDD"; // ← Correction ici
            form.submit();
          }
        });
      });
    }

    if (etatBtn) {
      etatBtn.addEventListener("click", function (e) {
        e.preventDefault();

        // Récupérer les IDs depuis l'input hidden
        const selectedIds = document.getElementById("selectedIds").value;

        if (!selectedIds) {
          Swal.fire(
            "Aucune sélection",
            "Veuillez sélectionner au moins une présence.",
            "warning"
          );
          return;
        }

        // Pour l'état de paiement, utiliser le filtre mois ou le mois courant
        const moisNumero = moisFilter
          ? moisFilter.value
          : new Date().getMonth() + 1;
        const annee = new Date().getFullYear();

        window.location.href = `${ROOT}/Enseignants/etat_paiementPresence?ids=${selectedIds}&mois=${moisNumero}&annee=${annee}`;
      });
    }
  }
  function setupValidationAjax() {
    document.addEventListener("click", function (e) {
      if (e.target.closest(".btn-valider-presence")) {
        e.preventDefault();
        const btn = e.target.closest(".btn-valider-presence");
        const presenceId = btn.dataset.id;
        const row = btn.closest("tr");

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch(ROOT + "/Enseignants/valider_presence_ajax", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id_presence: presenceId }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              const statusCell = row.querySelector("td:nth-child(6)");
              if (statusCell) {
                statusCell.innerHTML =
                  '<span class="badge bg-success">Validé</span>';
              }
              btn.remove();
            } else {
              throw new Error(data.message || "Erreur inconnue");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
          });
      }
    });
  }
  // Initialisation des événements
  const filters = [mois, enseignant, classe].filter((f) => f !== null);
  filters.forEach((f) => {
    if (f) {
      f.addEventListener("change", fetchPresences);
    }
  });

  // Chargement initial
  if (userRole === "enseignant") {
    console.log("Chargement initial pour enseignant");
    fetchPresences();
  } else if (mois) {
    afficherMessage("Choisissez un mois pour voir les présences");
  }
});
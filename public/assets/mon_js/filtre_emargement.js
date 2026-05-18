
document.addEventListener("DOMContentLoaded", function () {
  // Récupération des éléments avec vérification de null
  const mois = document.getElementById("moisFilter");
  const enseignant = document.getElementById("enseignantFilter");
  const classe = document.getElementById("classeFilter");
  const matiere = document.getElementById("matiereFilter");
  const tableBody = document.querySelector("#example tbody");

  // Vérifier que tableBody existe
  if (!tableBody) {
    console.error("Table body not found");
    return;
  }

  const userRole = document.body.dataset.role;
  const userId = document.body.dataset.userid;
  const enseignantId = document.body.dataset.enseignantid;

  // Fonction pour échapper les HTML
  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  function afficherMessage(msg) {
    tableBody.innerHTML = `<tr><td colspan="12" class="text-center text-muted">${msg}</td></tr>`;
  }

  function formatDate(dateStr) {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    return (
      d.toLocaleDateString("fr-FR") +
      " à " +
      d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" })
    );
  }

  function fetchEmargements() {
    // MODIFICATION: Logique différente pour les enseignants
    if (userRole === "enseignant") {
      // Pour les enseignants, on charge directement sans vérifier le mois
      const params = {
        mois: mois?.value || null,
        id_enseignant: enseignantId, // ID de l'enseignant connecté
        id_classe: null,
        id_matiere: null,
      };

      fetchEmargementsData(params);
      return;
    }

    // Pour les autres utilisateurs, vérifier le mois
    if (!mois?.value) {
      afficherMessage("Choisissez un mois pour voir ses émargements");
      return;
    }

    const params = {
      mois: mois?.value || null,
      id_enseignant: enseignant?.value || null,
      id_classe: classe?.value || null,
      id_matiere: matiere?.value || null,
    };

    fetchEmargementsData(params);
  }

  // NOUVELLE FONCTION: Factorisation de la récupération des données
  function fetchEmargementsData(params) {
    fetch(ROOT + "/Enseignants/ajax_filtre_emargement", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(params),
    })
      .then((res) => res.json())
      .then((data) => {
        displayEmargements(data);
      })
      .catch((err) => {
        console.error(err);
        afficherMessage("Erreur lors du chargement des données");
      });
  }

  // NOUVELLE FONCTION: Affichage des émargements
  function displayEmargements(data) {
    tableBody.innerHTML = "";
    if (!Array.isArray(data) || !data.length) {
      afficherMessage("Aucun émargement trouvé pour ce filtre");
      return;
    }

    data.forEach((emargement, index) => {
      const progression = emargement.progression || 0;
      const validationHtml =
        emargement.valide == 1
          ? `<span class="badge bg-success">Validé</span>`
          : `<span class="badge bg-warning text-dark">En attente</span>
             <button class="btn btn-sm btn-success btn-valider-emargement" 
                     data-id="${emargement.id_emargement}">
                 <i class="fa fa-check"></i>
             </button>`;

      tableBody.innerHTML += `
        <tr>
          <td>
            ${
              userRole !== "enseignant"
                ? `
            <div class="checkbox">
              <input type="checkbox" class="checkbox-input rowCheckbox" 
                     id="checkbox${index}" 
                     data-id="${emargement.id_enseignant}">
              <label for="checkbox${index}"></label>
            </div>`
                : ""
            }
          </td>
          <td>${emargement.nom_prenom_enseignant}</td>
          <td>${emargement.nom_classe}</td>
          <td>${emargement.nom_matiere}</td>
          <td>${emargement.titre_lecon}</td>
          <td>${formatDate(emargement.date_emargement)}</td>
          <td>${emargement.nom_trimestre}</td>
          <td>${emargement.type_contrat_enseignant}</td>
          <td>${validationHtml}</td>
          <td>
            <div class="progress" style="height:25px; cursor: pointer;" 
                 onclick="showProgressDetails(${emargement.id_enseignant}, ${
        emargement.id_classe
      }, ${emargement.id_matiere})">
              <div class="progress-bar ${
                progression == 100
                  ? "bg-success"
                  : progression > 0
                  ? "bg-primary"
                  : "bg-danger"
              }" 
                   style="width:${progression}%">
                ${progression.toFixed(2)}%
              </div>
            </div>
          </td>
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
                <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalEmargement"
                     data-id="${emargement.id_emargement}"
                     data-enseignant="${emargement.id_enseignant}"
                     data-classe="${emargement.id_classe}"
                     data-matiere="${emargement.id_matiere}"
                     data-chapitre="${escapeHtml(emargement.chapitre || "")}"
                     data-lecon="${emargement.id_lecon}"
                     data-nombre="${emargement.nombre_heure}"
                     data-trimestre="${emargement.id_trimestre}"
                     data-annee="${emargement.id_anneeScolaire}">
                     <i class="fa-solid fa-eye" style="color: #7367F0;"></i> Modifier
                </a>
                <a class="dropdown-item" href="${ROOT}/Enseignants/delete_emargement/${
        emargement.id_emargement
      }">
                     <i class="fa-solid fa-trash me-1" style="color: #EA5455;"></i>Supprimer
                </a>
              </div>
            </div>
          </td>
        </tr>
      `;
    });

    addCheckboxListeners();
    setupValidationAjax();
    initModalEvents();
  }

  function initModalEvents() {
    const modal = document.getElementById("modalEmargement");
    if (modal) {
      modal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const form = modal.querySelector("form");

        if (form) {
          form.querySelector('[name="id_emargement"]').value =
            button.dataset.id;
          form.querySelector('[name="id_enseignant"]').value =
            button.dataset.enseignant;
          form.querySelector('[name="id_classe"]').value =
            button.dataset.classe;
          form.querySelector('[name="id_matiere"]').value =
            button.dataset.matiere;
          form.querySelector('[name="chapitre"]').value =
            button.dataset.chapitre;
          form.querySelector('[name="id_lecon"]').value = button.dataset.lecon;
          form.querySelector('[name="nombre_heure"]').value =
            button.dataset.nombre;
          form.querySelector('[name="id_trimestre"]').value =
            button.dataset.trimestre;
          form.querySelector('[name="id_anneeScolaire"]').value =
            button.dataset.annee;
        }
      });
    }
  }

  function setupValidationAjax() {
    document.addEventListener("click", function (e) {
      if (e.target.closest(".btn-valider-emargement")) {
        e.preventDefault();
        const btn = e.target.closest(".btn-valider-emargement");
        const emargementId = btn.dataset.id;
        const row = btn.closest("tr");

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch(ROOT + "/Enseignants/valider_emargement_ajax", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            id_emargement: emargementId,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              return response.text().then((text) => {
                throw new Error(
                  `Erreur serveur: ${response.status} ${response.statusText} - ${text}`
                );
              });
            }
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              const statusCell = row.querySelector("td:nth-child(9)");
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

  function addCheckboxListeners() {
    const allSelect = document.getElementById("allSelect");
    const bulkActionBtn = document.getElementById("bulkActionBtn");
    const etatPaiementBtn = document.getElementById("etatPaiementBtn");
    const selectedIdsInput = document.getElementById("selectedIds");
    const typeContratHidden = document.getElementById("typeContratHidden");
    const moisPaiementHidden = document.getElementById("moisPaiementHidden");

    // Vérifier que les éléments existent avant de les utiliser
    if (!allSelect || !bulkActionBtn || !etatPaiementBtn || !selectedIdsInput) {
      return; // Éléments non trouvés, probablement un enseignant
    }

    function toggleBulkButtons() {
      const ids = Array.from(
        document.querySelectorAll(".rowCheckbox:checked")
      ).map((cb) => cb.dataset.id);

      bulkActionBtn.style.display = ids.length ? "inline-block" : "none";
      etatPaiementBtn.style.display = ids.length ? "inline-block" : "none";
      selectedIdsInput.value = ids.join(",");

      if (mois && moisPaiementHidden) {
        moisPaiementHidden.value = mois.value;
      }
    }

    allSelect.addEventListener("change", () => {
      document.querySelectorAll(".rowCheckbox").forEach((cb) => {
        if (cb.offsetParent) cb.checked = allSelect.checked;
      });
      toggleBulkButtons();
    });

    document.querySelectorAll(".rowCheckbox").forEach((cb) => {
      cb.addEventListener("change", toggleBulkButtons);
    });

    etatPaiementBtn.addEventListener("click", () => {
      const ids = Array.from(
        document.querySelectorAll(".rowCheckbox:checked")
      ).map((cb) => cb.dataset.id);
      if (ids.length)
        window.open(`${ROOT}/Enseignants/etat_paiement?ids=${ids.join(",")}`);
    });

    bulkActionBtn.addEventListener("click", function (e) {
      e.preventDefault();

      const selectedIds = selectedIdsInput.value;
      if (!selectedIds) {
        Swal.fire("Erreur", "Aucun enseignant sélectionné", "error");
        return;
      }

      const moisNumero = mois.value;
      if (!moisNumero) {
        Swal.fire("Erreur", "Veuillez sélectionner un mois", "error");
        return;
      }

      const now = new Date();
      const annee = now.getFullYear();
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
          document.getElementById("bulkPaymentForm").submit();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          typeContratHidden.value = "CDI_CDD";
          document.getElementById("bulkPaymentForm").submit();
        }
      });
    });
  }

  // MODIFICATION: Initialisation sélective des événements
  const filters = [mois, enseignant, classe, matiere].filter((f) => f !== null);
  filters.forEach((f) => {
    f.addEventListener("change", fetchEmargements);
  });

  // Chargement initial
  if (userRole === "enseignant") {
    fetchEmargements(); // Charge directement pour les enseignants
  } else if (mois) {
    afficherMessage("Choisissez un mois pour voir ses émargements");
  }
});

// FONCTION GLOBALE pour showProgressDetails
window.showProgressDetails = function (idEnseignant, idClasse, idMatiere) {
  const url = `${ROOT}/Enseignants/getProgressionDetails?id_enseignant=${idEnseignant}&id_classe=${idClasse}&id_matiere=${idMatiere}`;
  const modalBody = document.getElementById("progressionDetailsContent");

  if (!modalBody) {
    console.error("Element progressionDetailsContent non trouvé");
    return;
  }

  modalBody.innerHTML = "Chargement...";

  fetch(url)
    .then((res) => res.text())
    .then((html) => {
      modalBody.innerHTML = html;
      const modal = new bootstrap.Modal(
        document.getElementById("modalProgression")
      );
      modal.show();
    })
    .catch((err) => {
      console.error(
        "Erreur lors de la récupération des détails de progression:",
        err
      );
      modalBody.innerHTML = "<p class='text-danger'>Erreur de chargement.</p>";
    });
};
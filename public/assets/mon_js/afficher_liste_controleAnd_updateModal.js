document.addEventListener("DOMContentLoaded", function () {
  const idClasseEl = document.getElementById("id_classe");
  const idMatiereEl = document.getElementById("id_matiere");
  const idAnneeEl = document.getElementById("id_anneeScolaire");
  const idTrimestreEl = document.getElementById("id_trimestre");
  const tbody = document.getElementById("tableLISTE").querySelector("tbody");
  let currentMaxRows = 10;
  let statutsControle = [];

  // Ajout du checkbox global 
  function ensureCheckboxThead() {
    const thead = document.querySelector("#tableLISTE thead tr");
    if (!thead.querySelector('th[data-col="select"]')) {
      const th = document.createElement("th");
      th.setAttribute("data-col", "select");
      th.innerHTML = `<input type="checkbox" id="selectAllEleves">`;
      thead.insertBefore(th, thead.firstChild);
    }
  }

  // Recherche
  document.getElementById("tableSearch").addEventListener("keyup", function () {
    const value = this.value.toLowerCase();
    document.querySelectorAll("#tableLISTE tbody tr").forEach(function (row) {
      row.style.display = row.textContent.toLowerCase().includes(value)
        ? ""
        : "none";
    });
  });

  // Pagination
  function updatePagination() {
    const rows = document.querySelectorAll("#tableLISTE tbody tr");
    rows.forEach((row, index) => {
      row.style.display = index < currentMaxRows ? "" : "none";
    });
    updateAfficherButton(rows.length);
  }

  // Met à jour le bouton Afficher avec le nombre d'éléments
  function updateAfficherButton(total) {
    const btn = document.querySelector("#dropdownAfficher");
    if (btn) {
      btn.innerHTML = `<i class="fa fa-list-ol me-1"></i> Afficher : ${Math.min(
        currentMaxRows,
        total
      )} / ${total}`;
    }
  }

  document.querySelectorAll(".afficher-option").forEach(function (item) {
    item.addEventListener("click", function (e) {
      e.preventDefault();
      currentMaxRows = parseInt(this.getAttribute("data-value"), 10);
      updatePagination();
    });
  });

  // Modal unique
  function ensureModal(statutsControle) {
    if (!document.getElementById("modalEditControle")) {
      let optionsStatut = "";
      if (Array.isArray(statutsControle)) {
        statutsControle.forEach(function (statut) {
          optionsStatut += `<option value="${statut.id_controle}">${statut.type_controle}</option>`;
        });
      }
      const modalHtml = `
        <div class="modal fade" id="modalEditControle" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form id="formEditControle">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title w-100 text-center" id="modalEditControleLabel">Modification du contrôle de l'élève : <span id="modalEleveName"></span></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" id="edit_id_controle_eleve" name="id_controle_eleve">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label>Libellé</label>
                      <input type="text" class="form-control" id="edit_libelle" name="libelle" required>
                    </div>
                    <div class="col-md-6">
                      <label>Date</label>
                      <input type="date" class="form-control" id="edit_date" name="date" required>
                    </div>
                    <div class="col-md-6">
                      <label>Heure début</label>
                      <input type="time" class="form-control" id="edit_heure_debut" name="heure_debut" required>
                    </div>
                    <div class="col-md-6">
                      <label>Heure fin</label>
                      <input type="time" class="form-control" id="edit_heure_fin" name="heure_fin" required>
                    </div>
                    <div class="col-md-6">
                      <label>Statut</label>
                       <select class="form-select" id="edit_statut" name="id_controle">
                        ${optionsStatut}
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label>Notification</label>
                      <select class="form-select" id="edit_notifier_parent" name="notifier_parent">
                        <option value="1">Reçu</option>
                        <option value="0">Non Reçu</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="modal-footer justify-content-center">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                  <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
              </form>
            </div>
          </div>
        </div>`;
      document.body.insertAdjacentHTML("beforeend", modalHtml);
    }
    // Ajoute le modal de succès si pas déjà présent
    if (!document.getElementById("modalSuccess")) {
      document.body.insertAdjacentHTML(
        "beforeend",
        `
        <div class="modal fade" id="modalSuccess" tabindex="-1">
          <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-success">
              <div class="modal-body text-center p-4">
                <div class="mb-3">
                  <i class="fa fa-check-circle text-success" style="font-size:4em;"></i>
                </div>
                <h4 class="text-success mb-2">Réussite</h4>
                <div id="modalSuccessMsg">Modification faite avec succès.</div>
              </div>
            </div>
          </div>
        </div>

      `
      );
    }
  }

  // Récupérer la liste des statuts (type_controle) pour alimenter le select du modal
  function fetchStatutsControle(callback) {
    fetch(`${ROOT}/Controles/getStatutsControleAjax`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        idMatiere: idMatiereEl.value,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        statutsControle = data;
        if (typeof callback === "function") callback();
      })
      .catch(() => {
        statutsControle = [];
        if (typeof callback === "function") callback();
      });
  }
// Dans afficherControles
  function afficherControles(liste) {
    tbody.innerHTML = "";
    ensureCheckboxThead();
    if (!Array.isArray(liste) || liste.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Aucun contrôle trouvé</td></tr>`;
      updatePagination();
      return;
    }

    liste.forEach((ctrl) => {
      const badge =
        ctrl.notifier_parent == 1
          ? `<span class="badge bg-success">Reçu</span>`
          : `<span class="badge bg-danger">Non Reçu</span>`;

      const nomComplet = `${ctrl.prenom_eleve || ""} ${
        ctrl.nom_eleve || ""
      }`.trim();

      const row = document.createElement("tr");
      row.innerHTML = `
        <td data-col="select"><input type="checkbox" class="select-eleve"></td>
        <td data-col="eleve">${nomComplet}</td>
        <td data-col="date">${ctrl.date || ""}</td>
        <td data-col="libelle">${ctrl.libelle || ""}</td>
        <td data-col="heure">${(ctrl.heure_debut || "").substring(0, 5)} - ${(
        ctrl.heure_fin || ""
      ).substring(0, 5)}</td>
      <td data-col="notification">${badge}</td>
      <td data-col="statut">${ctrl.statut || ""}</td>
      <td data-col="action" class="text-center">
      <button class="btn btn-sm btn-info btn-edit-controle"
        data-id="${ctrl.id_controle_eleve}"
        data-libelle="${ctrl.libelle}"
        data-date="${ctrl.date}"
        data-heure_debut="${ctrl.heure_debut}"
        data-heure_fin="${ctrl.heure_fin}"
        data-notifier_parent="${ctrl.notifier_parent}"
        data-id-controle="${ctrl.id_controle}"
        data-statut="${ctrl.statut}"
        data-eleve="${nomComplet}">
        <i class="fa fa-edit"></i>
      </button>
    </td>`;
      tbody.appendChild(row);
    });

    // Gestion du selectAll
    const selectAll = document.getElementById("selectAllEleves");
    if (selectAll) {
      selectAll.checked = false;
      selectAll.addEventListener("change", function () {
        tbody.querySelectorAll(".select-eleve").forEach((cb) => {
          cb.checked = selectAll.checked;
        });
      });
    }
    // Si on décoche un élève, décocher le selectAll
    tbody.querySelectorAll(".select-eleve").forEach((cb) => {
      cb.addEventListener("change", function () {
        if (!this.checked && selectAll) selectAll.checked = false;
      });
    });

    tbody.querySelectorAll(".btn-edit-controle").forEach((btn) => {
      btn.addEventListener("click", function () {
        fetchStatutsControle(function () {
          ensureModal(statutsControle);

          // Remplir les options du select statut
          const selectStatut = document.getElementById("edit_statut");
          selectStatut.innerHTML = "";
          statutsControle.forEach(function (statut) {
            const option = document.createElement("option");
            option.value = statut.id_controle;
            option.textContent = statut.type_controle;
            selectStatut.appendChild(option);
          });

          document.getElementById("edit_id_controle_eleve").value =
            btn.dataset.id;
          document.getElementById("edit_libelle").value = btn.dataset.libelle;
          document.getElementById("edit_date").value = btn.dataset.date;
          document.getElementById("edit_heure_debut").value = (
            btn.dataset.heure_debut || ""
          ).substring(0, 5);
          document.getElementById("edit_heure_fin").value = (
            btn.dataset.heure_fin || ""
          ).substring(0, 5);
          document.getElementById("edit_notifier_parent").value =
            btn.dataset.notifier_parent;
          document.getElementById("modalEleveName").textContent =
            btn.dataset.eleve || "";

          // Sélectionner le bon statut (id_controle)
          const idControleValue = btn.getAttribute("data-id-controle");
          let found = false;
          Array.from(selectStatut.options).forEach((opt) => {
            if (opt.value == idControleValue) {
              opt.selected = true;
              found = true;
            } else {
              opt.selected = false;
            }
          });
          if (!found && selectStatut.options.length > 0) {
            selectStatut.options[0].selected = true;
          }

          new bootstrap.Modal(
            document.getElementById("modalEditControle")
          ).show();
        });
      });
    });

    updatePagination();
  }

  function chargerControles() {
    const payload = {
      idClasse: idClasseEl.value,
      idMatiere: idMatiereEl.value,
      idAnnee: idAnneeEl.value,
      idTrimestre: idTrimestreEl.value,
    };

    if (!payload.idClasse || !payload.idMatiere) {
      tbody.innerHTML = ` <tr>
                <td colspan="8" class="text-center" style="font-size:1.2em;">
                  <span style="font-size:1.5em;">🥰</span><br>
                  <span style="color: #28a745;">Sélectionnez une classe et une matière et les contrôles faits apparaîtront !</span>
                </td>
                </tr>`;
      updatePagination();
      return;
    }

    document.getElementById("loader").style.display = "block";

    fetch(`${ROOT}/Controles/selectListeControle_eleve_Ajax`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((data) => {
        document.getElementById("loader").style.display = "none";
        afficherControles(data);
      })
      .catch(() => {
        document.getElementById("loader").style.display = "none";
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Erreur de chargement</td></tr>`;
        updatePagination();
      });
  }

  idClasseEl.addEventListener("change", function () {
    fetchStatutsControle(chargerControles);
  });
  idMatiereEl.addEventListener("change", function () {
    fetchStatutsControle(chargerControles);
  });
  idAnneeEl.addEventListener("change", chargerControles);
  idTrimestreEl.addEventListener("change", chargerControles);

  document.addEventListener("submit", function (e) {
    if (e.target && e.target.id === "formEditControle") {
      e.preventDefault();
      const data = {};
      new FormData(e.target).forEach((v, k) => (data[k] = v));

      fetch(`${ROOT}/Controles/modifierControleAjax`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      })
        .then((res) => res.json())
        .then((resp) => {
          if (resp.success) {
            bootstrap.Modal.getInstance(
              document.getElementById("modalEditControle")
            ).hide();
            // Affiche le modal de succès
            const modalSuccess = new bootstrap.Modal(
              document.getElementById("modalSuccess")
            );
            document.getElementById("modalSuccessMsg").textContent =
              "Modification réussie !";
            modalSuccess.show();
            setTimeout(() => {
              modalSuccess.hide();
            }, 1500);
            chargerControles();
          } else {
            alert(resp.message || "Erreur lors de la modification.");
          }
        })
        .catch(() => alert("Erreur lors de la modification."));
    }
  });

  // Initialisation
  fetchStatutsControle(chargerControles);
});


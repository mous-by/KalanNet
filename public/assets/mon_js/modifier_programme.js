document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("click", function (e) {
    const removeLeconBtn = e.target.closest(".remove-lecon");
    if (removeLeconBtn) {
      handleRemoveLecon(removeLeconBtn);
      return;
    }

    const addLeconBtn = e.target.closest(".add-lecon");
    if (addLeconBtn) {
      handleAddLecon(addLeconBtn);
      return;
    }

    const addMatiereBtn = e.target.closest(".add-matiere");
    if (addMatiereBtn) {
      handleAddMatiere(addMatiereBtn);
      return;
    }

    const removeMatiereBtn = e.target.closest(".remove-matiere");
    if (removeMatiereBtn) {
      handleRemoveMatiere(removeMatiereBtn);
    }
  });

  function handleRemoveLecon(btn) {
    const row = btn.closest(".lecon-item");
    if (!row) {
      console.error("Ligne de leçon introuvable");
      return;
    }

    const idLeconInput = row.querySelector('input[name*="[id_lecon]"]');
    const idLecon = idLeconInput ? idLeconInput.value : "new";

    if (idLecon === "new") {
      row.remove();
      const container = document.querySelector(".lecons-container");
      if (container) renumberLecons(container);
      return;
    }

    Swal.fire({
      title: "Supprimer la leçon ?",
      text: "Cette action est irréversible !",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Oui, supprimer",
      cancelButtonText: "Annuler",
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`${ROOT}/Programmes/supprimerLecon/${idLecon}`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
        })
          .then((response) => {
            if (!response.ok) throw new Error("Erreur réseau");
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              const form = document.querySelector("form");
              if (form) {
                const hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = idLeconInput.name.replace(
                  "[id_lecon]",
                  "[delete]"
                );
                hidden.value = 1;
                form.appendChild(hidden);
              }

              row.remove();
              const container = document.querySelector(".lecons-container");
              if (container) renumberLecons(container);

              Swal.fire("Supprimée !", "La leçon a été supprimée.", "success");
            } else {
              throw new Error(data.error || "Erreur lors de la suppression");
            }
          })
          .catch((error) => {
            console.error("Erreur suppression:", error);
            Swal.fire(
              "Erreur",
              error.message || "Erreur lors de la suppression",
              "error"
            );
          });
      }
    });
  }

  function handleAddLecon(btn) {
    const programmeClasseId = btn.dataset.programmeClasse;
    const container = btn
      .closest(".matiere-item")
      .querySelector(".lecons-container");

    // Compter les leçons existantes pour cette matière
    const existingLecons = container.querySelectorAll(".lecon-item").length;
    const nextNumero = existingLecons + 1;

    const html = `
        <div class="row g-2 align-items-end mb-2 lecon-item">
          <div class="col-1">
            <input type="hidden" name="lecons[${programmeClasseId}][${existingLecons}][id_lecon]" value="new">
            <input type="number" class="form-control" name="lecons[${programmeClasseId}][${existingLecons}][numero]" value="${nextNumero}" readonly>
          </div>
          <div class="col-10">
            <input type="text" class="form-control" name="lecons[${programmeClasseId}][${existingLecons}][titre]" placeholder="Titre de la leçon" required>
          </div>
          <div class="col-1 text-end">
            <button type="button" class="btn btn-danger btn-sm remove-lecon">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      `;

    container.insertAdjacentHTML("beforeend", html);
  }
  // Fonction pour ajouter une nouvelle matière
  function handleAddMatiere(btn) {
    const container = document.querySelector(".matieres-container");
    const newProgClasseId = "new_" + Date.now();
    const idClasse = btn.dataset.idClasse;
    if (!idClasse) {
      Swal.fire("Erreur", "ID de la classe manquant", "error");
      return;
    }

    fetch(`${ROOT}/Programmes/get_matieres_classe_pour_update/${idClasse}`)
      .then((response) => response.json())
      .then((matieres) => {
        let options = '<option value="">-- Sélectionnez --</option>';
        matieres.forEach((m) => {
          options += `<option value="${m.id_matiere}">${m.nom_matiere}</option>`;
        });

        const html = `
            <div class="mb-4 border p-3 rounded matiere-item" data-programme-classe="${newProgClasseId}">
              <div class="row mb-3">
                <div class="col-10">
                  <select name="matieres[${newProgClasseId}][id_matiere]" class="form-select" required>
                    ${options}
                  </select>
                </div>
                <div class="col-2 text-end">
                  <button type="button" class="btn btn-danger btn-sm remove-matiere" data-programme-classe="${newProgClasseId}">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
              <div class="lecons-container"></div>
              <button type="button" class="btn btn-secondary add-lecon mt-2" data-programme-classe="${newProgClasseId}">
                <i class="fa-solid fa-plus"></i> Nouvelle leçon
              </button>
            </div>
          `;
        container.insertAdjacentHTML("beforeend", html);
      })
      .catch(() =>
        Swal.fire("Erreur", "Impossible de charger les matières", "error")
      );
  }

  function handleRemoveMatiere(btn) {
    const matiereItem = btn.closest(".matiere-item");
    const idMatiereInput = matiereItem.querySelector('[name*="[id_matiere]"]');
    const progClasseIdMatch = idMatiereInput?.name.match(/\[(.*?)\]/);
    const programmeClasseId = progClasseIdMatch ? progClasseIdMatch[1] : null;

    if (programmeClasseId && !programmeClasseId.startsWith("new_")) {
      Swal.fire({
        title: "Supprimer la matière ?",
        text: "Toutes ses leçons seront aussi supprimées !",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler",
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`${ROOT}/Programmes/supprimerMatiere/${programmeClasseId}`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                matiereItem.remove();
                Swal.fire(
                  "Supprimée !",
                  "La matière et ses leçons ont été supprimées.",
                  "success"
                );
              } else {
                Swal.fire(
                  "Erreur",
                  data.error || "Erreur lors de la suppression",
                  "error"
                );
              }
            })
            .catch(() => Swal.fire("Erreur", "Erreur réseau", "error"));
        }
      });
    } else {
      // cas matière pas encore enregistrée
      matiereItem.remove();
    }
  }

//   function renumberLecons(container) {
//     const matiereItem = container.closest(".matiere-item");
//     const programmeClasseId = matiereItem.dataset.programmeClasse;
//     const rows = container.querySelectorAll(".lecon-item");

//     rows.forEach((row, index) => {
//       // Mettre à jour le numéro
//       const numeroInput = row.querySelector('input[name*="[numero]"]');
//       if (numeroInput) numeroInput.value = index + 1;

//       // Mettre à jour tous les noms de champs
//       row.querySelectorAll("input").forEach((input) => {
//         input.name = input.name.replace(
//           /lecons\[\d+\]\[\d+\]/g,
//           `lecons[${programmeClasseId}][${index}]`
//         );
//       });
//     });
//   }

    function renumberLecons(container) {
    if (!container) {
        console.error("Le conteneur de leçons est introuvable");
        return;
    }

    const rows = container.querySelectorAll(".lecon-item");
    rows.forEach((row, index) => {
        const numeroInput = row.querySelector('input[name*="[numero]"]');
        if (numeroInput) numeroInput.value = index + 1;

        row.querySelectorAll("input").forEach((input) => {
        const name = input.name;
        const newName = name.replace(/\[\d+\]\[(\d+)\]/g, (match, p1) => {
            return `[${p1}][${index}]`;
        });
        input.name = newName;
        });
    });
    }

});

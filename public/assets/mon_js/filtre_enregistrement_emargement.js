document.addEventListener("DOMContentLoaded", function () {
  const contractRadiosContainer = document.querySelector(
    ".d-flex.justify-content-center.align-items-center.mb-3"
  );
  const contractRadios = document.querySelectorAll(
    "input[name='type_contrat']"
  );
  const enseignantSelect = document.getElementById("enseignant");
  const classeSelect = document.getElementById("classe");
  const matiereSelect = document.getElementById("matiere");

  // On récupère si l'utilisateur est SupAdmin (via data-attribute sur <body>)
  const isSupAdmin = document.body.getAttribute("data-supadmin") === "true";

  // On détecte si l'utilisateur est un enseignant (via un data-attribute ou autre moyen ?)
  // Ici, on suppose que le select enseignant est désactivé s'il s'agit d'un enseignant
  const isEnseignant = enseignantSelect.disabled;

  // Si enseignant connecté => on cache les radios directement
  if (isEnseignant) {
    if (contractRadiosContainer) {
      contractRadiosContainer.style.display = "none";
    }

    // On récupère son id depuis le select (qui ne peut changer)
    const enseignantId = enseignantSelect.value;

    // On charge les classes et matières pour cet enseignant directement
    loadClassesByEnseignant(enseignantId);
    updateMatiereOptionsByEnseignantId(enseignantId);
  } else {
    // Si admin : on écoute les changements sur les radios
    contractRadios.forEach((radio) => {
      radio.addEventListener("change", function () {
        updateSelectOptions();
        clearMatiereOptions();
      });
    });

    // Et aussi sur changement enseignant
    enseignantSelect.addEventListener("change", function () {
      const selectedId = this.value;
      updateMatiereOptionsByEnseignantId(selectedId);
      loadClassesByEnseignant(selectedId);
    });

    // On charge initialement la liste des enseignants en fonction du type contrat sélectionné
    updateSelectOptions();
  }

  // Charge les enseignants en fonction du type contrat (pour admin)
  function updateSelectOptions() {
    const selectedType =
      document.querySelector("input[name='type_contrat']:checked")?.value ||
      null;

    fetch("index.php?url=Enseignants/filtrer_enseignants", {
      method: "POST",
      body: JSON.stringify(isSupAdmin ? {} : { type: selectedType }),
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => response.json())
      .then((jsonData) => {
        enseignantSelect.innerHTML =
          "<option selected disabled>Sélectionnez un enseignant</option>";

        if (Array.isArray(jsonData)) {
          jsonData.forEach((enseignant) => {
            enseignantSelect.innerHTML += `<option value="${enseignant.id_enseignant}">${enseignant.nom_prenom_enseignant}</option>`;
          });
        } else {
          console.warn("Aucun enseignant trouvé.");
        }
      })
      .catch((error) => console.error("Erreur enseignants :", error));
  }

  // Charge matières pour un enseignant donné
  function updateMatiereOptionsByEnseignantId(enseignantId) {
    fetch("index.php?url=Enseignants/filtrer_matieres", {
      method: "POST",
      body: JSON.stringify(isSupAdmin ? {} : { enseignants: [enseignantId] }),
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => response.json())
      .then((data) => {
        matiereSelect.innerHTML =
          "<option selected disabled>Sélectionnez une matière</option>";

        if (Array.isArray(data)) {
          data.forEach((matiere) => {
            matiereSelect.innerHTML += `<option value="${matiere.id_matiere}">${matiere.nom_matiere}</option>`;
          });
        } else {
          console.warn("Aucune matière trouvée.");
        }
      })
      .catch((error) =>
        console.error("Erreur lors de la récupération des matières :", error)
      );
  }

  // Charge classes pour un enseignant donné, et si une seule classe, la sélectionne automatiquement
  function loadClassesByEnseignant(enseignantId) {
    fetch("index.php?url=Enseignants/filtrer_classes", {
      method: "POST",
      body: JSON.stringify({ enseignantId }),
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => response.json())
      .then((data) => {
        classeSelect.innerHTML =
          "<option selected disabled>Sélectionnez une classe</option>";

        if (Array.isArray(data)) {
          data.forEach((classe) => {
            classeSelect.innerHTML += `<option value="${classe.id_classe}">${classe.nom_classe}</option>`;
          });

          // Si une seule classe, on la sélectionne automatiquement
          if (data.length === 1) {
            classeSelect.value = data[0].id_classe;
          }
        } else {
          console.warn("Aucune classe trouvée.");
        }
      })
      .catch((error) =>
        console.error("Erreur lors de la récupération des classes :", error)
      );
  }

  function clearMatiereOptions() {
    matiereSelect.innerHTML =
      "<option selected disabled>Sélectionnez une matière</option>";
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const contractRadiosContainer = document.querySelector(
    ".d-flex.justify-content-center.align-items-center.mb-3"
  );
  const contractRadios = document.querySelectorAll(
    "input[name='type_contrat']"
  );
  const enseignantSelect = document.getElementById("enseignant");
  const classeSelect = document.getElementById("classe");
  const matiereSelect = document.getElementById("matiere");
  const leconSelect = document.getElementById("lecon");
  document.addEventListener("DOMContentLoaded", function () {
    const enseignantSelect = document.getElementById("enseignant");

    // Vérification renforcée pour les enseignants
    if (enseignantSelect && enseignantSelect.disabled) {
      // Si le select est vide ou mal rempli
      if (enseignantSelect.options.length <= 1) {
        // Récupération depuis les données de session
        fetch("index.php?url=Enseignants/getSessionData")
          .then((response) => response.json())
          .then((data) => {
            if (data.id_enseignant && data.nomPrenom) {
              enseignantSelect.innerHTML = `
                            <option value="${data.id_enseignant}" selected>
                                ${data.nomPrenom}
                            </option>
                        `;
            }
          })
          .catch((error) => console.error("Erreur:", error));
      }
    }
  });
  // Récupération de l'id et du nom de l'enseignant depuis le select (déjà pré-rempli côté PHP)
  // Correction: on récupère aussi depuis window si le select est vide (cas JS inclus dans <script>)
  let enseignantId = enseignantSelect.value;
  let enseignantName =
    enseignantSelect.options[enseignantSelect.selectedIndex]?.textContent || "";

  // Si le select est vide (cas où l'option n'est pas générée côté PHP), on utilise les variables globales
  if (!enseignantId && typeof window.enseignantId !== "undefined") {
    enseignantId = window.enseignantId;
    enseignantName = window.enseignantName || "";
    // On ajoute dynamiquement l'option dans le select si besoin
    if (enseignantSelect && enseignantId) {
      enseignantSelect.innerHTML = `<option value="${enseignantId}" selected>${enseignantName}</option>`;
    }
  }

  // On détecte si l'utilisateur est un enseignant (via un data-attribute ou autre moyen ?)
  // Ici, on suppose que le select enseignant est désactivé s'il s'agit d'un enseignant
  const isSupAdmin = document.body.getAttribute("data-supadmin") === "true";
  // Correction: on prend la variable globale si elle existe
  const isEnseignant =
    typeof window.isEnseignant !== "undefined"
      ? window.isEnseignant
      : enseignantSelect.disabled;

  if (isEnseignant) {
    if (contractRadiosContainer) {
      contractRadiosContainer.style.display = "none";
    }
    loadClassesByEnseignant(enseignantId);
    updateMatiereOptionsByEnseignantId(enseignantId);
  } else {
    contractRadios.forEach((radio) => {
      radio.addEventListener("change", function () {
        updateSelectOptions();
        clearMatiereOptions();
        clearLeconOptions();
      });
    });

    enseignantSelect.addEventListener("change", function () {
      const selectedId = this.value;
      updateMatiereOptionsByEnseignantId(selectedId);
      loadClassesByEnseignant(selectedId);
      clearLeconOptions();
    });

    updateSelectOptions();
  }

  // Pré-remplissage intelligent à l'ouverture du modal d'émargement
  const emargementModal = document.getElementById("modalCenter");
  if (emargementModal) {
    emargementModal.addEventListener("show.bs.modal", async function () {
      if (!isEnseignant) return;

      // L'enseignant est déjà pré-rempli côté PHP ou via JS, donc rien à faire ici pour le select enseignant

      try {
        // 1. Récupérez le cours actuel
        const coursResponse = await fetch(
          "index.php?url=Enseignants/get_cours_actuel"
        );
        const coursData = await coursResponse.json();

        // 2. Chargez les classes
        await loadClassesByEnseignant(enseignantId);

        // 3. Sélection automatique de la classe
        if (coursData.id_classe) {
          // Attendre que les options soient chargées
          await new Promise((resolve) => setTimeout(resolve, 300));

          // Sélectionner la bonne classe
          const optionClasse = [...classeSelect.options].find(
            (opt) => opt.value == coursData.id_classe
          );

          if (optionClasse) {
            optionClasse.selected = true;
            classeSelect.dispatchEvent(new Event("change"));

            // Attendre le chargement des matières
            await new Promise((resolve) => setTimeout(resolve, 300));

            // Sélectionner la matière
            if (coursData.id_matiere) {
              const optionMatiere = [...matiereSelect.options].find(
                (opt) => opt.value == coursData.id_matiere
              );

              if (optionMatiere) {
                optionMatiere.selected = true;
                matiereSelect.dispatchEvent(new Event("change"));

                // Calcul et affichage des heures
                if (coursData.heure_debut && coursData.heure_fin) {
                  const [h1, m1] = coursData.heure_debut.split(":");
                  const [h2, m2] = coursData.heure_fin.split(":");
                  const heures = h2 - h1 + (m2 - m1) / 60;
                  document.getElementById("nombre_heure").value =
                    formatHeures(heures);
                }
              }
            }
          }
        }
      } catch (error) {
        console.error("Erreur:", error);
      }
    });
  }

  // Charger les leçons selon classe et matière sélectionnées
  function chargerLecons() {
    const idClasse = classeSelect.value;
    const idMatiere = matiereSelect.value;
    const leconSelect = document.getElementById("id_lecon");
    if (!leconSelect) {
      console.error(
        "Erreur : le select des leçons (id_lecon) est introuvable dans le DOM !"
      );
      return;
    }
    leconSelect.innerHTML = '<option value="">Chargement...</option>';
    if (!idClasse || !idMatiere || idMatiere === "Sélectionnez une matière") {
      leconSelect.innerHTML =
        '<option value="">-- Sélectionnez une leçon --</option>';
      return;
    }
    fetch(
      `index.php?url=Enseignants/getLeconsByClasse&id_classe=${encodeURIComponent(
        idClasse
      )}&id_matiere=${encodeURIComponent(idMatiere)}`
    )
      .then((response) => response.json())
      .then((data) => {
        leconSelect.innerHTML = "";
        if (data.error) {
          leconSelect.innerHTML = `<option value="">Erreur : ${data.error}</option>`;
          return;
        }
        if (data.length === 0) {
          leconSelect.innerHTML =
            '<option value="">Aucune leçon disponible</option>';
          return;
        }
        leconSelect.innerHTML =
          '<option value="">-- Sélectionnez une leçon --</option>';
        data.forEach((lecon) => {
          const option = document.createElement("option");
          option.value = lecon.id_lecon;
          option.textContent = lecon.titre;
          leconSelect.appendChild(option);
        });
      })
      .catch((err) => {
        console.error(err);
        leconSelect.innerHTML =
          '<option value="">Erreur lors du chargement</option>';
      });
  }

  function clearLeconOptions() {
    if (leconSelect) {
      leconSelect.innerHTML =
        "<option selected disabled>-- Sélectionnez une leçon --</option>";
    }
  }

  function updateSelectOptions() {
    const selectedType =
      document.querySelector("input[name='type_contrat']:checked")?.value ||
      null;
    fetch("index.php?url=Enseignants/filtrer_enseignants", {
      method: "POST",
      body: JSON.stringify(isSupAdmin ? {} : { type: selectedType }),
      headers: { "Content-Type": "application/json" },
    })
      .then((response) => response.json())
      .then((jsonData) => {
        enseignantSelect.innerHTML =
          "<option selected disabled>Sélectionnez un enseignant</option>";
        if (Array.isArray(jsonData)) {
          jsonData.forEach((enseignant) => {
            enseignantSelect.innerHTML += `<option value="${enseignant.id_enseignant}">${enseignant.nom_prenom_enseignant}</option>`;
          });
        } else {
          console.warn("Aucun enseignant trouvé.");
        }
      })
      .catch((error) => console.error("Erreur enseignants :", error));
  }

  function updateMatiereOptionsByEnseignantId(enseignantId, classeId = null) {
    return new Promise((resolve, reject) => {
      const requestData = isSupAdmin
        ? {}
        : {
            enseignants: [enseignantId],
            classe: classeId,
          };

      fetch("index.php?url=Enseignants/filtrer_matieres", {
        method: "POST",
        body: JSON.stringify(requestData),
        headers: { "Content-Type": "application/json" },
      })
        .then((response) => response.json())
        .then((data) => {
          matiereSelect.innerHTML =
            "<option selected disabled>Sélectionnez une matière</option>";

          if (Array.isArray(data)) {
            data.forEach((matiere) => {
              matiereSelect.innerHTML += `<option value="${matiere.id_matiere}">${matiere.nom_matiere}</option>`;
            });
            resolve(data);
          } else {
            console.warn("Aucune matière trouvée.");
            resolve([]);
          }
        })
        .catch((error) => {
          console.error("Erreur lors de la récupération des matières :", error);
          reject(error);
        });
    });
  }
  classeSelect.addEventListener("change", () => {
    const enseignantId = enseignantSelect.value;
    const classeId = classeSelect.value;
    if (enseignantId && classeId) {
      updateMatiereOptionsByEnseignantId(enseignantId, classeId);
    }
    chargerLecons();
  });

  // Fonction pour charger les classes
  async function loadClassesByEnseignant(enseignantId) {
    const classeSelect = document.getElementById("classe");
    if (!classeSelect) return;

    try {
      const response = await fetch(
        "index.php?url=Enseignants/filtrer_classes",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ enseignantId }),
        }
      );

      const data = await response.json();
      classeSelect.innerHTML = '<option value="">Chargement...</option>';

      if (Array.isArray(data)) {
        classeSelect.innerHTML =
          "<option selected disabled>Sélectionnez une classe</option>";
        data.forEach((classe) => {
          const option = document.createElement("option");
          option.value = classe.id_classe;
          option.textContent = classe.nom_classe;
          classeSelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error("Erreur chargement classes:", error);
    }
  }

  // Fonction pour formater les heures
  function formatHeures(heuresDecimales) {
    const heures = Math.floor(heuresDecimales);
    const minutes = Math.round((heuresDecimales - heures) * 60);

    // Retourne "2h" pour 2.0 ou "2h30" pour 2.5
    return minutes === 0
      ? `${heures}h`
      : `${heures}h${minutes.toString().padStart(2, "0")}`;
  }

  function clearMatiereOptions() {
    matiereSelect.innerHTML =
      "<option selected disabled>Sélectionnez une matière</option>";
  }

  matiereSelect.addEventListener("change", () => {
    chargerLecons();
  });
    // Gestion de la soumission du formulaire
  const emargementForm = document.querySelector('#modalCenter form');
      if (emargementForm) {
        emargementForm.addEventListener('submit', function(e) {
          const enseignantSelect = document.getElementById('enseignant');
          
          // Si c'est un enseignant et que le select est disabled
          if (enseignantSelect && enseignantSelect.disabled) {
            // Vérifie si le champ caché existe déjà
            let hiddenInput = this.querySelector('input[name="id_enseignant"][type="hidden"]');
            
            // Si le champ n'existe pas, on le crée
            if (!hiddenInput) {
              hiddenInput = document.createElement('input');
              hiddenInput.type = 'hidden';
              hiddenInput.name = 'id_enseignant';
              this.appendChild(hiddenInput);
            }
            
            // Met à jour la valeur avec l'ID enseignant
            hiddenInput.value = enseignantSelect.value;
          }
        });
      }
  }); 


document.addEventListener("DOMContentLoaded", function () {
  // Initialisation des filtres
  const filters = {
    mois: document.getElementById("moisFilter"),
    enseignant: document.getElementById("enseignantFilter"),
    classe: document.getElementById("classeFilter"),
    matiere: document.getElementById("matiereFilter"),
  };

  // Initialisation DataTable
  let emargementTable = $("#emargementTable").DataTable({
    language: {
      url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json",
    },
    order: [[0, "desc"]],
    columns: [
      { data: "date_emargement" },
      { data: "nom_prenom_enseignant" },
      { data: "nom_classe" },
      { data: "nom_matiere" },
      {
        data: null,
        render: function (data) {
          return `
                        <div class="progress">
                            <div class="progress-bar" 
                                 style="width: ${data.progression}%">
                                ${data.progression}%
                            </div>
                        </div>
                        <small>${data.faites}/${data.prevues} leçons</small>
                    `;
        },
      },
    ],
  });

  // Initialisation des options de mois
  function initMonthFilter() {
    const months = [
      { value: "", text: "Tous les mois" },
      { value: "1", text: "Janvier" },
      { value: "2", text: "Février" },
      { value: "3", text: "Mars" },
      { value: "4", text: "Avril" },
      { value: "5", text: "Mai" },
      { value: "6", text: "Juin" },
      { value: "7", text: "Juillet" },
      { value: "8", text: "Août" },
      { value: "9", text: "Septembre" },
      { value: "10", text: "Octobre" },
      { value: "11", text: "Novembre" },
      { value: "12", text: "Décembre" },
    ];

    months.forEach((month) => {
      filters.mois.innerHTML += `<option value="${month.value}">${month.text}</option>`;
    });
  }

  // // Chargement des données
  // function loadEmargementData() {
  //   const loadingElement = document.getElementById("loadingIndicator");
  //   const errorElement = document.getElementById("errorMessage");

  //   if (loadingElement) loadingElement.style.display = "block";
  //   if (errorElement) errorElement.style.display = "none";

  //   fetch("index.php?url=Enseignants/ajax_filtre_emargement", {
  //     method: "POST",
  //     headers: {
  //       "Content-Type": "application/json",
  //       "X-Requested-With": "XMLHttpRequest",
  //     },
  //     body: JSON.stringify({
  //       mois: filters.mois.value,
  //       id_enseignant: filters.enseignant.value,
  //       id_classe: filters.classe.value,
  //       id_matiere: filters.matiere.value,
  //     }),
  //   })
  //     .then((response) => {
  //       if (!response.ok) throw new Error("Erreur réseau");
  //       return response.json();
  //     })
  //     .then((data) => {
  //       if (data.error) throw new Error(data.error);

  //       // Mise à jour DataTable
  //       emargementTable.clear().rows.add(data).draw();
  //     })
  //     .catch((error) => {
  //       console.error("Erreur:", error);
  //       if (errorElement) {
  //         errorElement.textContent = error.message;
  //         errorElement.style.display = "block";
  //       }
  //     })
  //     .finally(() => {
  //       if (loadingElement) loadingElement.style.display = "none";
  //     });
  // }
  // Chargement des données
  function loadEmargementData() {
    const loadingElement = document.getElementById("loadingIndicator");
    const errorElement = document.getElementById("errorMessage");

    if (loadingElement) loadingElement.style.display = "block";
    if (errorElement) errorElement.style.display = "none";

    // Récupérer les filtres avec vérification de null
    const filters = {
      mois: document.getElementById("moisFilter"),
      enseignant: document.getElementById("enseignantFilter"),
      classe: document.getElementById("classeFilter"),
      matiere: document.getElementById("matiereFilter"),
    };

    // Préparer les données avec vérification de null
    const requestData = {
      mois: filters.mois?.value || null,
      id_enseignant: filters.enseignant?.value || null,
      id_classe: filters.classe?.value || null,
      id_matiere: filters.matiere?.value || null,
    };

    fetch("index.php?url=Enseignants/ajax_filtre_emargement", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(requestData),
    })
      .then((response) => {
        if (!response.ok) throw new Error("Erreur réseau");
        return response.json();
      })
      .then((data) => {
        if (data.error) throw new Error(data.error);

        // Mise à jour DataTable
        if (
          window.emargementTable &&
          typeof emargementTable.clear === "function"
        ) {
          emargementTable.clear().rows.add(data).draw();
        } else {
          console.error("DataTable non initialisée");
        }
      })
      .catch((error) => {
        console.error("Erreur:", error);
        if (errorElement) {
          errorElement.textContent = error.message;
          errorElement.style.display = "block";
        }
      })
      .finally(() => {
        if (loadingElement) loadingElement.style.display = "none";
      });
  }
  // Initialisation
  initMonthFilter();

  // Écouteurs d'événements
  Object.values(filters).forEach((filter) => {
    filter.addEventListener("change", loadEmargementData);
  });

  // Premier chargement
  loadEmargementData();
});
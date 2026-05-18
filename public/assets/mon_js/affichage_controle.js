
document.addEventListener("DOMContentLoaded", function () {
  const idClasseEl = document.getElementById("id_classe");
  const idMatiereEl = document.getElementById("id_matiere");
  const idAnneeEl = document.getElementById("id_anneeScolaire");
  const tbody = document.getElementById("tableControleEleve");
  const submitBtn = document.querySelector(
    'button[name="enregistrer_controle"]'
  );
  const inputRecherche = document.getElementById("rechercheControle");
  const loader = document.getElementById("loader");

  let elevesOriginaux = [];

  function afficherLoader(show) {
    loader.style.display = show ? "block" : "none";
  }

  function afficherEleves(liste) {
    // Tri sécurisé avec gestion des valeurs nulles
    liste.sort((a, b) => {
      const nomA = (a.nom_eleve || "").toLowerCase();
      const nomB = (b.nom_eleve || "").toLowerCase();
      const prenomA = (a.prenom_eleve || "").toLowerCase();
      const prenomB = (b.prenom_eleve || "").toLowerCase();

      return (
        nomA.localeCompare(nomB, "fr", { sensitivity: "base" }) ||
        prenomA.localeCompare(prenomB, "fr", { sensitivity: "base" })
      );
    });

    tbody.innerHTML = "";

    if (liste.length > 0) {
      liste.forEach((eleve, index) => {
        const options = statutsControle
          .map(
            (st) =>
              `<option value="${st.id_controle}" ${
                st.type_controle === "retard avec excuse" ? "selected" : ""
              }>${st.type_controle}</option>`
          )
          .join("");

        const row = document.createElement("tr");
        row.setAttribute("data-id", eleve.id_eleve);
        row.innerHTML = `
                <td>${(index + 1).toString().padStart(2, "0")} - ${
          eleve.prenom_eleve || ""
        } ${eleve.nom_eleve || ""}</td>
                <td>
                    <select name="statut[${
                      eleve.id_eleve
                    }]" class="form-select form-select-sm">
                        ${options}
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm w-100 text-center" onclick="this.closest('tr').remove()">-</button>
                </td>`;
        tbody.appendChild(row);
      });
      submitBtn.disabled = false;
    } else {
      tbody.innerHTML = `<tr><td colspan="3" class="text-muted text-center">Aucun élève trouvé.</td></tr>`;
    }
  }

 

  function chargerEleves() {
    const idClasse = idClasseEl.value;
    const idMatiere = idMatiereEl.value;
    const idAnnee = idAnneeEl.value;

    tbody.innerHTML = "";
    submitBtn.disabled = true;
    elevesOriginaux = [];

    if (idClasse && idMatiere && idAnnee) {
      afficherLoader(true);

      fetch(`${ROOT}/Controles/get_eleves`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id_classe: idClasse,
          id_matiere: idMatiere,
          id_annee: idAnnee,
        }),
      })
        .then((res) => {
          if (!res.ok) throw new Error("Erreur réseau");
          return res.json();
        })
        .then((data) => {
          elevesOriginaux = data || [];
          afficherEleves(elevesOriginaux);
        })
        .catch((error) => {
          console.error("❌ Erreur lors du chargement des élèves :", error);
          tbody.innerHTML = `<tr><td colspan="3" class="text-danger text-center">Erreur lors du chargement.</td></tr>`;
        })
        .finally(() => {
          afficherLoader(false);
        });
    } else {
      tbody.innerHTML = `<tr><td colspan="3" class="text-warning text-center"><span style="font-size:1.5em;">🥰</span><br> 
                                                    <span style="color: #28a745;">Sélectionnez une classe et une matière et les élèves apparaîtront !</span></td></tr>`;
    }
  }

 
  function filtrerEleves() {
    const recherche = inputRecherche.value.trim().toLowerCase();
    afficherLoader(true);

    setTimeout(() => {
      const resultats = elevesOriginaux.filter((eleve) => {
        // Construction du nom complet à partir des champs séparés
        const nomComplet = `${eleve.prenom_eleve || ""} ${
          eleve.nom_eleve || ""
        }`.toLowerCase();
        return nomComplet.includes(recherche);
      });
      afficherEleves(resultats);
      afficherLoader(false);
    }, 200);
  }

  idClasseEl.addEventListener("change", chargerEleves);
  idMatiereEl.addEventListener("change", chargerEleves);
  idAnneeEl.addEventListener("change", chargerEleves);
  inputRecherche.addEventListener("input", filtrerEleves);
});
// Gestion de l'envoi du formulaire
document.querySelector("form").addEventListener("submit", async function (e) {
  e.preventDefault();

  // Récupération des valeurs communes à tous les élèves
  const id_classe = document.getElementById("id_classe").value;
  const id_matiere = document.getElementById("id_matiere").value;
  const id_annee_scolaire = document.getElementById("id_anneeScolaire").value;
  const id_trimestre = document.getElementById("id_trimestre").value;
  const id_ecole = document.getElementById("id_ecole").value; // doit être un id valide !
  const date = document.getElementById("date").value;
  const libelle = document.getElementById("libelle").value;
  const heure_debut = document.getElementById("heure_debut").value;
  const heure_fin = document.getElementById("heure_fin").value;
  const notifier_parent = document.getElementById("notifier_parent").checked
    ? 1
    : 0;

  // Construction du tableau d'élèves à envoyer
  const data = [];
  document
    .querySelectorAll("#tableControleEleve tr[data-id]")
    .forEach((row) => {
      const id_eleve = row.dataset.id;
      const statut_eleve = row.querySelector("select").value;

      data.push({
        id_eleve: id_eleve,
        id_classe: id_classe,
        id_matiere: id_matiere,
        id_annee_scolaire: id_annee_scolaire,
        id_trimestre: id_trimestre,
        id_ecole: id_ecole,
        date: date,
        libelle: libelle,
        heure_debut: heure_debut,
        heure_fin: heure_fin,
        notifier_parent: notifier_parent,
        statut: statut_eleve,
      });
    });

  // Vérification avant envoi
  if (data.length === 0) {
    alert("Aucun élève à enregistrer.");
    return;
  }

  const formData = { data: data };

  console.log("📤 Données envoyées au serveur :", formData);

  try {
    const response = await fetch(`${ROOT}/Controles/ajouter_controle`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData),
    });

    const result = await response.json();

    console.log("📥 Réponse serveur :", result);

    if (result.success) {
      alert(result.message);
      window.location.reload();
    } else {
      alert(
        "❌ Erreur : " +
          result.message +
          (result.errors ? "\n" + result.errors.join("\n") : "")
      );
    }
  } catch (error) {
    console.error("❌ Erreur de communication :", error);
    alert("Erreur de communication avec le serveur.");
  }
});


 
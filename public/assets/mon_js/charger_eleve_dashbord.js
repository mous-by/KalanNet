function chargerElevesSiSelectionValide() {
  const idClasse = document.getElementById("id_classe").value;
  const idAnnee = document.getElementById("id_anneeScolaire").value;
  const idTrimestre = document.getElementById("id_trimestre").value;
  const eleveSelect = document.getElementById("id_eleve");

  // Vérifie les sélections
  if (!idClasse || !idAnnee || !idTrimestre) {
    eleveSelect.innerHTML = `<option value="">Sélectionner un Elève</option>`;
    return;
  }

  eleveSelect.innerHTML = `<option value="">Chargement...</option>`;

  fetch(`${ROOT}/Controles/getElevesParClasseAjax`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      id_classe: idClasse,
      id_annee_scolaire: idAnnee,
      id_trimestre: idTrimestre,
    }),
  })
    .then((response) => {
      if (!response.ok) throw new Error("Erreur réseau");
      return response.json();
    })
    .then((data) => {
      if (
        data.success &&
        Array.isArray(data.eleves) &&
        data.eleves.length > 0
      ) {
        eleveSelect.innerHTML = `<option value="">Sélectionner un Elève</option>`;
        data.eleves.forEach((el) => {
          const opt = document.createElement("option");
          opt.value = el.id_eleve;
          opt.textContent = `${el.prenom_eleve} ${el.nom_eleve}`;
          eleveSelect.appendChild(opt);
        });
      } else {
        eleveSelect.innerHTML = `<option value="">Aucun élève trouvé</option>`;
      }
    })
    .catch((err) => {
      console.error("Erreur chargement:", err);
      eleveSelect.innerHTML = `<option value="">Erreur chargement</option>`;
    });
}
// Ajoute les écouteurs
["id_classe", "id_anneeScolaire", "id_trimestre"].forEach((id) => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener("change", chargerElevesSiSelectionValide);
  }
});

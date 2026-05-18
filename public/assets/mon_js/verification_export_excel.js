document.querySelector(".export-fiche").addEventListener("click", function (e) {
  e.preventDefault();

  const idClasse = document.getElementById("id_classe").value;
  const idMatiere = document.getElementById("id_matiere").value;
  const idAnnee = document.getElementById("id_anneeScolaire").value;
  const idTrimestre = document.getElementById("id_trimestre").value;

  if (!idClasse || !idMatiere || !idAnnee || !idTrimestre) {
    const modal = new bootstrap.Modal(
      document.getElementById("modalAlerteExport")
    );
    document.getElementById("modalAlerteExportMsg").textContent =
      "Veuillez sélectionner la classe, la matière, l'année scolaire et le trimestre avant d'exporter !";
    modal.show();
    return;
  }

  const url = `${ROOT}/Controles/exportFicheControle?id_classe=${encodeURIComponent(
    idClasse
  )}&id_matiere=${encodeURIComponent(
    idMatiere
  )}&id_annee_scolaire=${encodeURIComponent(
    idAnnee
  )}&id_trimestre=${encodeURIComponent(idTrimestre)}`;
  window.location.href = url;
});

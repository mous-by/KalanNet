let conduiteChart = null;

function chargerEtAfficherConduite() {
  const idEleve = document.getElementById("id_eleve").value;
  if (idEleve) {
    afficherCourbeConduiteEleve(idEleve);
  } else {
    genererEtAfficherConduiteClasse();
  }
}

function genererEtAfficherConduiteClasse() {
  const idClasse = document.getElementById("id_classe").value;
  const idAnnee = document.getElementById("id_anneeScolaire").value;
  const idTrimestre = document.getElementById("id_trimestre").value;

  if (!idClasse || !idAnnee || !idTrimestre) {
    document.getElementById("resultats_conduite").innerHTML = "";
    return;
  }

  fetch(`${ROOT}/Controles/genererConduitesClasse`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      id_classe: idClasse,
      id_annee: idAnnee,
      id_trimestre: idTrimestre,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success && Array.isArray(data.resultats)) {
        afficherTableauConduiteClasse(data.resultats);
      } else {
        afficherErreur(data.message || "Erreur lors du calcul des conduites");
      }
    })
    .catch(() => {
      afficherErreur("Erreur de communication avec le serveur.");
    });
}

function afficherCourbeConduiteEleve(idEleve) {
  fetch(`${ROOT}/Controles/getConduiteEleve?id_eleve=${idEleve}`)
    .then((res) => res.json())
    .then((data) => {
      if (
        data.success &&
        Array.isArray(data.historique) &&
        data.historique.length > 0
      ) {
        creerGraphiqueConduite(data.historique);
      } else {
        afficherInfo("Aucun historique de conduite pour cet élève");
      }
    })
    .catch(() => afficherErreur("Erreur lors du chargement de l'historique"));
}

function afficherTableauConduiteClasse(conduites) {
  if (conduiteChart) {
    conduiteChart.destroy();
    conduiteChart = null;
  }

  if (!conduites || conduites.length === 0) {
    document.getElementById(
      "resultats_conduite"
    ).innerHTML = `<div class="alert alert-info">Aucun élève trouvé</div>`;
    return;
  }

  const html = `
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Note de conduite</th>
                    </tr>
                </thead>
                <tbody>
                    ${conduites
                      .map(
                        (e) => `
                        <tr>
                            <td>${e.nom_complet}</td>
                            <td>
                                <span class="badge ${getCouleurNote(
                                  e.note_conduite
                                )}">
                                    ${parseFloat(e.note_conduite).toFixed(2)}
                                </span>
                            </td>
                        </tr>`
                      )
                      .join("")}
                </tbody>
            </table>
        </div>`;
  document.getElementById("resultats_conduite").innerHTML = html;
}

function creerGraphiqueConduite(historique) {
  document.getElementById("resultats_conduite").innerHTML = `
        <div class="chart-container" style="position: relative; height:400px; width:100%">
            <canvas id="canvasConduite"></canvas>
        </div>`;

  if (conduiteChart) {
    conduiteChart.destroy();
  }

  const labels = historique.map((item) => item.periode);
  const dataPoints = historique.map((item) => item.note_conduite);

  const ctx = document.getElementById("canvasConduite").getContext("2d");
  conduiteChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Note de conduite",
          data: dataPoints,
          borderColor: "#4e73df",
          backgroundColor: "rgba(78, 115, 223, 0.05)",
          pointBackgroundColor: dataPoints.map((n) => getCouleurNoteRGBA(n)),
          pointBorderColor: "#fff",
          pointRadius: 4,
          borderWidth: 2,
          fill: true,
        },
      ],
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        y: {
          min: 6,
          max: 18,
          title: {
            display: true,
            text: "Note de conduite",
          },
        },
        x: {
          title: {
            display: true,
            text: "Périodes",
          },
        },
      },
      plugins: {
        legend: { display: false },
      },
    },
  });
}

function getCouleurNote(note) {
  if (note >= 16) return "bg-success";
  if (note >= 12) return "bg-warning";
  return "bg-danger";
}

function getCouleurNoteRGBA(note) {
  if (note >= 16) return "rgba(40, 167, 69, 1)";
  if (note >= 12) return "rgba(255, 193, 7, 1)";
  return "rgba(220, 53, 69, 1)";
}

function afficherErreur(message) {
  document.getElementById(
    "resultats_conduite"
  ).innerHTML = `<div class="alert alert-danger">${message}</div>`;
}

function afficherInfo(message) {
  document.getElementById(
    "resultats_conduite"
  ).innerHTML = `<div class="alert alert-info">${message}</div>`;
}

document.addEventListener("DOMContentLoaded", function () {
  chargerEtAfficherConduite();
  ["id_classe", "id_anneeScolaire", "id_trimestre", "id_eleve"].forEach(
    (id) => {
      document
        .getElementById(id)
        .addEventListener("change", chargerEtAfficherConduite);
    }
  );
});

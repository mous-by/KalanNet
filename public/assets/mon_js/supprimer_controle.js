// document
//   .getElementById("btnSupprimerSelection")
//   .addEventListener("click", function (e) {
//     e.preventDefault();
//     const checked = document.querySelectorAll(".select-eleve:checked");
//     if (checked.length === 0) {
//       alert("Veuillez sélectionner au moins un contrôle à supprimer.");
//       return;
//     }
//     const modalConfirm = new bootstrap.Modal(
//       document.getElementById("modalConfirmSupp")
//     );
//     modalConfirm.show();
//     document.getElementById("confirmSuppBtn").onclick = function () {
//       const ids = Array.from(checked).map(
//         (cb) => cb.closest("tr").querySelector(".btn-edit-controle").dataset.id
//       );

//       fetch(`${ROOT}/Controles/supprimerControlesAjax`, {
//         method: "POST",
//         headers: { "Content-Type": "application/json" },
//         body: JSON.stringify({ ids }),
//       })
//         .then((res) => res.json())
//         .then((resp) => {
//           bootstrap.Modal.getInstance(
//             document.getElementById("modalConfirmSupp")
//           ).hide();

//           if (resp.success) {
//             const modalSuccessEl = document.getElementById("modalSuccess");
//             const modalSuccess = new bootstrap.Modal(modalSuccessEl);
//             document.getElementById("modalSuccessMsg").textContent =
//               resp.message || "Suppression réussie !";

//             modalSuccess.show();

//             // Utilise l'événement `shown.bs.modal` pour gérer le hide
//             modalSuccessEl.addEventListener(
//               "shown.bs.modal",
//               () => {
//                 setTimeout(() => {
//                   modalSuccess.hide();
//                   chargerControles(); // Recharge proprement APRÈS que le modal ait disparu
//                 }, 1500);
//               },
//               { once: true }
//             );
//           } else {
//             alert(resp.message || "Erreur lors de la suppression.");
//           }
//         })
//         .catch(() => {
//           alert("Erreur lors de la suppression (réseau ou serveur).");
//         });
//     };
//   });
document
  .getElementById("btnSupprimerSelection")
  .addEventListener("click", function (e) {
    e.preventDefault();
    const checked = document.querySelectorAll(".select-eleve:checked");

    if (checked.length === 0) {
      const modalErreur = new bootstrap.Modal(
        document.getElementById("modalErreurSupp")
      );
      document.getElementById("modalErreurSuppMsg").textContent =
        "Veuillez sélectionner au moins un contrôle à supprimer.";
      modalErreur.show();
      return;
    }

    const modalConfirm = new bootstrap.Modal(
      document.getElementById("modalConfirmSupp")
    );
    modalConfirm.show();

    document.getElementById("confirmSuppBtn").onclick = function () {
      const ids = Array.from(checked).map(
        (cb) => cb.closest("tr").querySelector(".btn-edit-controle").dataset.id
      );

      fetch(`${ROOT}/Controles/supprimerControlesAjax`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ids }),
      })
        .then((res) => res.json())
        .then((resp) => {
          bootstrap.Modal.getInstance(
            document.getElementById("modalConfirmSupp")
          ).hide();

          if (resp.success) {
            const modalSuccessEl = document.getElementById("modalSuccess");
            const modalSuccess = new bootstrap.Modal(modalSuccessEl);
            document.getElementById("modalSuccessMsg").textContent =
              resp.message || "Suppression réussie !";

            modalSuccess.show();

            modalSuccessEl.addEventListener(
              "shown.bs.modal",
              () => {
                setTimeout(() => {
                  modalSuccess.hide();
                  chargerControles();
                }, 1500);
              },
              { once: true }
            );
          } else {
            const modalErreur = new bootstrap.Modal(
              document.getElementById("modalErreurSupp")
            );
            document.getElementById("modalErreurSuppMsg").textContent =
              resp.message || "Erreur lors de la suppression.";
            modalErreur.show();
          }
        })
        .catch(() => {
          const modalErreur = new bootstrap.Modal(
            document.getElementById("modalErreurSupp")
          );
          document.getElementById("modalErreurSuppMsg").textContent =
            "Erreur lors de la suppression (réseau ou serveur).";
          modalErreur.show();
        });
    };
  });

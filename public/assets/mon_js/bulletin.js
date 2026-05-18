function imprimerBulletin(idEleve, idanne, idtrimestre, nomBulletin = "bulletin") {
const ordre = document.querySelector("#ordreEnseignement")?.value;
const id_annee = document.querySelector("#id_annee")?.value;
const id_trimestre = ordre !== 'fondamentale1' ? document.querySelector("#id_trimestre")?.value : null;
const mois = ordre === 'fondamentale1' ? document.querySelector("#mois")?.value : null;
console.log({ordre, id_annee, id_trimestre, mois});
   if (!id_annee || (ordre === 'fondamentale1' && !mois) || (ordre !== 'fondamentale1' && !id_trimestre)) {
    alert("Veuillez sélectionner l'année scolaire et la période (mois ou trimestre).");
    return;
}


    $("#loader").removeClass("d-none").addClass("d-flex");

    $.ajax({
        method: "POST",
        url: `http://localhost/gestion_de_scolarite/public/Classes/appercu_bulletin/${idEleve}`,
        data: {
            action: "print",
            id_annee,
            id_trimestre,
            mois,
            ordre,

        },
        success: function (response) {
            if (!response || response.trim() === "") {
                alert("Aucun contenu de bulletin trouvé.");
                $("#loader").removeClass("d-flex").addClass("d-none");
                return;
            }

            let container = document.createElement('div');
            container.id = 'pdf-container';
            container.style.margin = '0';
            container.style.padding = '0';
            container.style.backgroundColor = 'white';
            container.style.boxSizing = 'border-box';
            container.style.fontFamily = 'Arial, sans-serif';
            container.innerHTML = response;
            document.body.appendChild(container);

            // Fenêtre temporaire avec spinner
            let newWindow = window.open('', '_blank', 'width=700,height=900');
            if (!newWindow) {
                alert("Merci d'autoriser les popups.");
                document.body.removeChild(container);
                $("#loader").removeClass("d-flex").addClass("d-none");
                return;
            }

            newWindow.document.write(`
                <html>
                    <head><title>Chargement...</title></head>
                    <body style="display:flex;justify-content:center;align-items:center;height:100vh;">
                        <div>
                            <p>Génération du bulletin en cours...</p>
                            <div class="loader" style="
                                border: 6px solid #f3f3f3;
                                border-top: 6px solid #3498db;
                                border-radius: 50%;
                                width: 40px;
                                height: 40px;
                                animation: spin 1s linear infinite;
                                margin: 0 auto;
                            "></div>
                        </div>
                        <style>
                            @keyframes spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                        </style>
                    </body>
                </html>
            `);
            newWindow.document.close();

            setTimeout(() => {
                html2pdf()
                    .from(container)
                    .set({
                        margin: 0, // Enlève toutes les marges
                        filename: nomBulletin + '.pdf',
                        html2canvas: { scale: 2, useCORS: true },
                        jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' }
                    })
                    .outputPdf('blob')
                    .then(pdfBlob => {
                        const pdfUrl = URL.createObjectURL(pdfBlob);
                        newWindow.location.href = pdfUrl;
                        document.body.removeChild(container);
                        $("#loader").removeClass("d-flex").addClass("d-none");
                    })
                    .catch(error => {
                        console.error("Erreur génération PDF :", error);
                        alert("Erreur lors de la génération du PDF.");
                        document.body.removeChild(container);
                        newWindow.close();
                        $("#loader").removeClass("d-flex").addClass("d-none");
                    });
            }, 300);
        },
        error: function (error) {
            console.error('Erreur AJAX :', error);
            alert("Impossible de récupérer le bulletin.");
            $("#loader").removeClass("d-flex").addClass("d-none");
        }
    });
}



function imprimer(nomBulletin, html = null) {
    $("#loader").removeClass("d-none").addClass("d-flex");

    if (html == null) {
        html = document.getElementById("edt");
    }

    // Capitaliser le nom du fichier
    nomBulletin = nomBulletin
        .split("-")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join("-");

    // Création d'un conteneur propre pour éviter les marges
    let container = document.createElement('div');
    container.id = "pdf-impression-container";
    container.style.margin = "0";
    container.style.padding = "0";
    container.style.backgroundColor = "white";
    container.style.boxSizing = "border-box";
    container.style.fontFamily = "Arial, sans-serif";
    container.innerHTML = html.innerHTML;
    document.body.appendChild(container);

    html2pdf()
        .from(container)
        .set({
            margin: 0, // ← aucune marge autour
            filename: nomBulletin + ".pdf",
            html2canvas: { scale: 2 },
            jsPDF: { unit: "mm", format: "a5", orientation: "portrait" },
        })
        .outputPdf('bloburl')
        .then((pdfUrl) => {
            window.open(pdfUrl, '_blank');
            document.body.removeChild(container); // Nettoyage
            $("#loader").removeClass("d-flex").addClass("d-none");
        })
        .catch(error => {
            console.error("Erreur PDF :", error);
            alert("Erreur lors de la génération du PDF.");
            document.body.removeChild(container);
            $("#loader").removeClass("d-flex").addClass("d-none");
        });
}



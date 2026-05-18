function imprimer(nomFichier, contenuHTML = null) {
    if (!contenuHTML) {
        contenuHTML = document.getElementById("emploi");
    }

    const header = document.getElementById("header_ecole");
    const thead = document.getElementById("emploi_du_temps_thead");

    // Ajoute des classes CSS pour l'impression seulement
    if (header) header.classList.add("print-visible");
    if (thead) thead.classList.add("print-thead-visible");

    // Force perfect landscape scale during pdf generation
    const originalWidth = contenuHTML.style.width;
    const originalMaxWidth = contenuHTML.style.maxWidth;
    const originalMargin = contenuHTML.style.margin;
    const originalPadding = contenuHTML.style.padding;

    contenuHTML.style.width = "100%";
    contenuHTML.style.maxWidth = "1120px";
    contenuHTML.style.margin = "0 auto";
    contenuHTML.style.padding = "5px";

    html2pdf()
        .set({
            margin: [5, 5, 5, 5],
            filename: nomFichier,
            html2canvas: { 
                scale: 2, 
                useCORS: true, 
                logging: false,
                letterRendering: true
            },
            jsPDF: { unit: "mm", format: "a4", orientation: "landscape" },
            pagebreak: { mode: ['avoid-all'] }
        })
        .from(contenuHTML)
        .save()
        .then(() => {
            // Restore original styles
            contenuHTML.style.width = originalWidth;
            contenuHTML.style.maxWidth = originalMaxWidth;
            contenuHTML.style.margin = originalMargin;
            contenuHTML.style.padding = originalPadding;

            if (header) header.classList.remove("print-visible");
            if (thead) thead.classList.remove("print-thead-visible");
            $("#loader").removeClass("d-flex").addClass("d-none");
        });
}

// /* ============================
//    ✅ DETECTER LE ROLE
// ============================= */
// function getRoleUtilisateur(droit) {
//     if (!droit) return "Utilisateur";

//     switch (droit) {
//         case "DCAP": return "Directeur de CAP";
//         case "DAE": return "Directeur d'Académie";
//         case "Admin": return "Administrateur";
//         case "Gestionnaire": return "Gestionnaire";
//         case "Enseignant": return "Enseignant";
//         case "Parent": return "Parent";
//         default: return droit;
//     }
// }

// /* ============================
//    ✅ TELECHARGER FICHIER (NE PAS FERMER SweetAlert)
// ============================= */
// function telechargerFichier(id, type) {
//     const url = ROOT + "/Homes/telecharger_fichier?id=" + id + "&type=" + type;

//     const a = document.createElement("a");
//     a.href = url;
//     a.setAttribute("download", "");
//     document.body.appendChild(a);
//     a.click();
//     a.remove();
// }

// /* ============================
//    ✅ AFFICHER LES ANNONCES NON LUES
// ============================= */
// function afficherAnnoncesNonLues() {

//     fetch(ROOT + "/Homes/get_annonces_non_lues")
//         .then(res => res.text())
//         .then(text => {

//             let annonces;
//             try { annonces = JSON.parse(text); }
//             catch (e) {
//                 console.warn("JSON invalide", text);
//                 return;
//             }

//             if (!Array.isArray(annonces) || annonces.length === 0) return;

//             Swal.fire({
//                 title: ' 📢 Nouvelles Annonces ',
//                 html: genererHTMLAnnonces(annonces),
//                 width: '900px',
//                 background: "#f8f9fc",
//                 showConfirmButton: true,
//                 confirmButtonText: "Marquer comme lues ✅",
//                 confirmButtonColor: "#4e73df",
//                 customClass: {
//                     popup: "popup-annonce"
//                 },
//                 allowOutsideClick: false
//             }).then(() => {
//                 annonces.forEach(a => {
//                     marquerAnnonceLue(a.id_annonce, a.source);
//                 });
//             });
//         });
// }

// /* ============================
//    ✅ GENERATION DES CARTES D’ANNONCES
// ============================= */
// function genererHTMLAnnonces(annonces) {

//     let html = `
//         <style>
//             .annonce-card {
//                 padding: 15px;
//                 margin-bottom: 15px;
//                 border-radius: 10px;
//                 background: #ffffff;
//                 border-left: 6px solid #4e73df;
//                 box-shadow: 0 2px 4px rgba(0,0,0,0.08);
//             }
//             .annonce-card.academie { border-left-color: #1cc88a; }
//             .annonce-card.admin_gestionnaire { border-left-color: #e74a3b; }
//             .annonce-title {
//                 font-size: 18px;
//                 font-weight: bold;
//                 color: #2c3e50;
//                 margin-bottom: 6px;
//             }
//             .annonce-content {
//                 font-size: 14px;
//                 margin-bottom: 10px;
//             }
//             .badge-source {
//                 font-size: 11px;
//                 padding: 3px 8px;
//                 border-radius: 4px;
//                 margin-left: 8px;
//             }
//             .badge-cap { background: #4e73df; }
//             .badge-academie { background: #1cc88a; }
//             .badge-admin { background: #e74a3b; }
//             .btn-download {
//                 padding: 7px 14px;
//                 background: #4e73df;
//                 color: white;
//                 font-size: 13px;
//                 border-radius: 5px;
//                 cursor: pointer;
//                 display: inline-block;
//                 margin-top: 8px;
//             }
//             .btn-download:hover {
//                 background: #2e59d9;
//             }
//         </style>

//         <div style="max-height:450px; overflow-y:auto; padding-right:5px;">
//     `;

//     annonces.forEach(a => {

//         const roleAffiché = getRoleUtilisateur(a.droit);
        
//         // Déterminer la classe CSS en fonction de la source
//         let classe = "";
//         let badgeSource = "";
//         switch(a.source) {
//             case "academie":
//                 classe = "academie";
//                 badgeSource = '<span class="badge-source badge-academie">Académie</span>';
//                 break;
//             case "admin_gestionnaire":
//                 classe = "admin_gestionnaire";
//                 badgeSource = '<span class="badge-source badge-admin">École</span>';
//                 break;
//             default:
//                 classe = "cap";
//                 badgeSource = '<span class="badge-source badge-cap">CAP</span>';
//         }

//         let telecharger = "";
//         if (a.fichier_joint) {
//             telecharger = `
//                 <button class="btn-download"
//                         onclick="telechargerFichier('${a.id_annonce}', '${a.source}')">
//                     Télécharger le fichier joint
//                 </button>
//             `;
//         }

//         html += `
//             <div class="annonce-card ${classe}">
//                 <div class="annonce-title">
//                     <i class="fas fa-microphone" style="color:#4e73df;"></i>
//                     ${a.titre}
//                     ${badgeSource}
//                 </div>

//                 <div class="annonce-content">${a.contenu}</div>

//                 <div>
//                     <small>
//                         <b>${a.nomPrenom}</b> — ${roleAffiché}<br>
//                         <span style="color:gray">
//                             ${new Date(a.date_publication).toLocaleString()}
//                         </span>
//                     </small>
//                 </div>

//                 ${telecharger}
//             </div>
//         `;
//     });

//     html += "</div>";

//     return html;
// }

// /* ============================
//    ✅ MARQUER COMME LUE
// ============================= */
// function marquerAnnonceLue(id_annonce, type_annonce) {

//     fetch(ROOT + "/Homes/marquer_annonce_lue", {
//         method: "POST",
//         headers: {"Content-Type": "application/x-www-form-urlencoded"},
//         body: `id_annonce=${id_annonce}&type_annonce=${type_annonce}`
//     });
// }


/* ============================
   ✅ DETECTER LE ROLE
============================= */
function getRoleUtilisateur(droit) {
    if (!droit) return "Utilisateur";

    switch (droit) {
        case "DCAP": return "Directeur de CAP";
        case "DAE": return "Directeur d'Académie";
        case "Admin": return "Administrateur";
        case "Gestionnaire": return "Gestionnaire";
        case "Enseignant": return "Enseignant";
        case "Parent": return "Parent";
        default: return droit;
    }
}

/* ============================
   ✅ TELECHARGER FICHIER (NE PAS FERMER SweetAlert)
============================= */
function telechargerFichier(id, type) {
    const url = ROOT + "/Homes/telecharger_fichier?id=" + id + "&type=" + type;

    const a = document.createElement("a");
    a.href = url;
    a.setAttribute("download", "");
    document.body.appendChild(a);
    a.click();
    a.remove();
}

/* ============================
   ✅ AFFICHER LES ANNONCES NON LUES
============================= */
function afficherAnnoncesNonLues() {
    fetch(ROOT + "/Homes/get_annonces_non_lues")
        .then(res => res.text())
        .then(text => {
            let annonces;
            try { 
                annonces = JSON.parse(text); 
            } catch (e) {
                console.warn("JSON invalide", text);
                return;
            }

            if (!Array.isArray(annonces) || annonces.length === 0) return;

            Swal.fire({
                title: ' 📢 Nouvelles Annonces ',
                html: genererHTMLAnnonces(annonces),
                width: '900px',
                background: "#f8f9fc",
                showConfirmButton: true,
                confirmButtonText: "Marquer comme lues ✅",
                confirmButtonColor: "#4e73df",
                customClass: {
                    popup: "popup-annonce"
                },
                allowOutsideClick: false
            }).then(() => {
                annonces.forEach(a => {
                    marquerAnnonceLue(a.id_annonce, a.source);
                });
            });
        })
        .catch(error => {
            console.error("Erreur lors du chargement des annonces:", error);
        });
}

/* ============================
   ✅ GENERATION DES CARTES D'ANNONCES
============================= */

function genererHTMLAnnonces(annonces) {
    let html = `
        <style>
            .annonce-card {
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 10px;
                background: #ffffff;
                border-left: 6px solid #4e73df;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            }
            .annonce-card.academie { border-left-color: #1cc88a; }
            .annonce-card.admin_gestionnaire { border-left-color: #e74a3b; }
            .annonce-title {
                font-size: 18px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 6px;
            }
            .annonce-content {
                font-size: 14px;
                margin-bottom: 10px;
                line-height: 1.4;
            }
            .badge-source {
                font-size: 11px;
                padding: 3px 8px;
                border-radius: 4px;
                margin-left: 8px;
                color: white;
            }
            .badge-cap { background: #4e73df; }
            .badge-academie { background: #1cc88a; }
            .badge-admin { background: #e74a3b; }
            .badge-public {
                font-size: 10px;
                padding: 2px 6px;
                border-radius: 3px;
                margin-left: 5px;
                background: #6c757d;
                color: white;
            }
            .btn-download {
                padding: 7px 14px;
                background: #4e73df;
                color: white;
                font-size: 13px;
                border-radius: 5px;
                cursor: pointer;
                display: inline-block;
                margin-top: 8px;
                border: none;
                text-decoration: none;
            }
            .btn-download:hover {
                background: #2e59d9;
            }
            .annonce-meta {
                margin-top: 8px;
                font-size: 12px;
                color: #6c757d;
            }
            .annonce-auteur {
                font-weight: bold;
                color: #2c3e50;
            }
        </style>

        <div style="max-height:450px; overflow-y:auto; padding-right:5px;">
    `;

    annonces.forEach(a => {
        // Utiliser la fonction de l'utilisateur si elle existe, sinon utiliser le droit
        const fonctionAffichée = a.fonction || getRoleUtilisateur(a.droit);
        
        // Déterminer la classe CSS en fonction de la source
        let classe = "";
        let badgeSource = "";
        switch(a.source) {
            case "academie":
                classe = "academie";
                badgeSource = '<span class="badge-source badge-academie">Académie</span>';
                break;
            case "admin_gestionnaire":
                classe = "admin_gestionnaire";
                badgeSource = '<span class="badge-source badge-admin">École</span>';
                break;
            default:
                classe = "cap";
                badgeSource = '<span class="badge-source badge-cap">CAP</span>';
        }

        // Badge pour le public cible
        const publicCibleLabels = {
            'tous': '🌍 Tous',
            'admin': '👨‍💼 Admin',
            'gestionnaires': '📊 Gestionnaires',
            'enseignants': '👨‍🏫 Enseignants',
            'parents': '👪 Parents',
            'dcap': '🎯 DCAP',
            'dae': '🏛️ DAE'
        };
        const publicCible = publicCibleLabels[a.public_cible] || a.public_cible;
        const badgePublic = `<span class="badge-public">${publicCible}</span>`;

        let telecharger = "";
        if (a.fichier_joint && a.fichier_joint !== '') {
            telecharger = `
                <button class="btn-download"
                        onclick="telechargerFichier('${a.id_annonce}', '${a.source}')">
                    <i class="fas fa-download"></i> Télécharger le fichier joint
                    <small style="margin-left: 5px;">(${a.type_fichier || 'Fichier'})</small>
                </button>
            `;
        }

        html += `
            <div class="annonce-card ${classe}">
                <div class="annonce-title">
                    
                    ${a.titre}
                    ${badgeSource}
                    ${badgePublic}
                </div>

                <div class="annonce-content">${a.contenu}</div>

                ${telecharger}

                <div class="annonce-meta">
                    <span class="annonce-auteur">${a.nomPrenom}</span> — ${fonctionAffichée}<br>
                    <span style="color:gray">
                        ${new Date(a.date_publication).toLocaleString('fr-FR')}
                    </span>
                </div>
            </div>
        `;
    });

    html += "</div>";
    return html;
}


/* ============================
   ✅ MARQUER COMME LUE
============================= */
function marquerAnnonceLue(id_annonce, type_annonce) {
    console.log("📝 Marquage annonce comme lue:", { 
        id_annonce, 
        type_annonce,
        ID_UTILISATEUR: window.ID_UTILISATEUR 
    });

    if (!window.ID_UTILISATEUR) {
        console.error("❌ ID_UTILISATEUR non défini");
        return;
    }

    const params = new URLSearchParams();
    params.append('id_annonce', id_annonce);
    params.append('type_annonce', type_annonce);
    // Note: id_utilisateur n'est plus nécessaire car récupéré de la session

    fetch(ROOT + "/Homes/marquer_annonce_lue", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params
    })
    .then(response => {
        console.log("📥 Statut HTTP:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("✅ Réponse serveur:", data);
        if (data.success) {
            console.log("✅ Annonce marquée comme lue avec succès");
        } else {
            console.error("❌ Erreur du serveur:", data.error);
        }
    })
    .catch(error => {
        console.error("❌ Erreur fetch:", error);
    });
}

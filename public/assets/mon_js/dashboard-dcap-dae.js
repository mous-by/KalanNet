// dashboard-dcap-dae.js
class DashboardDCAPDAE {
    constructor() {
        this.ROOT = window.ROOT || '';
        this.init();
    }

    init() {
        $(document).ready(() => {
            this.initialiserDashboard();
            this.initialiserEvenements();
        });
    }

    // ==================== MÉTHODES COMMUNES ====================

    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: message,
            confirmButtonText: 'OK'
        });
    }

    showWarning(message) {
        Swal.fire({
            icon: 'warning',
            title: 'Attention',
            text: message,
            confirmButtonText: 'OK'
        });
    }

    getLabelPublicCible(publicCible) {
        const labels = {
            'tous': '🌍 Tous',
            'admin': '👨‍💼 Admin',
            'gestionnaires': '📊 Gestionnaires',
            'enseignants': '👨‍🏫 Enseignants',
            'parents': '👪 Parents',
            'dcap': '🎯 DCAP',
            'dae': '🏛️ DAE'
        };
        return labels[publicCible] || publicCible;
    }

    // ==================== FONCTIONS DCAP ====================

    chargerStatsDCAP() {
        console.log("🔍 Chargement des stats DCAP...");

        // Afficher un indicateur de chargement
        $('#total-ecoles-dcap').html('<i class="bi bi-arrow-repeat spinner"></i>');
        $('#total-enseignants-dcap').html('<i class="bi bi-arrow-repeat spinner"></i>');
        $('#total-eleves-dcap').html('<i class="bi bi-arrow-repeat spinner"></i>');
        $('#total-classes-dcap').html('<i class="bi bi-arrow-repeat spinner"></i>');

        $.get(`${this.ROOT}/Homes/get_stats_dcap`)
            .done((response) => {
                console.log("✅ Données DCAP reçues:", response);

                if (response.error) {
                    console.error("❌ Erreur DCAP:", response.error);
                    $('#liste-ecoles-dcap').html(`
                        <div class="alert alert-warning">
                            <strong>Attention:</strong> ${response.error}
                        </div>
                    `);
                    this.resetStatsDCAP();
                    return;
                }

                // Mettre à jour les KPI
                $('#total-ecoles-dcap').text(response.total_ecoles || 0);
                $('#total-enseignants-dcap').text(response.total_enseignants || 0);
                $('#total-eleves-dcap').text(response.total_eleves || 0);
                $('#total-classes-dcap').text(response.total_classes || 0);
                $('#ecoles-publiques-dcap').text(response.ecoles_publiques || 0);
                $('#ecoles-privees-dcap').text(response.ecoles_privées || 0);

                // Charger la liste des écoles
                this.chargerEcolesDCAP();
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur réseau DCAP:", error);
                $('#liste-ecoles-dcap').html(`
                    <div class="alert alert-danger">
                        <strong>Erreur réseau:</strong> Impossible de charger les données
                    </div>
                `);
                this.resetStatsDCAP();
            });
    }

    resetStatsDCAP() {
        $('#total-ecoles-dcap').text('0');
        $('#total-enseignants-dcap').text('0');
        $('#total-eleves-dcap').text('0');
        $('#total-classes-dcap').text('0');
        $('#ecoles-publiques-dcap').text('0');
        $('#ecoles-privees-dcap').text('0');
    }

    chargerEcolesDCAP() {
        console.log("🔍 Chargement des écoles DCAP...");

        $.get(`${this.ROOT}/Homes/get_ecoles_dcap`)
            .done((response) => {
                console.log("✅ Écoles DCAP reçues:", response);

                if (response.error) {
                    $('#liste-ecoles-dcap').html(`
                        <div class="alert alert-warning">
                            <strong>Erreur:</strong> ${response.error}
                        </div>
                    `);
                    return;
                }

                if (!response || response.length === 0) {
                    $('#liste-ecoles-dcap').html('<p class="text-muted">Aucune école dans ce CAP</p>');
                    return;
                }

                let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
                html += '<thead><tr><th>École</th><th>Type</th><th>Statut</th><th>Contact</th></tr></thead><tbody>';

                response.forEach(ecole => {
                    const statutClass = (ecole.statut === 'Public' || ecole.statut === 'public') ? 'bg-success' : 'bg-warning';
                    const statutText = (ecole.statut === 'Public' || ecole.statut === 'public') ? 'Public' : 'Privé';

                    html += `<tr>
                        <td><strong>${ecole.nomEcole || 'Non renseigné'}</strong></td>
                        <td><span class="badge bg-secondary">${ecole.typeEcole || ''}</span></td>
                        <td><span class="badge ${statutClass}">${statutText}</span></td>
                        <td><small>${ecole.telephone || 'N/A'}</small></td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                $('#liste-ecoles-dcap').html(html);
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur chargement écoles:", error);
                $('#liste-ecoles-dcap').html(`
                    <div class="alert alert-danger">
                        <strong>Erreur:</strong> Impossible de charger la liste des écoles
                    </div>
                `);
            });
    }

    chargerAnnoncesDCAP() {
        $.get(`${this.ROOT}/Homes/get_annonces_dcap`)
            .done((annonces) => {
                console.log("📢 Annonces DCAP reçues:", annonces);
                this.afficherAnnoncesDCAP(annonces);
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur chargement annonces:", error);
                $('#contenu-annonces-dcap').html('<p class="text-muted">Erreur lors du chargement des annonces</p>');
            });
    }

    afficherAnnoncesDCAP(annonces) {
        const container = $('#contenu-annonces-dcap');

        if (!annonces || annonces.length === 0) {
            container.html('<p class="text-muted">Aucune annonce publiée</p>');
            return;
        }

        let html = '';
        annonces.forEach(annonce => {
            const date = new Date(annonce.date_publication).toLocaleDateString('fr-FR');
            const hasFile = annonce.fichier_joint && annonce.fichier_joint !== '';
            const publicCible = this.getLabelPublicCible(annonce.public_cible);

            html += `
            <div class="border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${annonce.titre}</strong>
                        <span class="badge bg-secondary ms-2">${publicCible}</span>
                    </div>
                    <small class="text-muted">${date}</small>
                </div>
                <p class="mb-1 small">${annonce.contenu}</p>
                ${hasFile ? `
                <div class="mt-1">
                    <a href="${this.ROOT}/Homes/telecharger_fichier?id=${annonce.id_annonce}&type=cap" 
                       class="btn btn-sm btn-outline-primary" target="_blank">
                       <i class="bi bi-download"></i> Télécharger le fichier
                       <small class="ms-1">(${annonce.type_fichier})</small>
                    </a>
                </div>
                ` : ''}
                <small class="text-muted">
                    Par ${annonce.nomPrenom}
                </small>
            </div>
            `;
        });

        container.html(html);
    }

    // ==================== FONCTIONS DAE ====================

    chargerStatsDAE() {
        console.log("🔍 Chargement des stats DAE...");

        // Afficher un indicateur de chargement
        $('#total-cap-dae').html('<i class="bi bi-arrow-repeat spinner"></i>');
        $('#total-ecoles-dae').html('<i class="bi bi-arrow-repeat spinner"></i>');
        $('#total-enseignants-dae').html('<i class="bi bi-arrow-repeat spinner"></i>');
        $('#total-eleves-dae').html('<i class="bi bi-arrow-repeat spinner"></i>');

        $.get(`${this.ROOT}/Homes/get_stats_dae`)
            .done((response) => {
                console.log("✅ Données DAE reçues:", response);

                if (response.error) {
                    console.error("❌ Erreur DAE:", response.error);
                    this.resetStatsDAE();
                    return;
                }

                // Mettre à jour les KPI
                $('#total-cap-dae').text(response.total_cap || 0);
                $('#total-ecoles-dae').text(response.total_ecoles || 0);
                $('#total-enseignants-dae').text(response.total_enseignants || 0);
                $('#total-eleves-dae').text(response.total_eleves || 0);

                // Afficher la répartition par CAP
                if (response.repartition_cap && response.repartition_cap.length > 0) {
                    this.afficherRepartitionCAP(response.repartition_cap);
                }
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur réseau DAE:", error);
                this.resetStatsDAE();
            });
    }

    resetStatsDAE() {
        $('#total-cap-dae').text('0');
        $('#total-ecoles-dae').text('0');
        $('#total-enseignants-dae').text('0');
        $('#total-eleves-dae').text('0');
    }

    afficherRepartitionCAP(repartition) {
        let container = $('#repartition-cap-dae');
        if (!container.length) {
            // Créer le conteneur s'il n'existe pas
            $('#form-annonce-dae').closest('.card').after(`
                <div class="card mt-4">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0">Répartition par CAP</h6>
                    </div>
                    <div class="card-body">
                        <div id="repartition-cap-dae"></div>
                    </div>
                </div>
            `);
            container = $('#repartition-cap-dae');
        }

        let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
        html += '<thead><tr><th>CAP</th><th>Écoles</th><th>Enseignants</th><th>Élèves</th></tr></thead><tbody>';

        repartition.forEach(cap => {
            html += `<tr>
                <td><strong>${cap.nom_cap || 'Non renseigné'}</strong></td>
                <td>${cap.nb_ecoles || 0}</td>
                <td>${cap.nb_enseignants || 0}</td>
                <td>${cap.nb_eleves || 0}</td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        container.html(html);
    }

    chargerAnnoncesDAE() {
        $.get(`${this.ROOT}/Homes/get_annonces_dae`)
            .done((annonces) => {
                console.log("📢 Annonces DAE reçues:", annonces);
                this.afficherAnnoncesDAE(annonces);
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur chargement annonces DAE:", error);
            });
    }

    afficherAnnoncesDAE(annonces) {
        const container = $('#contenu-annonces-dae');
        if (!container.length) return;

        if (!annonces || annonces.length === 0) {
            container.html('<p class="text-muted">Aucune annonce régionale publiée</p>');
            return;
        }

        let html = '';
        annonces.forEach(annonce => {
            const date = new Date(annonce.date_publication).toLocaleDateString('fr-FR');
            const hasFile = annonce.fichier_joint && annonce.fichier_joint !== '';
            const publicCible = this.getLabelPublicCible(annonce.public_cible);

            html += `
            <div class="border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${annonce.titre}</strong>
                        <span class="badge bg-secondary ms-2">${publicCible}</span>
                    </div>
                    <small class="text-muted">${date}</small>
                </div>
                <p class="mb-1 small">${annonce.contenu}</p>
                ${hasFile ? `
                <div class="mt-1">
                    <a href="${this.ROOT}/Homes/telecharger_fichier?id=${annonce.id_annonce}&type=academie" 
                       class="btn btn-sm btn-outline-success" target="_blank">
                       <i class="bi bi-download"></i> Télécharger le fichier
                       <small class="ms-1">(${annonce.type_fichier})</small>
                    </a>
                </div>
                ` : ''}
                <small class="text-muted">
                    Par ${annonce.nomPrenom}
                </small>
            </div>
            `;
        });

        container.html(html);
    }
    // ==================== FONCTIONS ADMIN/GESTIONNAIRE ====================


    chargerAnnoncesAdminGestionnaire() {
        $.get(`${this.ROOT}/Homes/get_annonces_admin_gestionnaire`)
            .done((annonces) => {
                console.log("📢 Annonces Admin/Gestionnaire reçues:", annonces);
                this.afficherAnnoncesAdminGestionnaire(annonces);
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur chargement annonces admin/gestionnaire:", error);
                $('#contenu-annonces-admin_gestionnaire').html('<p class="text-muted">Erreur lors du chargement des annonces</p>');
            });
    }

    afficherAnnoncesAdminGestionnaire(annonces) {
        const container = $('#contenu-annonces-admin_gestionnaire');

        if (!annonces || annonces.length === 0) {
            container.html('<p class="text-muted">Aucune annonce publiée</p>');
            return;
        }

        let html = '';
        annonces.forEach(annonce => {
            const date = new Date(annonce.date_publication).toLocaleDateString('fr-FR');
            const hasFile = annonce.fichier_joint && annonce.fichier_joint !== '';
            const publicCible = this.getLabelPublicCible(annonce.public_cible);

            // Utiliser la fonction si elle existe, sinon le rôle
            const fonction = annonce.fonction || this.getLabelPublicCible(annonce.droit);

            html += `
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${annonce.titre}</strong>
                    <span class="badge bg-secondary ms-2">${publicCible}</span>
                </div>
                <small class="text-muted">${date}</small>
            </div>
            <p class="mb-1 small">${annonce.contenu}</p>
            ${hasFile ? `
            <div class="mt-1">
                <a href="${this.ROOT}/Homes/telecharger_fichier?id=${annonce.id_annonce}&type=admin_gestionnaire" 
                   class="btn btn-sm btn-outline-primary" target="_blank">
                   <i class="bi bi-download"></i> Télécharger le fichier
                   <small class="ms-1">(${annonce.type_fichier})</small>
                </a>
            </div>
            ` : ''}
            <small class="text-muted">
                Par ${annonce.nomPrenom} — ${fonction}
            </small>
        </div>
        `;
        });

        container.html(html);
    }

    gererPublicationAdminGestionnaire(e) {
        e.preventDefault();

        const titre = $('#titre-annonce-admin_gestionnaire').val();
        const contenu = $('#contenu-annonce-admin_gestionnaire').val();
        const publicCible = $('#public-cible-admin_gestionnaire').val();
        const fichierInput = $('#fichier-joint-admin_gestionnaire')[0];

        if (!titre || !contenu || !publicCible) {
            this.showWarning('Veuillez remplir tous les champs obligatoires');
            return;
        }

        const formData = new FormData();
        formData.append('titre', titre);
        formData.append('contenu', contenu);
        formData.append('public_cible', publicCible);

        if (fichierInput.files.length > 0) {
            formData.append('fichier_joint', fichierInput.files[0]);
        }

        const progressBar = $('#progress-upload-admin_gestionnaire');
        const btnPublier = $('#btn-publier-admin_gestionnaire');

        progressBar.removeClass('d-none');
        btnPublier.prop('disabled', true).html('<i class="bi bi-arrow-repeat spinner"></i> Publication...');

        $.ajax({
            url: `${this.ROOT}/Homes/publier_annonce_admin_gestionnaire`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: () => {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressBar.find('.progress-bar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: (response) => {
                if (typeof response === 'object' && response !== null) {
                    if (response.success) {
                        this.showSuccess(response.message);
                        this.reinitialiserFormulaireAdminGestionnaire();
                        this.chargerAnnoncesAdminGestionnaire();
                        this.chargerAnnoncesVisibles();
                    } else {
                        this.showError(response.error || 'Erreur inconnue');
                    }
                } else {
                    console.error("Réponse non JSON:", response);
                    this.showError('Erreur: réponse serveur invalide');
                }
            },
            error: (xhr, status, error) => {
                console.error("Erreur AJAX Admin/Gestionnaire:", error);
                let errorMessage = 'Erreur réseau lors de la publication';
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.error || errorMessage;
                    } catch (e) {
                        errorMessage = 'Erreur serveur: ' + xhr.responseText.substring(0, 100);
                    }
                }
                this.showError(errorMessage);
            },
            complete: () => {
                btnPublier.prop('disabled', false).html('<i class="bi bi-megaphone"></i> Publier l\'annonce');
                progressBar.addClass('d-none').find('.progress-bar').css('width', '0%');
            }
        });
    }

    reinitialiserFormulaireAdminGestionnaire() {
        $('#titre-annonce-admin_gestionnaire').val('');
        $('#contenu-annonce-admin_gestionnaire').val('');
        $('#public-cible-admin_gestionnaire').val('tous');
        $('#fichier-joint-admin_gestionnaire').val('');
        document.getElementById('form-annonce-admin_gestionnaire').reset();
    }








    chargerAnnoncesVisiblesEcole() {
        $.get(`${this.ROOT}/Homes/get_annonces_visibles_ecole`)
            .done((annonces) => {
                console.log("📢 Annonces visibles école reçues:", annonces);
                this.afficherAnnoncesVisiblesEcole(annonces);
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur chargement annonces visibles école:", error);
            });
    }

    afficherAnnoncesVisiblesEcole(annonces) {
        const container = $('#annonces-visibles-ecole');
        if (!container.length) return;

        if (!annonces || annonces.length === 0) {
            container.html('<p class="text-muted">Aucune annonce récente pour votre école</p>');
            return;
        }

        let html = '';
        annonces.forEach(annonce => {
            const date = new Date(annonce.date_publication).toLocaleDateString('fr-FR');
            const hasFile = annonce.fichier_joint && annonce.fichier_joint !== '';
            const publicCible = this.getLabelPublicCible(annonce.public_cible);

            html += `
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${annonce.titre}</strong>
                    <span class="badge bg-info ms-2">École</span>
                    <span class="badge bg-secondary ms-1">${publicCible}</span>
                </div>
                <small class="text-muted">${date}</small>
            </div>
            <p class="mb-1 small">${annonce.contenu}</p>
            ${hasFile ? `
            <div class="mt-1">
                <a href="${this.ROOT}/Homes/telecharger_fichier?id=${annonce.id_annonce}&type=admin_gestionnaire" 
                   class="btn btn-sm btn-outline-info" target="_blank">
                   <i class="bi bi-download"></i> Télécharger
                   <small class="ms-1">(${annonce.type_fichier})</small>
                </a>
            </div>
            ` : ''}
            <small class="text-muted">
                Par ${annonce.nomPrenom}
            </small>
        </div>
        `;
        });

        container.html(html);
    }
    // ==================== ANNONCES VISIBLES POUR TOUS ====================

    // chargerAnnoncesVisibles() {
    //     $.get(`${this.ROOT}/Homes/get_annonces_visibles`)
    //         .done((annonces) => {
    //             console.log("📢 Annonces visibles reçues:", annonces);
    //             this.afficherAnnoncesVisibles(annonces);
    //         })
    //         .fail((xhr, status, error) => {
    //             console.error("❌ Erreur chargement annonces visibles:", error);
    //             $('#annonces-visibles').html('<p class="text-muted">Erreur lors du chargement des annonces</p>');
    //         });
    // }

    chargerAnnoncesVisibles() {
        // Ne pas charger les annonces pour le SuperAdmin
        if (window.droitUtilisateur === 'SupAdmin') {
            console.log("🔒 SuperAdmin - Annonces visibles désactivées");
            return;
        }
        
        $.get(`${this.ROOT}/Homes/get_annonces_visibles`)
            .done((annonces) => {
                console.log("📢 Annonces visibles reçues:", annonces);
                this.afficherAnnoncesVisibles(annonces);
            })
            .fail((xhr, status, error) => {
                console.error("❌ Erreur chargement annonces visibles:", error);
                $('#annonces-visibles').html('<p class="text-muted">Erreur lors du chargement des annonces</p>');
            });
    }

    afficherAnnoncesVisibles(annonces) {
        const container = $('#annonces-visibles');

        if (!annonces || annonces.length === 0) {
            container.html('<p class="text-muted">Aucune annonce récente</p>');
            return;
        }

        let html = '';
        annonces.forEach(annonce => {
            const date = new Date(annonce.date_publication).toLocaleDateString('fr-FR');
            const hasFile = annonce.fichier_joint && annonce.fichier_joint !== '';
            const publicCible = this.getLabelPublicCible(annonce.public_cible);
            const source = annonce.source === 'cap' ? 'CAP' : 'Académie';
            const badgeClass = annonce.source === 'cap' ? 'bg-primary' : 'bg-success';

            html += `
            <div class="border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${annonce.titre}</strong>
                        <span class="badge ${badgeClass} ms-2">${source}</span>
                        <span class="badge bg-secondary ms-1">${publicCible}</span>
                    </div>
                    <small class="text-muted">${date}</small>
                </div>
                <p class="mb-1 small">${annonce.contenu}</p>
                ${hasFile ? `
                <div class="mt-1">
                    <a href="${this.ROOT}/Homes/telecharger_fichier?id=${annonce.id_annonce}&type=${annonce.source}" 
                       class="btn btn-sm btn-outline-primary" target="_blank">
                       <i class="bi bi-download"></i> Télécharger le fichier
                       <small class="ms-1">(${annonce.type_fichier})</small>
                    </a>
                </div>
                ` : ''}
                <small class="text-muted">
                    Par ${annonce.nomPrenom}
                </small>
            </div>
            `;
        });

        container.html(html);
    }

    // ==================== GESTION DES FORMULAIRES ====================



    // Dans dashboard-dcap-dae.js
    gererPublicationDCAP(e) {
        e.preventDefault();

        const titre = $('#titre-annonce-dcap').val();
        const contenu = $('#contenu-annonce-dcap').val();
        const publicCible = $('#public-cible-dcap').val();
        const fichierInput = $('#fichier-joint-dcap')[0];

        if (!titre || !contenu || !publicCible) {
            this.showWarning('Veuillez remplir tous les champs obligatoires');
            return;
        }

        const formData = new FormData();
        formData.append('titre', titre);
        formData.append('contenu', contenu);
        formData.append('public_cible', publicCible);

        if (fichierInput.files.length > 0) {
            formData.append('fichier_joint', fichierInput.files[0]);
        }

        const progressBar = $('#progress-upload-dcap');
        const btnPublier = $('#btn-publier-dcap');

        progressBar.removeClass('d-none');
        btnPublier.prop('disabled', true).html('<i class="bi bi-arrow-repeat spinner"></i> Publication...');

        $.ajax({
            url: `${this.ROOT}/Homes/publier_annonce_dcap`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: () => {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressBar.find('.progress-bar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: (response) => {
                // Vérifier que la réponse est bien du JSON
                if (typeof response === 'object' && response !== null) {
                    if (response.success) {
                        this.showSuccess(response.message);
                        this.reinitialiserFormulaireDCAP();
                        this.chargerAnnoncesDCAP();
                        this.chargerAnnoncesVisibles();
                    } else {
                        this.showError(response.error || 'Erreur inconnue');
                    }
                } else {
                    console.error("Réponse non JSON:", response);
                    this.showError('Erreur: réponse serveur invalide');
                }
            },
            error: (xhr, status, error) => {
                console.error("Erreur AJAX:", error, "Status:", status);

                // Essayer de récupérer le message d'erreur de la réponse
                let errorMessage = 'Erreur réseau lors de la publication';
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.error || errorMessage;
                    } catch (e) {
                        // Si ce n'est pas du JSON, afficher les premiers caractères
                        errorMessage = 'Erreur serveur: ' + xhr.responseText.substring(0, 100);
                    }
                }

                this.showError(errorMessage);
            },
            complete: () => {
                btnPublier.prop('disabled', false).html('<i class="bi bi-megaphone"></i> Publier l\'annonce');
                progressBar.addClass('d-none').find('.progress-bar').css('width', '0%');
            }
        });
    }

    // Ajoutez cette méthode pour réinitialiser le formulaire
    reinitialiserFormulaireDCAP() {
        // Réinitialiser les champs de texte
        $('#titre-annonce-dcap').val('');
        $('#contenu-annonce-dcap').val('');
        $('#public-cible-dcap').val('tous');

        // Réinitialiser le champ fichier (nécessaire pour vider la sélection)
        $('#fichier-joint-dcap').val('');

        // Réinitialiser tout le formulaire (méthode alternative)
        document.getElementById('form-annonce-dcap').reset();
    }

    gererPublicationDAE(e) {
        e.preventDefault();

        const titre = $('#titre-annonce-dae').val();
        const contenu = $('#contenu-annonce-dae').val();
        const publicCible = $('#public-cible-dae').val();
        const fichierInput = $('#fichier-joint-dae')[0];

        if (!titre || !contenu || !publicCible) {
            this.showWarning('Veuillez remplir tous les champs obligatoires');
            return;
        }

        const formData = new FormData();
        formData.append('titre', titre);
        formData.append('contenu', contenu);
        formData.append('public_cible', publicCible);

        if (fichierInput.files.length > 0) {
            formData.append('fichier_joint', fichierInput.files[0]);
        }

        const progressBar = $('#progress-upload-dae');
        const btnPublier = $('#btn-publier-dae');

        progressBar.removeClass('d-none');
        btnPublier.prop('disabled', true).html('<i class="bi bi-arrow-repeat spinner"></i> Publication...');

        $.ajax({
            url: `${this.ROOT}/Homes/publier_annonce_dae`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: () => {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressBar.find('.progress-bar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: (response) => {
                // Vérifier que la réponse est bien du JSON
                if (typeof response === 'object' && response !== null) {
                    if (response.success) {
                        this.showSuccess(response.message);
                        this.reinitialiserFormulaireDAE();
                        this.chargerAnnoncesDAE();
                        this.chargerAnnoncesVisibles();
                    } else {
                        this.showError(response.error || 'Erreur inconnue');
                    }
                } else {
                    console.error("Réponse non JSON:", response);
                    this.showError('Erreur: réponse serveur invalide');
                }
            },
            error: (xhr, status, error) => {
                console.error("Erreur AJAX DAE:", error, "Status:", status);

                // Essayer de récupérer le message d'erreur de la réponse
                let errorMessage = 'Erreur réseau lors de la publication';
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.error || errorMessage;
                    } catch (e) {
                        // Si ce n'est pas du JSON, afficher les premiers caractères
                        errorMessage = 'Erreur serveur: ' + xhr.responseText.substring(0, 100);
                    }
                }

                this.showError(errorMessage);
            },
            complete: () => {
                btnPublier.prop('disabled', false).html('<i class="bi bi-megaphone"></i> Publier l\'annonce régionale');
                progressBar.addClass('d-none').find('.progress-bar').css('width', '0%');
            }
        });
    }

    // Ajoutez cette méthode pour réinitialiser le formulaire DAE
    reinitialiserFormulaireDAE() {
        // Réinitialiser les champs de texte
        $('#titre-annonce-dae').val('');
        $('#contenu-annonce-dae').val('');
        $('#public-cible-dae').val('tous');

        // Réinitialiser le champ fichier (nécessaire pour vider la sélection)
        $('#fichier-joint-dae').val('');

        // Réinitialiser tout le formulaire (méthode alternative)
        document.getElementById('form-annonce-dae').reset();
    }


    // ==================== INITIALISATION ====================

    initialiserEvenements() {
        // Événements pour DCAP
        $(document).on('submit', '#form-annonce-dcap', (e) => this.gererPublicationDCAP(e));

        // Événements pour DAE
        $(document).on('submit', '#form-annonce-dae', (e) => this.gererPublicationDAE(e));
        // Événements pour Admin/Gestionnaire
        $(document).on('submit', '#form-annonce-admin_gestionnaire', (e) => this.gererPublicationAdminGestionnaire(e));
    }

    // initialiserDashboard() {
    //     const droitUtilisateur = window.droitUtilisateur || '';
    //     console.log("👤 Droit utilisateur détecté:", droitUtilisateur);

    //     if (droitUtilisateur === 'DCAP' || droitUtilisateur === 'D-CAP') {
    //         console.log("🚀 Initialisation DCAP");
    //         this.chargerStatsDCAP();
    //         this.chargerAnnoncesDCAP();
    //         setInterval(() => this.chargerStatsDCAP(), 30000);
    //         setInterval(() => this.chargerAnnoncesDCAP(), 60000);
    //     }

    //     if (droitUtilisateur === 'DAE') {
    //         console.log("🚀 Initialisation DAE");
    //         this.chargerStatsDAE();
    //         this.chargerAnnoncesDAE();
    //         setInterval(() => this.chargerStatsDAE(), 30000);
    //         setInterval(() => this.chargerAnnoncesDAE(), 60000);

    //         // Ajouter le conteneur pour les annonces DAE
    //         if (!$('#contenu-annonces-dae').length) {
    //             $('#form-annonce-dae').after(`
    //                 <div class="mt-4" id="liste-annonces-dae">
    //                     <h6>Annonces régionales récentes</h6>
    //                     <div id="contenu-annonces-dae"></div>
    //                 </div>
    //             `);
    //         }
    //     }
    //     // Pour Admin/Gestionnaire
    //     if (droitUtilisateur === 'Admin' || droitUtilisateur === 'Gestionnaire') {
    //         console.log("🚀 Initialisation Admin/Gestionnaire");
    //         this.chargerAnnoncesAdminGestionnaire();
    //         this.chargerAnnoncesVisiblesEcole();
    //         setInterval(() => this.chargerAnnoncesAdminGestionnaire(), 60000);
    //         setInterval(() => this.chargerAnnoncesVisiblesEcole(), 60000);
    //     }

    //     // Charger les annonces visibles pour tous les utilisateurs
    //     this.chargerAnnoncesVisibles();
    //     setInterval(() => this.chargerAnnoncesVisibles(), 120000);
    // }
    initialiserDashboard() {
    const droitUtilisateur = window.droitUtilisateur || '';
    console.log("👤 Droit utilisateur détecté:", droitUtilisateur);

    // Ne pas initialiser les annonces pour le SuperAdmin
    if (droitUtilisateur === 'SupAdmin') {
        console.log("🔒 Dashboard SuperAdmin - Fonctionnalités annonces désactivées");
        return;
    }

    if (droitUtilisateur === 'DCAP' || droitUtilisateur === 'D-CAP') {
        console.log("🚀 Initialisation DCAP");
        this.chargerStatsDCAP();
        this.chargerAnnoncesDCAP();
        setInterval(() => this.chargerStatsDCAP(), 30000);
        setInterval(() => this.chargerAnnoncesDCAP(), 60000);
    }

    if (droitUtilisateur === 'DAE') {
        console.log("🚀 Initialisation DAE");
        this.chargerStatsDAE();
        this.chargerAnnoncesDAE();
        setInterval(() => this.chargerStatsDAE(), 30000);
        setInterval(() => this.chargerAnnoncesDAE(), 60000);
    }

    if (droitUtilisateur === 'Admin' || droitUtilisateur === 'Gestionnaire') {
        console.log("🚀 Initialisation Admin/Gestionnaire");
        this.chargerAnnoncesAdminGestionnaire();
        this.chargerAnnoncesVisiblesEcole();
        setInterval(() => this.chargerAnnoncesAdminGestionnaire(), 60000);
        setInterval(() => this.chargerAnnoncesVisiblesEcole(), 60000);
    }

    // Charger les annonces visibles pour tous les utilisateurs (sauf SuperAdmin)
    this.chargerAnnoncesVisibles();
    setInterval(() => this.chargerAnnoncesVisibles(), 120000);
}
}

// Initialisation
new DashboardDCAPDAE();




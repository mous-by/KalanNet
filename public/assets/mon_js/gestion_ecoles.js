/**
 * Gestion de la hiérarchie Académie → CAP → École
 */
document.addEventListener('DOMContentLoaded', function() {
    const baseURL = document.getElementById('baseURL')?.value || '';
    
    initAddEcoleForm();
    initEditEcoleForm();
    
    function initAddEcoleForm() {
        const selectTypeEcole = document.getElementById("typeEcole");
        const selectAcademie = document.getElementById("id_academie");
        const selectCap = document.getElementById("id_cap");
        const capField = document.getElementById("capField");
        const nomFondamentalInput = document.querySelector('input[name="nomFondamentales"]');
        
        if (!selectTypeEcole || !selectAcademie) return;
        
        selectTypeEcole.addEventListener("change", function() {
            updateFieldsVisibility(this.value, capField, selectCap, nomFondamentalInput);
            // Réinitialiser le CAP si le type change
            resetCapSelect(selectCap);
            // Copier la valeur entre champs lycées/secondaires si nécessaire
            try {
                if (this.value === 'Secondaire Generale') {
                    const from = document.querySelector('input[name="nomSecondaireGenerale"]');
                    const to = document.querySelector('input[name="nomLycee"]');
                    if (from && to && from.value.trim() !== '' && to.value.trim() === '') {
                        to.value = from.value;
                    }
                } else if (this.value === 'Complexe Scolaire') {
                    const from = document.querySelector('input[name="nomLycee"]');
                    const to = document.querySelector('input[name="nomSecondaireGenerale"]');
                    if (from && to && from.value.trim() !== '' && to.value.trim() === '') {
                        to.value = from.value;
                    }
                }
            } catch (e) {
                // ignore
            }
        });
        
        selectAcademie.addEventListener("change", function() {
            const academieId = this.value;
            const typeEcole = selectTypeEcole.value;
            
            if (academieId && shouldShowCapField(typeEcole, nomFondamentalInput)) {
                loadCapsByAcademie(academieId, selectCap, capField);
            } else {
                resetCapSelect(selectCap);
                capField.style.display = "none";
            }
        });
        
        if (nomFondamentalInput) {
            nomFondamentalInput.addEventListener("input", function() {
                if (selectTypeEcole.value === "Complexe Scolaire") {
                    capField.style.display = this.value.trim() !== "" ? "block" : "none";
                    if (this.value.trim() === "") {
                        resetCapSelect(selectCap);
                    }
                }
            });
        }

        // Propagation entre les champs lycées/secondaires du formulaire d'ajout
        const inputNomSecondaireGenerale = document.querySelector('input[name="nomSecondaireGenerale"]');
        const inputNomLycee = document.querySelector('input[name="nomLycee"]');
        if (inputNomSecondaireGenerale && inputNomLycee) {
            inputNomSecondaireGenerale.addEventListener('input', function() {
                if (selectTypeEcole.value === 'Secondaire Generale') {
                    inputNomLycee.value = this.value;
                }
            });
            inputNomLycee.addEventListener('input', function() {
                if (selectTypeEcole.value === 'Complexe Scolaire') {
                    inputNomSecondaireGenerale.value = this.value;
                }
            });
        }

        updateFieldsVisibility(selectTypeEcole.value, capField, selectCap, nomFondamentalInput);
    }
    
    function initEditEcoleForm() {
        const modal = document.getElementById("modalCenterEcole");
        if (!modal) return;
        
        modal.addEventListener("show.bs.modal", function(event) {
            const button = event.relatedTarget;
            const ecoleId = button.getAttribute("data-idecole");
            
            loadEcoleDataForEdit(ecoleId, button);
        });
    }
    
    function loadEcoleDataForEdit(ecoleId, button) {
        fetch(`${baseURL}/Ecoles/getEcoleDataAjax/${ecoleId}`)
            .then(response => response.json())
            .then(ecoleData => {
                populateEditForm(ecoleData, button);
                setupEditFormListeners();
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données:', error);
                populateEditFormFromButton(button);
                setupEditFormListeners();
            });
    }
    
    function populateEditForm(ecoleData, button) {
        document.getElementById('modalIdEcole').value = ecoleData.idEcole;
        document.getElementById('modalTypeEcole').value = ecoleData.typeEcole;
        document.getElementById('modal_notifier_sms').checked = ecoleData.notification_sms === 1;
        
        document.getElementById('modalStatut').value = ecoleData.statut || 'public';
        document.getElementById('modalAdresse').value = ecoleData.adresse || '';
        document.getElementById('modalTelephone').value = ecoleData.telephone || '';
        document.getElementById('modalEmail').value = ecoleData.email || '';
        
        document.getElementById('modalIdAcademie').value = ecoleData.id_academie || '';

        const currentPlanCode = button.getAttribute('data-subscription-plan-code') || '';
        const currentPlanLibelle = button.getAttribute('data-subscription-plan-libelle') || '';
        const currentPlanCodeInput = document.getElementById('modalCurrentSubscriptionPlanCode');
        const currentPlanLabel = document.getElementById('modalCurrentSubscriptionLabel');
        const planSelect = document.getElementById('modalSubscriptionPlanCode');
        if (currentPlanCodeInput) currentPlanCodeInput.value = currentPlanCode;
        if (currentPlanLabel) {
            currentPlanLabel.textContent = 'Plan actuel: ' + (currentPlanLibelle ? `${currentPlanLibelle}${currentPlanCode ? ` (${currentPlanCode})` : ''}` : 'Non défini');
        }
        if (planSelect) {
            planSelect.value = '__KEEP__';
        }
        
        fillFieldsByType(ecoleData);
        
        const capField = document.getElementById("modalCapField");
        const selectCap = document.getElementById("modalIdCap");
        const nomFondamentalInput = document.getElementById('modalNomFondamentales');
        
        updateFieldsVisibility(ecoleData.typeEcole, capField, selectCap, nomFondamentalInput);
        
        // Charger les CAP seulement si nécessaire
        if (ecoleData.id_academie && shouldShowCapField(ecoleData.typeEcole, nomFondamentalInput)) {
            loadCapsByAcademieForEdit(ecoleData.id_academie, ecoleData.id_cap, selectCap, capField);
        }
    }
    
    function populateEditFormFromButton(button) {
        const data = {
            idEcole: button.getAttribute("data-idecole"),
            typeEcole: button.getAttribute("data-typeecole"),
            nomEcole: button.getAttribute("data-nomecole") || "",
            nomComplexe: button.getAttribute("data-nomcomplexe") || "",
            nomFondamental: button.getAttribute("data-nomfondamental") || "",
            nomLycee: button.getAttribute("data-nomlycee") || "",
            nomProfessionnel: button.getAttribute("data-nomprofessionnel") || "",
            cap: button.getAttribute("data-cap") || "",
            academie: button.getAttribute("data-academie") || "",
            id_academie: button.getAttribute("data-id_academie") || "",
            id_cap: button.getAttribute("data-id_cap") || "",
            notification_sms: button.getAttribute("data-notifier_sms") || "0",
            statut: button.getAttribute("data-statut") || "public",
            adresse: button.getAttribute("data-adresse") || "",
            telephone: button.getAttribute("data-telephone") || "",
            email: button.getAttribute("data-email") || ""
        };

        const currentPlanCode = button.getAttribute('data-subscription-plan-code') || '';
        const currentPlanLibelle = button.getAttribute('data-subscription-plan-libelle') || '';
        
        document.getElementById('modalIdEcole').value = data.idEcole;
        document.getElementById('modalTypeEcole').value = data.typeEcole;
        document.getElementById('modal_notifier_sms').checked = data.notification_sms === "1" || data.notification_sms === 1;
        
        document.getElementById('modalStatut').value = data.statut;
        document.getElementById('modalAdresse').value = data.adresse;
        document.getElementById('modalTelephone').value = data.telephone;
        document.getElementById('modalEmail').value = data.email;
        
        document.getElementById('modalIdAcademie').value = data.id_academie;

        const currentPlanCodeInput = document.getElementById('modalCurrentSubscriptionPlanCode');
        const currentPlanLabel = document.getElementById('modalCurrentSubscriptionLabel');
        const planSelect = document.getElementById('modalSubscriptionPlanCode');
        if (currentPlanCodeInput) currentPlanCodeInput.value = currentPlanCode;
        if (currentPlanLabel) {
            currentPlanLabel.textContent = 'Plan actuel: ' + (currentPlanLibelle ? `${currentPlanLibelle}${currentPlanCode ? ` (${currentPlanCode})` : ''}` : 'Non défini');
        }
        if (planSelect) {
            planSelect.value = '__KEEP__';
        }
        
        fillFieldsByType(data);
        
        const capField = document.getElementById("modalCapField");
        const selectCap = document.getElementById("modalIdCap");
        const nomFondamentalInput = document.getElementById('modalNomFondamentales');
        
        updateFieldsVisibility(data.typeEcole, capField, selectCap, nomFondamentalInput);
        
        // Pré-remplir le CAP si nécessaire
        if (data.id_cap && shouldShowCapField(data.typeEcole, nomFondamentalInput)) {
            setTimeout(() => {
                selectCap.value = data.id_cap;
            }, 100);
        }
    }
    
    function fillFieldsByType(data) {
        switch(data.typeEcole) {
            case "Complexe Scolaire":
                document.getElementById('modalNomComplexe').value = data.nomComplexe || data.nomEcole;
                document.getElementById('modalNomFondamentales').value = data.nomFondamental || "";
                document.getElementById('modalNomSecondaireGenerale').value = data.nomLycee || "";
                document.getElementById('modalNomTechniqueProfessionnelle').value = data.nomProfessionnel || "";
                break;
            case "Fondamentale I":
            case "Fondamentale II":
                document.getElementById('modalNomEcoleFondamental').value = data.nomFondamental || data.nomEcole;
                break;
            case "Secondaire Generale":
                document.getElementById('modalNomLycee').value = data.nomLycee || data.nomEcole;
                break;
            case "Secondaire Technique et Professionnel":
                document.getElementById('modalNomEtablissement').value = data.nomProfessionnel || data.nomEcole;
                break;
        }
    }
    
    function setupEditFormListeners() {
        const selectType = document.getElementById('modalTypeEcole');
        const selectAcademie = document.getElementById('modalIdAcademie');
        const capField = document.getElementById("modalCapField");
        const selectCap = document.getElementById("modalIdCap");
        const nomFondamentalInput = document.getElementById('modalNomFondamentales');
        
        selectType.addEventListener("change", function() {
            updateFieldsVisibility(this.value, capField, selectCap, nomFondamentalInput);
            // Réinitialiser le CAP si le type change
            resetCapSelect(selectCap);
            // Copier entre modalNomSecondaireGenerale et modalNomLycee selon le type
            try {
                if (this.value === 'Secondaire Generale') {
                    const from = document.getElementById('modalNomSecondaireGenerale');
                    const to = document.getElementById('modalNomLycee');
                    if (from && to && from.value.trim() !== '' && to.value.trim() === '') {
                        to.value = from.value;
                    }
                } else if (this.value === 'Complexe Scolaire') {
                    const from = document.getElementById('modalNomLycee');
                    const to = document.getElementById('modalNomSecondaireGenerale');
                    if (from && to && from.value.trim() !== '' && to.value.trim() === '') {
                        to.value = from.value;
                    }
                }
            } catch (e) {}
        });
        
        selectAcademie.addEventListener("change", function() {
            const academieId = this.value;
            const typeEcole = selectType.value;
            
            if (academieId && shouldShowCapField(typeEcole, nomFondamentalInput)) {
                loadCapsByAcademie(academieId, selectCap, capField);
            } else {
                resetCapSelect(selectCap);
                capField.style.display = "none";
            }
        });
        
        if (nomFondamentalInput) {
            nomFondamentalInput.addEventListener("input", function() {
                if (selectType.value === "Complexe Scolaire") {
                    capField.style.display = this.value.trim() !== "" ? "block" : "none";
                    if (this.value.trim() === "") {
                        resetCapSelect(selectCap);
                    }
                    
                    // Recharger les CAP si une académie est déjà sélectionnée
                    const academieId = selectAcademie.value;
                    if (academieId && this.value.trim() !== "") {
                        loadCapsByAcademie(academieId, selectCap, capField);
                    }
                }
            });
        }

        // Propagation entre les champs du modal
        const modalNomSecondaire = document.getElementById('modalNomSecondaireGenerale');
        const modalNomLycee = document.getElementById('modalNomLycee');
        if (modalNomSecondaire && modalNomLycee) {
            modalNomSecondaire.addEventListener('input', function() {
                const type = document.getElementById('modalTypeEcole').value;
                if (type === 'Secondaire Generale') {
                    modalNomLycee.value = this.value;
                }
            });
            modalNomLycee.addEventListener('input', function() {
                const type = document.getElementById('modalTypeEcole').value;
                if (type === 'Complexe Scolaire') {
                    modalNomSecondaire.value = this.value;
                }
            });
        }
        
    }
    
    // Fonction pour déterminer si le champ CAP doit être affiché
    function shouldShowCapField(typeEcole, nomFondamentalInput) {
        if (typeEcole === "Secondaire Generale" || typeEcole === "Secondaire Technique et Professionnel") {
            return false; // Pas de CAP pour ces types
        }
        
        if (typeEcole === "Complexe Scolaire") {
            // Pour Complexe Scolaire, afficher CAP seulement si nomFondamental n'est pas vide
            return nomFondamentalInput && nomFondamentalInput.value.trim() !== "";
        }
        
        // Pour Fondamentale I et II, toujours afficher CAP
        return typeEcole === "Fondamentale I" || typeEcole === "Fondamentale II";
    }
    
    function loadCapsByAcademieForEdit(academieId, currentCapId, selectCap, capField) {
        fetch(`${baseURL}/Ecoles/getCapsByAcademieAjax/${academieId}`)
            .then(response => response.json())
            .then(caps => {
                resetCapSelect(selectCap);
                
                if (caps && caps.length > 0) {
                    caps.forEach(cap => {
                        const option = document.createElement('option');
                        option.value = cap.id_cap;
                        option.textContent = cap.nom_cap;
                        if (cap.id_cap == currentCapId) {
                            option.selected = true;
                        }
                        selectCap.appendChild(option);
                    });
                    capField.style.display = "block";
                } else {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "Aucun CAP disponible pour cette académie";
                    selectCap.appendChild(option);
                    capField.style.display = "block";
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des CAP:', error);
                resetCapSelect(selectCap);
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "Erreur de chargement";
                selectCap.appendChild(option);
            });
    }
    
    function loadCapsByAcademie(academieId, selectCap, capField) {
        fetch(`${baseURL}/Ecoles/getCapsByAcademieAjax/${academieId}`)
            .then(response => response.json())
            .then(caps => {
                resetCapSelect(selectCap);
                
                if (caps && caps.length > 0) {
                    caps.forEach(cap => {
                        const option = document.createElement('option');
                        option.value = cap.id_cap;
                        option.textContent = cap.nom_cap;
                        selectCap.appendChild(option);
                    });
                    capField.style.display = "block";
                } else {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "Aucun CAP disponible pour cette académie";
                    selectCap.appendChild(option);
                    capField.style.display = "block";
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des CAP:', error);
                resetCapSelect(selectCap);
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "Erreur de chargement";
                selectCap.appendChild(option);
            });
    }
    
    function updateFieldsVisibility(typeEcole, capField, selectCap, nomFondamentalInput) {
        hideAllSpecificFields();
        
        const isComplexe = typeEcole === "Complexe Scolaire";
        const isFondamentale = typeEcole === "Fondamentale I" || typeEcole === "Fondamentale II";
        const isSecondaireGenerale = typeEcole === "Secondaire Generale";
        const isSecondaireTechnique = typeEcole === "Secondaire Technique et Professionnel";
        
        if (isComplexe) {
            document.getElementById("modalComplexeFields").style.display = "block";
            document.getElementById("complexeFields").style.display = "block";
            
            // Pour Complexe Scolaire, CAP seulement si nomFondamental n'est pas vide
            if (nomFondamentalInput && nomFondamentalInput.value.trim() !== "") {
                capField.style.display = "block";
            } else {
                capField.style.display = "none";
                resetCapSelect(selectCap);
            }
        } else if (isFondamentale) {
            document.getElementById("modalFondamentaleFields").style.display = "block";
            document.getElementById("fondamentaleFields").style.display = "block";
            capField.style.display = "block"; // Toujours afficher CAP pour Fondamentale
        } else if (isSecondaireGenerale) {
            document.getElementById("modalSecondaireGeneraleFields").style.display = "block";
            document.getElementById("secondaireGeneraleFields").style.display = "block";
            capField.style.display = "none"; // Cacher CAP pour Secondaire Générale
            resetCapSelect(selectCap);
        } else if (isSecondaireTechnique) {
            document.getElementById("modalSecondaireTechniqueFields").style.display = "block";
            document.getElementById("secondaireTechniqueFields").style.display = "block";
            capField.style.display = "none"; // Cacher CAP pour Secondaire Technique
            resetCapSelect(selectCap);
        }
    }
    
    function hideAllSpecificFields() {
        const fields = [
            "complexeFields", "fondamentaleFields", "secondaireGeneraleFields", "secondaireTechniqueFields",
            "modalComplexeFields", "modalFondamentaleFields", "modalSecondaireGeneraleFields", "modalSecondaireTechniqueFields"
        ];
        
        fields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) element.style.display = "none";
        });
    }
    
    function resetCapSelect(selectCap) {
        if (selectCap) {
            selectCap.innerHTML = '<option value="" selected disabled>Sélectionnez un CAP</option>';
        }
    }
});
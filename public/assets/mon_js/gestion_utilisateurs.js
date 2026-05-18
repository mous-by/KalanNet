function toggleNomPrenom() {
    const enseignantChecked = document.getElementById('enseignant').checked;
    const parentChecked = document.getElementById('parent').checked;
    const daeChecked = document.getElementById('dae').checked;
    const dcapChecked = document.getElementById('dcap').checked;
    
    const colGenre = document.getElementById('colGenre');
    const colFonction = document.getElementById('colFonction');
    const blocEcoleDroit = document.getElementById('blocEcoleDroit');
    const blocDAEDCAP = document.getElementById('blocDAEDCAP');
    const nomPrenomContainer = document.getElementById('nomPrenomContainer');
    const emailInput = document.getElementById('email_utilisateurs');
    const contactInput = document.getElementById('contact_utilisateur');
    const blocImageUpload = document.getElementById('blocImageUpload');
    const imagePreviewDiv = document.getElementById('imagePreviewDiv');
    const avatarDiv = document.getElementById('enseignantAvatarContainer');
    const avatarImg = document.getElementById('avatarEnseignantImg');
    const inputFonction = document.getElementById('inputFonction');
    const selectDroit = document.getElementById('selectDroit');
    const hiddenDroit = document.getElementById('hiddenDroit');

    // Masquer/Afficher les blocs selon le type
    if (enseignantChecked || parentChecked) {
        colGenre.style.setProperty('display', 'none', 'important');
        colFonction.style.setProperty('display', 'none', 'important');
        blocEcoleDroit.style.setProperty('display', 'none', 'important');
        blocDAEDCAP.style.setProperty('display', 'none', 'important');
    } else if (daeChecked || dcapChecked) {
        colGenre.style.setProperty('display', '', 'important');
        colFonction.style.setProperty('display', '', 'important');
        blocEcoleDroit.style.setProperty('display', 'none', 'important');
        blocDAEDCAP.style.setProperty('display', 'flex', 'important');
        
        // Pré-remplir la fonction et le droit
        if (daeChecked) {
            inputFonction.value = "DAE";
            selectDroit.value = "DAE";
            document.getElementById('blocCAP').style.display = 'none';
            // ensure hidden field carries the droit value when select is disabled
            if (hiddenDroit) { hiddenDroit.name = 'droit'; hiddenDroit.value = 'DAE'; }
        } else {
            inputFonction.value = "DCAP";
            selectDroit.value = "DCAP";
            document.getElementById('blocCAP').style.display = 'block';
            if (hiddenDroit) { hiddenDroit.name = 'droit'; hiddenDroit.value = 'DCAP'; }
        }
        
        // Rendre le champ droit en lecture seule
        selectDroit.disabled = true;
    } else {
        colGenre.style.setProperty('display', '', 'important');
        colFonction.style.setProperty('display', '', 'important');
        blocEcoleDroit.style.setProperty('display', '', 'important');
        blocDAEDCAP.style.setProperty('display', 'none', 'important');
        selectDroit.disabled = false;
        // remove hidden fallback so select value is used
        if (hiddenDroit) { hiddenDroit.removeAttribute('name'); hiddenDroit.value = ''; }
        inputFonction.value = '';
    }

    nomPrenomContainer.innerHTML = '';
    
    if (enseignantChecked) {
        const select = document.getElementById('selectEnseignantOriginal').cloneNode(true);
        select.id = 'selectEnseignant';
        select.classList.remove('d-none');
        select.setAttribute('onchange', "updateEmailAndContact('enseignant')");
        nomPrenomContainer.appendChild(select);

        if (select.options.length > 1) {
            select.selectedIndex = 1;
        }

        select.addEventListener('change', function() {
            updateEmailAndContact('enseignant');
        });
        updateEmailAndContact('enseignant');

        blocImageUpload.style.display = 'none';
        imagePreviewDiv.classList.add('d-none');
        avatarDiv.classList.remove('d-none');
        avatarImg.style.display = "none";
        avatarImg.src = "";
    } else {
        blocImageUpload.style.display = '';
        imagePreviewDiv.classList.remove('d-none');
        avatarDiv.classList.add('d-none');
        avatarImg.style.display = "none";
        avatarImg.src = "";
        
        if (parentChecked) {
            const select = document.getElementById('selectParentOriginal').cloneNode(true);
            select.id = 'selectParent';
            select.classList.remove('d-none');
            select.setAttribute('onchange', "updateEmailAndContact('parent')");
            nomPrenomContainer.appendChild(select);

            if (select.options.length > 1) {
                select.selectedIndex = 1;
            }

            select.addEventListener('change', function() {
                updateEmailAndContact('parent');
            });
            updateEmailAndContact('parent');
        } else {
            // Administrateur, DAE, DCAP
            nomPrenomContainer.innerHTML = `<input type="text" id="nom_prenom" class="form-control" name="nomPrenom" placeholder="Nom & Prénom" required />`;
            emailInput.value = '';
            contactInput.value = '';
            emailInput.disabled = false;
            contactInput.disabled = false;
            
            // Réinitialiser la fonction si pas DAE/DCAP
            if (!daeChecked && !dcapChecked) {
                inputFonction.value = '';
            }
        }
    }
}

function updateEmailAndContact(type) {
    let select;
    if (type === 'enseignant') {
        select = document.getElementById('selectEnseignant');
    } else if (type === 'parent') {
        select = document.getElementById('selectParent');
    } else {
        return;
    }
    let selectedOption = select.options[select.selectedIndex];
    if (select.selectedIndex === 0 && select.options.length > 1) {
        selectedOption = select.options[1];
        select.selectedIndex = 1;
    }
    const email = selectedOption ? selectedOption.getAttribute('data-email') || '' : '';
    const contact = selectedOption ? selectedOption.getAttribute('data-contact') || '' : '';
    document.getElementById('email_utilisateurs').value = email;
    document.getElementById('contact_utilisateur').value = contact;
    document.getElementById('email_utilisateurs').disabled = !!email;
    document.getElementById('contact_utilisateur').disabled = !!contact;

    // Affiche l'avatar enseignant uniquement si un avatar existe
    if (type === 'enseignant') {
        const avatar = selectedOption ? selectedOption.getAttribute('data-avatar') : '';
        const avatarImg = document.getElementById('avatarEnseignantImg');
        if (avatar && avatar.trim() !== "") {
            avatarImg.src = ROOT + "/public/images_enseignant/" + avatar;
            avatarImg.style.display = "block";
        } else {
            avatarImg.src = "";
            avatarImg.style.display = "none";
        }
    }
}

// Gestion du changement d'académie pour charger les CAP
function loadCapsByAcademie() {
    const academieId = document.getElementById('id_academie').value;
    const selectCAP = document.getElementById('id_cap');
    
    if (academieId) {
        fetch(`${ROOT}/Utilisateurs/getCapsByAcademieAjax/${academieId}`)
            .then(response => response.json())
            .then(caps => {
                selectCAP.innerHTML = '<option value="" selected disabled>Choisir un CAP</option>';
                
                if (caps && caps.length > 0) {
                    caps.forEach(cap => {
                        const option = document.createElement('option');
                        option.value = cap.id_cap;
                        option.textContent = cap.nom_cap;
                        selectCAP.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "Aucun CAP disponible pour cette académie";
                    selectCAP.appendChild(option);
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des CAP:', error);
                selectCAP.innerHTML = '<option value="" selected disabled>Erreur de chargement</option>';
            });
    } else {
        selectCAP.innerHTML = '<option value="" selected disabled>Choisir d\'abord une académie</option>';
    }
}

// Image upload preview
function initImagePreview() {
    const image = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    if (image && imagePreview) {
        image.addEventListener("change", function() {
            const file = image.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imagePreview.src = event.target.result;
                    imagePreview.style.display = "block";
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Définir ROOT si non défini
    if (typeof ROOT === 'undefined') {
        window.ROOT = '';
    }
    
    toggleNomPrenom();
    
    // Ajouter l'écouteur pour le changement d'académie
    const academieSelect = document.getElementById('id_academie');
    if (academieSelect) {
        academieSelect.addEventListener('change', loadCapsByAcademie);
    }
    
    // Ajouter les écouteurs pour tous les radios
    const radios = document.querySelectorAll('input[name="type_utilisateur"]');
    radios.forEach(radio => {
        radio.addEventListener('change', toggleNomPrenom);
    });
    
    // Initialiser la prévisualisation d'image
    initImagePreview();
});
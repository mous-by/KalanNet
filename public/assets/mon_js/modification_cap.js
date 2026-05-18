/**
 * Gestion des modals pour les CAP
 */
document.addEventListener('DOMContentLoaded', function() {
    // Modal d'édition CAP
    const editCapModal = document.getElementById('editCapModal');
    
    if (editCapModal) {
        editCapModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nom = button.getAttribute('data-nom');
            const code = button.getAttribute('data-code');
            const localite = button.getAttribute('data-localite');
            const academie = button.getAttribute('data-academie');
            
            // Remplir les champs du formulaire
            document.getElementById('editIdCap').value = id;
            document.getElementById('editNomCap').value = nom;
            document.getElementById('editCodeCap').value = code;
            document.getElementById('editLocaliteCap').value = localite;
            document.getElementById('editIdAcademie').value = academie;
        });
    }

    // Validation des formulaires CAP
    const addCapForm = document.querySelector('form[action*="ajouter_cap"]');
    const editCapForm = document.querySelector('form[action*="modifier_cap"]');
    
    // Fonction de validation générique
    function validateCapForm(form) {
        const nom = form.querySelector('input[name="nom_cap"]');
        const code = form.querySelector('input[name="code_cap"]');
        const localite = form.querySelector('input[name="localite_cap"]');
        const academie = form.querySelector('select[name="id_academie"]');
        
        let isValid = true;
        
        // Réinitialiser les styles d'erreur
        [nom, code, localite, academie].forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        // Validation des champs requis
        if (!nom.value.trim()) {
            nom.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!code.value.trim()) {
            code.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!localite.value.trim()) {
            localite.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!academie.value) {
            academie.classList.add('is-invalid');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Appliquer la validation aux formulaires
    if (addCapForm) {
        addCapForm.addEventListener('submit', function(e) {
            if (!validateCapForm(this)) {
                e.preventDefault();
                showToast('Veuillez remplir tous les champs obligatoires', 'error');
            }
        });
    }
    
    if (editCapForm) {
        editCapForm.addEventListener('submit', function(e) {
            if (!validateCapForm(this)) {
                e.preventDefault();
                showToast('Veuillez remplir tous les champs obligatoires', 'error');
            }
        });
    }
    
    // Fonction utilitaire pour afficher les toasts (si Bootstrap Toast est disponible)
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Nettoyer après la fermeture
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }
});

// Fonction pour confirmer la suppression d'un CAP
function confirmDeleteCap(capId, capName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le CAP "${capName}" ? Cette action est irréversible.`)) {
        window.location.href = `${document.getElementById('baseURL').value}/Academies/delete_cap/${capId}`;
    }
}
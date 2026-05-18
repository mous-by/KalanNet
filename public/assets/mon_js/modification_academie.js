document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editAcademieModal');
    
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nom = button.getAttribute('data-nom');
            const code = button.getAttribute('data-code');
            const localite = button.getAttribute('data-localite');
            
            document.getElementById('editIdAcademie').value = id;
            document.getElementById('editNomAcademie').value = nom;
            document.getElementById('editCodeAcademie').value = code;
            document.getElementById('editLocaliteAcademie').value = localite;
        });
    }
});
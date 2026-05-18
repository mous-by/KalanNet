document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-delete-matiere').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const matiereId = this.getAttribute('data-id');
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = ROOT + "/Matieres/delete/" + matiereId;
                }
            });
        });
    });

    // Affichage du message flash SweetAlert si présent
    if (typeof flashSweet !== "undefined" && flashSweet) {
        Swal.fire({
            icon: flashSweet.type,
            title: flashSweet.type === "success" ? "Succès" : "Erreur",
            text: flashSweet.message,
            confirmButtonText: 'OK'
        });
    }
});
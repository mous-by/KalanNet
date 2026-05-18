// Fonction pour générer le matricule automatiquement avec l'âge, lieu, et type de contrat
function generateMatricule() {
    let dateNaissance = document.getElementById('date_naissance').value;
    let lieuNaissance = document.getElementById('lieu_naissance').value;
    let genre = document.getElementById('genre').value;
    let typeContrat = document.getElementById('type_contrat').value;

    if (dateNaissance && lieuNaissance && genre && typeContrat) {
        // Calculer l'âge
        let today = new Date();
        let dateObj = new Date(dateNaissance);
        let age = today.getFullYear() - dateObj.getFullYear();
        let mois = today.getMonth() - dateObj.getMonth();

        // Si le mois actuel est avant le mois de naissance ou si on est dans le mois de naissance
        // mais que le jour actuel est avant le jour de naissance, décrémentez l'âge
        if (mois < 0 || (mois === 0 && today.getDate() < dateObj.getDate())) {
            age--;
        }

        // Récupérer les trois premières lettres du lieu de naissance
        let lieuPart = lieuNaissance.substring(0, 3).toUpperCase();

        // Récupérer les trois premières lettres du type de contrat
        let contratPart = typeContrat.substring(0, 3).toUpperCase();

        // Première lettre du genre
        let genrePart = genre.charAt(0).toUpperCase();

        // Générer le matricule avec l'âge à la place de la date de naissance
        let matricule = `Mle${age}${genrePart}-${lieuPart}-${contratPart}`;
        document.getElementById('matricule').value = matricule;
    }
}

// Attacher les événements de changement aux champs nécessaires
document.getElementById('date_naissance').addEventListener('change', generateMatricule);
document.getElementById('lieu_naissance').addEventListener('input', generateMatricule);
document.getElementById('genre').addEventListener('change', generateMatricule);
document.getElementById('type_contrat').addEventListener('change', generateMatricule);

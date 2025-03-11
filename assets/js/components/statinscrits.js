class StatInscrits {
    constructor(elementId) {
        this.elementId = elementId;
        this.fetchAndRenderStatistics();
    }

    // Récupère les statistiques et la liste des utilisateurs via une requête API
    async fetchStatistics() {
        try {
            const response = await fetch("/shareEquipe/api/user-statistics");
            if (!response.ok) {
                throw new Error("Erreur lors de la récupération des données.");
            }
            return await response.json();
        } catch (error) {
            console.error("Erreur lors de la requête API :", error);
            return null;
        }
    }

    // Convertit une date brute au format attendu par JavaScript
    formatDate(rawDate) {
        if (!rawDate) return null;
        try {
            // Traiter les cas où la date est un objet complexe
            if (typeof rawDate === "object" && rawDate.date) {
                rawDate = rawDate.date; // Extraire la valeur de la clé date
            }

            // Remplacer l'espace par un 'T' pour convertir en format ISO 8601
            const isoDate = rawDate.replace(" ", "T").split(".")[0]; // Retirer les microsecondes
            return new Date(isoDate);
        } catch (error) {
            console.error("Erreur de formatage de la date :", rawDate, error);
            return null;
        }
    }

    // Affiche les statistiques globales et la liste des utilisateurs dans le DOM
    async fetchAndRenderStatistics() {
        const element = document.getElementById(this.elementId);
        if (!element) {
            console.error(`Élément avec l'ID '${this.elementId}' introuvable.`);
            return;
        }

        const data = await this.fetchStatistics();

        if (!data) {
            element.innerHTML = `<p class="error">Impossible de charger les statistiques.</p>`;
            return;
        }

        const { stats, users } = data;

        // Générer les lignes du tableau des utilisateurs
        const userRows = users.map(user => {
            const date = this.formatDate(user.dateEnvoi);
            return `
                <tr>
                    <td>${user.email}</td>
                    <td>${date ? date.toLocaleDateString() : 'Date invalide'}</td>
                </tr>
            `;
        }).join('');

        // Générer le contenu HTML
        element.innerHTML = `
            <h3>Statistiques des utilisateurs</h3>
            <ul>
                <li><strong>Total d'utilisateurs :</strong> ${stats.totalUsers}</li>
                <li><strong>Dernière inscription :</strong> ${new Date(stats.lastRegistrationDate).toLocaleDateString()}</li>
            </ul>
            <h3 class="mt-4">Liste des utilisateurs</h3>
            <table class="table table-striped mt-2">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Date d'inscription</th>
                    </tr>
                </thead>
                <tbody>
                    ${userRows}
                </tbody>
            </table>
        `;
    }
}

// Initialisation du composant lorsque la page est chargée
document.addEventListener("DOMContentLoaded", () => {
    new StatInscrits("user-stats"); // L'élément qui contiendra les statistiques
});

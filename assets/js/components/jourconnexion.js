import { Chart, registerables } from "chart.js";

// Enregistrer les composants nécessaires
Chart.register(...registerables);

class JourConnexion {
    constructor(elementId, apiUrl) {
        this.elementId = elementId;
        this.apiUrl = apiUrl;
        this.fetchAndRenderGraph();
    }

    // Récupère les données des connexions via l'API
    async fetchConnectionsData() {
        try {
            const response = await fetch(this.apiUrl);
            if (!response.ok) {
                throw new Error("Erreur lors de la récupération des données.");
            }
            return await response.json();
        } catch (error) {
            console.error("Erreur lors de la requête API :", error);
            return null;
        }
    }

    // Génère le graphique
    renderGraph(data) {
        const ctx = document.getElementById(this.elementId).getContext("2d");

        // Labels : 31 derniers jours (dates)
        const labels = data.map(item => item.date); // Récupère les dates depuis l'API

        // Données : nombre de connexions par date
        const connections = data.map(item => item.totalConnections); // Récupère les connexions depuis l'API

        // Configuration du graphique
        new Chart(ctx, {
            type: "line", // Type de graphique en ligne
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Connexions des 31 derniers jours",
                        data: connections,
                        borderColor: "rgba(75, 192, 192, 1)", // Ligne principale
                        backgroundColor: "rgba(75, 192, 192, 0.2)", // Zone sous la ligne
                        borderWidth: 2,
                        fill: true, // Remplit sous la courbe
                        tension: 0.3, // Ajoute de la courbure à la ligne
                        pointRadius: 4, // Taille des points
                        pointHoverRadius: 6, // Taille des points au survol
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: "top",
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Nombre de connexions",
                        },
                    },
                    x: {
                        title: {
                            display: true,
                            text: "Dates",
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10, // Limite le nombre de labels affichés sur l'axe X
                        },
                    },
                },
            },
        });
    }

    // Récupère les données et affiche le graphique
    async fetchAndRenderGraph() {
        const data = await this.fetchConnectionsData();

        if (!data) {
            console.error("Impossible de charger les données pour le graphique.");
            return;
        }

        this.renderGraph(data);
    }
}

// Initialisation du composant lorsque la page est chargée
document.addEventListener("DOMContentLoaded", () => {
    new JourConnexion("connexion-graph", "https://s3-4684.nuage-peda.fr/shareEquipe/api/connections-data");
});

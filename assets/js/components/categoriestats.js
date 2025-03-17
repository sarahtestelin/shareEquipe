import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

class CategoryStats {
    constructor(elementId, apiUrl) {
        this.elementId = elementId;
        this.apiUrl = apiUrl;
        this.fetchAndRenderGraph();
    }

    async fetchCategoryData() {
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

    renderGraph(data) {
        const ctx = document.getElementById(this.elementId).getContext("2d");
        const labels = data.map(item => item.date);
        const categoriesCount = data.map(item => item.totalCategories);

        new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [{
                    label: "Catégories créées les 7 derniers jours",
                    data: categoriesCount,
                    borderColor: "rgba(75, 192, 192, 1)",
                    backgroundColor: "rgba(75, 192, 192, 0.2)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }],
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
                            text: "Nombre de catégories",
                        },
                    },
                    x: {
                        title: {
                            display: true,
                            text: "Dates",
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10,
                        },
                    },
                },
            },
        });
    }

    async fetchAndRenderGraph() {
        const data = await this.fetchCategoryData();

        if (!data) {
            console.error("Impossible de charger les données pour le graphique.");
            return;
        }

        this.renderGraph(data);
    }
}

// Initialisation du composant lorsque la page est chargée
document.addEventListener("DOMContentLoaded", () => {
    new CategoryStats("categorie-graph", "https://s3-4664.nuage-peda.fr/shareEquipe/api/category-statistics");
});
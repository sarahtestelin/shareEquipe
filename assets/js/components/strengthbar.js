class StrengthBar {
    constructor(inputId, barContainerId, barId, textId) {
        this.passwordField = document.getElementById(inputId);
        this.strengthBarContainer = document.getElementById(barContainerId);
        this.strengthBar = document.getElementById(barId);
        this.strengthText = document.getElementById(textId);

        if (!this.passwordField || !this.strengthBar || !this.strengthText || !this.strengthBarContainer) {
            console.error("Éléments de force du mot de passe introuvables !");
            return;
        }

        this.attachListeners();
    }

    attachListeners() {
        this.passwordField.addEventListener("input", () => {
            const password = this.passwordField.value;
            const score = this.calculateStrength(password);

            // Messages et code couleur
            const strengthMessages = ["Très faible", "Faible", "Normal", "Fort", "Très fort"];
            const strengthColors = ["#8b0000", "#ff0000", "#808080", "#00ff00", "#008000"];

            // Texte et couleur
            this.strengthText.textContent = strengthMessages[score];
            this.strengthText.style.color = strengthColors[score];

            // Largeur et couleur
            this.strengthBar.style.width = `${(score + 1) * 20}%`;
            this.strengthBar.style.backgroundColor = strengthColors[score];
        });
    }

    calculateStrength(password) {
        let score = 0;
        const longueurRequise = password.length >= 12;
        const minusculesVerif = (password.match(/[a-z]/g) || []).length;
        const majusculesVerif = (password.match(/[A-Z]/g) || []).length;
        const chiffresVerif = (password.match(/[0-9]/g) || []).length;

        if (!longueurRequise) return 0; // Trop court = Très faible
        if (minusculesVerif >= 1 && majusculesVerif >= 1 && chiffresVerif >= 1) score = 2; // Normal
        if (minusculesVerif >= 2 && majusculesVerif >= 2 && chiffresVerif >= 2) score = 3; // Fort
        if (minusculesVerif >= 3 && majusculesVerif >= 3 && chiffresVerif >= 3) score = 4; // Très fort
        if (score === 0) score = 1; // Si assez long mais sans les exigences requises, considérer comme faible

        return score;
    }
}

// Initialisation du composant lorsque la page est chargée
document.addEventListener("DOMContentLoaded", () => {
    new StrengthBar("inscription_form_plainPassword_first", "password-strength-container", "password-strength-bar", "password-strength-text");
});

export default StrengthBar;
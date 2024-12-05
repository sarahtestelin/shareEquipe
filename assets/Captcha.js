export default class Captcha {
    constructor(elementId) {
        this.elementId = elementId;
        this.captchaValue = this.generateCaptcha();
        this.renderCaptcha();
    }

    // Génère une valeur aléatoire à 4 chiffres
    generateCaptcha() {
        return Math.floor(1000 + Math.random() * 9000).toString();
    }

    // Affiche le captcha dans le DOM
    renderCaptcha() {
        const element = document.getElementById(this.elementId);
        if (!element) {
            console.error(`Element with ID '${this.elementId}' not found.`);
            return;
        }
        element.innerHTML = `
            <div>
                <span>${this.captchaValue}</span>
                <button type="button" id="refreshCaptcha">↻</button>
            </div>
            <input type="text" id="captchaInput" placeholder="Reproduisez le CAPTCHA" />
        `;

        // Ajoute un événement pour rafraîchir le CAPTCHA
        document.getElementById('refreshCaptcha').addEventListener('click', () => {
            this.captchaValue = this.generateCaptcha();
            this.renderCaptcha();
        });
    }

    // Valide si la saisie correspond au CAPTCHA
    validateCaptcha(inputValue) {
        return inputValue === this.captchaValue;
    }
}

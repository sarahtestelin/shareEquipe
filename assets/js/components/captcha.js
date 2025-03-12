// assets/js/components/captcha.js

class Captcha {
    constructor(elementId) {
        this.elementId = elementId;
        this.captchaValue = this.generateCaptcha();
        this.renderCaptcha();
        this.attachFormListener();
    }

    // Génère une valeur aléatoire à 4 chiffres
    generateCaptcha() {
        return Math.floor(1000 + Math.random() * 9000).toString();
    }

    // Affiche le CAPTCHA dans le DOM
    renderCaptcha() {
        const element = document.getElementById(this.elementId);
        if (!element) {
            console.error(`Element with ID '${this.elementId}' not found.`);
            return;
        }
        element.innerHTML = `
            <label class="fw-bold text-primary"> CAPTCHA </label>
            <div class="d-flex align-items-center gap-2">
                <span class="captcha-code">${this.captchaValue}</span>
                <button type="button" id="refreshCaptcha" class="btn btn-link" aria-label="Rafraîchir CAPTCHA">↻</button>
                <input type="text" id="captchaInput" class="form-control mt-2" placeholder="Entrez le code affiché" required />
            </div>
        `;

        // Ajoute un événement pour rafraîchir le CAPTCHA
        const refreshButton = document.getElementById('refreshCaptcha');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => {
                this.captchaValue = this.generateCaptcha();
                this.renderCaptcha();
            });
        }
    }

    // Valide si la saisie correspond au CAPTCHA
    validateCaptcha(inputValue) {
        return inputValue === this.captchaValue;
    }

    // Attache un écouteur d'événements au formulaire pour valider le CAPTCHA à la soumission
    attachFormListener() {
        const form = document.querySelector('form');  // Sélectionne le formulaire d'inscription
        if (!form) {
            console.error('Formulaire d\'inscription non trouvé.');
            return;
        }

        form.addEventListener('submit', (event) => {
            const userInput = document.getElementById('captchaInput').value.trim();  // Récupère la valeur entrée par l'utilisateur

            // Vérifie si le CAPTCHA est valide
            if (!this.validateCaptcha(userInput)) {
                event.preventDefault();  // Empêche l'envoi du formulaire
                alert('CAPTCHA invalide. Veuillez réessayer.');
            } else {
                document.getElementById('hiddenCaptchaValue').value = this.captchaValue;  // Si valide, on ajoute la valeur CAPTCHA dans le formulaire caché
            }
        });
    }
}

// Initialisation du CAPTCHA lorsque la page est chargée
document.addEventListener('DOMContentLoaded', () => {
    const captcha = new Captcha('captcha');  // L'élément qui contiendra le CAPTCHA
});

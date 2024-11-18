document.addEventListener('DOMContentLoaded', () => {
    const captchaValue = document.getElementById('captcha-value');
    const captchaInput = document.getElementById('captchaInput');
    const refreshButton = document.querySelector('.refresh-captcha');
    const submitButton = document.getElementById('submit-button');

    // Fonction pour récupérer le CAPTCHA depuis le serveur
    const fetchCaptcha = async () => {
        try {
            const response = await fetch('/captcha'); // URL de la route du contrôleur
            const data = await response.json();
            captchaValue.textContent = data.captcha; // Affiche le CAPTCHA dans le formulaire
            submitButton.disabled = true; // Désactive le bouton après un nouveau CAPTCHA
        } catch (error) {
            console.error('Erreur lors de la récupération du CAPTCHA :', error);
        }
    };

    // Vérifie si le CAPTCHA saisi correspond à celui affiché
    const validateCaptcha = () => {
        if (captchaInput.value === captchaValue.textContent) {
            submitButton.disabled = false; // Active le bouton si le CAPTCHA est correct
        } else {
            submitButton.disabled = true; // Désactive le bouton si incorrect
        }
    };

    // Charger le CAPTCHA au chargement de la page
    fetchCaptcha();

    // Rafraîchir le CAPTCHA au clic
    refreshButton.addEventListener('click', fetchCaptcha);

    // Valider le CAPTCHA en temps réel
    captchaInput.addEventListener('input', validateCaptcha);
});

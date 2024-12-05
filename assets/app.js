/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/captcha.css';

import Captcha from './Captcha.js';

// Initialise le CAPTCHA lorsque la page est chargée
document.addEventListener('DOMContentLoaded', () => {
    const captcha = new Captcha('captcha');
    const form = document.querySelector('form'); // Sélectionne ton formulaire d'inscription

    form.addEventListener('submit', (event) => {
        const userInput = document.getElementById('captchaInput').value;

        // Vérifie si le CAPTCHA est valide
        if (!captcha.validateCaptcha(userInput)) {
            event.preventDefault(); // Empêche l'envoi du formulaire
            alert('CAPTCHA invalide. Veuillez réessayer.');
        } else {
            document.getElementById('hiddenCaptchaValue').value = captcha.captchaValue;
        }
    });
});


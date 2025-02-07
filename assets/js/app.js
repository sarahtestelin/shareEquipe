import '../css/app.css'; 
import './components/captcha.js';
import '../css/captcha.css'; 
import './components/statinscrits.js';
import './components/jourconnexion.js';
import './components/strengthbar.js';
import '../css/strengthbar.css';

// Importer le composant captcha
import Captcha from './components/captcha';
import StrengthBar from './components/strengthbar';

// Instancier le composant captcha pour chaque élément avec la classe .captcha
document.querySelectorAll('.captcha').forEach(element => {
    new Captcha(element);
});

document.addEventListener("DOMContentLoaded", () => {
    new StrengthBar("registration_form_plainPassword", "password-strength-container", "password-strength-bar", "password-strength-text");
});

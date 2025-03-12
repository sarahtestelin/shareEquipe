import '../css/app.css'; 
import './components/captcha.js';
import '../css/captcha.css'; 
import './components/statinscrits.js';
import './components/jourconnexion.js';
import './components/strengthbar.js';
import '../css/strengthbar.css';
import './checkboxCategoryState.js';

// Importer le composant captcha
import Captcha from './components/captcha';
import StrengthBar from './components/strengthbar';
import CheckboxCategoryState from './checkboxCategoryState';

// Instancier le composant captcha pour chaque élément avec la classe .captcha
document.querySelectorAll('.captcha').forEach(element => {
    new Captcha(element);
});

document.addEventListener("DOMContentLoaded", () => {
    new StrengthBar();
    new CheckboxCategoryState();
});

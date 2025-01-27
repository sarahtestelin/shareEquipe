import '../css/app.css'; 
import './components/captcha.js';
import '../css/captcha.css'; 


// Importer le composant captcha
import Captcha from './components/captcha';

// Instancier le composant captcha pour chaque élément avec la classe .captcha
document.querySelectorAll('.captcha').forEach(element => {
    new Captcha(element);
});
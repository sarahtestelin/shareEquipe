class CheckboxCategoryState {
    constructor() {
        this.categorieCheckbox = document.querySelectorAll("input[type='checkbox'][name^='gestion_categories_form[categories]']");
        this.sCategorieCheckbox = document.querySelectorAll("input[type='checkbox'][name^='gestion_categories_form[scategories]']");
        this.attachCategoryListeners();
        this.attachSubCategoryListeners();
    }

    // Ajouter un événement sur chaque checkbox de catégorie
    attachCategoryListeners() {
        this.categorieCheckbox.forEach(categoryCheckbox => {
            categoryCheckbox.addEventListener("change", (event) => {
                this.handleCategoryChange();
            });
        });
    }

    // Ajouter un événement sur chaque checkbox de sous-catégorie
    attachSubCategoryListeners() {
        this.sCategorieCheckbox.forEach(subCategoryCheckbox => {
            subCategoryCheckbox.addEventListener("change", (event) => {
                this.handleSubCategoryChange();
            });
        });
    }

    // Etat des catégories
    handleCategoryChange() {
        const atLeastOneCategoryChecked = Array.from(this.categorieCheckbox).some(categoryCheckbox => categoryCheckbox.checked);  // Vérifier s'il y a au moins une catégorie cochée

        // Si au moins une catégorie est cochée, désactiver toutes les sous-catégories
        if (atLeastOneCategoryChecked) {
            this.sCategorieCheckbox.forEach(subCategoryCheckbox => {
                subCategoryCheckbox.disabled = true;
            });
        } else {
            // Si aucune catégorie n'est cochée, réactiver les sous-catégories
            this.sCategorieCheckbox.forEach(subCategoryCheckbox => {
                subCategoryCheckbox.disabled = false;
            });
        }
    }

    // Etat des sous-catégories
    handleSubCategoryChange() {
        const atLeastOneSubCategoryChecked = Array.from(this.sCategorieCheckbox).some(subCategoryCheckbox => subCategoryCheckbox.checked); // Vérifier s'il y a au moins une sous-catégorie cochée

        // Si au moins une sous-catégorie est cochée, désactiver toutes les catégories
        if (atLeastOneSubCategoryChecked) {
            this.categorieCheckbox.forEach(categoryCheckbox => {
                categoryCheckbox.disabled = true;
            });
        } else {
            // Si aucune sous-catégorie n'est cochée, réactiver les catégories
            this.categorieCheckbox.forEach(categoryCheckbox => {
                categoryCheckbox.disabled = false;
            });
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    new CheckboxCategoryState();
});

export default CheckboxCategoryState;

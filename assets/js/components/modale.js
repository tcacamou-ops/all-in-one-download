jQuery(document).ready(function ($) {
// Affiche la modale avec le contenu passé en paramètre
allI1d.showModale = function alli1dShowModal(content) {
    // Supprime une éventuelle modale déjà présente
    allI1d.closeModale();
    // Crée le HTML de la modale
    const modalHtml = `
        <div id="alli1d-modal-overlay" class="alli1d-modal-overlay">
            <div class="alli1d-modal">
                <button class="alli1d-modal-close" aria-label="Fermer">&times;</button>
                <div class="alli1d-modal-content">${content}</div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    // Ajoute l'écouteur pour fermer la modale
    document.querySelector('.alli1d-modal-close').addEventListener('click', allI1d.closeModale);
    document.addEventListener('keydown', alli1dModalEscListener);
}

// Ferme la modale
allI1d.closeModale = function alli1dCloseModal() {
    const overlay = document.getElementById('alli1d-modal-overlay');
    if (overlay) overlay.remove();
    document.removeEventListener('keydown', alli1dModalEscListener);
}

// Ferme la modale avec la touche Echap
function alli1dModalEscListener(e) {
    if (e.key === 'Escape') {
        allI1d.closeModale();
    }
}
});
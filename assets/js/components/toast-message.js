jQuery(document).ready(function ($) {
    allI1d.showToast = function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
    
        // Créer un élément toast.
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerText = message;
    
        // Ajouter le toast au conteneur.
        toastContainer.appendChild(toast);
    
        // Supprimer le toast après 3 secondes.
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    $(allI1d).on('allI1d:refreshMedias', function (response) {
        allI1d.showToast("Media refreshed successfully", "success");
    });

});
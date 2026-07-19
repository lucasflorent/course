// Ajoute un bouton "Afficher/Masquer" a cote de chaque champ mot de passe.
(function () {
    function initTogglesMotDePasse() {
        document.querySelectorAll('input[type="password"]').forEach(function (champ) {
            if (champ.dataset.toggleAjoute) {
                return;
            }
            champ.dataset.toggleAjoute = '1';

            var conteneur = document.createElement('div');
            conteneur.className = 'champ-mdp';
            champ.parentNode.insertBefore(conteneur, champ);
            conteneur.appendChild(champ);

            var bouton = document.createElement('button');
            bouton.type = 'button';
            bouton.className = 'bouton-secondaire bouton-oeil';
            bouton.textContent = 'Afficher';
            bouton.setAttribute('aria-label', 'Afficher le mot de passe');

            bouton.addEventListener('click', function () {
                var estMasque = champ.type === 'password';
                champ.type = estMasque ? 'text' : 'password';
                bouton.textContent = estMasque ? 'Masquer' : 'Afficher';
                bouton.setAttribute('aria-label', estMasque ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            });

            conteneur.appendChild(bouton);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTogglesMotDePasse);
    } else {
        initTogglesMotDePasse();
    }
})();

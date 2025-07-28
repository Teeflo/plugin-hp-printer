# Configuration du Plugin HP Printer

Après l'installation, chaque imprimante HP que vous souhaitez surveiller doit être configurée comme un équipement dans Jeedom.

## Ajout d'un Équipement (Imprimante)

1.  **Accéder à la Page du Plugin :**
    *   Allez dans `Plugins` > `Communication` > `HP Printer`.

2.  **Ajouter une Nouvelle Imprimante :**
    *   Cliquez sur le bouton `Ajouter` (le grand `+` vert) pour créer un nouvel équipement.

3.  **Paramètres Généraux :**
    *   **Nom de l'équipement :** Saisissez un nom descriptif pour votre imprimante (ex: "Imprimante Bureau", "HP Envy Salon"). Ce nom apparaîtra dans Jeedom.
    *   **Objet parent :** Associez cette imprimante à un objet Jeedom existant (ex: "Bureau", "Salon", "Maison"). Cela aide à organiser vos équipements.
    *   **Catégorie :** Cochez les catégories pertinentes pour votre imprimante (ex: "Multimédia", "Bureau", "Énergie").
    *   **Options :**
        *   `Activer` : Cochez cette case pour que l'équipement soit actif et que le plugin puisse interagir avec l'imprimante.
        *   `Visible` : Cochez cette case pour que l'équipement apparaisse sur votre Dashboard Jeedom.

4.  **Paramètres Spécifiques :**
    *   **Adresse IP :** C'est le paramètre le plus important. Saisissez l'adresse IP locale de votre imprimante HP (ex: `192.168.1.100`). Vous pouvez généralement trouver cette information sur l'écran de l'imprimante, via l'interface EWS de l'imprimante, ou via l'interface de votre routeur.
    *   **Protocole :** Choisissez le protocole de communication. La plupart des imprimantes HP utilisent `HTTP` par défaut pour leur interface EWS. Si votre imprimante est configurée pour `HTTPS`, sélectionnez cette option.
    *   **Vérifier le certificat SSL :**
        *   Si vous utilisez `HTTPS`, il est **fortement recommandé** de laisser cette option `Activé`.
        *   Si vous rencontrez des problèmes de connexion avec `HTTPS` et que vous êtes sûr de la sécurité de votre réseau local, vous pouvez temporairement la désactiver pour le dépannage. Soyez conscient que cela réduit la sécurité en cas d'attaques "man-in-the-middle".
    *   **Auto-actualisation :** Définissez la fréquence à laquelle le plugin doit interroger l'imprimante pour récupérer les dernières informations.
        *   Utilisez le format cron (ex: `*/5 * * * *` pour une actualisation toutes les 5 minutes).
        *   Cliquez sur l'icône `?` à côté du champ pour ouvrir l'assistant cron de Jeedom, qui vous aidera à générer l'expression.
    *   **Bouton "Tester" :** Après avoir saisi l'adresse IP et le protocole, cliquez sur ce bouton pour vérifier que Jeedom peut établir une connexion avec votre imprimante. Un message de succès ou d'erreur s'affichera.

5.  **Sauvegarder l'Équipement :**
    *   Une fois tous les paramètres configurés, cliquez sur le bouton `Sauvegarder` en haut de la page.
    *   Les commandes d'information et d'action seront automatiquement créées pour votre imprimante.

Répétez ces étapes pour chaque imprimante HP que vous souhaitez intégrer à Jeedom.

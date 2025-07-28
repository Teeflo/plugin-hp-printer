# Utilisation du Plugin HP Printer

Une fois votre imprimante HP configurée et sauvegardée dans Jeedom, le plugin créera automatiquement des commandes pour interagir et récupérer des informations.

## Commandes d'Information

Les commandes d'information sont utilisées pour afficher l'état et les données de votre imprimante. Elles sont visibles sur la page de l'équipement et peuvent être affichées sur votre Dashboard Jeedom.

*   **Modèle :** Le modèle de l'imprimante (ex: "HP OfficeJet Pro 9010").
*   **Numéro de série :** Le numéro de série unique de l'imprimante.
*   **Numéro produit :** Le numéro de produit de l'imprimante.
*   **Emplacement :** L'emplacement configuré de l'imprimante.
*   **Statut mot de passe :** Indique si un mot de passe est défini pour l'EWS.
*   **Délai veille :** Le temps avant que l'imprimante n'entre en mode veille.
*   **Arrêt automatique :** Le temps avant que l'imprimante ne s'éteigne automatiquement.
*   **Niveau encre couleur (CMY) :** Le pourcentage restant d'encre couleur (Cyan, Magenta, Jaune).
*   **Niveau encre noire (K) :** Le pourcentage restant d'encre noire.
*   **État cartouche couleur :** Le statut de la cartouche couleur (ex: "Bon", "Faible", "Vide").
*   **État cartouche noire :** Le statut de la cartouche noire.
*   **Marque cartouche couleur/noire :** La marque des cartouches installées.
*   **Modèle cartouche couleur/noire :** Le modèle des cartouches installées.
*   **Statut imprimante :** Le statut général de l'imprimante (ex: "Prête", "Occupée", "Erreur", "Hors ligne").
*   **Total impressions :** Le nombre total de pages imprimées depuis l'installation de l'imprimante.
*   **Impressions couleur/monochrome :** Le nombre de pages imprimées en couleur et en monochrome.
*   **Feuilles recto/recto-verso :** Le nombre de feuilles imprimées en mode simple et double face.
*   **Événements bourrage :** Le nombre de bourrages papier détectés.
*   **Mauvaises prises papier :** Le nombre d'événements de mauvaise prise papier.
*   **Encre utilisée (cumul) :** La quantité totale d'encre utilisée en ml.
*   **Nombre total de scans :** Le nombre total de documents scannés.
*   **Nombre total de copies :** Le nombre total de copies effectuées.
*   **Impressions réseau/Wi-Fi :** Le nombre d'impressions effectuées via le réseau filaire ou Wi-Fi.
*   **Nom d'hôte :** Le nom d'hôte actuel de l'imprimante sur le réseau.
*   **Nom d'hôte par défaut :** Le nom d'hôte par défaut de l'imprimante.
*   **ePrint Email/Mobile/Enregistrement/Connexion :** Le statut des différents services ePrint.

## Commandes d'Action

Les commandes d'action vous permettent de déclencher des opérations sur votre imprimante.

*   **Rafraîchir :** Cette commande force le plugin à récupérer immédiatement toutes les dernières informations de l'imprimante. Utile si vous avez besoin d'une mise à jour instantanée en dehors du cycle d'auto-actualisation.
*   **Test connexion :** Permet de vérifier la connectivité entre votre Jeedom et l'imprimante. Un message de succès ou d'échec s'affichera.
*   **Nettoyage des têtes :** Envoie une commande à l'imprimante pour lancer un cycle de nettoyage des têtes d'impression. Utilisez cette fonction si vous constatez des problèmes de qualité d'impression (bandes, couleurs manquantes).

## Affichage sur le Dashboard

Pour afficher les informations de votre imprimante sur le Dashboard Jeedom :

1.  Allez sur la page de l'équipement HP Printer.
2.  Pour chaque commande d'information que vous souhaitez afficher, cliquez sur l'icône d'engrenage à droite de la commande.
3.  Dans la fenêtre de configuration avancée, cochez `Afficher sur le Dashboard`.
4.  Vous pouvez également configurer l'historisation (`Historiser`) pour suivre l'évolution des niveaux d'encre ou des statistiques d'utilisation au fil du temps.

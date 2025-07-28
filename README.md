# Plugin HP Printer pour Jeedom

![Version](https://img.shields.io/badge/version-0.1-blue.svg)
![Jeedom](https://img.shields.io/badge/Jeedom-4.0%2B-green.svg)
![License](https://img.shields.io/badge/license-GPL--3.0-orange.svg)

## 📄 Description
Ce plugin Jeedom permet de récupérer et d'afficher les informations de diverses imprimantes HP via leur interface web embarquée (EWS - Embedded Web Server). Il offre une intégration transparente pour surveiller l'état de votre imprimante, les niveaux d'encre, les statistiques d'utilisation et bien plus encore, directement depuis votre système Jeedom.

## ✨ Fonctionnalités principales
- **Surveillance Complète :** Récupère et affiche des informations détaillées sur l'imprimante (modèle, numéro de série, emplacement, statut du mot de passe).
- **Niveaux de Consommables :** Suivi précis des niveaux d'encre (couleur et noir), de l'état et de la marque des cartouches.
- **Statut en Temps Réel :** Affiche le statut actuel de l'imprimante (prête, hors ligne, erreur, etc.).
- **Statistiques d'Utilisation :** Accédez aux données d'utilisation telles que le nombre total d'impressions, les impressions couleur/monochrome, les feuilles recto/recto-verso, les événements de bourrage, les scans et les copies.
- **Informations Réseau :** Récupère le nom d'hôte et d'autres détails réseau de l'imprimante.
- **Services ePrint :** Surveille le statut des services ePrint (email, mobile, enregistrement, connexion).
- **Actions Manuelles :** Permet de rafraîchir manuellement les données et de tester la connexion à l'imprimante.
- **Nettoyage des Têtes :** Lance un cycle de nettoyage des têtes d'impression directement depuis Jeedom.
- **Actualisation Automatique :** Configurez une fréquence d'actualisation automatique des données de l'imprimante.

## 🚀 Installation
L'installation du plugin HP Printer est simple et suit la procédure standard des plugins Jeedom :

1.  **Téléchargement :** Depuis l'interface Jeedom, allez dans `Plugins` > `Gestion des plugins` > `Market`. Recherchez "HP Printer" et cliquez sur `Installer`.
2.  **Activation :** Une fois l'installation terminée, activez le plugin.
3.  **Dépendances :** Ce plugin ne nécessite pas de dépendances externes spécifiques.
4.  **Configuration Initiale :** Après l'activation, vous serez redirigé vers la page de configuration du plugin.

## ⚙️ Configuration
Après l'installation, vous devrez configurer chaque équipement (imprimante) que vous souhaitez surveiller :

1.  **Accéder aux Équipements :** Allez dans `Plugins` > `Communication` > `HP Printer`.
2.  **Ajouter une Imprimante :** Cliquez sur le bouton `Ajouter` pour créer un nouvel équipement.
3.  **Paramètres Généraux :**
    *   **Nom de l'équipement :** Donnez un nom significatif à votre imprimante (ex: "Imprimante Bureau").
    *   **Objet parent :** Associez l'imprimante à un objet Jeedom existant (ex: "Bureau", "Salon").
    *   **Catégorie :** Attribuez une ou plusieurs catégories (ex: "Multimédia", "Bureau").
    *   **Options :** Cochez `Activer` pour activer l'équipement et `Visible` pour l'afficher sur le dashboard.
4.  **Paramètres Spécifiques :**
    *   **Adresse IP :** Saisissez l'adresse IP de votre imprimante HP sur votre réseau local (ex: `192.168.1.10`).
    *   **Protocole :** Choisissez `HTTP` ou `HTTPS` en fonction de la configuration de votre imprimante. La plupart des imprimantes utilisent `HTTP` par défaut pour leur EWS.
    *   **Vérifier le certificat SSL :** Si vous utilisez `HTTPS`, il est recommandé de laisser cette option activée. Si vous rencontrez des problèmes de connexion avec HTTPS, vous pouvez la désactiver (à vos risques et périls, car cela peut rendre votre système vulnérable aux attaques "man-in-the-middle").
    *   **Auto-actualisation :** Définissez la fréquence à laquelle le plugin doit récupérer les données de l'imprimante. Utilisez l'assistant cron (icône `?`) pour vous aider à définir une expression cron (ex: `*/5 * * * *` pour toutes les 5 minutes).
    *   **Bouton "Tester" :** Utilisez ce bouton pour vérifier que Jeedom peut communiquer avec votre imprimante.

5.  **Sauvegarder :** N'oubliez pas de cliquer sur `Sauvegarder` après avoir configuré votre équipement.

## 🖥️ Utilisation
Une fois votre imprimante configurée et sauvegardée, les commandes d'information seront automatiquement créées.

*   **Dashboard :** Les informations principales (niveaux d'encre, statut) seront visibles sur votre dashboard Jeedom.
*   **Page de l'équipement :** Accédez à la page de l'équipement (via `Plugins` > `Communication` > `HP Printer` puis cliquez sur votre imprimante) pour voir toutes les commandes d'information et d'action.
*   **Commandes d'Action :**
    *   **Rafraîchir :** Force une mise à jour immédiate de toutes les données de l'imprimante.
    *   **Test connexion :** Vérifie la connectivité avec l'imprimante.
    *   **Nettoyage des têtes :** Envoie une commande à l'imprimante pour lancer un cycle de nettoyage des têtes.

## ❓ Dépannage
*   **Connexion échouée :**
    *   Vérifiez que l'adresse IP de l'imprimante est correcte.
    *   Assurez-vous que l'imprimante est allumée et connectée au même réseau que votre Jeedom.
    *   Essayez de changer le protocole (HTTP/HTTPS).
    *   Si HTTPS est utilisé, essayez de désactiver temporairement la vérification SSL (uniquement pour le test).
    *   Vérifiez que l'EWS de l'imprimante est accessible depuis un navigateur web sur un autre appareil de votre réseau.
*   **Données non mises à jour :**
    *   Vérifiez l'expression cron de l'auto-actualisation.
    *   Lancez une actualisation manuelle via la commande `Rafraîchir`.
    *   Consultez les logs du plugin (`Analyse` > `Logs` > `hp_printer`) pour des messages d'erreur.

## 🤝 Contribution
Les contributions sont les bienvenues ! Si vous souhaitez améliorer ce plugin, signaler un bug ou proposer de nouvelles fonctionnalités, n'hésitez pas à :
1.  Ouvrir une issue sur le [dépôt GitHub](https://github.com/votre_utilisateur/plugin-hp-printer/issues).
2.  Soumettre une Pull Request avec vos modifications.

## 📜 Changelog
Consultez le [changelog](https://doc.jeedom.com/fr_FR/plugins/home%20automation/hp_printer/changelog) pour l'historique des versions et les nouvelles fonctionnalités.

## ⚖️ Licence
Ce plugin est distribué sous la licence [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

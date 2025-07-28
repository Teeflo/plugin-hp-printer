# Changelog du Plugin HP Printer

Ce document retrace les modifications et les nouvelles fonctionnalités apportées au plugin HP Printer.

## Version 0.1 (2025-07-29)

*   **Nouvelles fonctionnalités :**
    *   Ajout de la récupération des statistiques d'utilisation (total impressions, couleur, monochrome, recto/verso, bourrages, scans, copies).
    *   Ajout de la récupération des informations réseau (nom d'hôte).
    *   Ajout de la récupération du statut des services ePrint (email, mobile, enregistrement, connexion).
    *   Ajout de la commande d'action "Nettoyage des têtes".
    *   Amélioration de l'extraction des données XML pour une meilleure compatibilité avec différents modèles d'imprimantes.
*   **Améliorations :**
    *   Internationalisation complète du plugin (fr_FR, en_US, es_ES, de_DE, it_IT, pt_PT, ru_RU, ja_JP, nl_NL, tr_TR).
    *   Mise à jour du `README.md` avec une documentation plus détaillée.
    *   Optimisation du fichier `info.json` avec des métadonnées complètes et des descriptions multilingues.
    *   Amélioration de la gestion des erreurs et des logs pour le test de connexion et l'extraction des données.
*   **Corrections de bugs :**
    *   Correction du problème où les actions "Test connexion" et "Créer commandes" n'étaient pas gérées par le fichier AJAX.
    *   Correction de divers problèmes mineurs de compatibilité et de robustesse.

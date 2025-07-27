# Manuel de Tests pour le Plugin HP Printer

## Pré-requis

- Un système Jeedom fonctionnel.
- Le plugin HP Printer installé.
- Une imprimante HP compatible connectée au même réseau que Jeedom.

## 1. Test de la Connexion

1.  **Objectif:** Vérifier que le plugin peut se connecter à l'imprimante.
2.  **Étapes:**
    1.  Naviguer vers la page de configuration du plugin HP Printer.
    2.  Ajouter un nouvel équipement.
    3.  Entrer l'adresse IP de l'imprimante.
    4.  Sélectionner le protocole (HTTP ou HTTPS).
    5.  Cliquer sur le bouton "Tester".
3.  **Résultat Attendu:**
    - Un message de succès "Connection successful!" doit apparaître.
    - Si l'adresse IP est incorrecte, un message d'erreur doit apparaître.

## 2. Test de la Vérification SSL

1.  **Objectif:** Vérifier que l'option de vérification SSL fonctionne correctement.
2.  **Étapes:**
    1.  Configurer l'imprimante pour utiliser HTTPS.
    2.  Activer l'option "Vérifier le certificat SSL".
    3.  Cliquer sur le bouton "Tester".
    4.  Désactiver l'option "Vérifier le certificat SSL".
    5.  Cliquer sur le bouton "Tester".
3.  **Résultat Attendu:**
    - Si le certificat de l'imprimante est auto-signé, le test avec la vérification SSL activée doit échouer.
    - Le test avec la vérification SSL désactivée doit réussir.

## 3. Test de la Récupération des Données

1.  **Objectif:** Vérifier que le plugin récupère correctement les données de l'imprimante.
2.  **Étapes:**
    1.  Configurer un équipement avec une adresse IP valide.
    2.  Sauvegarder l'équipement.
    3.  Aller dans l'onglet "Commandes".
    4.  Cliquer sur le bouton "Rafraîchir les données".
5.  **Résultat Attendu:**
    - Les commandes doivent être peuplées avec les données de l'imprimante.
    - Les niveaux de toner, le nombre de pages imprimées, etc., doivent être corrects.

## 4. Test du Cron

1.  **Objectif:** Vérifier que le cron de rafraîchissement des données fonctionne.
2.  **Étapes:**
    1.  Configurer un équipement et s'assurer qu'il est activé.
    2.  Attendre que le cron s'exécute (toutes les 5 minutes par défaut).
3.  **Résultat Attendu:**
    - Les données de l'imprimante doivent être mises à jour automatiquement.

## 5. Test de la Protection CSRF

1.  **Objectif:** Vérifier que la protection CSRF est active.
2.  **Étapes:**
    1.  Ouvrir les outils de développement du navigateur.
    2.  Essayer d'exécuter une requête AJAX vers `plugins/hp_printer/core/ajax/hp_printer.ajax.php` sans le paramètre `apikey`.
3.  **Résultat Attendu:**
    - La requête doit échouer avec une erreur "Accès non autorisé".
    - La même requête avec le paramètre `apikey` doit réussir.

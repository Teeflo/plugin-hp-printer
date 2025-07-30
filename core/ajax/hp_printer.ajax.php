<?php

// Ce fichier gère les requêtes AJAX pour le plugin HP Printer.
// Il assure la sécurité, l'authentification et le traitement des actions spécifiques.

// Vérifications de sécurité et d'inclusion avec détection automatique du chemin
// Définit les chemins possibles pour trouver le fichier `core.inc.php` de Jeedom.
// Cela permet au plugin de fonctionner quelle que soit sa profondeur dans l'arborescence des plugins.
$possibleCorePaths = [
    dirname(__FILE__) . '/../../../core/php/core.inc.php',
    dirname(__FILE__) . '/../../../../core/php/core.inc.php',
    dirname(__FILE__) . '/../../../../../core/php/core.inc.php',
    __DIR__ . '/../../../core/php/core.inc.php',
    __DIR__ . '/../../../../core/php/core.inc.php'
];

// Initialisation des drapeaux de recherche du core
// `coreFound` indique si le fichier `core.inc.php` a été trouvé.
$coreFound = false;
// `usedCorePath` stocke le chemin absolu du fichier `core.inc.php` une fois trouvé.
$usedCorePath = '';

// Parcours des chemins possibles pour trouver le fichier core.inc.php de Jeedom
foreach ($possibleCorePaths as $corePath) {
    // Vérifie si le fichier existe à l'emplacement actuel
    if (file_exists($corePath)) {
        $coreFound = true;
        $usedCorePath = $corePath;

        // Sort de la boucle dès que le fichier est trouvé
        break;
    }
}

// Si le fichier core.inc.php n'est pas trouvé après avoir parcouru tous les chemins
// Cette condition est critique pour le bon fonctionnement du plugin.
if (!$coreFound) {
    // Arrête l'exécution et renvoie une erreur JSON
    // Le message d'erreur inclut les chemins testés pour faciliter le débogage.
    die(json_encode(array('state' => 'error', 'result' => 'Core Jeedom non trouvé. Chemins testés: ' . implode(', ', $possibleCorePaths))));
}

// Bloc try-catch pour gérer les exceptions et renvoyer des réponses AJAX appropriées.
try {
	// Inclusion du fichier core de Jeedom
	// Ce fichier est essentiel car il contient les fonctions et classes de base de Jeedom.
	require_once $usedCorePath;

	// Inclusion du fichier d'authentification de Jeedom
	// Ce fichier contient la fonction `isConnect` utilisée pour vérifier les droits de l'utilisateur.
	include_file('core', 'authentification', 'php');

	// Vérifie si l'utilisateur est connecté en tant qu'administrateur
	// Seuls les administrateurs sont autorisés à exécuter les actions AJAX de ce plugin.
	if (!isConnect('admin')) {
		// Si non, lance une exception pour accès non autorisé
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	// Initialise la gestion des requêtes AJAX de Jeedom
	// La classe `ajax` de Jeedom gère la structure des réponses JSON et les messages d'erreur/succès.
	ajax::init();

	// Récupère l'action demandée par la requête AJAX
	// La fonction `init` est une fonction utilitaire de Jeedom pour récupérer les paramètres de requête.
	$action = init('action');

	// Vérifie si une action a été spécifiée
	// Toutes les requêtes AJAX doivent inclure un paramètre 'action' pour déterminer l'opération à effectuer.
	if (empty($action)) {
		// Si aucune action n'est spécifiée, lance une exception
		throw new Exception(__('Aucune action spécifiée', __FILE__));
	}

	// Exécute l'action en fonction de la valeur de $action
	switch ($action) {
		// Cas où l'action est 'pullData' (rafraîchissement des données de l'imprimante)
		// Cette action est appelée pour mettre à jour les informations de l'imprimante.
		case 'pullData':
			// Récupère l'ID de l'équipement logique
			$eqLogicId = init('eqLogic_id');

			// Valide si l'ID de l'équipement est numérique
			// S'assure que l'ID fourni est un nombre valide pour éviter les injections ou les erreurs.
			if (!is_numeric($eqLogicId)) {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Invalid eqLogic ID', __FILE__));
			}

			// Récupère l'objet eqLogic correspondant à l'ID
			// Charge l'objet équipement Jeedom à partir de son ID.
			$eqLogic = eqLogic::byId($eqLogicId);

			// Vérifie si l'équipement existe et s'il est bien lié au plugin hp_printer
			// S'assure que l'équipement est valide et appartient bien à ce plugin.
			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Equipment not found or not linked to HP Printer plugin', __FILE__));
			}

			// Vérifie les droits de l'utilisateur sur le tableau de bord pour cet équipement
			// L'utilisateur doit avoir les droits de lecture ('r') sur le tableau de bord pour cet équipement.
			if (!security::hasRight('dashboard', 'r', $eqLogic->getHumanName())) {
				// Si l'utilisateur n'a pas les droits, renvoie une erreur AJAX
				ajax::error(__('You are not authorized to perform this action', __FILE__));
			}

			try {
				// Appelle la méthode cronPullData de l'équipement pour rafraîchir les données
				// Cette méthode est responsable de la récupération des données de l'imprimante.
				$eqLogic->cronPullData();

				// Renvoie un message de succès AJAX
				ajax::success(__('Data refreshed successfully', __FILE__));
			} catch (Exception $e) {
				// En cas d'erreur lors du rafraîchissement, renvoie un message d'erreur AJAX
				ajax::error(__('Error refreshing data: ', __FILE__) . $e->getMessage());
			}
			break;

		// Cas où l'action est 'createCommands' (création des commandes pour un équipement)
		// Cette action est appelée pour générer ou mettre à jour les commandes associées à un équipement.
		case 'createCommands':
			// Récupère l'ID de l'équipement logique
			$eqLogicId = init('eqLogic_id');

			// Valide si l'ID de l'équipement est numérique
			// S'assure que l'ID fourni est un nombre valide.
			if (!is_numeric($eqLogicId)) {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Invalid eqLogic ID', __FILE__));
			}

			// Récupère l'objet eqLogic correspondant à l'ID
			// Charge l'objet équipement Jeedom.
			$eqLogic = eqLogic::byId($eqLogicId);

			// Vérifie si l'équipement existe et s'il est bien lié au plugin hp_printer
			// S'assure que l'équipement est valide et appartient à ce plugin.
			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Equipment not found or not linked to HP Printer plugin', __FILE__));
			}

			// Vérifie les droits d'écriture de l'utilisateur sur le tableau de bord pour cet équipement
			// L'utilisateur doit avoir les droits d'écriture ('w') pour créer ou modifier des commandes.
			if (!security::hasRight('dashboard', 'w', $eqLogic->getHumanName())) {
				// Si l'utilisateur n'a pas les droits, renvoie une erreur AJAX
				ajax::error(__('You are not authorized to perform this action', __FILE__));
			}

			try {
				// Appelle la méthode postSave de l'équipement pour créer/mettre à jour les commandes
				// Cette méthode est définie dans la classe `hp_printer` et gère la logique de création des commandes.
				$eqLogic->postSave();

				// Renvoie un message de succès AJAX
				ajax::success(__('Commandes créées avec succès.', __FILE__));
			} catch (Exception $e) {
				// En cas d'erreur lors de la création des commandes, renvoie un message d'erreur AJAX
				ajax::error(__('Erreur lors de la création des commandes: ', __FILE__) . $e->getMessage());
			}
			break;

		// Cas où l'action est 'testConnection' (test de connexion à l'imprimante)
		// Cette action est utilisée pour vérifier la connectivité avec l'imprimante HP.
		case 'testConnection':
			// Récupère l'adresse IP de l'imprimante
			$ipAddress = init('ipAddress');

			// Vérifie si l'adresse IP est fournie
			// L'adresse IP est un paramètre obligatoire pour le test de connexion.
			if (empty($ipAddress)) {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('L\'adresse IP est requise pour le test de connexion.', __FILE__));
			}

			// Récupère le protocole (HTTP ou HTTPS), par défaut HTTP
			// Le protocole est utilisé pour construire l'URL de l'imprimante.
			$protocol = init('protocol', 'http');

			// Construit l'URL de l'API de l'imprimante pour récupérer les informations de configuration
			// L'endpoint `ProductConfigDyn.xml` est utilisé pour un test de connexion simple.
			$url = $protocol . '://' . $ipAddress . '/DevMgmt/ProductConfigDyn.xml';

			// Initialise une session cURL
			// cURL est utilisé pour effectuer des requêtes HTTP/HTTPS vers l'imprimante.
			$ch = curl_init();

			// Configure les options cURL
			curl_setopt_array($ch, [
				CURLOPT_URL => $url, // L'URL à appeler
				CURLOPT_RETURNTRANSFER => true, // Retourne le transfert sous forme de chaîne au lieu de l'afficher
				CURLOPT_TIMEOUT => 5, // Temps maximum en secondes pour l'exécution de la fonction cURL
				CURLOPT_CONNECTTIMEOUT => 3, // Temps maximum en secondes pour tenter de se connecter
				CURLOPT_SSL_VERIFYPEER => false, // Désactive la vérification du certificat SSL (utile pour les certificats auto-signés)
				CURLOPT_SSL_VERIFYHOST => false, // Désactive la vérification du nom d'hôte SSL
				CURLOPT_USERAGENT => 'Jeedom HP Printer Plugin Test', // Définit l'agent utilisateur
			]);

			// Exécute la requête cURL
			$response = curl_exec($ch);

			// Récupère le code HTTP de la réponse
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			// Récupère le numéro d'erreur cURL
			$curlErrno = curl_errno($ch);

			// Récupère la description de l'erreur cURL
			$curlError = curl_error($ch);

			// Ferme la session cURL
			curl_close($ch);

			// Vérifie si une erreur cURL s'est produite ou si le code HTTP n'est pas 200 (OK)
			// Un code HTTP 200 indique une réponse réussie du serveur de l'imprimante.
			if ($curlErrno !== 0 || $httpCode !== 200) {
				// Construit les détails de l'erreur
				$errorDetails = "Code HTTP: $httpCode, Erreur cURL: ($curlErrno) $curlError";

				// Renvoie un message d'erreur AJAX avec les détails
				ajax::error(__('La connexion à l\'imprimante a échoué. Vérifiez l\'adresse IP et que l\'imprimante est en ligne. Détails: ', __FILE__) . $errorDetails);
			}

			// Tente de parser la réponse XML pour s'assurer que c'est une réponse EWS valide
			// Les imprimantes HP renvoient généralement du XML pour leurs informations EWS.
			$xml = simplexml_load_string($response);

			// Vérifie si le parsing XML a échoué
			// Si la réponse n'est pas un XML valide, cela indique un problème même si la connexion a réussi.
			if ($xml === false) {
				// Si le XML est invalide, renvoie une erreur AJAX
				ajax::error(__('La connexion à l\'imprimante a réussi, mais la réponse n\'est pas un XML valide. Détails: ', __FILE__) . substr($response, 0, 200));
			}

			// Si tout est bon, renvoie un message de succès AJAX
			ajax::success(__('La connexion à l\'imprimante est réussie.', __FILE__));
			break;

		// Cas par défaut si l'action n'est pas reconnue
		// Gère les cas où l'action demandée ne correspond à aucune des actions définies.
		default:
			// Renvoie une erreur AJAX pour action inconnue
			ajax::error('Unknown action: ' . $action);
			break;
	}

	
} catch (Exception $e) {
	// Ce bloc gère toutes les exceptions qui peuvent survenir pendant l'exécution des actions AJAX.
	// Il assure une journalisation détaillée et une réponse d'erreur cohérente.

	// Log détaillé de l'erreur
	// Enregistre l'erreur dans les logs de Jeedom pour faciliter le débogage.
	log::add('hp_printer', 'error', 'Erreur AJAX - Action: ' . ($action ?? 'inconnue') . ' - Message: '. $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
	
	// Gestion de la réponse d'erreur selon la version de Jeedom
	// Tente d'utiliser les fonctions de gestion d'exception spécifiques à Jeedom (displayException/displayExeption)
	// pour formater la réponse d'erreur de manière appropriée.
	try {
		// Tente d'utiliser la fonction displayException si elle existe (Jeedom 4.x)
		if (function_exists('displayException')) {
			ajax::error(displayException($e), $e->getCode());
		}
		// Sinon, tente d'utiliser displayExeption (Jeedom 3.x)
		else if (function_exists('displayExeption')) {
			ajax::error(displayExeption($e), $e->getCode());
		}
		// Sinon, renvoie simplement le message de l'exception
		else {
			ajax::error($e->getMessage(), $e->getCode());
		}
	} catch (Exception $ajaxError) {
		// Si même la réponse AJAX échoue, renvoie une réponse JSON manuelle
		// C'est une mesure de dernier recours pour s'assurer qu'une réponse est toujours envoyée au client.
		header('Content-Type: application/json');
		echo json_encode([
			'state' => 'error',
			'result' => 'Erreur système: ' . $e->getMessage()
		]);
	}
}
?>

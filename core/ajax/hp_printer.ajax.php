<?php

// Vérifications de sécurité et d'inclusion avec détection automatique du chemin
$possibleCorePaths = [
    dirname(__FILE__) . '/../../../core/php/core.inc.php',
    dirname(__FILE__) . '/../../../../core/php/core.inc.php',
    dirname(__FILE__) . '/../../../../../core/php/core.inc.php',
    __DIR__ . '/../../../core/php/core.inc.php',
    __DIR__ . '/../../../../core/php/core.inc.php'
];

// Initialisation des drapeaux de recherche du core
$coreFound = false;
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
if (!$coreFound) {
    // Arrête l'exécution et renvoie une erreur JSON
    die(json_encode(array('state' => 'error', 'result' => 'Core Jeedom non trouvé. Chemins testés: ' . implode(', ', $possibleCorePaths))));
}

try {
	// Inclusion du fichier core de Jeedom
	require_once $usedCorePath;

	// Inclusion du fichier d'authentification de Jeedom
	include_file('core', 'authentification', 'php');

	// Vérifie si l'utilisateur est connecté en tant qu'administrateur
	if (!isConnect('admin')) {
		// Si non, lance une exception pour accès non autorisé
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	// Initialise la gestion des requêtes AJAX de Jeedom
	ajax::init();

	// Récupère l'action demandée par la requête AJAX
	$action = init('action');

	// Vérifie si une action a été spécifiée
	if (empty($action)) {
		// Si aucune action n'est spécifiée, lance une exception
		throw new Exception(__('Aucune action spécifiée', __FILE__));
	}

	// Exécute l'action en fonction de la valeur de $action
	switch ($action) {
		// Cas où l'action est 'pullData' (rafraîchissement des données de l'imprimante)
		case 'pullData':
			// Récupère l'ID de l'équipement logique
			$eqLogicId = init('eqLogic_id');

			// Valide si l'ID de l'équipement est numérique
			if (!is_numeric($eqLogicId)) {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Invalid eqLogic ID', __FILE__));
			}

			// Récupère l'objet eqLogic correspondant à l'ID
			$eqLogic = eqLogic::byId($eqLogicId);

			// Vérifie si l'équipement existe et s'il est bien lié au plugin hp_printer
			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Equipment not found or not linked to HP Printer plugin', __FILE__));
			}

			// Vérifie les droits de l'utilisateur sur le tableau de bord pour cet équipement
			if (!security::hasRight('dashboard', 'r', $eqLogic->getHumanName())) {
				// Si l'utilisateur n'a pas les droits, renvoie une erreur AJAX
				ajax::error(__('You are not authorized to perform this action', __FILE__));
			}

			try {
				// Appelle la méthode cronPullData de l'équipement pour rafraîchir les données
				$eqLogic->cronPullData();

				// Renvoie un message de succès AJAX
				ajax::success(__('Data refreshed successfully', __FILE__));
			} catch (Exception $e) {
				// En cas d'erreur lors du rafraîchissement, renvoie un message d'erreur AJAX
				ajax::error(__('Error refreshing data: ', __FILE__) . $e->getMessage());
			}
			break;

		// Cas où l'action est 'createCommands' (création des commandes pour un équipement)
		case 'createCommands':
			// Récupère l'ID de l'équipement logique
			$eqLogicId = init('eqLogic_id');

			// Valide si l'ID de l'équipement est numérique
			if (!is_numeric($eqLogicId)) {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Invalid eqLogic ID', __FILE__));
			}

			// Récupère l'objet eqLogic correspondant à l'ID
			$eqLogic = eqLogic::byId($eqLogicId);

			// Vérifie si l'équipement existe et s'il est bien lié au plugin hp_printer
			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('Equipment not found or not linked to HP Printer plugin', __FILE__));
			}

			// Vérifie les droits d'écriture de l'utilisateur sur le tableau de bord pour cet équipement
			if (!security::hasRight('dashboard', 'w', $eqLogic->getHumanName())) {
				// Si l'utilisateur n'a pas les droits, renvoie une erreur AJAX
				ajax::error(__('You are not authorized to perform this action', __FILE__));
			}

			try {
				// Appelle la méthode postSave de l'équipement pour créer/mettre à jour les commandes
				$eqLogic->postSave();

				// Renvoie un message de succès AJAX
				ajax::success(__('Commandes créées avec succès.', __FILE__));
			} catch (Exception $e) {
				// En cas d'erreur lors de la création des commandes, renvoie un message d'erreur AJAX
				ajax::error(__('Erreur lors de la création des commandes: ', __FILE__) . $e->getMessage());
			}
			break;

		// Cas où l'action est 'testConnection' (test de connexion à l'imprimante)
		case 'testConnection':
			// Récupère l'adresse IP de l'imprimante
			$ipAddress = init('ipAddress');

			// Vérifie si l'adresse IP est fournie
			if (empty($ipAddress)) {
				// Si non, renvoie une erreur AJAX
				ajax::error(__('L\'adresse IP est requise pour le test de connexion.', __FILE__));
			}

			// Récupère le protocole (HTTP ou HTTPS), par défaut HTTP
			$protocol = init('protocol', 'http');

			// Construit l'URL de l'API de l'imprimante pour récupérer les informations de configuration
			$url = $protocol . '://' . $ipAddress . '/DevMgmt/ProductConfigDyn.xml';

			// Initialise une session cURL
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
			if ($curlErrno !== 0 || $httpCode !== 200) {
				// Construit les détails de l'erreur
				$errorDetails = "Code HTTP: $httpCode, Erreur cURL: ($curlErrno) $curlError";

				// Renvoie un message d'erreur AJAX avec les détails
				ajax::error(__('La connexion à l\'imprimante a échoué. Vérifiez l\'adresse IP et que l\'imprimante est en ligne. Détails: ', __FILE__) . $errorDetails);
			}

			// Tente de parser la réponse XML pour s'assurer que c'est une réponse EWS valide
			$xml = simplexml_load_string($response);

			// Vérifie si le parsing XML a échoué
			if ($xml === false) {
				// Si le XML est invalide, renvoie une erreur AJAX
				ajax::error(__('La connexion à l\'imprimante a réussi, mais la réponse n\'est pas un XML valide. Détails: ', __FILE__) . substr($response, 0, 200));
			}

			// Si tout est bon, renvoie un message de succès AJAX
			ajax::success(__('La connexion à l\'imprimante est réussie.', __FILE__));
			break;

		// Cas par défaut si l'action n'est pas reconnue
		default:
			// Renvoie une erreur AJAX pour action inconnue
			ajax::error('Unknown action: ' . $action);
			break;
	}

	
} catch (Exception $e) {
	// Log détaillé de l'erreur
	log::add('hp_printer', 'error', 'Erreur AJAX - Action: ' . ($action ?? 'inconnue') . ' - Message: '. $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
	
	// Gestion de la réponse d'erreur selon la version de Jeedom
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
		header('Content-Type: application/json');
		echo json_encode([
			'state' => 'error',
			'result' => 'Erreur système: ' . $e->getMessage()
		]);
	}
}
?>

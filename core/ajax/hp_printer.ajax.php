<?php

// Vérifications de sécurité et d'inclusion avec détection automatique du chemin
$possibleCorePaths = [
    dirname(__FILE__) . '/../../../core/php/core.inc.php',
    dirname(__FILE__) . '/../../../../core/php/core.inc.php',
    dirname(__FILE__) . '/../../../../../core/php/core.inc.php',
    __DIR__ . '/../../../core/php/core.inc.php',
    __DIR__ . '/../../../../core/php/core.inc.php'
];

$coreFound = false;
$usedCorePath = '';

foreach ($possibleCorePaths as $corePath) {
    if (file_exists($corePath)) {
        $coreFound = true;
        $usedCorePath = $corePath;
        break;
    }
}

if (!$coreFound) {
    die(json_encode(array('state' => 'error', 'result' => 'Core Jeedom non trouvé. Chemins testés: ' . implode(', ', $possibleCorePaths))));
}

try {
	require_once $usedCorePath;
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	ajax::init();

	$action = init('action');
	if (empty($action)) {
		throw new Exception(__('Aucune action spécifiée', __FILE__));
	}

	switch ($action) {
		case 'pullData':
			$eqLogicId = init('eqLogic_id');

			if (!is_numeric($eqLogicId)) {
				ajax::success(array('state' => 'error', 'message' => __('Invalid eqLogic ID', __FILE__)));
			}

			$eqLogic = eqLogic::byId($eqLogicId);

			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				ajax::success(array('state' => 'error', 'message' => __('Equipment not found or not linked to HP Printer plugin', __FILE__)));
			}

			if (!security::hasRight('dashboard', 'r', $eqLogic->getHumanName())) {
				ajax::success(array('state' => 'error', 'message' => __('You are not authorized to perform this action', __FILE__)));
			}

			try {
				$eqLogic->cronPullData();
				ajax::success(array('state' => 'ok', 'message' => __('Data refreshed successfully', __FILE__)));
			} catch (Exception $e) {
				ajax::success(array('state' => 'error', 'message' => __('Error refreshing data: ', __FILE__) . $e->getMessage()));
			}
			break;

		case 'test':
			try {
				log::add('hp_printer', 'info', 'Début du test de connexion');
				
				$ipAddress = init('ip_address');
				$protocol = init('protocol', 'http');
				$verifySslParam = init('verifySsl');
				
				// Gestion intelligente du SSL : désactiver par défaut pour HTTPS avec les imprimantes HP
				$verifySsl = false;
				if ($protocol === 'http') {
					// En HTTP, le paramètre SSL n'a pas d'importance
					$verifySsl = false;
				} else if ($protocol === 'https') {
					// En HTTPS, utiliser le paramètre utilisateur, mais par défaut désactivé
					if ($verifySslParam === 'true' || $verifySslParam === true || $verifySslParam === 1 || $verifySslParam === '1') {
						$verifySsl = true;
					} else {
						$verifySsl = false; // Par défaut, désactivé pour les imprimantes HP
					}
				}

				log::add('hp_printer', 'info', 'Paramètres reçus - IP: ' . $ipAddress . ', Protocole: ' . $protocol . ', SSL: ' . ($verifySsl ? 'activé' : 'désactivé') . ' (param reçu: ' . $verifySslParam . ')');

				if (empty($ipAddress)) {
					log::add('hp_printer', 'error', 'Adresse IP manquante');
					ajax::error(__('L\'adresse IP est requise pour le test de connexion.', __FILE__));
					return;
				}

				// Validation de l'adresse IP
				if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
					log::add('hp_printer', 'error', 'Adresse IP invalide: ' . $ipAddress);
					ajax::error(__('L\'adresse IP fournie n\'est pas valide.', __FILE__));
					return;
				}

				// Validation du protocole
				if (!in_array($protocol, ['http', 'https'])) {
					$protocol = 'http';
				}

				$url = $protocol . "://" . $ipAddress . "/DevMgmt/ProductConfigDyn.xml";
				log::add('hp_printer', 'info', 'Test de connexion vers: ' . $url . ' (SSL verify: ' . ($verifySsl ? 'oui' : 'non') . ')');
				
				$ch = curl_init();
				if (!$ch) {
					log::add('hp_printer', 'error', 'Impossible d\'initialiser cURL');
					ajax::error(__('Erreur d\'initialisation de cURL.', __FILE__));
					return;
				}

				curl_setopt_array($ch, [
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT => 10,
					CURLOPT_CONNECTTIMEOUT => 5,
					CURLOPT_SSL_VERIFYPEER => $verifySsl,
					CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
					CURLOPT_FOLLOWLOCATION => false,
					CURLOPT_USERAGENT => 'Jeedom HP Printer Test',
					CURLOPT_HEADER => false,
					CURLOPT_NOBODY => false
				]);

				$response = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$curlErrno = curl_errno($ch);
				$curlError = curl_error($ch);
				curl_close($ch);

				log::add('hp_printer', 'info', 'Résultat cURL - Code HTTP: ' . $httpCode . ', Erreur cURL: ' . $curlErrno . ' (' . $curlError . ')');

				if ($curlErrno) {
					$errorMessage = __('Erreur cURL: ', __FILE__) . $curlError . ' (Code: ' . $curlErrno . ')';
					
					// Si c'est une erreur SSL, suggérer la solution
					if ($curlErrno == 60) {
						if ($verifySsl) {
							$errorMessage .= __(' - SOLUTION: Décochez "Vérifier le certificat SSL" pour les imprimantes HP.', __FILE__);
						} else {
							$errorMessage .= __(' - SSL désactivé mais erreur persistante. Vérifiez l\'adresse IP.', __FILE__);
						}
					}
					
					log::add('hp_printer', 'error', $errorMessage);
					ajax::error($errorMessage);
					return;
				}

				if ($httpCode !== 200) {
					$errorMessage = __('La connexion à l\'imprimante a échoué. Code HTTP: ', __FILE__) . $httpCode . __(' - Vérifiez l\'adresse IP et que l\'imprimante est en ligne.', __FILE__);
					log::add('hp_printer', 'error', $errorMessage);
					ajax::error($errorMessage);
					return;
				}

				if (empty($response)) {
					$errorMessage = __('Réponse vide de l\'imprimante. Vérifiez que l\'imprimante supporte l\'interface web.', __FILE__);
					log::add('hp_printer', 'error', $errorMessage);
					ajax::error($errorMessage);
					return;
				}

				// Vérifier que la réponse contient du XML valide
				$xml = @simplexml_load_string($response);
				if ($xml === false) {
					$errorMessage = __('Réponse invalide de l\'imprimante. Format XML attendu.', __FILE__);
					log::add('hp_printer', 'error', $errorMessage . ' - Début réponse: ' . substr($response, 0, 200));
					ajax::error($errorMessage);
					return;
				}

				$successMessage = __('La connexion à l\'imprimante est réussie. Imprimante HP détectée', __FILE__);
				if ($protocol === 'https' && !$verifySsl) {
					$successMessage .= __(' (HTTPS sans vérification SSL)', __FILE__);
				} else if ($protocol === 'https' && $verifySsl) {
					$successMessage .= __(' (HTTPS avec certificat validé)', __FILE__);
				}
				$successMessage .= '.';

				log::add('hp_printer', 'info', 'Test de connexion réussi vers ' . $ipAddress . ' (' . $protocol . ')');
				ajax::success($successMessage);
				
			} catch (Exception $e) {
				log::add('hp_printer', 'error', 'Exception dans test de connexion: ' . $e->getMessage());
				ajax::error(__('Erreur lors du test de connexion: ', __FILE__) . $e->getMessage());
				return;
			}
			break;

		case 'createCommands':
			$eqLogicId = init('eqLogic_id');

			if (!is_numeric($eqLogicId)) {
				ajax::success(array('state' => 'error', 'message' => __('Invalid eqLogic ID', __FILE__)));
			}

			$eqLogic = eqLogic::byId($eqLogicId);

			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				ajax::success(array('state' => 'error', 'message' => __('Equipment not found or not linked to HP Printer plugin', __FILE__)));
			}

			if (!security::hasRight('dashboard', 'w', $eqLogic->getHumanName())) {
				ajax::success(array('state' => 'error', 'message' => __('You are not authorized to perform this action', __FILE__)));
			}

			try {
				$eqLogic->postSave();
				ajax::success(array('state' => 'ok', 'message' => __('Commandes créées avec succès.', __FILE__)));
			} catch (Exception $e) {
				ajax::success(array('state' => 'error', 'message' => __('Erreur lors de la création des commandes: ', __FILE__) . $e->getMessage()));
			}
			break;

		default:
			ajax::success(array('state' => 'error', 'message' => 'Unknown action: ' . $action));
			break;
	}
	
} catch (Exception $e) {
	// Log détaillé de l'erreur
	log::add('hp_printer', 'error', 'Erreur AJAX - Action: ' . ($action ?? 'inconnue') . ' - Message: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
	
	// Gestion de la réponse d'erreur selon la version de Jeedom
	try {
		if (function_exists('displayException')) {
			ajax::error(displayException($e), $e->getCode());
		} else if (function_exists('displayExeption')) {
			ajax::error(displayExeption($e), $e->getCode());
		} else {
			ajax::error($e->getMessage(), $e->getCode());
		}
	} catch (Exception $ajaxError) {
		// Si même la réponse AJAX échoue, réponse JSON manuelle
		header('Content-Type: application/json');
		echo json_encode([
			'state' => 'error',
			'result' => 'Erreur système: ' . $e->getMessage()
		]);
	}
}
?>
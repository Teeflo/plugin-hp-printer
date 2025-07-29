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
				ajax::error(__('Invalid eqLogic ID', __FILE__));
			}

			$eqLogic = eqLogic::byId($eqLogicId);

			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				ajax::error(__('Equipment not found or not linked to HP Printer plugin', __FILE__));
			}

			if (!security::hasRight('dashboard', 'r', $eqLogic->getHumanName())) {
				ajax::error(__('You are not authorized to perform this action', __FILE__));
			}

			try {
				$eqLogic->cronPullData();
				ajax::success(__('Data refreshed successfully', __FILE__));
			} catch (Exception $e) {
				ajax::error(__('Error refreshing data: ', __FILE__) . $e->getMessage());
			}
			break;

		case 'createCommands':
			$eqLogicId = init('eqLogic_id');

			if (!is_numeric($eqLogicId)) {
				ajax::error(__('Invalid eqLogic ID', __FILE__));
			}

			$eqLogic = eqLogic::byId($eqLogicId);

			if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
				ajax::error(__('Equipment not found or not linked to HP Printer plugin', __FILE__));
			}

			if (!security::hasRight('dashboard', 'w', $eqLogic->getHumanName())) {
				ajax::error(__('You are not authorized to perform this action', __FILE__));
			}

			try {
				$eqLogic->postSave();
				ajax::success(__('Commandes créées avec succès.', __FILE__));
			} catch (Exception $e) {
				ajax::error(__('Erreur lors de la création des commandes: ', __FILE__) . $e->getMessage());
			}
			break;

		case 'testConnection':
			$ipAddress = init('ipAddress');
			if (empty($ipAddress)) {
				ajax::error(__('L\'adresse IP est requise pour le test de connexion.', __FILE__));
			}

			$protocol = init('protocol', 'http');
			$url = $protocol . '://' . $ipAddress . '/DevMgmt/ProductConfigDyn.xml';

			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 5,
				CURLOPT_CONNECTTIMEOUT => 3,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_USERAGENT => 'Jeedom HP Printer Plugin Test',
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlErrno = curl_errno($ch);
			$curlError = curl_error($ch);
			curl_close($ch);

			if ($curlErrno !== 0 || $httpCode !== 200) {
				$errorDetails = "Code HTTP: $httpCode, Erreur cURL: ($curlErrno) $curlError";
				ajax::error(__('La connexion à l\'imprimante a échoué. Vérifiez l\'adresse IP et que l\'imprimante est en ligne. Détails: ', __FILE__) . $errorDetails);
			}

			// Try to parse XML to ensure it\'s a valid EWS response
			$xml = simplexml_load_string($response);
			if ($xml === false) {
				ajax::error(__('La connexion à l\'imprimante a réussi, mais la réponse n\'est pas un XML valide. Détails: ', __FILE__) . substr($response, 0, 200));
			}

			ajax::success(__('La connexion à l\'imprimante est réussie.', __FILE__));
			break;

		default:
			ajax::error('Unknown action: ' . $action);
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

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

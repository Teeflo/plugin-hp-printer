<?php

try {
	require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
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
			$ipAddress = init('ip_address');
			$protocol = init('protocol');
			$verifySsl = init('verifySsl') === 'true';

			if (empty($ipAddress)) {
				ajax::success(array('state' => 'error', 'message' => __('L'adresse IP est requise pour le test de connexion.', __FILE__)));
			}

			$url = $protocol . "://" . $ipAddress . "/DevMgmt/ProductConfigDyn.xml";
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 5,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_SSL_VERIFYPEER => !$verifySsl,
				CURLOPT_SSL_VERIFYHOST => !$verifySsl ? 0 : 2,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_USERAGENT => 'Jeedom HP Printer Test'
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlErrno = curl_errno($ch);
			$curlError = curl_error($ch);
			curl_close($ch);

			if ($curlErrno || $httpCode !== 200) {
				ajax::success(array('state' => 'error', 'message' => __('La connexion à l'imprimante a échoué. Vérifiez l'adresse IP et que l'imprimante est en ligne. Détails: ', __FILE__) . ($curlErrno ? $curlError : "HTTP Code: " . $httpCode)));
			} else {
				ajax::success(array('state' => 'ok', 'message' => __('La connexion à l'imprimante est réussie.', __FILE__)));
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
	if (version_compare(jeedom::version(), '4.4', '>=')) {
		ajax::error(displayException($e), $e->getCode());
	} else {
		ajax::error(displayExeption($e), $e->getCode());
	}
}
?>
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
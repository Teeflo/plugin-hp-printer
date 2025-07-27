<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'hp_printer', 'class', 'hp_printer');

switch (init('action')) {
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
        if (empty($ipAddress)) {
            ajax::success(array('state' => 'error', 'result' => __('IP address cannot be empty', __FILE__)));
        }

        // Validate IP address format
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            ajax::success(array('state' => 'error', 'result' => __('Invalid IP address format', __FILE__)));
        }

        try {
            $protocol = init('protocol', 'http');
            $hpPrinterApi = new hp_printer_connector($ipAddress, $protocol);
            $testData = $hpPrinterApi->getProductConfigInfo(); // Attempt to fetch some data

            if (!empty($testData)) {
                ajax::success(array('state' => 'ok', 'result' => __('Connection successful!', __FILE__)));
            } else {
                ajax::success(array('state' => 'error', 'result' => __('Connection failed or no data retrieved. Check IP address and printer status.', __FILE__)));
            }
        } catch (Exception $e) {
            ajax::success(array('state' => 'error', 'result' => __('Error during connection test: ', __FILE__) . $e->getMessage()));
        }
        break;

    default:
        ajax::success(array('state' => 'error', 'message' => __('Unknown action', __FILE__)));
        break;
}
?>
<?php
require_once __DIR__ . '/../../core/php/core.inc.php';


/**
 * Class hp_printer_eqLogic
 *
 * This class represents an HP printer equipment in Jeedom.
 */
class hp_printer extends eqLogic {

    /**
     * Pulls all data from the printer and updates Jeedom commands.
     * This method is intended to be called by a Jeedom cron job.
     *
     * @param int $eqLogicId The ID of the Jeedom equipment logic.
     */
    public function cronPullData() {
        log::add('hp_printer', 'info', 'Starting data pull for eqLogic ID: ' . $this->getId());

        $ipAddress = $this->getConfiguration('ipAddress');
        $protocol = $this->getConfiguration('protocol', 'http');

        if (empty($ipAddress)) {
            log::error('hp_printer: IP address not configured for eqLogic ID: ' . $this->getId());
            return;
        }

        $hpPrinterApi = new hp_printer_connector($ipAddress, $protocol);

        $allData = [];

        try {
            $allData = array_merge($allData, $hpPrinterApi->getConsumableInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching consumable info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getNetworkAppInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching network app info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getPrintConfigInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching print config info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getProductConfigInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching product config info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getProductStatusInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching product status info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getProductUsageInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching product usage info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getIoConfigInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching I/O config info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        try {
            $allData = array_merge($allData, $hpPrinterApi->getEPrintConfigInfo());
        } catch (Exception $e) {
            log::error('hp_printer: Error fetching ePrint config info for eqLogic ID: ' . $this->getId() . ' - ' . $e->getMessage());
        }

        foreach ($allData as $key => $value) {
            $cmd = $this->getCmd(null, $key);
            if (is_object($cmd)) {
                $cmd->setValue($value)->save();
                log::debug('hp_printer: Updated command ' . $key . ' with value ' . $value);
            } else {
                log::debug('hp_printer: Command not found for key: ' . $key);
            }
        }
        log::add('hp_printer', 'info', 'Finished data pull for eqLogic ID: ' . $this->getId());
    }
}
?>
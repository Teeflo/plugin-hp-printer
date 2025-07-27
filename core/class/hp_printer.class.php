<?php

/* ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../php/hp_printer.inc.php';



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

        $hpPrinterApi = new hp_printer_connector($ipAddress, $protocol, $this->getConfiguration());

        $allData = [];
        $maxRetries = 3;
        $retryDelay = 10; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $allData = array_merge($allData, $hpPrinterApi->getConsumableInfo());
                $allData = array_merge($allData, $hpPrinterApi->getNetworkAppInfo());
                $allData = array_merge($allData, $hpPrinterApi->getPrintConfigInfo());
                $allData = array_merge($allData, $hpPrinterApi->getProductConfigInfo());
                $allData = array_merge($allData, $hpPrinterApi->getProductStatusInfo());
                $allData = array_merge($allData, $hpPrinterApi->getProductUsageInfo());
                $allData = array_merge($allData, $hpPrinterApi->getIoConfigInfo());
                $allData = array_merge($allData, $hpPrinterApi->getEPrintConfigInfo());

                // If we get here, all data was fetched successfully, so we can break the loop
                break;

            } catch (Exception $e) {
                log::add('hp_printer', 'warning', "Attempt {$attempt}/{$maxRetries} failed for eqLogic ID " . $this->getId() . ": " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                } else {
                    log::add('hp_printer', 'error', "All {$maxRetries} attempts failed for eqLogic ID " . $this->getId() . ". Last error: " . $e->getMessage());
                    // Optionally, set equipment to an error state here
                    // $this->setStatus('health', '0');
                    return; // Stop execution for this cron cycle
                }
            }
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
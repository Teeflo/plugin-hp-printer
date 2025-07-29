<?php

/* ***************************Includes********************************* */
if (!class_exists('eqLogic')) {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
}

/**
 * Class hp_printer
 *
 * This class represents an HP printer equipment in Jeedom.
 * It handles communication with HP printers via their embedded web server (EWS)
 * and manages automatic data collection via XML endpoints.
 */
class hp_printer extends eqLogic {

    /**
     * Called after saving the equipment - creates all necessary commands automatically
     */
    public function postSave() {
        log::add('hp_printer', 'info', 'Creating HP printer commands for equipment: ' . $this->getName());

        $commandsJsonPath = dirname(__FILE__) . '/../../plugin_info/commands.json';
        if (!file_exists($commandsJsonPath)) {
            log::add('hp_printer', 'error', 'commands.json not found at: ' . $commandsJsonPath);
            return;
        }

        $commandsData = json_decode(file_get_contents($commandsJsonPath), true);
        if ($commandsData === null) {
            log::add('hp_printer', 'error', 'Failed to decode commands.json');
            return;
        }

        foreach ($commandsData as $commandDef) {
            $this->createCommand(
                $commandDef['id'],
                $commandDef['name'],
                $commandDef['type'],
                $commandDef['subType'],
                $commandDef['unit'] ?? '',
                $commandDef['visible'] ?? true,
                $commandDef['historized'] ?? false
            );
        }

        log::add('hp_printer', 'info', 'HP printer commands created successfully from commands.json');
    }

    /**
     * Helper function to create a command
     */
    private function createCommand($logicalId, $name, $type, $subType, $unit = '', $visible = true, $historized = false) {
        $cmd = $this->getCmd(null, $logicalId);
        if (!is_object($cmd)) {
            $cmd = new hp_printerCmd();
            $cmd->setLogicalId($logicalId);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setName($name);
        }
        
        $cmd->setType($type);
        $cmd->setSubType($subType);
        $cmd->setUnite($unit);
        $cmd->setIsVisible($visible ? 1 : 0);
        $cmd->setIsHistorized($historized ? 1 : 0);
        
        // Désactiver le bouton test pour les commandes info car elles ne sont pas exécutables
        if ($type === 'info') {
            $cmd->setDisplay('showNameOn', 1);
            $cmd->setDisplay('showStatOn', 1);
            // Cache le bouton de test pour les commandes info
            $cmd->setConfiguration('actionCheckCmd', '');
        }
        
        $cmd->save();
    }

    /**
     * Pulls all data from the printer and updates Jeedom commands.
     * This method is intended to be called by a Jeedom cron job.
     */
    public function cronPullData() {
        log::add('hp_printer', 'info', 'Starting data pull for eqLogic ID: ' . $this->getId());

        $ipAddress = $this->getConfiguration('ipAddress');
        $protocol = $this->getConfiguration('protocol', 'http');

        log::add('hp_printer', 'info', 'Configuration - IP: ' . $ipAddress . ', Protocol: ' . $protocol);

        if (empty($ipAddress)) {
            log::add('hp_printer', 'error', 'IP address not configured for eqLogic ID: ' . $this->getId());
            log::add('hp_printer', 'error', 'Please configure the IP address in the equipment settings');
            return;
        }

        try {
            $commandsJsonPath = dirname(__FILE__) . '/../../plugin_info/commands.json';
            $commandsData = json_decode(file_get_contents($commandsJsonPath), true);

            $endpoints = [];
            foreach ($commandsData as $commandDef) {
                if (isset($commandDef['xml_source'])) {
                    $endpoints[$commandDef['xml_source']][] = $commandDef;
                }
            }

            foreach ($endpoints as $xmlSource => $cmds) {
                $url = $protocol . "://" . $ipAddress . $xmlSource;
                $xmlData = $this->fetchXMLData($url);

                if ($xmlData !== false) {
                    foreach ($cmds as $cmdDef) {
                        if (isset($cmdDef['is_consumable']) && $cmdDef['is_consumable'] === true) {
                            $this->updateConsumableCommand($xmlData, $cmdDef);
                        } else {
                            $value = $this->extractXMLValue($xmlData, $cmdDef['xpath']);
                            $this->updateCommandValue($cmdDef['id'], $value);
                        }
                    }
                }
            }

            log::add('hp_printer', 'info', 'Data pull completed successfully for eqLogic ID: ' . $this->getId());
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Failed to pull data for eqLogic ID ' . $this->getId() . ': ' . $e->getMessage());
        }
    }

    private function updateConsumableCommand($xmlData, $cmdDef) {
        $consumables = $xmlData->xpath('//*[local-name()="ConsumableInfo"]');
        foreach ($consumables as $consumable) {
            $labelCodeNodes = $consumable->xpath('.//*[local-name()="ConsumableLabelCode"]');
            $labelCode = !empty($labelCodeNodes) ? (string)$labelCodeNodes[0] : '';

            if ($labelCode === $cmdDef['consumable_type']) {
                $valueNodes = $consumable->xpath('.//*[local-name()="' . $cmdDef['xpath'] . '"]');
                $value = !empty($valueNodes) ? (string)$valueNodes[0] : '';
                $this->updateCommandValue($cmdDef['id'], $value);
                break;
            }
        }
    }

    /**
     * Fetches XML data from URL
     */
    private function fetchXMLData($url) {
        log::add('hp_printer', 'debug', 'Fetching XML from: ' . $url);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'Jeedom HP Printer Plugin'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        log::add('hp_printer', 'debug', 'HTTP Response: ' . $httpCode . ', Size: ' . strlen($response) . ' bytes');
        
        if ($curlErrno) {
            log::add('hp_printer', 'error', "cURL Error for $url: ($curlErrno) $curlError");
            return false;
        }
        
        if ($httpCode !== 200) {
            log::add('hp_printer', 'error', "HTTP Error for $url: Code $httpCode");
            return false;
        }
        
        if (empty($response)) {
            log::add('hp_printer', 'error', "Empty response from $url");
            return false;
        }
        
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            log::add('hp_printer', 'error', "Invalid XML response from $url");
            log::add('hp_printer', 'debug', "Response start: " . substr($response, 0, 200));
            return false;
        }
        
        log::add('hp_printer', 'debug', 'XML parsed successfully from: ' . $url);
        return $xml;
    }

    /**
     * Extracts XML value by element name, handling namespaces properly
     */
    private function extractXMLValue($xml, $elementName) {
        log::add('hp_printer', 'debug', "Extracting value for element: $elementName");
        
        // Method 1: Try with local-name() for namespaced elements
        $xpath = "//*[local-name()='$elementName']";
        $nodes = $xml->xpath($xpath);
        if (!empty($nodes)) {
            $value = (string)$nodes[0];
            log::add('hp_printer', 'debug', "Found '$elementName' with local-name(): '$value'");
            return $value;
        }
        
        // Method 2: Try simple xpath without namespace
        $xpath = "//$elementName";
        $nodes = $xml->xpath($xpath);
        if (!empty($nodes)) {
            $value = (string)$nodes[0];
            log::add('hp_printer', 'debug', "Found '$elementName' with simple xpath: '$value'");
            return $value;
        }
        
        // Method 3: Try with common namespace prefixes
        $prefixes = ['dd:', 'prdcfgdyn:', 'prdcfgdyn2:', 'ccdyn:', 'pscat:', 'pudyn:', 'io:', 'ep:'];
        foreach ($prefixes as $prefix) {
            $xpath = "//*[name()='{$prefix}{$elementName}']";
            $nodes = $xml->xpath($xpath);
            if (!empty($nodes)) {
                $value = (string)$nodes[0];
                log::add('hp_printer', 'debug', "Found '$elementName' with prefix '$prefix': '$value'");
                return $value;
            }
        }
        
        // Method 4: Case-insensitive search
        $xpath = "//*[translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='" . strtolower($elementName) . "']";
        $nodes = $xml->xpath($xpath);
        if (!empty($nodes)) {
            $value = (string)$nodes[0];
            log::add('hp_printer', 'debug', "Found '$elementName' with case-insensitive search: '$value'");
            return $value;
        }
        
        log::add('hp_printer', 'warning', "Element '$elementName' not found in XML");
        return '';
    }

    /**
     * Updates a command value
     */
    private function updateCommandValue($logicalId, $value) {
        log::add('hp_printer', 'debug', "Attempting to update command '$logicalId' with value: '$value'");
        
        if ($value !== '') {
            $cmd = $this->getCmd(null, $logicalId);
            if (is_object($cmd)) {
                $cmd->event($value);
                log::add('hp_printer', 'info', "Successfully updated command '$logicalId' with value: '$value'");
            } else {
                log::add('hp_printer', 'error', "Command '$logicalId' not found - cannot update value");
            }
        }
    }
}

class hp_printerCmd extends cmd {
    public static $_widgetPossibility = array('custom' => true);
    private static $isExecuting = false;

    public function execute($_options = array()) {
        // Protection: ne pas exécuter les commandes de type 'info'
        if ($this->getType() === 'info') {
            log::add('hp_printer', 'warning', 'Tentative d\'exécution d\'une commande info: ' . $this->getName() . ' (ID: ' . $this->getLogicalId() . ')');
            throw new Exception(__('Les commandes de type information ne peuvent pas être exécutées.', __FILE__));
        }
        
        if (self::$isExecuting) {
            return;
        }

        self::$isExecuting = true;
        $eqLogic = $this->getEqLogic();
        
        try {
            switch ($this->getLogicalId()) {
                case 'refresh':
                    $eqLogic->cronPullData();
                    break;

                case 'testConnection':                    // Test the connection and return a success or error message                    $ipAddress = $eqLogic->getConfiguration('ipAddress');                    if (empty($ipAddress)) {                        throw new Exception(__('L'adresse IP n'est pas configurée pour le test de connexion.', __FILE__));                    }                    $protocol = $eqLogic->getConfiguration('protocol', 'http');                    $url = $protocol . '://' . $ipAddress;                    $ch = curl_init();                    curl_setopt_array($ch, [                        CURLOPT_URL => $url,                        CURLOPT_RETURNTRANSFER => true,                        CURLOPT_TIMEOUT => 5,                        CURLOPT_CONNECTTIMEOUT => 3,                        CURLOPT_SSL_VERIFYPEER => false,                        CURLOPT_SSL_VERIFYHOST => false,                        CURLOPT_NOBODY => true                    ]);                    curl_exec($ch);                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);                    $curlErrno = curl_errno($ch);                    curl_close($ch);                    if ($curlErrno !== 0 || $httpCode >= 400) {                        throw new Exception(__('La connexion à l'imprimante a échoué. Vérifiez l'adresse IP et que l'imprimante est en ligne.', __FILE__));                    } else {                        // La connexion est réussie, pas besoin de renvoyer de message ici                        // Le message de succès est géré côté JS                    }                    break;

                case 'cleanHeads':
                    // Start a cleaning cycle
                    $url = $eqLogic->getConfiguration('protocol', 'http') . "://" . $eqLogic->getConfiguration('ipAddress') . "/DevMgmt/InternalPrintDyn.xml";
                    $xml = '<ipdyn:InternalPrintDyn xmlns:ipdyn="http://www.hp.com/schemas/imaging/con/ledm/internalprintdyn/2008/03/21" xmlns:dd="http://www.hp.com/schemas/imaging/con/dictionaries/1.0/"><ipdyn:Job><ipdyn:JobType>cleaningPage</ipdyn:JobType></ipdyn:Job></ipdyn:InternalPrintDyn>';
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $xml,
                        CURLOPT_HTTPHEADER => ['Content-Type: application/xml'],
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                    break;
                    
                default:
                    // No action for other commands
                    break;
            }
        } finally {
            self::$isExecuting = false;
        }
    }
}
?>
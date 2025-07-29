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

        // Commands based on ProductConfigDyn.xml analysis
        $this->createCommand('makeAndModel', 'Modèle', 'info', 'string', '', true, false);
        $this->createCommand('serialNumber', 'Numéro de série', 'info', 'string', '', true, false);
        $this->createCommand('productNumber', 'Numéro produit', 'info', 'string', '', true, false);
        $this->createCommand('deviceLocation', 'Emplacement', 'info', 'string', '', true, false);
        $this->createCommand('passwordStatus', 'Statut mot de passe', 'info', 'string', '', true, false);
        $this->createCommand('powerSaveTimeout', 'Délai veille', 'info', 'string', '', false, false);
        $this->createCommand('autoOffTime', 'Arrêt automatique', 'info', 'string', '', false, false);

        // Commands based on ConsumableConfigDyn.xml analysis
        $this->createCommand('cartridge_cmy_level', 'Niveau encre couleur (CMY)', 'info', 'numeric', '%', true, true);
        $this->createCommand('cartridge_k_level', 'Niveau encre noire (K)', 'info', 'numeric', '%', true, true);
        $this->createCommand('cartridge_cmy_state', 'État cartouche couleur', 'info', 'string', '', true, false);
        $this->createCommand('cartridge_k_state', 'État cartouche noire', 'info', 'string', '', true, false);
        $this->createCommand('cartridge_cmy_brand', 'Marque cartouche couleur', 'info', 'string', '', false, false);
        $this->createCommand('cartridge_k_brand', 'Marque cartouche noire', 'info', 'string', '', false, false);

        // Commands based on ProductStatusDyn.xml analysis
        $this->createCommand('printerStatus', 'Statut imprimante', 'info', 'string', '', true, false);

        // NEW: Commands based on ProductUsageDyn.xml analysis (usage statistics)
        $this->createCommand('totalImpressions', 'Total impressions', 'info', 'numeric', 'pages', true, true);
        $this->createCommand('colorImpressions', 'Impressions couleur', 'info', 'numeric', 'pages', true, true);
        $this->createCommand('monoImpressions', 'Impressions monochrome', 'info', 'numeric', 'pages', true, true);
        $this->createCommand('simplexSheets', 'Feuilles recto', 'info', 'numeric', 'feuilles', true, true);
        $this->createCommand('duplexSheets', 'Feuilles recto-verso', 'info', 'numeric', 'feuilles', true, true);
        $this->createCommand('jamEvents', 'Événements bourrage', 'info', 'numeric', 'événements', true, true);
        $this->createCommand('mispickEvents', 'Mauvaises prises papier', 'info', 'numeric', 'événements', true, true);
        $this->createCommand('cumulativeInkUsed', 'Encre utilisée (cumul)', 'info', 'numeric', 'ml', true, true);
        $this->createCommand('scanImages', 'Nombre total de scans', 'info', 'numeric', 'scans', true, true);
        $this->createCommand('copyImpressions', 'Nombre total de copies', 'info', 'numeric', 'copies', true, true);
        $this->createCommand('networkImpressions', 'Impressions réseau', 'info', 'numeric', 'pages', true, true);
        $this->createCommand('wirelessImpressions', 'Impressions Wi-Fi', 'info', 'numeric', 'pages', true, true);

        // NEW: Commands based on ConsumableConfigDyn.xml analysis
        $this->createCommand('cartridgeModelCMY', 'Modèle cartouche couleur', 'info', 'string', '', false, false);
        $this->createCommand('cartridgeModelK', 'Modèle cartouche noire', 'info', 'string', '', false, false);

        // NEW: Commands based on IoConfig.xml analysis (network information)
        $this->createCommand('hostname', 'Nom d\'hôte', 'info', 'string', '', true, false);
        $this->createCommand('defaultHostname', 'Nom d\'hôte par défaut', 'info', 'string', '', false, false);

        // NEW: Commands based on ePrintConfigDyn.xml analysis (ePrint services)
        $this->createCommand('eprintEmailStatus', 'ePrint Email', 'info', 'string', '', true, false);
        $this->createCommand('eprintMobileStatus', 'ePrint Mobile', 'info', 'string', '', true, false);
        $this->createCommand('eprintRegistration', 'ePrint Enregistrement', 'info', 'string', '', true, false);
        $this->createCommand('eprintConnection', 'ePrint Connexion', 'info', 'string', '', true, false);

        // Action commands
        $this->createCommand('refresh', 'Rafraîchir', 'action', 'other', '', true, false);
        $this->createCommand('testConnection', 'Test connexion', 'action', 'other', '', true, false);
        $this->createCommand('cleanHeads', 'Nettoyage des têtes', 'action', 'other', '', true, false);

        log::add('hp_printer', 'info', 'HP printer commands created successfully - Total: 28 commands');
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

        // Debug: Log de la configuration
        log::add('hp_printer', 'info', 'Configuration - IP: ' . $ipAddress . ', Protocol: ' . $protocol);

        if (empty($ipAddress)) {
            log::add('hp_printer', 'error', 'IP address not configured for eqLogic ID: ' . $this->getId());
            log::add('hp_printer', 'error', 'Please configure the IP address in the equipment settings');
            return;
        }

        try {
            // Fetch and update data from HP printer XML endpoints
            $this->updateFromXMLEndpoints($ipAddress, $protocol);
            log::add('hp_printer', 'info', 'Data pull completed successfully for eqLogic ID: ' . $this->getId());
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Failed to pull data for eqLogic ID ' . $this->getId() . ': ' . $e->getMessage());
        }
    }

    /**
     * Updates commands from HP printer XML endpoints
     */
    private function updateFromXMLEndpoints($ipAddress, $protocol) {
        // Update product information
        $this->updateProductConfig($ipAddress, $protocol);
        
        // Update consumable levels
        $this->updateConsumableConfig($ipAddress, $protocol);
        
        // Update printer status
        $this->updateProductStatus($ipAddress, $protocol);
        
        // NEW: Update usage statistics
        $this->updateProductUsage($ipAddress, $protocol);
        
        // NEW: Update network configuration
        $this->updateNetworkConfig($ipAddress, $protocol);
        
        // NEW: Update ePrint services
        $this->updateEPrintConfig($ipAddress, $protocol);
    }

    /**
     * Updates product configuration data
     */
    private function updateProductConfig($ipAddress, $protocol) {
        log::add('hp_printer', 'info', 'Starting product config update');
        
        $url = $protocol . "://" . $ipAddress . "/DevMgmt/ProductConfigDyn.xml";
        $xmlData = $this->fetchXMLData($url);
        
        if ($xmlData !== false) {
            log::add('hp_printer', 'info', 'Product config XML data retrieved successfully');
            
            // Extract product information with logging
            $makeModel = $this->extractXMLValue($xmlData, 'MakeAndModel');
            $serialNumber = $this->extractXMLValue($xmlData, 'SerialNumber');
            
            log::add('hp_printer', 'debug', "Extracted MakeAndModel: '$makeModel'");
            log::add('hp_printer', 'debug', "Extracted SerialNumber: '$serialNumber'");
            
            $this->updateCommandValue('makeAndModel', $makeModel);
            $this->updateCommandValue('serialNumber', $serialNumber);
            $this->updateCommandValue('productNumber', $this->extractXMLValue($xmlData, 'ProductNumber'));
            $this->updateCommandValue('deviceLocation', $this->extractXMLValue($xmlData, 'DeviceLocation'));
            $this->updateCommandValue('passwordStatus', $this->extractXMLValue($xmlData, 'PasswordStatus'));
            $this->updateCommandValue('powerSaveTimeout', $this->extractXMLValue($xmlData, 'PowerSaveTimeout'));
            $this->updateCommandValue('autoOffTime', $this->extractXMLValue($xmlData, 'AutoOffTime'));
            
            log::add('hp_printer', 'info', 'Product config update completed');
        } else {
            log::add('hp_printer', 'error', 'Failed to retrieve product config XML data');
        }
    }

    /**
     * Updates consumable configuration data (ink levels)
     */
    private function updateConsumableConfig($ipAddress, $protocol) {
        $url = $protocol . "://" . $ipAddress . "/DevMgmt/ConsumableConfigDyn.xml";
        $xmlData = $this->fetchXMLData($url);
        
        if ($xmlData !== false) {
            // Simplified approach: find all ConsumableInfo blocks using local-name()
            $consumables = $xmlData->xpath('//*[local-name()="ConsumableInfo"]');
            
            foreach ($consumables as $consumable) {
                // Extract data using our improved extractXMLValue function
                $labelCodeNodes = $consumable->xpath('.//*[local-name()="ConsumableLabelCode"]');
                $levelNodes = $consumable->xpath('.//*[local-name()="ConsumablePercentageLevelRemaining"]');
                $stateNodes = $consumable->xpath('.//*[local-name()="ConsumableState"]');
                $brandNodes = $consumable->xpath('.//*[local-name()="Brand"]');
                
                $labelCode = !empty($labelCodeNodes) ? (string)$labelCodeNodes[0] : '';
                $level = !empty($levelNodes) ? (string)$levelNodes[0] : '';
                $state = !empty($stateNodes) ? (string)$stateNodes[0] : '';
                $brand = !empty($brandNodes) ? (string)$brandNodes[0] : '';
                $modelNodes = $consumable->xpath('.//*[local-name()="ConsumableSelectibilityNumber"]');
                $model = !empty($modelNodes) ? (string)$modelNodes[0] : '';
                
                if ($labelCode === 'CMY') {
                    // Color cartridge
                    $this->updateCommandValue('cartridge_cmy_level', $level);
                    $this->updateCommandValue('cartridge_cmy_state', $state);
                    $this->updateCommandValue('cartridge_cmy_brand', $brand);
                    $this->updateCommandValue('cartridgeModelCMY', $model);
                } elseif ($labelCode === 'K') {
                    // Black cartridge
                    $this->updateCommandValue('cartridge_k_level', $level);
                    $this->updateCommandValue('cartridge_k_state', $state);
                    $this->updateCommandValue('cartridge_k_brand', $brand);
                    $this->updateCommandValue('cartridgeModelK', $model);
                }
            }
        }
    }

    /**
     * Updates product status data
     */
    private function updateProductStatus($ipAddress, $protocol) {
        $url = $protocol . "://" . $ipAddress . "/DevMgmt/ProductStatusDyn.xml";
        $xmlData = $this->fetchXMLData($url);
        
        if ($xmlData !== false) {
            $status = $this->extractXMLValue($xmlData, 'StatusCategory');
            $this->updateCommandValue('printerStatus', $status);
        }
    }

    /**
     * Updates product usage statistics from ProductUsageDyn.xml
     */
    private function updateProductUsage($ipAddress, $protocol) {
        $url = $protocol . "://" . $ipAddress . "/DevMgmt/ProductUsageDyn.xml";
        $xmlData = $this->fetchXMLData($url);
        
        if ($xmlData !== false) {
            // Extract usage statistics using simplified xpath
            $this->updateCommandValue('totalImpressions', $this->extractXMLValue($xmlData, 'TotalImpressions'));
            $this->updateCommandValue('colorImpressions', $this->extractXMLValue($xmlData, 'ColorImpressions'));
            $this->updateCommandValue('monoImpressions', $this->extractXMLValue($xmlData, 'MonochromeImpressions'));
            $this->updateCommandValue('simplexSheets', $this->extractXMLValue($xmlData, 'SimplexSheets'));
            $this->updateCommandValue('duplexSheets', $this->extractXMLValue($xmlData, 'DuplexSheets'));
            $this->updateCommandValue('jamEvents', $this->extractXMLValue($xmlData, 'JamEvents'));
            $this->updateCommandValue('mispickEvents', $this->extractXMLValue($xmlData, 'MispickEvents'));
            $this->updateCommandValue('scanImages', $this->extractXMLValue($xmlData, 'ScanImages'));
            $this->updateCommandValue('copyImpressions', $this->extractXMLValue($xmlData, 'CopyTotalImpressions'));
            $this->updateCommandValue('networkImpressions', $this->extractXMLValue($xmlData, 'NetworkImpressions'));
            $this->updateCommandValue('wirelessImpressions', $this->extractXMLValue($xmlData, 'WirelessNetworkImpressions'));
            
            // Extract cumulative ink usage - look for ValueFloat elements
            $inkUsageNodes = $xmlData->xpath('//*[local-name()="ValueFloat"]');
            if (!empty($inkUsageNodes)) {
                $this->updateCommandValue('cumulativeInkUsed', (string)$inkUsageNodes[0]);
            }
        }
    }

    /**
     * Updates network configuration from IoConfig.xml
     */
    private function updateNetworkConfig($ipAddress, $protocol) {
        $url = $protocol . "://" . $ipAddress . "/IoMgmt/IoConfig.xml";
        $xmlData = $this->fetchXMLData($url);
        
        if ($xmlData !== false) {
            // Extract network information
            $this->updateCommandValue('hostname', $this->extractXMLValue($xmlData, 'Hostname'));
            $this->updateCommandValue('defaultHostname', $this->extractXMLValue($xmlData, 'DefaultHostname'));
        }
    }

    /**
     * Updates ePrint services configuration from ePrintConfigDyn.xml
     */
    private function updateEPrintConfig($ipAddress, $protocol) {
        $url = $protocol . "://" . $ipAddress . "/ePrint/ePrintConfigDyn.xml";
        $xmlData = $this->fetchXMLData($url);
        
        if ($xmlData !== false) {
            // Extract ePrint service status
            $this->updateCommandValue('eprintEmailStatus', $this->extractXMLValue($xmlData, 'EmailService'));
            $this->updateCommandValue('eprintMobileStatus', $this->extractXMLValue($xmlData, 'MobileAppsService'));
            $this->updateCommandValue('eprintRegistration', $this->extractXMLValue($xmlData, 'RegistrationState'));
            $this->updateCommandValue('eprintConnection', $this->extractXMLValue($xmlData, 'SignalingConnectionState'));
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
        } else {
            log::add('hp_printer', 'warning', "Empty value for command '$logicalId' - skipping update");
        }
    }
}

class hp_printerCmd extends cmd {
    public static $_widgetPossibility = array('custom' => true);
    private static $isExecuting = false;

    public function execute($_options = array()) {
        if (self::$isExecuting) {
            return;
        }

        self::$isExecuting = true;
        $eqLogic = $this->getEqLogic();
        
        try {
            switch ($this->getLogicalId()) {
                case 'refresh':
                    // Trigger a full data pull for the equipment
                    $eqLogic->cronPullData();
                    break;
                    
                case 'testConnection':
                    // Test connection to the printer
                    $ipAddress = $eqLogic->getConfiguration('ipAddress');
                    $protocol = $eqLogic->getConfiguration('protocol', 'http');
                    
                    if (empty($ipAddress)) {
                        log::add('hp_printer', 'error', 'IP address not configured for connection test');
                        jeedom::message(array('message' => __('L\'adresse IP n\'est pas configurée pour le test de connexion.', __FILE__), 'type' => 'error'));
                        self::$isExecuting = false;
                        return;
                    }
                    
                    $url = $protocol . "://" . $ipAddress . "/DevMgmt/ProductConfigDyn.xml";
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 5,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_USERAGENT => 'Jeedom HP Printer Test'
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErrno = curl_errno($ch);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    if ($curlErrno || $httpCode !== 200) {
                        log::add('hp_printer', 'warning', 'Connection test failed for ' . $ipAddress . ' - HTTP: ' . $httpCode . ' - cURL Error: ' . $curlError);
                        jeedom::message(array('message' => __('La connexion à l\'imprimante a échoué. Vérifiez l\'adresse IP et que l\'imprimante est en ligne.', __FILE__), 'type' => 'error'));
                    } else {
                        log::add('hp_printer', 'info', 'Connection test successful for ' . $ipAddress);
                        jeedom::message(array('message' => __('La connexion à l\'imprimante est réussie.', __FILE__), 'type' => 'success'));
                    }
                    break;

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

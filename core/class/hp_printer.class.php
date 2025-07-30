<?php

/* ***************************Includes********************************* */
// Vérifie si la classe `eqLogic` n'est pas déjà définie.
// Si elle ne l'est pas, cela signifie que le fichier `core.inc.php` de Jeedom n'a pas encore été inclus.
if (!class_exists('eqLogic')) {
    // Inclut le fichier `core.inc.php` qui contient les classes et fonctions de base de Jeedom.
    // Le chemin est relatif au répertoire du plugin.
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
     *
     * Cette méthode est automatiquement appelée par Jeedom après la sauvegarde d'un équipement.
     * Son rôle est de créer ou de mettre à jour toutes les commandes associées à l'équipement HP Printer
     * en se basant sur la définition des commandes dans le fichier `commands.json`.
     */
    public function postSave() {
        // Ajoute une entrée dans les logs de Jeedom pour indiquer le début de la création des commandes.
        log::add('hp_printer', 'info', 'Creating HP printer commands for equipment: ' . $this->getName());

        // Définit le chemin absolu vers le fichier `commands.json`.
        $commandsJsonPath = dirname(__FILE__) . '/../../plugin_info/commands.json';
        // Vérifie si le fichier `commands.json` existe.
        if (!file_exists($commandsJsonPath)) {
            // Si le fichier n'est pas trouvé, enregistre une erreur et arrête l'exécution de la méthode.
            log::add('hp_printer', 'error', 'commands.json not found at: ' . $commandsJsonPath);
            return;
        }

        // Lit le contenu du fichier `commands.json` et le décode en tableau associatif PHP.
        $commandsData = json_decode(file_get_contents($commandsJsonPath), true);
        // Vérifie si le décodage JSON a échoué (par exemple, fichier mal formé).
        if ($commandsData === null) {
            // Si le décodage échoue, enregistre une erreur et arrête l'exécution.
            log::add('hp_printer', 'error', 'Failed to decode commands.json');
            return;
        }

        // Parcourt chaque définition de commande trouvée dans `commands.json`.
        foreach ($commandsData as $commandDef) {
            // Appelle la méthode `createCommand` pour créer ou mettre à jour chaque commande.
            // Les paramètres sont extraits du tableau `$commandDef` avec des valeurs par défaut si non spécifiées.
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

        // Ajoute une entrée dans les logs pour confirmer la création réussie des commandes.
        log::add('hp_printer', 'info', 'HP printer commands created successfully from commands.json');
    }

    /**
     * Helper function to create a command
     *
     * Cette fonction utilitaire crée ou met à jour une commande spécifique pour l'équipement.
     * Elle est appelée par `postSave` pour chaque commande définie dans `commands.json`.
     *
     * @param string $logicalId L'ID logique de la commande (ex: 'makeAndModel').
     * @param string $name Le nom affiché de la commande (ex: 'Modèle').
     * @param string $type Le type de la commande (info ou action).
     * @param string $subType Le sous-type de la commande (string, numeric, binary, other).
     * @param string $unit L'unité de la commande (ex: '%', 'pages').
     * @param bool $visible Indique si la commande doit être visible sur le dashboard.
     * @param bool $historized Indique si la valeur de la commande doit être historisée.
     */
    private function createCommand($logicalId, $name, $type, $subType, $unit = '', $visible = true, $historized = false) {
        // Tente de récupérer une commande existante avec le même ID logique pour cet équipement.
        $cmd = $this->getCmd(null, $logicalId);
        // Si la commande n'existe pas, crée une nouvelle instance de `hp_printerCmd`.
        if (!is_object($cmd)) {
            $cmd = new hp_printerCmd();
            // Définit l'ID logique de la commande.
            $cmd->setLogicalId($logicalId);
            // Associe la commande à l'équipement actuel.
            $cmd->setEqLogic_id($this->getId());
            // Définit le nom de la commande.
            $cmd->setName($name);
        }
        
        // Définit le type de la commande (info ou action).
        $cmd->setType($type);
        // Définit le sous-type de la commande (string, numeric, binary, other).
        $cmd->setSubType($subType);
        // Définit l'unité de la commande.
        $cmd->setUnite($unit);
        // Définit la visibilité de la commande (1 pour visible, 0 pour caché).
        $cmd->setIsVisible($visible ? 1 : 0);
        // Définit si la commande doit être historisée (1 pour oui, 0 pour non).
        $cmd->setIsHistorized($historized ? 1 : 0);
        
        // Désactiver le bouton test pour les commandes info car elles ne sont pas exécutables
        // Les commandes de type 'info' ne doivent pas avoir de bouton de test car elles ne déclenchent pas d'action.
        if ($type === 'info') {
            // Affiche le nom de la commande sur le widget.
            $cmd->setDisplay('showNameOn', 1);
            // Affiche le statut de la commande sur le widget.
            $cmd->setDisplay('showStatOn', 1);
            // Cache le bouton de test pour les commandes info en vidant l'action de vérification.
            $cmd->setConfiguration('actionCheckCmd', '');
        }
        
        // Sauvegarde la commande dans la base de données de Jeedom.
        $cmd->save();
    }

    /**
     * Pulls all data from the printer and updates Jeedom commands.
     * This method is intended to be called by a Jeedom cron job.
     *
     * Cette méthode est le cœur du plugin. Elle est exécutée périodiquement (via cron)
     * pour récupérer les dernières informations de l'imprimante HP et mettre à jour
     * les valeurs des commandes correspondantes dans Jeedom.
     */
    public function cronPullData() {
        // Enregistre le début de l'opération de récupération des données dans les logs.
        log::add('hp_printer', 'info', 'Starting data pull for eqLogic ID: ' . $this->getId());

        // Récupère l'adresse IP et le protocole (HTTP/HTTPS) configurés pour cet équipement.
        $ipAddress = $this->getConfiguration('ipAddress');
        $protocol = $this->getConfiguration('protocol', 'http');

        // Log les informations de configuration utilisées.
        log::add('hp_printer', 'info', 'Configuration - IP: ' . $ipAddress . ', Protocol: ' . $protocol);

        // Vérifie si l'adresse IP est configurée.
        if (empty($ipAddress)) {
            // Si l'adresse IP est manquante, enregistre une erreur et arrête l'exécution.
            log::add('hp_printer', 'error', 'IP address not configured for eqLogic ID: ' . $this->getId());
            log::add('hp_printer', 'error', 'Please configure the IP address in the equipment settings');
            return;
        }

        try {
            // Lit le fichier `commands.json` pour obtenir la liste des commandes et leurs sources XML.
            $commandsJsonPath = dirname(__FILE__) . '/../../plugin_info/commands.json';
            $commandsData = json_decode(file_get_contents($commandsJsonPath), true);

            // Initialise un tableau pour regrouper les commandes par source XML (endpoint).
            $endpoints = [];
            // Parcourt toutes les définitions de commandes.
            foreach ($commandsData as $commandDef) {
                // Si la commande a une source XML définie, l'ajoute au tableau `$endpoints`.
                if (isset($commandDef['xml_source'])) {
                    $endpoints[$commandDef['xml_source']][] = $commandDef;
                }
            }

            // Parcourt chaque endpoint XML unique.
            foreach ($endpoints as $xmlSource => $cmds) {
                // Construit l'URL complète de l'endpoint XML.
                $url = $protocol . "://" . $ipAddress . $xmlSource;
                // Récupère les données XML depuis l'imprimante pour cet endpoint.
                $xmlData = $this->fetchXMLData($url);

                // Si les données XML ont été récupérées avec succès.
                if ($xmlData !== false) {
                    // Parcourt toutes les commandes associées à cet endpoint.
                    foreach ($cmds as $cmdDef) {
                        // Vérifie si la commande est une commande de consommable (encre, etc.).
                        if (isset($cmdDef['is_consumable']) && $cmdDef['is_consumable'] === true) {
                            // Met à jour la commande de consommable en utilisant une logique spécifique.
                            $this->updateConsumableCommand($xmlData, $cmdDef);
                        } else {
                            // Extrait la valeur de la commande à partir des données XML en utilisant le chemin XPath.
                            $value = $this->extractXMLValue($xmlData, $cmdDef['xpath']);
                            // Met à jour la valeur de la commande dans Jeedom.
                            $this->updateCommandValue($cmdDef['id'], $value);
                        }
                    }
                }
            }

            // Enregistre la fin de l'opération de récupération des données dans les logs.
            log::add('hp_printer', 'info', 'Data pull completed successfully for eqLogic ID: ' . $this->getId());
        } catch (Exception $e) {
            // En cas d'erreur pendant la récupération des données, enregistre l'erreur dans les logs.
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
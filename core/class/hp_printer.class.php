<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class hp_printer extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  public static function cron() {
    foreach (eqLogic::byType('hp_printer') as $hp_printer) {
      $cron = $hp_printer->getConfiguration('refresh_cron');
      if (!empty($cron)) {
        $hp_printer->pull();
      }
    }
  }

  public static function cron5() {
    // This cron is not used as we have a configurable cron
  }
  
  public static function deamon_start() {
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      return;
    }
    log::add('hp_printer', 'info', 'Lancement du démon');
    $path = realpath(dirname(__FILE__) . '/../resources/demond/demond.py');
    $cmd = 'python3 ' . $path . ' --loglevel ' . log::getLogLevel('hp_printer') . ' --callback ' . network::getCallbackUrl() . ' --apikey ' . jeedom::getApiKey('hp_printer') . ' --pid ' . jeedom::getTmpFolder('hp_printer') . '/demond.pid';
    $cron = cron::byClassAndFunction('hp_printer', 'deamon_start');
    if (is_object($cron)) {
      $cron->stop();
      $cron->remove();
    }
    $cron = new cron();
    $cron->setClass('hp_printer');
    $cron->setFunction('deamon_start');
    $cron->setEnable(1);
    $cron->setDeamon(1);
    $cron->setSchedule('* * * * *');
    $cron->setTimeout('15');
    $cron->setCmd($cmd);
    $cron->save();
    $cron->start();
  }

  public static function deamon_stop() {
    $cron = cron::byClassAndFunction('hp_printer', 'deamon_start');
    if (is_object($cron)) {
      $cron->stop();
      $cron->remove();
    }
    log::add('hp_printer', 'info', 'Arrêt du démon');
  }

  public static function deamon_info() {
    $return = array('launchable' => 'nok', 'state' => 'nok', 'log' => 'nok', 'auto' => 0);
    $cron = cron::byClassAndFunction('hp_printer', 'deamon_start');
    if (is_object($cron)) {
      $return['launchable'] = 'ok';
      $return['state'] = $cron->getState();
      $return['log'] = $cron->getLog();
      $return['auto'] = $cron->getAuto();
    }
    return $return;
  }
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      // Cette function doit retourner des infos complémentataires sous la forme d'un
      // string contenant les infos formatées en HTML.
      return "les infos essentiel de mon plugin";
   }
   */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  public function postSave() {
    // Ne créer les commandes que si l'équipement a un ID valide et qu'il n'y a pas déjà de commandes
    if ($this->getId() == '') {
        return;
    }

    $commands = [
        'printer_status' => ['name' => __('Statut Imprimante', __FILE__), 'type' => 'info', 'subType' => 'string'],
        'page_count' => ['name' => __('Compteur Pages', __FILE__), 'type' => 'info', 'subType' => 'numeric'],
        'printer_model' => ['name' => __('Modèle Imprimante', __FILE__), 'type' => 'info', 'subType' => 'string'],
        'serial_number' => ['name' => __('Numéro Série', __FILE__), 'type' => 'info', 'subType' => 'string'],
        'mac_address' => ['name' => __('Adresse MAC', __FILE__), 'type' => 'info', 'subType' => 'string'],
        'paper_tray_status' => ['name' => __('Statut Bac Papier', __FILE__), 'type' => 'info', 'subType' => 'string'],
        'error_messages' => ['name' => __('Messages Erreur', __FILE__), 'type' => 'info', 'subType' => 'string'],
        'network_status' => ['name' => __('Statut Réseau', __FILE__), 'type' => 'info', 'subType' => 'string'],
    ];

    foreach ($commands as $logical => $info) {
        $cmd = $this->getCmd(null, $logical);
        if (!is_object($cmd)) {
            $cmd = new hp_printerCmd();
            $cmd->setLogicalId($logical);
            $cmd->setEqLogic_id($this->getId());
        }
        $cmd->setType($info['type']);
        $cmd->setSubType($info['subType']);
        $cmd->setName($info['name']);
        $cmd->setIsVisible(1);
        $cmd->setIsHistorized(1);
        $cmd->save();
    }

    // Handle ink levels dynamically
    $ip_address = $this->getConfiguration('ip_address');
    if (!empty($ip_address)) {
        $urls_to_try = [
            "http://" . $ip_address . "/ProductConfigDyn.xml",
            "http://" . $ip_address . "/ConsumableConfigDyn.xml"
        ];

        $html_result = false;
        foreach ($urls_to_try as $url) {
            $result = $this->fetchHtml($url);
            if ($result['html'] !== false) {
                $html_result = $result;
                break;
            }
        }

        if ($html_result !== false) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html_result['html']);
            libxml_clear_errors();
            $xpath = new DOMXPath($dom);

            $inkNodes = $xpath->query("//table[@id='ink_levels_table']//tr | //div[contains(@class, 'ink-cartridge')] | //*[contains(@class, 'ink-color')]");
            foreach ($inkNodes as $node) {
                $color = '';
                $level = '';

                if ($node->nodeName === 'tr') {
                    $tds = $node->getElementsByTagName('td');
                    if ($tds->length >= 3) {
                        $color = trim($tds->item(0)->nodeValue);
                        if (preg_match('/(\d+)%/', $tds->item(2)->nodeValue, $matches)) {
                            $level = $matches[1];
                        }
                    }
                } else if ($node->nodeName === 'div' && $node->hasAttribute('data-color') && $node->hasAttribute('data-level')) {
                    $color = $node->getAttribute('data-color');
                    $level = $node->getAttribute('data-level');
                } else if (strpos($node->getAttribute('class'), 'ink-color') !== false) {
                    $color = trim($node->nodeValue);
                    $percentageNode = $xpath->query("following-sibling::*[contains(@class, 'ink-percentage')]", $node);
                    if ($percentageNode->length > 0 && preg_match('/(\d+)%/', $percentageNode->item(0)->nodeValue, $matches)) {
                        $level = $matches[1];
                    }
                }

                if (!empty($color) && !empty($level)) {
                    $logicalIdColor = strtolower(str_replace([' ', '-', '_'], '', $color));
                    $cmdName = 'ink_level_' . $logicalIdColor;
                    $humanName = 'Niveau Encre ' . ucfirst($color);

                    $cmd = $this->getCmd(null, $cmdName);
                    if (!is_object($cmd)) {
                        $cmd = parent::addCmd('info', $cmdName, $humanName, 'numeric', '%');
                        $cmd->save();
                    }
                }
            }
        }
    }

    $this->pull();
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  private function fetchHtml($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds timeout for connection
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);      // 10 seconds timeout for the entire request
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        log::add('hp_printer', 'debug', 'Successfully fetched HTML from ' . $url . '. HTTP status: ' . $http_code . '. HTML length: ' . strlen($html));
        return ['html' => $html, 'http_code' => $http_code, 'curl_error' => $curl_error];
    } else {
        log::add('hp_printer', 'error', 'Failed to fetch HTML from ' . $url . '. HTTP status: ' . $http_code . '. cURL error: ' . $curl_error);
        return ['html' => false, 'http_code' => $http_code, 'curl_error' => $curl_error];
    }
  }
  
  public function pull() {
    log::add('hp_printer', 'debug', 'Starting pull() for equipment: ' . $this->getName());
    $ip_address = $this->getConfiguration('ip_address');
    if (empty($ip_address)) {
      log::add('hp_printer', 'error', 'Adresse IP de l\'imprimante non configurée pour l\'équipement ' . $this->getName());
      return;
    }

    $urls_to_try = [
        "http://" . $ip_address . "/",
        "http://" . $ip_address . "/ProductConfigDyn.xml",
        "http://" . $ip_address . "/ConsumableConfigDyn.xml"
    ];

    $html_result = false;
    $successful_url = '';
    foreach ($urls_to_try as $url) {
        log::add('hp_printer', 'debug', 'Attempting to fetch data from URL: ' . $url);
        $result = $this->fetchHtml($url);
        if ($result['html'] !== false) {
            $html_result = $result;
            $successful_url = $url;
            break;
        }
    }

    if ($html_result === false) {
        log::add('hp_printer', 'error', 'Failed to fetch HTML from all attempted URLs for ' . $this->getName() . '. Last attempt: HTTP status ' . $result['http_code'] . ', cURL error: ' . $result['curl_error']);
        return;
    }
    
    $html = $html_result['html'];
    log::add('hp_printer', 'debug', 'Successfully fetched HTML from ' . $successful_url . '. HTTP status: ' . $html_result['http_code'] . '. HTML length: ' . strlen($html));

    // --- Extracting Printer Status from ProductStatusDyn.xml ---
      $productStatusDynUrl = "http://" . $ip_address . "/DevMgmt/ProductStatusDyn.xml";
      $productStatusDynHtml = $this->fetchHtml($productStatusDynUrl)['html'];
      if ($productStatusDynHtml !== false && preg_match('/(inPowerSave|ready|offline)/', $productStatusDynHtml, $matches)) {
        $printerStatus = trim($matches[1]);
        $cmd = $this->getCmd(null, 'printer_status');
        if (!is_object($cmd)) {
            $cmd = parent::addCmd('info', 'printer_status', 'Statut Imprimante', 'string');
            $cmd->save();
            log::add('hp_printer', 'debug', 'Created new command: printer_status');
        }
        $cmd->execCmd($printerStatus);
        log::add('hp_printer', 'debug', 'Printer Status: ' . $printerStatus);
      } else {
        log::add('hp_printer', 'warning', 'Could not find printer status for ' . $this->getName() . '. Please check ProductStatusDyn.xml content.');
      }

      // --- Extracting Page Count from ProductUsageDyn.xml ---
      $productUsageDynUrl = "http://" . $ip_address . "/DevMgmt/ProductUsageDyn.xml";
      $productUsageDynHtml = $this->fetchHtml($productUsageDynUrl)['html'];
      if ($productUsageDynHtml !== false && preg_match('/SVN-IPG-LEDM\.119 \d{4}-\d{2}-\d{2} (\d+)/', $productUsageDynHtml, $matches)) {
        $pageCount = (int)trim($matches[1]);
        $cmd = $this->getCmd(null, 'page_count');
        if (!is_object($cmd)) {
            $cmd = parent::addCmd('info', 'page_count', 'Compteur Pages', 'numeric');
            $cmd->save();
            log::add('hp_printer', 'debug', 'Created new command: page_count');
        }
        $cmd->execCmd($pageCount);
        log::add('hp_printer', 'debug', 'Page Count: ' . $pageCount);
      } else {
        log::add('hp_printer', 'warning', 'Could not find page count for ' . $this->getName() . '. Please check ProductUsageDyn.xml content.');
      }

      // --- Extracting Printer Model from NetAppsDyn.xml ---
      $netAppsDynUrl = "http://" . $ip_address . "/DevMgmt/NetAppsDyn.xml";
      $netAppsDynHtml = $this->fetchHtml($netAppsDynUrl)['html'];
      if ($netAppsDynHtml !== false && preg_match('/HP ENVY (\d+) series/', $netAppsDynHtml, $matches)) {
        $printerModel = 'HP ENVY ' . trim($matches[1]) . ' series';
        $cmd = $this->getCmd(null, 'printer_model');
        if (!is_object($cmd)) {
            $cmd = parent::addCmd('info', 'printer_model', 'Modèle Imprimante', 'string');
            $cmd->save();
            log::add('hp_printer', 'debug', 'Created new command: printer_model');
        }
        $cmd->execCmd($printerModel);
        log::add('hp_printer', 'debug', 'Printer Model: ' . $printerModel);
      } else {
        log::add('hp_printer', 'warning', 'Could not find printer model for ' . $this->getName() . '. Please check NetAppsDyn.xml content.');
      }

      // --- Extracting Serial Number (not found in provided samples, leaving as is for now) ---
      // $serialNumber = ''; // No clear pattern in provided text
      // log::add('hp_printer', 'warning', 'Could not find serial number for ' . $this->getName() . '. No clear pattern in provided text.');

      // --- Extracting MAC Address from NetAppsDyn.xml ---
      if ($netAppsDynHtml !== false && preg_match('/HP([0-9A-F]{12})\.local\./', $netAppsDynHtml, $matches)) {
        $macAddress = trim($matches[1]);
        $cmd = $this->getCmd(null, 'mac_address');
        if (!is_object($cmd)) {
            $cmd = parent::addCmd('info', 'mac_address', 'Adresse MAC', 'string');
            $cmd->save();
            log::add('hp_printer', 'debug', 'Created new command: mac_address');
        }
        $cmd->execCmd($macAddress);
        log::add('hp_printer', 'debug', 'MAC Address: ' . $macAddress);
      } else {
        log::add('hp_printer', 'warning', 'Could not find MAC address for ' . $this->getName() . '. Please check NetAppsDyn.xml content.');
      }

      // --- Extracting Paper Tray Status (not found in provided samples, leaving as is for now) ---
      // $paperTrayStatus = ''; // No clear pattern in provided text
      // log::add('hp_printer', 'warning', 'Could not find paper tray status for ' . $this->getName() . '. No clear pattern in provided text.');

      // --- Extracting Error Messages (not found in provided samples, leaving as is for now) ---
      // $errorMessages = []; // No clear pattern in provided text
      // log::add('hp_printer', 'warning', 'Could not find error messages for ' . $this->getName() . '. No clear pattern in provided text.');

      // --- Extracting Network Status from NetAppsDyn.xml ---
      if ($netAppsDynHtml !== false && preg_match('/IPP (enabled|disabled)/', $netAppsDynHtml, $matches)) {
        $networkStatus = trim($matches[1]);
        $cmd = $this->getCmd(null, 'network_status');
        if (!is_object($cmd)) {
            $cmd = parent::addCmd('info', 'network_status', 'Statut Réseau', 'string');
            $cmd->save();
            log::add('hp_printer', 'debug', 'Created new command: network_status');
        }
        $cmd->execCmd($networkStatus);
        log::add('hp_printer', 'debug', 'Network Status: ' . $networkStatus);
      } else {
        log::add('hp_printer', 'warning', 'Could not find network status for ' . $this->getName() . '. Please check NetAppsDyn.xml content.');
      }

      // --- Extracting Ink Levels from ConsumableConfigDyn.xml ---
      $consumableConfigDynUrl = "http://" . $ip_address . "/DevMgmt/ConsumableConfigDyn.xml";
      $consumableConfigDynHtml = $this->fetchHtml($consumableConfigDynUrl)['html'];
      if ($consumableConfigDynHtml !== false) {
        // Example: "80 userReplaceable 304XL 0 inkCartridge HP 2023-11-01 4 CMYTriDots rotateZero 255 255 255 255 255 255 255 255 255 00000000000000866d75af16b89a1179 0 tenthsOfMilliliters generic class2 notSupported markingAgent TIJ2 00000000000000000000000000000000000000000000000000000000000000000883200D0000366AAFF385F495A591230014810F20BA36002081F40260244FA9 large K newGenuineHP ok HP acknowledge false false false false Eureka everyday3 50 userReplaceable 304XL 1 inkCartridge HP 2024-10-01 4 SmallCircle rotateZero 0 0 0 0 0 0 255 255 255 000000000000003557f9c3f495a59123"
        if (preg_match_all('/(\d+) userReplaceable .*? (CMY|K)TriDots/', $consumableConfigDynHtml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $level = $match[1];
                $color = ($match[2] == 'CMY') ? 'color' : 'black'; // Assuming CMY is color, K is black

                $logicalIdColor = strtolower(str_replace([' ', '-', '_'], '', $color));
                $cmdName = 'ink_level_' . $logicalIdColor;
                $humanName = 'Niveau Encre ' . ucfirst($color);

                $cmd = $this->getCmd(null, $cmdName);
                if (!is_object($cmd)) {
                    $cmd = parent::addCmd('info', $cmdName, $humanName, 'numeric', '%');
                    $cmd->save(); // Save the new command
                    log::add('hp_printer', 'debug', 'Created new command: ' . $cmdName);
                }
                $cmd->execCmd((int)$level);
                log::add('hp_printer', 'debug', 'Ink Level - ' . $humanName . ': ' . $level . '%');
            }
        } else {
            log::add('hp_printer', 'warning', 'Could not find ink levels for ' . $this->getName() . '. Please check ConsumableConfigDyn.xml content.');
        }
      } else {
        log::add('hp_printer', 'warning', 'Could not fetch ConsumableConfigDyn.xml for ' . $this->getName());
      }

      log::add('hp_printer', 'info', 'Data pulled successfully for ' . $this->getName());

    
      
  }

  /*     * **********************Getteur Setteur*************************** */
}

class hp_printerCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */
}

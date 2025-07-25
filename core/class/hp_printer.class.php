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
      if (!empty($cron) && cron::isCronValid($cron)) {
        if (cron::checkCron($cron)) {
          $hp_printer->pull();
        }
      }
    }
  }

  public static function cron5() {
    // This cron is not used as we have a configurable cron
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

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
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

  public function pull() {
    $ip_address = $this->getConfiguration('ip_address');
    if (empty($ip_address)) {
      log::add('hp_printer', 'error', 'Adresse IP de l\'imprimante non configurée pour l\'équipement ' . $this->getName());
      return;
    }

    // The URL to fetch. This might vary between HP printer models.
    // A common path for supplies information is /hp/device/info_supplies.html or /info_supplies.html
    // You might need to investigate the specific printer's EWS to find the correct URL.
    $url = "http://" . $ip_address . "/hp/device/info_supplies.html";
    log::add('hp_printer', 'debug', 'Fetching data from: ' . $url);

    try {
      $html = file_get_contents($url);
      if ($html === false) {
        throw new Exception('Failed to fetch HTML from ' . $url);
      }

      $dom = new DOMDocument();
      // Suppress warnings for malformed HTML, which is common in embedded web servers
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();
      $xpath = new DOMXPath($dom);

      // --- Extracting Printer Status ---
      // This XPath is a placeholder. You will need to inspect the actual HTML of your printer's EWS
      // to find the correct XPath or CSS selector for the printer status.
      // Common patterns: elements with specific IDs, classes, or text content.
      // Example: <span id="hp-printer-status">Ready</span>
      $nodes = $xpath->query("//span[@id='hp-printer-status'] | //div[contains(text(), 'Status:')]/following-sibling::span");
      if ($nodes->length > 0) {
        $printerStatus = trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'printer_status')->execCmd($printerStatus);
        log::add('hp_printer', 'debug', 'Printer Status: ' . $printerStatus);
      } else {
        log::add('hp_printer', 'warning', 'Could not find printer status for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting Page Count ---
      // Example: <span id="TotalPagesPrinted">12345</span> or <td class="label">Total Pages:</td><td class="value">12345</td>
      $nodes = $xpath->query("//span[@id='TotalPagesPrinted'] | //td[contains(text(), 'Total Pages:')]/following-sibling::td");
      if ($nodes->length > 0) {
        $pageCount = (int)trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'page_count')->execCmd($pageCount);
        log::add('hp_printer', 'debug', 'Page Count: ' . $pageCount);
      } else {
        log::add('hp_printer', 'warning', 'Could not find page count for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting Printer Model ---
      // Example: <span id="ProductName">HP LaserJet Pro MFP M1234</span> or <td class="label">Model Name:</td><td class="value">HP LaserJet Pro MFP M1234</td>
      $nodes = $xpath->query("//span[@id='ProductName'] | //td[contains(text(), 'Model Name:')]/following-sibling::td");
      if ($nodes->length > 0) {
        $printerModel = trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'printer_model')->execCmd($printerModel);
        log::add('hp_printer', 'debug', 'Printer Model: ' . $printerModel);
      } else {
        log::add('hp_printer', 'warning', 'Could not find printer model for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting Serial Number ---
      // Example: <span id="SerialNumber">ABC123DEF456</span> or <td class="label">Serial Number:</td><td class="value">ABC123DEF456</td>
      $nodes = $xpath->query("//span[@id='SerialNumber'] | //td[contains(text(), 'Serial Number:')]/following-sibling::td");
      if ($nodes->length > 0) {
        $serialNumber = trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'serial_number')->execCmd($serialNumber);
        log::add('hp_printer', 'debug', 'Serial Number: ' . $serialNumber);
      } else {
        log::add('hp_printer', 'warning', 'Could not find serial number for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting MAC Address ---
      // Example: <span id="MACAddress">00:11:22:33:44:55</span> or <td class="label">MAC Address:</td><td class="value">00:11:22:33:44:55</td>
      $nodes = $xpath->query("//span[@id='MACAddress'] | //td[contains(text(), 'MAC Address:')]/following-sibling::td");
      if ($nodes->length > 0) {
        $macAddress = trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'mac_address')->execCmd($macAddress);
        log::add('hp_printer', 'debug', 'MAC Address: ' . $macAddress);
      } else {
        log::add('hp_printer', 'warning', 'Could not find MAC address for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting Paper Tray Status ---
      // Example: <span id="PaperTrayStatus">OK</span> or <div class="tray-status">Empty</div>
      $nodes = $xpath->query("//span[@id='PaperTrayStatus'] | //div[contains(@class, 'tray-status')] | //td[contains(text(), 'Paper Tray:')]/following-sibling::td");
      if ($nodes->length > 0) {
        $paperTrayStatus = trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'paper_tray_status')->execCmd($paperTrayStatus);
        log::add('hp_printer', 'debug', 'Paper Tray Status: ' . $paperTrayStatus);
      } else {
        log::add('hp_printer', 'warning', 'Could not find paper tray status for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting Error Messages ---
      // Example: <div class="error-message">Paper Jam</div> or <ul><li>Error: Out of paper</li></ul>
      $nodes = $xpath->query("//div[contains(@class, 'error-message')] | //ul[@class='error-list']/li");
      $errorMessages = [];
      foreach ($nodes as $node) {
        $errorMessages[] = trim($node->nodeValue);
      }
      $this->getCmd(null, 'error_messages')->execCmd(implode(', ', $errorMessages));
      if (!empty($errorMessages)) {
        log::add('hp_printer', 'debug', 'Error Messages: ' . implode(', ', $errorMessages));
      } else {
        log::add('hp_printer', 'debug', 'No error messages found for ' . $this->getName());
      }


      // --- Extracting Network Status ---
      // Example: <span id="NetworkStatus">Connected (Wi-Fi)</span> or <td class="label">Network Status:</td><td class="value">Connected (Ethernet)</td>
      $nodes = $xpath->query("//span[@id='NetworkStatus'] | //td[contains(text(), 'Network Status:')]/following-sibling::td");
      if ($nodes->length > 0) {
        $networkStatus = trim($nodes->item(0)->nodeValue);
        $this->getCmd(null, 'network_status')->execCmd($networkStatus);
        log::add('hp_printer', 'debug', 'Network Status: ' . $networkStatus);
      } else {
        log::add('hp_printer', 'warning', 'Could not find network status for ' . $this->getName() . '. Please check the HTML structure and XPath.');
      }

      // --- Extracting Ink Levels (dynamic creation and update) ---
      // This is highly dependent on the HTML structure. HP EWS pages vary significantly.
      // Common patterns: tables with ink cartridge information, divs with data attributes.
      // Example 1 (table): <tr><td>Black</td><td><div class="ink-level-bar" style="width: 80%;"></div></td><td>80%</td></tr>
      // Example 2 (div with data attributes): <div class="ink-cartridge" data-color="black" data-level="75">
      // Example 3 (more generic, looking for common ink color names and percentages):
      //   <span class="ink-color">Black</span>: <span class="ink-percentage">80%</span>
      $inkNodes = $xpath->query("//table[@id='ink_levels_table']//tr | //div[contains(@class, 'ink-cartridge')] | //*[contains(@class, 'ink-color')]");

      foreach ($inkNodes as $node) {
        $color = '';
        $level = '';

        // Attempt to extract from table rows (Example 1)
        if ($node->nodeName === 'tr') {
          $tds = $node->getElementsByTagName('td');
          if ($tds->length >= 3) {
            $color = trim($tds->item(0)->nodeValue);
            if (preg_match('/(\d+)%/', $tds->item(2)->nodeValue, $matches)) {
              $level = $matches[1];
            }
          }
        }
        // Attempt to extract from div with data attributes (Example 2)
        else if ($node->nodeName === 'div' && $node->hasAttribute('data-color') && $node->hasAttribute('data-level')) {
          $color = $node->getAttribute('data-color');
          $level = $node->getAttribute('data-level');
        }
        // Attempt to extract from generic elements (Example 3)
        else if (strpos($node->getAttribute('class'), 'ink-color') !== false) {
          $color = trim($node->nodeValue);
          $percentageNode = $xpath->query("following-sibling::*[contains(@class, 'ink-percentage')]", $node);
          if ($percentageNode->length > 0 && preg_match('/(\d+)%/', $percentageNode->item(0)->nodeValue, $matches)) {
            $level = $matches[1];
          }
        }

        if (!empty($color) && !empty($level)) {
          // Sanitize color name for logical ID
          $logicalIdColor = strtolower(str_replace([' ', '-', '_'], '', $color));
          $cmdName = 'ink_level_' . $logicalIdColor;
          $humanName = 'Niveau Encre ' . ucfirst($color);

          $cmd = $this->getCmd(null, $cmdName);
          if (!is_object($cmd)) {
            $cmd = $this->addCmd('info', $cmdName, $humanName, 'numeric', '%');
            $cmd->save(); // Save the new command
          }
          $cmd->execCmd((int)$level);
          log::add('hp_printer', 'debug', 'Ink Level - ' . $humanName . ': ' . $level . '%');
        }
      }

      log::add('hp_printer', 'info', 'Data pulled successfully for ' . $this->getName());

    } catch (Exception $e) {
      log::add('hp_printer', 'error', 'Error pulling data for ' . $this->getName() . ': ' . $e->getMessage());
    }
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

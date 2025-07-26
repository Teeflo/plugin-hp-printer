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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();

    if (init('action') == 'test') {
        $ip_address = init('ip_address');
        
        if (empty($ip_address)) {
            throw new Exception(__('Adresse IP de l'imprimante manquante', __FILE__));
        }
        
        $testUrl = "http://" . $ip_address . "/";
        log::add('hp_printer', 'debug', 'Test de connexion vers : ' . $testUrl);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($html === false) {
            log::add('hp_printer', 'error', 'Erreur cURL lors du test : ' . $curl_error);
            throw new Exception(__('Erreur de connexion : ', __FILE__) . $curl_error);
        }
        
        log::add('hp_printer', 'debug', 'Code de réponse du test : ' . $http_code);
        
        if ($http_code >= 200 && $http_code < 300) {
            log::add('hp_printer', 'info', 'Test de connexion réussi');
            ajax::success(__('Connexion réussie', __FILE__));
        } else {
            $errorMsg = __('Code réponse', __FILE__) . ' ' . $http_code . ' : ' . $curl_error;
            log::add('hp_printer', 'error', 'Test de connexion échoué : ' . $errorMsg);
            throw new Exception($errorMsg);
        }
    }

    if (init('action') == 'add') {
        $eqLogic = new hp_printer();
        $eqLogic->setName(__('Nouvelle imprimante HP', __FILE__));
        $eqLogic->setEqType_name('hp_printer');
        $eqLogic->setIsEnable(1);
        $eqLogic->setIsVisible(1);
        $eqLogic->save();
        ajax::success(utils::o2a($eqLogic));
    }
    
    if (init('action') == 'get') {
        $eqLogic = hp_printer::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Équipement introuvable', __FILE__));
        }
        ajax::success(utils::o2a($eqLogic));
    }
    
    if (init('action') == 'save') {
        $eqLogicData = json_decode(init('eqLogic'), true);
        log::add('hp_printer', 'debug', 'Données reçues pour sauvegarde : ' . json_encode($eqLogicData));
        
        if (!is_array($eqLogicData)) {
            throw new Exception(__('Données d'équipement invalides', __FILE__));
        }
        
        if (empty($eqLogicData['id'])) {
            log::add('hp_printer', 'debug', 'Création d'un nouvel équipement');
            $eqLogic = new hp_printer();
        } else {
            log::add('hp_printer', 'debug', 'Modification de l'équipement ID : ' . $eqLogicData['id']);
            $eqLogic = hp_printer::byId($eqLogicData['id']);
            if (!is_object($eqLogic)) {
                throw new Exception(__('Équipement introuvable', __FILE__));
            }
        }
        
        utils::a2o($eqLogic, jeedom::fromHumanReadable($eqLogicData));
        $eqLogic->save();
        
        $savedData = utils::o2a($eqLogic);
        log::add('hp_printer', 'debug', 'Données sauvegardées : ' . json_encode($savedData));
        
        ajax::success($savedData);
    }
    
    if (init('action') == 'remove') {
        $id = init('id');
        log::add('hp_printer', 'debug', 'Received request to remove equipment with ID: ' . $id);
        $eqLogic = hp_printer::byId($id);
        if (!is_object($eqLogic)) {
            log::add('hp_printer', 'error', 'Equipment with ID ' . $id . ' not found for removal.');
            throw new Exception(__('Équipement introuvable', __FILE__));
        }
        $eqLogic->remove();
        log::add('hp_printer', 'info', 'Equipment with ID ' . $id . ' successfully removed.');
        ajax::success();
    }
    
    if (init('action') == 'getAll') {
        $eqLogics = hp_printer::byType('hp_printer');
        $result = [];
        foreach ($eqLogics as $eqLogic) {
            $result[] = utils::o2a($eqLogic);
        }
        ajax::success($result);
    }
    
    if (init('action') == 'getCmd') {
        $eqLogic = hp_printer::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Équipement introuvable', __FILE__));
        }
        $cmds = $eqLogic->getCmd();
        $result = [];
        foreach ($cmds as $cmd) {
            $result[] = utils::o2a($cmd);
        }
        ajax::success($result);
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

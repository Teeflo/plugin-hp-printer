<?php

// Check if the user has access to the plugin
if (!security::api_call_allowed('hp_printer')) {
    throw new Exception(__('Vous n'êtes pas autorisé à accéder à cette page', __FILE__));
}

// Get the equipment logic ID from the URL
$eqLogicId = init('eqLogic_id');

// Load the equipment logic object
$eqLogic = eqLogic::byId($eqLogicId);

// Check if the equipment logic exists and belongs to this plugin
if (!is_object($eqLogic) || $eqLogic->getPluginId() != 'hp_printer') {
    throw new Exception(__('Équipement introuvable ou non lié au plugin HP Printer', __FILE__));
}

// Include the class file
require_once dirname(__FILE__) . '/../../core/class/hp_printer.class.php';

// Get all commands associated with this equipment
$commands = cmd::byEqLogicId($eqLogicId);

// Prepare data for display
$data = [];
foreach ($commands as $cmd) {
    $data[$cmd->getLogicalId()] = $cmd->getValue();
}

// Get the IP address from the equipment configuration
$ipAddress = $eqLogic->getConfiguration('ipAddress');

?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-cogs"></i> {{Configuration}}</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control eqLogicAttr" data-l1key="name" placeholder="{{Nom de l'équipement}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Adresse IP de l'imprimante}}</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="ipAddress" placeholder="{{Ex: 192.168.1.10}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Activer}}</label>
                            <div class="col-sm-6">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Visible}}</label>
                            <div class="col-sm-6">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-list-alt"></i> {{Informations de l'imprimante}}</h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <button class="btn btn-primary" id="bt_refresh_data"><i class="fa fa-sync"></i> {{Rafraîchir les données}}</button>
                </div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{Information}}</th>
                            <th>{{Valeur}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Display data from getProductConfigInfo()
                        echo '<tr><td colspan="2"><strong>{{Informations Générales}}</strong></td></tr>';
                        echo '<tr><td>{{Firmware Revision}}</td><td>' . (isset($data['firmwareRevision']) ? $data['firmwareRevision'] : '') . '</td></tr>';
                        echo '<tr><td>{{Firmware Date}}</td><td>' . (isset($data['firmwareDate']) ? $data['firmwareDate'] : '') . '</td></tr>';
                        echo '<tr><td>{{Make and Model}}</td><td>' . (isset($data['makeAndModel']) ? $data['makeAndModel'] : '') . '</td></tr>';
                        echo '<tr><td>{{Serial Number}}</td><td>' . (isset($data['serialNumber']) ? $data['serialNumber'] : '') . '</td></tr>';
                        echo '<tr><td>{{Product Number}}</td><td>' . (isset($data['productNumber']) ? $data['productNumber'] : '') . '</td></tr>';
                        echo '<tr><td>{{Password Status}}</td><td>' . (isset($data['passwordStatus']) ? $data['passwordStatus'] : '') . '</td></tr>';
                        echo '<tr><td>{{Region Identifier}}</td><td>' . (isset($data['regionIdentifier']) ? $data['regionIdentifier'] : '') . '</td></tr>';
                        echo '<tr><td>{{Power Save Timeout}}</td><td>' . (isset($data['powerSaveTimeout']) ? $data['powerSaveTimeout'] : '') . '</td></tr>';
                        echo '<tr><td>{{Auto Off Time}}</td><td>' . (isset($data['autoOffTime']) ? $data['autoOffTime'] : '') . '</td></tr>';
                        echo '<tr><td>{{Device Language}}</td><td>' . (isset($data['deviceLanguage']) ? $data['deviceLanguage'] : '') . '</td></tr>';
                        echo '<tr><td>{{Preferred Language}}</td><td>' . (isset($data['preferredLanguage']) ? $data['preferredLanguage'] : '') . '</td></tr>';
                        echo '<tr><td>{{Country and Region}}</td><td>' . (isset($data['countryAndRegionName']) ? $data['countryAndRegionName'] : '') . '</td></tr>';
                        echo '<tr><td>{{Printer Timestamp}}</td><td>' . (isset($data['printerTimestamp']) ? $data['printerTimestamp'] : '') . '</td></tr>';
                        echo '<tr><td>{{Time Format}}</td><td>' . (isset($data['timeFormat']) ? $data['timeFormat'] : '') . '</td></tr>';
                        echo '<tr><td>{{Date Format}}</td><td>' . (isset($data['dateFormat']) ? $data['dateFormat'] : '') . '</td></tr>';
                        echo '<tr><td>{{Quiet Print Mode}}</td><td>' . (isset($data['quietPrintMode']) ? $data['quietPrintMode'] : '') . '</td></tr>';
                        echo '<tr><td>{{Duplex Printing}}</td><td>' . (isset($data['duplex']) ? $data['duplex'] : '') . '</td></tr>';
                        echo '<tr><td>{{EWS Status}}</td><td>' . (isset($data['ewsStatus']) ? $data['ewsStatus'] : '') . '</td></tr>';
                        echo '<tr><td>{{Device Location}}</td><td>' . (isset($data['deviceLocation']) ? $data['deviceLocation'] : '') . '</td></tr>';
                        echo '<tr><td>{{Control Panel Access}}</td><td>' . (isset($data['controlPanelAccess']) ? $data['controlPanelAccess'] : '') . '</td></tr>';
                        echo '<tr><td>{{Available Memory (KB)}}</td><td>' . (isset($data['availableMemoryKB']) ? $data['availableMemoryKB'] : '') . '</td></tr>';
                        echo '<tr><td>{{Total Memory (KB)}}</td><td>' . (isset($data['totalMemoryKB']) ? $data['totalMemoryKB'] : '') . '</td></tr>';

                        // Display data from getProductStatusInfo()
                        echo '<tr><td colspan="2"><strong>{{Statut de l'imprimante}}</strong></td></tr>';
                        echo '<tr><td>{{Printer Status}}</td><td>' . (isset($data['statusCategory']) ? $data['statusCategory'] : '') . '</td></tr>';
                        echo '<tr><td>{{Status String ID}}</td><td>' . (isset($data['statusStringId']) ? $data['statusStringId'] : '') . '</td></tr>';
                        echo '<tr><td>{{Alert Table Mod. Number}}</td><td>' . (isset($data['alertTableModificationNumber']) ? $data['alertTableModificationNumber'] : '') . '</td></tr>';

                        // Display data from getProductUsageInfo()
                        echo '<tr><td colspan="2"><strong>{{Statistiques d'utilisation}}</strong></td></tr>';
                        echo '<tr><td>{{Total Impressions}}</td><td>' . (isset($data['totalImpressions']) ? $data['totalImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Monochrome Impressions}}</td><td>' . (isset($data['monochromeImpressions']) ? $data['monochromeImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Color Impressions}}</td><td>' . (isset($data['colorImpressions']) ? $data['colorImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Simplex Sheets}}</td><td>' . (isset($data['simplexSheets']) ? $data['simplexSheets'] : '') . '</td></tr>';
                        echo '<tr><td>{{Duplex Sheets}}</td><td>' . (isset($data['duplexSheets']) ? $data['duplexSheets'] : '') . '</td></tr>';
                        echo '<tr><td>{{Jam Events}}</td><td>' . (isset($data['jamEvents']) ? $data['jamEvents'] : '') . '</td></tr>';
                        echo '<tr><td>{{Mispick Events}}</td><td>' . (isset($data['mispickEvents']) ? $data['mispickEvents'] : '') . '</td></tr>';
                        echo '<tr><td>{{Front Panel Cancel Presses}}</td><td>' . (isset($data['totalFrontPanelCancelPresses']) ? $data['totalFrontPanelCancelPresses'] : '') . '</td></tr>';
                        echo '<tr><td>{{Total Ink/Toner Used}}</td><td>' . (isset($data['cumulativeMarkingAgentUsedTotal']) ? $data['cumulativeMarkingAgentUsedTotal'] : '') . ' ' . (isset($data['cumulativeMarkingAgentUsedTotalUnit']) ? $data['cumulativeMarkingAgentUsedTotalUnit'] : '') . '</td></tr>';
                        echo '<tr><td>{{EWS Access Count}}</td><td>' . (isset($data['ewsAccessCount']) ? $data['ewsAccessCount'] : '') . '</td></tr>';
                        echo '<tr><td>{{Network Impressions}}</td><td>' . (isset($data['networkImpressions']) ? $data['networkImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Wireless Impressions}}</td><td>' . (isset($data['wirelessNetworkImpressions']) ? $data['wirelessNetworkImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Total Scans}}</td><td>' . (isset($data['scannerTotalScanImages']) ? $data['scannerTotalScanImages'] : '') . '</td></tr>';
                        echo '<tr><td>{{Flatbed Scans}}</td><td>' . (isset($data['scannerFlatbedImages']) ? $data['scannerFlatbedImages'] : '') . '</td></tr>';
                        echo '<tr><td>{{Total Copies}}</td><td>' . (isset($data['copyTotalImpressions']) ? $data['copyTotalImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Color Copies}}</td><td>' . (isset($data['copyColorImpressions']) ? $data['copyColorImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Monochrome Copies}}</td><td>' . (isset($data['copyMonochromeImpressions']) ? $data['copyMonochromeImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Scan Flatbed Images}}</td><td>' . (isset($data['scanFlatbedImages']) ? $data['scanFlatbedImages'] : '') . '</td></tr>';
                        echo '<tr><td>{{Total Prints}}</td><td>' . (isset($data['printTotalImpressions']) ? $data['printTotalImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Photo Prints}}</td><td>' . (isset($data['printPhotoImpressions']) ? $data['printPhotoImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cloud Prints}}</td><td>' . (isset($data['printCloudPrintImpressions']) ? $data['printCloudPrintImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Plain Paper Usage}}</td><td>' . (isset($data['usage_plain_impressions']) ? $data['usage_plain_impressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Photo Paper Usage}}</td><td>' . (isset($data['usage_photoStandard_impressions']) ? $data['usage_photoStandard_impressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Plain Normal Prints}}</td><td>' . (isset($data['usage_plain_normalImpressions']) ? $data['usage_plain_normalImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Plain Draft Prints}}</td><td>' . (isset($data['usage_plain_draftImpressions']) ? $data['usage_plain_draftImpressions'] : '') . '</td></tr>';
                        echo '<tr><td>{{Plain Better Prints}}</td><td>' . (isset($data['usage_plain_betterImpressions']) ? $data['usage_plain_betterImpressions'] : '') . '</td></tr>';

                        // Display data from getConsumableInfo()
                        echo '<tr><td colspan="2"><strong>{{Informations sur les consommables}}</strong></td></tr>';
                        echo '<tr><td>{{User Replaceable Cartridges}}</td><td>' . (isset($data['numOfUserReplaceableConsumables']) ? $data['numOfUserReplaceableConsumables'] : '') . '</td></tr>';
                        echo '<tr><td>{{Non-User Replaceable Cartridges}}</td><td>' . (isset($data['numOfNonUserReplaceableConsumables']) ? $data['numOfNonUserReplaceableConsumables'] : '') . '</td></tr>';
                        echo '<tr><td>{{Alignment Mode}}</td><td>' . (isset($data['alignmentMode']) ? $data['alignmentMode'] : '') . '</td></tr>';
                        echo '<tr><td>{{Single Cartridge Mode}}</td><td>' . (isset($data['singleCartridgeMode']) ? $data['singleCartridgeMode'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 Color}}</td><td>' . (isset($data['consumable_1_markerColor']) ? $data['consumable_1_markerColor'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 Level (%)}}</td><td>' . (isset($data['consumable_1_percentageLevelRemaining']) ? $data['consumable_1_percentageLevelRemaining'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 Model}}</td><td>' . (isset($data['consumable_1_selectibilityNumber']) ? $data['consumable_1_selectibilityNumber'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 State}}</td><td>' . (isset($data['consumable_1_lifeState']) ? $data['consumable_1_lifeState'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 Count}}</td><td>' . (isset($data['consumable_1_cumulativeConsumableCount']) ? $data['consumable_1_cumulativeConsumableCount'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 Used}}</td><td>' . (isset($data['consumable_1_cumulativeMarkingAgentUsed']) ? $data['consumable_1_cumulativeMarkingAgentUsed'] : '') . ' ' . (isset($data['consumable_1_cumulativeMarkingAgentUsedUnit']) ? $data['consumable_1_cumulativeMarkingAgentUsedUnit'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 1 Raw Level (%)}}</td><td>' . (isset($data['consumable_1_rawPercentageLevelRemaining']) ? $data['consumable_1_rawPercentageLevelRemaining'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 Color}}</td><td>' . (isset($data['consumable_2_markerColor']) ? $data['consumable_2_markerColor'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 Level (%)}}</td><td>' . (isset($data['consumable_2_percentageLevelRemaining']) ? $data['consumable_2_percentageLevelRemaining'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 Model}}</td><td>' . (isset($data['consumable_2_selectibilityNumber']) ? $data['consumable_2_selectibilityNumber'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 State}}</td><td>' . (isset($data['consumable_2_lifeState']) ? $data['consumable_2_lifeState'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 Count}}</td><td>' . (isset($data['consumable_2_cumulativeConsumableCount']) ? $data['consumable_2_cumulativeConsumableCount'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 Used}}</td><td>' . (isset($data['consumable_2_cumulativeMarkingAgentUsed']) ? $data['consumable_2_cumulativeMarkingAgentUsed'] : '') . ' ' . (isset($data['consumable_2_cumulativeMarkingAgentUsedUnit']) ? $data['consumable_2_cumulativeMarkingAgentUsedUnit'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cartridge 2 Raw Level (%)}}</td><td>' . (isset($data['consumable_2_rawPercentageLevelRemaining']) ? $data['consumable_2_rawPercentageLevelRemaining'] : '') . '</td></tr>';

                        // Display data from getNetworkAppInfo()
                        echo '<tr><td colspan="2"><strong>{{Informations Réseau}}</strong></td></tr>';
                        echo '<tr><td>{{mDNS Support}}</td><td>' . (isset($data['mdnsSupport']) ? $data['mdnsSupport'] : '') . '</td></tr>';
                        echo '<tr><td>{{Application Service Name}}</td><td>' . (isset($data['applicationServiceName']) ? $data['applicationServiceName'] : '') . '</td></tr>';
                        echo '<tr><td>{{Domain Name}}</td><td>' . (isset($data['domainName']) ? $data['domainName'] : '') . '</td></tr>';
                        echo '<tr><td>{{Proxy Support}}</td><td>' . (isset($data['proxySupport']) ? $data['proxySupport'] : '') . '</td></tr>';
                        echo '<tr><td>{{SNMP Support}}</td><td>' . (isset($data['snmpSupport']) ? $data['snmpSupport'] : '') . '</td></tr>';
                        echo '<tr><td>{{WS-Discovery}}</td><td>' . (isset($data['wsDiscovery']) ? $data['wsDiscovery'] : '') . '</td></tr>';
                        echo '<tr><td>{{WS-Print}}</td><td>' . (isset($data['wsPrint']) ? $data['wsPrint'] : '') . '</td></tr>';
                        echo '<tr><td>{{WS-Scan}}</td><td>' . (isset($data['wsScan']) ? $data['wsScan'] : '') . '</td></tr>';
                        echo '<tr><td>{{HTTPS Redirection}}</td><td>' . (isset($data['httpsRedirection']) ? $data['httpsRedirection'] : '') . '</td></tr>';
                        echo '<tr><td>{{Port 9100 Printing}}</td><td>' . (isset($data['port9100PrintingSupport']) ? $data['port9100PrintingSupport'] : '') . '</td></tr>';
                        echo '<tr><td>{{IPP Support}}</td><td>' . (isset($data['ippSupport']) ? $data['ippSupport'] : '') . '</td></tr>';
                        echo '<tr><td>{{Web Scan}}</td><td>' . (isset($data['webScan']) ? $data['webScan'] : '') . '</td></tr>';
                        echo '<tr><td>{{Direct Print}}</td><td>' . (isset($data['directPrint']) ? $data['directPrint'] : '') . '</td></tr>';

                        // Display data from getPrintConfigInfo()
                        echo '<tr><td colspan="2"><strong>{{Paramètres d'impression}}</strong></td></tr>';
                        echo '<tr><td>{{Default Print Copies}}</td><td>' . (isset($data['defaultPrintCopies']) ? $data['defaultPrintCopies'] : '') . '</td></tr>';
                        echo '<tr><td>{{Default Courier}}</td><td>' . (isset($data['defaultCourier']) ? $data['defaultCourier'] : '') . '</td></tr>';
                        echo '<tr><td>{{Default PDL Orientation}}</td><td>' . (isset($data['defaultPdlInterpreterOrientation']) ? $data['defaultPdlInterpreterOrientation'] : '') . '</td></tr>';
                        echo '<tr><td>{{Jam Recovery}}</td><td>' . (isset($data['jamRecovery']) ? $data['jamRecovery'] : '') . '</td></tr>';
                        echo '<tr><td>{{Resolution Setting}}</td><td>' . (isset($data['resolutionSetting']) ? $data['resolutionSetting'] : '') . '</td></tr>';
                        echo '<tr><td>{{Borderless Printing}}</td><td>' . (isset($data['borderlessPrinting']) ? $data['borderlessPrinting'] : '') . '</td></tr>';
                        echo '<tr><td>{{Print Quality}}</td><td>' . (isset($data['printQuality']) ? $data['printQuality'] : '') . '</td></tr>';
                        echo '<tr><td>{{ColorLok}}</td><td>' . (isset($data['colorLok']) ? $data['colorLok'] : '') . '</td></tr>';
                        echo '<tr><td>{{Ink Sliders}}</td><td>' . (isset($data['inkSliders']) ? $data['inkSliders'] : '') . '</td></tr>';

                        // Display data from getIoConfigInfo()
                        echo '<tr><td colspan="2"><strong>{{Configuration E/S}}</strong></td></tr>';
                        echo '<tr><td>{{Hostname}}</td><td>' . (isset($data['hostname']) ? $data['hostname'] : '') . '</td></tr>';
                        echo '<tr><td>{{Default Hostname}}</td><td>' . (isset($data['defaultHostname']) ? $data['defaultHostname'] : '') . '</td></tr>';
                        echo '<tr><td>{{Hostname Config Method}}</td><td>' . (isset($data['currentHostnameConfigByMethod']) ? $data['currentHostnameConfigByMethod'] : '') . '</td></tr>';
                        echo '<tr><td>{{IPv4 Domain Name}}</td><td>' . (isset($data['ipv4DomainName']) ? $data['ipv4DomainName'] : '') . '</td></tr>';
                        echo '<tr><td>{{IPv6 Domain Name}}</td><td>' . (isset($data['ipv6DomainName']) ? $data['ipv6DomainName'] : '') . '</td></tr>';

                        // Display data from getEPrintConfigInfo()
                        echo '<tr><td colspan="2"><strong>{{Configuration ePrint}}</strong></td></tr>';
                        echo '<tr><td>{{ePrint Email Service}}</td><td>' . (isset($data['emailService']) ? $data['emailService'] : '') . '</td></tr>';
                        echo '<tr><td>{{ePrint Mobile Apps}}</td><td>' . (isset($data['mobileAppsService']) ? $data['mobileAppsService'] : '') . '</td></tr>';
                        echo '<tr><td>{{ePrint Registration State}}</td><td>' . (isset($data['registrationState']) ? $data['registrationState'] : '') . '</td></tr>';
                        echo '<tr><td>{{ePrint Connection State}}</td><td>' . (isset($data['signalingConnectionState']) ? $data['signalingConnectionState'] : '') . '</td></tr>';
                        echo '<tr><td>{{Cloud Services Status}}</td><td>' . (isset($data['cloudServicesSwitchStatus']) ? $data['cloudServicesSwitchStatus'] : '') . '</td></tr>';
                        echo '<tr><td>{{ePrint Reg. Step}}</td><td>' . (isset($data['registrationStepCompleted']) ? $data['registrationStepCompleted'] : '') . '</td></tr>';
                        echo '<tr><td>{{ePrint Platform ID}}</td><td>' . (isset($data['platformIdentifier']) ? $data['platformIdentifier'] : '') . '</td></tr>';
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include the JavaScript file for the plugin
include_file('desktop', 'hp_printer', 'js', 'hp_printer');
?>
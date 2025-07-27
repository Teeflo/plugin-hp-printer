<?php
/**
 * Class hp_printer_api
 *
 * This class handles direct communication with HP printers to retrieve various status and configuration data.
 */
class hp_printer_connector {

    private $ipAddress; // The IP address of the HP printer
    private $protocol; // The protocol to use (http or https)
    private $configuration; // The equipment configuration

    /**
     * Constructor
     *
     * @param string $ipAddress The IP address of the HP printer.
     * @param string $protocol The protocol to use (http or https). Defaults to 'http'.
     * @param array $configuration The equipment configuration.
     */
    public function __construct($ipAddress, $protocol = 'http', $configuration = []) {
        $this->ipAddress = $ipAddress;
        $this->protocol = $protocol;
        $this->configuration = $configuration;
    }

    /**
     * Get a configuration value.
     *
     * @param string $key The key of the configuration value.
     * @param mixed $default The default value if the key is not found.
     * @return mixed The configuration value.
     */
    private function getConfiguration($key, $default = null) {
        return isset($this->configuration[$key]) ? $this->configuration[$key] : $default;
    }

    /**
     * Fetches XML content from the printer using cURL and parses it into a SimpleXMLElement object.
     *
     * @param string $path The path to the XML endpoint (e.g., '/DevMgmt/ProductConfigDyn.xml').
     * @return SimpleXMLElement|false Returns a SimpleXMLElement object on success, or false on failure.
     */
    private function _fetchXml($path) {
        $url = $this->protocol . "://" . $this->ipAddress . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout

        if ($this->protocol === 'https') {
            $verifySsl = $this->getConfiguration('verifySsl', true);
            if ($verifySsl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            } else {
                // AVERTISSEMENT : La désactivation de la vérification SSL expose à des risques de sécurité.
                // À n'utiliser qu'en connaissance de cause dans un environnement contrôlé.
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlErrno) {
            throw new Exception("cURL Error ({$curlErrno}): {$curlError}", 1);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: Received HTTP code {$httpCode}", 2);
        }

        if (empty($response)) {
            throw new Exception("Empty response from printer", 3);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            $xmlErrors = [];
            foreach (libxml_get_errors() as $error) {
                $xmlErrors[] = trim($error->message);
            }
            libxml_clear_errors();
            throw new Exception("Failed to parse XML: " . implode('; ', $xmlErrors), 4);
        }
        return $xml;
    }

    /**
     * Helper function to safely get a single XPath result as a string.
     *
     * @param SimpleXMLElement $xml The SimpleXMLElement object.
     * @param string $xpath The XPath query.
     * @return string The string value of the first result, or an empty string if not found.
     */
    private function _getXpathValue(SimpleXMLElement $xml, $xpath) {
        $result = $xml->xpath($xpath);
        return (isset($result[0])) ? (string)$result[0] : '';
    }

    /**
     * Retrieves consumable information from the printer.
     *
     * @return array An associative array of consumable data.
     */
    public function getConsumableInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/DevMgmt/ConsumableConfigDyn.xml');
            // Register namespaces for XPath queries
            $xml->registerXPathNamespace('ccdyn', 'http://www.hp.com/schemas/imaging/con/ledm/consumableconfigdyn/2007/11/19');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd2', 'http://www.hp.com/schemas/imaging/con/dictionaries/2008/10/10');

            $data['numOfUserReplaceableConsumables'] = $this->_getXpathValue($xml, '//dd:NumOfUserReplaceableConsumables');
            $data['numOfNonUserReplaceableConsumables'] = $this->_getXpathValue($xml, '//dd:NumOfNonUserReplaceableConsumables');
            $data['alignmentMode'] = $this->_getXpathValue($xml, '//dd:AlignmentMode');
            $data['singleCartridgeMode'] = $this->_getXpathValue($xml, '//ccdyn:SingleCartridgeMode');

            $consumables = $xml->xpath('//ccdyn:ConsumableInfo');
            foreach ($consumables as $index => $consumable) {
                $data['consumable_' . ($index + 1) . '_percentageLevelRemaining'] = $this->_getXpathValue($consumable, './dd:ConsumablePercentageLevelRemaining');
                $data['consumable_' . ($index + 1) . '_selectibilityNumber'] = $this->_getXpathValue($consumable, './dd:ConsumableSelectibilityNumber');
                $data['consumable_' . ($index + 1) . '_lifeState'] = $this->_getXpathValue($consumable, './dd:ConsumableLifeState/dd:ConsumableState');
                $data['consumable_' . ($index + 1) . '_markerColor'] = $this->_getXpathValue($consumable, './dd:MarkerColor');
                $data['consumable_' . ($index + 1) . '_cumulativeConsumableCount'] = $this->_getXpathValue($consumable, './dd2:CumulativeConsumableCount');
                $data['consumable_' . ($index + 1) . '_cumulativeMarkingAgentUsed'] = $this->_getXpathValue($consumable, './dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
                $data['consumable_' . ($index + 1) . '_cumulativeMarkingAgentUsedUnit'] = $this->_getXpathValue($consumable, './dd2:CumulativeMarkingAgentUsed/dd:Unit');
                $data['consumable_' . ($index + 1) . '_rawPercentageLevelRemaining'] = $this->_getXpathValue($consumable, './dd:ConsumableRawPercentageLevelRemaining');
            }
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching consumable info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves network application information from the printer.
     *
     * @return array An associative array of network application data.
     */
    public function getNetworkAppInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/DevMgmt/NetAppsDyn.xml');
            $xml->registerXPathNamespace('nadyn', 'http://www.hp.com/schemas/imaging/con/ledm/netappdyn/2009/06/24');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd3', 'http://www.hp.com/schemas/imaging/con/dictionaries/2009/04/06');

            $data['mdnsSupport'] = $this->_getXpathValue($xml, '//dd:MDNSSupport');
            $data['applicationServiceName'] = $this->_getXpathValue($xml, '//dd:ApplicationServiceName');
            $data['domainName'] = $this->_getXpathValue($xml, '//dd3:DomainName');
            $data['proxySupport'] = $this->_getXpathValue($xml, '//nadyn:ProxyConfig/dd:ProxySupport');
            $data['snmpSupport'] = $this->_getXpathValue($xml, '//dd:SNMPConfigWithVersion/dd:SNMP');
            $data['wsDiscovery'] = $this->_getXpathValue($xml, '//nadyn:WebServicesConfig/dd:WSDiscovery');
            $data['wsPrint'] = $this->_getXpathValue($xml, '//nadyn:WebServicesConfig/dd:WSPrint');
            $data['wsScan'] = $this->_getXpathValue($xml, '//nadyn:WebServicesConfig/nadyn:WSScan');
            $data['httpsRedirection'] = $this->_getXpathValue($xml, '//dd:HTTPSRedirection');
            $data['port9100PrintingSupport'] = $this->_getXpathValue($xml, '//dd:Port9100PrintingSupport');
            $data['ippSupport'] = $this->_getXpathValue($xml, '//dd:IPPSupport');
            $data['webScan'] = $this->_getXpathValue($xml, '//nadyn:WebScan');
            $data['directPrint'] = $this->_getXpathValue($xml, '//nadyn:DirectPrint');
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching network app info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves print configuration information from the printer.
     *
     * @return array An associative array of print configuration data.
     */
    public function getPrintConfigInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/DevMgmt/PrintConfigDyn.xml');
            $xml->registerXPathNamespace('prncfgdyn2', 'http://www.hp.com/schemas/imaging/con/ledm/printconfigdyn/2009/05/06');
            $xml->registerXPathNamespace('prncfgdyn', 'http://www.hp.com/schemas/imaging/con/ledm/printconfigdyn/2007/11/02');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $data['defaultPrintCopies'] = $this->_getXpathValue($xml, '//dd:DefaultPrintCopies');
            $data['defaultCourier'] = $this->_getXpathValue($xml, '//dd:DefaultCourier');
            $data['defaultPdlInterpreterOrientation'] = $this->_getXpathValue($xml, '//dd:DefaultPDLInterpreterOrientation');
            $data['jamRecovery'] = $this->_getXpathValue($xml, '//dd:JamRecovery');
            $data['resolutionSetting'] = $this->_getXpathValue($xml, '//dd:ResolutionSetting');
            $data['borderlessPrinting'] = $this->_getXpathValue($xml, '//dd:BorderlessPrinting');
            $data['printQuality'] = $this->_getXpathValue($xml, '//dd:PrintQuality');
            $data['colorLok'] = $this->_getXpathValue($xml, '//prncfgdyn:ColorLok');
            $data['inkSliders'] = $this->_getXpathValue($xml, '//prncfgdyn2:InkSliders');
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching print config info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves product configuration information from the printer.
     *
     * @return array An associative array of product configuration data.
     */
    public function getProductConfigInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/DevMgmt/ProductConfigDyn.xml');
            $xml->registerXPathNamespace('prdcfgdyn2', 'http://www.hp.com/schemas/imaging/con/ledm/productconfigdyn/2009/03/16');
            $xml->registerXPathNamespace('prdcfgdyn', 'http://www.hp.com/schemas/imaging/con/ledm/productconfigdyn/2007/11/05');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $data['firmwareRevision'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:Version/dd:Revision');
            $data['firmwareDate'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:Version/dd:Date');
            $data['makeAndModel'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:MakeAndModel');
            $data['serialNumber'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:SerialNumber');
            $data['productNumber'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:ProductNumber');
            $data['passwordStatus'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:PasswordStatus');
            $data['regionIdentifier'] = $this->_getXpathValue($xml, '//prdcfgdyn:ProductInformation/dd:RegionInformation/dd:RegionIdentifier');
            $data['powerSaveTimeout'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:PowerSaveTimeout');
            $data['autoOffTime'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:AutoOffTime');
            $data['deviceLanguage'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/prdcfgdyn:ProductLanguage/dd:DeviceLanguage');
            $data['preferredLanguage'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/prdcfgdyn:ProductLanguage/dd:PreferredLanguage');
            $data['countryAndRegionName'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/prdcfgdyn:CountryAndRegionName');
            $data['timestamp'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:TimeStamp');
            $data['timeFormat'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:TimeFormat');
            $data['dateFormat'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:DateFormat');
            $data['quietPrintMode'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:QuietPrintMode');
            $data['duplex'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:Duplex');
            $data['ewsStatus'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/prdcfgdyn:EWS/dd:EnableDisable');
            $data['deviceLocation'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/prdcfgdyn:DeviceInformation/dd:DeviceLocation');
            $data['controlPanelAccess'] = $this->_getXpathValue($xml, '//prdcfgdyn2:ProductSettings/dd:ControlPanelAccess');
            $data['availableMemoryKB'] = $this->_getXpathValue($xml, '//prdcfgdyn:Memory/dd:AvailableMemory');
            $data['totalMemoryKB'] = $this->_getXpathValue($xml, '//prdcfgdyn:Memory/dd:TotalMemory');
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching product config info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves product status information from the printer.
     *
     * @return array An associative array of product status data.
     */
    public function getProductStatusInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/DevMgmt/ProductStatusDyn.xml');
            $xml->registerXPathNamespace('psdyn', 'http://www.hp.com/schemas/imaging/con/ledm/productstatusdyn/2007/10/31');
            $xml->registerXPathNamespace('pscat', 'http://www.hp.com/schemas/imaging/con/ledm/productstatuscategories/2007/10/31');
            $xml->registerXPathNamespace('locid', 'http://www.hp.com/schemas/imaging/con/ledm/localizationids/2007/10/31');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $data['statusCategory'] = $this->_getXpathValue($xml, '//pscat:StatusCategory');
            $data['statusStringId'] = $this->_getXpathValue($xml, '//locid:StringId');
            $data['alertTableModificationNumber'] = $this->_getXpathValue($xml, '//dd:ModificationNumber');
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching product status info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves product usage information from the printer.
     *
     * @return array An associative array of product usage data.
     */
    public function getProductUsageInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/DevMgmt/ProductUsageDyn.xml');
            $xml->registerXPathNamespace('pudyn', 'http://www.hp.com/schemas/imaging/con/ledm/productusagedyn/2007/12/11');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd2', 'http://www.hp.com/schemas/imaging/con/dictionaries/2008/10/10');

            $data['totalImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:TotalImpressions');
            $data['monochromeImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:MonochromeImpressions');
            $data['colorImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:ColorImpressions');
            $data['simplexSheets'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:SimplexSheets');
            $data['duplexSheets'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:DuplexSheets');
            $data['jamEvents'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:JamEvents');
            $data['mispickEvents'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:MispickEvents');
            $data['totalFrontPanelCancelPresses'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:TotalFrontPanelCancelPresses');
            $data['cumulativeMarkingAgentUsedTotal'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/pudyn:UsageByMarkingAgent/dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
            $data['cumulativeMarkingAgentUsedTotalUnit'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/pudyn:UsageByMarkingAgent/dd2:CumulativeMarkingAgentUsed/dd:Unit');
            $data['ewsAccessCount'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:EWSAccessCount');
            $data['networkImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:NetworkImpressions');
            $data['wirelessNetworkImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrinterSubunit/dd:WirelessNetworkImpressions');

            // Consumables (dynamic iteration)
            $consumables = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable');
            foreach ($consumables as $index => $consumable) {
                $data['consumable_' . ($index + 1) . '_markerColor'] = $this->_getXpathValue($consumable, './dd:MarkerColor');
                $data['consumable_' . ($index + 1) . '_cumulativeConsumableCount'] = $this->_getXpathValue($consumable, './dd2:CumulativeConsumableCount');
                $data['consumable_' . ($index + 1) . '_cumulativeMarkingAgentUsed'] = $this->_getXpathValue($consumable, './dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
                $data['consumable_' . ($index + 1) . '_cumulativeMarkingAgentUsedUnit'] = $this->_getXpathValue($consumable, './dd2:CumulativeMarkingAgentUsed/dd:Unit');
                $data['consumable_' . ($index + 1) . '_rawPercentageLevelRemaining'] = $this->_getXpathValue($consumable, './dd:ConsumableRawPercentageLevelRemaining');
            }

            $data['scannerTotalScanImages'] = $this->_getXpathValue($xml, '//pudyn:ScannerEngineSubunit/dd:ScanImages');
            $data['scannerFlatbedImages'] = $this->_getXpathValue($xml, '//pudyn:ScannerEngineSubunit/dd:FlatbedImages');
            $data['copyTotalImpressions'] = $this->_getXpathValue($xml, '//pudyn:CopyApplicationSubunit/dd:TotalImpressions');
            $data['copyColorImpressions'] = $this->_getXpathValue($xml, '//pudyn:CopyApplicationSubunit/dd:ColorImpressions');
            $data['copyMonochromeImpressions'] = $this->_getXpathValue($xml, '//pudyn:CopyApplicationSubunit/dd:MonochromeImpressions');
            $data['scanFlatbedImages'] = $this->_getXpathValue($xml, '//pudyn:ScanApplicationSubunit/dd:FlatbedImages');
            $data['printTotalImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrintApplicationSubunit/dd:TotalImpressions');
            $data['printPhotoImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrintApplicationSubunit/dd:PhotoImpressions');
            $data['printCloudPrintImpressions'] = $this->_getXpathValue($xml, '//pudyn:PrintApplicationSubunit/dd:CloudPrintImpressions');

            // Usage by Media Type (example for 'plain' and 'photoStandard')
            $plainImpressions = $xml->xpath('//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:TotalImpressions');
            if (!empty($plainImpressions)) {
                $data['usage_plain_impressions'] = (string)$plainImpressions[0];
            }
            $photoImpressions = $xml->xpath('//pudyn:UsageByMediaType[dd:MediaType="photoStandard"]/dd:TotalImpressions');
            if (!empty($photoImpressions)) {
                $data['usage_photoStandard_impressions'] = (string)$photoImpressions[0];
            }

            // Usage by Quality (example for 'plain' media type)
            $normalImpressions = $this->_getXpathValue($xml, '//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:UsageByQuality/dd:NormalImpressions');
            $draftImpressions = $this->_getXpathValue($xml, '//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:UsageByQuality/dd:DraftImpressions');
            $betterImpressions = $this->_getXpathValue($xml, '//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:UsageByQuality/dd:BetterImpressions');

            if (!empty($normalImpressions)) {
                $data['usage_plain_normalImpressions'] = $normalImpressions;
            }
            if (!empty($draftImpressions)) {
                $data['usage_plain_draftImpressions'] = $draftImpressions;
            }
            if (!empty($betterImpressions)) {
                $data['usage_plain_betterImpressions'] = $betterImpressions;
            }
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching product usage info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves I/O configuration information from the printer.
     *
     * @return array An associative array of I/O configuration data.
     */
    public function getIoConfigInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/IoMgmt/IoConfig.xml');
            $xml->registerXPathNamespace('io', 'http://www.hp.com/schemas/imaging/con/ledm/iomgmt/2008/11/30');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd3', 'http://www.hp.com/schemas/imaging/con/dictionaries/2009/04/06');

            $data['hostname'] = $this->_getXpathValue($xml, '//dd3:Hostname');
            $data['defaultHostname'] = $this->_getXpathValue($xml, '//dd3:DefaultHostname');
            $data['currentHostnameConfigByMethod'] = $this->_getXpathValue($xml, '//dd:CurrentHostnameConfigByMethod');
            $data['ipv4DomainName'] = $this->_getXpathValue($xml, '//io:IPv4DomainName/dd3:DomainName');
            $data['ipv6DomainName'] = $this->_getXpathValue($xml, '//io:IPv6DomainName/dd3:DomainName');
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching I/O config info: ' . $e->getMessage());
        }
        return $data;
    }

    /**
     * Retrieves ePrint configuration information from the printer.
     *
     * @return array An associative array of ePrint configuration data.
     */
    public function getEPrintConfigInfo() {
        $data = [];
        try {
            $xml = $this->_fetchXml('/ePrint/ePrintConfigDyn.xml');
            $xml->registerXPathNamespace('ep', 'http://www.hp.com/schemas/imaging/con/eprint/2010/04/30');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $data['emailService'] = $this->_getXpathValue($xml, '//ep:CloudConfiguration/ep:EmailService');
            $data['mobileAppsService'] = $this->_getXpathValue($xml, '//ep:CloudConfiguration/ep:MobileAppsService');
            $data['registrationState'] = $this->_getXpathValue($xml, '//ep:RegistrationState');
            $data['signalingConnectionState'] = $this->_getXpathValue($xml, '//ep:SignalingConnectionState');
            $data['cloudServicesSwitchStatus'] = $this->_getXpathValue($xml, '//ep:CloudServicesSwitch/ep:Status');
            $data['registrationStepCompleted'] = $this->_getXpathValue($xml, '//ep:RegistrationDetails/ep:RegistrationStepCompleted');
            $data['platformIdentifier'] = $this->_getXpathValue($xml, '//ep:RegistrationDetails/ep:PlatformIdentifier');
        } catch (Exception $e) {
            log::add('hp_printer', 'error', 'Error fetching ePrint config info: ' . $e->getMessage());
        }
        return $data;
    }
}
?>
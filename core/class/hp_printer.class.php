<?php
/**
 * Class hp_printer
 *
 * This class handles communication with HP printers to retrieve various status and configuration data.
 */
class hp_printer {

    private $ipAddress; // The IP address of the HP printer

    /**
     * Constructor
     *
     * @param string $ipAddress The IP address of the HP printer.
     */
    public function __construct($ipAddress) {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Fetches XML content from the printer using cURL and parses it into a SimpleXMLElement object.
     *
     * @param string $path The path to the XML endpoint (e.g., '/DevMgmt/ProductConfigDyn.xml').
     * @return SimpleXMLElement|false Returns a SimpleXMLElement object on success, or false on failure.
     */
    private function _fetchXml($path) {
        $url = "http://" . $this->ipAddress . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            log::error("HP Printer Plugin: Failed to fetch XML from {$url}. HTTP Code: {$httpCode}, Error: {$error}");
            return false;
        }

        // Suppress XML parsing errors to handle malformed XML gracefully
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = $error->message;
            }
            libxml_clear_errors();
            log::error("HP Printer Plugin: Failed to parse XML from {$url}. Errors: " . implode(", ", $errors));
            // Return an empty SimpleXMLElement to prevent errors in subsequent XPath queries
            return new SimpleXMLElement('<root/>');
        }
        return $xml;
    }

    /**
     * Retrieves consumable information from the printer.
     *
     * @return array An associative array of consumable data.
     */
    public function getConsumableInfo() {
        $data = [];
        $xml = $this->_fetchXml('/DevMgmt/ConsumableConfigDyn.xml');
        if ($xml) {
            // Register namespaces for XPath queries
            $xml->registerXPathNamespace('ccdyn', 'http://www.hp.com/schemas/imaging/con/ledm/consumableconfigdyn/2007/11/19');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd2', 'http://www.hp.com/schemas/imaging/con/dictionaries/2008/10/10');

            $result = $xml->xpath('//dd:NumOfUserReplaceableConsumables');
            $data['numOfUserReplaceableConsumables'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:NumOfNonUserReplaceableConsumables');
            $data['numOfNonUserReplaceableConsumables'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:AlignmentMode');
            $data['alignmentMode'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ccdyn:SingleCartridgeMode');
            $data['singleCartridgeMode'] = (isset($result[0])) ? (string)$result[0] : '';

            $consumables = $xml->xpath('//ccdyn:ConsumableInfo');
            foreach ($consumables as $index => $consumable) {
                $result = $consumable->xpath('./dd:ConsumablePercentageLevelRemaining');
                $data['consumable_' . ($index + 1) . '_percentageLevelRemaining'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd:ConsumableSelectibilityNumber');
                $data['consumable_' . ($index + 1) . '_selectibilityNumber'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd:ConsumableLifeState/dd:ConsumableState');
                $data['consumable_' . ($index + 1) . '_lifeState'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd:MarkerColor');
                $data['consumable_' . ($index + 1) . '_markerColor'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd2:CumulativeConsumableCount');
                $data['consumable_' . ($index + 1) . '_cumulativeConsumableCount'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
                $data['consumable_' . ($index + 1) . '_cumulativeMarkingAgentUsed'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd2:CumulativeMarkingAgentUsed/dd:Unit');
                $data['consumable_' . ($index + 1) . '_cumulativeMarkingAgentUsedUnit'] = (isset($result[0])) ? (string)$result[0] : '';
                $result = $consumable->xpath('./dd:ConsumableRawPercentageLevelRemaining');
                $data['consumable_' . ($index + 1) . '_rawPercentageLevelRemaining'] = (isset($result[0])) ? (string)$result[0] : '';
            }
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
        $xml = $this->_fetchXml('/DevMgmt/NetAppsDyn.xml');
        if ($xml) {
            $xml->registerXPathNamespace('nadyn', 'http://www.hp.com/schemas/imaging/con/ledm/netappdyn/2009/06/24');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd3', 'http://www.hp.com/schemas/imaging/con/dictionaries/2009/04/06');

            $result = $xml->xpath('//dd:MDNSSupport');
            $data['mdnsSupport'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:ApplicationServiceName');
            $data['applicationServiceName'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd3:DomainName');
            $data['domainName'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//nadyn:ProxyConfig/dd:ProxySupport');
            $data['proxySupport'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:SNMPConfigWithVersion/dd:SNMP');
            $data['snmpSupport'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//nadyn:WebServicesConfig/dd:WSDiscovery');
            $data['wsDiscovery'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//nadyn:WebServicesConfig/dd:WSPrint');
            $data['wsPrint'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//nadyn:WebServicesConfig/nadyn:WSScan');
            $data['wsScan'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:HTTPSRedirection');
            $data['httpsRedirection'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:Port9100PrintingSupport');
            $data['port9100PrintingSupport'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:IPPSupport');
            $data['ippSupport'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//nadyn:WebScan');
            $data['webScan'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//nadyn:DirectPrint');
            $data['directPrint'] = (isset($result[0])) ? (string)$result[0] : '';
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
        $xml = $this->_fetchXml('/DevMgmt/PrintConfigDyn.xml');
        if ($xml) {
            $xml->registerXPathNamespace('prncfgdyn2', 'http://www.hp.com/schemas/imaging/con/ledm/printconfigdyn/2009/05/06');
            $xml->registerXPathNamespace('prncfgdyn', 'http://www.hp.com/schemas/imaging/con/ledm/printconfigdyn/2007/11/02');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $result = $xml->xpath('//dd:DefaultPrintCopies');
            $data['defaultPrintCopies'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:DefaultCourier');
            $data['defaultCourier'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:DefaultPDLInterpreterOrientation');
            $data['defaultPdlInterpreterOrientation'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:JamRecovery');
            $data['jamRecovery'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:ResolutionSetting');
            $data['resolutionSetting'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:BorderlessPrinting');
            $data['borderlessPrinting'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:PrintQuality');
            $data['printQuality'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prncfgdyn:ColorLok');
            $data['colorLok'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prncfgdyn2:InkSliders');
            $data['inkSliders'] = (isset($result[0])) ? (string)$result[0] : '';
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
        $xml = $this->_fetchXml('/DevMgmt/ProductConfigDyn.xml');
        if ($xml) {
            $xml->registerXPathNamespace('prdcfgdyn2', 'http://www.hp.com/schemas/imaging/con/ledm/productconfigdyn/2009/03/16');
            $xml->registerXPathNamespace('prdcfgdyn', 'http://www.hp.com/schemas/imaging/con/ledm/productconfigdyn/2007/11/05');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:Version/dd:Revision');
            $data['firmwareRevision'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:Version/dd:Date');
            $data['firmwareDate'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:MakeAndModel');
            $data['makeAndModel'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:SerialNumber');
            $data['serialNumber'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:ProductNumber');
            $data['productNumber'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:PasswordStatus');
            $data['passwordStatus'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:ProductInformation/dd:RegionInformation/dd:RegionIdentifier');
            $data['regionIdentifier'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:PowerSaveTimeout');
            $data['powerSaveTimeout'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:AutoOffTime');
            $data['autoOffTime'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/prdcfgdyn:ProductLanguage/dd:DeviceLanguage');
            $data['deviceLanguage'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/prdcfgdyn:ProductLanguage/dd:PreferredLanguage');
            $data['preferredLanguage'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/prdcfgdyn:CountryAndRegionName');
            $data['countryAndRegionName'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:TimeStamp');
            $data['timestamp'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:TimeFormat');
            $data['timeFormat'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:DateFormat');
            $data['dateFormat'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:QuietPrintMode');
            $data['quietPrintMode'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:Duplex');
            $data['duplex'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/prdcfgdyn:EWS/dd:EnableDisable');
            $data['ewsStatus'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/prdcfgdyn:DeviceInformation/dd:DeviceLocation');
            $data['deviceLocation'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn2:ProductSettings/dd:ControlPanelAccess');
            $data['controlPanelAccess'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:Memory/dd:AvailableMemory');
            $data['availableMemoryKB'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//prdcfgdyn:Memory/dd:TotalMemory');
            $data['totalMemoryKB'] = (isset($result[0])) ? (string)$result[0] : '';
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
        $xml = $this->_fetchXml('/DevMgmt/ProductStatusDyn.xml');
        if ($xml) {
            $xml->registerXPathNamespace('psdyn', 'http://www.hp.com/schemas/imaging/con/ledm/productstatusdyn/2007/10/31');
            $xml->registerXPathNamespace('pscat', 'http://www.hp.com/schemas/imaging/con/ledm/productstatuscategories/2007/10/31');
            $xml->registerXPathNamespace('locid', 'http://www.hp.com/schemas/imaging/con/ledm/localizationids/2007/10/31');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $result = $xml->xpath('//pscat:StatusCategory');
            $data['statusCategory'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//locid:StringId');
            $data['statusStringId'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:ModificationNumber');
            $data['alertTableModificationNumber'] = (isset($result[0])) ? (string)$result[0] : '';
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
        $xml = $this->_fetchXml('/DevMgmt/ProductUsageDyn.xml');
        if ($xml) {
            $xml->registerXPathNamespace('pudyn', 'http://www.hp.com/schemas/imaging/con/ledm/productusagedyn/2007/12/11');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd2', 'http://www.hp.com/schemas/imaging/con/dictionaries/2008/10/10');

            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:TotalImpressions');
            $data['totalImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:MonochromeImpressions');
            $data['monochromeImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:ColorImpressions');
            $data['colorImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:SimplexSheets');
            $data['simplexSheets'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:DuplexSheets');
            $data['duplexSheets'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:JamEvents');
            $data['jamEvents'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:MispickEvents');
            $data['mispickEvents'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:TotalFrontPanelCancelPresses');
            $data['totalFrontPanelCancelPresses'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/pudyn:UsageByMarkingAgent/dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
            $data['cumulativeMarkingAgentUsedTotal'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/pudyn:UsageByMarkingAgent/dd2:CumulativeMarkingAgentUsed/dd:Unit');
            $data['cumulativeMarkingAgentUsedTotalUnit'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:EWSAccessCount');
            $data['ewsAccessCount'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:NetworkImpressions');
            $data['networkImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrinterSubunit/dd:WirelessNetworkImpressions');
            $data['wirelessNetworkImpressions'] = (isset($result[0])) ? (string)$result[0] : '';

            // Consumable 1 (Color)
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[1]/dd:MarkerColor');
            $data['consumable_1_markerColor'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[1]/dd2:CumulativeConsumableCount');
            $data['consumable_1_cumulativeConsumableCount'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[1]/dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
            $data['consumable_1_cumulativeMarkingAgentUsed'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[1]/dd2:CumulativeMarkingAgentUsed/dd:Unit');
            $data['consumable_1_cumulativeMarkingAgentUsedUnit'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[1]/dd:ConsumableRawPercentageLevelRemaining');
            $data['consumable_1_rawPercentageLevelRemaining'] = (isset($result[0])) ? (string)$result[0] : '';

            // Consumable 2 (Black)
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[2]/dd:MarkerColor');
            $data['consumable_2_markerColor'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[2]/dd2:CumulativeConsumableCount');
            $data['consumable_2_cumulativeConsumableCount'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[2]/dd2:CumulativeMarkingAgentUsed/dd:ValueFloat');
            $data['consumable_2_cumulativeMarkingAgentUsed'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[2]/dd2:CumulativeMarkingAgentUsed/dd:Unit');
            $data['consumable_2_cumulativeMarkingAgentUsedUnit'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ConsumableSubunit/pudyn:Consumable[2]/dd:ConsumableRawPercentageLevelRemaining');
            $data['consumable_2_rawPercentageLevelRemaining'] = (isset($result[0])) ? (string)$result[0] : '';

            $result = $xml->xpath('//pudyn:ScannerEngineSubunit/dd:ScanImages');
            $data['scannerTotalScanImages'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ScannerEngineSubunit/dd:FlatbedImages');
            $data['scannerFlatbedImages'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:CopyApplicationSubunit/dd:TotalImpressions');
            $data['copyTotalImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:CopyApplicationSubunit/dd:ColorImpressions');
            $data['copyColorImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:CopyApplicationSubunit/dd:MonochromeImpressions');
            $data['copyMonochromeImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:ScanApplicationSubunit/dd:FlatbedImages');
            $data['scanFlatbedImages'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrintApplicationSubunit/dd:TotalImpressions');
            $data['printTotalImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrintApplicationSubunit/dd:PhotoImpressions');
            $data['printPhotoImpressions'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//pudyn:PrintApplicationSubunit/dd:CloudPrintImpressions');
            $data['printCloudPrintImpressions'] = (isset($result[0])) ? (string)$result[0] : '';

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
            $normalImpressions = $xml->xpath('//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:UsageByQuality/dd:NormalImpressions');
            if (!empty($normalImpressions)) {
                $data['usage_plain_normalImpressions'] = (string)$normalImpressions[0];
            }
            $draftImpressions = $xml->xpath('//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:UsageByQuality/dd:DraftImpressions');
            if (!empty($draftImpressions)) {
                $data['usage_plain_draftImpressions'] = (string)$draftImpressions[0];
            }
            $betterImpressions = $xml->xpath('//pudyn:UsageByMediaType[dd:MediaType="plain"]/dd:UsageByQuality/dd:BetterImpressions');
            if (!empty($betterImpressions)) {
                $data['usage_plain_betterImpressions'] = (string)$betterImpressions[0];
            }
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
        $xml = $this->_fetchXml('/IoMgmt/IoConfig.xml');
        if ($xml) {
            $xml->registerXPathNamespace('io', 'http://www.hp.com/schemas/imaging/con/ledm/iomgmt/2008/11/30');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            $xml->registerXPathNamespace('dd3', 'http://www.hp.com/schemas/imaging/con/dictionaries/2009/04/06');

            $result = $xml->xpath('//dd3:Hostname');
            $data['hostname'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd3:DefaultHostname');
            $data['defaultHostname'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//dd:CurrentHostnameConfigByMethod');
            $data['currentHostnameConfigByMethod'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//io:IPv4DomainName/dd3:DomainName');
            $data['ipv4DomainName'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//io:IPv6DomainName/dd3:DomainName');
            $data['ipv6DomainName'] = (isset($result[0])) ? (string)$result[0] : '';
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
        $xml = $this->_fetchXml('/ePrint/ePrintConfigDyn.xml');
        if ($xml) {
            $xml->registerXPathNamespace('ep', 'http://www.hp.com/schemas/imaging/con/eprint/2010/04/30');
            $xml->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');

            $result = $xml->xpath('//ep:CloudConfiguration/ep:EmailService');
            $data['emailService'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ep:CloudConfiguration/ep:MobileAppsService');
            $data['mobileAppsService'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ep:RegistrationState');
            $data['registrationState'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ep:SignalingConnectionState');
            $data['signalingConnectionState'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ep:CloudServicesSwitch/ep:Status');
            $data['cloudServicesSwitchStatus'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ep:RegistrationDetails/ep:RegistrationStepCompleted');
            $data['registrationStepCompleted'] = (isset($result[0])) ? (string)$result[0] : '';
            $result = $xml->xpath('//ep:RegistrationDetails/ep:PlatformIdentifier');
            $data['platformIdentifier'] = (isset($result[0])) ? (string)$result[0] : '';
        }
        return $data;
    }

    /**
     * Pulls all data from the printer and updates Jeedom commands.
     * This method is intended to be called by a Jeedom cron job.
     *
     * @param int $eqLogicId The ID of the Jeedom equipment logic.
     */
    public static function pullData($eqLogicId) {
        log::add('hp_printer', 'info', 'Starting data pull for eqLogic ID: ' . $eqLogicId);

        $eqLogic = eqLogic::byId($eqLogicId);
        if (!is_object($eqLogic)) {
            log::error('HP Printer Plugin: Invalid eqLogic ID provided: ' . $eqLogicId);
            return;
        }

        $ipAddress = $eqLogic->getConfiguration('ipAddress'); // Assuming 'ipAddress' is stored in configuration
        if (empty($ipAddress)) {
            log::error('HP Printer Plugin: IP address not configured for eqLogic ID: ' . $eqLogicId);
            return;
        }

        $hpPrinter = new hp_printer($ipAddress);

        $allData = [];
        $allData = array_merge($allData, $hpPrinter->getConsumableInfo());
        $allData = array_merge($allData, $hpPrinter->getNetworkAppInfo());
        $allData = array_merge($allData, $hpPrinter->getPrintConfigInfo());
        $allData = array_merge($allData, $hpPrinter->getProductConfigInfo());
        $allData = array_merge($allData, $hpPrinter->getProductStatusInfo());
        $allData = array_merge($allData, $hpPrinter->getProductUsageInfo());
        $allData = array_merge($allData, $hpPrinter->getIoConfigInfo());
        $allData = array_merge($allData, $hpPrinter->getEPrintConfigInfo());

        foreach ($allData as $key => $value) {
            $cmd = $eqLogic->getCmd(null, $key);
            if (is_object($cmd)) {
                $cmd->setValue($value)->save();
                log::debug('HP Printer Plugin: Updated command ' . $key . ' with value ' . $value);
            } else {
                log::warning('HP Printer Plugin: Command not found for key: ' . $key);
            }
        }
        log::add('hp_printer', 'info', 'Finished data pull for eqLogic ID: ' . $eqLogicId);
    }
}
?>
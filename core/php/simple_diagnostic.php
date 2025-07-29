<?php
/**
 * Script de diagnostic simple pour HP Printer Plugin
 * Ce script ne dépend pas du core Jeedom pour un diagnostic de base
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    $diagnostics = array();
    
    // Test PHP de base
    $diagnostics['php_version'] = PHP_VERSION;
    $diagnostics['php_ok'] = version_compare(PHP_VERSION, '7.0', '>=');
    
    // Test cURL
    $diagnostics['curl_available'] = function_exists('curl_init');
    
    // Test des chemins
    $pluginPath = dirname(__FILE__, 3);
    $diagnostics['plugin_path'] = $pluginPath;
    $diagnostics['plugin_path_exists'] = is_dir($pluginPath);
    
    // Tester les chemins possibles vers le core Jeedom
    $possibleCorePaths = [
        $pluginPath . '/../../core/php/core.inc.php',
        $pluginPath . '/../../../core/php/core.inc.php',
        $pluginPath . '/../../../../core/php/core.inc.php'
    ];
    
    $diagnostics['core_paths_tested'] = array();
    $diagnostics['core_found'] = false;
    $diagnostics['core_path'] = '';
    
    foreach ($possibleCorePaths as $path) {
        $exists = file_exists($path);
        $diagnostics['core_paths_tested'][] = array(
            'path' => $path,
            'exists' => $exists
        );
        
        if ($exists && !$diagnostics['core_found']) {
            $diagnostics['core_found'] = true;
            $diagnostics['core_path'] = $path;
        }
    }
    
    // Test réseau simple
    if ($diagnostics['curl_available']) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'http://www.google.com',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_NOBODY => true
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $diagnostics['network_test'] = array(
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'success' => ($httpCode >= 200 && $httpCode < 400)
        );
    }
    
    // Informations serveur
    $diagnostics['server_info'] = array(
        'software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
        'document_root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'Unknown',
        'script_name' => isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'Unknown'
    );
    
    // Résultat
    $allOk = $diagnostics['php_ok'] && 
             $diagnostics['curl_available'] && 
             $diagnostics['core_found'] && 
             $diagnostics['plugin_path_exists'];
    
    echo json_encode(array(
        'status' => $allOk ? 'success' : 'warning',
        'message' => $allOk ? 'Tous les tests de base sont OK' : 'Certains tests ont échoué',
        'diagnostics' => $diagnostics,
        'timestamp' => date('Y-m-d H:i:s')
    ), JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Erreur lors du diagnostic: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ));
}
?>

<?php
/**
 * Script de test pour diagnostiquer les problèmes de connexion HP Printer
 * À utiliser temporairement pour le débogage
 */

// Headers pour éviter les problèmes de cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction simple de logging
function debug_log($message) {
    error_log("[HP_PRINTER_DEBUG] " . $message);
}

try {
    debug_log("Début du script de test");
    
    // Vérifier si le core Jeedom est accessible
    $coreFile = dirname(__FILE__) . '/../../../core/php/core.inc.php';
    if (!file_exists($coreFile)) {
        throw new Exception("Core Jeedom non trouvé: " . $coreFile);
    }
    debug_log("Core Jeedom trouvé");
    
    require_once $coreFile;
    debug_log("Core Jeedom inclus");
    
    include_file('core', 'authentification', 'php');
    debug_log("Authentification incluse");
    
    // Simuler un test de connexion simple
    $testIP = '8.8.8.8'; // Google DNS pour test de connectivité réseau de base
    
    if (function_exists('curl_init')) {
        debug_log("cURL disponible");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://{$testIP}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_NOBODY => true
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        debug_log("Test cURL - Code: {$httpCode}, Erreur: {$curlError}");
        
        echo json_encode([
            'status' => 'ok',
            'message' => 'Script de débogage exécuté avec succès',
            'curl_available' => true,
            'core_loaded' => true,
            'test_connection' => [
                'http_code' => $httpCode,
                'curl_error' => $curlError
            ]
        ]);
    } else {
        throw new Exception("cURL non disponible");
    }
    
} catch (Exception $e) {
    debug_log("Erreur: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>

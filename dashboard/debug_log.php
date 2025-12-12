<?php
// Debug file untuk troubleshoot log
// Simpan sebagai dashboard/debug_log.php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug Log Connection</h3>";

$load = $_GET['load'];
$session = $_GET['session'];

echo "<p>Load: " . $load . "</p>";
echo "<p>Session: " . $session . "</p>";

// Include necessary files
if (file_exists('../include/config.php')) {
    include('../include/config.php');
    echo "<p>✓ Config loaded</p>";
} else {
    echo "<p>✗ Config file not found</p>";
}

if (file_exists('../include/readcfg.php')) {
    include('../include/readcfg.php');
    echo "<p>✓ Readcfg loaded</p>";
} else {
    echo "<p>✗ Readcfg file not found</p>";
}

if (file_exists('../lib/routeros_api.class.php')) {
    include_once('../lib/routeros_api.class.php');
    echo "<p>✓ RouterOS API loaded</p>";
} else {
    echo "<p>✗ RouterOS API file not found</p>";
}

if (file_exists('../lib/formatbytesbites.php')) {
    include_once('../lib/formatbytesbites.php');
    echo "<p>✓ Format bytes loaded</p>";
} else {
    echo "<p>✗ Format bytes file not found</p>";
}

// Test API connection
$API = new RouterosAPI();
$API->debug = false;

echo "<h4>API Connection Test:</h4>";
echo "<p>Host: " . $iphost . "</p>";
echo "<p>User: " . $userhost . "</p>";

if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    echo "<p>✓ API Connected successfully</p>";
    
    // Test log command
    echo "<h4>Testing Log Commands:</h4>";
    
    // Test 1: Basic log print
    try {
        $getlog1 = $API->comm("/log/print", array("count" => "5"));
        echo "<p>✓ Basic log query returned " . count($getlog1) . " entries</p>";
        
        if (count($getlog1) > 0) {
            echo "<pre>";
            print_r($getlog1[0]);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Basic log query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Hotspot specific log
    try {
        $getlog2 = $API->comm("/log/print", array(
            "?topics" => "hotspot",
            "count" => "5"
        ));
        echo "<p>✓ Hotspot log query returned " . count($getlog2) . " entries</p>";
        
        if (count($getlog2) > 0) {
            echo "<pre>";
            print_r($getlog2[0]);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Hotspot log query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 3: All available log topics
    try {
        $getlog3 = $API->comm("/log/print", array("count" => "10"));
        echo "<h4>Available Log Topics:</h4>";
        $topics = array();
        foreach ($getlog3 as $log) {
            if (isset($log['topics'])) {
                $topics[] = $log['topics'];
            }
        }
        $unique_topics = array_unique($topics);
        echo "<p>Topics: " . implode(", ", $unique_topics) . "</p>";
        
    } catch (Exception $e) {
        echo "<p>✗ Topics query failed: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>✗ API Connection failed</p>";
}
?>
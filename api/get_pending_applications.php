<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    $appManager = new ApplicationManager();
    // Get all pending applications, limit to 50 for performance
    $result = $appManager->getList(['status' => 'pending'], 1, 50);
    
    echo json_encode([
        'success' => true,
        'data' => $result['applications']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

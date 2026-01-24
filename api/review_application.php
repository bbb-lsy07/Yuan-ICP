<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$id = $input['id'] ?? null;
$action = $input['action'] ?? null; // 'approve' or 'reject'
$reason = $input['reason'] ?? '';

if (!$id || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing id or action']);
    exit;
}

try {
    $appManager = new ApplicationManager();
    $adminId = $_SESSION['user_id'];
    
    $result = $appManager->review($id, $action, $adminId, $reason);
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? '已通过' : '已驳回'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

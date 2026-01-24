<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 设置响应头
header('Content-Type: application/json');

// 使用 auth.php 提供的统一登录检查
require_login();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('无效的请求数据');
    }
    
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $action = isset($input['action']) ? $input['action'] : '';
    $reason = isset($input['reason']) ? $input['reason'] : '';
    
    if (!$id || !$action) {
        throw new Exception('缺少必要参数');
    }
    
    $appManager = new ApplicationManager();
    $currentUser = current_user();
    
    if ($action === 'approve') {
        $appManager->review($id, 'approve', $currentUser['id']);
    } elseif ($action === 'reject') {
        if (empty($reason)) {
            throw new Exception('驳回操作必须提供原因');
        }
        $appManager->review($id, 'reject', $currentUser['id'], $reason);
    } else {
        throw new Exception('无效的操作类型');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

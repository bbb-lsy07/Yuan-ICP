<?php
/**
 * 统一的后台管理API接口
 */
require_once __DIR__.'/../includes/bootstrap.php';

// 初始化环境配置
Environment::init();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 检查管理员权限
check_admin_auth();

try {
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    
    // 获取操作类型
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id && $action !== 'clear_logs') {
        throw new Exception('无效的ID');
    }
    
    $db = db();
    $currentUser = current_user();
    
    switch ($action) {
        case 'delete_announcement':
            $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            
            // 记录操作日志
            log_admin_action('delete', 'announcement', "删除公告 ID: {$id}");
            
            echo json_encode(['success' => true, 'message' => '公告已删除']);
            break;
            
        case 'toggle_announcement_pin':
            $stmt = $db->prepare("UPDATE announcements SET is_pinned = NOT is_pinned WHERE id = ?");
            $stmt->execute([$id]);
            
            // 获取更新后的置顶状态
            $stmt = $db->prepare("SELECT is_pinned FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            $is_pinned = $stmt->fetchColumn();
            
            // 记录操作日志
            log_admin_action('update', 'announcement', "切换公告置顶状态 ID: {$id}, 置顶: " . ($is_pinned ? '是' : '否'));
            
            echo json_encode([
                'success' => true, 
                'message' => '置顶状态已更新',
                'is_pinned' => (bool)$is_pinned
            ]);
            break;
            
        case 'delete_application':
            $stmt = $db->prepare("DELETE FROM icp_applications WHERE id = ?");
            $stmt->execute([$id]);
            
            // 记录操作日志
            log_admin_action('delete', 'application', "删除申请 ID: {$id}");
            
            echo json_encode(['success' => true, 'message' => '申请已删除']);
            break;
            
        case 'approve_application':
            $stmt = $db->prepare("UPDATE icp_applications SET status = 'approved', reviewed_at = ".db_now().", reviewed_by = ? WHERE id = ?");
            $stmt->execute([$currentUser['id'], $id]);
            
            // 记录操作日志
            log_admin_action('update', 'application', "批准申请 ID: {$id}");
            
            echo json_encode(['success' => true, 'message' => '申请已批准']);
            break;
            
        case 'reject_application':
            $reason = trim($_POST['reason'] ?? '');
            if (empty($reason)) {
                throw new Exception('请填写拒绝原因');
            }
            
            $stmt = $db->prepare("UPDATE icp_applications SET status = 'rejected', reject_reason = ?, reviewed_at = ".db_now().", reviewed_by = ? WHERE id = ?");
            $stmt->execute([$reason, $currentUser['id'], $id]);
            
            // 记录操作日志
            log_admin_action('update', 'application', "拒绝申请 ID: {$id}, 原因: {$reason}");
            
            echo json_encode(['success' => true, 'message' => '申请已拒绝']);
            break;
            
        case 'toggle_number_status':
            $stmt = $db->prepare("UPDATE selectable_numbers SET status = CASE WHEN status = 'available' THEN 'reserved' ELSE 'available' END WHERE id = ?");
            $stmt->execute([$id]);
            
            // 获取更新后的状态
            $stmt = $db->prepare("SELECT status FROM selectable_numbers WHERE id = ?");
            $stmt->execute([$id]);
            $status = $stmt->fetchColumn();
            
            // 记录操作日志
            log_admin_action('update', 'number', "切换号码状态 ID: {$id}, 状态: {$status}");
            
            echo json_encode([
                'success' => true, 
                'message' => '号码状态已更新',
                'status' => $status
            ]);
            break;
            
        case 'delete_number':
            $stmt = $db->prepare("DELETE FROM selectable_numbers WHERE id = ?");
            $stmt->execute([$id]);
            
            // 记录操作日志
            log_admin_action('delete', 'number', "删除号码 ID: {$id}");
            
            echo json_encode(['success' => true, 'message' => '号码已删除']);
            break;
            
        case 'clear_logs':
            $stmt = $db->prepare("DELETE FROM admin_logs");
            $stmt->execute();
            
            // 记录操作日志
            log_admin_action('delete', 'logs', '清除所有操作日志');
            
            echo json_encode(['success' => true, 'message' => '所有日志已清除']);
            break;
            
        default:
            throw new Exception('无效的操作');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

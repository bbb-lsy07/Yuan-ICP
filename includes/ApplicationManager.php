<?php
/**
 * 备案申请管理类
 * 处理所有与备案申请相关的数据库操作
 */
class ApplicationManager {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * 创建新的备案申请
     * @param array $data 申请数据
     * @return int 申请ID
     * @throws Exception
     */
    public function create($data) {
        $required = ['number', 'website_name', 'domain', 'description', 'owner_name', 'owner_email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("缺少必需字段: {$field}");
            }
        }
        
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO icp_applications (number, website_name, domain, description, owner_name, owner_email, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $data['number'],
                $data['website_name'],
                $data['domain'],
                $data['description'],
                $data['owner_name'],
                $data['owner_email']
            ]);
            
            $applicationId = $this->db->lastInsertId();
            
            // 如果是手动模式，更新号码状态
            $isAutoGenerate = get_config('number_auto_generate', false);
            if (!$isAutoGenerate) {
                $stmt = $this->db->prepare("UPDATE selectable_numbers SET status = 'used', used_at = CURRENT_TIMESTAMP WHERE number = ?");
                $stmt->execute([$data['number']]);
            }
            
            $this->db->commit();
            
            // 发送钉钉通知
            $this->sendDingTalkNotification($applicationId, $data);
            
            return $applicationId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * 获取申请列表
     * @param array $filters 筛选条件
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function getList($filters = [], $page = 1, $perPage = 15) {
        $query = "SELECT a.*, u.username as reviewer FROM icp_applications a
                  LEFT JOIN admin_users u ON a.reviewed_by = u.id";
        $where = [];
        $params = [];
        
        // 状态筛选
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'])) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
        }
        
        // 搜索条件
        if (!empty($filters['search'])) {
            $where[] = "(a.website_name LIKE ? OR a.domain LIKE ? OR a.number LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // 组合WHERE条件
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        // 获取总数
        $countQuery = "SELECT COUNT(*) FROM ($query) as total";
        $total = $this->db->prepare($countQuery);
        $total->execute($params);
        $totalItems = $total->fetchColumn();
        
        // 分页
        $pagination = new Pagination($page, $totalItems, $perPage);
        $query .= " ORDER BY a.created_at DESC " . $pagination->getSqlLimit();
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $applications = $stmt->fetchAll();
        
        // 处理数据
        foreach ($applications as &$app) {
            $app['is_premium'] = check_if_number_is_premium($app['number']);
            $app['created_at_formatted'] = date('Y-m-d H:i', strtotime($app['created_at']));
            $app['status_text'] = $this->getStatusText($app['status']);
            $app['status_class'] = $this->getStatusClass($app['status']);
        }
        
        return [
            'applications' => $applications,
            'pagination' => $pagination->getInfo()
        ];
    }
    
    /**
     * 根据ID获取申请详情
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username as reviewer 
            FROM icp_applications a
            LEFT JOIN admin_users u ON a.reviewed_by = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $application = $stmt->fetch();
        
        if ($application) {
            $application['is_premium'] = check_if_number_is_premium($application['number']);
            $application['created_at_formatted'] = date('Y-m-d H:i', strtotime($application['created_at']));
            $application['status_text'] = $this->getStatusText($application['status']);
            $application['status_class'] = $this->getStatusClass($application['status']);
        }
        
        return $application;
    }
    
    /**
     * 审核申请
     * @param int $id 申请ID
     * @param string $action 操作类型 (approve/reject)
     * @param int $reviewerId 审核人ID
     * @param string $reason 驳回原因（驳回时必需）
     * @return bool
     * @throws Exception
     */
    public function review($id, $action, $reviewerId, $reason = '') {
        if (!in_array($action, ['approve', 'reject'])) {
            throw new InvalidArgumentException("无效的操作类型: {$action}");
        }
        
        if ($action === 'reject' && empty($reason)) {
            throw new InvalidArgumentException("驳回操作必须提供原因");
        }
        
        $this->db->beginTransaction();
        try {
            // 获取申请详情
            $application = $this->getById($id);
            if (!$application) {
                throw new Exception("申请不存在");
            }
            
            if ($application['status'] !== 'pending') {
                throw new Exception("该申请已被处理");
            }
            
            // 更新申请状态
            if ($action === 'approve') {
                $stmt = $this->db->prepare("
                    UPDATE icp_applications 
                    SET status = 'approved', reviewed_by = ?, reviewed_at = " . db_now() . ", is_resubmitted = 0
                    WHERE id = ?
                ");
                $stmt->execute([$reviewerId, $id]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE icp_applications 
                    SET status = 'rejected', reviewed_by = ?, reviewed_at = " . db_now() . ", reject_reason = ?, is_resubmitted = 0
                    WHERE id = ?
                ");
                $stmt->execute([$reviewerId, $reason, $id]);
            }
            
            $this->db->commit();
            
            // 发送邮件通知
            $this->sendNotificationEmail($application, $action, $reason);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * 更新申请信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $allowedFields = ['website_name', 'domain', 'description', 'owner_name', 'owner_email'];
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $params[] = $id;
        $query = "UPDATE icp_applications SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * 删除申请
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM icp_applications WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * 获取统计信息
     * @return array
     */
    public function getStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status IN ('pending', 'pending_payment') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM icp_applications
        ");
        return $stmt->fetch();
    }
    
    /**
     * 发送通知邮件
     * @param array $application
     * @param string $action
     * @param string $reason
     */
    private function sendNotificationEmail($application, $action, $reason = '') {
        $siteName = get_config('site_name', 'Yuan-ICP');
        $site_url = rtrim(get_config('site_url', ''), '/');
        $query_url = "{$site_url}/query.php?icp_number=" . urlencode($application['number']);
        $code_snippet = "<a href='{$query_url}' target='_blank' rel='noopener'>{$application['number']}</a>";
        
        if ($action === 'approve') {
            $mail_data = [
                'user_name' => $application['owner_name'],
                'badge' => '审核通过',
                'subject' => "【{$siteName}】您的备案申请已通过",
                'body' => "恭喜您！您为网站 <strong>{$application['website_name']} ({$application['domain']})</strong> 提交的备案申请已审核通过。<br><br>您的备案号为：<strong style='color: #3b82f6;'>{$application['number']}</strong><br><br>请按照要求将下方备案号链接放置在您网站的底部：",
                'code' => $code_snippet
            ];
        } else {
            $mail_data = [
                'user_name' => $application['owner_name'],
                'badge' => '审核结果',
                'subject' => "【{$siteName}】您的备案申请已被驳回",
                'body' => "很遗憾地通知您，您为网站 <strong>{$application['website_name']} ({$application['domain']})</strong> 提交的备案申请已被驳回。<br><br><div style='background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px;'><strong style='color: #dc2626;'>驳回原因：</strong><br>" . nl2br(htmlspecialchars($reason)) . "</div>请您根据驳回原因修改信息后重新提交申请。感谢您的理解与合作！"
            ];
        }
        
        $html_body = format_email_modern($mail_data);
        send_email($application['owner_email'], $application['owner_name'], $mail_data['subject'], $html_body);
    }
    
    /**
     * 发送钉钉通知
     */
    private function sendDingTalkNotification($applicationId, $data) {
        $enabled = get_config('dingtalk_enabled', '0');
        $webhook = get_config('dingtalk_webhook', '');
        
        if ($enabled !== '1' || empty($webhook)) {
            return;
        }
        
        $siteName = get_config('site_name', 'Yuan-ICP');
        $siteUrl = rtrim(get_config('site_url', ''), '/');
        // 如果没有配置 site_url，尝试从当前请求推断 (虽然 ApplicationManager 可能在 API 中被调用)
        if (empty($siteUrl)) {
             $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
             $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
             $siteUrl = "$protocol://$host";
        }
        
        $adminUrl = $siteUrl . '/admin/quick_approval.php'; // Link to quick approval
        
        $markdown = "### 新的ICP备案申请\n\n";
        $markdown .= "**申请ID**: {$applicationId}\n\n";
        $markdown .= "**网站名称**: {$data['website_name']}\n\n";
        $markdown .= "**域名**: {$data['domain']}\n\n";
        $markdown .= "**申请号码**: {$data['number']}\n\n";
        $markdown .= "**申请人**: {$data['owner_name']}\n\n";
        $markdown .= "**时间**: " . date('Y-m-d H:i:s') . "\n\n";
        $markdown .= "[点击进行极速审批]({$adminUrl})";
        
        $payload = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => "【{$siteName}】新备案申请",
                'text' => $markdown
            ]
        ];
        
        try {
            $ch = curl_init($webhook);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            // 忽略 SSL 证书验证 (防止本地开发环境问题)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                error_log("DingTalk notification curl error: " . curl_error($ch));
            }
            curl_close($ch);
        } catch (Exception $e) {
            error_log("DingTalk notification failed: " . $e->getMessage());
        }
    }

    /**
     * 获取状态文本
     * @param string $status
     * @return string
     */
    private function getStatusText($status) {
        $statuses = get_application_statuses();
        return $statuses[$status]['text'] ?? '未知';
    }
    
    /**
     * 获取状态CSS类
     * @param string $status
     * @return string
     */
    private function getStatusClass($status) {
        $statuses = get_application_statuses();
        return $statuses[$status]['class'] ?? 'secondary';
    }
}
?>

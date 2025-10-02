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
                SUM(CASE WHEN status = 'pending' OR status = 'pending_payment' THEN 1 ELSE 0 END) as pending,
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
        
        if ($action === 'approve') {
            $subject = "【{$siteName}】您的备案申请已通过";
            $body = "
                <p>尊敬的用户 {$application['owner_name']},</p>
                <p>恭喜您！您为网站 <strong>{$application['website_name']} ({$application['domain']})</strong> 提交的备案申请已审核通过。</p>
                <p>您的备案号为：<strong>{$application['number']}</strong></p>
                <p>请按照要求将备案号链接放置在您网站的底部。感谢您的使用！</p>
                <br>
                <p>-- {$siteName} 团队</p>
            ";
        } else {
            $subject = "【{$siteName}】您的备案申请已被驳回";
            $body = "
                <p>尊敬的用户 {$application['owner_name']},</p>
                <p>很遗憾地通知您，您为网站 <strong>{$application['website_name']} ({$application['domain']})</strong> 提交的备案申请已被驳回。</p>
                <p>驳回原因如下：</p>
                <blockquote style='border-left: 4px solid #ccc; padding-left: 15px; margin-left: 0;'>
                    <p>" . nl2br(htmlspecialchars($reason)) . "</p>
                </blockquote>
                <p>请您根据驳回原因修改信息后重新提交申请。感谢您的理解与合作！</p>
                <br>
                <p>-- {$siteName} 团队</p>
            ";
        }
        
        send_email($application['owner_email'], $application['owner_name'], $subject, $body);
    }
    
    /**
     * 获取状态文本
     * @param string $status
     * @return string
     */
    private function getStatusText($status) {
        $statusMap = [
            'pending' => '待审核',
            'pending_payment' => '待付款',
            'approved' => '已通过',
            'rejected' => '已驳回'
        ];
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取状态CSS类
     * @param string $status
     * @return string
     */
    private function getStatusClass($status) {
        $classMap = [
            'pending' => 'warning',
            'pending_payment' => 'info',
            'approved' => 'success',
            'rejected' => 'danger'
        ];
        return $classMap[$status] ?? 'secondary';
    }
}
?>

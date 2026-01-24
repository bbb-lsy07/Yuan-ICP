<?php
/**
 * 公告管理类
 * 处理所有与公告相关的数据库操作
 */
class AnnouncementManager {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * 创建新公告
     * @param array $data 公告数据
     * @return int 公告ID
     * @throws Exception
     */
    public function create($data) {
        $required = ['title', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("缺少必需字段: {$field}");
            }
        }
        
        // 验证数据
        $this->validateData($data);
        
        $stmt = $this->db->prepare("
            INSERT INTO announcements (title, content, is_pinned, created_at, updated_at) 
            VALUES (?, ?, ?, " . db_now() . ", " . db_now() . ")
        ");
        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['is_pinned'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 更新公告
     * @param int $id 公告ID
     * @param array $data 更新数据
     * @return bool
     * @throws Exception
     */
    public function update($id, $data) {
        if (empty($id) || !is_numeric($id)) {
            throw new InvalidArgumentException("无效的公告ID");
        }
        
        // 验证数据
        $this->validateData($data);
        
        $stmt = $this->db->prepare("
            UPDATE announcements 
            SET title = ?, content = ?, is_pinned = ?, updated_at = " . db_now() . " 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['content'],
            $data['is_pinned'] ?? 0,
            $id
        ]);
    }
    
    /**
     * 获取公告列表
     * @param array $filters 筛选条件
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function getList($filters = [], $page = 1, $perPage = 15) {
        $query = "SELECT * FROM announcements";
        $where = [];
        $params = [];
        
        // 状态筛选
        if (isset($filters['is_pinned']) && $filters['is_pinned'] !== '') {
            $where[] = "is_pinned = ?";
            $params[] = $filters['is_pinned'];
        }
        
        // 搜索条件
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR content LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
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
        $query .= " ORDER BY is_pinned DESC, created_at DESC " . $pagination->getSqlLimit();
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $announcements = $stmt->fetchAll();
        
        // 处理数据
        foreach ($announcements as &$announcement) {
            $announcement['created_at_formatted'] = date('Y-m-d H:i', strtotime($announcement['created_at']));
            $announcement['updated_at_formatted'] = date('Y-m-d H:i', strtotime($announcement['updated_at']));
            $announcement['content_preview'] = mb_substr(strip_tags($announcement['content']), 0, 100) . '...';
        }
        
        return [
            'announcements' => $announcements,
            'pagination' => $pagination->getInfo()
        ];
    }
    
    /**
     * 根据ID获取公告详情
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        if (empty($id) || !is_numeric($id)) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        $announcement = $stmt->fetch();
        
        if ($announcement) {
            $announcement['created_at_formatted'] = date('Y-m-d H:i', strtotime($announcement['created_at']));
            $announcement['updated_at_formatted'] = date('Y-m-d H:i', strtotime($announcement['updated_at']));
        }
        
        return $announcement;
    }
    
    /**
     * 删除公告
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM announcements WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * 切换置顶状态
     * @param int $id
     * @return bool
     */
    public function togglePinned($id) {
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        
        $stmt = $this->db->prepare("UPDATE announcements SET is_pinned = NOT is_pinned, updated_at = " . db_now() . " WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * 获取前台显示的公告列表
     * @param int $limit 限制数量
     * @return array
     */
    public function getPublicList($limit = 6) {
        $stmt = $this->db->prepare("
            SELECT id, title, content, created_at, is_pinned 
            FROM announcements 
            ORDER BY is_pinned DESC, created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $announcements = $stmt->fetchAll();
        
        // 处理数据
        foreach ($announcements as &$announcement) {
            $announcement['created_at_formatted'] = date('Y-m-d', strtotime($announcement['created_at']));
            $announcement['content_preview'] = mb_substr(strip_tags($announcement['content']), 0, 150) . '...';
        }
        
        return $announcements;
    }
    
    /**
     * 验证公告数据
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateData($data) {
        // 验证标题
        if (empty($data['title'])) {
            throw new InvalidArgumentException('标题不能为空');
        }
        if (strlen($data['title']) > 200) {
            throw new InvalidArgumentException('标题不能超过200个字符');
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\s\-_.,!?()（）]+$/u', $data['title'])) {
            throw new InvalidArgumentException('标题包含非法字符');
        }
        
        // 验证内容
        if (empty($data['content'])) {
            throw new InvalidArgumentException('内容不能为空');
        }
        if (strlen($data['content']) > 10000) {
            throw new InvalidArgumentException('内容不能超过10000个字符');
        }
        
        // 验证置顶状态
        if (isset($data['is_pinned']) && !in_array($data['is_pinned'], [0, 1])) {
            throw new InvalidArgumentException('置顶状态值无效');
        }
    }
}
?>

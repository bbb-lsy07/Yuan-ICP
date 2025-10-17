<?php
// 此文件被 admin/plugin_proxy.php 包含

$message = '';
$error = '';
$db = db();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的请求，请刷新页面重试';
    } else {
        try {
            // 检查是否是清除记录请求
            if (isset($_POST['clear_logs'])) {
                $db->exec("DELETE FROM plugin_app_security_logs");
                $message = '所有日志记录已清除！';
            } else {
                // 保存设置
                $settings_to_save = [
                    'app_security_guard_enabled' => isset($_POST['enabled']) ? '1' : '0',
                    'app_security_guard_count'   => max(1, intval($_POST['count'] ?? 5)),
                    'app_security_guard_minutes' => max(1, intval($_POST['minutes'] ?? 10)),
                ];
                
                $stmt = $db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
                foreach ($settings_to_save as $key => $value) {
                    $stmt->execute([$key, $value]);
                }
                $message = '申请频率限制设置已成功保存！';
            }
        } catch (Exception $e) {
            $error = '操作失败: ' . $e->getMessage();
        }
    }
}

// 获取当前设置
$is_enabled = get_config('app_security_guard_enabled', '0');
$limit_count = get_config('app_security_guard_count', '5');
$limit_minutes = get_config('app_security_guard_minutes', '10');

// 获取最新的50条日志记录
$logs = [];
try {
    $logs = $db->query("SELECT * FROM plugin_app_security_logs ORDER BY attempt_time DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {
    $error .= " 无法加载日志: " . $e->getMessage();
}
?>

<h2 class="mb-4">申请安全设置 - 频率限制</h2>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h5 class="mb-0">频率限制规则</h5></div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" <?php echo $is_enabled === '1' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="enabled"><strong>启用申请频率限制</strong></label>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="input-group mb-3">
                        <span class="input-group-text">每</span>
                        <input type="number" class="form-control" name="minutes" value="<?php echo htmlspecialchars($limit_minutes); ?>" min="1">
                        <span class="input-group-text">分钟内, 单个IP最多允许</span>
                        <input type="number" class="form-control" name="count" value="<?php echo htmlspecialchars($limit_count); ?>" min="1">
                        <span class="input-group-text">次申请</span>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存设置</button>
            </div>
        </form>
    </div>
</div>

<!-- 新增：日志记录卡片 -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">最近申请尝试记录 (最近50条)</h5>
        <form method="post" style="display: inline;" onsubmit="return confirm('确定要清除所有日志记录吗？此操作不可恢复！');">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="clear_logs" value="1">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash"></i> 清除所有记录
            </button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>时间</th>
                        <th>IP 地址</th>
                        <th>状态</th>
                        <th>详情</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">暂无记录</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['attempt_time']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td>
                                    <?php if ($log['status'] === 'allowed'): ?>
                                        <span class="badge bg-success">允许</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">拦截</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
// 此文件由 admin/plugin_proxy.php 自动包含，因此已登录

$message = '';
$error = '';
$db = db();
$total_sent = 0;
$total_failed = 0;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的请求，请刷新重试。';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $target_group = $_POST['target_group'] ?? 'all';

        if (empty($subject) || empty($content)) {
            $error = '邮件标题和内容不能为空！';
        } else {
            try {
                // 1. 根据目标群体构建查询
                $sql = "SELECT DISTINCT owner_name, owner_email FROM icp_applications WHERE owner_email != ''";
                $params = [];

                if ($target_group === 'approved') {
                    $sql .= " AND status = ?";
                    $params[] = 'approved';
                } elseif ($target_group === 'pending') {
                    $sql .= " AND status IN (?, ?)";
                    $params[] = 'pending';
                    $params[] = 'pending_payment';
                }

                // 2. 获取所有目标邮箱
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($recipients)) {
                    $error = '没有找到符合条件的用户可以发送。';
                } else {
                    // 3. 循环发送邮件
                    foreach ($recipients as $recipient) {
                        try {
                            // === 修复代码开始 ===
                            // 为每个用户创建个性化的邮件标题和内容
                            $personalized_subject = str_replace(
                                ['{site_name}', '{owner_name}'],
                                [get_config('site_name', 'Yuan-ICP'), $recipient['owner_name']],
                                $subject
                            );

                            $personalized_content = str_replace(
                                ['{site_name}', '{owner_name}'],
                                [get_config('site_name', 'Yuan-ICP'), $recipient['owner_name']],
                                $content
                            );
                            // === 修复代码结束 ===

                            // 使用替换后的个性化内容发送邮件
                            if (send_email($recipient['owner_email'], $recipient['owner_name'], $personalized_subject, $personalized_content)) {
                                $total_sent++;
                            } else {
                                $total_failed++;
                            }
                        } catch (Exception $e) {
                            $total_failed++;
                            // 可以在日志中记录具体的失败原因
                            error_log("Mass Mailer Error: Failed to send to {$recipient['owner_email']}. Reason: " . $e->getMessage());
                        }
                    }
                    $message = "邮件发送任务完成！成功发送 {$total_sent} 封，失败 {$total_failed} 封。";
                }
            } catch (Exception $e) {
                $error = '执行邮件发送任务时发生错误：' . $e->getMessage();
            }
        }
    }
}
?>

<h2 class="mb-4">邮件群发助手</h2>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">撰写新邮件</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="mb-3">
                <label for="target_group" class="form-label"><strong>发送至</strong></label>
                <select name="target_group" id="target_group" class="form-select">
                    <option value="all">所有用户</option>
                    <option value="approved">仅已通过的用户</option>
                    <option value="pending">仅待审核/待付款的用户</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label"><strong>邮件标题</strong></label>
                <input type="text" class="form-control" id="subject" name="subject" required>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label"><strong>邮件内容</strong> (支持HTML)</label>
                <textarea class="form-control" id="content" name="content" rows="12" required></textarea>
                 <div class="form-text">
                    您可以在内容中使用 <code>{site_name}</code> 和 <code>{owner_name}</code> 作为占位符，发送时会自动替换为站点名称和用户称呼。
                </div>
            </div>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <strong>注意:</strong> 发送大量邮件可能需要较长时间，请耐心等待页面响应，期间请勿关闭或刷新页面。
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> 立即发送
                </button>
            </div>
        </form>
    </div>
</div>
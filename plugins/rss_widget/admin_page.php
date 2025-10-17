<?php
// 此文件由 admin/plugin_proxy.php 包含

// --- 后端逻辑处理 ---
$db = db();

// 处理POST请求 (强制刷新 或 清除日志)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['force_refresh'])) {
        if (file_exists(RSS_WIDGET_CACHE_FILE)) {
            unlink(RSS_WIDGET_CACHE_FILE);
        }
        rss_widget_fetch_and_cache_feed();
        $message = '缓存已强制刷新！最新的RSS文章已抓取。';
    }
    if (isset($_POST['clear_log'])) {
        if (file_exists(RSS_WIDGET_LOG_FILE)) {
            unlink(RSS_WIDGET_LOG_FILE);
        }
        $message = '日志文件已清除。';
    }
}

// 获取缓存状态
$cache_status = '缓存不存在。';
$last_updated = 'N/A';
if (file_exists(RSS_WIDGET_CACHE_FILE)) {
    $cache_data = json_decode(file_get_contents(RSS_WIDGET_CACHE_FILE), true);
    if ($cache_data && isset($cache_data['timestamp'])) {
        $last_updated = date('Y-m-d H:i:s', $cache_data['timestamp']);
        $time_since = time() - $cache_data['timestamp'];
        if ($time_since < 3600) {
            $cache_status = '<strong class="text-success">有效</strong> (将在 ' . round((3600 - $time_since) / 60) . ' 分钟后过期)';
        } else {
            $cache_status = '<strong class="text-warning">已过期</strong> (将在下次访问首页时刷新)';
        }
    }
}

// 获取已通过的域名
$approved_sites = $db->query("SELECT DISTINCT domain FROM icp_applications WHERE status = 'approved' AND domain != ''")->fetchAll(PDO::FETCH_COLUMN);

// 读取日志
$log_content = file_exists(RSS_WIDGET_LOG_FILE) ? htmlspecialchars(file_get_contents(RSS_WIDGET_LOG_FILE)) : '日志文件不存在。';

$allSettings = get_config();
?>

<!-- --- 前端页面展示 --- -->
<h2 class="mb-4">RSS文章小部件</h2>

<?php if (!empty($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

<div class="row">
    <!-- 左侧：设置与状态 -->
    <div class="col-lg-5 d-flex flex-column">
        <!-- 设置卡片 -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">基础设置</h5></div>
            <div class="card-body">
                <form id="rss-settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="rss_widget_enabled" name="rss_widget_enabled" value="1" <?php echo !empty($allSettings['rss_widget_enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="rss_widget_enabled"><strong>在首页显示小部件</strong></label>
                    </div>
                    <div class="mb-3">
                        <label for="rss_widget_title" class="form-label">小部件标题</label>
                        <input type="text" class="form-control" id="rss_widget_title" name="rss_widget_title" value="<?php echo htmlspecialchars($allSettings['rss_widget_title'] ?? '联盟站点最新文章'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="rss_widget_count" class="form-label">显示文章数量</label>
                        <input type="number" class="form-control" id="rss_widget_count" name="rss_widget_count" value="<?php echo htmlspecialchars($allSettings['rss_widget_count'] ?? '10'); ?>" min="1" max="50">
                    </div>
                </form>
            </div>
            <div class="card-footer text-end">
                 <button type="button" id="save-rss-settings" class="btn btn-primary"><i class="fas fa-save me-2"></i>保存设置</button>
            </div>
        </div>

        <!-- 状态卡片 -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0">运行状态</h5></div>
            <div class="card-body">
                <p><strong>缓存状态:</strong> <?php echo $cache_status; ?></p>
                <p><strong>上次更新:</strong> <?php echo $last_updated; ?></p>
                <form method="post" onsubmit="return confirm('确定要立即刷新所有RSS源吗？这可能需要一些时间。')">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="force_refresh" value="1">
                    <button type="submit" class="btn btn-outline-info w-100"><i class="fas fa-sync-alt me-2"></i>强制刷新缓存</button>
                </form>
            </div>
        </div>
    </div>

    <!-- 右侧：诊断信息 -->
    <div class="col-lg-7 d-flex flex-column">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">运行日志与诊断</h5>
                <form method="post" onsubmit="return confirm('确定要清除所有日志吗？')">
                     <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                     <input type="hidden" name="clear_log" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i> 清除日志</button>
                </form>
            </div>
            <div class="card-body">
                <h6><i class="fas fa-sitemap me-2"></i>目标站点 (<?php echo count($approved_sites); ?>)</h6>
                <p class="text-muted small">插件将尝试从以下已通过备案的网站自动发现RSS源。</p>
                <div>
                <?php if(empty($approved_sites)): ?>
                    <span class="badge bg-secondary">暂无</span>
                <?php else: ?>
                    <?php foreach($approved_sites as $domain): ?>
                        <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($domain); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
                <hr>
                <h6><i class="fas fa-history me-2"></i>抓取日志</h6>
                <pre style="height: 400px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; overflow-y: scroll; font-size: 0.8rem; white-space: pre-wrap; word-wrap: break-word;"><?php echo $log_content; ?></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('save-rss-settings').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 保存中...';

    const form = document.getElementById('rss-settings-form');
    const formData = new FormData(form);
    
    // 使用插件自己的API文件，实现完全独立
    fetch('../plugins/rss_widget/api.php', {
        method: 'POST',
        body: new URLSearchParams({
            'csrf_token': formData.get('csrf_token'),
            'rss_widget_enabled': form.querySelector('#rss_widget_enabled').checked ? '1' : '0',
            'rss_widget_title': form.querySelector('#rss_widget_title').value,
            'rss_widget_count': form.querySelector('#rss_widget_count').value
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (window.toast) {
                // 提示用户保存成功，并告知即将刷新
                window.toast.success(result.message + ' 页面将在2秒后刷新。');
            } else {
                alert(result.message);
            }
            // 设置一个2秒的延迟后自动刷新页面
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // 如果失败，则恢复按钮状态
            throw new Error(result.message);
        }
    })
    .catch(error => {
        if (window.toast) {
            window.toast.error(error.message || '保存时发生未知错误。');
        } else {
            alert('错误: ' + (error.message || '保存时发生未知错误。'));
        }
        // 只有在失败时才恢复按钮
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>保存设置';
    });
});
</script>
<?php
// 此文件被 admin/plugin_proxy.php 包含，因此已有权限检查
$db = db();
$stats = $db->query("SELECT COUNT(*) as total_requests, 
                    SUM(CASE WHEN type = 'svg' THEN 1 ELSE 0 END) as svg_requests,
                    SUM(CASE WHEN type = 'json' THEN 1 ELSE 0 END) as json_requests
                    FROM plugin_stats_badge_logs")->fetch();

$top_requests = $db->query("
    SELECT a.number, a.website_name, COUNT(l.id) as request_count
    FROM plugin_stats_badge_logs l
    JOIN icp_applications a ON l.application_id = a.id
    GROUP BY l.application_id
    ORDER BY request_count DESC
    LIMIT 10
")->fetchAll();

?>

<h2 class="mb-4">徽章与API统计</h2>

<div class="row">
    <div class="col-md-4">
        <div class="stat-card"><h5>总请求数</h5><h2><?php echo (int)$stats['total_requests']; ?></h2></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><h5>SVG徽章请求</h5><h2><?php echo (int)$stats['svg_requests']; ?></h2></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><h5>JSON API请求</h5><h2><?php echo (int)$stats['json_requests']; ?></h2></div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">使用说明</h5>
    </div>
    <div class="card-body">
        <p>您的用户可以在他们的网站上使用以下链接来展示动态生成的备案徽章或获取JSON数据。</p>
        <p>请将 <code>[备案号]</code> 替换为用户的实际备案号。</p>
        <hr>
        <h5>SVG 徽章链接:</h5>
        <pre class="bg-light p-2 rounded"><code><?php echo htmlspecialchars(get_config('site_url')); ?>/plugins/stats_badge/api.php?type=svg&number=[备案号]</code></pre>
        <p>示例嵌入代码:</p>
        <pre class="bg-light p-2 rounded"><code><?php echo htmlspecialchars('<img src="' . get_config('site_url') . '/plugins/stats_badge/api.php?type=svg&number=[备案号]" alt="ICP备案">'); ?></code></pre>
        
        <h6>自定义选项:</h6>
        <ul>
            <li><code>label</code> - 自定义标签文本 (默认: 站点名称)</li>
            <li><code>color</code> - 自定义颜色 (6位十六进制，如: 34D399)</li>
        </ul>
        <p>示例:</p>
        <pre class="bg-light p-2 rounded"><code><?php echo htmlspecialchars(get_config('site_url')); ?>/plugins/stats_badge/api.php?type=svg&number=[备案号]&label=我的网站&color=FF6B6B</code></pre>
        <hr>
        <h5>JSON API 链接:</h5>
        <pre class="bg-light p-2 rounded"><code><?php echo htmlspecialchars(get_config('site_url')); ?>/plugins/stats_badge/api.php?type=json&number=[备案号]</code></pre>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">请求次数排行榜 (Top 10)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>排名</th><th>备案号</th><th>网站名称</th><th>请求次数</th></tr></thead>
                <tbody>
                    <?php if(empty($top_requests)): ?>
                        <tr><td colspan="4" class="text-center">暂无请求记录</td></tr>
                    <?php else: ?>
                        <?php foreach($top_requests as $index => $req): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($req['number']); ?></td>
                            <td><?php echo htmlspecialchars($req['website_name']); ?></td>
                            <td><?php echo $req['request_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

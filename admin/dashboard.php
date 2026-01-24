<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

try {
    // 使用ApplicationManager获取统计数据
    $appManager = new ApplicationManager();
    $stats = $appManager->getStats();
    
    // 获取最近5条备案申请
    $recentResult = $appManager->getList([], 1, 5);
    $recentApps = $recentResult['applications'];
    
} catch (Exception $e) {
    handle_error('加载仪表盘数据失败: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 仪表盘</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- 主内容区 -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">仪表盘</h2>
                    <button type="button" class="btn btn-outline-primary" onclick="runSystemCheck()">
                        <i class="fas fa-stethoscope me-2"></i>系统体检
                    </button>
                </div>
                
                <!-- 安装目录警告 -->
                <?php if (file_exists(__DIR__.'/../install/')): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert" id="installWarning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>安全警告！</strong> 检测到安装目录仍然存在，这可能会带来安全风险。请立即删除 <code>install</code> 目录。
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                </div>
                <?php endif; ?>
                
                <!-- 统计卡片 -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card total">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>总申请数</h5>
                                    <h2><?php echo $stats['total']; ?></h2>
                                </div>
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card pending">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>待审核</h5>
                                    <h2><?php echo $stats['pending']; ?></h2>
                                </div>
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card approved">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>已通过</h5>
                                    <h2><?php echo $stats['approved']; ?></h2>
                                </div>
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card rejected">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>已驳回</h5>
                                    <h2><?php echo $stats['rejected']; ?></h2>
                                </div>
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 图表区域 -->
                <div class="row mt-4 chart-container">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">近7日申请量趋势</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">备案状态分布</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 最近申请 -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">最近备案申请</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>备案号</th>
                                        <th>网站名称</th>
                                        <th>域名</th>
                                        <th>申请人</th>
                                        <th>状态</th>
                                        <th>申请时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentApps as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['number']); ?></td>
                                        <td><?php echo htmlspecialchars($app['website_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['domain']); ?></td>
                                        <td><?php echo htmlspecialchars($app['owner_name']); ?></td>
                                        <td>
                                            <!-- 重要修改：直接使用 ApplicationManager 提供的 class 和 text -->
                                            <span class="badge bg-<?php echo htmlspecialchars($app['status_class']); ?>">
                                                <?php echo htmlspecialchars($app['status_text']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?></td>
                                        <td>
                                            <a href="applications.php?search=<?php echo urlencode($app['number']); ?>" class="btn btn-sm btn-primary">查看</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 加载图表数据
        async function loadChartData() {
            try {
                const response = await fetch('../api/get_dashboard_stats.php');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    
                    // 初始化趋势图
                    initTrendChart(data.trend);
                    
                    // 初始化状态分布图
                    initStatusChart(data.statusDistribution);
                } else {
                    console.error('加载图表数据失败:', result.error);
                }
            } catch (error) {
                console.error('加载图表数据失败:', error);
            }
        }
        
        // 初始化趋势图
        function initTrendChart(trendData) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                        label: '申请数量',
                        data: trendData.data,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        
        // 初始化状态分布图
        function initStatusChart(statusData) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.data,
                        backgroundColor: statusData.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }
        
        // 页面加载完成后初始化图表
        document.addEventListener('DOMContentLoaded', function() {
            loadChartData();
        });

        // 系统体检功能
        async function runSystemCheck() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>体检中...';
            btn.disabled = true;

            try {
                const response = await fetch('../api/system_check.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSystemCheckModal(result.data);
                } else {
                    alert('系统体检失败: ' + result.error);
                }
            } catch (error) {
                alert('系统体检失败: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function showSystemCheckModal(checkResults) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-stethoscope me-2"></i>系统体检报告
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${generateCheckReport(checkResults)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }

        function generateCheckReport(results) {
            let html = '<div class="system-check-report">';
            
            results.forEach(check => {
                const statusClass = check.status === 'success' ? 'text-success' : 
                                  check.status === 'warning' ? 'text-warning' : 'text-danger';
                const icon = check.status === 'success' ? 'fa-check-circle' : 
                           check.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle';
                
                html += `
                    <div class="check-item mb-3 p-3 border rounded">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas ${icon} ${statusClass} me-2"></i>
                            <strong>${check.name}</strong>
                        </div>
                        <p class="mb-1 text-muted">${check.description}</p>
                        ${check.details ? `<small class="text-muted">${check.details}</small>` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            return html;
        }
    </script>
</body>
</html>

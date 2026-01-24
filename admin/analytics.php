<?php
require_once __DIR__.'/../includes/bootstrap.php';

require_login();

// 检查分析权限
if (!has_permission('system.analytics')) {
    handle_error('您没有权限访问数据分析页面', true, 403);
}

$pageTitle = '数据分析';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Yuan-ICP 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <?php include 'includes/admin_sidebar.php'; ?>
            </nav>

            <!-- 主内容区 -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> <?php echo $pageTitle; ?></h1>
                </div>

                <!-- 统计卡片 -->
                <div class="row mb-4" id="stats-cards">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title" id="total-applications">-</h4>
                                        <p class="card-text">总申请数</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title" id="approved-applications">-</h4>
                                        <p class="card-text">已通过</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title" id="pending-applications">-</h4>
                                        <p class="card-text">待审核</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title" id="rejected-applications">-</h4>
                                        <p class="card-text">已驳回</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 图表区域 -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-pie-chart"></i> 申请状态分布</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-line-chart"></i> 申请趋势（最近7天）</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 最近活动 -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-history"></i> 最近活动</h5>
                            </div>
                            <div class="card-body">
                                <div id="recent-activities">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 页面加载完成后获取数据
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            loadRecentActivities();
        });

        // 加载仪表盘统计数据
        async function loadDashboardStats() {
            try {
                const response = await fetch('../api/get_dashboard_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // 更新统计卡片
                    document.getElementById('total-applications').textContent = data.stats.total || 0;
                    document.getElementById('approved-applications').textContent = data.stats.approved || 0;
                    document.getElementById('pending-applications').textContent = data.stats.pending || 0;
                    document.getElementById('rejected-applications').textContent = data.stats.rejected || 0;
                    
                    // 绘制状态分布饼图
                    drawStatusChart(data.stats);
                    
                    // 绘制趋势图
                    if (data.trend) {
                        drawTrendChart(data.trend);
                    }
                } else {
                    console.error('获取统计数据失败:', data.error);
                }
            } catch (error) {
                console.error('请求失败:', error);
            }
        }

        // 绘制状态分布饼图
        function drawStatusChart(stats) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['已通过', '待审核', '已驳回'],
                    datasets: [{
                        data: [stats.approved || 0, stats.pending || 0, stats.rejected || 0],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // 绘制趋势图
        function drawTrendChart(trendData) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            const labels = trendData.map(item => item.date);
            const data = trendData.map(item => item.count);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '申请数量',
                        data: data,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // 加载最近活动
        async function loadRecentActivities() {
            try {
                const response = await fetch('../api/get_applications.php?limit=10&sort=created_at&order=desc');
                const data = await response.json();
                
                if (data.success && data.applications.length > 0) {
                    const activitiesHtml = data.applications.map(app => `
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong>${app.website_name}</strong> (${app.domain})
                                <br>
                                <small class="text-muted">申请号: ${app.number}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-${getStatusClass(app.status)}">${getStatusText(app.status)}</span>
                                <br>
                                <small class="text-muted">${app.created_at_formatted}</small>
                            </div>
                        </div>
                    `).join('');
                    
                    document.getElementById('recent-activities').innerHTML = activitiesHtml;
                } else {
                    document.getElementById('recent-activities').innerHTML = '<p class="text-muted">暂无最近活动</p>';
                }
            } catch (error) {
                console.error('加载最近活动失败:', error);
                document.getElementById('recent-activities').innerHTML = '<p class="text-danger">加载失败</p>';
            }
        }

        // 获取状态对应的CSS类
        function getStatusClass(status) {
            const statusMap = {
                'pending': 'warning',
                'pending_payment': 'info',
                'approved': 'success',
                'rejected': 'danger'
            };
            return statusMap[status] || 'secondary';
        }

        // 获取状态文本
        function getStatusText(status) {
            const statusMap = {
                'pending': '待审核',
                'pending_payment': '待付款',
                'approved': '已通过',
                'rejected': '已驳回'
            };
            return statusMap[status] || '未知';
        }
    </script>
</body>
</html>
<?php
// 从传递的 $data 数组中解压变量
extract($data); 
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? ($config['site_name'] ?? 'Yuan-ICP')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($config['seo_description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($config['seo_keywords'] ?? ''); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    /* --- 通用样式 --- */
    body { display: flex; flex-direction: column; min-height: 100vh; }
    .main-content { flex: 1; }
    .query-container, .apply-form, .result-container, .selector-container {
        max-width: 800px;
        margin: 3rem auto;
        padding: 2rem;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .form-header { text-align: center; margin-bottom: 2rem; }

    /* --- 步骤指示器样式 (用于 apply.php, select_number.php) --- */
    .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2rem; }
    .step { text-align: center; flex: 1; position: relative; }
    .step-number { width: 40px; height: 40px; background: #e9ecef; color: #495057; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; border: 2px solid #dee2e6; }
    .step.active .step-number { background: #4a6cf7; color: white; border-color: #4a6cf7; }
    .step.completed .step-number { background: #28a745; color: white; border-color: #28a745; }
    .step:not(:last-child)::after { content: ''; position: absolute; top: 20px; left: 70%; width: 60%; height: 2px; background: #ddd; z-index: -1; }

    /* --- 号码选择样式 (用于 select_number.php) --- */
    .number-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; margin-top: 1.5rem; min-height: 150px; }
    .number-card { border: 2px solid #dee2e6; border-radius: 8px; padding: 1rem; text-align: center; cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden; }
    .number-card:hover { border-color: #6e8efb; transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    .number-card.selected { border-color: #4a6cf7; background-color: #f0f3ff; font-weight: bold; box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.3); }
    .number-card .number { font-size: 1.2rem; font-weight: bold; }
    .premium-badge { position: absolute; top: -1px; right: -1px; background: #ffc107; color: #333; padding: 2px 8px; font-size: 0.75rem; border-radius: 0 8px 0 8px; font-weight: bold; }
    .loading-spinner { display: none; }

    /* --- 结果页面样式 (用于 result.php) --- */
    .status-badge { font-size: 1rem; padding: 0.5rem 1rem; border-radius: 50px; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-approved { background-color: #d4edda; color: #155724; }
    .status-rejected { background-color: #f8d7da; color: #721c24; }
    .code-block { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; font-family: monospace; position: relative; white-space: pre-wrap; word-break: break-all; }
    .copy-btn { position: absolute; top: 0.5rem; right: 0.5rem; }

.hero-section {
    background: #007bff;
    color: white;
    padding: 5rem 0;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 123, 255, 0.8);
    z-index: -1;
}

.announcement-card {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s;
}

.announcement-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    font-size: 2rem;
    color: #007bff;
    margin-bottom: 0.5rem;
}
</style>
</head>
<body>
    <!-- 导航栏 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php
                $menu_items = [
                    ['name' => '首页', 'url' => 'index.php', 'active' => 'home'],
                    ['name' => '申请备案', 'url' => 'apply.php', 'active' => 'apply'],
                    ['name' => '备案查询', 'url' => 'query.php', 'active' => 'query'],
                    ['name' => '网站迁跃', 'url' => 'leap.php', 'active' => 'leap']
                ];

                foreach ($menu_items as $item):
                ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active_page ?? '') === $item['active'] ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

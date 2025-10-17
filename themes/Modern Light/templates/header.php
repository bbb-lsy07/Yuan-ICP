<?php extract($data); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? ($config['site_name'] ?? 'Yuan-ICP')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($config['seo_description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($config['seo_keywords'] ?? ''); ?>">
    <link rel="icon" type="image/png" href="/img/oiips.jpeg">
    <link rel="stylesheet" href="<?php echo get_theme_url(); ?>/style.css">
    <script defer src="https://unpkg.com/swup@4"></script>
    <script defer src="https://unpkg.com/@swup/slide-theme@2"></script>
    <style>
        :root {
            --accent-color: <?php echo get_theme_option('accent_color', '#3b82f6'); ?>;
            --accent-color-hover: <?php 
                // 简单的颜色加深逻辑
                $color = get_theme_option('accent_color', '#3b82f6');
                $color = ltrim($color, '#');
                $r = max(0, hexdec(substr($color, 0, 2)) - 20);
                $g = max(0, hexdec(substr($color, 2, 2)) - 20);
                $b = max(0, hexdec(substr($color, 4, 2)) - 20);
                echo sprintf("#%02x%02x%02x", $r, $g, $b);
            ?>;
        }
    </style>
</head>
<body data-active-page="<?php echo htmlspecialchars($active_page ?? 'home'); ?>">
    <?php if (get_theme_option('show_background_shapes', '1') === '1'): ?>
    <div class="background-shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
        <div class="shape shape4"></div>
    </div>
    <?php endif; ?>

    <header class="site-header">
        <nav class="header-nav">
            <a href="/" class="logo-link">
                <div class="logo-img"><img src="/img/oiips.jpeg" alt="Logo"></div>
                <span class="site-name"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></span>
            </a>
            <div class="nav-menu">
                <a href="index.php" class="<?php echo ($active_page ?? '') === 'home' ? 'active' : ''; ?>">首页</a>
                <a href="apply.php" class="<?php echo ($active_page ?? '') === 'apply' ? 'active' : ''; ?>">申请</a>
                <a href="query.php" class="<?php echo ($active_page ?? '') === 'query' ? 'active' : ''; ?>">查询</a>
                <a href="announcements.php" class="<?php echo ($active_page ?? '') === 'announcements' ? 'active' : ''; ?>">公告</a>
                <a href="leap.php" class="<?php echo ($active_page ?? '') === 'leap' ? 'active' : ''; ?>">迁跃</a>
            </div>
        </nav>
    </header>

    <main id="swup" class="container transition-slide">
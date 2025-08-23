<?php extract($data); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body, html { height: 100%; margin: 0; padding: 0; overflow: hidden; }
        .leap-container-wrapper { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; background-color: #000; color: #fff; text-align: center; }
        .leap-container { z-index: 10; }
        .countdown { font-size: 3rem; margin-bottom: 2rem; font-weight: bold; }
        .skip-btn { position: fixed; bottom: 2rem; right: 2rem; z-index: 100; }
        #stars { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; }
        .star { position: absolute; background-color: #fff; border-radius: 50%; animation: fly linear infinite; }
        @keyframes fly {
            0% { transform: translateX(0) translateY(0) scale(0.2); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateX(100vw) translateY(-100vh) scale(1); opacity: 0; }
        }
    </style>
</head>
<body>
    <div id="stars"></div>
    <div class="leap-container-wrapper">
        <div class="leap-container">
            <h1 class="mb-4">网站迁跃中...</h1>
            <div class="countdown" id="countdown">5</div>
            <p>正在准备随机前往一个已备案网站</p>
        </div>
        <a href="<?php echo htmlspecialchars($target_site); ?>" class="btn btn-outline-light skip-btn" id="skipBtn">
            <i class="fas fa-forward me-2"></i>立即跳转
        </a>
    </div>

    <?php if (!empty($page_scripts)): ?>
        <?php foreach ($page_scripts as $script_url): ?>
            <script src="<?php echo htmlspecialchars($script_url); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
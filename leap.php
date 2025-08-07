<?php
require_once __DIR__.'/includes/functions.php';

$db = db();

// 获取所有已通过的备案网站
$sites = $db->query("
    SELECT domain 
    FROM icp_applications 
    WHERE status = 'approved' 
    AND domain IS NOT NULL
    AND domain != ''
")->fetchAll(PDO::FETCH_COLUMN);

// 随机选择一个网站
$target_site = '';
if (!empty($sites)) {
    $random_index = array_rand($sites);
    $target_site = 'https://' . $sites[$random_index];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站迁跃 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #000;
            color: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .leap-container {
            text-align: center;
            z-index: 10;
        }
        .countdown {
            font-size: 3rem;
            margin-bottom: 2rem;
            font-weight: bold;
        }
        .skip-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 100;
        }
        #stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        .star {
            position: absolute;
            background-color: #fff;
            border-radius: 50%;
            animation: fly linear infinite;
        }
        @keyframes fly {
            0% {
                transform: translateX(0) translateY(0) scale(0.2);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateX(100vw) translateY(-100vh) scale(1);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div id="stars"></div>
    
    <div class="leap-container">
        <h1 class="mb-4">网站迁跃中...</h1>
        <div class="countdown" id="countdown">5</div>
        <p>正在准备随机前往一个已备案网站</p>
    </div>

    <a href="<?php echo !empty($target_site) ? $target_site : 'index.php'; ?>" class="btn btn-outline-light skip-btn" id="skipBtn">
        <i class="fas fa-forward me-2"></i>立即跳转
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 创建星空背景
        function createStars() {
            const starsContainer = document.getElementById('stars');
            const starCount = 100;
            
            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                
                // 随机大小
                const size = Math.random() * 3;
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                
                // 随机位置
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                
                // 随机动画时长和延迟
                const duration = 2 + Math.random() * 3;
                const delay = Math.random() * 5;
                star.style.animation = `fly ${duration}s linear ${delay}s infinite`;
                
                starsContainer.appendChild(star);
            }
        }

        // 倒计时
        function startCountdown() {
            let seconds = 5;
            const countdownEl = document.getElementById('countdown');
            const skipBtn = document.getElementById('skipBtn');
            
            const timer = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    skipBtn.click();
                }
            }, 1000);
        }

        // 初始化
        document.addEventListener('DOMContentLoaded', () => {
            createStars();
            startCountdown();
        });
    </script>
</body>
</html>

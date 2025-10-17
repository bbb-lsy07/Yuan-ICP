<?php extract($data); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo get_theme_url(); ?>/style.css">
</head>
<body class="travel-body">

    <canvas id="space-canvas"></canvas>

    <div class="container travel-container page-transition">
        <div class="header" style="margin-bottom: 30px;">
            <h1 class="holographic-text">星链穿梭</h1>
            <p class="note" style="font-size: 1.1rem; margin-top: 10px;">正在穿越虚拟网络，即将抵达一个新的站点！</p>
        </div>
        
        <?php if (empty($target_site) || $target_site === 'index.php'): ?>
            <div class="travel-info card-effect" style="max-width: 500px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--accent-color);"></i>
                </div>
                <h2 class="holographic-text" style="font-size: 2rem; margin-bottom: 15px;">迁跃失败</h2>
                <p style="font-size: 1.1rem; line-height: 1.6; margin-bottom: 20px;">当前星链网络中暂无可用站点，无法进行迁跃。快去申请一个，成为第一个坐标吧！</p>
                <div style="text-align: center;">
                    <a href="apply.php" class="glow-button primary large">
                        <i class="fas fa-rocket"></i> 立即申请
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- --- 修改开始 --- -->
            <div class="travel-info">
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-rocket" style="font-size: 3rem; color: var(--neon-color); animation: pulse 1.5s infinite;"></i>
                </div>
                <h2 class="holographic-text" style="font-size: 2rem; margin-bottom: 20px;">准备迁跃</h2>
                
                <!-- 使用 .travel-target 样式来展示目标信息，让布局更饱满 -->
                <div class="travel-target">
                    <p><strong><i class="fas fa-bullseye" style="margin-right: 8px;"></i>传送目标：</strong> <?php echo htmlspecialchars(parse_url($target_site, PHP_URL_HOST)); ?></p>
                    <p><strong><i class="fas fa-link" style="margin-right: 8px;"></i>目标地址：</strong> <a href="<?php echo htmlspecialchars($target_site); ?>" target="_blank">点击此处立即前往 &raquo;</a></p>
                </div>

                <div style="text-align: center; font-size: 1.2rem; margin-top:25px;">
                    <p>系统将在 <span id="countdown">10</span> 秒后自动迁跃！</p>
                </div>

                <!-- --- 新增内容开始 --- -->
                <div class="system-status-panel">
                    <p class="status-text">> 正在初始化迁跃程序...</p>
                    <p class="status-text">> 正在计算空间折叠坐标...</p>
                    <p class="status-text">> <span id="typing-status"></span></p>
                </div>
                <!-- --- 新增内容结束 --- -->
            </div>
            <!-- --- 修改结束 --- -->
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('loaded');
            
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                let countdown = 10;
                const interval = setInterval(() => {
                    countdown--;
                    countdownElement.innerText = countdown;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        <?php if (!empty($target_site) && $target_site !== 'index.php'): ?>
                        window.location.href = "<?php echo htmlspecialchars($target_site); ?>";
                        <?php endif; ?>
                    }
                }, 1000);
            }

            // --- 新增打字效果JS开始 ---
            const typingElement = document.getElementById('typing-status');
            if (typingElement) {
                const statuses = [
                    '航线已确认...',
                    '跃迁引擎已启动...',
                    '能量已充满...',
                    '准备进入超光速航行...'
                ];
                let statusIndex = 0;
                let charIndex = 0;

                function type() {
                    if (charIndex < statuses[statusIndex].length) {
                        typingElement.textContent += statuses[statusIndex].charAt(charIndex);
                        charIndex++;
                        setTimeout(type, 100);
                    } else {
                        setTimeout(() => {
                            charIndex = 0;
                            statusIndex = (statusIndex + 1) % statuses.length;
                            typingElement.textContent = '';
                            type();
                        }, 2000); // 切换到下一条状态前的延迟
                    }
                }
                type();
            }
            // --- 新增打字效果JS结束 ---

            const canvas = document.getElementById('space-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const stars = [];
            for (let i = 0; i < 200; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    radius: Math.random() * 1.5,
                    speed: Math.random() * 0.2 + 0.1,
                    opacity: Math.random()
                });
            }

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                stars.forEach(star => {
                    star.y -= star.speed;
                    if (star.y < 0) {
                        star.y = canvas.height;
                        star.x = Math.random() * canvas.width;
                    }
                    ctx.beginPath();
                    ctx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 255, 255, ${star.opacity})`;
                    ctx.fill();
                });
                requestAnimationFrame(animate);
            }
            animate();
            
            window.addEventListener('resize', () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            });
        });
    </script>
</body>
</html>

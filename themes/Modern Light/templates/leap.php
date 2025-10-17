<?php 
extract($data); 

// 判断是否存在一个有效的、非首页的目标迁跃网站
$is_leap_possible = !empty($target_site) && $target_site !== 'index.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- 引入 Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Fonts for Text -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&family=Martian+Mono:wght@700&family=Noto+Sans+SC:wght@400;700&display=swap" rel="stylesheet">

    <style>
        /* --- 全局与背景 --- */
        :root {
            --glow-color: rgba(59, 130, 246, 0.8); /* 统一的发光颜色 */
            --panel-bg-color: rgba(15, 20, 40, 0.75); /* 更换为深色、更不透明的背景 */
            --panel-border-color: rgba(59, 130, 246, 0.5);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }
        body {
            background: #000;
            overflow: hidden;
            font-family: 'Noto Sans SC', 'Montserrat', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            cursor: none; /* 彻底禁用鼠标指针 */
            pointer-events: none; /* 禁用所有鼠标事件 */
        }

        #app {
            width: 100%; height: 100%;
        }

        #canvas {
            position: fixed;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 1;
        }

        /* --- 开场英雄文本 --- */
        .hero {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            z-index: 10;
            transition: opacity 1.5s cubic-bezier(0.7, 0, 0.3, 1);
        }
        .hero h1, .hero h2 {
            color: white;
            text-shadow: 0 0 25px rgba(0, 0, 0, 1);
            line-height: 1.1;
            user-select: none;
            text-transform: uppercase;
        }
        .hero h1 { font-size: 7vw; font-weight: 700; }
        .hero h2 { font-size: 5vw; font-weight: 500; }

        /* --- 玻璃容器与面板 --- */
        .glass-container {
            position: absolute;
            top: 50%; left: 50%;
            width: 90%; max-width: 450px;
            height: 280px;
            transform: translate(-50%, -50%);
            z-index: 100;
            opacity: 0;
            /* 初始状态在屏幕外，为入场动画准备 */
            transform: translate(-50%, -50%) scale(0.7);
            transition: all 1.5s cubic-bezier(0.23, 1, 0.32, 1);
            border-radius: 22px; /* 给容器也加上圆角 */
        }
        .glass-container.is-visible {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        
        .glass-panel {
            position: absolute;
            width: 100%; height: 100%;
            border-radius: 20px;
            
            /* 关键修复：移除模糊，使用更不透明的深色背景 */
            background: var(--panel-bg-color);
            border: 1px solid var(--panel-border-color);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.2); /* 柔和的辉光代替硬阴影 */
            
            color: #fff;
            padding: 2rem;
            overflow: hidden;

            /* 为内容切换提供过渡 */
            transition: all 0.5s ease;
        }

        .panel-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            /* 内容切换时的动画 */
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
        }

        .panel-content.is-fading-out {
            opacity: 0;
            transform: translateY(20px);
        }

        .glass-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 0 0 15px rgba(255,255,255,0.5);
        }
        .glass-title {
            font-size: 2rem;
            font-weight: 700;
        }
        .glass-subtitle {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        /* 搜寻状态的动画 */
        .is-searching .glass-title {
            position: relative;
        }
        .is-searching .glass-title::after {
            content: '';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            text-align: left;
            animation: ellipsis 1.5s infinite;
        }
        @keyframes ellipsis {
            0% { content: ''; }
            33% { content: '.'; }
            66% { content: '..'; }
            100% { content: '...'; }
        }

        @keyframes searching-glow {
            0%, 100% { box-shadow: 0 0 20px 0px var(--glow-color); }
            50% { box-shadow: 0 0 40px 10px var(--glow-color); }
        }
        .glass-container.is-searching {
            animation: searching-glow 2s ease-in-out infinite;
        }

        /* 进度条样式 */
        .progress-number {
            font-size: 6rem;
            font-weight: 700;
            font-family: 'Martian Mono', monospace;
            background: linear-gradient(135deg, #83f36e, #60aed5);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(131, 243, 110, 0.3);
        }
        .progress-status {
            font-size: 1.2rem;
            color: #ccc;
        }
        
        /* 失败状态样式 */
        .failure-panel {
            text-align: center;
        }
    </style>
</head>
<body>

<div id="app">
  <canvas id="canvas"></canvas>
  
  <div class="hero" id="hero-section">
    <h1>时空迁跃</h1>
    <h2>RANDOM LEAP</h2>
  </div>

  <div class="glass-container" id="glass-container">
      <?php if ($is_leap_possible): ?>
          <div class="glass-panel" id="main-panel">
              <!-- 此内容将被JS动态填充 -->
          </div>
      <?php else: ?>
          <div class="glass-panel failure-panel">
              <div class="panel-content">
                  <div class="glass-icon" style="font-size: 3rem; color: #ff5555;"><i class="fas fa-exclamation-triangle"></i></div>
                  <h2 class="glass-title">迁跃失败</h2>
                  <p class="glass-subtitle">当前无可用目标站点</p>
              </div>
          </div>
      <?php endif; ?>
  </div>
</div>

<script type="module">
    // --- 1. 背景动画 ---
    import TubesCursor from "https://cdn.jsdelivr.net/npm/threejs-components@0.0.19/build/cursors/tubes1.min.js";
    const tubesApp = TubesCursor(document.getElementById('canvas'), {
        tubes: {
            colors: ["#f967fb", "#53bc28", "#695d5"],
            lights: { intensity: 200, colors: ["#83f36e", "#fe8a2e", "#ff008a", "#60aed5"] }
        }
    });
    
    // 关键：彻底禁用鼠标追踪
    requestAnimationFrame(() => {
        if (tubesApp && tubesApp.tubes && typeof tubesApp.tubes.disableTracking === 'function') {
            tubesApp.tubes.disableTracking();
        }
    });

    // --- 2. 主要动画编排 ---
    <?php if ($is_leap_possible): ?>
    const heroSection = document.getElementById('hero-section');
    const glassContainer = document.getElementById('glass-container');
    const mainPanel = document.getElementById('main-panel');
    
    // 动态创建面板内容的函数
    const createPanelContent = (content) => {
        return `
            <div class="panel-content">
                <div class="glass-icon"><i class="fas ${content.icon}"></i></div>
                <h2 class="glass-title">${content.title}</h2>
                <p class="glass-subtitle">${content.subtitle}</p>
            </div>
        `;
    };

    // --- 动画序列 ---
    const startMainSequence = () => {
        // 步骤 1: 淡出英雄文本
        heroSection.style.opacity = '0';
        setTimeout(() => {
            heroSection.style.display = 'none';
            // 步骤 2: 玻璃面板入场
            glassContainer.classList.add('is-visible');
            // 关键修复：面板入场后，立即显示“搜寻中”内容，而不是等待
            startSearching(); 
        }, 1500);
    };

    const startSearching = () => {
        const searchingDuration = 4000; // 搜寻动画总时长
        
        const searchingContent = {
            icon: 'fa-search-location', // 关键修复：移除 fa-spin
            title: '正在搜寻',
            subtitle: '为您匹配有趣的站点'
        };
        mainPanel.innerHTML = createPanelContent(searchingContent);
        mainPanel.querySelector('.panel-content').classList.add('is-searching');
        
        // 延迟一帧，确保内容已渲染，然后开始发光动画
        requestAnimationFrame(() => {
            glassContainer.classList.add('is-searching');
        });

        setTimeout(() => {
            revealWinner();
        }, searchingDuration);
    };

    const revealWinner = () => {
        glassContainer.classList.remove('is-searching');
        const currentContent = mainPanel.querySelector('.panel-content');
        currentContent.classList.add('is-fading-out');

        setTimeout(() => {
            const revealContent = {
                icon: 'fa-map-marker-alt',
                title: '定位成功',
                subtitle: '<?php echo htmlspecialchars($target_site); ?>'
            };
            mainPanel.innerHTML = createPanelContent(revealContent);
            mainPanel.style.background = 'rgba(59, 130, 246, 0.4)'; // 状态改变时，背景颜色也改变
            setTimeout(showProgress, 3000); // 展示3秒目标站点
        }, 400);
    };
    
    const showProgress = () => {
        const currentContent = mainPanel.querySelector('.panel-content');
        currentContent.classList.add('is-fading-out');

        setTimeout(() => {
            mainPanel.innerHTML = `
                <div class="panel-content">
                    <div class="progress-number" id="progressNumber">0</div>
                    <div class="progress-status" id="progressStatus">准备中...</div>
                </div>
            `;
            mainPanel.style.background = 'var(--panel-bg-color)'; // 恢复默认背景
            startProgressAnimation();
        }, 400);
    };

    const startProgressAnimation = () => {
        const progressNumber = document.getElementById('progressNumber');
        const progressStatus = document.getElementById('progressStatus');
        let progressValue = 0;
        const steps = [{number:20,status:"建立连接...",delay:600},{number:40,status:"验证目标...",delay:800},{number:60,status:"准备传送...",delay:700},{number:80,status:"能量充能...",delay:600},{number:100,status:"执行迁跃...",delay:500}];
        let currentStep = 0;
        
        function animateNumber(from, to, duration) {
            const startTime = performance.now();
            const difference = to - from;
            const update = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                progressNumber.textContent = Math.round(from + difference * easeOutCubic);
                if (progress < 1) requestAnimationFrame(update);
            };
            requestAnimationFrame(update);
        }

        function updateProgress() {
            if(currentStep >= steps.length) {
                setTimeout(() => {
                    document.body.style.transition = 'opacity 0.8s ease-in-out';
                    document.body.style.opacity = '0';
                    setTimeout(() => window.location.href = '<?php echo $target_site; ?>', 800);
                }, 500);
                return;
            }
            const step = steps[currentStep];
            animateNumber(progressValue, step.number, 300);
            progressValue = step.number;
            progressStatus.textContent = step.status;
            currentStep++;
            setTimeout(updateProgress, step.delay);
        }
        updateProgress();
    };

    window.addEventListener('load', () => {
        setTimeout(startMainSequence, 2500); // 页面加载后2.5秒自动开始
    });
    <?php else: ?>
        // 如果不可能飞跃，则在2.5秒后显示失败面板
        window.addEventListener('load', () => {
            const heroSection = document.getElementById('hero-section');
            const glassContainer = document.getElementById('glass-container');
            setTimeout(() => {
                 heroSection.style.opacity = '0';
                 setTimeout(() => {
                    heroSection.style.display = 'none';
                    glassContainer.classList.add('is-visible');
                 }, 1500);
            }, 2500);
        });
    <?php endif; ?>
</script>

</body>
</html>
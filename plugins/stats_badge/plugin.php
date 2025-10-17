<?php
/**
 * 插件信息
 */
$plugin_info = [
    'name' => 'Statistics & Badge',
    'identifier' => 'stats_badge',
    'version' => '1.0',
    'description' => '为用户提供动态SVG备案徽章和JSON API，并为管理员提供调用统计。',
    'author' => 'bbb-lsy07',
];

// 如果是通过 include 加载的，返回插件信息
if (basename($_SERVER['PHP_SELF']) === 'plugin.php' || isset($_GET['plugin_info'])) {
    return $plugin_info;
}

/**
 * 插件激活
 */
function stats_badge_activate() {
    $db = db();
    $db->exec("
        CREATE TABLE IF NOT EXISTS plugin_stats_badge_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            application_id INTEGER NOT NULL,
            type TEXT, -- 'svg' or 'json'
            ip_address TEXT,
            user_agent TEXT,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

/**
 * 插件停用
 */
function stats_badge_deactivate() {
    // 停用时不做破坏性操作
    error_log('Statistics & Badge plugin has been deactivated.');
}

/**
 * 插件卸载
 */
function stats_badge_uninstall() {
    $db = db();
    $db->exec("DROP TABLE IF EXISTS plugin_stats_badge_logs");
}

/**
 * 注册后台菜单
 */
EnhancedPluginHooks::registerAdminMenu(
    'stats_badge', // 修改ID与标识符一致，便于管理
    '徽章统计',
    'plugin_proxy.php?plugin=stats_badge',
    'fas fa-chart-bar'
    // 移除 'extensions' 参数，让菜单作为独立菜单显示
);

// --- START: 使用新的内容过滤钩子 ---
/**
 * 钩子：过滤主内容，在特定模板上添加信息 (V2 - 兼容 query 和 result 模板)
 */
PluginHooks::add('the_content', function($args) {
    // 从参数中解构出需要的数据
    $template = $args['template'];
    $data = $args['data'];
    $application = null;

    // 关键修复：根据模板不同，从不同的键获取申请信息
    if ($template === 'result' && isset($data['application'])) {
        $application = $data['application'];
    } elseif ($template === 'query' && isset($data['result'])) {
        // 在 query.php 页面，数据在 $data['result'] 中
        $application = $data['result'];
    }

    // 检查是否为 result 或 query 模板，并且申请已通过
    if ($application && $application['status'] === 'approved') {
        $site_url = rtrim(get_config('site_url', ''), '/');
        $badge_url = $site_url . '/plugins/stats_badge/api.php?type=svg&number=' . urlencode($application['number']) . '&color=3b82f6';
        $json_url = $site_url . '/plugins/stats_badge/api.php?type=json&number=' . urlencode($application['number']);

        $html_code = htmlspecialchars('<a href="' . $site_url . '/query.php?icp_number=' . urlencode($application['number']) . '" target="_blank" rel="noopener noreferrer">' .
            '<img src="' . $badge_url . '" alt="ICP备案">' .
        '</a>');

        $badge_info_html = '
        <div class="main-card" style="max-width: 700px; margin: 1.5rem auto;">
            <div style="
                background: var(--card-bg, #ffffff);
                border: 1px solid var(--card-border-color, #e2e8f0);
                border-radius: 12px;
                padding: 1.5rem;
                position: relative;
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="
                        margin: 0; 
                        font-size: 1.25rem; 
                        font-weight: 600; 
                        color: var(--text-primary, #1e293b);
                        display: flex; 
                        align-items: center; 
                        gap: 0.5rem;
                    ">
                        <i class="fas fa-certificate" style="color: var(--accent-color, #3b82f6);"></i> 备案徽章
                    </h3>
                    <button onclick="toggleBadgeDetails()" id="toggleBtn" style="
                        background: var(--accent-color, #3b82f6);
                        border: none;
                        color: white;
                        padding: 0.5rem 1rem;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 0.8rem;
                        transition: all 0.2s;
                        display: flex;
                        align-items: center;
                        gap: 0.375rem;
                    " onmouseover="this.style.background=\'var(--accent-color-hover, #2563eb)\'" onmouseout="this.style.background=\'var(--accent-color, #3b82f6)\'">
                        <i class="fas fa-chevron-down" id="toggleIcon"></i> 展开详情
                    </button>
                </div>
                
                <p style="
                    margin: 0 0 1rem 0; 
                    color: var(--text-secondary, #64748b); 
                    line-height: 1.5;
                    font-size: 0.9rem;
                ">
                    恭喜！您的备案已通过审核。点击上方按钮查看徽章详情和获取代码。
                </p>
                    
                <!-- 可折叠的详情区域 -->
                <div id="badgeDetails" style="
                    display: none;
                    overflow: hidden;
                    transition: all 0.3s ease-in-out;
                    opacity: 0;
                    max-height: 0;
                ">
                    <!-- 自定义区域 -->
                    <div style="
                        background: var(--card-bg, #f8fafc);
                        border: 1px solid var(--card-border-color, #e2e8f0);
                        border-radius: 8px;
                        padding: 1rem;
                        margin: 1rem 0;
                    ">
                        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-primary, #1e293b); font-weight: 600;">自定义徽章</h4>
                        <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="
                                    display: block;
                                    font-size: 0.85rem; 
                                    color: var(--text-secondary, #64748b); 
                                    font-weight: 500;
                                    margin-bottom: 0.5rem;
                                ">徽章颜色</label>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="color" id="badgeColor" value="#3b82f6" style="
                                        width: 40px;
                                        height: 32px;
                                        border: 1px solid var(--card-border-color, #e2e8f0);
                                        border-radius: 4px;
                                        cursor: pointer;
                                    " oninput="updateBadgePreview()" onchange="updateBadgePreview()">
                                    <span style="
                                        font-size: 0.8rem;
                                        color: var(--text-secondary, #64748b);
                                        font-family: monospace;
                                    " id="colorValue">#3b82f6</span>
                                </div>
                            </div>
                            
                            <div style="flex: 1; min-width: 200px;">
                                <label style="
                                    display: block;
                                    font-size: 0.85rem; 
                                    color: var(--text-secondary, #64748b); 
                                    font-weight: 500;
                                    margin-bottom: 0.5rem;
                                ">徽章标签</label>
                                <input type="text" id="badgeLabel" value="' . htmlspecialchars(get_config('site_name', 'Yuan-ICP')) . '" placeholder="输入徽章标签" style="
                                    width: 100%;
                                    padding: 0.5rem;
                                    border: 1px solid var(--card-border-color, #e2e8f0);
                                    border-radius: 4px;
                                    font-size: 0.85rem;
                                    background: var(--card-bg, #ffffff);
                                " oninput="updateBadgePreview()" onchange="updateBadgePreview()">
                            </div>
                        </div>
                    </div>
                        
                    <!-- 徽章预览 -->
                    <div style="
                        background: var(--card-bg, #f8fafc);
                        border: 1px solid var(--card-border-color, #e2e8f0);
                        border-radius: 8px;
                        padding: 1rem;
                        margin: 1rem 0;
                        text-align: center;
                    ">
                        <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-primary, #1e293b); font-weight: 600;">徽章预览</h4>
                        <div id="badgePreview" style="display: inline-block;">
                            <a href="' . $site_url . '/query.php?icp_number=' . urlencode($application['number']) . '" target="_blank" rel="noopener noreferrer">
                                <img src="' . $badge_url . '" alt="ICP备案" style="border-radius: 4px;">
                            </a>
                        </div>
                    </div>
                        
                    <!-- 代码生成区域 -->
                    <div id="codeSection" style="
                        display: none;
                        overflow: hidden;
                        transition: all 0.3s ease-in-out;
                        opacity: 0;
                        max-height: 0;
                    ">
                        <div style="
                            background: var(--card-bg, #f8fafc);
                            border: 1px solid var(--card-border-color, #e2e8f0);
                            border-radius: 8px;
                            padding: 1rem;
                            margin: 1rem 0;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <h4 style="margin: 0; font-size: 1rem; color: var(--text-primary, #1e293b); font-weight: 600;">HTML 代码</h4>
                                <button onclick="copyToClipboard(this)" id="copyBtn" style="
                                    background: var(--accent-color, #3b82f6);
                                    border: none;
                                    color: white;
                                    padding: 0.375rem 0.75rem;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    font-size: 0.75rem;
                                    transition: all 0.2s;
                                    display: flex;
                                    align-items: center;
                                    gap: 0.25rem;
                                " onmouseover="this.style.background=\'var(--accent-color-hover, #2563eb)\'" onmouseout="this.style.background=\'var(--accent-color, #3b82f6)\'">
                                    <i class="fas fa-copy"></i> 复制
                                </button>
                            </div>
                            <pre id="generatedCode" style="
                                margin: 0;
                                padding: 0.75rem;
                                background: #1e293b;
                                color: #e2e8f0;
                                border-radius: 4px;
                                font-size: 0.75rem;
                                line-height: 1.4;
                                overflow-x: auto;
                                white-space: pre-wrap;
                                word-break: break-all;
                                font-family: \'Monaco\', \'Menlo\', \'Ubuntu Mono\', monospace;
                            "></pre>
                        </div>
                    </div>
                        
                    <!-- 操作按钮 -->
                    <div style="
                        display: flex;
                        gap: 0.75rem;
                        flex-wrap: wrap;
                        margin-top: 1rem;
                    ">
                        <button onclick="generateCode()" id="generateBtn" style="
                            background: var(--accent-color, #3b82f6);
                            border: none;
                            color: white;
                            padding: 0.5rem 1rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 0.8rem;
                            transition: all 0.2s;
                            display: flex;
                            align-items: center;
                            gap: 0.375rem;
                        " onmouseover="this.style.background=\'var(--accent-color-hover, #2563eb)\'" onmouseout="this.style.background=\'var(--accent-color, #3b82f6)\'">
                            <i class="fas fa-code"></i> 获取代码
                        </button>
                        <a href="' . htmlspecialchars($json_url) . '" target="_blank" style="
                            background: var(--card-bg, #f1f5f9);
                            color: var(--text-secondary, #475569);
                            text-decoration: none;
                            padding: 0.5rem 0.75rem;
                            border-radius: 6px;
                            font-size: 0.8rem;
                            transition: all 0.2s;
                            display: flex;
                            align-items: center;
                            gap: 0.375rem;
                            border: 1px solid var(--card-border-color, #e2e8f0);
                        " onmouseover="this.style.background=\'var(--card-border-color, #e2e8f0)\'; this.style.color=\'var(--text-primary, #334155)\'" onmouseout="this.style.background=\'var(--card-bg, #f1f5f9)\'; this.style.color=\'var(--text-secondary, #475569)\'">
                            <i class="fas fa-code"></i> JSON API
                        </a>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        let isExpanded = false;
        const badgeNumber = "' . urlencode($application['number']) . '";
        const siteUrl = "' . $site_url . '";
        
        function toggleBadgeDetails() {
            const details = document.getElementById(\'badgeDetails\');
            const toggleBtn = document.getElementById(\'toggleBtn\');
            
            if (isExpanded) {
                // 收起动画
                details.style.maxHeight = \'0px\';
                details.style.opacity = \'0\';
                setTimeout(() => {
                    details.style.display = \'none\';
                }, 300);
                toggleBtn.innerHTML = \'<i class="fas fa-chevron-down" id="toggleIcon"></i> 展开详情\';
                isExpanded = false;
            } else {
                // 展开动画
                details.style.display = \'block\';
                // 重置样式以获取真实高度
                details.style.maxHeight = \'none\';
                details.style.opacity = \'1\';
                
                // 获取实际高度
                const height = details.offsetHeight;
                
                // 重置为收起状态
                details.style.maxHeight = \'0px\';
                details.style.opacity = \'0\';
                
                // 强制重排
                details.offsetHeight;
                
                // 触发展开动画
                details.style.maxHeight = height + \'px\';
                details.style.opacity = \'1\';
                
                toggleBtn.innerHTML = \'<i class="fas fa-chevron-up" id="toggleIcon"></i> 收起详情\';
                isExpanded = true;
            }
        }
        
        let updateTimeout;
        function updateBadgePreview() {
            // 清除之前的定时器
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }
            
            // 设置新的定时器，防抖处理
            updateTimeout = setTimeout(() => {
                const color = document.getElementById(\'badgeColor\').value;
                const label = document.getElementById(\'badgeLabel\').value;
                const colorHex = color.replace(\'#\', \'\');
                
                // 更新颜色值显示
                const colorValue = document.getElementById(\'colorValue\');
                if (colorValue) {
                    colorValue.textContent = color;
                }
                
                const newBadgeUrl = siteUrl + \'/plugins/stats_badge/api.php?type=svg&number=\' + badgeNumber + \'&color=\' + colorHex + \'&label=\' + encodeURIComponent(label);
                const previewImg = document.querySelector(\'#badgePreview img\');
                if (previewImg) {
                    previewImg.src = newBadgeUrl;
                }
                
                // 如果代码区域已显示，也更新代码
                const codeSection = document.getElementById(\'codeSection\');
                if (codeSection && codeSection.style.display !== \'none\') {
                    updateGeneratedCode();
                }
            }, 300); // 300ms 防抖
        }
        
        function updateGeneratedCode() {
            const color = document.getElementById(\'badgeColor\').value;
            const label = document.getElementById(\'badgeLabel\').value;
            const colorHex = color.replace(\'#\', \'\');
            
            const badgeUrl = siteUrl + \'/plugins/stats_badge/api.php?type=svg&number=\' + badgeNumber + \'&color=\' + colorHex + \'&label=\' + encodeURIComponent(label);
            const queryUrl = siteUrl + \'/query.php?icp_number=\' + badgeNumber;
            
            const htmlCode = \'<a href="\' + queryUrl + \'" target="_blank" rel="noopener noreferrer">\' +
                            \'<img src="\' + badgeUrl + \'" alt="ICP备案">\' +
                            \'</a>\';
            
            const generatedCode = document.getElementById(\'generatedCode\');
            if (generatedCode) {
                generatedCode.textContent = htmlCode;
            }
        }
        
        function generateCode() {
            // 生成代码
            updateGeneratedCode();
            
            // 显示代码区域（带动画）
            const codeSection = document.getElementById(\'codeSection\');
            
            // 先显示元素以获取真实高度
            codeSection.style.display = \'block\';
            codeSection.style.maxHeight = \'none\';
            codeSection.style.opacity = \'1\';
            
            // 获取实际高度
            const height = codeSection.offsetHeight;
            
            // 重置为收起状态
            codeSection.style.maxHeight = \'0px\';
            codeSection.style.opacity = \'0\';
            
            // 强制重排
            codeSection.offsetHeight;
            
            // 触发展开动画
            codeSection.style.maxHeight = height + \'px\';
            codeSection.style.opacity = \'1\';
            
            // 更新按钮状态
            const generateBtn = document.getElementById(\'generateBtn\');
            generateBtn.innerHTML = \'<i class="fas fa-check"></i> 已生成\';
            generateBtn.style.background = \'#10b981\';
            generateBtn.onmouseover = function() { this.style.background = \'#059669\'; };
            generateBtn.onmouseout = function() { this.style.background = \'#10b981\'; };
            
            // 滚动到代码区域
            setTimeout(() => {
                codeSection.scrollIntoView({ behavior: \'smooth\', block: \'nearest\' });
            }, 350);
        }
        
        function copyToClipboard(button) {
            const pre = button.parentElement.nextElementSibling;
            const text = pre.textContent;
            
            // 添加加载状态
            const originalText = button.innerHTML;
            button.innerHTML = \'<i class="fas fa-spinner fa-spin"></i> 复制中...\';
            button.style.background = \'#6b7280\';
            button.disabled = true;
            
            navigator.clipboard.writeText(text).then(function() {
                button.innerHTML = \'<i class="fas fa-check"></i> 已复制\';
                button.style.background = \'#10b981\';
                
                // 显示成功提示
                showToast(\'代码已复制到剪贴板！\', \'success\');
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.style.background = \'#3b82f6\';
                    button.disabled = false;
                }, 2000);
            }).catch(function() {
                button.innerHTML = \'<i class="fas fa-times"></i> 复制失败\';
                button.style.background = \'#ef4444\';
                showToast(\'复制失败，请手动复制代码\', \'error\');
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.style.background = \'#3b82f6\';
                    button.disabled = false;
                }, 2000);
            });
        }
        
        function showToast(message, type = \'info\') {
            // 创建提示框
            const toast = document.createElement(\'div\');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === \'success\' ? \'#10b981\' : type === \'error\' ? \'#ef4444\' : \'#3b82f6\'};
                color: white;
                padding: 0.75rem 1rem;
                border-radius: 6px;
                font-size: 0.875rem;
                font-weight: 500;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // 显示动画
            setTimeout(() => {
                toast.style.transform = \'translateX(0)\';
            }, 10);
            
            // 自动隐藏
            setTimeout(() => {
                toast.style.transform = \'translateX(100%)\';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        </script>
        ';

        // 将插件内容附加到原模板内容的末尾
        $args['content'] .= $badge_info_html;
    }
    
    // 必须返回修改后的 $args 数组
    return $args;
});
// --- END: 使用新的内容过滤钩子 ---

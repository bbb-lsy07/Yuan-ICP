<?php
/**
 * 插件信息
 */
$plugin_info = [
    'name'          => 'RSS文章小部件',
    'identifier'    => 'rss_widget',
    'version'       => '3.0.0', // 版本升级
    'description'   => '自动发现并聚合所有已通过备案网站的RSS文章，在首页小部件中展示。内置强大的诊断工具和智能RSS发现算法。',
    'author'        => 'bbb-lsy07',
];

if (isset($_GET['plugin_info'])) {
    return $plugin_info;
}

// 定义缓存文件和日志文件路径
define('RSS_WIDGET_CACHE_FILE', __DIR__ . '/../../data/rss_widget_cache.json');
define('RSS_WIDGET_LOG_FILE', __DIR__ . '/../../data/rss_widget.log');

if (!function_exists('rss_widget_log')) {
    /**
     * 插件专用的日志记录函数
     * @param string $message 日志信息
     */
    function rss_widget_log($message) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        // 使用追加模式写入日志，如果文件不存在则自动创建
        file_put_contents(RSS_WIDGET_LOG_FILE, $log_entry, FILE_APPEND);
    }
}

if (!function_exists('rss_widget_activate')) {
    function rss_widget_activate() {
        $db = db();
        $defaults = [
            'rss_widget_enabled' => '1',
            'rss_widget_title'   => '联盟站点最新文章',
            'rss_widget_count'   => '10',
        ];
        $stmt = $db->prepare("INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES (?, ?)");
        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        // 激活时检查data目录是否可写
        if (!is_writable(__DIR__ . '/../../data/')) {
            rss_widget_log("警告: data目录不可写，缓存和日志功能将无法使用。");
        }
    }
}

if (!function_exists('rss_widget_uninstall')) {
    function rss_widget_uninstall() {
        $db = db();
        $db->exec("DELETE FROM system_config WHERE config_key LIKE 'rss_widget_%'");
        if (file_exists(RSS_WIDGET_CACHE_FILE)) unlink(RSS_WIDGET_CACHE_FILE);
        if (file_exists(RSS_WIDGET_LOG_FILE)) unlink(RSS_WIDGET_LOG_FILE);
    }
}

EnhancedPluginHooks::registerAdminMenu(
    'rss_widget_admin_page', 'RSS文章小部件', 'plugin_proxy.php?plugin=rss_widget', 'fas fa-rss', null, 50
);

if (!function_exists('find_rss_feed_from_url')) {
    /**
     * 查找RSS Feed (V3 - 智能版)
     * 1. 检查HTML <link> 标签
     * 2. 如果失败，尝试常见的路径如 /rss.xml, /atom.xml, /feed
     */
    function find_rss_feed_from_url($url) {
    if (!preg_match("~^https?://~i", $url)) {
        $url = "http://" . $url;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Yuan-ICP-RSS-Bot/3.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code != 200 || empty($html)) {
        rss_widget_log("发现(1/2)失败: 访问 {$url} 失败。状态码: {$http_code}, 错误: {$error}");
    } else {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        libxml_clear_errors();
        $links = $doc->getElementsByTagName('link');
        foreach ($links as $link) {
            $type = $link->getAttribute('type');
            if (in_array($type, ['application/rss+xml', 'application/atom+xml'])) {
                $feed_url = $link->getAttribute('href');
                if (!preg_match("~^https?://~i", $feed_url)) {
                    $parts = parse_url($final_url);
                    $base_url = $parts['scheme'] . '://' . $parts['host'];
                    $feed_url = (substr($feed_url, 0, 1) === '/') ? $base_url . $feed_url : $base_url . rtrim(dirname($parts['path']), '/') . '/' . $feed_url;
                }
                rss_widget_log("发现(1/2)成功: 在 {$url} HTML中找到RSS地址: {$feed_url}");
                return $feed_url;
            }
        }
        rss_widget_log("发现(1/2)失败: 在 {$url} HTML中未找到<link>标签。");
    }

    // 阶段2：如果上面没找到，尝试常见路径
    rss_widget_log("发现(2/2)开始: 尝试 {$url} 的常见RSS路径...");
    $common_paths = ['/rss.xml', '/atom.xml', '/feed', '/rss', '/feed.xml'];
    $parts = parse_url($final_url);
    $base_url = $parts['scheme'] . '://' . $parts['host'];

    foreach ($common_paths as $path) {
        $test_url = $base_url . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $test_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true, // 只获取响应头，速度更快
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'Yuan-ICP-RSS-Bot/3.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            rss_widget_log("发现(2/2)成功: 成功访问通用路径 {$test_url}");
            return $test_url;
        } else {
            rss_widget_log("发现(2/2)尝试: 路径 {$test_url} 返回状态码 {$http_code}");
        }
    }
    
    rss_widget_log("发现(2/2)失败: 尝试所有常见路径后仍未找到 {$url} 的RSS地址。");
    return null;
    }
}

if (!function_exists('rss_widget_fetch_and_cache_feed')) {
    /**
     * 抓取并缓存RSS源的核心函数（已升级为动态多源）
     */
    function rss_widget_fetch_and_cache_feed() {
    rss_widget_log("开始执行RSS抓取任务...");
    $db = db();
    $all_items = [];
    
    // 1. 获取所有已通过的网站域名
    $stmt = $db->query("SELECT DISTINCT domain FROM icp_applications WHERE status = 'approved' AND domain != ''");
    $domains = $stmt->fetchAll(PDO::FETCH_COLUMN);
    rss_widget_log("找到 " . count($domains) . " 个已通过的域名进行处理。");

    // 2. 遍历域名，发现并抓取RSS
    foreach ($domains as $domain) {
        $feed_url = find_rss_feed_from_url($domain);
        if (!$feed_url) continue;

        // 使用cURL获取feed内容
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $feed_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Yuan-ICP-RSS-Bot/3.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $xml_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code != 200 || empty($xml_content)) {
            rss_widget_log("抓取失败: 无法获取 {$feed_url} 的内容。HTTP状态码: {$http_code}, cURL错误: {$error}");
            continue;
        }

        try {
            // 错误抑制符，防止无效XML产生警告
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xml_content);
            libxml_clear_errors();

            $entries = $xml->channel->item ?? $xml->entry;
            if (!$entries) {
                 rss_widget_log("解析警告: 在 {$feed_url} 中找不到 <item> 或 <entry> 标签。");
                 continue;
            }
            
            foreach ($entries as $entry) {
                $link_node = $entry->link;
                $link = (string)($link_node['href'] ?? $link_node);
                if(empty($link)) $link = (string)$link_node; // 兼容更多情况

                $all_items[] = [
                    'title'       => (string)$entry->title,
                    'link'        => $link,
                    'description' => strip_tags((string)($entry->description ?? $entry->summary ?? $entry->content)),
                    'pubDate'     => strtotime((string)($entry->pubDate ?? $entry->published ?? $entry->updated)),
                ];
            }
             rss_widget_log("抓取成功: 从 {$feed_url} 获取了 " . count($entries) . " 篇文章。");
        } catch (Exception $e) {
            rss_widget_log("解析失败: 解析 {$feed_url} 时出错: " . $e->getMessage());
            continue;
        }
    }

    usort($all_items, function($a, $b) {
        return ($b['pubDate'] ?? 0) - ($a['pubDate'] ?? 0);
    });

    $count = (int)get_config('rss_widget_count', 10);
    $final_items = array_slice($all_items, 0, $count);
    
    $cache_data = [ 'timestamp' => time(), 'items' => $final_items ];
    if(is_writable(dirname(RSS_WIDGET_CACHE_FILE))) {
        file_put_contents(RSS_WIDGET_CACHE_FILE, json_encode($cache_data, JSON_PRETTY_PRINT));
        rss_widget_log("任务完成: 成功抓取并缓存了 " . count($final_items) . " 篇文章。");
    } else {
        rss_widget_log("严重错误: 缓存目录 " . dirname(RSS_WIDGET_CACHE_FILE) . " 不可写！请检查权限。");
    }
    
    return $final_items;
    }
}

if (!function_exists('rss_widget_display_on_homepage')) {
    /**
     * 在首页显示RSS小部件的函数 (优化缓存逻辑)
     */
    function rss_widget_display_on_homepage($args) {
    if ($args['template'] !== 'home' || get_config('rss_widget_enabled', '0') !== '1') {
        return $args;
    }

    $items = [];
    $cache_expired = true;
    
    if (file_exists(RSS_WIDGET_CACHE_FILE)) {
        $cache = json_decode(file_get_contents(RSS_WIDGET_CACHE_FILE), true);
        if ($cache && isset($cache['timestamp']) && (time() - $cache['timestamp'] < 3600)) { // 1小时缓存
            $items = $cache['items'];
            $cache_expired = false;
        }
    }
    
    if ($cache_expired) {
        $items = rss_widget_fetch_and_cache_feed();
    }
    
    if (!empty($items)) {
        // === 修复代码开始 ===
        // 无论是否从缓存加载，都重新获取并应用最新的"显示数量"设置
        $count = (int)get_config('rss_widget_count', 10);
        $items_to_display = array_slice($items, 0, $count);
        // === 修复代码结束 ===

        $widget_title = htmlspecialchars(get_config('rss_widget_title', '联盟站点最新文章'));
        ob_start();
        ?>
        <section class="main-card animate-fade-in-up" style="margin-top: 2rem;">
            <h2 class="section-title"><i class="fas fa-rss"></i> <?php echo $widget_title; ?></h2>
            <div class="rss-articles-container">
                <?php foreach($items_to_display as $index => $item): // <-- 修改点：使用新截取的数组 ?>
                <div class="rss-article-item">
                    <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer" class="rss-article-link">
                        <div class="rss-article-content">
                            <h3 class="rss-article-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="rss-article-excerpt"><?php echo mb_substr(htmlspecialchars($item['description']), 0, 120); ?><?php echo mb_strlen($item['description']) > 120 ? '...' : ''; ?></p>
                            <div class="rss-article-meta">
                                <span class="rss-article-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('Y-m-d', $item['pubDate']); ?>
                                </span>
                                <span class="rss-article-source">
                                    <i class="fas fa-external-link-alt"></i>
                                    阅读原文
                                </span>
                            </div>
                        </div>
                        <div class="rss-article-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <style>
        .rss-articles-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .rss-article-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .rss-article-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #3b82f6;
        }
        
        .rss-article-link {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        
        .rss-article-link:hover {
            text-decoration: none;
            color: inherit;
        }
        
        .rss-article-content {
            flex: 1;
            min-width: 0;
        }
        
        .rss-article-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 0.5rem 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .rss-article-excerpt {
            color: #718096;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0 0 0.75rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .rss-article-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8rem;
            color: #a0aec0;
        }
        
        .rss-article-date,
        .rss-article-source {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .rss-article-source {
            color: #3b82f6;
            font-weight: 500;
        }
        
        .rss-article-arrow {
            color: #cbd5e0;
            font-size: 1.2rem;
            margin-left: 1rem;
            transition: all 0.3s ease;
        }
        
        .rss-article-item:hover .rss-article-arrow {
            color: #3b82f6;
            transform: translateX(4px);
        }
        
        @media (max-width: 768px) {
            .rss-article-link {
                padding: 1rem;
            }
            
            .rss-article-title {
                font-size: 1rem;
            }
            
            .rss-article-excerpt {
                font-size: 0.85rem;
            }
            
            .rss-article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        </style>
        <?php
        $rss_html = ob_get_clean();
        $args['content'] .= $rss_html;
    }
    
    return $args;
    }
}

PluginHooks::add('the_content', 'rss_widget_display_on_homepage');
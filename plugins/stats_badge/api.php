<?php
require_once __DIR__.'/../../includes/bootstrap.php';

$type = $_GET['type'] ?? 'svg';
$number = $_GET['number'] ?? '';

if (empty($number)) {
    header("HTTP/1.1 400 Bad Request");
    die('{"error":"Missing parameter: number"}');
}

try {
    $db = db();
    $stmt = $db->prepare("SELECT id, website_name, status FROM icp_applications WHERE number = ? AND status = 'approved' LIMIT 1");
    $stmt->execute([$number]);
    $application = $stmt->fetch();

    if (!$application) {
        header("HTTP/1.1 404 Not Found");
        die('{"error":"Application not found or not approved"}');
    }

    // Log the request
    $stmt_log = $db->prepare("INSERT INTO plugin_stats_badge_logs (application_id, type, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt_log->execute([$application['id'], $type, get_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '']);

    if ($type === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'number' => $number,
            'site_name' => $application['website_name'],
            'status' => 'approved'
        ]);
        exit;
    }

    // Default to SVG
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-cache, no-store, must-revalidate'); // 动态图片不应被浏览器强缓存

    // 获取自定义参数
    $label = htmlspecialchars($_GET['label'] ?? get_config('site_name', 'Yuan-ICP'));
    $status_text = '已通过'; // 来自数据库
    $color = preg_match('/^[a-fA-F0-9]{3,6}$/', $_GET['color'] ?? '') ? '#' . $_GET['color'] : '#3b82f6'; // 默认蓝色
    $style = $_GET['style'] ?? 'flat'; // 'flat', 'plastic'

    $label_width = (mb_strlen($label) * 8) + 10;
    $status_width = (mb_strlen($number) * 7) + 10;
    $total_width = $label_width + $status_width;

    $label_x = $label_width / 2 * 10;
    $status_x = ($label_width + $status_width / 2) * 10;
    $label_text_length = $label_width * 9;
    $status_text_length = $status_width * 9;

    echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$total_width}" height="20" role="img" aria-label="{$label}: {$number}">
    <title>{$label}: {$number}</title>
    <linearGradient id="s" x2="0" y2="100%">
        <stop offset="0" stop-color="#fff" stop-opacity=".7"/>
        <stop offset=".1" stop-color="#aaa" stop-opacity=".1"/>
        <stop offset=".9" stop-color="#000" stop-opacity=".3"/>
        <stop offset="1" stop-color="#000" stop-opacity=".5"/>
    </linearGradient>
    <clipPath id="r">
        <rect width="{$total_width}" height="20" rx="3" fill="#fff"/>
    </clipPath>
    <g clip-path="url(#r)">
        <rect width="{$label_width}" height="20" fill="#555"/>
        <rect x="{$label_width}" width="{$status_width}" height="20" fill="{$color}"/>
        <rect width="{$total_width}" height="20" fill="url(#s)"/>
    </g>
    <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" text-rendering="geometricPrecision" font-size="110">
        <text aria-hidden="true" x="{$label_x}" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$label_text_length}">{$label}</text>
        <text x="{$label_x}" y="140" transform="scale(.1)" fill="#fff" textLength="{$label_text_length}">{$label}</text>
        <text aria-hidden="true" x="{$status_x}" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$status_text_length}">{$number}</text>
        <text x="{$status_x}" y="140" transform="scale(.1)" fill="#fff" textLength="{$status_text_length}">{$number}</text>
    </g>
</svg>
SVG;

} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    error_log("Badge API Error: " . $e->getMessage());
    die('{"error":"Internal Server Error"}');
}

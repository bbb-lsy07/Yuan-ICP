<?php
// api/get_numbers.php
session_start();
require_once __DIR__.'/../includes/functions.php';

// 获取系统配置，判断是自动生成还是从数据库读取
$db = db();
$stmt_config = $db->query("SELECT config_key, config_value FROM system_config WHERE config_key = 'number_auto_generate'");
$config = $stmt_config->fetch(PDO::FETCH_KEY_PAIR);
$is_auto_generate_enabled = (bool)($config['number_auto_generate'] ?? false);

$numbers = [];

if ($is_auto_generate_enabled) {
    // 模式一：自动生成
    $generated_numbers = [];
    $attempts = 0; // 尝试次数，防止死循环
    while (count($numbers) < 12 && $attempts < 100) {
        $unique_num = generate_unique_icp_number();
        if ($unique_num && !in_array($unique_num, $generated_numbers)) {
            $numbers[] = [
                'number' => $unique_num,
                'is_premium' => is_premium_number($unique_num) // 判断是否为靓号
            ];
            $generated_numbers[] = $unique_num;
        }
        $attempts++;
    }
} else {
    // 模式二：从数据库号码池获取
    $db_numbers = $db->query("
        SELECT number, is_premium FROM selectable_numbers 
        WHERE status = 'available'
        ORDER BY RANDOM()
        LIMIT 12
    ")->fetchAll();
    // 确保is_premium字段是布尔值
    foreach ($db_numbers as $num) {
        $numbers[] = [
            'number' => $num['number'],
            'is_premium' => (bool)$num['is_premium']
        ];
    }
}

// 以JSON格式返回数据
header('Content-Type: application/json');
echo json_encode(['success' => true, 'numbers' => $numbers]);

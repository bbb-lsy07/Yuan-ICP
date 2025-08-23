<?php
// api/get_numbers.php
session_start();
require_once __DIR__.'/../includes/functions.php';

/**
 * 检查号码是否为靓号的简单规则
 * @param string $number
 * @return bool
 */
function is_premium_number($number) {
    // 规则1: 包含三个或以上连续相同的数字或字母 (例如 888, AAA)
    if (preg_match('/(.)\\1\\1/', $number)) {
        return true;
    }
    // 规则2: 包含常见的靓号组合
    $lucky_sequences = ['666', '888', '999', '520', '1314', 'ABC', 'XYZ'];
    foreach ($lucky_sequences as $seq) {
        if (stripos($number, $seq) !== false) {
            return true;
        }
    }
    // 规则3: AABB 或 ABAB 形式
    if (preg_match('/(.)\\1(.)\\2/', $number) || preg_match('/(.)(.)\\1\\2/', $number)) {
        return true;
    }
    return false;
}

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

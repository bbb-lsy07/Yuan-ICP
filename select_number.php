<?php
session_start();
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/theme_manager.php';

$db = db();
$error = '';

// 检查会话中是否存在申请数据，如果没有则返回第一步
if (!isset($_SESSION['application_data'])) {
    header("Location: apply.php");
    exit;
}

// 获取系统配置
$stmt_config = $db->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('number_auto_generate', 'sponsor_message')");
$config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
$is_auto_generate_enabled = (bool)($config['number_auto_generate'] ?? false);
$sponsor_message = $config['sponsor_message'] ?? '选择靓号或自定义号码是对我们服务的肯定，如果您愿意，可以对我们进行赞赏以支持我们更好地发展！';

// 处理最终的表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_number = trim($_POST['number'] ?? '');
    
    if (empty($selected_number)) {
        $error = '请选择一个备案号。';
    } else {
        $app_data = $_SESSION['application_data'];
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO icp_applications (number, website_name, domain, description, owner_name, owner_email, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $selected_number, $app_data['site_name'], $app_data['domain'],
                $app_data['description'], $app_data['contact_name'], $app_data['contact_email']
            ]);
            $application_id = $db->lastInsertId();
            
            if (!$is_auto_generate_enabled) {
                $stmt_update = $db->prepare("UPDATE selectable_numbers SET status = 'used', used_by = ?, used_at = CURRENT_TIMESTAMP WHERE number = ?");
                $stmt_update->execute([$application_id, $selected_number]);
            }
            
            $db->commit();
            unset($_SESSION['application_data']);
            header("Location: result.php?application_id=" . $application_id);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = '处理您的请求时发生错误，请重试。';
        }
    }
}

// 准备传递给模板的数据
$data = [
    'config' => $config,
    'error' => $error,
    'sponsor_message' => $sponsor_message,
    'page_title' => '选择备案号 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'select_number',
    'inline_script' => "
    document.addEventListener('DOMContentLoaded', function() {
        const numberGrid = document.querySelector('.number-grid');
        const numberCards = document.querySelectorAll('.number-card');
        const loadingSpinner = document.querySelector('.loading-spinner');

        numberCards.forEach(card => {
            card.addEventListener('click', function() {
                numberCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                document.querySelector('#number').value = this.querySelector('.number').textContent;
            });
        });

        document.querySelector('#number_form').addEventListener('submit', function(event) {
            event.preventDefault();
            if (!document.querySelector('.number-card.selected')) {
                alert('请选择一个备案号。');
                return;
            }
            this.submit();
        });
    });
    "
];

// 准备传递给模板的数据
$data = [
    'config' => $config,
    'error' => $error,
    'sponsor_message' => $sponsor_message,
    'page_title' => '选择备案号 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'select_number',
    'inline_script' => "
    document.addEventListener('DOMContentLoaded', function() {
        const numberGrid = document.querySelector('.number-grid');
        const numberCards = document.querySelectorAll('.number-card');
        const loadingSpinner = document.querySelector('.loading-spinner');

        numberCards.forEach(card => {
            card.addEventListener('click', function() {
                numberCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                document.querySelector('#number').value = this.querySelector('.number').textContent;
            });
        });

        document.querySelector('#number_form').addEventListener('submit', function(event) {
            event.preventDefault();
            if (!document.querySelector('.number-card.selected')) {
                alert('请选择一个备案号。');
                return;
            }
            this.submit();
        });
    });
    "
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('select_number', $data);
ThemeManager::render('footer', $data);

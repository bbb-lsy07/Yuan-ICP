<?php
require_once __DIR__.'/../includes/bootstrap.php';

check_admin_auth();

$themeName = $_GET['theme'] ?? '';
$message = '';
$error = '';

if (empty($themeName)) {
    redirect('themes.php');
}

$themeInfo = ThemeManager::getAvailableThemes()[$themeName] ?? null;
if (!$themeInfo) {
    redirect('themes.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的请求，请刷新重试。';
    } else {
        $options = $_POST['options'] ?? [];
        $definedOptions = ThemeManager::getThemeOptions($themeName);

        try {
            foreach ($definedOptions as $key => $option) {
                // 对 checkbox 特殊处理，未提交则视为 '0'
                if ($option['type'] === 'checkbox') {
                    $value = isset($options[$key]) ? '1' : '0';
                } else {
                    $value = $options[$key] ?? '';
                }
                ThemeManager::setThemeOption($key, $value, $themeName);
            }
            $message = '主题选项已成功保存！';
        } catch (Exception $e) {
            $error = '保存失败：' . $e->getMessage();
        }
    }
}

$currentOptions = ThemeManager::getAllThemeOptions($themeName);
$definedOptions = ThemeManager::getThemeOptions($themeName);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑主题选项: <?php echo htmlspecialchars($themeInfo['name']); ?> - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">主题管理</h1>
                </div>
                
                <!-- 子导航 -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="themes.php">
                            <i class="fas fa-palette me-1"></i>主题列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="debug_themes.php">
                            <i class="fas fa-bug me-1"></i>主题调试
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="theme_options.php?theme=<?php echo urlencode($themeName); ?>">
                            <i class="fas fa-cog me-1"></i>主题选项
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>编辑主题选项: <strong><?php echo htmlspecialchars($themeInfo['name']); ?></strong></h2>
                    <a href="themes.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> 返回主题列表</a>
                </div>
                
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <form id="theme-options-form" method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="row">
                        <!-- 选项表单 -->
                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">自定义选项</h5>
                                </div>
                                <div class="card-body" style="max-height: 65vh; overflow-y: auto;">
                                    <?php if (empty($definedOptions)): ?>
                                        <p class="text-muted">该主题没有可配置的选项。</p>
                                    <?php else: ?>
                                        <?php foreach ($definedOptions as $key => $option): 
                                            $currentValue = $currentOptions[$key] ?? ($option['default'] ?? '');
                                        ?>
                                            <div class="mb-4">
                                                <label for="option_<?php echo $key; ?>" class="form-label fw-bold"><?php echo htmlspecialchars($option['label']); ?></label>
                                                <?php switch($option['type']):
                                                    case 'text':
                                                    case 'url':
                                                    case 'email': ?>
                                                        <input type="<?php echo $option['type']; ?>" class="form-control" id="option_<?php echo $key; ?>" name="options[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($currentValue); ?>">
                                                        <?php break; ?>
                                                    <?php case 'textarea': ?>
                                                        <textarea class="form-control" id="option_<?php echo $key; ?>" name="options[<?php echo $key; ?>]" rows="4"><?php echo htmlspecialchars($currentValue); ?></textarea>
                                                        <?php break; ?>
                                                    <?php case 'checkbox': ?>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="option_<?php echo $key; ?>" name="options[<?php echo $key; ?>]" value="1" <?php echo ($currentValue == '1') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="option_<?php echo $key; ?>"><?php echo htmlspecialchars($option['label']); ?></label>
                                                        </div>
                                                        <?php break; ?>
                                                    <?php case 'select': ?>
                                                        <select class="form-select" id="option_<?php echo $key; ?>" name="options[<?php echo $key; ?>]">
                                                            <?php foreach($option['choices'] as $value => $label): ?>
                                                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($value == $currentValue) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <?php break; ?>
                                                    <?php case 'color': ?>
                                                        <input type="color" class="form-control form-control-color" id="option_<?php echo $key; ?>" name="options[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($currentValue); ?>">
                                                        <?php break; ?>
                                                <?php endswitch; ?>
                                                <?php if (!empty($option['description'])): ?>
                                                    <div class="form-text mt-1"><?php echo htmlspecialchars($option['description']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存更改</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 实时预览 -->
                        <div class="col-lg-7">
                             <div class="card sticky-top" style="top: 20px;">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">实时预览</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshPreview"><i class="fas fa-sync-alt"></i> 刷新</button>
                                </div>
                                <div class="card-body p-0">
                                    <iframe id="themePreview" src="../index.php?preview_theme=<?php echo urlencode($themeName); ?>" width="100%" height="600" frameborder="0" style="border-radius: 0 0 var(--radius-lg) var(--radius-lg);"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('theme-options-form');
            const previewIframe = document.getElementById('themePreview');
            const themeName = '<?php echo urlencode($themeName); ?>';

            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            const updatePreview = debounce(function() {
                const formData = new FormData(form);
                const options = {};
                for (let [key, value] of formData.entries()) {
                    if (key.startsWith('options[')) {
                        let newKey = key.replace('options[', '').replace(']', '');
                        options[newKey] = value;
                    }
                }
                
                // 处理未选中的checkbox
                form.querySelectorAll('input[type=checkbox]').forEach(cb => {
                    const key = cb.name.replace('options[', '').replace(']', '');
                    if (!cb.checked) {
                        options[key] = '0';
                    }
                });

                fetch('../api/preview_theme.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        theme: '<?php echo $themeName; ?>',
                        options: options
                    })
                })
                .then(response => response.json())
                .then(() => {
                    previewIframe.src = `../index.php?preview_theme=${themeName}&t=${Date.now()}`;
                })
                .catch(error => console.error('Preview update failed:', error));
            }, 500);

            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('input', updatePreview);
            });
            
            document.getElementById('refreshPreview').addEventListener('click', () => {
                 previewIframe.src = `../index.php?preview_theme=${themeName}&t=${Date.now()}`;
            });
        });
    </script>
</body>
</html>

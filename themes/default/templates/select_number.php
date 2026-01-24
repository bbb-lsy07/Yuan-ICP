<?php
// 解压从控制器传递过来的变量
extract($data);
?>
<div class="container my-5">
    <div class="selector-container">
        <!-- 步骤指示器 -->
        <div class="step-indicator">
            <div class="step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-title">填写信息</div></div>
            <div class="step active"><div class="step-number">2</div><div class="step-title">选择号码</div></div>
            <div class="step"><div class="step-number">3</div><div class="step-title">完成申请</div></div>
        </div>
        
        <div class="form-header">
            <h2>选择备案号</h2>
            <p class="text-muted">为您的网站选择一个心仪的备案号</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="random-tab" data-bs-toggle="tab" data-bs-target="#random" type="button" role="tab">随机选号</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom" type="button" role="tab">自定义靓号</button></li>
        </ul>
        <form method="post" id="number-form">
            <input type="hidden" name="number" id="selected_number">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="random" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <h5 class="mb-0">请选择一个号码</h5>
                        <button type="button" class="btn btn-outline-primary" id="refresh-numbers"><span class="spinner-border spinner-border-sm loading-spinner" role="status" aria-hidden="true"></span><i class="fas fa-sync-alt me-1"></i>换一批</button>
                    </div>
                    <div class="number-grid" id="number-grid-container"></div>
                </div>
                <div class="tab-pane fade" id="custom" role="tabpanel">
                    <div class="mt-3 p-3 border rounded">
                        <h5 class="mb-3">输入您想要的号码</h5>
                        <div class="input-group">
                            <input type="text" class="form-control" id="custom_number_input" placeholder="例如：Yuan-ICP-888888">
                            <button class="btn btn-primary" type="button" id="select-custom-number">就选这个!</button>
                        </div>
                        <div class="form-text mt-2">自定义号码需要赞赏支持。选定后，您的号码将被标记为 <strong id="custom-selection-display" class="text-success d-none"></strong></div>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2 mt-4">
                <button type="submit" id="submit-btn" class="btn btn-primary btn-lg" disabled>确认选择并完成申请</button>
                <a href="apply.php" class="btn btn-outline-secondary">返回修改信息</a>
            </div>
        </form>
    </div>
</div>

<!-- 赞赏提示 Modal -->
<div class="modal fade" id="sponsorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-gem text-warning me-2"></i>靓号选择确认</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>您选择了一个靓号/自定义号码，非常感谢您的青睐！</p>
                <div class="alert alert-info">
                    <?php 
                        // 优先使用传进来的消息，其次使用系统配置里的消息，最后使用默认文本
                        $msg = $sponsor_message ?? $config['sponsor_message'] ?? '感谢您的支持！请完成赞助以锁定该靓号。';
                        echo nl2br(htmlspecialchars($msg)); 
                    ?>
                </div>
                <p>此操作遵循君子协议，点击下方按钮即表示您已知晓并愿意支持我们。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">再想想</button>
                <button type="button" class="btn btn-primary" id="confirm-sponsor-btn">我确认并继续</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const numberForm = document.getElementById('number-form');
    const hiddenInput = document.getElementById('selected_number');
    const submitBtn = document.getElementById('submit-btn');
    const numberGridContainer = document.getElementById('number-grid-container');
    const refreshBtn = document.getElementById('refresh-numbers');
    const customInput = document.getElementById('custom_number_input');
    const selectCustomBtn = document.getElementById('select-custom-number');
    const customSelectionDisplay = document.getElementById('custom-selection-display');
    const sponsorModalEl = document.getElementById('sponsorModal');
    const sponsorModal = new bootstrap.Modal(sponsorModalEl);
    const confirmSponsorBtn = document.getElementById('confirm-sponsor-btn');
    let selectedNumberIsPremium = false;

    // 获取号码列表
    async function fetchAndRenderNumbers() {
        refreshBtn.disabled = true;
        refreshBtn.querySelector('.loading-spinner').style.display = 'inline-block';
        refreshBtn.querySelector('.fa-sync-alt').style.display = 'none';
        numberGridContainer.innerHTML = '<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';

        try {
            const response = await fetch('api/get_numbers.php');
            const data = await response.json();
            numberGridContainer.innerHTML = '';
            
            if (data.success && data.numbers.length > 0) {
                data.numbers.forEach(num => {
                    const card = document.createElement('div');
                    card.className = 'number-card';
                    card.dataset.number = num.number;
                    card.dataset.premium = num.is_premium ? '1' : '0';
                    let premiumBadge = num.is_premium ? '<div class="premium-badge"><i class="fas fa-gem"></i> 靓号</div>' : '';
                    card.innerHTML = `${premiumBadge}<div class="number">${num.number}</div>`;
                    card.addEventListener('click', handleNumberSelection);
                    numberGridContainer.appendChild(card);
                });
            } else {
                numberGridContainer.innerHTML = '<div class="col-12 text-center p-3 alert alert-warning">暂无可用号码。</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            numberGridContainer.innerHTML = '<div class="col-12 text-center p-3 alert alert-danger">加载失败。</div>';
        } finally {
            refreshBtn.disabled = false;
            refreshBtn.querySelector('.loading-spinner').style.display = 'none';
            refreshBtn.querySelector('.fa-sync-alt').style.display = 'inline-block';
        }
    }

    // 处理号码点击
    function handleNumberSelection(event) {
        const card = event.currentTarget;
        clearAllSelections();
        card.classList.add('selected');
        hiddenInput.value = card.dataset.number;
        selectedNumberIsPremium = card.dataset.premium === '1';
        
        if (selectedNumberIsPremium) {
            sponsorModal.show();
        } else {
            submitBtn.disabled = false;
        }
    }

    // 处理自定义号码
    function handleCustomNumberSelection() {
        const number = customInput.value.trim();
        if (number === '') {
            alert('请输入号码！');
            return;
        }
        
        clearAllSelections();
        hiddenInput.value = number;
        selectedNumberIsPremium = true;
        customSelectionDisplay.textContent = `已选定: ${number}`;
        customSelectionDisplay.classList.remove('d-none');
        sponsorModal.show();
    }

    function clearAllSelections() {
        document.querySelectorAll('.number-card.selected').forEach(c => c.classList.remove('selected'));
        submitBtn.disabled = true;
        hiddenInput.value = '';
        customSelectionDisplay.classList.add('d-none');
    }

    // --- 核心修复：AJAX 提交表单 ---
    numberForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // 阻止默认的跳转/刷新行为
        
        const number = hiddenInput.value;
        if (!number) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 正在提交...';

        try {
            const formData = new FormData();
            formData.append('number', number);

            // 调用后端最终确认接口
            const response = await fetch('api/finalize_application.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // 如果是靓号需要支付，跳转到结果页（带支付提示）
                // 如果是普通号，直接跳转到结果页
                window.location.href = result.redirect || 'result.php?application_id=' + result.application_id;
            } else {
                alert('提交失败：' + (result.error || '未知错误'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = '确认选择并完成申请';
            }
        } catch (error) {
            alert('网络请求失败，请检查连接');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '确认选择并完成申请';
        }
    });

    // 绑定其他事件
    refreshBtn.addEventListener('click', fetchAndRenderNumbers);
    selectCustomBtn.addEventListener('click', handleCustomNumberSelection);
    confirmSponsorBtn.addEventListener('click', () => {
        submitBtn.disabled = false;
        sponsorModal.hide();
    });

    fetchAndRenderNumbers();
});
</script>
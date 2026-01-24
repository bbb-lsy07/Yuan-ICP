<?php extract($data); ?>
<div class="container my-5">
    <div class="query-container">
        <h2 class="mb-4 text-center">备案查询</h2>
        
        <!-- 查询表单 -->
        <form class="mb-5">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="icp_number" class="form-label">备案号</label>
                    <input type="text" class="form-control" id="icp_number" name="icp_number" 
                        value="<?php echo htmlspecialchars($icp_number); ?>" placeholder="输入备案号">
                </div>
                <div class="col-md-6">
                    <label for="domain" class="form-label">或域名</label>
                    <input type="text" class="form-control" id="domain" name="domain" 
                        value="<?php echo htmlspecialchars($domain); ?>" placeholder="输入域名">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">查询</button>
                </div>
            </div>
        </form>
        
        <!-- 查询结果 -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($result): ?>
            <div class="card result-card mb-4">
                <div class="card-body">
                    <h4 class="card-title">备案信息</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>备案号:</strong> <?php echo htmlspecialchars($result['number']); ?></p>
                            <p><strong>网站名称:</strong> <?php echo htmlspecialchars($result['website_name']); ?></p>
                            <p><strong>域名:</strong> <?php echo htmlspecialchars($result['domain']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>审核人:</strong> <?php echo htmlspecialchars($result['reviewer'] ?? '系统'); ?></p>
                            <p><strong>审核时间:</strong> <?php echo date('Y-m-d H:i', strtotime($result['reviewed_at'])); ?></p>
                            <p><strong>网站描述:</strong> <?php echo htmlspecialchars($result['description']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__.'/../includes/bootstrap.php';

// Check login status
require_login();

// Get pending applications
try {
    // 修复：强制按创建时间升序排列 (FIFO)
    $db = db();
    $applications = $db->query("SELECT * FROM icp_applications WHERE status = 'pending' ORDER BY created_at ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化数据以匹配视图
    foreach ($applications as &$app) {
        $app['created_at_formatted'] = date('Y-m-d H:i', strtotime($app['created_at']));
        // 如果有其他需要处理的字段（如 screenshot_url），在这里处理
    }
    unset($app); // 断开引用
} catch (Exception $e) {
    handle_error('Failed to load applications: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>极速审批 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .approval-container {
            position: relative;
            max-width: 500px;
            height: 600px;
            margin: 40px auto;
            perspective: 1000px;
        }
        
        .approval-card {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease, opacity 0.3s ease, box-shadow 0.3s ease;
            cursor: grab;
            user-select: none;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
        }
        
        .approval-card:active {
            cursor: grabbing;
        }
        
        .card-header-badge {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .website-screenshot {
            width: 100%;
            height: 200px;
            background-color: #f3f4f6;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .website-screenshot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-row {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 0.9rem;
            flex-shrink: 0;
            width: 80px;
        }
        
        .info-value {
            color: #111827;
            font-weight: 500;
            text-align: right;
            word-break: break-all;
        }
        
        .action-buttons {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 100;
        }
        
        .action-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-reject {
            background-color: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }
        
        .btn-approve {
            background-color: #10b981;
            color: white;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        
        .action-btn:active {
            transform: scale(0.95);
        }
        
        .empty-state {
            text-align: center;
            padding-top: 100px;
            color: #6b7280;
            display: none;
        }
        
        .status-badge {
            position: absolute;
            top: 40px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2rem;
            opacity: 0;
            transform: rotate(-15deg);
            z-index: 10;
            border: 4px solid;
        }
        
        .status-badge.approve {
            right: 40px;
            color: #10b981;
            border-color: #10b981;
            transform: rotate(15deg);
        }
        
        .status-badge.reject {
            left: 40px;
            color: #ef4444;
            border-color: #ef4444;
            transform: rotate(-15deg);
        }
        
        /* Modal tweaks */
        .modal-body textarea {
            resize: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content position-relative" style="height: 100vh; overflow: hidden; background: #f9fafb;">
                <div class="d-flex justify-content-between align-items-center pt-4 px-4">
                    <h2 class="mb-0">极速审批</h2>
                    <span class="text-muted">待审核: <span id="pending-count"><?php echo count($applications); ?></span></span>
                </div>
                
                <?php if (empty($applications)): ?>
                    <div class="empty-state" style="display: block;">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h3>所有申请已处理完毕</h3>
                        <p>干得漂亮！</p>
                        <a href="dashboard.php" class="btn btn-primary mt-3">返回仪表盘</a>
                    </div>
                <?php else: ?>
                    <div class="approval-container" id="card-stack">
                        <?php foreach (array_reverse($applications) as $index => $app): ?>
                        <div class="approval-card" data-id="<?php echo $app['id']; ?>" style="z-index: <?php echo $index + 1; ?>">
                            <?php if (isset($app['is_resubmitted']) && $app['is_resubmitted']): ?>
                            <div class="resubmit-tag" style="position: absolute; top: 0; left: 0; background: #3b82f6; color: white; padding: 4px 12px; font-size: 0.7rem; font-weight: bold; border-radius: 16px 0 16px 0; z-index: 20; box-shadow: 2px 2px 5px rgba(0,0,0,0.1);">
                                <i class="fas fa-redo-alt"></i> 修正案
                            </div>
                            <?php endif; ?>
                            <div class="status-badge approve">通过</div>
                            <div class="status-badge reject">驳回</div>
                            
                            <div class="website-screenshot">
                                <!-- Ideally, we would have a screenshot service here. Using a placeholder for now. -->
                                <i class="fas fa-globe"></i>
                                <?php if (isset($app['screenshot_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($app['screenshot_url']); ?>" alt="Website Screenshot">
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="mb-3 text-center"><?php echo htmlspecialchars($app['website_name']); ?></h4>
                            
                            <div class="info-row">
                                <span class="info-label">备案号</span>
                                <span class="info-value font-monospace text-primary"><?php echo htmlspecialchars($app['number']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">域名</span>
                                <span class="info-value">
                                    <a href="http://<?php echo htmlspecialchars($app['domain']); ?>" target="_blank" onclick="event.stopPropagation()">
                                        <?php echo htmlspecialchars($app['domain']); ?> <i class="fas fa-external-link-alt small"></i>
                                    </a>
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">申请人</span>
                                <span class="info-value"><?php echo htmlspecialchars($app['owner_name']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">邮箱</span>
                                <span class="info-value"><?php echo htmlspecialchars($app['owner_email']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">简介</span>
                                <span class="info-value text-muted fw-normal"><?php echo nl2br(htmlspecialchars($app['description'])); ?></span>
                            </div>
                            
                            <div class="info-row mt-3">
                                <span class="info-label">申请时间</span>
                                <span class="info-value fw-normal text-muted"><?php echo $app['created_at_formatted']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="action-btn btn-reject" id="btn-reject" title="驳回 (Left Arrow)">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="action-btn btn-approve" id="btn-approve" title="通过 (Right Arrow)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    
                    <div class="empty-state" id="final-empty-state">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h3>所有申请已处理完毕</h3>
                        <p>干得漂亮！</p>
                        <a href="dashboard.php" class="btn btn-primary mt-3">返回仪表盘</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Reject Reason Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">驳回申请</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectForm">
                        <input type="hidden" id="rejectAppId">
                        <div class="mb-3">
                            <label class="form-label">驳回原因</label>
                            <select class="form-select mb-2" id="rejectReasonSelect">
                                <option value="">-- 选择常用原因 --</option>
                                <option value="网站内容不符合要求">网站内容不符合要求</option>
                                <option value="网站无法访问">网站无法访问</option>
                                <option value="网站名称不规范">网站名称不规范</option>
                                <option value="域名信息不匹配">域名信息不匹配</option>
                                <option value="包含违法违规信息">包含违法违规信息</option>
                                <option value="其他">其他</option>
                            </select>
                            <textarea class="form-control" id="rejectReason" rows="3" placeholder="请输入具体原因..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">确认驳回</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://hammerjs.github.io/dist/hammer.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardStack = document.getElementById('card-stack');
            if (!cardStack) return;
            
            const cards = Array.from(document.querySelectorAll('.approval-card'));
            const btnApprove = document.getElementById('btn-approve');
            const btnReject = document.getElementById('btn-reject');
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            const rejectReasonSelect = document.getElementById('rejectReasonSelect');
            const rejectReasonText = document.getElementById('rejectReason');
            const confirmRejectBtn = document.getElementById('confirmReject');
            
            let currentCardIndex = cards.length - 1;
            
            // Initialize Hammer.js for swipe gestures
            cards.forEach((card, index) => {
                const hammertime = new Hammer(card);
                
                // Only enable swipe for the top card
                if (index !== currentCardIndex) return;
                
                initHammer(hammertime, card);
            });
            
            function initHammer(hammertime, card) {
                hammertime.on('pan', function(ev) {
                    if (card !== cards[currentCardIndex]) return;
                    
                    card.style.transition = 'none';
                    const xPos = ev.deltaX;
                    const rotate = xPos / 20;
                    
                    card.style.transform = `translateX(${xPos}px) rotate(${rotate}deg)`;
                    
                    // Show badges
                    const approveBadge = card.querySelector('.status-badge.approve');
                    const rejectBadge = card.querySelector('.status-badge.reject');
                    
                    if (xPos > 0) {
                        approveBadge.style.opacity = Math.min(xPos / 100, 1);
                        rejectBadge.style.opacity = 0;
                    } else {
                        rejectBadge.style.opacity = Math.min(Math.abs(xPos) / 100, 1);
                        approveBadge.style.opacity = 0;
                    }
                });
                
                hammertime.on('panend', function(ev) {
                    if (card !== cards[currentCardIndex]) return;
                    
                    card.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                    const xPos = ev.deltaX;
                    
                    if (xPos > 150) {
                        // Swipe Right - Approve
                        handleApprove(card);
                    } else if (xPos < -150) {
                        // Swipe Left - Reject
                        handleRejectInit(card);
                    } else {
                        // Reset
                        card.style.transform = '';
                        card.querySelector('.status-badge.approve').style.opacity = 0;
                        card.querySelector('.status-badge.reject').style.opacity = 0;
                    }
                });
            }
            
            function updateTopCard() {
                if (currentCardIndex >= 0) {
                    const card = cards[currentCardIndex];
                    const hammertime = new Hammer(card);
                    initHammer(hammertime, card);
                } else {
                    document.getElementById('card-stack').style.display = 'none';
                    document.getElementById('final-empty-state').style.display = 'block';
                    document.querySelector('.action-buttons').style.display = 'none';
                }
                
                // Update pending count
                document.getElementById('pending-count').textContent = currentCardIndex + 1;
            }
            
            // Button handlers
            btnApprove.addEventListener('click', () => {
                if (currentCardIndex >= 0) handleApprove(cards[currentCardIndex]);
            });
            
            btnReject.addEventListener('click', () => {
                if (currentCardIndex >= 0) handleRejectInit(cards[currentCardIndex]);
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (currentCardIndex < 0) return;
                if (document.querySelector('.modal.show')) return; // Don't trigger if modal is open
                
                if (e.key === 'ArrowRight') {
                    handleApprove(cards[currentCardIndex]);
                } else if (e.key === 'ArrowLeft') {
                    handleRejectInit(cards[currentCardIndex]);
                }
            });
            
            // Approve Logic
            function handleApprove(card) {
                const appId = card.dataset.id;
                
                // Visual feedback
                card.style.transform = 'translateX(1000px) rotate(30deg)';
                card.style.opacity = '0';
                
                // API Call
                fetch('../api/quick_approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: appId,
                        action: 'approve'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success toast?
                    } else {
                        alert('操作失败: ' + data.error);
                        // Revert card?
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('网络错误');
                });
                
                currentCardIndex--;
                updateTopCard();
            }
            
            // Reject Logic Initialization
            function handleRejectInit(card) {
                const appId = card.dataset.id;
                document.getElementById('rejectAppId').value = appId;
                rejectReasonText.value = '';
                rejectReasonSelect.value = '';
                rejectModal.show();
            }
            
            // Reason select helper
            rejectReasonSelect.addEventListener('change', function() {
                if (this.value && this.value !== '其他') {
                    rejectReasonText.value = this.value;
                }
            });
            
            // Confirm Reject
            confirmRejectBtn.addEventListener('click', () => {
                const appId = document.getElementById('rejectAppId').value;
                const reason = rejectReasonText.value;
                
                if (!reason) {
                    alert('请输入驳回原因');
                    return;
                }
                
                const card = document.querySelector(`.approval-card[data-id="${appId}"]`);
                
                // Visual feedback
                if (card) {
                    card.style.transform = 'translateX(-1000px) rotate(-30deg)';
                    card.style.opacity = '0';
                }
                
                // API Call
                fetch('../api/quick_approve.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: appId,
                        action: 'reject',
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        rejectModal.hide();
                        if (card === cards[currentCardIndex]) {
                            currentCardIndex--;
                            updateTopCard();
                        }
                    } else {
                        alert('操作失败: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('网络错误');
                });
            });
        });
    </script>
</body>
</html>

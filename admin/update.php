<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线更新 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 main-content">
                <h2 class="mb-4">系统在线更新</h2>
                
                <div id="alert-container"></div>
                
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">当前版本: <span id="current-version-badge" class="badge bg-primary fs-6 ms-2">...</span></h5>
                        <button id="refresh-versions-btn" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i> 刷新列表
                        </button>
                    </div>
                </div>

                <div id="release-list">
                    <!-- Version list will be populated by JS -->
                </div>
                
                <div id="update-progress-area" style="display: none;" class="card mt-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-cogs"></i> 更新进度</h5></div>
                    <div class="card-body">
                        <div id="update-progress-log">等待更新日志...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const releaseListContainer = document.getElementById('release-list');
            const progressArea = document.getElementById('update-progress-area');
            const logContainer = document.getElementById('update-progress-log');
            const alertContainer = document.getElementById('alert-container');
            const refreshBtn = document.getElementById('refresh-versions-btn');
            let pollInterval;

            async function fetchReleases() {
                setLoadingState(true);
                try {
                    const response = await apiRequest('check');
                    document.getElementById('current-version-badge').textContent = 'v' + response.current_version;
                    renderReleases(response.releases, response.current_version);
                } catch (e) {
                    showAlert('获取版本列表失败: ' + e.message, 'danger');
                    releaseListContainer.innerHTML = `<div class="alert alert-danger">获取版本列表失败，请刷新页面重试。</div>`;
                } finally {
                    setLoadingState(false);
                }
            }

            function renderReleases(releases, currentVersion) {
                releaseListContainer.innerHTML = '';
                if (!releases || releases.length === 0) {
                    releaseListContainer.innerHTML = '<div class="alert alert-info">未找到任何发布版本。</div>';
                    return;
                }
                
                let latestVersion = currentVersion;
                releases.forEach(release => {
                    if (versionCompare(release.version, latestVersion) > 0) {
                        latestVersion = release.version;
                    }
                });

                releases.forEach(release => {
                    const isCurrent = (release.version === currentVersion);
                    const isNewest = (release.version === latestVersion && !isCurrent);
                    const isOlder = versionCompare(release.version, currentVersion) < 0;

                    const card = document.createElement('div');
                    card.className = `card version-card mb-3 ${isCurrent ? 'border-primary' : ''} ${isNewest ? 'border-success' : ''}`;
                    
                    let buttonHtml = '';
                    const isUpdateAction = versionCompare(release.version, currentVersion) > 0;

                    // 新增条件：检查版本是否低于或等于v5.22
                    if (versionCompare(release.version, '5.22') <= 0) {
                        buttonHtml = `<button class="btn btn-secondary" disabled title="此版本不支持在线安装或重装"><i class="fas fa-ban"></i> 此版本不可安装</button>`;
                    } else if (!release.download_url) {
                        buttonHtml = `<button class="btn btn-secondary" disabled title="此版本缺少更新包资源"><i class="fas fa-times-circle"></i> 无法更新</button>`;
                    } else {
                        const actionText = isUpdateAction ? '更新到此版本' : '重新安装';
                        const btnClass = isUpdateAction ? 'btn-success' : 'btn-outline-secondary';
                        const iconClass = isUpdateAction ? 'fa-arrow-circle-up' : 'fa-redo';
                        buttonHtml = `<button class="btn ${btnClass} start-update-btn" data-url="${release.download_url}" data-version="${release.version}"><i class="fas ${iconClass}"></i> ${actionText}</button>`;
                    }
                    
                    let assetsHtml = '';
                    if (release.assets && release.assets.length > 0) {
                        assetsHtml = `
                            <div class="assets-section">
                                <h6><i class="fas fa-download"></i> 资源文件</h6>
                                <ul class="asset-list">
                                    ${release.assets.map(asset => `
                                        <li class="asset-item">
                                            <div class="asset-info">
                                                <i class="fas ${asset.name.endsWith('.zip') ? 'fa-file-archive' : 'fa-file-code'}"></i>
                                                <span>${asset.name}</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="asset-size me-3">${formatBytes(asset.size)}</span>
                                                <a href="${asset.url}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">下载</a>
                                            </div>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>`;
                    }

                    const changelogInitialClass = isNewest ? 'changelog partially-expanded' : 'changelog';
                    const iconInitialClass = isNewest ? 'fas fa-chevron-up' : 'fas fa-chevron-down';

                    card.innerHTML = `
                        <div class="card-body">
                            <div>
                                <h5 class="card-title mb-1">
                                    Yuan-ICP v${release.version}
                                    ${isCurrent ? '<span class="badge bg-primary ms-2">当前版本</span>' : ''}
                                    ${isOlder ? '<span class="badge bg-light text-dark ms-2">历史版本</span>' : ''}
                                    ${isNewest ? '<span class="badge bg-success ms-2">最新版本</span>' : ''}
                                </h5>
                                <small class="text-muted">发布于: ${release.published_at}</small>
                            </div>
                            <div>${buttonHtml}</div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="#" class="text-decoration-none toggle-changelog">查看更新日志 <i class="${iconInitialClass}"></i></a>
                                ${release.full_changelog_url ? `<a href="${release.full_changelog_url}" target="_blank" class="text-decoration-none text-muted small">查看完整差异 <i class="fas fa-external-link-alt"></i></a>` : ''}
                            </div>
                            <div class="${changelogInitialClass}">
                                <div class="changelog-content">
                                    ${markdownToHtml(release.changelog)}
                                </div>
                            </div>
                            ${assetsHtml}
                        </div>`;
                    releaseListContainer.appendChild(card);
                });
            }

            async function startUpdate(button, url, version) {
                const originalHtml = button.innerHTML;
                document.querySelectorAll('.start-update-btn').forEach(btn => btn.disabled = true);
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 准备中...';
                progressArea.style.display = 'block';
                logContainer.textContent = '等待更新日志...';
                
                try {
                    await apiRequest('download', { download_url: url });
                    pollInterval = setInterval(pollProgress, 1500);
                    await apiRequest('update', { new_version: version });
                    clearInterval(pollInterval);
                    await pollProgress(); // Final poll to get the last logs
                    showAlert('更新成功！页面将在5秒后自动刷新。', 'success');
                    setTimeout(() => window.location.reload(), 5000);
                } catch (e) {
                    clearInterval(pollInterval);
                    await pollProgress(); // Try to get error logs
                    showAlert('更新失败: ' + e.message, 'danger');
                    logContainer.innerHTML += `<br><span style="color: #ef4444;">&gt; 错误: ${e.message}<br>&gt; 更新已中止。</span>`;
                    document.querySelectorAll('.start-update-btn').forEach(btn => btn.disabled = false);
                    button.innerHTML = originalHtml;
                }
            }
            
            function setLoadingState(isLoading) {
                refreshBtn.disabled = isLoading;
                if (isLoading) {
                    releaseListContainer.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">正在从GitHub获取版本列表...</p></div>`;
                }
            }
            
            releaseListContainer.addEventListener('click', function(e) {
                const changelogToggle = e.target.closest('.toggle-changelog');
                if (changelogToggle) {
                    e.preventDefault();
                    const parentFooter = changelogToggle.closest('.card-footer');
                    const changelog = parentFooter.querySelector('.changelog');
                    const icon = changelogToggle.querySelector('i');

                    changelog.classList.toggle('partially-expanded');
                    changelog.classList.toggle('fully-expanded');
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                }
                
                const updateButton = e.target.closest('.start-update-btn');
                if (updateButton) {
                    const url = updateButton.dataset.url;
                    const version = updateButton.dataset.version;
                    const isUpdating = versionCompare(version, get_system_version()) > 0;
                    if (confirm(`确定要${isUpdating ? '更新' : '重新安装'}到 v${version} 吗？\n请确保已备份！`)) {
                        startUpdate(updateButton, url, version);
                    }
                }
            });

            async function pollProgress() {
                try {
                    const response = await fetch('../data/update_log.txt?t=' + Date.now());
                    if (!response.ok) return;
                    const text = await response.text();
                    if (logContainer.textContent !== text) {
                        logContainer.textContent = text;
                        logContainer.scrollTop = logContainer.scrollHeight;
                    }
                } catch (e) { console.warn('Polling error:', e); }
            }
            
            async function apiRequest(action, data = {}) {
                const formData = new FormData();
                formData.append('action', action);
                for (const key in data) { formData.append(key, data[key]); }

                const response = await fetch('../api/update_manager.php', { method: 'POST', body: formData });
                const resultText = await response.text();
                
                if (!response.ok) throw new Error(`服务器错误 (${response.status}): ${resultText.substring(0, 500)}`);
                
                try {
                    const result = JSON.parse(resultText);
                    if (!result.success) throw new Error(result.message);
                    return result;
                } catch (e) {
                    throw new Error('无法解析服务器响应。响应内容: ' + resultText.substring(0, 300) + '...');
                }
            }

            function showAlert(message, type = 'info') {
                const alertEl = document.createElement('div');
                alertEl.className = `alert alert-${type} alert-dismissible fade show`;
                alertEl.setAttribute('role', 'alert');
                alertEl.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                alertContainer.innerHTML = '';
                alertContainer.appendChild(alertEl);
            }

            function markdownToHtml(md) {
                if (!md) return '<p>无更新日志。</p>';
                return marked.parse(md);
            }

            function versionCompare(v1, v2) {
                if (!v1 || !v2) return 0;
                const parts1 = v1.split('.').map(s => parseInt(s, 10) || 0);
                const parts2 = v2.split('.').map(s => parseInt(s, 10) || 0);
                const len = Math.max(parts1.length, parts2.length);
                for (let i = 0; i < len; i++) {
                    const p1 = parts1[i] || 0;
                    const p2 = parts2[i] || 0;
                    if (p1 > p2) return 1;
                    if (p1 < p2) return -1;
                }
                return 0;
            }
            
            function get_system_version() {
                return document.getElementById('current-version-badge').textContent.replace('v', '');
            }

            function formatBytes(bytes, decimals = 2) {
                if (!+bytes) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
            }

            refreshBtn.addEventListener('click', fetchReleases);
            fetchReleases();
        });
    </script>
</body>
</html>
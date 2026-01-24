/**
 * 后台管理API调用工具类
 */
class AdminAPI {
    constructor() {
        this.baseUrl = '';
        this.loading = false;
    }
    
    /**
     * 显示加载状态
     */
    showLoading() {
        if (this.loading) return;
        this.loading = true;
        
        // 创建加载遮罩
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = 
            '<div class="loading-spinner">' +
                '<div class="spinner-border text-primary" role="status">' +
                    '<span class="visually-hidden">加载中...</span>' +
                '</div>' +
                '<div class="mt-2">加载中...</div>' +
            '</div>';
        
        // 添加样式
        const style = document.createElement('style');
        style.textContent = 
            '.loading-overlay {' +
                'position: fixed;' +
                'top: 0;' +
                'left: 0;' +
                'width: 100%;' +
                'height: 100%;' +
                'background: rgba(0, 0, 0, 0.5);' +
                'display: flex;' +
                'justify-content: center;' +
                'align-items: center;' +
                'z-index: 9999;' +
            '}' +
            '.loading-spinner {' +
                'background: white;' +
                'padding: 2rem;' +
                'border-radius: 8px;' +
                'text-align: center;' +
                'box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);' +
            '}';
        document.head.appendChild(style);
        document.body.appendChild(overlay);
    }
    
    /**
     * 隐藏加载状态
     */
    hideLoading() {
        this.loading = false;
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    /**
     * 显示错误消息
     */
    showError(message) {
        if (window.toast) {
            window.toast.error(message);
        } else {
            // 回退到传统alert
            const existingAlert = document.querySelector('.alert-danger');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = 
                '<strong>错误：</strong> ' + message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            
            const container = document.querySelector('.main-content');
            if (container) {
                container.insertBefore(alert, container.firstChild);
            }
        }
    }
    
    /**
     * 显示成功消息
     */
    showSuccess(message) {
        if (window.toast) {
            window.toast.success(message);
        } else {
            // 回退到传统alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = 
                '<strong>成功：</strong> ' + message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            
            const container = document.querySelector('.main-content');
            if (container) {
                container.insertBefore(alert, container.firstChild);
            }
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    }
    
    /**
     * 显示警告消息
     */
    showWarning(message) {
        if (window.toast) {
            window.toast.warning(message);
        } else {
            this.showCustomAlert(message, 'warning');
        }
    }
    
    /**
     * 显示信息消息
     */
    showInfo(message) {
        if (window.toast) {
            window.toast.info(message);
        } else {
            this.showCustomAlert(message, 'info');
        }
    }
    
    /**
     * 显示自定义弹窗
     */
    showCustomAlert(message, type = 'info') {
        // 创建弹窗元素
        const alertOverlay = document.createElement('div');
        alertOverlay.className = 'custom-alert-overlay';
        alertOverlay.style.cssText = `
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        `;
        
        const alertBox = document.createElement('div');
        alertBox.style.cssText = `
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: slideInUp 0.3s ease;
        `;
        
        const iconMap = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        
        const colorMap = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        };
        
        alertBox.innerHTML = `
            <div style="font-size: 3rem; margin-bottom: 1rem; color: ${colorMap[type] || colorMap.info};">
                <i class="fas ${iconMap[type] || iconMap.info}"></i>
            </div>
            <p style="font-size: 1.1rem; color: #1f2937; margin-bottom: 1.5rem;">${message}</p>
            <button class="btn btn-primary" onclick="this.closest('.custom-alert-overlay').remove()">好的</button>
        `;
        
        alertOverlay.appendChild(alertBox);
        document.body.appendChild(alertOverlay);
        
        // 添加动画样式
        if (!document.querySelector('#custom-alert-styles')) {
            const style = document.createElement('style');
            style.id = 'custom-alert-styles';
            style.textContent = `
                @keyframes slideInUp { 
                    from { opacity: 0; transform: translateY(30px); } 
                    to { opacity: 1; transform: translateY(0); } 
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    /**
     * 发送API请求 (最终修正版)
     */
    async request(url, options = {}) {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest'
        };

        // 合并默认头和传入的头，而不是替换
        const finalOptions = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...(options.headers || {})
            }
        };

        // 如果请求体是 FormData，则必须删除 Content-Type 头，
        // 以便浏览器能自动设置正确的 multipart/form-data 和 boundary。
        if (finalOptions.body instanceof FormData) {
            delete finalOptions.headers['Content-Type'];
        } else {
            // 对于其他请求，确保 Content-Type 为 application/json
            finalOptions.headers['Content-Type'] = finalOptions.headers['Content-Type'] || 'application/json';
        }

        try {
            const response = await fetch(url, finalOptions);

            if (response.status === 401) {
                const data = await response.json();
                this.showError(data.message || '会话已过期，2秒后将跳转到登录页。');
                setTimeout(() => {
                    window.location.href = data.redirect || '/admin/login.php';
                }, 2000);
                throw new Error('Session expired');
            }
            
            // 尝试解析JSON，如果失败则提供更详细的错误
            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error("收到的响应不是有效的JSON:", responseText);
                throw new Error('服务器返回了无效的数据格式，请检查后台日志。');
            }

            if (!response.ok) {
                throw new Error(data.message || '请求失败');
            }
            
            return data;
        } catch (error) {
            console.error('API请求错误:', error);
            if (error.message !== 'Session expired') {
                this.showError(error.message); // 向用户显示错误
                throw error;
            }
        }
    }
    
    /**
     * 获取备案申请列表
     */
    async getApplications(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.status) params.append('status', filters.status);
        if (filters.search) params.append('search', filters.search);
        if (filters.page) params.append('page', filters.page);
        if (filters.per_page) params.append('per_page', filters.per_page);
        
        return await this.request('api/get_applications.php?' + params.toString());
    }
    
    /**
     * 获取公告列表
     */
    async getAnnouncements(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.search) params.append('search', filters.search);
        if (filters.page) params.append('page', filters.page);
        if (filters.per_page) params.append('per_page', filters.per_page);
        
        return await this.request('api/get_announcements.php?' + params.toString());
    }
    
    /**
     * 审核申请
     */
    async reviewApplication(id, action, reason = '') {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', action);
        if (reason) formData.append('reason', reason);
        
        return await this.request('applications.php', {
            method: 'POST',
            body: formData,
            headers: {} // 让浏览器自动设置Content-Type
        });
    }
    
    /**
     * 删除公告
     */
    async deleteAnnouncement(id) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'delete_announcement');
        
        // 修改点：将 URL 改为当前页面路径
        return await this.request(window.location.pathname, {
            method: 'POST',
            body: formData,
        });
    }
    
    /**
     * 切换公告置顶状态
     */
    async toggleAnnouncementPin(id) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'toggle_announcement_pin');
        
        // 修改点：将 URL 改为当前页面路径
        return await this.request(window.location.pathname, {
            method: 'POST',
            body: formData,
        });
    }
    
    /**
     * 审核申请
     */
    async reviewApplication(id, action, reason = null) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', action);
        if (reason) {
            formData.append('reason', reason);
        }
        
        return await this.request('applications.php', {
            method: 'POST',
            body: formData,
            headers: {}
        });
    }
}

/**
 * 备案申请管理页面
 */
class ApplicationManager {
    constructor() {
        this.api = new AdminAPI();
        this.currentFilters = {
            status: 'all',
            search: '',
            page: 1
        };
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadData();
    }
    
    bindEvents() {
        // 搜索表单
        const searchForm = document.querySelector('form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSearch();
            });
        }
        
        // 状态筛选
        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => {
                this.handleFilterChange();
            });
        }
        
        // 搜索框
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.handleSearch();
            }, 500));
        }
    }
    
    handleSearch() {
        const form = document.querySelector('form');
        const formData = new FormData(form);
        
        this.currentFilters = {
            status: formData.get('status') || 'all',
            search: formData.get('search') || '',
            page: 1
        };
        
        this.loadData();
    }
    
    handleFilterChange() {
        this.handleSearch();
    }
    
    async loadData() {
        try {
            this.api.showLoading();
            const response = await this.api.getApplications(this.currentFilters);
            
            if (response.success) {
                this.renderTable(response.data.applications);
                this.renderPagination(response.data.pagination);
            } else {
                this.api.showError(response.message || '加载数据失败');
            }
        } catch (error) {
            this.api.showError(error.message);
        } finally {
            this.api.hideLoading();
        }
    }
    
    renderTable(applications) {
        const tbody = document.querySelector('table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = applications.map(app => {
            let html = '<tr>';
            html += '<td>';
            html += app.number;
            if (app.is_premium) {
                html += '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-gem"></i> 靓号</span>';
            }
            html += '</td>';
            html += '<td>' + this.escapeHtml(app.website_name) + '</td>';
            html += '<td>' + this.escapeHtml(app.domain) + '</td>';
            html += '<td>';
            html += '<span class="badge bg-' + app.status_class + ' status-badge">';
            html += app.status_text;
            html += '</span>';
            html += '</td>';
            html += '<td>' + app.created_at_formatted + '</td>';
            html += '<td>' + this.escapeHtml(app.reviewer || '-') + '</td>';
            html += '<td>';
            html += '<div class="btn-group btn-group-sm">';
            if (app.status === 'pending') {
                html += '<button type="button" class="btn btn-success" onclick="appManager.approveApplication(' + app.id + ')">通过</button>';
                html += '<button type="button" class="btn btn-danger" onclick="appManager.rejectApplication(' + app.id + ')">驳回</button>';
            }
            html += '<a href="application_edit.php?id=' + app.id + '" class="btn btn-info">编辑</a>';
            html += '<a href="applications.php?search=' + encodeURIComponent(app.number) + '" class="btn btn-primary">查看</a>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
            return html;
        }).join('');
    }
    
    renderPagination(pagination) {
        const paginationContainer = document.querySelector('.pagination-container');
        if (!paginationContainer) return;
        
        if (pagination.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // 上一页
        if (pagination.has_prev) {
            html += '<li class="page-item"><a class="page-link" href="#" onclick="appManager.goToPage(' + pagination.prev_page + ')">上一页</a></li>';
        } else {
            html += '<li class="page-item disabled"><span class="page-link">上一页</span></li>';
        }
        
        // 页码
        const start = Math.max(1, pagination.current_page - 2);
        const end = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        for (let i = start; i <= end; i++) {
            if (i === pagination.current_page) {
                html += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
            } else {
                html += '<li class="page-item"><a class="page-link" href="#" onclick="appManager.goToPage(' + i + ')">' + i + '</a></li>';
            }
        }
        
        // 下一页
        if (pagination.has_next) {
            html += '<li class="page-item"><a class="page-link" href="#" onclick="appManager.goToPage(' + pagination.next_page + ')">下一页</a></li>';
        } else {
            html += '<li class="page-item disabled"><span class="page-link">下一页</span></li>';
        }
        
        html += '</ul></nav>';
        const startItem = (pagination.current_page - 1) * pagination.per_page + 1;
        const endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
        html += '<div class="text-center mt-3 text-muted"><small>显示第 ' + startItem + '-' + endItem + ' 条，共 ' + pagination.total_items + ' 条记录</small></div>';
        
        paginationContainer.innerHTML = html;
    }
    
    goToPage(page) {
        this.currentFilters.page = page;
        this.loadData();
    }
    
    async approveApplication(id) {
        if (!confirm('确定要通过此备案申请吗？')) return;
        
        try {
            this.api.showLoading();
            await this.api.reviewApplication(id, 'approve');
            this.api.showSuccess('申请已通过');
            this.loadData();
        } catch (error) {
            this.api.showError(error.message);
        } finally {
            this.api.hideLoading();
        }
    }
    
    async rejectApplication(id) {
        const reason = prompt('请输入驳回原因：');
        if (!reason) return;
        
        try {
            this.api.showLoading();
            await this.api.reviewApplication(id, 'reject', reason);
            this.api.showSuccess('申请已驳回');
            this.loadData();
        } catch (error) {
            this.api.showError(error.message);
        } finally {
            this.api.hideLoading();
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// 全局实例
let appManager;

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.application-manager')) {
        appManager = new ApplicationManager();
    }
});

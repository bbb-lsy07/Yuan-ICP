/**
 * Toast通知组件
 */
class Toast {
    constructor() {
        this.container = null;
        this.init();
    }
    
    init() {
        // 创建Toast容器
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'toast-container position-fixed top-0 end-0 p-3';
        this.container.style.zIndex = '9999';
        document.body.appendChild(this.container);
    }
    
    /**
     * 显示Toast通知
     * @param {string} message 消息内容
     * @param {string} type 类型: success, error, warning, info
     * @param {number} duration 显示时长(毫秒)，0表示不自动关闭
     */
    show(message, type = 'info', duration = 5000) {
        const toastId = 'toast-' + Date.now();
        const toastElement = document.createElement('div');
        toastElement.id = toastId;
        toastElement.className = 'toast';
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        // 根据类型设置样式
        const typeClasses = {
            'success': 'bg-success text-white',
            'error': 'bg-danger text-white',
            'warning': 'bg-warning text-dark',
            'info': 'bg-info text-white'
        };
        
        const typeIcons = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        
        toastElement.innerHTML = `
            <div class="toast-header ${typeClasses[type]}">
                <i class="${typeIcons[type]} me-2"></i>
                <strong class="me-auto">${this.getTypeTitle(type)}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        this.container.appendChild(toastElement);
        
        // 初始化Bootstrap Toast
        const bsToast = new bootstrap.Toast(toastElement, {
            autohide: duration > 0,
            delay: duration
        });
        
        bsToast.show();
        
        // Toast关闭后移除元素
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
        
        return bsToast;
    }
    
    /**
     * 显示成功消息
     */
    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    }
    
    /**
     * 显示错误消息
     */
    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }
    
    /**
     * 显示警告消息
     */
    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    }
    
    /**
     * 显示信息消息
     */
    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    }
    
    /**
     * 获取类型标题
     */
    getTypeTitle(type) {
        const titles = {
            'success': '成功',
            'error': '错误',
            'warning': '警告',
            'info': '提示'
        };
        return titles[type] || '提示';
    }
    
    /**
     * 清除所有Toast
     */
    clear() {
        const toasts = this.container.querySelectorAll('.toast');
        toasts.forEach(toast => {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        });
    }
}

// 全局Toast实例
window.toast = new Toast();

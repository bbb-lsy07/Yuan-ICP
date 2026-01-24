/**
 * Yuan-ICP 核心业务逻辑库
 * 独立于主题的业务功能，所有主题都可以使用
 */

// 全局 YICP 对象
window.YICP = window.YICP || {};

// API 基础配置
YICP.config = {
    apiBase: 'api/',
    timeout: 30000
};

// 工具函数
YICP.utils = {
    /**
     * 发送 API 请求
     */
    async request(endpoint, options = {}) {
        const url = YICP.config.apiBase + endpoint;
        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API请求失败:', error);
            throw error;
        }
    },

    /**
     * 显示消息提示
     */
    showMessage(message, type = 'info', container = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `yicp-message yicp-message-${type}`;
        messageDiv.innerHTML = `
            <div class="yicp-message-content">
                <span class="yicp-message-text">${message}</span>
                <button class="yicp-message-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        const target = container || document.body;
        target.insertBefore(messageDiv, target.firstChild);

        // 自动消失
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);

        return messageDiv;
    },

    /**
     * 验证器
     */
    validators: {
        required: (value, message = '此字段不能为空') => {
            return value && value.trim() ? null : message;
        },
        
        email: (value, message = '请输入有效的邮箱地址') => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value) ? null : message;
        },
        
        domain: (value, message = '请输入有效的域名格式') => {
            const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
            return domainRegex.test(value.trim()) ? null : message;
        },
        
        maxLength: (maxLen, message) => (value) => {
            return value.length <= maxLen ? null : message || `不能超过${maxLen}个字符`;
        }
    },

    /**
     * 表单验证
     */
    validateForm(form, rules) {
        let isValid = true;
        const errors = {};

        Object.keys(rules).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            const fieldRules = rules[fieldName];
            const value = field.value;

            for (const rule of fieldRules) {
                const error = rule(value);
                if (error) {
                    errors[fieldName] = error;
                    this.showFieldError(field, error);
                    isValid = false;
                    break;
                } else {
                    this.clearFieldError(field);
                }
            }
        });

        return { isValid, errors };
    },

    showFieldError(field, message) {
        this.clearFieldError(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'yicp-field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
        field.classList.add('yicp-error');
    },

    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.yicp-field-error');
        if (existingError) existingError.remove();
        field.classList.remove('yicp-error');
    }
};

// 申请表单模块
YICP.applyForm = {
    init() {
        const form = document.querySelector('[data-yicp-apply-form]');
        if (!form || form.dataset.yicpInitialized) return;
        
        form.dataset.yicpInitialized = 'true';
        this.bindEvents(form);
    },

    bindEvents(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        
        // 表单验证规则
        const rules = {
            site_name: [
                YICP.utils.validators.required,
                YICP.utils.validators.maxLength(50)
            ],
            domain: [
                YICP.utils.validators.required,
                YICP.utils.validators.domain
            ],
            contact_name: [
                YICP.utils.validators.required
            ],
            contact_email: [
                YICP.utils.validators.required,
                YICP.utils.validators.email
            ]
        };

        // 实时验证
        Object.keys(rules).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('blur', () => {
                    YICP.utils.validateForm(form, { [fieldName]: rules[fieldName] });
                });
            }
        });

        // 表单提交
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const validation = YICP.utils.validateForm(form, rules);
            if (!validation.isValid) {
                YICP.utils.showMessage('请检查并修正表单中的错误', 'error');
                return;
            }

            const originalHTML = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                if (result.success) {
                    YICP.utils.showMessage(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    YICP.utils.showMessage(result.error, 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalHTML;
                }
            } catch (error) {
                YICP.utils.showMessage('网络请求失败，请稍后重试', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalHTML;
            }
        });
    }
};

// 选号模块
YICP.numberSelector = {
    currentPage: 1,
    currentSearch: '',
    selectedNumber: null,
    isLoading: false,

    init() {
        const container = document.querySelector('[data-yicp-number-grid]');
        if (!container || container.dataset.yicpInitialized) return;
        
        container.dataset.yicpInitialized = 'true';
        this.container = container;
        this.bindEvents();
        this.loadNumbers();
    },

    bindEvents() {
        // 搜索功能
        const searchInput = document.querySelector('[data-yicp-search]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentSearch = e.target.value;
                    this.currentPage = 1;
                    this.loadNumbers();
                }, 500);
            });
        }

        // 刷新按钮
        const refreshBtn = document.querySelector('[data-yicp-refresh]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.currentPage = 1;
                this.loadNumbers();
            });
        }

        // 加载更多
        const loadMoreBtn = document.querySelector('[data-yicp-load-more]');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.currentPage++;
                this.loadNumbers(true);
            });
        }
    },

    async loadNumbers(append = false) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        if (!append) {
            this.container.innerHTML = '<div class="yicp-loading"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>';
        }

        try {
            const data = await YICP.utils.request(`get_numbers.php?page=${this.currentPage}&search=${encodeURIComponent(this.currentSearch)}`);
            
            if (!append) {
                this.container.innerHTML = '';
            }

            if (data.success && data.numbers.length > 0) {
                data.numbers.forEach(number => {
                    this.container.appendChild(this.createNumberCard(number));
                });

                // 更新加载更多按钮
                const loadMoreBtn = document.querySelector('[data-yicp-load-more]');
                const loadMoreContainer = document.querySelector('[data-yicp-load-more-container]');
                if (loadMoreContainer) {
                    loadMoreContainer.style.display = data.has_more ? 'block' : 'none';
                }
            } else if (!append) {
                this.container.innerHTML = '<div class="yicp-empty">暂无可用号码</div>';
            }
        } catch (error) {
            YICP.utils.showMessage('加载号码失败，请稍后重试', 'error');
            if (!append) {
                this.container.innerHTML = '<div class="yicp-error">加载失败，请刷新重试</div>';
            }
        } finally {
            this.isLoading = false;
            
            const loadMoreBtn = document.querySelector('[data-yicp-load-more]');
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
                loadMoreBtn.innerHTML = '加载更多';
            }
        }
    },

    createNumberCard(number) {
        const card = document.createElement('div');
        card.className = `yicp-number-card ${number.is_premium ? 'premium' : ''}`;
        card.dataset.number = number.number;
        
        card.innerHTML = `
            <div class="number-display">${number.number}</div>
            ${number.is_premium ? '<div class="premium-badge">靓号</div>' : ''}
            <button class="select-btn" onclick="YICP.numberSelector.selectNumber('${number.number}', ${number.is_premium})">
                选择此号
            </button>
        `;
        
        return card;
    },

    selectNumber(number, isPremium) {
        this.selectedNumber = { number, isPremium };
        
        // 更新选中状态
        document.querySelectorAll('.yicp-number-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        const selectedCard = document.querySelector(`[data-number="${number}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        // 更新显示
        const selectedDisplay = document.querySelector('[data-yicp-selected-display]');
        if (selectedDisplay) {
            selectedDisplay.textContent = `已选择: ${number}${isPremium ? ' (靓号)' : ''}`;
        }

        // 启用确认按钮
        const confirmBtn = document.querySelector('[data-yicp-confirm]');
        if (confirmBtn) {
            confirmBtn.disabled = false;
        }

        // 触发自定义事件
        document.dispatchEvent(new CustomEvent('yicp:numberSelected', {
            detail: { number, isPremium }
        }));
    }
};

// 复制功能
YICP.copyToClipboard = {
    init() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-yicp-copy]')) {
                const text = e.target.dataset.yicpCopy || e.target.textContent;
                this.copy(text);
            }
        });
    },

    async copy(text) {
        try {
            await navigator.clipboard.writeText(text);
            YICP.utils.showMessage('已复制到剪贴板', 'success');
        } catch (error) {
            // 降级方案
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            YICP.utils.showMessage('已复制到剪贴板', 'success');
        }
    }
};

// 自动初始化
document.addEventListener('DOMContentLoaded', () => {
    YICP.applyForm.init();
    YICP.numberSelector.init();
    YICP.copyToClipboard.init();
});

// 如果使用了 Swup 或其他 SPA 路由，也要在页面切换后重新初始化
document.addEventListener('swup:contentReplaced', () => {
    YICP.applyForm.init();
    YICP.numberSelector.init();
});
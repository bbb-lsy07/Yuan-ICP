document.addEventListener('DOMContentLoaded', function() {
    // 确保 Swup.js 已加载
    if (typeof Swup === 'undefined') {
        console.error('Swup.js is not loaded.');
        initializePage(); // 即使没有 Swup，也运行一次初始化
        return;
    }

    const swup = new Swup({
        plugins: [new SwupSlideTheme()],
        animateHistoryBrowsing: true
    });

    // --- 全局初始化函数 ---
    // 这个函数包含了所有需要在页面加载或切换后执行的逻辑
    function initializePage() {
        initNav();
        // 如果页面上有其他需要初始化的脚本，也可以在这里调用
        if (document.getElementById('icp-apply-form')) initApplyForm();
        if (document.getElementById('number-grid-container')) initSelectNumberPage();
        if (document.querySelector('.result-guide')) initResultPage();
    }

    // --- 导航栏逻辑 (核心修复) ---
    const navMenu = document.querySelector('.nav-menu');
    let navIndicator;
    let mouseLeaveTimeout = null; // 全局鼠标离开定时器
    let scrollTimeout = null; // 滚动事件定时器

    // 获取或创建指示器元素
    function getNavIndicator() {
        if (!navMenu) return null;
        // 尝试在 DOM 中查找，如果不存在则创建
        let indicator = navMenu.querySelector('.nav-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'nav-indicator';
            navMenu.appendChild(indicator);
        }
        navIndicator = indicator; // 缓存到变量中
        return navIndicator;
    }

    // 更新指示器位置和高亮链接的文字颜色
    function updateNavIndicator(targetLink, isImmediate = false) {
        const indicator = getNavIndicator();
        if (!indicator) return;

        // 先清除所有链接的active类，确保只有目标链接有active类
        if (navMenu) {
            navMenu.querySelectorAll('a').forEach(link => {
                link.classList.remove('active');
                link.style.color = ''; // 清除内联样式
            });
        }
        
        if (targetLink) {
            const linkRect = targetLink.getBoundingClientRect();
            const menuRect = navMenu.getBoundingClientRect();
            
            // isImmediate 用于页面加载时，无动画地移动指示器
            if (isImmediate) {
                indicator.style.transition = 'none';
            } else {
                // 确保有滑动动画效果
                indicator.style.transition = 'all 0.3s ease';
            }
            
            indicator.style.width = `${linkRect.width}px`;
            // 重要修复：计算left时，加上滚动偏移量
            indicator.style.left = `${linkRect.left - menuRect.left + navMenu.scrollLeft}px`;
            indicator.style.opacity = '1';

            // 只有目标链接获得active类
            targetLink.classList.add('active');
            
            if (isImmediate) {
                // 强制浏览器重绘，然后恢复动画效果
                indicator.offsetHeight; 
                indicator.style.transition = 'all 0.3s ease';
            }
        } else {
            // 如果没有目标链接，则隐藏指示器
            indicator.style.opacity = '0';
            // 所有链接的active类已经在上面被清除了
        }
    }
    
    // 根据当前URL路径，找出当前应该激活的链接（不设置active类）
    function updateActiveLink() {
        if (!navMenu) return null;
        let activeLink = null;
        const currentPath = window.location.pathname;

        navMenu.querySelectorAll('a').forEach(link => {
            const href = link.getAttribute('href');
            // 处理相对路径和绝对路径
            let linkPath = href;
            if (href.startsWith('http')) {
                linkPath = new URL(href).pathname;
            } else if (href.startsWith('/')) {
                linkPath = href;
            } else {
                // 相对路径，需要与当前路径比较
                linkPath = href;
            }
            
            // 根据当前URL路径匹配对应的导航链接
            let isActive = false;
            
            if (currentPath === '/' || currentPath === '/index.php' || currentPath.endsWith('/')) {
                // 首页
                isActive = linkPath === '/' || linkPath === '/index.php' || linkPath === 'index.php' || linkPath.endsWith('/') || linkPath === '';
            } else if (currentPath.includes('/apply.php') || currentPath.includes('/select_number.php') || 
                      currentPath.includes('/result.php') || currentPath.includes('/details.php')) {
                // 申请相关页面
                isActive = linkPath.includes('apply.php');
            } else if (currentPath.includes('/query.php')) {
                // 查询页面
                isActive = linkPath.includes('query.php');
            } else if (currentPath.includes('/announcements.php')) {
                // 公告页面
                isActive = linkPath.includes('announcements.php');
            } else if (currentPath.includes('/leap.php')) {
                // 跳转页面
                isActive = linkPath.includes('leap.php');
            }

            if (isActive) {
                activeLink = link;
            }
        });
        
        // 如果没有找到匹配的链接，默认激活首页
        if (!activeLink) {
            const homeLink = navMenu.querySelector('a[href="/"], a[href="/index.php"], a[href="index.php"]');
            if (homeLink) {
                activeLink = homeLink;
            }
        }
        
        return activeLink;
    }

    // 初始化导航栏的函数，包含所有相关逻辑
    function initNav() {
        if (!navMenu) return;
        getNavIndicator(); // 确保指示器存在
        
        // 鼠标悬停效果的事件监听只绑定一次
        if (!navMenu.dataset.listenersAttached) {
            navMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('mouseenter', () => {
                    // 清除鼠标离开的定时器
                    if (mouseLeaveTimeout) {
                        clearTimeout(mouseLeaveTimeout);
                        mouseLeaveTimeout = null;
                    }
                    updateNavIndicator(link);
                });
            });
            
            navMenu.addEventListener('mouseleave', () => {
                // 鼠标离开时，延迟1秒后回到当前激活的链接
                mouseLeaveTimeout = setTimeout(() => {
                    const activeLink = updateActiveLink(); // 重新计算激活链接
                    if (activeLink) {
                        updateNavIndicator(activeLink, false); // 使用滑动效果
                    }
                    mouseLeaveTimeout = null; // 清除定时器引用
                }, 1000);
            });

            // 重要修复：添加滚动事件监听器
            navMenu.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const activeLink = updateActiveLink();
                    if (activeLink) {
                         updateNavIndicator(activeLink, false);
                    }
                }, 150); // 滚动停止150ms后更新
            });
            
            navMenu.dataset.listenersAttached = 'true';
        }
        
        // 更新激活状态和指示器位置（延迟执行，避免与点击事件冲突）
        setTimeout(() => {
            const activeLink = updateActiveLink(); // 更新 active class
            if (activeLink) {
                // 确保指示器有滑动动画效果
                const indicator = getNavIndicator();
                if (indicator) {
                    indicator.style.transition = 'all 0.3s ease';
                }
                updateNavIndicator(activeLink, false); // 使用滑动效果
            }
        }, 100); // 短暂延迟，确保页面完全加载
    }

    // --- 申请表单逻辑 ---
    function initApplyForm() {
        const form = document.getElementById('icp-apply-form');
        if (!form || form.dataset.initialized) return;
        form.dataset.initialized = 'true';

        const submitButton = form.querySelector('button[type="submit"]');

        const fields = {
            site_name: form.querySelector('[name="site_name"]'),
            domain: form.querySelector('[name="domain"]'),
            contact_name: form.querySelector('[name="contact_name"]'),
            contact_email: form.querySelector('[name="contact_email"]')
        };

        const validateField = (field, validator) => {
            const input = fields[field];
            if (!input) return true;
            const error = validator(input.value);
            if (error) {
                showFieldError(input, error);
                return false;
            }
            clearError(input);
            return true;
        };
        
        const validators = {
            site_name: value => {
                if (!value.trim()) return '网站名称不能为空';
                if (value.length > 50) return '网站名称不能超过50个字符';
                return null;
            },
            domain: value => {
                if (!value.trim()) return '域名不能为空';
                const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
                if (!domainRegex.test(value.trim())) return '请输入有效的域名格式';
                return null;
            },
            contact_name: value => {
                if (!value.trim()) return '您的称呼不能为空';
                return null;
            },
            contact_email: value => {
                if (!value.trim()) return '您的邮箱不能为空';
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value.trim())) return '请输入有效的邮箱地址';
                return null;
            }
        };

        const showFieldError = (input, message) => {
            clearError(input);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = message;
            input.parentNode.appendChild(errorDiv);
            input.classList.add('error');
        };

        const clearError = (input) => {
            const existingError = input.parentNode.querySelector('.field-error');
            if (existingError) existingError.remove();
            input.classList.remove('error');
        };

        // 实时验证
        Object.keys(fields).forEach(field => {
            const input = fields[field];
            if (input) {
                input.addEventListener('blur', () => validateField(field, validators[field]));
                input.addEventListener('input', () => {
                    if (input.classList.contains('error')) {
                        validateField(field, validators[field]);
                    }
                });
            }
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // 验证所有字段
            let isValid = true;
            Object.keys(validators).forEach(field => {
                if (!validateField(field, validators[field])) {
                    isValid = false;
                }
            });

            if (!isValid) {
                showApplyMessage('请检查并修正表单中的错误', 'error');
                return;
            }

            const originalButtonHTML = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
            
            // 移除旧的消息
            const existingMessage = form.parentNode.querySelector('.form-message');
            if(existingMessage) existingMessage.remove();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                });
                const result = await response.json();

                if (result.success) {
                    showApplyMessage(result.message, 'success');
                    setTimeout(() => { 
                        if (typeof swup !== 'undefined') {
                            swup.navigate(result.redirect); 
                        } else {
                            window.location.href = result.redirect;
                        }
                    }, 1500);
                } else {
                    showApplyMessage(result.error, 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHTML;
                }
            } catch (error) {
                showApplyMessage('网络请求失败或响应格式错误。', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHTML;
            }
        });

        function showApplyMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'form-message';
            messageDiv.textContent = message;
            messageDiv.classList.add(type === 'success' ? 'success' : 'error');
            form.parentNode.insertBefore(messageDiv, form);
        }
    }

    // --- 选号页面逻辑 ---
    function initSelectNumberPage() {
        const grid = document.getElementById('number-grid-container');
        if (!grid) return;

        let currentPage = 1;
        let currentSearch = '';
        let selectedNumberInfo = null;
        let isLoading = false;

        const searchInput = document.getElementById('search-input');
        const refreshBtn = document.getElementById('refresh-numbers');
        const loadMoreBtn = document.getElementById('load-more-btn');
        const loadMoreContainer = document.getElementById('load-more-container');
        const selectedDisplay = document.getElementById('selected-display');
        const confirmBtn = document.getElementById('confirm-btn');
        const paymentModal = document.getElementById('payment-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const paymentForm = document.getElementById('payment-form');
        const contactAdminBtn = document.getElementById('contact-admin-btn');
        
        async function fetchNumbers(page = 1, search = '', append = false) {
            if (isLoading) return;
            isLoading = true;
            if (!append) {
                grid.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
                confirmBtn.disabled = true;
                selectedDisplay.textContent = '请选择一个号码';
                selectedNumberInfo = null;
            } else {
                loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 加载中...';
                loadMoreBtn.disabled = true;
            }
            try {
                const response = await fetch(`/api/get_numbers.php?page=${page}&search=${encodeURIComponent(search)}`);
                const rawText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseErr) {
                    const start = rawText.indexOf('{');
                    const end = rawText.lastIndexOf('}');
                    if (start !== -1 && end !== -1 && end > start) {
                        data = JSON.parse(rawText.slice(start, end + 1));
                    } else {
                        throw parseErr;
                    }
                }

                if (!append) grid.innerHTML = '';

                if (data && data.success === true && Array.isArray(data.numbers) && data.numbers.length > 0) {
                    data.numbers.forEach(num => grid.appendChild(createNumberCard(num)));
                    loadMoreContainer.style.display = data.has_more ? 'block' : 'none';
                } else if (data && data.success === true) {
                    grid.innerHTML = '<p class="empty-state">未找到匹配的号码。</p>';
                    loadMoreContainer.style.display = 'none';
                } else if (data && data.success === false) {
                    const err = data.error || data.message || '加载号码失败，请稍后重试。';
                    grid.innerHTML = `<p class="error-state">${err}</p>`;
                    loadMoreContainer.style.display = 'none';
                } else {
                    grid.innerHTML = '<p class="error-state">加载号码失败，请重试。</p>';
                    loadMoreContainer.style.display = 'none';
                }
            } catch (e) {
                if (!append) grid.innerHTML = '<p class="error-state">加载号码失败，请重试。</p>';
            } finally {
                isLoading = false;
                if (append) {
                    loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> 加载更多';
                    loadMoreBtn.disabled = false;
                }
            }
        }

        function createNumberCard(num) {
            const card = document.createElement('div');
            card.className = 'number-card';
            card.dataset.number = num.number;
            card.dataset.isPremium = num.is_premium;
            if (num.is_premium) {
                card.classList.add('premium');
                card.innerHTML = `<div class="number-text">${num.number}</div><div class="premium-badge" title="靓号"><i class="fas fa-gem"></i></div>`;
            } else {
                card.innerHTML = `<div class="number-text">${num.number}</div>`;
            }
            card.addEventListener('click', () => selectNumber(num, card));
            return card;
        }

        function selectNumber(number, card) {
            document.querySelectorAll('.number-card.selected').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedNumberInfo = number;
            selectedDisplay.innerHTML = `<strong>已选:</strong> ${number.number} ${number.is_premium ? '<span class="premium-text">(靓号)</span>' : ''}`;
            confirmBtn.disabled = false;
        }
        
        async function submitApplication(isPayment = false, paymentData = null) {
            if (!selectedNumberInfo) return;
            const btn = isPayment ? paymentForm.querySelector('button[type="submit"]') : confirmBtn;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';

            try {
                const formData = new FormData();
                formData.append('number', selectedNumberInfo.number);
                if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);

                let api_url = '/api/finalize_application.php';
                let body = formData;
                
                if (isPayment) {
                    api_url = '/api/submit_payment.php';
                    body = new FormData(paymentForm);
                }

                const response = await fetch(api_url, { method: 'POST', body });
            const result = await response.json();

            if (result.success) {
                    hidePaymentModal();
                    showCustomAlert(result.message || '操作成功！', 'success');
                    setTimeout(() => swup.navigate(result.redirect), 1500);
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                showCustomAlert(error.message || '网络错误，请重试', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        async function processConfirmation() {
            if (!selectedNumberInfo) {
                showCustomAlert('请先选择一个号码。', 'warning');
                return;
            }

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';

            try {
                const formData = new FormData();
                formData.append('number', selectedNumberInfo.number);
                if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);

                const response = await fetch('/api/finalize_application.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    if (result.requires_payment) {
                        showPaymentModal();
                    } else {
                        showCustomAlert(result.message, 'success');
                        setTimeout(() => swup.navigate(result.redirect), 1500);
                    }
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                showCustomAlert(error.message || '网络错误，请重试', 'error');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> 确认选择';
            }
        }
        
        confirmBtn.addEventListener('click', processConfirmation);

        if (paymentForm) {
            paymentForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';

                try {
                    const response = await fetch('/api/submit_payment.php', { method: 'POST', body: new FormData(this) });
                    const result = await response.json();
                    if (result.success) {
                        hidePaymentModal();
                        showCustomAlert(result.message, 'success');
                        setTimeout(() => swup.navigate(result.redirect), 1500);
                    } else {
                        throw new Error(result.error);
                    }
                } catch (error) {
                    showCustomAlert(error.message || '网络错误，请重试', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
        
        // Modal helpers
        const showPaymentModal = () => { if(paymentModal) paymentModal.style.display = 'flex'; };
        const hidePaymentModal = () => { if(paymentModal) paymentModal.style.display = 'none'; };
        if (closeModalBtn) closeModalBtn.addEventListener('click', hidePaymentModal);
        if (contactAdminBtn) contactAdminBtn.addEventListener('click', hidePaymentModal);
        if (paymentModal) paymentModal.addEventListener('click', (e) => { if(e.target === paymentModal) hidePaymentModal(); });

        // Event listeners
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentPage = 1;
                currentSearch = searchInput.value;
                fetchNumbers(1, searchInput.value, false);
            }, 500);
        });
        refreshBtn.addEventListener('click', () => { searchInput.value = ''; currentSearch = ''; currentPage = 1; fetchNumbers(1, '', false); });
        loadMoreBtn.addEventListener('click', () => { currentPage++; fetchNumbers(currentPage, currentSearch, true); });

        // 自定义弹窗
        function showCustomAlert(message, type = 'info') {
            const alert = document.getElementById('custom-alert');
            if (!alert) return;
            
            const messageEl = document.getElementById('custom-alert-message');
            const closeBtn = document.getElementById('custom-alert-close');
            
            if (messageEl) messageEl.textContent = message;
            alert.className = `custom-alert-overlay ${type}`;
            alert.style.display = 'flex';
            
            if (closeBtn) {
                closeBtn.onclick = () => {
                    alert.style.display = 'none';
                };
            }
        }

        // Initial load
        fetchNumbers(1, '', false);
    }

    // --- Result 页面逻辑 ---
    function initResultPage() {
        window.copyCodeToClipboard = function(container) {
            const textToCopy = container.querySelector('pre').innerText;
            const feedback = container.querySelector('.copy-feedback');
            navigator.clipboard.writeText(textToCopy).then(() => {
                feedback.innerHTML = '<i class="fas fa-check"></i> 已复制!';
                container.classList.add('copied');
                setTimeout(() => { 
                    feedback.innerHTML = '<i class="fas fa-copy"></i> 点击复制'; 
                    container.classList.remove('copied'); 
                }, 2000);
            }).catch(err => {
                feedback.innerHTML = '<i class="fas fa-times"></i> 复制失败!';
            });
        };
    }
    
    // --- Swup 事件钩子 (关键修复) ---
    initializePage(); // 1. 首次加载时，立即执行一次初始化
    swup.hooks.on('page:view', initializePage); // 2. 每次 Swup 切换页面后，再次执行初始化
    
    // 监听URL变化（包括浏览器前进/后退按钮）
    let lastUrl = window.location.href;
    let urlSyncTimeout = null;
    new MutationObserver(() => {
        const url = window.location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            
            // 清除之前的定时器
            if (urlSyncTimeout) {
                clearTimeout(urlSyncTimeout);
            }
            
            // URL变化时，延迟重新计算激活链接和指示器位置
            urlSyncTimeout = setTimeout(() => {
                if (navMenu) {
                    const activeLink = updateActiveLink();
                    if (activeLink) {
                        updateNavIndicator(activeLink, false); // 使用滑动效果
                    }
                }
            }, 1000); // 1秒延迟，与点击事件保持一致
        }
    }).observe(document, { subtree: true, childList: true });
    
    // 点击链接时，先更新active状态，然后让Swup处理页面切换
    let positionSyncTimeout = null;
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && navMenu && navMenu.contains(link) && link.href && link.href !== window.location.href) {
            // 清除之前的定时器
            if (positionSyncTimeout) {
                clearTimeout(positionSyncTimeout);
            }
            
            // 立即更新active状态，但不移动指示器
            navMenu.querySelectorAll('a').forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            
            // 设置1秒延迟同步指示器位置
            positionSyncTimeout = setTimeout(() => {
                if (navMenu) {
                    const activeLink = updateActiveLink();
                    if (activeLink) {
                        updateNavIndicator(activeLink, false); // 使用滑动效果
                    }
                }
            }, 1000);
        }
    });
});
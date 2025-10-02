/**
 * 申请表单AJAX处理 (带实时验证) - v2, ID Selector
 */
document.addEventListener('DOMContentLoaded', function() {
    // 使用ID选择器，确保唯一性和准确性
    const form = document.getElementById('icp-apply-form'); 
    if (!form) return;

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
            if (!emailRegex.test(value.trim())) return '请输入有效的邮箱格式';
            return null;
        }
    };

    Object.keys(fields).forEach(key => {
        const input = fields[key];
        if (input) {
            input.addEventListener('input', () => validateField(key, validators[key]));
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        let isFormValid = true;
        Object.keys(validators).forEach(key => {
            if (!validateField(key, validators[key])) {
                isFormValid = false;
            }
        });

        if (!isFormValid) return;

        setLoading(true);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            });
            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'success');
                setTimeout(() => { window.location.href = result.redirect; }, 1500);
            } else {
                showMessage(result.error, 'error');
                setLoading(false);
            }
        } catch (error) {
            showMessage('网络请求失败，请稍后重试。', 'error');
            setLoading(false);
        }
    });

    function showFieldError(input, message) {
        clearError(input);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.cssText = 'color: #f44336; font-size: 0.8rem; margin-top: 5px;';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
        input.style.borderColor = '#f44336';
    }

    function clearError(input) {
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        input.style.borderColor = '';
    }

    function setLoading(loading) {
        if (!submitButton) return;
        if (loading) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-arrow-right"></i> 下一步';
        }
    }

    function showMessage(message, type) {
        const existingMessage = document.querySelector('.form-message');
        if (existingMessage) existingMessage.remove();

        const messageDiv = document.createElement('div');
        messageDiv.className = 'form-message card-effect';
        messageDiv.textContent = message;
        messageDiv.style.textAlign = 'center';
        messageDiv.style.fontWeight = 'bold';
        messageDiv.style.margin = '1rem 0';
        messageDiv.style.padding = '1rem';
        messageDiv.style.color = type === 'success' ? '#2ecc71' : '#f44336';
        messageDiv.style.background = type === 'success' ? 'rgba(46, 204, 113, 0.1)' : 'rgba(244, 67, 54, 0.1)';
        form.parentNode.insertBefore(messageDiv, form);
    }
});

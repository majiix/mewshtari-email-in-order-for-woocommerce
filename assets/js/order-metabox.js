document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('mewshtari_template_select');
    const subjectInput = document.getElementById('mewshtari_email_subject');
    const sendButton = document.getElementById('mewshtari-send-email-btn');
    const btnSpinner = document.getElementById('mewshtari-btn-spinner');
    const btnText = document.getElementById('mewshtari-btn-text');
    const statusCard = document.getElementById('mewshtari-status-card');
    const statusIcon = document.getElementById('mewshtari-status-icon');
    const statusText = document.getElementById('mewshtari-status-text');
    const toast = document.getElementById('mewshtari-toast');

    let countdownTimer = null;
    let secondsLeft = 10;

    if (typeof mewshtariMetaboxData === 'undefined') return;

    if (!selector) return;

    // Handle badge clicks (Click to copy)
    document.querySelectorAll('.mewshtari-mb-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const tag = this.getAttribute('data-tag');
            navigator.clipboard.writeText(tag).then(() => {
                showToast(mewshtariMetaboxData.i18n.copied + tag);
            }).catch(() => {
                // Fallback
                const el = document.createElement('textarea');
                el.value = tag;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                showToast(mewshtariMetaboxData.i18n.copied + tag);
            });
        });
    });

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2000);
    }

    function updateStatus(type, message) {
        if (!statusCard) return;
        statusCard.className = 'mewshtari-status-card visible ' + type;
        statusText.textContent = message;

        if (type === 'sending') {
            statusIcon.innerHTML = '<span class="mewshtari-pulse-dot"></span>';
        } else if (type === 'success') {
            statusIcon.innerHTML = `
                <svg class="mewshtari-success-checkmark" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `;
        } else if (type === 'error') {
            statusIcon.innerHTML = '⚠️';
            statusCard.classList.remove('mewshtari-shake');
            // Trigger reflow to restart animation
            void statusCard.offsetWidth;
            statusCard.classList.add('mewshtari-shake');
        }
    }

    function hideStatus() {
        if (statusCard) {
            statusCard.classList.remove('visible');
        }
    }

    function populateTemplate() {
        const index = selector.value;
        if (index === '') {
            setEditorContent('');
            if (subjectInput) {
                subjectInput.value = 'Confirmation';
            }
            hideStatus();
            return;
        }

        const template = mewshtariMetaboxData.templates[index];
        if (!template) return;

        let html = template.html || '';
        html = html.replace(/\[name\]/g, mewshtariMetaboxData.orderData.name);
        html = html.replace(/\[product_title\]/g, mewshtariMetaboxData.orderData.product_title);
        html = html.replace(/\[product_link\]/g, mewshtariMetaboxData.orderData.product_link);
        html = html.replace(/\[order_date\]/g, mewshtariMetaboxData.orderData.order_date);

        setEditorContent(html);

        let subject = template.subject || 'Confirmation';
        subject = subject.replace(/\[name\]/g, mewshtariMetaboxData.orderData.name);
        subject = subject.replace(/\[product_title\]/g, mewshtariMetaboxData.orderData.product_title);
        subject = subject.replace(/\[product_link\]/g, mewshtariMetaboxData.orderData.product_link);
        subject = subject.replace(/\[order_date\]/g, mewshtariMetaboxData.orderData.order_date);

        if (subjectInput) {
            subjectInput.value = subject;
        }
        hideStatus();
    }

    selector.addEventListener('change', populateTemplate);

    // Default to the first template if nothing is selected yet
    if (selector.value === '' && mewshtariMetaboxData.templates && Object.keys(mewshtariMetaboxData.templates).length > 0) {
        selector.value = '0';
    }

    // Populate editor on page load
    populateTemplate();

    // If TinyMCE initializes later, ensure it loads the compiled default template content
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function(e) {
            if (e.editor.id === 'mewshtari_email_content') {
                e.editor.on('init', function() {
                    populateTemplate();
                });
            }
        });
    }

    function setEditorContent(content) {
        const editorId = 'mewshtari_email_content';
        const textarea = document.getElementById(editorId);
        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
            tinymce.get(editorId).setContent(content);
        }
        if (textarea) {
            textarea.value = content;
        }
    }

    function getEditorContent() {
        const editorId = 'mewshtari_email_content';
        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
            return tinymce.get(editorId).getContent();
        }
        const textarea = document.getElementById(editorId);
        return textarea ? textarea.value : '';
    }

    function setFormDisabled(disabled) {
        if (selector) selector.disabled = disabled;
        if (subjectInput) subjectInput.disabled = disabled;
        const editorId = 'mewshtari_email_content';
        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
            try {
                tinymce.get(editorId).mode.set(disabled ? 'readonly' : 'design');
            } catch(e) {}
        }
    }

    if (sendButton) {
        sendButton.addEventListener('click', function() {
            // If countdown is active, cancel it
            if (countdownTimer !== null) {
                clearInterval(countdownTimer);
                countdownTimer = null;
                sendButton.classList.remove('cancel-state');
                btnText.textContent = mewshtariMetaboxData.i18n.sendToCustomerText;
                setFormDisabled(false);
                updateStatus('error', mewshtariMetaboxData.i18n.sendingCancelled);
                return;
            }

            const index = selector.value;
            if (index === '') {
                updateStatus('error', mewshtariMetaboxData.i18n.selectTemplate);
                if (selector) selector.focus();
                return;
            }

            const subject = subjectInput.value.trim();
            if (!subject) {
                updateStatus('error', mewshtariMetaboxData.i18n.enterSubject);
                if (subjectInput) subjectInput.focus();
                return;
            }

            const body = getEditorContent().trim();
            if (!body) {
                updateStatus('error', mewshtariMetaboxData.i18n.enterContent);
                return;
            }

            // Start 10 seconds countdown
            secondsLeft = 10;
            setFormDisabled(true);
            sendButton.classList.add('cancel-state');
            btnText.textContent = mewshtariMetaboxData.i18n.cancelSending + ' (' + secondsLeft + 's)';
            updateStatus('sending', mewshtariMetaboxData.i18n.sendingIn);

            countdownTimer = setInterval(function() {
                secondsLeft--;
                if (secondsLeft > 0) {
                    btnText.textContent = mewshtariMetaboxData.i18n.cancelSending + ' (' + secondsLeft + 's)';
                    updateStatus('sending', mewshtariMetaboxData.i18n.sendingInSec + secondsLeft + mewshtariMetaboxData.i18n.secondsEllipsis);
                } else {
                    clearInterval(countdownTimer);
                    countdownTimer = null;
                    sendButton.classList.remove('cancel-state');
                    sendButton.disabled = true;
                    btnText.textContent = mewshtariMetaboxData.i18n.sendingText;
                    if (btnSpinner) btnSpinner.style.display = 'inline-block';
                    updateStatus('sending', mewshtariMetaboxData.i18n.transmittingText);

                    const params = new URLSearchParams();
                    params.append('action', 'mewshtari_send_custom_email');
                    params.append('order_id', mewshtariMetaboxData.orderId);
                    params.append('subject', subject);
                    params.append('body', body);
                    params.append('template_index', index);
                    params.append('security', mewshtariMetaboxData.nonce);

                    fetch(mewshtariMetaboxData.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: params.toString()
                    })
                    .then(response => response.json())
                    .then(res => {
                        if (res.success) {
                            if (btnSpinner) btnSpinner.style.display = 'none';
                            updateStatus('success', mewshtariMetaboxData.i18n.sendSuccessText);
                            setTimeout(function() {
                                location.reload();
                            }, 1200);
                        } else {
                            sendButton.disabled = false;
                            if (btnSpinner) btnSpinner.style.display = 'none';
                            btnText.textContent = mewshtariMetaboxData.i18n.sendToCustomerText;
                            setFormDisabled(false);
                            updateStatus('error', res.data || mewshtariMetaboxData.i18n.sendFailedText);
                        }
                    })
                    .catch(() => {
                        sendButton.disabled = false;
                        if (btnSpinner) btnSpinner.style.display = 'none';
                        btnText.textContent = mewshtariMetaboxData.i18n.sendToCustomerText;
                        setFormDisabled(false);
                        updateStatus('error', mewshtariMetaboxData.i18n.sendErrorText);
                    });
                }
            }, 1000);
        });
    }
});

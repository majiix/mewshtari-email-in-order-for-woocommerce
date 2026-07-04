document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('mewshtari-templates-list');
    const addButton = document.getElementById('mewshtari-add-template-btn');
    const form = document.getElementById('mewshtari-settings-form');
    const saveBtn = document.getElementById('mewshtari-save-settings-btn');
    const saveStatus = document.getElementById('mewshtari-save-status');

    if (typeof mewshtariSettingsData === 'undefined') return;
    const statuses = mewshtariSettingsData.statuses;

    if (!container || !addButton) return;

    addButton.addEventListener('click', function() {
        const index = container.children.length;
        const editorId = 'mewshtari_settings_html_new_' + Date.now() + '_' + index;
        const card = createTemplateCard(index, editorId);
        container.appendChild(card);

        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.initialize(editorId, {
                tinymce: {
                    wpautop: true,
                    plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wplink,wpdialogs',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,fullscreen,wp_adv',
                    toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                    setup: function(ed) {
                        ed.on('change', function() {
                            tinymce.triggerSave();
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        }
    });

    container.addEventListener('click', function(e) {
        const target = e.target;
        if (target.classList.contains('mewshtari-btn-danger') && !target.classList.contains('mewshtari-delete-confirm')) {
            const card = target.closest('.mewshtari-card');
            if (card) {
                let overlay = card.querySelector('.mewshtari-delete-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'mewshtari-delete-overlay';
                    overlay.innerHTML = `
                        <div class="mewshtari-delete-overlay-text">${mewshtariSettingsData.i18n.deleteConfirm}</div>
                        <div class="mewshtari-delete-overlay-buttons">
                            <button type="button" class="mewshtari-btn mewshtari-btn-danger mewshtari-delete-confirm">${mewshtariSettingsData.i18n.yesDelete}</button>
                            <button type="button" class="mewshtari-btn mewshtari-btn-secondary mewshtari-delete-cancel">${mewshtariSettingsData.i18n.cancel}</button>
                        </div>
                    `;
                    card.appendChild(overlay);
                }
                overlay.offsetHeight; // force reflow
                overlay.classList.add('active');
            }
        } else if (target.classList.contains('mewshtari-move-up')) {
            const card = target.closest('.mewshtari-card');
            const prev = card.previousElementSibling;
            if (prev) {
                swapCards(card, prev);
                updateCardIndices();
            }
        } else if (target.classList.contains('mewshtari-move-down')) {
            const card = target.closest('.mewshtari-card');
            const next = card.nextElementSibling;
            if (next) {
                swapCards(next, card);
                updateCardIndices();
            }
        } else if (target.classList.contains('mewshtari-delete-cancel')) {
            const overlay = target.closest('.mewshtari-delete-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        } else if (target.classList.contains('mewshtari-delete-confirm')) {
            const card = target.closest('.mewshtari-card');
            if (card) {
                const textarea = card.querySelector('textarea[name$="[html]"]');
                if (textarea && textarea.id && typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.remove(textarea.id);
                }
                card.remove();
                updateCardIndices();
                saveSettings();
            }
        }
    });

    function getEditorValue(id) {
        if (typeof tinymce !== 'undefined' && tinymce.get(id)) {
            return tinymce.get(id).getContent();
        }
        const el = document.getElementById(id);
        return el ? el.value : '';
    }

    function reinitEditor(id, content) {
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.initialize(id, {
                tinymce: {
                    wpautop: true,
                    plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wplink,wpdialogs',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,fullscreen,wp_adv',
                    toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                    setup: function(ed) {
                        ed.on('change', function() {
                            if (typeof tinymce !== 'undefined') {
                                tinymce.triggerSave();
                            }
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        }
        if (typeof tinymce !== 'undefined' && tinymce.get(id)) {
            tinymce.get(id).setContent(content);
        } else {
            const el = document.getElementById(id);
            if (el) el.value = content;
        }
    }

    function swapCards(card1, card2) {
        const textarea1 = card1.querySelector('textarea[name$="[html]"]');
        const textarea2 = card2.querySelector('textarea[name$="[html]"]');
        
        const id1 = textarea1 ? textarea1.id : null;
        const id2 = textarea2 ? textarea2.id : null;
        
        let val1 = '';
        let val2 = '';
        
        if (id1 && typeof wp !== 'undefined' && wp.editor) {
            val1 = getEditorValue(id1);
            wp.editor.remove(id1);
        }
        if (id2 && typeof wp !== 'undefined' && wp.editor) {
            val2 = getEditorValue(id2);
            wp.editor.remove(id2);
        }
        
        container.insertBefore(card1, card2);
        
        if (id1 && typeof wp !== 'undefined' && wp.editor) {
            reinitEditor(id1, val1);
        }
        if (id2 && typeof wp !== 'undefined' && wp.editor) {
            reinitEditor(id2, val2);
        }
    }

    function showSaveStatus(type, message) {
        if (!saveStatus) return;
        if (type === 'saving') {
            saveStatus.style.color = '#2563eb';
            saveStatus.innerHTML = `<span class="mewshtari-status-dot saving"></span>` + message;
        } else if (type === 'success') {
            saveStatus.style.color = '#16a34a';
            saveStatus.innerHTML = `<span class="mewshtari-status-dot success"></span>` + message;
            setTimeout(() => {
                saveStatus.innerHTML = '';
            }, 2500);
        } else if (type === 'error') {
            saveStatus.style.color = '#ef4444';
            saveStatus.innerHTML = `<span class="mewshtari-status-dot error"></span>` + message;
        }
    }

    function saveSettings() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        if (!form || !saveBtn) return;
        saveBtn.disabled = true;
        showSaveStatus('saving', mewshtariSettingsData.i18n.saving);

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        fetch(mewshtariSettingsData.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(res => {
            saveBtn.disabled = false;
            if (res.success) {
                showSaveStatus('success', res.data || mewshtariSettingsData.i18n.saveSuccess);
            } else {
                showSaveStatus('error', res.data || mewshtariSettingsData.i18n.saveFailed);
            }
        })
        .catch(() => {
            saveBtn.disabled = false;
            showSaveStatus('error', mewshtariSettingsData.i18n.saveError);
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }

    function updateCardIndices() {
        const cards = container.querySelectorAll('.mewshtari-card');
        cards.forEach((card, index) => {
            card.setAttribute('data-index', index);
            card.querySelectorAll('[name]').forEach(input => {
                const nameAttr = input.getAttribute('name');
                if (nameAttr) {
                    const updatedName = nameAttr.replace(/\[\d+\]/, '[' + index + ']');
                    input.setAttribute('name', updatedName);
                }
            });
        });
    }

    function createTemplateCard(index, editorId) {
        const card = document.createElement('div');
        card.className = 'mewshtari-card';
        card.setAttribute('data-index', index);

        let optionsHtml = '';
        for (const [key, val] of Object.entries(statuses)) {
            optionsHtml += `<option value="${key}">${val}</option>`;
        }

        card.innerHTML = `
            <div class="mewshtari-card-grid">
                <div class="mewshtari-field-group">
                    <label>${mewshtariSettingsData.i18n.nameLabel}</label>
                    <input type="text" name="mewshtari_email_templates[${index}][name]" value="" required />
                </div>
                <div class="mewshtari-field-group">
                    <label>${mewshtariSettingsData.i18n.subjectLabel}</label>
                    <input type="text" name="mewshtari_email_templates[${index}][subject]" value="" required />
                </div>
                <div class="mewshtari-field-group">
                    <label>${mewshtariSettingsData.i18n.statusLabel}</label>
                    <select name="mewshtari_email_templates[${index}][status]">
                        ${optionsHtml}
                    </select>
                </div>
            </div>
            <div class="mewshtari-field-group">
                <label>${mewshtariSettingsData.i18n.htmlLabel}</label>
                <div class="mewshtari-settings-editor-wrapper">
                    <textarea id="${editorId}" name="mewshtari_email_templates[${index}][html]" rows="8" required></textarea>
                </div>
            </div>
            <div class="mewshtari-card-actions">
                <button type="button" class="mewshtari-btn mewshtari-btn-secondary mewshtari-move-btn mewshtari-move-up" title="${mewshtariSettingsData.i18n.moveUp}">&uarr;</button>
                <button type="button" class="mewshtari-btn mewshtari-btn-secondary mewshtari-move-btn mewshtari-move-down" title="${mewshtariSettingsData.i18n.moveDown}">&darr;</button>
                <button type="button" class="mewshtari-btn mewshtari-btn-danger">${mewshtariSettingsData.i18n.delete}</button>
            </div>
        `;
        return card;
    }
});

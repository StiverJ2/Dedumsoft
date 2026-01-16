/**
 * CRUD Utilities for Dedumsoft
 * Provides modal dialogs and API helpers for Create, Update, Delete operations
 */

var DsCrud = (function() {
    'use strict';

    var toastContainer = null;
    var currentModal = null;

    function ensureToastContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'ds-toast-container';
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    function toast(message, type) {
        type = type || 'success';
        var container = ensureToastContainer();
        var el = document.createElement('div');
        el.className = 'ds-toast ds-toast--' + type;
        el.textContent = message;
        container.appendChild(el);
        setTimeout(function() {
            el.style.opacity = '0';
            setTimeout(function() {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 300);
        }, 3000);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function closeModal() {
        if (currentModal && currentModal.overlay && currentModal.overlay.parentNode) {
            currentModal.overlay.classList.remove('ds-modal--open');
            setTimeout(function() {
                if (currentModal && currentModal.overlay && currentModal.overlay.parentNode) {
                    currentModal.overlay.parentNode.removeChild(currentModal.overlay);
                }
                currentModal = null;
            }, 200);
        }
    }

    function openModal(options) {
        closeModal();

        var title = options.title || 'Modal';
        var bodyHtml = options.body || '';
        var onSave = options.onSave;
        var saveText = options.saveText || 'Guardar';
        var cancelText = options.cancelText || 'Cancelar';

        var overlay = document.createElement('div');
        overlay.className = 'ds-modal-overlay';

        var modal = document.createElement('div');
        modal.className = 'ds-modal';

        var header = document.createElement('div');
        header.className = 'ds-modal-header';
        header.innerHTML = '<h3>' + escapeHtml(title) + '</h3><button type="button" class="ds-modal-close" aria-label="Cerrar">&times;</button>';

        var body = document.createElement('div');
        body.className = 'ds-modal-body';
        body.innerHTML = bodyHtml;

        var footer = document.createElement('div');
        footer.className = 'ds-modal-footer';
        footer.innerHTML = '<button type="button" class="btn btn-secondary ds-modal-cancel">' + escapeHtml(cancelText) + '</button>' +
            '<button type="button" class="btn ds-modal-save">' + escapeHtml(saveText) + '</button>';

        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        currentModal = { overlay: overlay, modal: modal, body: body };

        requestAnimationFrame(function() {
            overlay.classList.add('ds-modal--open');
        });

        header.querySelector('.ds-modal-close').addEventListener('click', closeModal);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal();
        });
        footer.querySelector('.ds-modal-cancel').addEventListener('click', closeModal);
        footer.querySelector('.ds-modal-save').addEventListener('click', function() {
            if (onSave) {
                onSave(modal);
            }
        });

        var firstInput = body.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(function() { firstInput.focus(); }, 100);
        }

        return { close: closeModal, modal: modal, body: body, overlay: overlay };
    }

    function confirm(message, onConfirm) {
        openModal({
            title: 'Confirmar',
            body: '<p class="ds-confirm-text">' + escapeHtml(message) + '</p>',
            saveText: 'Eliminar',
            cancelText: 'Cancelar',
            onSave: function() {
                if (onConfirm) onConfirm();
                closeModal();
            }
        });
    }

    function field(options) {
        var name = options.name;
        var label = options.label || name;
        var type = options.type || 'text';
        var value = options.value !== undefined && options.value !== null ? options.value : '';
        var required = options.required ? ' required' : '';
        var requiredMark = options.required ? ' <span style="color:#c00">*</span>' : '';
        var placeholder = options.placeholder || '';
        var optionsList = options.options || [];
        var attrs = options.attrs || '';

        var html = '<div class="ds-form-group">';
        html += '<label for="field-' + escapeHtml(name) + '">' + escapeHtml(label) + requiredMark + '</label>';

        if (type === 'select') {
            html += '<select id="field-' + escapeHtml(name) + '" name="' + escapeHtml(name) + '" class="ds-form-control"' + required + '>';
            for (var i = 0; i < optionsList.length; i++) {
                var opt = optionsList[i];
                var optVal = typeof opt === 'object' ? opt.value : opt;
                var optLabel = typeof opt === 'object' ? opt.label : opt;
                var selected = String(optVal) === String(value) ? ' selected' : '';
                html += '<option value="' + escapeHtml(optVal) + '"' + selected + '>' + escapeHtml(optLabel) + '</option>';
            }
            html += '</select>';
        } else if (type === 'textarea') {
            html += '<textarea id="field-' + escapeHtml(name) + '" name="' + escapeHtml(name) + '" class="ds-form-control" placeholder="' + escapeHtml(placeholder) + '"' + required + ' ' + attrs + '>' + escapeHtml(value) + '</textarea>';
        } else {
            html += '<input type="' + escapeHtml(type) + '" id="field-' + escapeHtml(name) + '" name="' + escapeHtml(name) + '" class="ds-form-control" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(placeholder) + '"' + required + ' ' + attrs + '>';
        }

        html += '</div>';
        return html;
    }

    function api(url, method, data, onSuccess, onError) {
        var options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };
        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            options.body = JSON.stringify(data);
        }
        fetch(url, options)
            .then(function(res) {
                return res.json().then(function(json) {
                    return { ok: res.ok, status: res.status, data: json };
                });
            })
            .then(function(result) {
                if (result.ok && result.data.CODIGO !== 500) {
                    if (onSuccess) onSuccess(result.data);
                } else {
                    var errMsg = result.data.MENSAJE || result.data.message || 'Error en la operación';
                    if (onError) onError(errMsg);
                }
            })
            .catch(function(err) {
                if (onError) onError(err.message || 'Error de conexión');
            });
    }

    function actionButtons(id) {
        if (id === undefined || id === null) return '';
        var html = '<div class="ds-actions-cell">';
        html += '<button type="button" class="ds-action-btn ds-action-btn--edit" data-action="edit" data-id="' + escapeHtml(id) + '" title="Editar"><img src="assets/icons/fatcow/16/pencil.png" alt="Editar"></button>';
        html += '<button type="button" class="ds-action-btn ds-action-btn--delete" data-action="delete" data-id="' + escapeHtml(id) + '" title="Eliminar"><img src="assets/icons/fatcow/16/cross.png" alt="Eliminar"></button>';
        html += '</div>';
        return html;
    }

    return {
        toast: toast,
        openModal: openModal,
        closeModal: closeModal,
        confirm: confirm,
        field: field,
        api: api,
        actionButtons: actionButtons,
        escapeHtml: escapeHtml
    };
})();

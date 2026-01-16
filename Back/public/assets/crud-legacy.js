/**
 * CRUD Utilities for Dedumsoft - Legacy/IE8 Compatible Version
 * Uses only features available in IE8+
 */

var DsCrud = (function() {
    var toastContainer = null;
    var currentModal = null;

    // IE8 compatible event listener
    function addEvent(el, type, fn) {
        if (el.addEventListener) {
            el.addEventListener(type, fn, false);
        } else if (el.attachEvent) {
            el.attachEvent('on' + type, fn);
        }
    }

    // IE8 compatible class check
    function hasClass(el, cls) {
        return (' ' + el.className + ' ').indexOf(' ' + cls + ' ') > -1;
    }

    // IE8 compatible class add
    function addClass(el, cls) {
        if (!hasClass(el, cls)) {
            el.className = el.className ? el.className + ' ' + cls : cls;
        }
    }

    // IE8 compatible class remove
    function removeClass(el, cls) {
        el.className = el.className.replace(new RegExp('(^|\\s)' + cls + '(\\s|$)', 'g'), ' ').replace(/^\s+|\s+$/g, '');
    }

    // Get element by id
    function getById(id) {
        return document.getElementById(id);
    }

    // Query selector fallback for IE8
    function query(selector, parent) {
        parent = parent || document;
        if (parent.querySelector) {
            return parent.querySelector(selector);
        }
        // Basic fallback for class selectors
        if (selector.charAt(0) === '.') {
            var cls = selector.substring(1);
            var all = parent.getElementsByTagName('*');
            for (var i = 0; i < all.length; i++) {
                if (hasClass(all[i], cls)) return all[i];
            }
        }
        return null;
    }

    function ensureToastContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'ds-toast-container';
            // IE8 compatible positioning
            toastContainer.style.cssText = 'position:absolute;top:10px;right:10px;width:280px;z-index:2000;';
            document.body.appendChild(toastContainer);
        }
        // Update position on each call (for scroll)
        var scrollTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
        toastContainer.style.top = (scrollTop + 10) + 'px';
        return toastContainer;
    }

    function toast(message, type) {
        type = type || 'success';
        var container = ensureToastContainer();
        var el = document.createElement('div');
        el.className = 'ds-toast ds-toast--' + type;
        // IE8 compatible inline styles
        var bgColor = type === 'error' ? '#a94442' : '#4a7c4e';
        el.style.cssText = 'padding:10px 15px;margin-bottom:8px;background:' + bgColor + ';color:#fff;font-size:13px;border:1px solid #000;';
        if (el.textContent !== undefined) {
            el.textContent = message;
        } else {
            el.innerText = message;
        }
        container.appendChild(el);
        setTimeout(function() {
            // IE8 doesn't support opacity transitions, just remove
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 3000);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        if (div.textContent !== undefined) {
            div.textContent = String(text);
        } else {
            div.innerText = String(text);
        }
        return div.innerHTML;
    }

    function closeModal() {
        if (currentModal && currentModal.overlay && currentModal.overlay.parentNode) {
            currentModal.overlay.parentNode.removeChild(currentModal.overlay);
        }
        if (currentModal && currentModal.modal && currentModal.modal.parentNode) {
            currentModal.modal.parentNode.removeChild(currentModal.modal);
        }
        currentModal = null;
    }

    // Get scroll position for IE8
    function getScrollTop() {
        return document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    function getScrollLeft() {
        return document.documentElement.scrollLeft || document.body.scrollLeft || 0;
    }

    function openModal(options) {
        closeModal();

        var title = options.title || 'Modal';
        var bodyHtml = options.body || '';
        var onSave = options.onSave;
        var saveText = options.saveText || 'Guardar';
        var cancelText = options.cancelText || 'Cancelar';

        // Get document and viewport dimensions for IE8
        var docWidth = Math.max(
            document.body.scrollWidth || 0,
            document.documentElement.scrollWidth || 0,
            document.body.offsetWidth || 0,
            document.documentElement.offsetWidth || 0,
            document.documentElement.clientWidth || 0
        );
        var docHeight = Math.max(
            document.body.scrollHeight || 0,
            document.documentElement.scrollHeight || 0,
            document.body.offsetHeight || 0,
            document.documentElement.offsetHeight || 0,
            document.documentElement.clientHeight || 0
        );
        var viewportWidth = document.documentElement.clientWidth || document.body.clientWidth || 800;
        var scrollTop = getScrollTop();

        // Create overlay - cover entire document
        var overlay = document.createElement('div');
        overlay.className = 'ds-modal-overlay ds-modal--open';
        overlay.style.cssText = 'position:absolute;top:0;left:0;width:' + docWidth + 'px;height:' + docHeight + 'px;background:#000;z-index:1000;';
        // IE8 opacity filter
        overlay.style.filter = 'alpha(opacity=50)';

        // Calculate modal position - center horizontally in viewport, offset from scroll top
        var modalWidth = 500;
        var modalLeft = Math.floor((viewportWidth - modalWidth) / 2);
        if (modalLeft < 10) modalLeft = 10;
        var modalTop = scrollTop + 50;

        var modal = document.createElement('div');
        modal.className = 'ds-modal';
        modal.style.cssText = 'position:absolute;top:' + modalTop + 'px;left:' + modalLeft + 'px;width:' + modalWidth + 'px;z-index:1001;background:#fff;border:2px solid #333;';

        var header = document.createElement('div');
        header.className = 'ds-modal-header';
        header.style.cssText = 'padding:12px 15px;border-bottom:1px solid #ccc;background:#f5f5f5;zoom:1;';
        header.innerHTML = '<h3 style="margin:0;float:left;font-size:16px;font-weight:bold;color:#333;">' + escapeHtml(title) + '</h3><button type="button" class="ds-modal-close" style="float:right;background:none;border:none;font-size:20px;cursor:pointer;font-weight:bold;color:#666;">&times;</button><div style="clear:both;"></div>';

        var body = document.createElement('div');
        body.className = 'ds-modal-body';
        body.style.cssText = 'padding:15px;background:#fff;';
        body.innerHTML = bodyHtml;

        var footer = document.createElement('div');
        footer.className = 'ds-modal-footer';
        footer.style.cssText = 'padding:12px 15px;border-top:1px solid #ccc;background:#f5f5f5;text-align:right;zoom:1;';
        footer.innerHTML = '<button type="button" class="btn btn-secondary ds-modal-cancel" style="margin-right:8px;padding:6px 12px;cursor:pointer;">' + escapeHtml(cancelText) + '</button>' +
            '<button type="button" class="btn btn-add ds-modal-save" style="padding:6px 12px;background:#d4af37;color:#fff;border:none;cursor:pointer;font-weight:bold;">' + escapeHtml(saveText) + '</button>';

        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        
        // Add overlay first, then modal as sibling (not child) for IE8
        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        currentModal = { overlay: overlay, modal: modal, body: body };

        // Close button
        var closeBtn = query('.ds-modal-close', header);
        if (closeBtn) addEvent(closeBtn, 'click', closeModal);
        
        // Overlay click
        addEvent(overlay, 'click', function(e) {
            e = e || window.event;
            var target = e.target || e.srcElement;
            if (target === overlay) closeModal();
        });

        // Cancel button
        var cancelBtn = query('.ds-modal-cancel', footer);
        if (cancelBtn) addEvent(cancelBtn, 'click', closeModal);
        
        // Save button
        var saveBtn = query('.ds-modal-save', footer);
        if (saveBtn) {
            addEvent(saveBtn, 'click', function() {
                if (onSave) {
                    onSave(modal);
                }
            });
        }

        // Focus first input
        var inputs = body.getElementsByTagName('input');
        if (inputs.length === 0) inputs = body.getElementsByTagName('select');
        if (inputs.length > 0) {
            setTimeout(function() { inputs[0].focus(); }, 100);
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
        if (!options) options = {};
        var name = options.name || '';
        var label = options.label || name;
        var type = options.type ? String(options.type) : 'text';
        var value = (options.value !== undefined && options.value !== null) ? String(options.value) : '';
        var required = options.required ? ' required' : '';
        var requiredMark = options.required ? ' <span style="color:#c00">*</span>' : '';
        var placeholder = options.placeholder || '';
        var optionsList = options.options;
        var attrs = options.attrs || '';

        // Ensure optionsList is an array
        if (!optionsList || typeof optionsList.length === 'undefined') {
            optionsList = [];
        }

        var html = '<div class="ds-form-group">';
        html += '<label for="field-' + escapeHtml(name) + '">' + escapeHtml(label) + requiredMark + '</label>';

        // Check if this should be a select
        var isSelect = (type == 'select' || type == 'SELECT') && optionsList.length > 0;
        
        if (isSelect) {
            html += '<select id="field-' + escapeHtml(name) + '" name="' + escapeHtml(name) + '" class="ds-form-control" style="width:100%;height:28px;padding:4px 8px;font-size:13px;border:1px solid #999;background:#fff;display:block;"' + required + '>';
            for (var i = 0; i < optionsList.length; i++) {
                var opt = optionsList[i];
                var optVal, optLabel;
                if (opt && typeof opt == 'object') {
                    optVal = opt.value !== undefined ? opt.value : '';
                    optLabel = opt.label !== undefined ? opt.label : optVal;
                } else {
                    optVal = opt;
                    optLabel = opt;
                }
                var isSelected = (String(optVal) == value);
                html += '<option value="' + escapeHtml(optVal) + '"' + (isSelected ? ' selected="selected"' : '') + '>' + escapeHtml(optLabel) + '</option>';
            }
            html += '</select>';
        } else if (type == 'textarea') {
            html += '<textarea id="field-' + escapeHtml(name) + '" name="' + escapeHtml(name) + '" class="ds-form-control" placeholder="' + escapeHtml(placeholder) + '"' + required + ' ' + attrs + '>' + escapeHtml(value) + '</textarea>';
        } else {
            html += '<input type="' + escapeHtml(type) + '" id="field-' + escapeHtml(name) + '" name="' + escapeHtml(name) + '" class="ds-form-control" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(placeholder) + '"' + required + ' ' + attrs + '>';
        }

        html += '</div>';
        return html;
    }

    // IE8 compatible XHR
    function createXHR() {
        if (window.XMLHttpRequest) {
            return new XMLHttpRequest();
        }
        try {
            return new ActiveXObject('Msxml2.XMLHTTP.6.0');
        } catch (e1) {
            try {
                return new ActiveXObject('Msxml2.XMLHTTP.3.0');
            } catch (e2) {
                try {
                    return new ActiveXObject('Microsoft.XMLHTTP');
                } catch (e3) {
                    return null;
                }
            }
        }
    }

    // Simple JSON parse for IE8
    function parseJSON(str) {
        if (window.JSON && JSON.parse) {
            return JSON.parse(str);
        }
        // Fallback - use eval with some safety (not ideal but works for IE8)
        return eval('(' + str + ')');
    }

    // Simple JSON stringify for IE8
    function stringifyJSON(obj) {
        if (window.JSON && JSON.stringify) {
            return JSON.stringify(obj);
        }
        // Basic fallback
        var parts = [];
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                var val = obj[key];
                if (typeof val === 'string') {
                    parts.push('"' + key + '":"' + val.replace(/"/g, '\\"') + '"');
                } else if (val === null) {
                    parts.push('"' + key + '":null');
                } else {
                    parts.push('"' + key + '":' + val);
                }
            }
        }
        return '{' + parts.join(',') + '}';
    }

    function api(url, method, data, onSuccess, onError) {
        var xhr = createXHR();
        if (!xhr) {
            if (onError) onError('No se pudo crear la conexion');
            return;
        }
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                var response = null;
                try {
                    response = parseJSON(xhr.responseText);
                } catch (e) {
                    response = { CODIGO: xhr.status, MENSAJE: 'Error de respuesta del servidor' };
                }
                if (xhr.status >= 200 && xhr.status < 300 && response.CODIGO !== 500) {
                    if (onSuccess) onSuccess(response);
                } else {
                    var errMsg = response.MENSAJE || response.message || 'Error en la operacion';
                    if (onError) onError(errMsg);
                }
            }
        };
        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            xhr.send(stringifyJSON(data));
        } else {
            xhr.send(null);
        }
    }

    function actionButtons(id) {
        if (id === undefined || id === null) return '';
        var html = '<div class="ds-actions-cell">';
        html += '<button type="button" class="ds-action-btn ds-action-btn--edit" data-action="edit" data-id="' + escapeHtml(id) + '" title="Editar"><img src="assets/icons/fatcow/16/pencil.png" alt="E"></button>';
        html += '<button type="button" class="ds-action-btn ds-action-btn--delete" data-action="delete" data-id="' + escapeHtml(id) + '" title="Eliminar"><img src="assets/icons/fatcow/16/cross.png" alt="X"></button>';
        html += '</div>';
        return html;
    }

    // Helper to get text content from element (IE8 compatible)
    function getTextContent(el) {
        return el.textContent !== undefined ? el.textContent : el.innerText;
    }

    // Helper for legacy tables - add action buttons to existing rows
    // Now only passes ID to callbacks, not row data (for security - avoid XSS from HTML)
    function initLegacyTable(tableId, options) {
        options = options || {};
        var table = getById(tableId);
        if (!table) return;
        
        var rows = table.getElementsByTagName('tr');
        for (var i = 0; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName('td');
            if (cells.length > 0) {
                // First cell should be the ID
                var id = getTextContent(cells[0]);
                id = id.replace(/^\s+|\s+$/g, '');
                
                // Find or create the actions cell
                var actionsCell = cells[cells.length - 1];
                if (hasClass(actionsCell, 'ds-actions-col')) {
                    actionsCell.innerHTML = actionButtons(id);
                }
            }
        }
        
        // Attach click handlers for edit/delete
        addEvent(table, 'click', function(e) {
            e = e || window.event;
            var target = e.target || e.srcElement;
            
            // Find the button
            while (target && target !== table) {
                if (target.tagName === 'BUTTON' && hasClass(target, 'ds-action-btn')) {
                    var action = target.getAttribute('data-action');
                    var rowId = target.getAttribute('data-id');
                    
                    // Only pass ID to callbacks - they should fetch data from API
                    if (action === 'edit' && options.onEdit) {
                        options.onEdit(rowId);
                    } else if (action === 'delete' && options.onDelete) {
                        options.onDelete(rowId);
                    }
                    break;
                }
                target = target.parentNode;
            }
        });
    }

    // Helper to get form data
    function getFormData(formOrModal) {
        var data = {};
        var inputs = formOrModal.getElementsByTagName('input');
        var selects = formOrModal.getElementsByTagName('select');
        var textareas = formOrModal.getElementsByTagName('textarea');
        
        var process = function(el) {
            var name = el.name || el.getAttribute('name');
            if (!name) return;
            if (el.type === 'checkbox') {
                data[name] = el.checked;
            } else {
                data[name] = el.value;
            }
        };
        
        for (var i = 0; i < inputs.length; i++) process(inputs[i]);
        for (var j = 0; j < selects.length; j++) process(selects[j]);
        for (var k = 0; k < textareas.length; k++) process(textareas[k]);
        
        return data;
    }

    // Helper to validate required fields
    function validateForm(formOrModal) {
        var inputs = formOrModal.getElementsByTagName('input');
        var selects = formOrModal.getElementsByTagName('select');
        
        var checkRequired = function(el) {
            if (el.getAttribute('required') !== null || el.required) {
                if (!el.value || el.value === '') {
                    el.style.borderColor = '#dc3545';
                    el.focus();
                    return false;
                }
                el.style.borderColor = '';
            }
            return true;
        };
        
        for (var i = 0; i < inputs.length; i++) {
            if (!checkRequired(inputs[i])) return false;
        }
        for (var j = 0; j < selects.length; j++) {
            if (!checkRequired(selects[j])) return false;
        }
        return true;
    }

    return {
        toast: toast,
        openModal: openModal,
        closeModal: closeModal,
        confirm: confirm,
        field: field,
        api: api,
        actionButtons: actionButtons,
        escapeHtml: escapeHtml,
        initLegacyTable: initLegacyTable,
        getFormData: getFormData,
        validateForm: validateForm,
        addEvent: addEvent,
        getById: getById,
        query: query
    };
})();

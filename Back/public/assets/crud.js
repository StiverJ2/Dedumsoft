/**
 * ============================================================================
 * UTILIDADES CRUD PARA DEDUMSOFT
 * ============================================================================
 * 
 * Biblioteca JavaScript que proporciona funcionalidades de modales y CRUD
 * para operaciones de Crear, Actualizar y Eliminar.
 * 
 * COMPONENTES PRINCIPALES:
 * - Sistema de modales dinámicos
 * - Notificaciones toast (requiere Notyf)
 * - Llamadas API con Axios
 * - Generador de campos de formulario
 * - Botones de acción para tablas
 * 
 * DEPENDENCIAS:
 * - Notyf (notificaciones)
 * - Axios (peticiones HTTP)
 * 
 * USO:
 *   DsCrud.openModal({ title: 'Nuevo', body: html, onSave: fn });
 *   DsCrud.toast('Mensaje', 'success');
 *   DsCrud.api('/api/endpoint', 'POST', data, onSuccess, onError);
 * 
 * @package Dedumsoft\Assets
 * @author  Equipo Dedumsoft
 */

const DsCrud = (() => {
    'use strict';

    // Estado interno del módulo
    let currentModal = null;  // Referencia al modal activo
    let notyf = null;         // Instancia de Notyf

    // =========================================================================
    // SISTEMA DE NOTIFICACIONES TOAST
    // =========================================================================
    
    /**
     * Inicializa la instancia de Notyf para notificaciones.
     * Solo se crea una vez (singleton).
     * @returns {Notyf|null} Instancia de Notyf o null si no está disponible
     */
    const initNotyf = () => {
        if (!notyf && window.Notyf) {
            notyf = new Notyf({
                duration: 3000,
                position: { x: 'right', y: 'top' },
                dismissible: true
            });
        }
        return notyf;
    };

    /**
     * Muestra una notificación toast al usuario.
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo: 'success' (default) o 'error'
     */
    const toast = (message, type = 'success') => {
        const notyfInstance = initNotyf();
        if (notyfInstance) {
            if (type === 'error') {
                notyfInstance.error(message);
            } else {
                notyfInstance.success(message);
            }
        } else {
            // Fallback a consola si Notyf no está disponible
            console[type === 'error' ? 'error' : 'log'](message);
        }
    };

    const escapeHtml = (text) => {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    };

    const closeModal = () => {
        if (currentModal && currentModal.overlay && currentModal.overlay.parentNode) {
            currentModal.overlay.classList.remove('ds-modal--open');
            setTimeout(() => {
                if (currentModal && currentModal.overlay && currentModal.overlay.parentNode) {
                    currentModal.overlay.parentNode.removeChild(currentModal.overlay);
                }
                currentModal = null;
            }, 200);
        }
    };

    const openModal = (options) => {
        closeModal();

        const title = options.title || 'Modal';
        const bodyHtml = options.body || '';
        const onSave = options.onSave;
        const saveText = options.saveText || 'Guardar';
        const cancelText = options.cancelText || 'Cancelar';

        const overlay = document.createElement('div');
        overlay.className = 'ds-modal-overlay';

        const modal = document.createElement('div');
        modal.className = 'ds-modal';

        const header = document.createElement('div');
        header.className = 'ds-modal-header';
        header.innerHTML = `<h3>${escapeHtml(title)}</h3><button type="button" class="ds-modal-close" aria-label="Cerrar">&times;</button>`;

        const body = document.createElement('div');
        body.className = 'ds-modal-body';
        body.innerHTML = bodyHtml;

        const footer = document.createElement('div');
        footer.className = 'ds-modal-footer';
        footer.innerHTML = `<button type="button" class="btn btn-secondary ds-modal-cancel">${escapeHtml(cancelText)}</button>
            <button type="button" class="btn ds-modal-save">${escapeHtml(saveText)}</button>`;

        modal.appendChild(header);
        modal.appendChild(body);
        modal.appendChild(footer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        currentModal = { overlay, modal, body };

        requestAnimationFrame(() => {
            overlay.classList.add('ds-modal--open');
        });

        header.querySelector('.ds-modal-close').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });
        footer.querySelector('.ds-modal-cancel').addEventListener('click', closeModal);
        footer.querySelector('.ds-modal-save').addEventListener('click', () => {
            if (onSave) {
                const formData = getFormData(modal);
                onSave(modal, closeModal, formData);
            }
        });

        const firstInput = body.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }

        return { close: closeModal, modal, body, overlay };
    };

    const getFormData = (formOrModal) => {
        const data = {};
        const inputs = formOrModal.querySelectorAll('input, select, textarea');
        
        inputs.forEach((el) => {
            const name = el.name || el.getAttribute('name');
            if (!name) return;
            
            if (el.type === 'checkbox') {
                data[name] = el.checked;
            } else if (el.type === 'radio') {
                if (el.checked) {
                    data[name] = el.value;
                }
            } else {
                data[name] = el.value;
            }
        });
        
        return data;
    };

    const confirm = (message, onConfirm) => {
        openModal({
            title: 'Confirmar',
            body: `<p class="ds-confirm-text">${escapeHtml(message)}</p>`,
            saveText: 'Eliminar',
            cancelText: 'Cancelar',
            onSave: () => {
                if (onConfirm) onConfirm();
                closeModal();
            }
        });
    };

    const field = (options) => {
        const name = options.name;
        const label = options.label || name;
        const type = options.type || 'text';
        const value = options.value !== undefined && options.value !== null ? options.value : '';
        const required = options.required ? ' required' : '';
        const requiredMark = options.required ? ' <span style="color:#c00">*</span>' : '';
        const placeholder = options.placeholder || '';
        const optionsList = options.options || [];
        const attrs = options.attrs || '';

        let html = '<div class="ds-form-group">';
        html += `<label for="field-${escapeHtml(name)}">${escapeHtml(label)}${requiredMark}</label>`;

        if (type === 'select') {
            html += `<select id="field-${escapeHtml(name)}" name="${escapeHtml(name)}" class="ds-form-control"${required}>`;
            optionsList.forEach((opt) => {
                const optVal = typeof opt === 'object' ? opt.value : opt;
                const optLabel = typeof opt === 'object' ? opt.label : opt;
                const selected = String(optVal) === String(value) ? ' selected' : '';
                html += `<option value="${escapeHtml(optVal)}"${selected}>${escapeHtml(optLabel)}</option>`;
            });
            html += '</select>';
        } else if (type === 'textarea') {
            html += `<textarea id="field-${escapeHtml(name)}" name="${escapeHtml(name)}" class="ds-form-control" placeholder="${escapeHtml(placeholder)}"${required} ${attrs}>${escapeHtml(value)}</textarea>`;
        } else {
            html += `<input type="${escapeHtml(type)}" id="field-${escapeHtml(name)}" name="${escapeHtml(name)}" class="ds-form-control" value="${escapeHtml(value)}" placeholder="${escapeHtml(placeholder)}"${required} ${attrs}>`;
        }

        html += '</div>';
        return html;
    };

    const api = (url, method, data, onSuccess, onError) => {
        const config = {
            method: method,
            url: url,
            headers: { 'Content-Type': 'application/json' }
        };
        
        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            config.data = data;
        }
        
        axios(config)
            .then((response) => {
                const responseData = response.data;
                if (responseData.CODIGO !== 500) {
                    if (onSuccess) onSuccess(true, responseData);
                } else {
                    const errMsg = responseData.MENSAJE || responseData.message || 'Error en la operación';
                    if (onError) onError(errMsg);
                    else if (onSuccess) onSuccess(false, responseData);
                }
            })
            .catch((error) => {
                const errMsg = error.response?.data?.MENSAJE || error.message || 'Error de conexión';
                if (onError) onError(errMsg);
                else if (onSuccess) onSuccess(false, { MENSAJE: errMsg });
            });
    };

    const actionButtons = (id) => {
        if (id === undefined || id === null) return '';
        const useEmoji = typeof window !== 'undefined' && window.DEDUMSOFT_ICON_MODE === 'emoji';
        let html = '<div class="ds-actions-cell">';
        if (useEmoji) {
            html += `<button type="button" class="ds-action-btn ds-action-btn--edit" data-action="edit" data-id="${escapeHtml(id)}" title="Editar" aria-label="Editar">✏️</button>`;
            html += `<button type="button" class="ds-action-btn ds-action-btn--delete" data-action="delete" data-id="${escapeHtml(id)}" title="Eliminar" aria-label="Eliminar">🗑️</button>`;
        } else {
            html += `<button type="button" class="ds-action-btn ds-action-btn--edit" data-action="edit" data-id="${escapeHtml(id)}" title="Editar"><img src="assets/icons/fatcow/16/pencil.png" alt="Editar"></button>`;
            html += `<button type="button" class="ds-action-btn ds-action-btn--delete" data-action="delete" data-id="${escapeHtml(id)}" title="Eliminar"><img src="assets/icons/fatcow/16/cross.png" alt="Eliminar"></button>`;
        }
        html += '</div>';
        return html;
    };

    return {
        toast: toast,
        openModal: openModal,
        closeModal: closeModal,
        confirm: confirm,
        field: field,
        api: api,
        actionButtons: actionButtons,
        escapeHtml: escapeHtml,
        getFormData: getFormData
    };
})();

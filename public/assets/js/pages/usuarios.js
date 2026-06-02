/**
 * ============================================================================
 * USUARIOS — JavaScript Moderno (DataTables)
 * ============================================================================
 */

const currentUserId = window._currentUserId || 0;

const formatRol = (rol) => {
    if (!rol) return '';
    const key = String(rol).toLowerCase();
    const label = DsCrud.escapeHtml(String(rol).charAt(0).toUpperCase() + String(rol).slice(1));
    let cls = 'ds-badge--neutral';
    if (key === 'administrador') cls = 'ds-badge--danger';
    else if (key === 'operador') cls = 'ds-badge--info';
    else if (key === 'lectura') cls = 'ds-badge--muted';
    return `<span class="ds-badge ${cls}">${label}</span>`;
};

const formatActivo = (v) => {
    if (v) return '<span class="ds-badge ds-badge--success">Activo</span>';
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
};

const renderAcciones = (data, type, row) => {
    if (type !== 'display') return '';
    const isActive = !!row.activo;
    const label = isActive ? 'Desactivar' : 'Activar';
    const isSelf = Number(row.id_usuario) === Number(currentUserId);
    const btnClass = isActive ? 'ds-action-btn--delete' : 'ds-action-btn--edit';
    const disabledAttr = isSelf && isActive ? ' disabled aria-disabled="true"' : '';
    let icon = '';
    if (window.DEDUMSOFT_ICON_MODE === 'emoji') {
        icon = isActive ? '🚫' : '✅';
    } else {
        const img = isActive ? 'cross.png' : 'arrow_refresh.png';
        icon = `<img src="assets/icons/fatcow/16/${img}" alt="${label}">`;
    }
    return `<div class="ds-actions-cell">
        <button type="button" class="ds-action-btn ${btnClass}" data-action="toggle" data-id="${DsCrud.escapeHtml(row.id_usuario)}" data-activo="${isActive ? '1' : '0'}" title="${label}" aria-label="${label}"${disabledAttr}>${icon}</button>
    </div>`;
};

let usuariosTable;
let especialidadesOptions = null;
const roleOptions = [
    { value: 1, label: 'Administrador' },
    { value: 2, label: 'Operador' },
    { value: 3, label: 'Lectura' }
];

const loadEspecialidades = (done) => {
    if (Array.isArray(especialidadesOptions)) {
        done(especialidadesOptions);
        return;
    }
    DsCrud.api('api/catalogos/opciones.php?tipo=especialidades', 'GET', null, (success, resp) => {
        if (!success) {
            DsCrud.toast(resp.MENSAJE || 'No se pudieron cargar las especialidades.', 'error');
            especialidadesOptions = [];
            done(especialidadesOptions);
            return;
        }
        const data = Array.isArray(resp.DATOS) ? resp.DATOS : [];
        especialidadesOptions = data.map((item) => ({
            value: item.value,
            label: item.label
        }));
        done(especialidadesOptions);
    }, (err) => {
        DsCrud.toast(err || 'No se pudieron cargar las especialidades.', 'error');
        especialidadesOptions = [];
        done(especialidadesOptions);
    });
};

const openCreateModal = () => {
    loadEspecialidades((especialidades) => {
        const especialidadOptions = [{ value: '', label: 'Seleccione una especialidad' }].concat(especialidades);
        const operatorFields = [
            DsCrud.field({ name: 'apellido', label: 'Apellido (Operador)', placeholder: 'Requerido para Operador' }),
            DsCrud.field({ name: 'especialidad_id', label: 'Especialidad (Operador)', type: 'select', options: especialidadOptions }),
            DsCrud.field({ name: 'telefono', label: 'Teléfono (Operador)', placeholder: 'Opcional' })
        ].join('');

        const body = [
            DsCrud.field({ name: 'username', label: 'Usuario', required: true, placeholder: 'usuario' }),
            DsCrud.field({ name: 'nombre', label: 'Nombre', required: true, placeholder: 'Nombre' }),
            '<div class="ds-operator-fields" style="display:none;">' + operatorFields + '</div>',
            DsCrud.field({ name: 'email', label: 'Email', type: 'email', placeholder: 'correo@dominio.com' }),
            DsCrud.field({ name: 'rolid', label: 'Rol', type: 'select', required: true, options: roleOptions }),
            DsCrud.field({ name: 'password', label: 'Contraseña', type: 'password', required: true, attrs: 'autocomplete="new-password"' }),
            DsCrud.field({ name: 'password_confirm', label: 'Confirmar contraseña', type: 'password', required: true, attrs: 'autocomplete="new-password"' }),
            '<p class="muted">Campos de artesano se usan solo para rol Operador.</p>'
        ].join('');

        const modalRef = DsCrud.openModal({
            title: 'Nuevo usuario',
            body,
            saveText: 'Crear',
            cancelText: 'Cancelar',
            onSave: (modalEl, close, formData) => {
                const username = (formData.username || '').trim();
                const nombre = (formData.nombre || '').trim();
                const apellido = (formData.apellido || '').trim();
                const especialidadId = Number(formData.especialidad_id || 0);
                const telefono = (formData.telefono || '').trim();
                const email = (formData.email || '').trim();
                const rolid = Number(formData.rolid || 0);
                const password = String(formData.password || '');
                const passwordConfirm = String(formData.password_confirm || '');

                if (!username || !nombre || !rolid || !password) {
                    DsCrud.toast('Complete los campos requeridos.', 'error');
                    return;
                }
                if (password.length < 8) {
                    DsCrud.toast('La contraseña debe tener al menos 8 caracteres.', 'error');
                    return;
                }
                if (password !== passwordConfirm) {
                    DsCrud.toast('Las contraseñas no coinciden.', 'error');
                    return;
                }
                if (rolid === 2 && !apellido) {
                    DsCrud.toast('Apellido requerido para rol Operador.', 'error');
                    return;
                }

                DsCrud.api('api/usuarios.php', 'POST', {
                    username,
                    nombre,
                    apellido,
                    especialidad_id: especialidadId > 0 ? especialidadId : null,
                    telefono,
                    email,
                    rolid,
                    password
                }, (success, resp) => {
                    if (success) {
                        DsCrud.toast(resp.MENSAJE || 'Usuario creado');
                        if (usuariosTable) {
                            usuariosTable.ajax.reload(null, false);
                        }
                        close();
                    } else {
                        DsCrud.toast(resp.MENSAJE || 'Error al crear usuario', 'error');
                    }
                });
            }
        });

        const modalEl = modalRef && modalRef.modal ? modalRef.modal : null;
        if (!modalEl) return;
        const rolSelect = modalEl.querySelector('select[name="rolid"]');
        const operatorWrap = modalEl.querySelector('.ds-operator-fields');
        const apellidoInput = modalEl.querySelector('input[name="apellido"]');
        const especialidadSelect = modalEl.querySelector('select[name="especialidad_id"]');
        const telefonoInput = modalEl.querySelector('input[name="telefono"]');

        const toggleOperatorFields = () => {
            if (!rolSelect || !operatorWrap) return;
            const isOperador = Number(rolSelect.value) === 2;
            operatorWrap.style.display = isOperador ? 'block' : 'none';
            if (apellidoInput) {
                if (isOperador) {
                    apellidoInput.setAttribute('required', 'required');
                } else {
                    apellidoInput.removeAttribute('required');
                    apellidoInput.value = '';
                }
            }
            if (!isOperador) {
                if (especialidadSelect) especialidadSelect.value = '';
                if (telefonoInput) telefonoInput.value = '';
            }
        };

        if (rolSelect) {
            rolSelect.addEventListener('change', toggleOperatorFields);
        }
        toggleOperatorFields();
    });
};

$(() => {
    usuariosTable = $('#usuarios-table').DataTable({
        ajax: {
            url: 'api/reportes/reportes_usuarios.php',
            dataSrc: 'DATOS'
        },
        columns: [
            { data: 'id_usuario' },
            { data: 'username' },
            { data: 'nombre' },
            { data: 'rol', render: formatRol },
            { data: 'activo', render: formatActivo },
            { data: null, orderable: false, searchable: false, render: renderAcciones }
        ],
        language: { url: 'assets/dataTables.es-ES.json' }
    });

    const applyRolFilter = () => {
        const rolEl = document.getElementById('usuario-rol-modern');
        const value = rolEl ? (rolEl.value || '') : '';
        if (!usuariosTable) return;
        if (!value) {
            usuariosTable.column(3).search('').draw();
            return;
        }
        const escapeRegex = $.fn.dataTable.util.escapeRegex || function (v) {
            return v.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        };
        const safe = escapeRegex(value);
        usuariosTable.column(3).search('^' + safe + '$', true, false).draw();
    };

    const openToggleModal = (row) => {
        if (!row) return;
        const isActive = !!row.activo;
        if (Number(row.id_usuario) === Number(currentUserId) && isActive) {
            DsCrud.toast('No puedes desactivar tu propio usuario.', 'error');
            return;
        }
        const actionLabel = isActive ? 'Desactivar' : 'Activar';
        const username = row.username || '';
        DsCrud.openModal({
            title: `${actionLabel} usuario`,
            body: `<p>¿Desea ${actionLabel.toLowerCase()} al usuario <strong>${DsCrud.escapeHtml(username)}</strong>?</p>`,
            saveText: actionLabel,
            cancelText: 'Cancelar',
            onSave: (modalEl, close) => {
                DsCrud.api('api/usuarios.php', 'PATCH', {
                    id: row.id_usuario,
                    activo: !isActive
                }, (success, resp) => {
                    if (success) {
                        DsCrud.toast(resp.MENSAJE || 'Usuario actualizado');
                        usuariosTable.ajax.reload(null, false);
                        close();
                    } else {
                        DsCrud.toast(resp.MENSAJE || 'Error al actualizar', 'error');
                    }
                });
            }
        });
    };

    $('#usuarios-table').on('click', '.ds-action-btn[data-action="toggle"]', function () {
        const row = usuariosTable.row($(this).closest('tr')).data();
        openToggleModal(row);
    });

    $('#usuarios-create-btn').on('click', () => {
        openCreateModal();
    });

    const rolFilterBtn = document.getElementById('usuarios-filtrar-modern');
    const rolClearBtn = document.getElementById('usuarios-limpiar-modern');
    const rolSelect = document.getElementById('usuario-rol-modern');
    if (rolFilterBtn) {
        rolFilterBtn.addEventListener('click', applyRolFilter);
    }
    if (rolClearBtn) {
        rolClearBtn.addEventListener('click', () => {
            if (rolSelect) rolSelect.value = '';
            applyRolFilter();
        });
    }
    if (rolSelect) {
        rolSelect.addEventListener('change', applyRolFilter);
    }
});

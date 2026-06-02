/**
 * ============================================================================
 * USUARIOS — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function () {
    'use strict';

    if (window.DedumTableSort) DedumTableSort.init('usuarios-table');

    var currentUserId = window._currentUserId || 0;
    var table = DsCrud.getById('usuarios-table');
    if (!table) return;

    function getText(el) {
        if (!el) return '';
        return el.textContent !== undefined ? el.textContent : el.innerText;
    }

    function openToggleModal(id, username, isActive) {
        if (!id) return;
        var numericId = parseInt(id, 10);
        if (!isNaN(numericId) && numericId === currentUserId && isActive) {
            DsCrud.toast('No puedes desactivar tu propio usuario.', 'error');
            return;
        }
        var actionLabel = isActive ? 'Desactivar' : 'Activar';
        DsCrud.openModal({
            title: actionLabel + ' usuario',
            body: '<p>¿Desea ' + actionLabel.toLowerCase() + ' al usuario <strong>' +
                DsCrud.escapeHtml(username || '') + '</strong>?</p>',
            saveText: actionLabel,
            cancelText: 'Cancelar',
            onSave: function () {
                DsCrud.apiLegacy('api/usuarios.php', 'PATCH', {
                    id: numericId,
                    activo: !isActive
                }, function () {
                    DsCrud.toast('Usuario actualizado', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e || 'Error al actualizar', 'error');
                });
            }
        });
    }

    var roleOptions = [
        { value: '1', label: 'Administrador' },
        { value: '2', label: 'Operador' },
        { value: '3', label: 'Lectura' }
    ];

    var especialidadesOptions = null;

    function loadEspecialidades(done) {
        if (especialidadesOptions && typeof especialidadesOptions.length === 'number') {
            done(especialidadesOptions);
            return;
        }
        DsCrud.apiLegacy('api/catalogos/opciones.php', 'GET', { tipo: 'especialidades' }, function (resp) {
            var data = resp && resp.DATOS && resp.DATOS.length ? resp.DATOS : [];
            especialidadesOptions = [];
            for (var i = 0; i < data.length; i++) {
                especialidadesOptions.push({
                    value: data[i].value,
                    label: data[i].label
                });
            }
            done(especialidadesOptions);
        }, function (err) {
            DsCrud.toast(err || 'No se pudieron cargar las especialidades.', 'error');
            especialidadesOptions = [];
            done(especialidadesOptions);
        });
    }

    function openCreateModal() {
        loadEspecialidades(function (especialidades) {
            var especialidadOptions = [{ value: '', label: 'Seleccione una especialidad' }];
            for (var i = 0; i < especialidades.length; i++) {
                especialidadOptions.push(especialidades[i]);
            }

            var operatorFields = '';
            operatorFields += DsCrud.field({ name: 'apellido', label: 'Apellido (Operador)', placeholder: 'Requerido para Operador' });
            operatorFields += DsCrud.field({ name: 'especialidad_id', label: 'Especialidad (Operador)', type: 'select', options: especialidadOptions });
            operatorFields += DsCrud.field({ name: 'telefono', label: 'Telefono (Operador)', placeholder: 'Opcional' });

            var body = '';
            body += DsCrud.field({ name: 'username', label: 'Usuario', required: true, placeholder: 'usuario' });
            body += DsCrud.field({ name: 'nombre', label: 'Nombre', required: true, placeholder: 'Nombre' });
            body += '<div class="ds-operator-fields" style="display:none;">' + operatorFields + '</div>';
            body += DsCrud.field({ name: 'email', label: 'Email', type: 'text', placeholder: 'correo@dominio.com' });
            body += DsCrud.field({ name: 'rolid', label: 'Rol', type: 'select', required: true, options: roleOptions });
            body += DsCrud.field({ name: 'password', label: 'Contrasena', type: 'password', required: true, attrs: 'autocomplete="new-password"' });
            body += DsCrud.field({ name: 'password_confirm', label: 'Confirmar contrasena', type: 'password', required: true, attrs: 'autocomplete="new-password"' });
            body += '<p class="muted">Campos de artesano se usan solo para rol Operador.</p>';

            var modalRef = DsCrud.openModal({
                title: 'Nuevo usuario',
                body: body,
                saveText: 'Crear',
                cancelText: 'Cancelar',
                onSave: function (modal) {
                    if (!DsCrud.validateForm(modal)) return;
                    var data = DsCrud.getFormData(modal);
                    var username = (data.username || '').replace(/^\s+|\s+$/g, '');
                    var nombre = (data.nombre || '').replace(/^\s+|\s+$/g, '');
                    var apellido = (data.apellido || '').replace(/^\s+|\s+$/g, '');
                    var especialidadId = parseInt(data.especialidad_id || '0', 10);
                    var telefono = (data.telefono || '').replace(/^\s+|\s+$/g, '');
                    var email = (data.email || '').replace(/^\s+|\s+$/g, '');
                    var rolid = parseInt(data.rolid || '0', 10);
                    var password = String(data.password || '');
                    var passwordConfirm = String(data.password_confirm || '');

                    if (!username || !nombre || !rolid || !password) {
                        DsCrud.toast('Complete los campos requeridos.', 'error');
                        return;
                    }
                    if (password.length < 8) {
                        DsCrud.toast('La contrasena debe tener al menos 8 caracteres.', 'error');
                        return;
                    }
                    if (password !== passwordConfirm) {
                        DsCrud.toast('Las contrasenas no coinciden.', 'error');
                        return;
                    }
                    if (rolid === 2 && !apellido) {
                        DsCrud.toast('Apellido requerido para rol Operador.', 'error');
                        return;
                    }
                    if (isNaN(especialidadId) || especialidadId <= 0) {
                        especialidadId = null;
                    }

                    DsCrud.apiLegacy('api/usuarios.php', 'POST', {
                        username: username,
                        nombre: nombre,
                        apellido: apellido,
                        especialidad_id: especialidadId,
                        telefono: telefono,
                        email: email,
                        rolid: rolid,
                        password: password
                    }, function (resp) {
                        DsCrud.toast(resp.MENSAJE || 'Usuario creado', 'success');
                        DsCrud.closeModal();
                        location.reload();
                    }, function (err) {
                        DsCrud.toast(err || 'Error al crear usuario', 'error');
                    });
                }
            });

            var modalEl = modalRef && modalRef.modal ? modalRef.modal : null;
            if (!modalEl) return;
            var rolSelect = DsCrud.query('select[name="rolid"]', modalEl);
            var operatorWrap = DsCrud.query('.ds-operator-fields', modalEl);
            var apellidoInput = DsCrud.query('input[name="apellido"]', modalEl);
            var especialidadSelect = DsCrud.query('select[name="especialidad_id"]', modalEl);
            var telefonoInput = DsCrud.query('input[name="telefono"]', modalEl);

            function toggleOperatorFields() {
                if (!rolSelect || !operatorWrap) return;
                var isOperador = String(rolSelect.value) === '2';
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
            }

            if (rolSelect) {
                DsCrud.addEvent(rolSelect, 'change', toggleOperatorFields);
            }
            toggleOperatorFields();
        });
    }

    DsCrud.addEvent(table, 'click', function (e) {
        e = e || window.event;
        var target = e.target || e.srcElement;
        while (target && target !== table) {
            if (target.tagName === 'BUTTON' && target.getAttribute('data-action') === 'toggle') {
                var id = target.getAttribute('data-id');
                var isActive = target.getAttribute('data-activo') === '1';
                var tr = target;
                while (tr && tr.tagName !== 'TR') {
                    tr = tr.parentNode;
                }
                var username = '';
                if (tr) {
                    var cells = tr.getElementsByTagName('td');
                    if (cells.length > 1) {
                        username = getText(cells[1]).replace(/^\s+|\s+$/g, '');
                    }
                }
                openToggleModal(id, username, isActive);
                break;
            }
            target = target.parentNode;
        }
    });

    var createBtn = DsCrud.getById('usuarios-create-btn');
    if (createBtn) {
        DsCrud.addEvent(createBtn, 'click', function () {
            openCreateModal();
        });
    }
})();

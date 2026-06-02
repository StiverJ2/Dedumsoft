<?php
/**
 * View: Artesano Ordenes
 *
 * Variables disponibles:
 * @var int    $artesano_id
 * @var string $artesano_nombre
 * @var array  $ordenes_rows
 * @var bool   $legacy
 */
if (!function_exists('format_prioridad_badge')) {
    function format_prioridad_badge($prioridad)
    {
        $prioridad = strtolower(trim((string) $prioridad));
        $cls = 'ds-badge--neutral';
        if ($prioridad === 'alta' || $prioridad === 'urgente') {
            $cls = 'ds-badge--danger';
        } elseif ($prioridad === 'media' || $prioridad === 'normal') {
            $cls = 'ds-badge--warning';
        } elseif ($prioridad === 'baja') {
            $cls = 'ds-badge--muted';
        }
        return '<span class="ds-badge ' . $cls . '">' . v_e(ucfirst($prioridad ?: 'Normal')) . '</span>';
    }
}

if (!function_exists('format_estado_badge')) {
    function format_estado_badge($estado)
    {
        $estado_lower = strtolower(trim((string) $estado));
        $cls = 'ds-badge--neutral';
        if ($estado_lower === 'pendiente') {
            $cls = 'ds-badge--warning';
        } elseif ($estado_lower === 'en_proceso') {
            $cls = 'ds-badge--info';
        } elseif ($estado_lower === 'terminada') {
            $cls = 'ds-badge--success';
        } elseif ($estado_lower === 'cancelada') {
            $cls = 'ds-badge--danger';
        } elseif ($estado_lower === 'pausada') {
            $cls = 'ds-badge--muted';
        }
        return '<span class="ds-badge ' . $cls . '">' . v_e(strtoupper(str_replace('_', ' ', $estado ?: 'Sin estado'))) . '</span>';
    }
}
?>
<div class="content">
    <div class="content-header">
        <h1>Mis Ordenes</h1>
        <p>Ordenes asignadas a <?php echo v_e((string) ($artesano_nombre ?: 'mi')); ?></p>
    </div>

    <?php if (!$artesano_id): ?>
    <div class="ds-alert ds-alert--warning">
        <strong>Atencion:</strong> No se encontro un perfil de artesano asociado a tu cuenta.
        Contacta al administrador para vincular tu usuario.
    </div>
    <?php else: ?>

    <div class="card">
        <div class="ds-toolbar">
            <strong>Ordenes de produccion</strong>
        </div>
        <div class="table-responsive">
            <table id="ordenes-artesano-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha inicio</th>
                        <th>Fecha estimada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($ordenes_rows as $row): ?>
                    <tr data-id="<?php echo (int) $row['id']; ?>">
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo v_e((string) $row['producto_nombre']); ?></td>
                        <td><?php echo (int) $row['cantidad']; ?></td>
                        <td><?php echo format_prioridad_badge($row['prioridad']); ?></td>
                        <td><?php echo format_estado_badge($row['estado']); ?></td>
                        <td><?php echo $row['fecha_inicio'] ? date('Y-m-d', strtotime($row['fecha_inicio'])) : '-'; ?>
                        </td>
                        <td><?php echo $row['fecha_fin_estimada'] ? date('Y-m-d', strtotime($row['fecha_fin_estimada'])) : '-'; ?>
                        </td>
                        <td class="ds-actions-col">
                            <button type="button" class="ds-action-btn" data-action="estado" title="Cambiar estado">
                                <img src="assets/icons/fatcow/16/arrow_refresh.png" alt="Estado" class="ds-icon-img">
                            </button>
                            <button type="button" class="ds-action-btn" data-action="consumo" title="Registrar consumo">
                                <img src="assets/icons/fatcow/16/box_out.png" alt="Consumo" class="ds-icon-img">
                            </button>
                            <button type="button" class="ds-action-btn" data-action="terminar"
                                title="Registrar pieza terminada">
                                <img src="assets/icons/fatcow/16/tick.png" alt="Terminar" class="ds-icon-img">
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ordenes_rows)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No tienes ordenes asignadas</td>
                    </tr>
                    <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

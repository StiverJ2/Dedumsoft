<?php
/**
 * ============================================================================
 * CONFIGURACION DE CATALOGOS (UI + API)
 * ============================================================================
 *
 * Define los catalogos administrables desde Ajustes > Catalogos.
 * Se usa tanto para construir la UI como para validar en la API.
 *
 * @package Dedumsoft\Database
 */

function dedumsoft_catalogs_config()
{
    return [
        'areas' => [
            'label' => 'Areas',
            'icon' => 'building.png',
            'emoji' => '🏢',
            'table' => 'areas',
            'order_by' => 'nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'tipos_oro' => [
            'label' => 'Tipos de oro',
            'icon' => 'diamond.png',
            'emoji' => '💎',
            'table' => 'tipos_oro',
            'order_by' => 'kilates, nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'kilates', 'pureza_porcentaje', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'kilates', 'label' => 'Kilates'],
                ['key' => 'pureza_porcentaje', 'label' => 'Pureza %'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'kilates', 'label' => 'Kilates', 'input' => 'number', 'data' => 'decimal', 'attrs' => 'min="0" step="0.01"'],
                ['name' => 'pureza_porcentaje', 'label' => 'Pureza (%)', 'input' => 'number', 'data' => 'decimal', 'attrs' => 'min="0" step="0.01"'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'tipos_proveedor' => [
            'label' => 'Tipos de proveedor',
            'icon' => 'lorry.png',
            'emoji' => '🚚',
            'table' => 'tipos_proveedor',
            'order_by' => 'nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'tipos_maquinaria' => [
            'label' => 'Tipos de maquinaria',
            'icon' => 'toolbox.png',
            'emoji' => '🧰',
            'table' => 'tipos_maquinaria',
            'order_by' => 'nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'especialidades' => [
            'label' => 'Especialidades',
            'icon' => 'award_star_gold_blue.png',
            'emoji' => '⭐',
            'table' => 'cat_especialidad',
            'order_by' => 'nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'estados_maquinaria' => [
            'label' => 'Estados de maquinaria',
            'icon' => 'cog.png',
            'emoji' => '⚙️',
            'table' => 'estados_maquinaria',
            'order_by' => 'id',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'color', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'color'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text'],
                ['name' => 'color', 'label' => 'Color', 'input' => 'text', 'data' => 'text', 'placeholder' => '#7a7a7a']
            ]
        ],
        'estados_orden' => [
            'label' => 'Estados de orden',
            'icon' => 'clipboard_empty.png',
            'emoji' => '📋',
            'table' => 'estados_orden',
            'order_by' => 'id',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'color', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'color'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text'],
                ['name' => 'color', 'label' => 'Color', 'input' => 'text', 'data' => 'text', 'placeholder' => '#7a7a7a']
            ]
        ],
        'prioridades' => [
            'label' => 'Prioridades',
            'icon' => 'exclamation.png',
            'emoji' => '❗',
            'table' => 'prioridades',
            'order_by' => 'id',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'color', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'color'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text'],
                ['name' => 'color', 'label' => 'Color', 'input' => 'text', 'data' => 'text', 'placeholder' => '#7a7a7a']
            ]
        ],
        'tipos_material' => [
            'label' => 'Tipos de material',
            'icon' => 'box.png',
            'emoji' => '📦',
            'table' => 'tipos_material',
            'order_by' => 'nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'niveles_calidad' => [
            'label' => 'Niveles de calidad',
            'icon' => 'medal_award_gold.png',
            'emoji' => '🥇',
            'table' => 'niveles_calidad',
            'order_by' => 'id',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'descripcion', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text']
            ]
        ],
        'productos' => [
            'label' => 'Productos',
            'icon' => 'handbag.png',
            'emoji' => '🛍️',
            'table' => 'productos',
            'order_by' => 'nombre',
            'filter_activo' => true,
            'columns' => ['id', 'nombre', 'tipo', 'descripcion', 'tiempo_fabricacion_horas', 'precio_venta', 'imagen_url', 'activo'],
            'list_columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'tipo', 'label' => 'Tipo'],
                ['key' => 'tiempo_fabricacion_horas', 'label' => 'Horas', 'type' => 'number'],
                ['key' => 'precio_venta', 'label' => 'Precio', 'type' => 'money'],
                ['key' => 'activo', 'label' => 'Estado', 'type' => 'bool']
            ],
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre', 'required' => true, 'input' => 'text', 'data' => 'text'],
                ['name' => 'tipo', 'label' => 'Tipo', 'input' => 'text', 'data' => 'text'],
                ['name' => 'descripcion', 'label' => 'Descripcion', 'input' => 'textarea', 'data' => 'text'],
                ['name' => 'tiempo_fabricacion_horas', 'label' => 'Horas de fabricacion', 'input' => 'number', 'data' => 'decimal', 'attrs' => 'min="0" step="0.1"'],
                ['name' => 'precio_venta', 'label' => 'Precio de venta', 'input' => 'number', 'data' => 'decimal', 'attrs' => 'min="0" step="0.01"'],
                ['name' => 'imagen_url', 'label' => 'URL de imagen', 'input' => 'text', 'data' => 'text']
            ]
        ]
    ];
}

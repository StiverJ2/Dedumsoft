<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE COMPRAS
 * ============================================================================
 * 
 * Endpoint para registrar entradas de inventario (compras).
 * Soporta múltiples tipos de inventario: oro, insumos, maquinaria.
 * 
 * Métodos soportados:
 * - POST: Registrar nueva compra/entrada de inventario
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 2 (Inventario)
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

// Solo aceptar POST
if (!validateHttpMethod('POST')) {
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(2); // Menú: Inventario

// =============================================================================
// POST: Registrar compra/entrada de inventario
// =============================================================================
// Body JSON:
//   - tipo_inventario (string): 'oro', 'insumo' o 'maquinaria'
//   - item_id (int): ID del item en la tabla correspondiente
//   - cantidad (float): Cantidad a agregar
//   - motivo (string, opcional): Razón de la compra
//   - referencia (string, opcional): Número de factura, etc.
//   - fecha (date, opcional): Fecha de la compra
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Compra registrada.', ID: <mov_id> }

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
    exit;
}

// Extraer y validar parámetros
$tipo = strtolower(trim($input['tipo_inventario'] ?? ''));
$item_id = $input['item_id'] ?? null;
$cantidad = $input['cantidad'] ?? null;
$motivo = $input['motivo'] ?? null;
$referencia = $input['referencia'] ?? null;
$fecha = $input['fecha'] ?? null;

// Validar campos obligatorios
if ($tipo === '' || $item_id === null || $item_id === '') {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Tipo e item_id requeridos.']);
    exit;
}

if ($cantidad === null || !is_numeric($cantidad) || (float) $cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Cantidad invalida.']);
    exit;
}

// Normalizar fecha vacía a null
if ($fecha === '') {
    $fecha = null;
}

// Obtener ID del usuario actual para auditoría
$user = get_session_user();
$usuario_id = $user['id_usuario'] ?? null;

try {
    // Seleccionar función según tipo de inventario
    switch ($tipo) {
        case 'oro':
            $stmt = $connLogic->prepare(
                'SELECT fun_registrar_compra_oro(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)'
            );
            break;
        case 'insumo':
        case 'insumos':
            $stmt = $connLogic->prepare(
                'SELECT fun_registrar_compra_insumo(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)'
            );
            break;
        case 'maquinaria':
            $stmt = $connLogic->prepare(
                'SELECT fun_registrar_compra_maquinaria(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)'
            );
            break;
        default:
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Tipo de inventario no soportado.']);
            exit;
    }

    // Enlazar parámetros con tipos apropiados
    $stmt->bindValue(':item_id', (int) $item_id, PDO::PARAM_INT);
    $stmt->bindValue(':cantidad', $cantidad);
    $stmt->bindValue(':motivo', $motivo, isset($motivo) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':referencia', $referencia, isset($referencia) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':usuario_id', $usuario_id, isset($usuario_id) ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':fecha', $fecha, isset($fecha) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();

    // Obtener ID del movimiento creado
    $mov_id = $stmt->fetchColumn();

    http_response_code(201);
    echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Compra registrada.', 'ID' => (int) $mov_id]);
} catch (PDOException $e) {
    error_log('compras POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

    // Detectar si es error de item no encontrado
    $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
    http_response_code($code);
    echo json_encode([
        'CODIGO' => $code,
        'MENSAJE' => $code === 404 ? 'Item no encontrado.' : 'Error al registrar compra.'
    ]);
}

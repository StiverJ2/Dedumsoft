<?php
/**
 * ============================================================================
 * REPORTE CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para reportes gerenciales.
 *
 * Unifica el comportamiento entre:
 * - public/reportes.php (vista HTML)
 * - public/api/reportes/*.php (APIs JSON)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/ReporteRepository.php';

class ReporteController extends Controller
{
    /** @var ReporteRepository */
    private $repo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new ReporteRepository($pdo);
    }

    // ========================================================================
    // REPORTES (para APIs y vistas)
    // ========================================================================

    /**
     * Reporte de produccion por periodo.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteProduccion(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->produccion($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteProduccion error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de inventario consolidado.
     *
     * @return array<string, mixed>
     */
    public function reporteInventario(): array
    {
        try {
            $rows = $this->repo->inventario();
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteInventario error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de eficiencia de artesanos.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteEficiencia(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->eficienciaArtesanos($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteEficiencia error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de uso de materiales.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteMateriales(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->usoMateriales($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteMateriales error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de ventas.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteVentas(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->ventas($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteVentas error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de compras.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteCompras(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->compras($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteCompras error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de usuarios.
     *
     * @return array<string, mixed>
     */
    public function reporteUsuarios(): array
    {
        try {
            $rows = $this->repo->usuarios();
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteUsuarios error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    // ========================================================================
    // LEGACY PAGE HELPERS (para public/reportes.php)
    // ========================================================================

    /**
     * Prepara los datos necesarios para renderizar la pagina de reportes.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageData(array $get, bool $legacy): array
    {
        $desde = $this->toString($get['desde'] ?? date('Y-m-01'));
        $hasta = $this->toString($get['hasta'] ?? date('Y-m-t'));
        $input_type = $legacy ? 'text' : 'date';
        $cache_bust = $this->toString($get['cb'] ?? '');
        $chart_params = 'desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta);
        if ($cache_bust !== '') {
            $chart_params .= '&cb=' . urlencode($cache_bust);
        }

        $rep_produccion = [];
        $rep_inventario = [];
        $rep_eficiencia = [];
        $rep_materiales = [];
        $rep_ventas = [];
        $rep_compras = [];
        $rep_usuarios = [];

        if ($legacy) {
            $result = $this->reporteProduccion($desde, $hasta);
            if ($result['success']) {
                $rep_produccion = $result['data'];
            }

            $result = $this->reporteInventario();
            if ($result['success']) {
                $rep_inventario = $result['data'];
            }

            $result = $this->reporteEficiencia($desde, $hasta);
            if ($result['success']) {
                $rep_eficiencia = $result['data'];
            }

            $result = $this->reporteMateriales($desde, $hasta);
            if ($result['success']) {
                $rep_materiales = $result['data'];
            }

            $result = $this->reporteVentas($desde, $hasta);
            if ($result['success']) {
                $rep_ventas = $result['data'];
            }

            $result = $this->reporteCompras($desde, $hasta);
            if ($result['success']) {
                $rep_compras = $result['data'];
            }

            $result = $this->reporteUsuarios();
            if ($result['success']) {
                $rep_usuarios = $result['data'];
            }
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'input_type' => $input_type,
            'chart_params' => $chart_params,
            'rep_produccion' => $rep_produccion,
            'rep_inventario' => $rep_inventario,
            'rep_eficiencia' => $rep_eficiencia,
            'rep_materiales' => $rep_materiales,
            'rep_ventas' => $rep_ventas,
            'rep_compras' => $rep_compras,
            'rep_usuarios' => $rep_usuarios,
            'legacy' => $legacy,
        ];
    }
}

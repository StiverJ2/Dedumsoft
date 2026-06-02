<?php
/**
 * View: Index Operario
 *
 * Variables disponibles:
 * @var array $cards
 * @var array $tones
 * @var bool  $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Panel Operario</h1>
        <p>Accesos rapidos segun tus permisos</p>
    </div>

    <div class="dashboard-grid">
        <?php foreach ($cards as $idx => $card): ?>
            <a class="dashboard-card <?php echo $tones[$idx % count($tones)]; ?>"
                href="<?php echo v_e($card['href']); ?>" style="color: inherit; text-decoration: none;">
                <div class="card-icon"><?php echo $card['icon']; ?></div>
                <h3><?php echo v_e($card['title']); ?></h3>
                <p class="stat"><?php echo v_e($card['cta']); ?></p>
                <div class="card-footer">
                    <span><?php echo v_e($card['desc']); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

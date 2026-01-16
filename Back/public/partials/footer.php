<?php
require_once __DIR__ . '/../../connection/guard.php';
$legacy = dedumsoft_is_legacy_browser();
?>
</main>
</div>
<?php if (!$legacy): ?>
<script src="../bootstrap/popper.min.js"></script>
<script src="../bootstrap/bootstrap.min.js"></script>
<?php endif; ?>
</body>
</html>

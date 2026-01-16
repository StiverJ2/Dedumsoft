<?php
require_once __DIR__ . '/../../connection/guard.php';
$legacy = dedumsoft_is_legacy_browser();
?>
</main>
</div>
<?php if ($legacy): ?>
    <script src="assets/table-sort.js"></script>
    <script src="assets/crud-legacy.js"></script>
<?php else: ?>
    <script src="assets/jquery-3.7.1.min.js"></script>
    <script src="assets/jquery.dataTables.min.js"></script>
    <script src="assets/dataTables.bootstrap5.min.js"></script>
    <script src="../bootstrap/popper.min.js"></script>
    <script src="../bootstrap/bootstrap.min.js"></script>
    <script src="assets/crud.js"></script>
<?php endif; ?>
</body>

</html>
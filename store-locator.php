<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$city = isset($_GET['city']) ? (string)$_GET['city'] : 'bengaluru';
$mapQuery = urlencode($city);
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h1 class="h4 mb-3">Store Locator</h1>
<div class="ratio ratio-16x9">
  <iframe src="https://www.google.com/maps?q=<?= e($mapQuery) ?>&output=embed" loading="lazy" allowfullscreen></iframe>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

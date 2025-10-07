<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$cities = $pdo->query('SELECT name, slug, state FROM cities ORDER BY name')->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h1 class="h4 mb-3">Cities We Serve</h1>
<ul class="list-group">
  <?php foreach ($cities as $c): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <span><?= e($c['name']) ?><?= $c['state'] ? ', ' . e($c['state']) : '' ?></span>
      <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('store-locator.php?city=' . $c['slug'])) ?>">View on Map</a>
    </li>
  <?php endforeach; ?>
</ul>
<?php include __DIR__ . '/includes/footer.php'; ?>

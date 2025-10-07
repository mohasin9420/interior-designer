<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$services = $pdo->query("SELECT id, title, slug, summary, content, image_path, price_range FROM services WHERE status='active' ORDER BY created_at DESC")->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h1 class="h4 mb-3">Our Services</h1>
<div class="row g-3">
  <?php foreach ($services as $s): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <img src="<?= e($s['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($s['title']) ?>">
        <div class="card-body">
          <h2 class="h6 mb-1"><?= e($s['title']) ?></h2>
          <?php if (!empty($s['price_range'])): ?>
            <div class="small text-muted mb-1">Price: <?= e($s['price_range']) ?></div>
          <?php endif; ?>
          <p class="small text-muted mb-2"><?= e(mb_strimwidth((string)($s['summary'] ?? ''), 0, 140, '…')) ?></p>
          <a class="btn btn-sm btn-outline-primary" href="#">Learn more</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

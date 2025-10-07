<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$posts = $pdo->query("SELECT id, title, slug, excerpt, image_path, published_at FROM blog_posts WHERE status='published' ORDER BY published_at DESC")->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h1 class="h4 mb-3">Blog</h1>
<div class="row g-3">
  <?php foreach ($posts as $p): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <img src="<?= e($p['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
        <div class="card-body">
          <h2 class="h6 mb-1"><?= e($p['title']) ?></h2>
          <div class="small text-muted mb-2"><?= e((string)$p['published_at']) ?></div>
          <p class="small mb-0"><?= e(mb_strimwidth((string)($p['excerpt'] ?? ''), 0, 140, '…')) ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

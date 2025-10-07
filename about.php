<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$stmt = $pdo->prepare("SELECT title, content FROM pages WHERE slug='about' LIMIT 1");
$stmt->execute();
$page = $stmt->fetch();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h1 class="h4 mb-3"><?= e($page['title'] ?? 'About Us') ?></h1>
<div><?= $page ? $page['content'] : '<p>Write about your company here.</p>' ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>

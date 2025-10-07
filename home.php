<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();

// Load company info and value propositions (optional tables)
try {
    $companyInfo = $pdo->query("SELECT * FROM homepage_company_info WHERE is_active = 1 LIMIT 1")->fetch();
    $valueProps = $pdo->query("SELECT * FROM homepage_value_propositions WHERE is_active = 1 ORDER BY display_order LIMIT 10")->fetchAll();
} catch (PDOException $e) {
    $companyInfo = null;
    $valueProps = [];
}

// Load recent portfolio projects
$projects = $pdo->query(
    "SELECT p.id, p.title, p.description, p.image_path, p.location, p.property_type, c.name AS category
     FROM portfolio p
     LEFT JOIN project_categories c ON p.category_id=c.id
     WHERE p.status='active'
     ORDER BY p.created_at DESC
     LIMIT 9"
)->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container py-5">
  <div class="row mb-5">
    <div class="col-lg-8 mx-auto text-center">
      <h1 class="display-4 fw-bold mb-3">Home Page</h1>
      <p class="lead text-muted">Discover our story, what we offer, and a curated selection of our portfolio.</p>
    </div>
  </div>

  <?php if ($companyInfo): ?>
  <section class="company-info py-4">
    <div class="row align-items-start">
      <div class="col-lg-8">
        <div class="company-story mb-4">
          <?= $companyInfo['company_description'] ?>
        </div>
        <div class="key-metrics">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-center">
                <h3 class="h4 mb-1"><?= e($companyInfo['metric_1_value']) ?></h3>
                <div class="text-muted small"><?= e($companyInfo['metric_1_label']) ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <h3 class="h4 mb-1"><?= e($companyInfo['metric_2_value']) ?></h3>
                <div class="text-muted small"><?= e($companyInfo['metric_2_label']) ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <h3 class="h4 mb-1"><?= e($companyInfo['metric_3_value']) ?></h3>
                <div class="text-muted small"><?= e($companyInfo['metric_3_label']) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 mt-4 mt-lg-0">
        <div class="value-propositions">
          <h3 class="h5 mb-3">Why Choose Us</h3>
          <ul class="list-group">
            <?php foreach ($valueProps as $prop): ?>
            <li class="list-group-item border-0 ps-0 d-flex align-items-start">
              <i class="bi bi-check-circle-fill text-success me-2"></i>
              <span><?= e($prop['proposition_text']) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php if (!empty($companyInfo['final_tagline'])): ?>
          <div class="mt-4 fw-semibold"><?= e($companyInfo['final_tagline']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="py-5 bg-light rounded-3">
    <div class="text-center mb-4">
      <h2 class="h3 mb-2">Our Portfolio</h2>
      <p class="text-muted mb-0">A sample of our latest interior design projects.</p>
    </div>
    <div class="row g-4">
      <?php if (empty($projects)): ?>
        <div class="col-12 text-center">
          <p class="text-muted">No projects available right now. Please check back soon.</p>
        </div>
      <?php else: ?>
        <?php foreach ($projects as $p): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <img src="<?= e($p['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
            <div class="card-body">
              <h3 class="h5 mb-2"><?= e($p['title']) ?></h3>
              <?php if (!empty($p['category'])): ?>
                <div class="mb-2"><span class="badge bg-secondary"><?= e($p['category']) ?></span></div>
              <?php endif; ?>
              <?php if (!empty($p['location'])): ?>
                <div class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i> <?= e($p['location']) ?></div>
              <?php endif; ?>
              <?php if (!empty($p['description'])): ?>
                <p class="small text-muted mb-3"><?= e(mb_strimwidth((string)$p['description'], 0, 110, '…')) ?></p>
              <?php endif; ?>
              <a href="<?= e(base_url('project-detail.php?id=' . $p['id'])) ?>" class="btn btn-sm btn-outline-primary">View Project</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="text-center mt-4">
      <a class="btn btn-primary" href="<?= e(base_url('portfolio.php')) ?>">View All Projects</a>
    </div>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

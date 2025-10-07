<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

// Get page meta data for SEO
$pageTitle = "Our Portfolio - Interior Design Projects";
$pageDescription = "Browse our collection of stunning interior design projects. From modern apartments to luxury villas, see how we transform spaces.";

$pdo = getPDO();
$categories = $pdo->query('SELECT id, name, slug FROM project_categories ORDER BY name')->fetchAll();
$cat = isset($_GET['cat']) ? (string)$_GET['cat'] : '';
$params = [];

// Enhanced query to get more project details
$sql = "SELECT p.id, p.title, p.description, p.image_path, p.location, p.property_type, 
        c.name AS category, c.slug AS category_slug 
        FROM portfolio p 
        LEFT JOIN project_categories c ON p.category_id=c.id 
        WHERE p.status='active'";

// Filter by category if specified
if ($cat !== '') {
    $sql .= ' AND c.slug = :slug';
    $params[':slug'] = $cat;
    
    // Get category details for title
    $catStmt = $pdo->prepare("SELECT name FROM project_categories WHERE slug = :slug LIMIT 1");
    $catStmt->execute([':slug' => $cat]);
    $catInfo = $catStmt->fetch();
    if ($catInfo) {
        $pageTitle = $catInfo['name'] . " Interior Design Projects - Our Portfolio";
        $pageDescription = "Browse our " . $catInfo['name'] . " interior design projects. See our expertise in creating beautiful " . strtolower($catInfo['name']) . " spaces.";
    }
}

$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container py-5">
  <!-- Hero Section -->
  <div class="row mb-5">
    <div class="col-lg-8 mx-auto text-center">
      <h1 class="display-4 fw-bold mb-3">Our Portfolio</h1>
      <p class="lead text-muted">Explore our collection of stunning interior design projects that showcase our expertise and creativity.</p>
    </div>
  </div>
  
  <!-- Filter Section -->
  <div class="row mb-4">
    <div class="col-md-8 mx-auto">
      <div class="d-flex justify-content-center">
        <div class="filter-buttons">
          <a href="<?= base_url('portfolio.php') ?>" class="btn <?= $cat === '' ? 'btn-primary' : 'btn-outline-secondary' ?> me-2 mb-2">All Projects</a>
          <?php foreach ($categories as $c): ?>
            <a href="<?= base_url('portfolio.php?cat=' . e($c['slug'])) ?>" class="btn <?= $cat === $c['slug'] ? 'btn-primary' : 'btn-outline-secondary' ?> me-2 mb-2"><?= e($c['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Projects Grid -->
  <div class="row g-4">
    <?php if (empty($projects)): ?>
      <div class="col-12 text-center py-5">
        <p class="text-muted">No projects found in this category. Please check back later.</p>
      </div>
    <?php else: ?>
      <?php foreach ($projects as $p): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm hover-scale">
            <div class="position-relative">
              <img src="<?= e($p['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
              <?php if (!empty($p['property_type'])): ?>
                <span class="badge bg-primary position-absolute top-0 end-0 m-3"><?= e($p['property_type']) ?></span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <h3 class="h5 mb-2"><?= e($p['title']) ?></h3>
              <?php if (!empty($p['category'])): ?>
                <div class="mb-2"><span class="badge bg-secondary"><?= e($p['category']) ?></span></div>
              <?php endif; ?>
              <?php if (!empty($p['location'])): ?>
                <div class="text-muted mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?= e($p['location']) ?></div>
              <?php endif; ?>
              <?php if (!empty($p['description'])): ?>
                <p class="card-text small text-muted"><?= substr(e($p['description']), 0, 100) ?>...</p>
              <?php endif; ?>
              <a href="<?= base_url('project-detail.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary mt-2">View Details</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Custom CSS for hover effects -->
<style>
.hover-scale {
  transition: transform 0.3s ease;
}
.hover-scale:hover {
  transform: translateY(-5px);
}
</style>
<?php include __DIR__ . '/includes/footer.php'; ?>

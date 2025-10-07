<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

// Get project ID from URL
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($projectId <= 0) {
    // Redirect to portfolio page if no valid ID
    redirect('portfolio.php');
}

$pdo = getPDO();

// Get project details
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category, c.slug AS category_slug 
    FROM portfolio p 
    LEFT JOIN project_categories c ON p.category_id = c.id 
    WHERE p.id = :id AND p.status = 'active' 
    LIMIT 1
");
$stmt->execute([':id' => $projectId]);
$project = $stmt->fetch();

// Redirect if project not found
if (!$project) {
    redirect('portfolio.php');
}

// Get related projects
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.image_path 
    FROM portfolio p 
    WHERE p.category_id = :category_id 
    AND p.id != :current_id 
    AND p.status = 'active' 
    ORDER BY RAND() 
    LIMIT 3
");
$stmt->execute([
    ':category_id' => $project['category_id'] ?: 0,
    ':current_id' => $projectId
]);
$relatedProjects = $stmt->fetchAll();

// Set page meta data
$pageTitle = $project['title'] . " - Interior Design Project";
$pageDescription = substr(strip_tags($project['description'] ?: ''), 0, 160);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url() ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('portfolio.php') ?>">Portfolio</a></li>
            <?php if (!empty($project['category'])): ?>
                <li class="breadcrumb-item"><a href="<?= base_url('portfolio.php?cat=' . e($project['category_slug'])) ?>"><?= e($project['category']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e($project['title']) ?></li>
        </ol>
    </nav>

    <!-- Project Header -->
    <div class="row mb-5">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-3"><?= e($project['title']) ?></h1>
            <div class="d-flex flex-wrap gap-3 mb-4">
                <?php if (!empty($project['category'])): ?>
                    <span class="badge bg-primary p-2"><i class="fas fa-tag me-1"></i> <?= e($project['category']) ?></span>
                <?php endif; ?>
                <?php if (!empty($project['location'])): ?>
                    <span class="badge bg-secondary p-2"><i class="fas fa-map-marker-alt me-1"></i> <?= e($project['location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($project['property_type'])): ?>
                    <span class="badge bg-info p-2"><i class="fas fa-home me-1"></i> <?= e($project['property_type']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Project Image -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="project-image-container">
                <img src="<?= e($project['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" 
                     alt="<?= e($project['title']) ?>" 
                     class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>

    <!-- Project Details -->
    <div class="row mb-5">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h4 mb-4">Project Details</h2>
                    <div class="project-description mb-4">
                        <?= nl2br(e($project['description'] ?: 'No description available.')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="h5 mb-3">Project Information</h3>
                    <ul class="list-group list-group-flush">
                        <?php if (!empty($project['property_type'])): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Property Type:</span>
                                <span class="fw-bold"><?= e($project['property_type']) ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($project['location'])): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Location:</span>
                                <span class="fw-bold"><?= e($project['location']) ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($project['category'])): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Style:</span>
                                <span class="fw-bold"><?= e($project['category']) ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Get Estimate CTA -->
            <div class="card shadow-sm mt-4 bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="h5 mb-3">Like what you see?</h3>
                    <p>Get a personalized estimate for your interior design project.</p>
                    <a href="<?= base_url('get-estimate.php') ?>" class="btn btn-light">Get Free Estimate</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Projects -->
    <?php if (!empty($relatedProjects)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="h3 mb-4">Similar Projects</h2>
                <div class="row g-4">
                    <?php foreach ($relatedProjects as $related): ?>
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm hover-scale">
                                <img src="<?= e($related['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" 
                                     class="card-img-top" 
                                     alt="<?= e($related['title']) ?>">
                                <div class="card-body">
                                    <h3 class="h6 mb-0"><?= e($related['title']) ?></h3>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <a href="<?= base_url('project-detail.php?id=' . $related['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Custom CSS for project detail page -->
<style>
.project-image-container {
    max-height: 600px;
    overflow: hidden;
    border-radius: 8px;
}
.project-image-container img {
    width: 100%;
    object-fit: cover;
}
.hover-scale {
    transition: transform 0.3s ease;
}
.hover-scale:hover {
    transform: translateY(-5px);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$projects = $pdo->query("SELECT p.id, p.title, p.image_path, c.name AS category FROM portfolio p LEFT JOIN project_categories c ON p.category_id=c.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
$services = $pdo->query("SELECT id, title, summary, image_path, slug FROM services WHERE status='active' ORDER BY created_at DESC LIMIT 6")->fetchAll();
$blogPosts = $pdo->query("SELECT id, title, slug, excerpt, image_path, published_at FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 3")->fetchAll();

// Get modular homepage content
$hero = $pdo->query("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1")->fetch();
$testimonialSection = $pdo->query("SELECT * FROM homepage_testimonials_section WHERE is_active = 1 LIMIT 1")->fetch();
$testimonials = $pdo->query("SELECT * FROM homepage_testimonials WHERE is_active = 1 ORDER BY display_order, id LIMIT 6")->fetchAll();
$processSection = $pdo->query("SELECT * FROM homepage_process_section WHERE is_active = 1 LIMIT 1")->fetch();
$processSteps = $pdo->query("SELECT * FROM homepage_process_steps WHERE is_active = 1 ORDER BY display_order, step_number LIMIT 6")->fetchAll();
$companyInfo = $pdo->query("SELECT * FROM homepage_company_info WHERE is_active = 1 LIMIT 1")->fetch();
$valueProps = $pdo->query("SELECT * FROM homepage_value_propositions WHERE is_active = 1 ORDER BY display_order LIMIT 10")->fetchAll();

// Get featured portfolio projects for homepage
try {
    $portfolioSection = $pdo->query("SELECT * FROM homepage_portfolio_section WHERE is_active = 1 LIMIT 1")->fetch();
    $featuredProjects = $pdo->query("
        SELECT p.id, p.title, p.description, p.image_path, p.location, p.property_type, c.name AS category, f.display_order
        FROM homepage_featured_projects f
        JOIN portfolio p ON f.portfolio_id = p.id
        LEFT JOIN project_categories c ON p.category_id = c.id
        WHERE f.is_active = 1 AND p.status = 'active'
        ORDER BY f.display_order, f.id
        LIMIT 6
    ")->fetchAll();
} catch (PDOException $e) {
    // Tables might not exist yet
    $portfolioSection = null;
    $featuredProjects = [];
}

// Default portfolio section if not in database
if (!$portfolioSection) {
    $portfolioSection = [
        'section_title' => 'Our Featured Projects',
        'section_description' => 'Explore our handpicked selection of stunning interior design projects that showcase our expertise and creativity.',
        'is_active' => 1
    ];
}

// Use default values if database content is not available
if (!$hero) {
    $hero = [
        'main_headline' => 'Best Interior Designers',
        'sub_headline' => 'In Chennai',
        'tagline' => 'Home Interiors Within Your Budget - Unbeatable Quality @ Unbelievable Price!',
        'cta_box_title' => 'Explore 50,000+ Design Ideas',
        'cta_box_subtitle' => 'Get Free Estimate and Interior Design Ideas in Minutes',
        'form_name_label' => 'Name',
        'form_phone_label' => 'Phone Number',
        'form_email_label' => 'Email',
        'form_location_label' => 'Property Location',
        'button_text' => 'Chat with Design Expert',
        'background_image' => ''
    ];
}

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'estimate') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $propertyType = trim((string)($_POST['property_type'] ?? ''));
        $propertyLocation = trim((string)($_POST['property_location'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($name === '' || $phone === '') {
            $errors[] = 'Name and phone are required.';
        }
        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO contact_submissions (type, name, email, phone, property_type, property_location, message) VALUES (\'estimate\', :name, :email, :phone, :ptype, :plocation, :message)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email ?: null,
                ':phone' => $phone,
                ':ptype' => $propertyType ?: null,
                ':plocation' => $propertyLocation ?: null,
                ':message' => $message ?: null,
            ]);
            $success = true;
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero" <?php if (!empty($hero['background_image'])): ?>style="background-image: url('<?= e($hero['background_image']) ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <h1 class="display-5 fw-bold"><?= e($hero['main_headline']) ?></h1>
        <h2 class="h3"><?= e($hero['sub_headline']) ?></h2>
        <p class="lead"><?= e($hero['tagline']) ?></p>
      </div>
      <div class="col-lg-5 ms-auto">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3"><?= e($hero['cta_box_title']) ?></h2>
            <p class="text-muted mb-3"><?= e($hero['cta_box_subtitle']) ?></p>
            <?php if ($success): ?>
              <div class="alert alert-success">Thank you! We will contact you shortly.</div>
            <?php elseif (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_type" value="estimate">
              <div class="mb-2">
                <label class="form-label"><?= e($hero['form_name_label']) ?></label>
                <input class="form-control" name="name" required>
              </div>
              <div class="mb-2">
                <label class="form-label"><?= e($hero['form_email_label']) ?></label>
                <input type="email" class="form-control" name="email">
              </div>
              <div class="mb-2">
                <label class="form-label"><?= e($hero['form_phone_label']) ?></label>
                <input class="form-control" name="phone" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Property Type</label>
                <select class="form-select" name="property_type">
                  <option value="">Select</option>
                  <option>1BHK</option><option>2BHK</option><option>3BHK</option><option>4BHK</option><option>Villa</option><option>Office</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label"><?= e($hero['form_location_label']) ?></label>
                <input class="form-control" name="property_location">
              </div>
              <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="2"></textarea>
              </div>
              <button class="btn btn-primary w-100" type="submit"><?= e($hero['button_text']) ?></button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials Section -->
<?php if ($testimonialSection): ?>
<section class="testimonials py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title"><?= e($testimonialSection['section_title']) ?></h2>
      <p class="section-subtitle"><?= e($testimonialSection['section_subtitle']) ?></p>
    </div>
    
    <div class="row">
      <?php foreach ($testimonials as $testimonial): ?>
      <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center mb-3">
              <?php if (!empty($testimonial['customer_image'])): ?>
              <img src="<?= e($testimonial['customer_image']) ?>" alt="<?= e($testimonial['customer_name']) ?>" class="rounded-circle me-3" width="60" height="60">
              <?php endif; ?>
              <div>
                <h5 class="card-title mb-0"><?= e($testimonial['customer_name']) ?></h5>
                <p class="text-muted small mb-0">Design Expert: <?= e($testimonial['design_expert_name']) ?></p>
              </div>
            </div>
            <h6 class="quote-title"><?= e($testimonial['quote_title']) ?></h6>
            <p class="card-text"><?= e($testimonial['testimonial_text']) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Process Section -->
<?php if ($processSection): ?>
<section class="process py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title"><?= e($processSection['section_title']) ?></h2>
      <p class="section-subtitle"><?= e($processSection['section_subtitle']) ?></p>
    </div>
    
    <div class="row">
      <?php foreach ($processSteps as $step): ?>
      <div class="col-md-4 mb-4">
        <div class="process-step text-center">
          <?php if (!empty($step['image_path'])): ?>
          <div class="step-icon mb-3">
            <img src="<?= e($step['image_path']) ?>" alt="Step <?= e($step['step_number']) ?>" width="80" height="80">
          </div>
          <?php else: ?>
          <div class="step-number mb-3">
            <span class="badge bg-primary rounded-circle p-3"><?= e($step['step_number']) ?></span>
          </div>
          <?php endif; ?>
          <h3 class="h5"><?= e($step['step_title']) ?></h3>
          <p><?= e($step['step_description']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Company Info Section -->
<?php if ($companyInfo): ?>
<section class="company-info py-5 bg-light">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <div class="company-story">
          <?= $companyInfo['company_description'] ?>
        </div>
        
        <div class="key-metrics mt-4">
          <div class="row">
            <div class="col-md-4 mb-3">
              <div class="metric text-center">
                <h3 class="h4"><?= e($companyInfo['metric_1_value']) ?></h3>
                <p class="text-muted"><?= e($companyInfo['metric_1_label']) ?></p>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="metric text-center">
                <h3 class="h4"><?= e($companyInfo['metric_2_value']) ?></h3>
                <p class="text-muted"><?= e($companyInfo['metric_2_label']) ?></p>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="metric text-center">
                <h3 class="h4"><?= e($companyInfo['metric_3_value']) ?></h3>
                <p class="text-muted"><?= e($companyInfo['metric_3_label']) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="value-propositions mt-4 mt-lg-0">
          <h3 class="h5 mb-3">Why Choose Us</h3>
          <ul class="list-group">
            <?php foreach ($valueProps as $prop): ?>
            <li class="list-group-item border-0 ps-0">
              <i class="bi bi-check-circle-fill text-success me-2"></i>
              <?= e($prop['proposition_text']) ?>
            </li>
            <?php endforeach; ?>
          </ul>
          
          <?php if (!empty($companyInfo['final_tagline'])): ?>
          <div class="tagline mt-4">
            <p class="fw-bold"><?= e($companyInfo['final_tagline']) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Portfolio Section -->
<?php if ($portfolioSection && !empty($featuredProjects)): ?>
<section class="portfolio-section py-5 bg-light">
    <div class="container">
        <div class="row mb-4 text-center">
            <div class="col-lg-8 mx-auto">
                <h2 class="section-title"><?= e($portfolioSection['section_title']) ?></h2>
                <?php if (!empty($portfolioSection['section_description'])): ?>
                <p class="section-description"><?= e($portfolioSection['section_description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($featuredProjects as $project): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="portfolio-item card h-100">
                    <?php if (!empty($project['image_path'])): ?>
                    <div class="portfolio-image">
                        <img src="<?= e($project['image_path']) ?>" alt="<?= e($project['title']) ?>" class="card-img-top">
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= e($project['title']) ?></h5>
                        <?php if (!empty($project['category'])): ?>
                        <div class="project-category mb-2">
                            <span class="badge bg-primary"><?= e($project['category']) ?></span>
                            <?php if (!empty($project['property_type'])): ?>
                            <span class="badge bg-secondary"><?= e($project['property_type']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($project['description'])): ?>
                        <p class="card-text"><?= e(substr($project['description'], 0, 100)) ?>...</p>
                        <?php endif; ?>
                        <a href="portfolio-detail.php?id=<?= e($project['id']) ?>" class="btn btn-outline-primary btn-sm">View Project</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="portfolio.php" class="btn btn-primary">View All Projects</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="py-5">
  <h2 class="h4 mb-3">Featured Projects</h2>
  <div class="row g-3">
    <?php foreach ($projects as $p): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <img src="<?= e($p['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
          <div class="card-body">
            <h3 class="h6 mb-1"><?= e($p['title']) ?></h3>
            <div class="text-muted small"><?= e($p['category'] ?? 'Uncategorized') ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="py-5">
  <h2 class="h4 mb-3">Our Services</h2>
  <div class="row g-3">
    <?php foreach ($services as $s): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <img src="<?= e($s['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($s['title']) ?>">
          <div class="card-body">
            <h3 class="h6 mb-1"><?= e($s['title']) ?></h3>
            <p class="small text-muted mb-0"><?= e(mb_strimwidth((string)($s['summary'] ?? ''), 0, 120, '…')) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="py-5">
  <h2 class="h4 mb-3">Testimonials</h2>
  <div class="row g-3">
    <?php foreach ($testimonials as $t): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="mb-2">
              <?php for ($i=0; $i < (int)$t['rating']; $i++): ?>⭐<?php endfor; ?>
            </div>
            <blockquote class="blockquote">
              <p class="mb-0"><?= e(mb_strimwidth((string)$t['testimonial'], 0, 180, '…')) ?></p>
            </blockquote>
            <div class="mt-2 fw-semibold">— <?= e($t['client_name']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="py-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="h4 mb-0">Latest from the Blog</h2>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('blog.php')) ?>">View all</a>
  </div>
  <div class="row g-3">
    <?php foreach ($blogPosts as $p): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <img src="<?= e($p['image_path'] ?: base_url('assets/images/placeholder.jpg')) ?>" class="card-img-top" alt="<?= e($p['title']) ?>">
          <div class="card-body">
            <h3 class="h6 mb-1"><?= e($p['title']) ?></h3>
            <div class="small text-muted mb-2"><?= e((string)$p['published_at']) ?></div>
            <p class="small text-muted mb-0"><?= e(mb_strimwidth((string)($p['excerpt'] ?? ''), 0, 120, '…')) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

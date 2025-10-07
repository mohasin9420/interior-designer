<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    redirect(base_url() . '/admin/index.php');
}

$pdo = getPDO();
$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$testimonialId = (int)($_GET['id'] ?? 0);

// Get section data
$section = $pdo->query("SELECT * FROM homepage_testimonials_section WHERE id = 1")->fetch();
if (!$section) {
    // Create default section if it doesn't exist
    $stmt = $pdo->prepare("INSERT INTO homepage_testimonials_section (section_title, section_subtitle) VALUES ('Our Work, Their Words', 'Designing Happy Decor Homes. Real Experiences, Stunning Interiors – Hear from Our Customers!')");
    $stmt->execute();
    $section = $pdo->query("SELECT * FROM homepage_testimonials_section WHERE id = 1")->fetch();
}

// Handle section update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_section'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $sectionTitle = trim($_POST['section_title'] ?? '');
        $sectionSubtitle = trim($_POST['section_subtitle'] ?? '');
        
        if (empty($sectionTitle)) {
            $errors[] = 'Section title is required.';
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE homepage_testimonials_section SET section_title = :title, section_subtitle = :subtitle WHERE id = :id");
            $stmt->execute([
                ':title' => $sectionTitle,
                ':subtitle' => $sectionSubtitle,
                ':id' => $section['id']
            ]);
            
            $success = true;
            $section['section_title'] = $sectionTitle;
            $section['section_subtitle'] = $sectionSubtitle;
        }
    }
}

// Handle testimonial creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $quoteTitle = trim($_POST['quote_title'] ?? '');
        $testimonialText = trim($_POST['testimonial_text'] ?? '');
        $customerName = trim($_POST['customer_name'] ?? '');
        $designExpertName = trim($_POST['design_expert_name'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($quoteTitle) || empty($testimonialText) || empty($customerName)) {
            $errors[] = 'Quote title, testimonial text, and customer name are required.';
        }
        
        // Handle image upload
        $customerImage = '';
        if ($testimonialId > 0) {
            $stmt = $pdo->prepare("SELECT customer_image FROM homepage_testimonials WHERE id = :id");
            $stmt->execute([':id' => $testimonialId]);
            $currentImage = $stmt->fetchColumn();
            $customerImage = $currentImage;
        }
        
        if (isset($_FILES['customer_image']) && $_FILES['customer_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            $fileName = 'testimonial_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $uploadFile = $uploadDir . $fileName;
            
            // Check if it's a valid image
            $check = getimagesize($_FILES['customer_image']['tmp_name']);
            if ($check === false) {
                $errors[] = 'File is not a valid image.';
            } else {
                // Move the uploaded file
                if (move_uploaded_file($_FILES['customer_image']['tmp_name'], $uploadFile)) {
                    $customerImage = 'assets/uploads/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }
        
        // If no errors, save to database
        if (empty($errors)) {
            if ($testimonialId > 0) {
                // Update existing testimonial
                $stmt = $pdo->prepare("
                    UPDATE homepage_testimonials SET 
                    quote_title = :quote_title,
                    testimonial_text = :testimonial_text,
                    customer_name = :customer_name,
                    design_expert_name = :design_expert_name,
                    customer_image = :customer_image,
                    display_order = :display_order,
                    is_active = :is_active
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':quote_title' => $quoteTitle,
                    ':testimonial_text' => $testimonialText,
                    ':customer_name' => $customerName,
                    ':design_expert_name' => $designExpertName,
                    ':customer_image' => $customerImage,
                    ':display_order' => $displayOrder,
                    ':is_active' => $isActive,
                    ':id' => $testimonialId
                ]);
            } else {
                // Create new testimonial
                $stmt = $pdo->prepare("
                    INSERT INTO homepage_testimonials 
                    (section_id, quote_title, testimonial_text, customer_name, design_expert_name, customer_image, display_order, is_active) 
                    VALUES 
                    (:section_id, :quote_title, :testimonial_text, :customer_name, :design_expert_name, :customer_image, :display_order, :is_active)
                ");
                
                $stmt->execute([
                    ':section_id' => $section['id'],
                    ':quote_title' => $quoteTitle,
                    ':testimonial_text' => $testimonialText,
                    ':customer_name' => $customerName,
                    ':design_expert_name' => $designExpertName,
                    ':customer_image' => $customerImage,
                    ':display_order' => $displayOrder,
                    ':is_active' => $isActive
                ]);
            }
            
            $success = true;
            redirect(base_url() . '/admin/homepage_testimonials.php');
        }
    }
}

// Handle testimonial deletion
if ($action === 'delete' && $testimonialId > 0) {
    $stmt = $pdo->prepare("DELETE FROM homepage_testimonials WHERE id = :id");
    $stmt->execute([':id' => $testimonialId]);
    redirect(base_url() . '/admin/homepage_testimonials.php');
}

// Get testimonial for editing
$testimonial = null;
if ($action === 'edit' && $testimonialId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM homepage_testimonials WHERE id = :id");
    $stmt->execute([':id' => $testimonialId]);
    $testimonial = $stmt->fetch();
    
    if (!$testimonial) {
        redirect(base_url() . '/admin/homepage_testimonials.php');
    }
}

// Get all testimonials
$testimonials = $pdo->query("SELECT * FROM homepage_testimonials WHERE section_id = {$section['id']} ORDER BY display_order, id")->fetchAll();

$pageTitle = 'Manage Testimonials Section';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Testimonials Section</h1>
                <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="btn btn-primary">Add New Testimonial</a>
                <?php else: ?>
                    <a href="?action=list" class="btn btn-secondary">Back to List</a>
                <?php endif; ?>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">Changes saved successfully!</div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <!-- Section Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        Section Settings
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="update_section" value="1">
                            
                            <div class="mb-3">
                                <label for="section_title" class="form-label">Section Title</label>
                                <input type="text" class="form-control" id="section_title" name="section_title" value="<?= e($section['section_title']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="section_subtitle" class="form-label">Section Subtitle</label>
                                <textarea class="form-control" id="section_subtitle" name="section_subtitle" rows="2"><?= e($section['section_subtitle']) ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Section Settings</button>
                        </form>
                    </div>
                </div>
                
                <!-- Testimonials List -->
                <div class="card">
                    <div class="card-header">
                        Testimonials
                    </div>
                    <div class="card-body">
                        <?php if (empty($testimonials)): ?>
                            <p class="text-muted">No testimonials added yet. Click "Add New Testimonial" to create one.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Customer</th>
                                            <th>Quote</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testimonials as $t): ?>
                                            <tr>
                                                <td><?= e($t['display_order']) ?></td>
                                                <td>
                                                    <?php if (!empty($t['customer_image'])): ?>
                                                        <img src="<?= base_url() . '/' . e($t['customer_image']) ?>" alt="<?= e($t['customer_name']) ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <?= e($t['customer_name']) ?>
                                                </td>
                                                <td>
                                                    <strong><?= e($t['quote_title']) ?></strong><br>
                                                    <small><?= mb_substr(e($t['testimonial_text']), 0, 100) ?>...</small>
                                                </td>
                                                <td>
                                                    <?= $t['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                                                </td>
                                                <td>
                                                    <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <a href="?action=delete&id=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this testimonial?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Add/Edit Testimonial Form -->
                <div class="card">
                    <div class="card-header">
                        <?= $action === 'edit' ? 'Edit Testimonial' : 'Add New Testimonial' ?>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="save_testimonial" value="1">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quote_title" class="form-label">Quote Title/Headline</label>
                                        <input type="text" class="form-control" id="quote_title" name="quote_title" value="<?= e($testimonial['quote_title'] ?? '') ?>" required>
                                        <div class="form-text">Short quote like "That was delicate..." or "The experience was really amazing!"</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="testimonial_text" class="form-label">Full Testimonial Text</label>
                                        <textarea class="form-control" id="testimonial_text" name="testimonial_text" rows="4" required><?= e($testimonial['testimonial_text'] ?? '') ?></textarea>
                                        <div class="form-text">The complete testimonial from the customer</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Customer Name & Family</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?= e($testimonial['customer_name'] ?? '') ?>" required>
                                        <div class="form-text">Example: "Mr. Narendra & Family" or "Chetan & Anu"</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="design_expert_name" class="form-label">Design Expert Name</label>
                                        <input type="text" class="form-control" id="design_expert_name" name="design_expert_name" value="<?= e($testimonial['design_expert_name'] ?? '') ?>">
                                        <div class="form-text">Example: "Lux Chu won Design Expert" (optional)</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_image" class="form-label">Customer Image/Icon</label>
                                        <?php if (!empty($testimonial['customer_image'])): ?>
                                            <div class="mb-2">
                                                <img src="<?= base_url() . '/' . e($testimonial['customer_image']) ?>" alt="Customer image" class="img-thumbnail" style="max-height: 150px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="customer_image" name="customer_image">
                                        <div class="form-text">Upload a customer photo or representative icon. <?= !empty($testimonial['customer_image']) ? 'Leave empty to keep current image.' : '' ?></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="display_order" class="form-label">Display Order</label>
                                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= e($testimonial['display_order'] ?? '0') ?>" min="0">
                                        <div class="form-text">Lower numbers appear first</div>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?= (isset($testimonial) && $testimonial['is_active']) || !isset($testimonial) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                        <div class="form-text">Uncheck to hide this testimonial from the website</div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Testimonial</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
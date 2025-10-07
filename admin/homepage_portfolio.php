<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';

$pdo = getPDO();
$errors = [];
$success = false;

// Get portfolio section data
try {
    $portfolioSection = $pdo->query("SELECT * FROM homepage_portfolio_section WHERE id = 1")->fetch();
    if (!$portfolioSection) {
        // Create default if not exists
        $pdo->exec("INSERT INTO homepage_portfolio_section (section_title, section_description, is_active) 
                    VALUES ('Our Featured Projects', 'Explore our handpicked selection of stunning interior design projects that showcase our expertise and creativity.', 1)");
        $portfolioSection = $pdo->query("SELECT * FROM homepage_portfolio_section WHERE id = 1")->fetch();
    }
} catch (PDOException $e) {
    // Create the table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `homepage_portfolio_section` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `section_title` varchar(255) NOT NULL DEFAULT 'Our Featured Projects',
        `section_description` text DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Insert default data
    $pdo->exec("INSERT INTO homepage_portfolio_section (id, section_title, section_description, is_active) 
                VALUES (1, 'Our Featured Projects', 'Explore our handpicked selection of stunning interior design projects that showcase our expertise and creativity.', 1)");
    
    $portfolioSection = $pdo->query("SELECT * FROM homepage_portfolio_section WHERE id = 1")->fetch();
}

// Get all portfolio projects
try {
    $allProjects = $pdo->query("
        SELECT p.id, p.title, p.image_path, p.property_type, c.name AS category 
        FROM portfolio p 
        LEFT JOIN project_categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $allProjects = [];
}

// Create featured projects table if it doesn't exist
try {
    // Get featured projects
    $featuredProjects = $pdo->query("
        SELECT f.id, f.portfolio_id, f.display_order, f.is_active, 
               p.title, p.image_path, p.property_type, c.name AS category
        FROM homepage_featured_projects f
        JOIN portfolio p ON f.portfolio_id = p.id
        LEFT JOIN project_categories c ON p.category_id = c.id
        ORDER BY f.display_order, f.id
    ")->fetchAll();
} catch (PDOException $e) {
    // Create the table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `homepage_featured_projects` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `portfolio_id` int(11) NOT NULL,
        `display_order` int(11) NOT NULL DEFAULT 0,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        KEY `portfolio_id` (`portfolio_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $featuredProjects = [];
}

// Handle section update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_section'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $sectionTitle = trim($_POST['section_title'] ?? '');
        $sectionDescription = trim($_POST['section_description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($sectionTitle)) {
            $errors[] = 'Section title is required.';
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE homepage_portfolio_section SET section_title = ?, section_description = ?, is_active = ? WHERE id = 1");
            $stmt->execute([$sectionTitle, $sectionDescription, $isActive]);
            $success = true;
            $portfolioSection['section_title'] = $sectionTitle;
            $portfolioSection['section_description'] = $sectionDescription;
            $portfolioSection['is_active'] = $isActive;
        }
    }
}

// Handle add featured project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_featured'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $portfolioId = (int)($_POST['portfolio_id'] ?? 0);
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($portfolioId <= 0) {
            $errors[] = 'Please select a valid project.';
        }
        
        // Check if project is already featured
        $stmt = $pdo->prepare("SELECT id FROM homepage_featured_projects WHERE portfolio_id = ?");
        $stmt->execute([$portfolioId]);
        if ($stmt->fetch()) {
            $errors[] = 'This project is already featured on the homepage.';
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO homepage_featured_projects (portfolio_id, display_order, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$portfolioId, $displayOrder, $isActive]);
            $success = true;
            
            // Refresh featured projects list
            $featuredProjects = $pdo->query("
                SELECT f.id, f.portfolio_id, f.display_order, f.is_active, 
                       p.title, p.image_path, p.property_type, c.name AS category
                FROM homepage_featured_projects f
                JOIN portfolio p ON f.portfolio_id = p.id
                LEFT JOIN project_categories c ON p.category_id = c.id
                ORDER BY f.display_order, f.id
            ")->fetchAll();
        }
    }
}

// Handle update featured project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_featured'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $featuredId = (int)($_POST['featured_id'] ?? 0);
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($featuredId <= 0) {
            $errors[] = 'Invalid featured project ID.';
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE homepage_featured_projects SET display_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$displayOrder, $isActive, $featuredId]);
            $success = true;
            
            // Refresh featured projects list
            $featuredProjects = $pdo->query("
                SELECT f.id, f.portfolio_id, f.display_order, f.is_active, 
                       p.title, p.image_path, p.property_type, c.name AS category
                FROM homepage_featured_projects f
                JOIN portfolio p ON f.portfolio_id = p.id
                LEFT JOIN project_categories c ON p.category_id = c.id
                ORDER BY f.display_order, f.id
            ")->fetchAll();
        }
    }
}

// Handle remove featured project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_featured'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $featuredId = (int)($_POST['featured_id'] ?? 0);
        
        if ($featuredId <= 0) {
            $errors[] = 'Invalid featured project ID.';
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("DELETE FROM homepage_featured_projects WHERE id = ?");
            $stmt->execute([$featuredId]);
            $success = true;
            
            // Refresh featured projects list
            $featuredProjects = $pdo->query("
                SELECT f.id, f.portfolio_id, f.display_order, f.is_active, 
                       p.title, p.image_path, p.property_type, c.name AS category
                FROM homepage_featured_projects f
                JOIN portfolio p ON f.portfolio_id = p.id
                LEFT JOIN project_categories c ON p.category_id = c.id
                ORDER BY f.display_order, f.id
            ")->fetchAll();
        }
    }
}

$pageTitle = 'Manage Homepage Portfolio Section';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Homepage Portfolio Section</h1>
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
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Portfolio Section Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                
                                <div class="mb-3">
                                    <label for="section_title" class="form-label">Section Title</label>
                                    <input type="text" class="form-control" id="section_title" name="section_title" 
                                           value="<?= e($portfolioSection['section_title']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="section_description" class="form-label">Section Description</label>
                                    <textarea class="form-control" id="section_description" name="section_description" 
                                              rows="3"><?= e($portfolioSection['section_description']) ?></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           <?= $portfolioSection['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Show Portfolio Section on Homepage</label>
                                </div>
                                
                                <button type="submit" name="update_section" class="btn btn-primary">Save Section Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Featured Projects</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                                Add Featured Project
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Property Type</th>
                                            <th>Display Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($featuredProjects)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No featured projects found. Add projects to showcase on the homepage.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($featuredProjects as $project): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($project['image_path'])): ?>
                                                            <img src="<?= e(base_url($project['image_path'])) ?>" width="50" height="50" alt="Project Image">
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= e($project['title']) ?></td>
                                                    <td><?= e($project['category'] ?? 'Uncategorized') ?></td>
                                                    <td><?= e($project['property_type'] ?? 'N/A') ?></td>
                                                    <td><?= e($project['display_order']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $project['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                            <?= $project['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary edit-featured" 
                                                                data-id="<?= e($project['id']) ?>"
                                                                data-order="<?= e($project['display_order']) ?>"
                                                                data-active="<?= e($project['is_active']) ?>"
                                                                data-bs-toggle="modal" data-bs-target="#editProjectModal">
                                                            Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger remove-featured"
                                                                data-id="<?= e($project['id']) ?>"
                                                                data-title="<?= e($project['title']) ?>"
                                                                data-bs-toggle="modal" data-bs-target="#removeProjectModal">
                                                            Remove
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Featured Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addProjectModalLabel">Add Featured Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="portfolio_id" class="form-label">Select Project</label>
                        <select class="form-select" id="portfolio_id" name="portfolio_id" required>
                            <option value="">-- Select a project --</option>
                            <?php foreach ($allProjects as $project): ?>
                                <?php 
                                // Check if project is already featured
                                $isAlreadyFeatured = false;
                                foreach ($featuredProjects as $featured) {
                                    if ($featured['portfolio_id'] == $project['id']) {
                                        $isAlreadyFeatured = true;
                                        break;
                                    }
                                }
                                if (!$isAlreadyFeatured):
                                ?>
                                <option value="<?= e($project['id']) ?>">
                                    <?= e($project['title']) ?> 
                                    <?= !empty($project['category']) ? '(' . e($project['category']) . ')' : '' ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="0">
                        <div class="form-text">Lower numbers will be displayed first.</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_featured" class="btn btn-primary">Add to Homepage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Featured Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="featured_id" id="edit_featured_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">Edit Featured Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="edit_display_order" name="display_order" min="0" value="0">
                        <div class="form-text">Lower numbers will be displayed first.</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_featured" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Featured Project Modal -->
<div class="modal fade" id="removeProjectModal" tabindex="-1" aria-labelledby="removeProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="featured_id" id="remove_featured_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="removeProjectModalLabel">Remove Featured Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove <strong id="remove_project_title"></strong> from the homepage featured projects?</p>
                    <p>This will not delete the project from your portfolio, only remove it from the homepage.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="remove_featured" class="btn btn-danger">Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit featured project button clicks
    document.querySelectorAll('.edit-featured').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const order = this.getAttribute('data-order');
            const active = this.getAttribute('data-active') === '1';
            
            document.getElementById('edit_featured_id').value = id;
            document.getElementById('edit_display_order').value = order;
            document.getElementById('edit_is_active').checked = active;
        });
    });
    
    // Handle remove featured project button clicks
    document.querySelectorAll('.remove-featured').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            
            document.getElementById('remove_featured_id').value = id;
            document.getElementById('remove_project_title').textContent = title;
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
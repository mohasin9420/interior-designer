<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';

$pdo = getPDO();
$errors = [];
$success = false;

// Get company info data
$companyInfo = $pdo->query("SELECT * FROM homepage_company_info WHERE id = 1")->fetch();
if (!$companyInfo) {
    // Create default if not exists
    $pdo->exec("INSERT INTO homepage_company_info (section_title, company_description, years_experience, projects_completed, happy_clients, awards_won, is_active) 
                VALUES ('About Decorpot', 'Decorpot is a leading interior design company specializing in custom solutions for homes and offices.', 10, 500, 450, 25, 1)");
    $companyInfo = $pdo->query("SELECT * FROM homepage_company_info WHERE id = 1")->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company_info'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $sectionTitle = trim($_POST['section_title'] ?? '');
        $companyDescription = trim($_POST['company_description'] ?? '');
        $yearsExperience = (int)($_POST['years_experience'] ?? 0);
        $projectsCompleted = (int)($_POST['projects_completed'] ?? 0);
        $happyClients = (int)($_POST['happy_clients'] ?? 0);
        $awardsWon = (int)($_POST['awards_won'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate inputs
        if (empty($sectionTitle) || empty($companyDescription)) {
            $errors[] = 'Section title and company description are required.';
        }
        
        if (empty($errors)) {
            // Handle image upload
            $imagePath = $companyInfo['company_image'];
            if (!empty($_FILES['company_image']['name'])) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                $fileName = 'company_info_' . time() . '_' . substr(md5(rand()), 0, 8) . '.jpg';
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['company_image']['tmp_name'], $uploadFile)) {
                    $imagePath = 'assets/uploads/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
            
            if (empty($errors)) {
                $sql = "UPDATE homepage_company_info SET 
                        section_title = ?, 
                        company_description = ?, 
                        years_experience = ?, 
                        projects_completed = ?, 
                        happy_clients = ?, 
                        awards_won = ?, 
                        is_active = ?";
                
                $params = [$sectionTitle, $companyDescription, $yearsExperience, $projectsCompleted, $happyClients, $awardsWon, $isActive];
                
                if ($imagePath) {
                    $sql .= ", company_image = ?";
                    $params[] = $imagePath;
                }
                
                $sql .= " WHERE id = 1";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $success = true;
                // Refresh company info data
                $companyInfo = $pdo->query("SELECT * FROM homepage_company_info WHERE id = 1")->fetch();
            }
        }
    }
}

// Handle value proposition management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_value_proposition'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $propId = (int)($_POST['prop_id'] ?? 0);
        $propTitle = trim($_POST['prop_title'] ?? '');
        $propDescription = trim($_POST['prop_description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['prop_is_active']) ? 1 : 0;
        
        // Validate inputs
        if (empty($propTitle) || empty($propDescription)) {
            $errors[] = 'Title and description are required.';
        }
        
        if (empty($errors)) {
            // Handle icon upload
            $iconPath = null;
            if (!empty($_FILES['prop_icon']['name'])) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                $fileName = 'value_prop_' . time() . '_' . substr(md5(rand()), 0, 8) . '.jpg';
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['prop_icon']['tmp_name'], $uploadFile)) {
                    $iconPath = 'assets/uploads/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload icon.';
                }
            }
            
            if (empty($errors)) {
                if ($propId > 0) {
                    // Update existing value proposition
                    $sql = "UPDATE homepage_value_propositions SET 
                            title = ?, 
                            description = ?, 
                            display_order = ?, 
                            is_active = ?";
                    
                    $params = [$propTitle, $propDescription, $displayOrder, $isActive];
                    
                    if ($iconPath) {
                        $sql .= ", icon = ?";
                        $params[] = $iconPath;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $propId;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                } else {
                    // Create new value proposition
                    $stmt = $pdo->prepare("INSERT INTO homepage_value_propositions 
                                          (title, description, icon, display_order, is_active) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$propTitle, $propDescription, $iconPath, $displayOrder, $isActive]);
                }
                
                $success = true;
            }
        }
    }
}

// Handle value proposition deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_value_proposition'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $propId = (int)($_POST['prop_id'] ?? 0);
        
        if ($propId > 0) {
            $stmt = $pdo->prepare("DELETE FROM homepage_value_propositions WHERE id = ?");
            $stmt->execute([$propId]);
            $success = true;
        }
    }
}

// Get all value propositions
$valueProps = $pdo->query("SELECT * FROM homepage_value_propositions ORDER BY display_order")->fetchAll();

$pageTitle = 'Manage Company Info Section';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Company Info Section</h1>
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
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Company Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="section_title" class="form-label">Section Title</label>
                                            <input type="text" class="form-control" id="section_title" name="section_title" 
                                                   value="<?= e($companyInfo['section_title']) ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="company_description" class="form-label">Company Description</label>
                                            <textarea class="form-control" id="company_description" name="company_description" 
                                                      rows="5" required><?= e($companyInfo['company_description']) ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                                   <?= $companyInfo['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="company_image" class="form-label">Company Image</label>
                                            <input type="file" class="form-control" id="company_image" name="company_image" accept="image/*">
                                            <?php if (!empty($companyInfo['company_image'])): ?>
                                                <div class="mt-2">
                                                    <p>Current image:</p>
                                                    <img src="<?= e(base_url($companyInfo['company_image'])) ?>" alt="Company Image" 
                                                         style="max-width: 200px; max-height: 200px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="years_experience" class="form-label">Years of Experience</label>
                                                    <input type="number" class="form-control" id="years_experience" name="years_experience" 
                                                           value="<?= e($companyInfo['years_experience']) ?>" min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="projects_completed" class="form-label">Projects Completed</label>
                                                    <input type="number" class="form-control" id="projects_completed" name="projects_completed" 
                                                           value="<?= e($companyInfo['projects_completed']) ?>" min="0" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="happy_clients" class="form-label">Happy Clients</label>
                                                    <input type="number" class="form-control" id="happy_clients" name="happy_clients" 
                                                           value="<?= e($companyInfo['happy_clients']) ?>" min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="awards_won" class="form-label">Awards Won</label>
                                                    <input type="number" class="form-control" id="awards_won" name="awards_won" 
                                                           value="<?= e($companyInfo['awards_won']) ?>" min="0" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_company_info" class="btn btn-primary">Save Company Info</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Value Propositions</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#propModal">
                                Add New Value Proposition
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Icon</th>
                                            <th>Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($valueProps)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No value propositions found. Add your first one!</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($valueProps as $prop): ?>
                                                <tr>
                                                    <td><?= e($prop['title']) ?></td>
                                                    <td><?= e(substr($prop['description'], 0, 50)) ?>...</td>
                                                    <td>
                                                        <?php if (!empty($prop['icon'])): ?>
                                                            <img src="<?= e(base_url($prop['icon'])) ?>" width="50" height="50" alt="Icon">
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Icon</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= e($prop['display_order']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $prop['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                            <?= $prop['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary edit-prop" 
                                                                data-id="<?= e($prop['id']) ?>"
                                                                data-title="<?= e($prop['title']) ?>"
                                                                data-description="<?= e($prop['description']) ?>"
                                                                data-order="<?= e($prop['display_order']) ?>"
                                                                data-active="<?= e($prop['is_active']) ?>"
                                                                data-bs-toggle="modal" data-bs-target="#propModal">
                                                            Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger delete-prop"
                                                                data-id="<?= e($prop['id']) ?>"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                            Delete
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

<!-- Value Proposition Modal -->
<div class="modal fade" id="propModal" tabindex="-1" aria-labelledby="propModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="prop_id" id="prop_id" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="propModalLabel">Add/Edit Value Proposition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="prop_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="prop_title" name="prop_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="prop_description" class="form-label">Description</label>
                        <textarea class="form-control" id="prop_description" name="prop_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="prop_icon" class="form-label">Icon (Optional)</label>
                        <input type="file" class="form-control" id="prop_icon" name="prop_icon" accept="image/*">
                        <div id="icon_preview_container" class="mt-2 d-none">
                            <p>Current icon:</p>
                            <img id="icon_preview" src="" alt="Current Icon" style="max-width: 100px; max-height: 100px;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="0">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="prop_is_active" name="prop_is_active" checked>
                        <label class="form-check-label" for="prop_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_value_proposition" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="prop_id" id="delete_prop_id" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this value proposition? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_value_proposition" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit value proposition button clicks
    document.querySelectorAll('.edit-prop').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const order = this.getAttribute('data-order');
            const active = this.getAttribute('data-active') === '1';
            
            document.getElementById('prop_id').value = id;
            document.getElementById('prop_title').value = title;
            document.getElementById('prop_description').value = description;
            document.getElementById('display_order').value = order;
            document.getElementById('prop_is_active').checked = active;
            
            document.getElementById('propModalLabel').textContent = 'Edit Value Proposition';
        });
    });
    
    // Handle delete value proposition button clicks
    document.querySelectorAll('.delete-prop').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('delete_prop_id').value = id;
        });
    });
    
    // Reset modal form when adding a new value proposition
    const propModal = document.getElementById('propModal');
    propModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('prop_id').value = '0';
        document.getElementById('prop_title').value = '';
        document.getElementById('prop_description').value = '';
        document.getElementById('display_order').value = '0';
        document.getElementById('prop_is_active').checked = true;
        document.getElementById('propModalLabel').textContent = 'Add New Value Proposition';
        document.getElementById('icon_preview_container').classList.add('d-none');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
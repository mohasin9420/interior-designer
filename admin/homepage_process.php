<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';

$pdo = getPDO();
$errors = [];
$success = false;

// Get process section data
$processSection = $pdo->query("SELECT * FROM homepage_process_section WHERE id = 1")->fetch();
if (!$processSection) {
    // Create default if not exists
    $pdo->exec("INSERT INTO homepage_process_section (section_title, is_active) VALUES ('How Does Our Interior Designers Work', 1)");
    $processSection = $pdo->query("SELECT * FROM homepage_process_section WHERE id = 1")->fetch();
}

// Get all process steps
$processSteps = $pdo->query("SELECT * FROM homepage_process_steps ORDER BY display_order, step_number")->fetchAll();

// Handle section update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_section'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $sectionTitle = trim($_POST['section_title'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($sectionTitle)) {
            $errors[] = 'Section title is required.';
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE homepage_process_section SET section_title = ?, is_active = ? WHERE id = 1");
            $stmt->execute([$sectionTitle, $isActive]);
            $success = true;
            $processSection['section_title'] = $sectionTitle;
            $processSection['is_active'] = $isActive;
        }
    }
}

// Handle step creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_step'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $stepId = (int)($_POST['step_id'] ?? 0);
        $stepNumber = (int)($_POST['step_number'] ?? 0);
        $stepTitle = trim($_POST['step_title'] ?? '');
        $stepDescription = trim($_POST['step_description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['step_is_active']) ? 1 : 0;
        
        // Validate inputs
        if (empty($stepTitle) || empty($stepDescription) || $stepNumber < 1) {
            $errors[] = 'Step number, title and description are required.';
        }
        
        if (empty($errors)) {
            // Handle image upload
            $imagePath = null;
            if (!empty($_FILES['step_image']['name'])) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                $fileName = 'process_step_' . time() . '_' . substr(md5(rand()), 0, 8) . '.jpg';
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['step_image']['tmp_name'], $uploadFile)) {
                    $imagePath = 'assets/uploads/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
            
            if (empty($errors)) {
                if ($stepId > 0) {
                    // Update existing step
                    $sql = "UPDATE homepage_process_steps SET 
                            step_number = ?, 
                            step_title = ?, 
                            step_description = ?, 
                            display_order = ?, 
                            is_active = ?";
                    
                    $params = [$stepNumber, $stepTitle, $stepDescription, $displayOrder, $isActive];
                    
                    if ($imagePath) {
                        $sql .= ", step_image = ?";
                        $params[] = $imagePath;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $stepId;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                } else {
                    // Create new step
                    $stmt = $pdo->prepare("INSERT INTO homepage_process_steps 
                                          (section_id, step_number, step_title, step_description, step_image, display_order, is_active) 
                                          VALUES (1, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$stepNumber, $stepTitle, $stepDescription, $imagePath, $displayOrder, $isActive]);
                }
                
                $success = true;
                // Refresh steps list
                $processSteps = $pdo->query("SELECT * FROM homepage_process_steps ORDER BY display_order, step_number")->fetchAll();
            }
        }
    }
}

// Handle step deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_step'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $stepId = (int)($_POST['step_id'] ?? 0);
        
        if ($stepId > 0) {
            $stmt = $pdo->prepare("DELETE FROM homepage_process_steps WHERE id = ?");
            $stmt->execute([$stepId]);
            $success = true;
            // Refresh steps list
            $processSteps = $pdo->query("SELECT * FROM homepage_process_steps ORDER BY display_order, step_number")->fetchAll();
        }
    }
}

$pageTitle = 'Manage Process Steps Section';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Process Steps Section</h1>
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
                            <h5>Process Section Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                
                                <div class="mb-3">
                                    <label for="section_title" class="form-label">Section Title</label>
                                    <input type="text" class="form-control" id="section_title" name="section_title" 
                                           value="<?= e($processSection['section_title']) ?>" required>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           <?= $processSection['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
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
                            <h5>Process Steps</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#stepModal">
                                Add New Step
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Step #</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Image</th>
                                            <th>Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($processSteps)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No process steps found. Add your first step!</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($processSteps as $step): ?>
                                                <tr>
                                                    <td><?= e($step['step_number']) ?></td>
                                                    <td><?= e($step['step_title']) ?></td>
                                                    <td><?= e(substr($step['step_description'], 0, 50)) ?>...</td>
                                                    <td>
                                                        <?php if (!empty($step['step_image'])): ?>
                                                            <img src="<?= e(base_url($step['step_image'])) ?>" width="50" height="50" alt="Step Image">
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= e($step['display_order']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $step['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                            <?= $step['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary edit-step" 
                                                                data-id="<?= e($step['id']) ?>"
                                                                data-number="<?= e($step['step_number']) ?>"
                                                                data-title="<?= e($step['step_title']) ?>"
                                                                data-description="<?= e($step['step_description']) ?>"
                                                                data-order="<?= e($step['display_order']) ?>"
                                                                data-active="<?= e($step['is_active']) ?>"
                                                                data-bs-toggle="modal" data-bs-target="#stepModal">
                                                            Edit
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger delete-step"
                                                                data-id="<?= e($step['id']) ?>"
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

<!-- Step Modal -->
<div class="modal fade" id="stepModal" tabindex="-1" aria-labelledby="stepModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="step_id" id="step_id" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="stepModalLabel">Add/Edit Process Step</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="step_number" class="form-label">Step Number</label>
                                <input type="number" class="form-control" id="step_number" name="step_number" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="step_title" class="form-label">Step Title</label>
                        <input type="text" class="form-control" id="step_title" name="step_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="step_description" class="form-label">Step Description</label>
                        <textarea class="form-control" id="step_description" name="step_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="step_image" class="form-label">Step Image (Optional)</label>
                        <input type="file" class="form-control" id="step_image" name="step_image" accept="image/*">
                        <div id="image_preview_container" class="mt-2 d-none">
                            <p>Current image:</p>
                            <img id="image_preview" src="" alt="Current Image" style="max-width: 200px; max-height: 200px;">
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="step_is_active" name="step_is_active" checked>
                        <label class="form-check-label" for="step_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_step" class="btn btn-primary">Save Step</button>
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
                <input type="hidden" name="step_id" id="delete_step_id" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this process step? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_step" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit step button clicks
    document.querySelectorAll('.edit-step').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const number = this.getAttribute('data-number');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const order = this.getAttribute('data-order');
            const active = this.getAttribute('data-active') === '1';
            
            document.getElementById('step_id').value = id;
            document.getElementById('step_number').value = number;
            document.getElementById('step_title').value = title;
            document.getElementById('step_description').value = description;
            document.getElementById('display_order').value = order;
            document.getElementById('step_is_active').checked = active;
            
            document.getElementById('stepModalLabel').textContent = 'Edit Process Step';
        });
    });
    
    // Handle delete step button clicks
    document.querySelectorAll('.delete-step').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('delete_step_id').value = id;
        });
    });
    
    // Reset modal form when adding a new step
    const stepModal = document.getElementById('stepModal');
    stepModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('step_id').value = '0';
        document.getElementById('step_number').value = '';
        document.getElementById('step_title').value = '';
        document.getElementById('step_description').value = '';
        document.getElementById('display_order').value = '0';
        document.getElementById('step_is_active').checked = true;
        document.getElementById('stepModalLabel').textContent = 'Add New Process Step';
        document.getElementById('image_preview_container').classList.add('d-none');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
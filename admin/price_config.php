<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getPDO();
$errors = [];
$success = null;
$editing = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $propertyType = trim((string)($_POST['property_type'] ?? ''));
        $roomType = trim((string)($_POST['room_type'] ?? ''));
        $designStyle = trim((string)($_POST['design_style'] ?? ''));
        $basePrice = (float)($_POST['base_price'] ?? 0);
        $pricePerSqft = (float)($_POST['price_per_sqft'] ?? 0);

        if ($propertyType === '' || $roomType === '' || $designStyle === '') {
            $errors[] = 'Property type, room type, and design style are required.';
        }

        if ($basePrice <= 0 || $pricePerSqft <= 0) {
            $errors[] = 'Base price and price per sq. ft. must be greater than zero.';
        }

        if (!$errors) {
            if ($id > 0) {
                // Update existing record
                $stmt = $pdo->prepare('UPDATE price_config SET 
                    property_type = :property_type, 
                    room_type = :room_type, 
                    design_style = :design_style, 
                    base_price = :base_price, 
                    price_per_sqft = :price_per_sqft 
                    WHERE id = :id');
                $stmt->execute([
                    ':property_type' => $propertyType,
                    ':room_type' => $roomType,
                    ':design_style' => $designStyle,
                    ':base_price' => $basePrice,
                    ':price_per_sqft' => $pricePerSqft,
                    ':id' => $id
                ]);
                $success = 'Price configuration updated successfully.';
            } else {
                // Insert new record
                $stmt = $pdo->prepare('INSERT INTO price_config (property_type, room_type, design_style, base_price, price_per_sqft) 
                    VALUES (:property_type, :room_type, :design_style, :base_price, :price_per_sqft)');
                $stmt->execute([
                    ':property_type' => $propertyType,
                    ':room_type' => $roomType,
                    ':design_style' => $designStyle,
                    ':base_price' => $basePrice,
                    ':price_per_sqft' => $pricePerSqft
                ]);
                $success = 'Price configuration added successfully.';
            }
        }
    }
    
    // If no errors and not editing, redirect to clear the form
    if (!$errors && empty($_POST['edit'])) {
        header('Location: price_config.php?success=' . urlencode($success));
        exit;
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM price_config WHERE id = ?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM price_config WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: price_config.php?success=' . urlencode('Price configuration deleted successfully.'));
    exit;
}

// Get success message from URL if redirected
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Fetch all price configurations
$stmt = $pdo->query('SELECT * FROM price_config ORDER BY property_type, room_type, design_style');
$priceConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct property types, room types, and design styles for dropdowns
$propertyTypes = [];
$roomTypes = [];
$designStyles = [];

$stmt = $pdo->query('SELECT DISTINCT property_type FROM price_config ORDER BY property_type');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $propertyTypes[] = $row['property_type'];
}

$stmt = $pdo->query('SELECT DISTINCT room_type FROM price_config ORDER BY room_type');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roomTypes[] = $row['room_type'];
}

$stmt = $pdo->query('SELECT DISTINCT design_style FROM price_config ORDER BY design_style');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $designStyles[] = $row['design_style'];
}

// Add default values if empty
if (empty($propertyTypes)) {
    $propertyTypes = ['1BHK', '2BHK', '3BHK', '4BHK', 'Villa', 'Office'];
}
if (empty($roomTypes)) {
    $roomTypes = ['Living Room', 'Bedroom', 'Kitchen', 'Bathroom', 'Dining Room', 'Study Room'];
}
if (empty($designStyles)) {
    $designStyles = ['Modern', 'Contemporary', 'Traditional', 'Minimalist', 'Industrial', 'Scandinavian'];
}

$pageTitle = 'Manage Price Configurations';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Price Configurations</h1>
    <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formCollapse" aria-expanded="<?= $editing ? 'true' : 'false' ?>">
        <?= $editing ? 'Edit Configuration' : 'Add New Configuration' ?>
    </button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
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

<div class="collapse <?= ($editing || !empty($errors)) ? 'show' : '' ?>" id="formCollapse">
    <div class="card mb-4">
        <div class="card-header">
            <?= $editing ? 'Edit Price Configuration' : 'Add New Price Configuration' ?>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $editing ? e($editing['id']) : '0' ?>">
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Property Type</label>
                        <input type="text" class="form-control" name="property_type" list="propertyTypeList" value="<?= $editing ? e($editing['property_type']) : '' ?>" required>
                        <datalist id="propertyTypeList">
                            <?php foreach ($propertyTypes as $type): ?>
                                <option value="<?= e($type) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Room Type</label>
                        <input type="text" class="form-control" name="room_type" list="roomTypeList" value="<?= $editing ? e($editing['room_type']) : '' ?>" required>
                        <datalist id="roomTypeList">
                            <?php foreach ($roomTypes as $type): ?>
                                <option value="<?= e($type) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Design Style</label>
                        <input type="text" class="form-control" name="design_style" list="designStyleList" value="<?= $editing ? e($editing['design_style']) : '' ?>" required>
                        <datalist id="designStyleList">
                            <?php foreach ($designStyles as $style): ?>
                                <option value="<?= e($style) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Base Price (₹)</label>
                        <input type="number" class="form-control" name="base_price" min="1" step="1000" value="<?= $editing ? e($editing['base_price']) : '75000' ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Price Per Sq. Ft. (₹)</label>
                        <input type="number" class="form-control" name="price_per_sqft" min="1" step="50" value="<?= $editing ? e($editing['price_per_sqft']) : '1200' ?>" required>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?> Configuration</button>
                    <?php if ($editing): ?>
                        <a href="price_config.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        All Price Configurations
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Property Type</th>
                        <th>Room Type</th>
                        <th>Design Style</th>
                        <th>Base Price (₹)</th>
                        <th>Price Per Sq. Ft. (₹)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($priceConfigs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-3">No price configurations found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($priceConfigs as $config): ?>
                            <tr>
                                <td><?= e($config['property_type']) ?></td>
                                <td><?= e($config['room_type']) ?></td>
                                <td><?= e($config['design_style']) ?></td>
                                <td><?= number_format($config['base_price']) ?></td>
                                <td><?= number_format($config['price_per_sqft']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?= $config['id'] ?>" class="btn btn-outline-primary">Edit</a>
                                        <a href="?delete=<?= $config['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this configuration?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
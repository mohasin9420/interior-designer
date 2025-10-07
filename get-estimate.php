<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pageTitle = "Get a Free Estimate | Interior Design Cost Calculator";
$pageDescription = "Calculate the cost of your interior design project with our free online estimator. Get instant pricing based on your requirements.";

$pdo = getPDO();
$errors = [];
$success = false;

// Fetch price configuration data
$propertyTypes = [];
$roomTypes = [];
$designStyles = [];

try {
    // Get property types
    $stmt = $pdo->query("SELECT DISTINCT property_type FROM price_config ORDER BY property_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $propertyTypes[] = $row['property_type'];
    }
    
    // Get room types
    $stmt = $pdo->query("SELECT DISTINCT room_type FROM price_config ORDER BY room_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $roomTypes[] = $row['room_type'];
    }
    
    // Get design styles
    $stmt = $pdo->query("SELECT DISTINCT design_style FROM price_config ORDER BY design_style");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $designStyles[] = $row['design_style'];
    }
} catch (PDOException $e) {
    // Fallback values if database query fails
    $propertyTypes = ['1BHK', '2BHK', '3BHK', '4BHK', 'Villa', 'Office'];
    $roomTypes = ['Living Room', 'Bedroom', 'Kitchen', 'Bathroom', 'Dining Room', 'Study Room'];
    $designStyles = ['Modern', 'Contemporary', 'Traditional', 'Minimalist', 'Industrial', 'Scandinavian'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $propertyType = trim((string)($_POST['property_type'] ?? ''));
        $roomType = trim((string)($_POST['room_type'] ?? ''));
        $designStyle = trim((string)($_POST['design_style'] ?? ''));
        $area = (int)($_POST['area'] ?? 0);
        $estimatedCost = (float)($_POST['estimated_cost'] ?? 0);
        $message = trim((string)($_POST['message'] ?? ''));
        
        if ($name === '' || $phone === '') {
            $errors[] = 'Name and phone are required.';
        }
        
        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO contact_submissions (type, name, email, phone, property_type, room_type, design_style, area, estimated_cost, message) 
                                  VALUES (\'estimate\', :name, :email, :phone, :ptype, :rtype, :dstyle, :area, :cost, :message)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email ?: null,
                ':phone' => $phone,
                ':ptype' => $propertyType ?: null,
                ':rtype' => $roomType ?: null,
                ':dstyle' => $designStyle ?: null,
                ':area' => $area ?: null,
                ':cost' => $estimatedCost ?: null,
                ':message' => $message ?: null,
            ]);
            $success = true;
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 fw-bold mb-3">Interior Design Cost Calculator</h1>
            <p class="lead">Get an instant estimate for your interior design project. Fill in the details below to calculate your project cost.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <h4>Thank you for your submission!</h4>
            <p>We've received your estimate request and will contact you shortly to discuss your project in detail.</p>
        </div>
    <?php elseif (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="post" id="estimateForm">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="estimated_cost" id="estimatedCostInput" value="0">
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h3 class="h5 mb-3">Project Details</h3>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" name="property_type" id="propertyType" required>
                                    <option value="">Select Property Type</option>
                                    <?php foreach ($propertyTypes as $type): ?>
                                        <option value="<?= e($type) ?>"><?= e($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Room Type</label>
                                <select class="form-select" name="room_type" id="roomType" required>
                                    <option value="">Select Room Type</option>
                                    <?php foreach ($roomTypes as $type): ?>
                                        <option value="<?= e($type) ?>"><?= e($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Design Style</label>
                                <select class="form-select" name="design_style" id="designStyle" required>
                                    <option value="">Select Design Style</option>
                                    <?php foreach ($designStyles as $style): ?>
                                        <option value="<?= e($style) ?>"><?= e($style) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Area (sq. ft.)</label>
                                <input type="number" class="form-control" name="area" id="area" min="50" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h3 class="h5 mb-3">Additional Options</h3>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="customFurniture">
                                    <label class="form-check-label" for="customFurniture">
                                        Custom Furniture (+15%)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="smartHome">
                                    <label class="form-check-label" for="smartHome">
                                        Smart Home Integration (+10%)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sustainableMaterials">
                                    <label class="form-check-label" for="sustainableMaterials">
                                        Sustainable Materials (+8%)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="expeditedTimeline">
                                    <label class="form-check-label" for="expeditedTimeline">
                                        Expedited Timeline (+12%)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <button type="button" id="calculateBtn" class="btn btn-primary">Calculate Estimate</button>
                            </div>
                        </div>
                        
                        <div id="estimateResult" class="d-none">
                            <div class="alert alert-info mb-4">
                                <h4 class="alert-heading">Your Estimated Cost</h4>
                                <p class="display-6 fw-bold" id="estimatedCost">₹0</p>
                                <p class="mb-0">This is an approximate estimate based on your inputs. For a detailed quote, please provide your contact information below.</p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h3 class="h5 mb-3">Your Contact Information</h3>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input class="form-control" name="name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="phone" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label class="form-label">Additional Requirements</label>
                                    <textarea class="form-control" name="message" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-success" type="submit">Submit Request</button>
                                    <button type="button" id="recalculateBtn" class="btn btn-outline-secondary">Recalculate</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="h5 mb-3">Why Choose Us</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Professional designers with 10+ years experience</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Transparent pricing with no hidden costs</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Quality materials and craftsmanship</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> On-time project delivery</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 5-year warranty on all installations</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="h5 mb-3">Our Process</h3>
                    <ol class="ps-3">
                        <li class="mb-2">Initial consultation and requirements gathering</li>
                        <li class="mb-2">Design concept presentation</li>
                        <li class="mb-2">Detailed planning and material selection</li>
                        <li class="mb-2">Execution and installation</li>
                        <li class="mb-2">Final walkthrough and handover</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Price configuration data (fallback if database fails)
    const priceConfig = {
        '1BHK': {
            'Living Room': {
                'Modern': { basePrice: 75000, pricePerSqft: 1200 },
                'Contemporary': { basePrice: 85000, pricePerSqft: 1300 },
                'Traditional': { basePrice: 70000, pricePerSqft: 1100 },
                'Minimalist': { basePrice: 65000, pricePerSqft: 1000 },
                'Industrial': { basePrice: 80000, pricePerSqft: 1250 },
                'Scandinavian': { basePrice: 75000, pricePerSqft: 1150 }
            },
            'Bedroom': {
                'Modern': { basePrice: 60000, pricePerSqft: 1000 },
                'Contemporary': { basePrice: 70000, pricePerSqft: 1100 },
                'Traditional': { basePrice: 65000, pricePerSqft: 950 },
                'Minimalist': { basePrice: 55000, pricePerSqft: 900 },
                'Industrial': { basePrice: 65000, pricePerSqft: 1050 },
                'Scandinavian': { basePrice: 60000, pricePerSqft: 1000 }
            },
            'Kitchen': {
                'Modern': { basePrice: 90000, pricePerSqft: 1500 },
                'Contemporary': { basePrice: 100000, pricePerSqft: 1600 },
                'Traditional': { basePrice: 85000, pricePerSqft: 1400 },
                'Minimalist': { basePrice: 80000, pricePerSqft: 1300 },
                'Industrial': { basePrice: 95000, pricePerSqft: 1550 },
                'Scandinavian': { basePrice: 90000, pricePerSqft: 1450 }
            }
        },
        '2BHK': {
            'Living Room': {
                'Modern': { basePrice: 90000, pricePerSqft: 1300 },
                'Contemporary': { basePrice: 100000, pricePerSqft: 1400 },
                'Traditional': { basePrice: 85000, pricePerSqft: 1200 },
                'Minimalist': { basePrice: 80000, pricePerSqft: 1100 },
                'Industrial': { basePrice: 95000, pricePerSqft: 1350 },
                'Scandinavian': { basePrice: 90000, pricePerSqft: 1250 }
            }
        },
        '3BHK': {
            'Living Room': {
                'Modern': { basePrice: 120000, pricePerSqft: 1400 },
                'Contemporary': { basePrice: 130000, pricePerSqft: 1500 },
                'Traditional': { basePrice: 110000, pricePerSqft: 1300 },
                'Minimalist': { basePrice: 100000, pricePerSqft: 1200 },
                'Industrial': { basePrice: 125000, pricePerSqft: 1450 },
                'Scandinavian': { basePrice: 115000, pricePerSqft: 1350 }
            }
        }
    };
    
    // Default values for any missing combinations
    const defaultPricing = { basePrice: 75000, pricePerSqft: 1200 };
    
    // Elements
    const calculateBtn = document.getElementById('calculateBtn');
    const recalculateBtn = document.getElementById('recalculateBtn');
    const estimateResult = document.getElementById('estimateResult');
    const estimatedCost = document.getElementById('estimatedCost');
    const estimatedCostInput = document.getElementById('estimatedCostInput');
    
    // Form elements
    const propertyType = document.getElementById('propertyType');
    const roomType = document.getElementById('roomType');
    const designStyle = document.getElementById('designStyle');
    const area = document.getElementById('area');
    
    // Additional options
    const customFurniture = document.getElementById('customFurniture');
    const smartHome = document.getElementById('smartHome');
    const sustainableMaterials = document.getElementById('sustainableMaterials');
    const expeditedTimeline = document.getElementById('expeditedTimeline');
    
    // Calculate button click handler
    calculateBtn.addEventListener('click', function() {
        // Validate form
        if (!propertyType.value || !roomType.value || !designStyle.value || !area.value) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Get pricing data
        let pricing;
        try {
            pricing = priceConfig[propertyType.value][roomType.value][designStyle.value];
        } catch (e) {
            // Use default pricing if combination not found
            pricing = defaultPricing;
        }
        
        // Calculate base cost
        let cost = pricing.basePrice + (pricing.pricePerSqft * parseInt(area.value));
        
        // Add additional options
        if (customFurniture.checked) cost *= 1.15;
        if (smartHome.checked) cost *= 1.10;
        if (sustainableMaterials.checked) cost *= 1.08;
        if (expeditedTimeline.checked) cost *= 1.12;
        
        // Round to nearest thousand
        cost = Math.round(cost / 1000) * 1000;
        
        // Display result
        estimatedCost.textContent = '₹' + cost.toLocaleString();
        estimatedCostInput.value = cost;
        estimateResult.classList.remove('d-none');
        calculateBtn.classList.add('d-none');
    });
    
    // Recalculate button click handler
    recalculateBtn.addEventListener('click', function() {
        estimateResult.classList.add('d-none');
        calculateBtn.classList.remove('d-none');
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

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

// Get current hero content
$hero = $pdo->query("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1")->fetch();

// If no hero content exists, create default
if (!$hero) {
    $stmt = $pdo->prepare("
        INSERT INTO homepage_hero 
        (main_headline, sub_headline, tagline, cta_box_title, cta_box_subtitle) 
        VALUES 
        ('Best Interior Designers', 'In Chennai', 'Home Interiors Within Your Budget - Unbeatable Quality @ Unbelievable Price!', 
        'Explore 50,000+ Design Ideas', 'Get Free Estimate and Interior Design Ideas in Minutes')
    ");
    $stmt->execute();
    $hero = $pdo->query("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1")->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        // Get form data
        $mainHeadline = trim($_POST['main_headline'] ?? '');
        $subHeadline = trim($_POST['sub_headline'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $ctaBoxTitle = trim($_POST['cta_box_title'] ?? '');
        $ctaBoxSubtitle = trim($_POST['cta_box_subtitle'] ?? '');
        $formNameLabel = trim($_POST['form_name_label'] ?? 'Name');
        $formPhoneLabel = trim($_POST['form_phone_label'] ?? 'Phone Number');
        $formEmailLabel = trim($_POST['form_email_label'] ?? 'Email');
        $formLocationLabel = trim($_POST['form_location_label'] ?? 'Property Location');
        $buttonText = trim($_POST['button_text'] ?? 'Chat with Design Expert');

        // Validate required fields
        if (empty($mainHeadline) || empty($subHeadline) || empty($tagline) || empty($ctaBoxTitle) || empty($ctaBoxSubtitle)) {
            $errors[] = 'All headline and tagline fields are required.';
        }

        // Handle image upload
        $backgroundImage = $hero['background_image'] ?? '';
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            $fileName = 'hero_bg_' . time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
            $uploadFile = $uploadDir . $fileName;
            
            // Check if it's a valid image
            $check = getimagesize($_FILES['background_image']['tmp_name']);
            if ($check === false) {
                $errors[] = 'File is not a valid image.';
            } else {
                // Move the uploaded file
                if (move_uploaded_file($_FILES['background_image']['tmp_name'], $uploadFile)) {
                    $backgroundImage = 'assets/uploads/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }

        // If no errors, update the database
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE homepage_hero SET 
                main_headline = :main_headline,
                sub_headline = :sub_headline,
                tagline = :tagline,
                cta_box_title = :cta_box_title,
                cta_box_subtitle = :cta_box_subtitle,
                form_name_label = :form_name_label,
                form_phone_label = :form_phone_label,
                form_email_label = :form_email_label,
                form_location_label = :form_location_label,
                button_text = :button_text,
                background_image = :background_image
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':main_headline' => $mainHeadline,
                ':sub_headline' => $subHeadline,
                ':tagline' => $tagline,
                ':cta_box_title' => $ctaBoxTitle,
                ':cta_box_subtitle' => $ctaBoxSubtitle,
                ':form_name_label' => $formNameLabel,
                ':form_phone_label' => $formPhoneLabel,
                ':form_email_label' => $formEmailLabel,
                ':form_location_label' => $formLocationLabel,
                ':button_text' => $buttonText,
                ':background_image' => $backgroundImage,
                ':id' => $hero['id']
            ]);
            
            $success = true;
            // Refresh hero data
            $hero = $pdo->query("SELECT * FROM homepage_hero WHERE id = {$hero['id']}")->fetch();
        }
    }
}

$pageTitle = 'Manage Homepage Hero Section';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Homepage Hero Section</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">Hero section updated successfully!</div>
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
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            Edit Hero Section Content
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                
                                <div class="mb-3">
                                    <label for="main_headline" class="form-label">Main Headline</label>
                                    <input type="text" class="form-control" id="main_headline" name="main_headline" value="<?= e($hero['main_headline'] ?? '') ?>" required>
                                    <div class="form-text">Example: "Best Interior Designers"</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sub_headline" class="form-label">Sub Headline</label>
                                    <input type="text" class="form-control" id="sub_headline" name="sub_headline" value="<?= e($hero['sub_headline'] ?? '') ?>" required>
                                    <div class="form-text">Example: "In Chennai" (City name)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tagline" class="form-label">Tagline</label>
                                    <textarea class="form-control" id="tagline" name="tagline" rows="2" required><?= e($hero['tagline'] ?? '') ?></textarea>
                                    <div class="form-text">Main marketing message</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cta_box_title" class="form-label">CTA Box Title</label>
                                    <input type="text" class="form-control" id="cta_box_title" name="cta_box_title" value="<?= e($hero['cta_box_title'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cta_box_subtitle" class="form-label">CTA Box Subtitle</label>
                                    <input type="text" class="form-control" id="cta_box_subtitle" name="cta_box_subtitle" value="<?= e($hero['cta_box_subtitle'] ?? '') ?>" required>
                                </div>
                                
                                <h5 class="mt-4">Form Labels</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="form_name_label" class="form-label">Name Field Label</label>
                                            <input type="text" class="form-control" id="form_name_label" name="form_name_label" value="<?= e($hero['form_name_label'] ?? 'Name') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="form_phone_label" class="form-label">Phone Field Label</label>
                                            <input type="text" class="form-control" id="form_phone_label" name="form_phone_label" value="<?= e($hero['form_phone_label'] ?? 'Phone Number') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="form_email_label" class="form-label">Email Field Label</label>
                                            <input type="text" class="form-control" id="form_email_label" name="form_email_label" value="<?= e($hero['form_email_label'] ?? 'Email') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="form_location_label" class="form-label">Location Field Label</label>
                                            <input type="text" class="form-control" id="form_location_label" name="form_location_label" value="<?= e($hero['form_location_label'] ?? 'Property Location') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="button_text" class="form-label">Button Text</label>
                                    <input type="text" class="form-control" id="button_text" name="button_text" value="<?= e($hero['button_text'] ?? 'Chat with Design Expert') ?>">
                                    <div class="form-text">Example: "Chat with Design Expert", "WhatsApp", "Book Consultation"</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="background_image" class="form-label">Background Image</label>
                                    <?php if (!empty($hero['background_image'])): ?>
                                        <div class="mb-2">
                                            <img src="<?= base_url() . '/' . e($hero['background_image']) ?>" alt="Current background" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="background_image" name="background_image">
                                    <div class="form-text">Recommended size: 1920x1080px. Leave empty to keep current image.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            Preview
                        </div>
                        <div class="card-body">
                            <div class="preview-container" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                                <h3 id="preview-main-headline"><?= e($hero['main_headline'] ?? '') ?></h3>
                                <h4 id="preview-sub-headline"><?= e($hero['sub_headline'] ?? '') ?></h4>
                                <p id="preview-tagline"><?= e($hero['tagline'] ?? '') ?></p>
                                <div class="mt-3 p-3" style="background-color: white; border-radius: 5px;">
                                    <h5 id="preview-cta-title"><?= e($hero['cta_box_title'] ?? '') ?></h5>
                                    <p id="preview-cta-subtitle"><?= e($hero['cta_box_subtitle'] ?? '') ?></p>
                                    <div class="form-preview">
                                        <div class="mb-2">
                                            <label id="preview-name-label"><?= e($hero['form_name_label'] ?? 'Name') ?></label>
                                            <div class="form-control-preview"></div>
                                        </div>
                                        <div class="mb-2">
                                            <label id="preview-phone-label"><?= e($hero['form_phone_label'] ?? 'Phone Number') ?></label>
                                            <div class="form-control-preview"></div>
                                        </div>
                                        <button class="btn btn-primary btn-sm mt-2" id="preview-button"><?= e($hero['button_text'] ?? 'Chat with Design Expert') ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.form-control-preview {
    height: 20px;
    background-color: #e9ecef;
    border-radius: 4px;
}
</style>

<script>
// Live preview functionality
document.addEventListener('DOMContentLoaded', function() {
    const mainHeadline = document.getElementById('main_headline');
    const subHeadline = document.getElementById('sub_headline');
    const tagline = document.getElementById('tagline');
    const ctaBoxTitle = document.getElementById('cta_box_title');
    const ctaBoxSubtitle = document.getElementById('cta_box_subtitle');
    const formNameLabel = document.getElementById('form_name_label');
    const formPhoneLabel = document.getElementById('form_phone_label');
    const buttonText = document.getElementById('button_text');
    
    // Preview elements
    const previewMainHeadline = document.getElementById('preview-main-headline');
    const previewSubHeadline = document.getElementById('preview-sub-headline');
    const previewTagline = document.getElementById('preview-tagline');
    const previewCtaTitle = document.getElementById('preview-cta-title');
    const previewCtaSubtitle = document.getElementById('preview-cta-subtitle');
    const previewNameLabel = document.getElementById('preview-name-label');
    const previewPhoneLabel = document.getElementById('preview-phone-label');
    const previewButton = document.getElementById('preview-button');
    
    // Update preview on input
    mainHeadline.addEventListener('input', function() {
        previewMainHeadline.textContent = this.value;
    });
    
    subHeadline.addEventListener('input', function() {
        previewSubHeadline.textContent = this.value;
    });
    
    tagline.addEventListener('input', function() {
        previewTagline.textContent = this.value;
    });
    
    ctaBoxTitle.addEventListener('input', function() {
        previewCtaTitle.textContent = this.value;
    });
    
    ctaBoxSubtitle.addEventListener('input', function() {
        previewCtaSubtitle.textContent = this.value;
    });
    
    formNameLabel.addEventListener('input', function() {
        previewNameLabel.textContent = this.value;
    });
    
    formPhoneLabel.addEventListener('input', function() {
        previewPhoneLabel.textContent = this.value;
    });
    
    buttonText.addEventListener('input', function() {
        previewButton.textContent = this.value;
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
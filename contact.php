<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$pdo = getPDO();
$cities = $pdo->query('SELECT id, name FROM cities ORDER BY name')->fetchAll();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $cityId = (int)($_POST['city_id'] ?? 0);
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($name === '' || $phone === '') {
            $errors[] = 'Name and phone are required.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO contact_submissions (type, name, email, phone, city_id, subject, message) VALUES (\'contact\', :name, :email, :phone, :city_id, :subject, :message)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email ?: null,
                ':phone' => $phone,
                ':city_id' => $cityId ?: null,
                ':subject' => $subject ?: null,
                ':message' => $message ?: null,
            ]);
            $success = true;
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h1 class="h4 mb-3">Contact Us</h1>
<div class="row">
  <div class="col-lg-6">
    <?php if ($success): ?>
      <div class="alert alert-success">Thanks! We will get back to you soon.</div>
    <?php elseif (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="mb-2">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required>
      </div>
      <div class="mb-2">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email">
      </div>
      <div class="mb-2">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" required>
      </div>
      <div class="mb-2">
        <label class="form-label">City</label>
        <select class="form-select" name="city_id">
          <option value="">Select city</option>
          <?php foreach ($cities as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-2">
        <label class="form-label">Subject</label>
        <input class="form-control" name="subject">
      </div>
      <div class="mb-3">
        <label class="form-label">Message</label>
        <textarea class="form-control" rows="4" name="message"></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Send Message</button>
    </form>
  </div>
  <div class="col-lg-6">
    <div class="ratio ratio-16x9">
      <iframe src="https://www.google.com/maps?q=Bengaluru&output=embed" allowfullscreen loading="lazy"></iframe>
    </div>
    <div class="mt-3">
      <h2 class="h6">Office</h2>
      <p class="mb-0">Decorpot Interiors, Bengaluru</p>
      <p class="mb-0">Phone: +91-00000-00000</p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

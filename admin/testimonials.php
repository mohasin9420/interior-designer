<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getPDO();
$errors = [];
$success = null;

if (($_GET['action'] ?? '') && isset($_GET['id'])) {
    if (!verify_csrf_token($_GET['token'] ?? null)) {
        $errors[] = 'Invalid action token.';
    } else {
        $id = (int)$_GET['id'];
        if ($_GET['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE testimonials SET status='approved' WHERE id=:id");
            $stmt->execute([':id' => $id]);
            $success = 'approved';
        } elseif ($_GET['action'] === 'reject') {
            $stmt = $pdo->prepare("UPDATE testimonials SET status='pending' WHERE id=:id");
            $stmt->execute([':id' => $id]);
            $success = 'reverted to pending';
        } elseif ($_GET['action'] === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM testimonials WHERE id=:id');
            $stmt->execute([':id' => $id]);
            $success = 'deleted';
        }
    }
}

$items = $pdo->query('SELECT * FROM testimonials ORDER BY created_at DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h1 class="h4 mb-3">Manage Testimonials</h1>
<?php if ($success): ?>
  <div class="alert alert-success">Testimonial <?= e($success) ?> successfully.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead><tr><th>Client</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($it['client_name']) ?></div>
            <div class="small text-muted"><?= e(mb_strimwidth((string)$it['testimonial'], 0, 100, '…')) ?></div>
          </td>
          <td><?= (int)$it['rating'] ?></td>
          <td><span class="badge bg-<?= $it['status'] === 'approved' ? 'success' : 'secondary' ?>"><?= e($it['status']) ?></span></td>
          <td>
            <?php if ($it['status'] !== 'approved'): ?>
              <a class="btn btn-sm btn-success" href="<?= e(base_url('admin/testimonials.php?action=approve&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>">Approve</a>
            <?php else: ?>
              <a class="btn btn-sm btn-warning" href="<?= e(base_url('admin/testimonials.php?action=reject&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>">Revert</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-danger" href="<?= e(base_url('admin/testimonials.php?action=delete&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>" onclick="return confirm('Delete this testimonial?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

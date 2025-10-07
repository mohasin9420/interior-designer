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
        if ($_GET['action'] === 'close') {
            $pdo->prepare("UPDATE contact_submissions SET status='closed' WHERE id=:id")->execute([':id' => $id]);
            $success = 'marked as closed';
        } elseif ($_GET['action'] === 'reviewed') {
            $pdo->prepare("UPDATE contact_submissions SET status='reviewed' WHERE id=:id")->execute([':id' => $id]);
            $success = 'marked as reviewed';
        } elseif ($_GET['action'] === 'delete') {
            $pdo->prepare('DELETE FROM contact_submissions WHERE id=:id')->execute([':id' => $id]);
            $success = 'deleted';
        }
    }
}

$items = $pdo->query('SELECT cs.*, c.name AS city_name FROM contact_submissions cs LEFT JOIN cities c ON cs.city_id=c.id ORDER BY cs.created_at DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h1 class="h4 mb-3">Form Submissions</h1>
<?php if ($success): ?><div class="alert alert-success">Submission <?= e($success) ?>.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead><tr><th>Type</th><th>Name</th><th>Phone</th><th>City</th><th>Subject</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= e($it['type']) ?></td>
        <td>
          <div class="fw-semibold"><?= e($it['name']) ?></div>
          <?php if (!empty($it['message'])): ?><div class="small text-muted"><?= e(mb_strimwidth((string)$it['message'], 0, 80, '…')) ?></div><?php endif; ?>
        </td>
        <td><?= e($it['phone'] ?? '-') ?></td>
        <td><?= e($it['city_name'] ?? '-') ?></td>
        <td><?= e($it['subject'] ?? '-') ?></td>
        <td><span class="badge bg-<?php echo $it['status'] === 'new' ? 'primary' : ($it['status'] === 'reviewed' ? 'warning' : 'secondary'); ?>"><?= e($it['status']) ?></span></td>
        <td><?= e($it['created_at']) ?></td>
        <td>
          <a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('admin/submissions.php?action=reviewed&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>">Mark Reviewed</a>
          <a class="btn btn-sm btn-outline-success" href="<?= e(base_url('admin/submissions.php?action=close&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>">Close</a>
          <a class="btn btn-sm btn-outline-danger" href="<?= e(base_url('admin/submissions.php?action=delete&id=' . (int)$it['id'] . '&token=' . csrf_token())) ?>" onclick="return confirm('Delete this submission?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

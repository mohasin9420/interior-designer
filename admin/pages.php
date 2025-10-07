<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getPDO();
$errors = [];
$success = null;

$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : 'about';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission.';
    } else {
        $title = trim((string)($_POST['title'] ?? ''));
        $content = (string)($_POST['content'] ?? '');
        $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
        $metaDescription = trim((string)($_POST['meta_description'] ?? ''));
        if ($title === '') { $errors[] = 'Title is required'; }
        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO pages (slug, title, content, meta_title, meta_description, status) VALUES (:slug, :title, :content, :mt, :md, \"published\") ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content), meta_title=VALUES(meta_title), meta_description=VALUES(meta_description)');
            $stmt->execute([
                ':slug' => $slug,
                ':title' => $title,
                ':content' => $content,
                ':mt' => $metaTitle ?: null,
                ':md' => $metaDescription ?: null,
            ]);
            $success = 'saved';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug=:slug LIMIT 1');
$stmt->execute([':slug' => $slug]);
$page = $stmt->fetch();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h1 class="h4 mb-3">Edit Page: <?= e(strtoupper($slug)) ?></h1>
<?php if ($success): ?><div class="alert alert-success">Page saved.</div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($page['title'] ?? '') ?>" required></div>
  <div class="mb-2"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="10"><?= e($page['content'] ?? '') ?></textarea></div>
  <div class="mb-2"><label class="form-label">Meta Title</label><input class="form-control" name="meta_title" value="<?= e($page['meta_title'] ?? '') ?>"></div>
  <div class="mb-3"><label class="form-label">Meta Description</label><input class="form-control" name="meta_description" value="<?= e($page['meta_description'] ?? '') ?>"></div>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
<link href="https://cdn.jsdelivr.net/npm/suneditor@2.45.1/dist/css/suneditor.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/suneditor@2.45.1/dist/suneditor.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const editor = SUNEDITOR.create(document.querySelector('textarea[name="content"]'), {
      height: 300,
      buttonList: [['undo', 'redo'], ['bold', 'italic', 'underline'], ['list', 'align'], ['link', 'image'], ['fullScreen', 'codeView']]
    });
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

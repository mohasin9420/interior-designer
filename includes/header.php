<?php
declare(strict_types=1);
$settings = require __DIR__ . '/../config/settings.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Decorpot Interiors</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('css/homepage-modules.css')) ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand" href="<?= e(base_url()) ?>">Decorpot</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('portfolio.php')) ?>">Portfolio</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('services.php')) ?>">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('blog.php')) ?>">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(base_url('contact.php')) ?>">Contact</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-2" href="<?= e(base_url('get-estimate.php')) ?>">Get Estimate</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="py-4">
  <div class="container">

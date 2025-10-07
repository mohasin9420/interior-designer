<div id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3 sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/dashboard.php')) ?>">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/portfolio.php')) ?>">
                    <i class="bi bi-grid me-1"></i> Portfolio
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/testimonials.php')) ?>">
                    <i class="bi bi-chat-quote me-1"></i> Testimonials
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/submissions.php')) ?>">
                    <i class="bi bi-envelope me-1"></i> Submissions
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Homepage Sections</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/homepage_hero.php')) ?>">
                    <i class="bi bi-image me-1"></i> Hero Section
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/homepage_testimonials.php')) ?>">
                    <i class="bi bi-chat-square-quote me-1"></i> Testimonials
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/homepage_process.php')) ?>">
                    <i class="bi bi-list-ol me-1"></i> Process Steps
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/homepage_company_info.php')) ?>">
                    <i class="bi bi-building me-1"></i> Company Info
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= e(base_url('admin/homepage_portfolio.php')) ?>">
                    <i class="bi bi-collection me-1"></i> Featured Projects
                </a>
            </li>
        </ul>
    </div>
</div>
<?php
$minimal_header = isset($minimal_header) ? $minimal_header : false;
$is_logged_in = isset($_SESSION['role']);
$is_admin = $is_logged_in && $_SESSION['role'] === 'admin';
$is_prisoner = $is_logged_in && $_SESSION['role'] === 'prisoner';
?>
<header class="site-header">
    <div class="site-header-accent"></div>
    <div class="site-header-inner">
        <a href="<?php echo $is_admin ? 'admin_dashboard.php' : ($is_prisoner ? 'prisoner_dashboard.php' : 'index.php'); ?>" class="site-logo">
            <span class="site-logo-mark">
                <span class="site-logo-letter">J</span>
            </span>
            <span class="site-logo-brand">
                <span class="site-logo-text">JDBMS</span>
                <span class="site-logo-tagline">Jail Database Management System</span>
            </span>
        </a>
        <?php if (!$minimal_header): ?>
        <button type="button" class="site-nav-toggle" id="site-nav-toggle" aria-label="Open menu" aria-expanded="false">
            <span class="site-nav-toggle-bar"></span>
            <span class="site-nav-toggle-bar"></span>
            <span class="site-nav-toggle-bar"></span>
        </button>
        <nav class="site-nav" id="site-nav" aria-hidden="false">
            <div class="site-nav-links">
            <?php if ($is_admin): ?>
                <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
                <a href="add_prisoner.php" class="nav-link">Add Prisoner</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <a href="visitors.php" class="nav-link">Visitors</a>
                <a href="incidents.php" class="nav-link">Incidents</a>
                <a href="announcements.php" class="nav-link">Announcements</a>
            <?php elseif ($is_prisoner): ?>
                <a href="prisoner_dashboard.php" class="nav-link">Dashboard</a>
                <a href="prisoner_parole.php" class="nav-link">Parole</a>
                <a href="my_visits.php" class="nav-link">My Visits</a>
            <?php endif; ?>
            </div>
            <?php if ($is_logged_in): ?>
            <div class="site-nav-divider"></div>
            <a href="logout.php" class="nav-link nav-link-out">Logout</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</header>
<?php if (!$minimal_header): ?>
<script>
(function() {
    var toggle = document.getElementById('site-nav-toggle');
    var nav = document.getElementById('site-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            var open = nav.classList.toggle('site-nav-open');
            toggle.setAttribute('aria-expanded', open);
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
        nav.querySelectorAll('.nav-link').forEach(function(a) {
            a.addEventListener('click', function() { nav.classList.remove('site-nav-open'); toggle.setAttribute('aria-expanded', 'false'); toggle.setAttribute('aria-label', 'Open menu'); });
        });
    }
})();
</script>
<?php endif; ?>

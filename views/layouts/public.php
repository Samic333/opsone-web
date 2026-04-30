<?php
/**
 * Public Layout — Full-width marketing layout for OpsOne public pages
 * No sidebar, marketing navigation with CTA buttons
 */
$brand = $brand ?? require CONFIG_PATH . '/branding.php';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($brand['product_name']) ?> — <?= e($brand['product_tagline']) ?>. Role-based crew management, safety, rostering, and document distribution in one platform.">
    <title><?= e($pageTitle ?? $brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/public.css">
</head>
<body>

<!-- Navigation -->
<nav class="pub-nav" id="pubNav">
    <div class="pub-nav-inner">
        <a href="/home" class="pub-nav-brand" aria-label="<?= e($brand['product_name']) ?> home">
            <?= opsoneLogoMark(28) ?>
            <?= opsoneWordmark('lg') ?>
        </a>
        <button class="pub-nav-toggle" id="pubNavToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <div class="pub-nav-links" id="pubNavLinks">
            <a href="/features" class="<?= $currentPath === '/features' ? 'active' : '' ?>">Features</a>
            <a href="/how-it-works" class="<?= $currentPath === '/how-it-works' ? 'active' : '' ?>">How It Works</a>
            <a href="/pricing" class="<?= $currentPath === '/pricing' ? 'active' : '' ?>">Pricing</a>
            <a href="/support" class="<?= $currentPath === '/support' ? 'active' : '' ?>">Support</a>
            <a href="/contact" class="<?= $currentPath === '/contact' ? 'active' : '' ?>">Contact</a>
        </div>
        <div class="pub-nav-actions">
            <a href="/login" class="pub-nav-quiet-link">Client Login</a>
            <a href="/contact" class="pub-btn pub-btn-primary">Request Demo</a>
        </div>
    </div>
</nav>

<!-- Page Content -->
<main class="pub-main">
    <?= $content ?? '' ?>
</main>

<!-- Footer -->
<footer class="pub-footer">
    <div class="pub-footer-inner">
        <div class="pub-footer-grid">
            <div class="pub-footer-brand">
                <div class="pub-footer-logo">
                    <?= opsoneLogoMark(24) ?>
                    <?= opsoneWordmark('md') ?>
                </div>
                <p><?= e($brand['product_tagline']) ?></p>
                <p class="pub-footer-copy">© <?= e($brand['copyright_year']) ?> <?= e($brand['company_name']) ?>. All rights reserved.</p>
            </div>
            <div class="pub-footer-col">
                <h4>Product</h4>
                <a href="/features">Features</a>
                <a href="/how-it-works">How It Works</a>
                <a href="/pricing">Pricing</a>
                <a href="/faq">FAQ</a>
            </div>
            <div class="pub-footer-col">
                <h4>Company</h4>
                <a href="/about">About</a>
                <a href="/support">Support</a>
                <a href="/contact">Contact</a>
            </div>
            <div class="pub-footer-col">
                <h4>Legal</h4>
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms of Use</a>
            </div>
            <div class="pub-footer-col">
                <h4>Access</h4>
                <a href="/contact">Request Demo</a>
                <a href="/request-assessment">Request Assessment</a>
                <a href="/login">Client Login</a>
            </div>
        </div>
        <div class="pub-footer-bottom">
            <p>Version <?= e($brand['version']) ?></p>
        </div>
    </div>
</footer>

<script>
(function() {
    // Mobile nav toggle
    const toggle = document.getElementById('pubNavToggle');
    const links = document.getElementById('pubNavLinks');
    if (toggle && links) {
        toggle.addEventListener('click', function() {
            links.classList.toggle('open');
            toggle.classList.toggle('open');
        });
    }
    // Sticky nav
    const nav = document.getElementById('pubNav');
    window.addEventListener('scroll', function() {
        nav.classList.toggle('scrolled', window.scrollY > 40);
    });
})();
</script>
</body>
</html>

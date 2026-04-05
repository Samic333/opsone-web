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
    <meta name="description" content="<?= e($brand['product_name']) ?> — <?= e($brand['product_tagline']) ?>. Secure internal airline operations platform for iPad.">
    <title><?= e($pageTitle ?? $brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/public.css">
</head>
<body>

<!-- Navigation -->
<nav class="pub-nav" id="pubNav">
    <div class="pub-nav-inner">
        <a href="/home" class="pub-nav-brand">
            <div class="pub-nav-logo">✈</div>
            <span><?= e($brand['product_name']) ?></span>
        </a>
        <button class="pub-nav-toggle" id="pubNavToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <div class="pub-nav-links" id="pubNavLinks">
            <a href="/features" class="<?= $currentPath === '/features' ? 'active' : '' ?>">Features</a>
            <a href="/how-it-works" class="<?= $currentPath === '/how-it-works' ? 'active' : '' ?>">How It Works</a>
            <a href="/install-info" class="<?= $currentPath === '/install-info' ? 'active' : '' ?>">Deployment</a>
            <a href="/support" class="<?= $currentPath === '/support' ? 'active' : '' ?>">Support</a>
            <a href="/faq" class="<?= $currentPath === '/faq' ? 'active' : '' ?>">FAQ</a>
        </div>
        <div class="pub-nav-actions">
            <a href="/login" class="pub-btn pub-btn-outline">Airline Login</a>
            <a href="/install" class="pub-btn pub-btn-primary">Install <?= e($brand['product_name']) ?></a>
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
                    <div class="pub-nav-logo">✈</div>
                    <span><?= e($brand['product_name']) ?></span>
                </div>
                <p><?= e($brand['product_tagline']) ?></p>
                <p class="pub-footer-copy">© <?= e($brand['copyright_year']) ?> <?= e($brand['company_name']) ?>. All rights reserved.</p>
            </div>
            <div class="pub-footer-col">
                <h4>Product</h4>
                <a href="/features">Features</a>
                <a href="/how-it-works">How It Works</a>
                <a href="/install-info">Internal Deployment</a>
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
                <a href="/login">Airline Login</a>
                <a href="/install">Install App</a>
            </div>
        </div>
        <div class="pub-footer-bottom">
            <p>Internal use only. Not for public distribution.</p>
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

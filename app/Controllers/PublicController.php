<?php
/**
 * PublicController — handles all public-facing pages (no auth required)
 */
class PublicController {
    private array $brand;

    public function __construct() {
        $this->brand = require CONFIG_PATH . '/branding.php';
    }

    public function home(): void {
        $brand = $this->brand;
        $pageTitle = $brand['product_name'] . ' — ' . $brand['product_tagline'];
        $content = $this->render('public/home', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function features(): void {
        $brand = $this->brand;
        $pageTitle = 'Features — ' . $brand['product_name'];
        $content = $this->render('public/features', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function howItWorks(): void {
        $brand = $this->brand;
        $pageTitle = 'How It Works — ' . $brand['product_name'];
        $content = $this->render('public/how-it-works', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function installInfo(): void {
        $brand = $this->brand;
        $pageTitle = 'Internal Deployment — ' . $brand['product_name'];
        $content = $this->render('public/install-info', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function support(): void {
        $brand = $this->brand;
        $pageTitle = 'Support — ' . $brand['product_name'];
        $content = $this->render('public/support', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function contact(): void {
        $brand = $this->brand;
        $pageTitle = 'Contact — ' . $brand['product_name'];
        $content = $this->render('public/contact', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function faq(): void {
        $brand = $this->brand;
        $pageTitle = 'FAQ — ' . $brand['product_name'];
        $content = $this->render('public/faq', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function about(): void {
        $brand = $this->brand;
        $pageTitle = 'About — ' . $brand['product_name'];
        $content = $this->render('public/about', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function privacy(): void {
        $brand = $this->brand;
        $pageTitle = 'Privacy Policy — ' . $brand['product_name'];
        $content = $this->render('public/privacy', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function terms(): void {
        $brand = $this->brand;
        $pageTitle = 'Terms of Use — ' . $brand['product_name'];
        $content = $this->render('public/terms', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    private function render(string $view, array $data = []): string {
        extract($data);
        ob_start();
        require VIEWS_PATH . '/' . $view . '.php';
        return ob_get_clean();
    }
}

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
        $flashMsg = null;
        $flashType = null;
        if (!empty($_SESSION['_contact_flash'])) {
            [$flashType, $flashMsg] = $_SESSION['_contact_flash'];
            unset($_SESSION['_contact_flash']);
        }
        $content = $this->render('public/contact', compact('brand', 'flashType', 'flashMsg'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function submitContact(): void {
        if (!verifyCsrf()) {
            $_SESSION['_contact_flash'] = ['error', 'Invalid form submission. Please reload and try again.'];
            header('Location: /contact');
            exit;
        }

        $name    = trim($_POST['name']         ?? '');
        $email   = trim($_POST['email']        ?? '');
        $org     = trim($_POST['organization'] ?? '');
        $subject = trim($_POST['subject']      ?? '');
        $message = trim($_POST['message']      ?? '');

        $errors = [];
        if ($name === '')  $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if ($message === '') $errors[] = 'Message is required.';
        if (strlen($message) > 5000) $errors[] = 'Message is too long.';

        if ($errors) {
            $_SESSION['_contact_flash'] = ['error', implode(' ', $errors)];
            header('Location: /contact');
            exit;
        }

        // Persist to a local file (until an email/CRM integration lands) so demo requests are never lost.
        $dir = BASE_PATH . '/storage/contact_submissions';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $payload = [
            'received_at' => date('c'),
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'name'        => $name,
            'email'       => $email,
            'organization'=> $org,
            'subject'     => $subject,
            'message'     => $message,
        ];
        @file_put_contents($dir . '/' . date('Ymd_His') . '_' . substr(md5($email . microtime()), 0, 8) . '.json',
                           json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $_SESSION['_contact_flash'] = ['success', 'Thanks — your message has been received. The team will reply within one business day.'];
        header('Location: /contact');
        exit;
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

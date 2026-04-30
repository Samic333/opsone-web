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
        $pageTitle = 'Request a Demo — ' . $brand['product_name'];

        $flashMsg = null;
        $flashType = null;
        if (!empty($_SESSION['_contact_flash'])) {
            [$flashType, $flashMsg] = $_SESSION['_contact_flash'];
            unset($_SESSION['_contact_flash']);
        }

        // Per-field errors + repopulated values from a failed submit
        $errors = $_SESSION['_contact_errors'] ?? [];
        $values = $_SESSION['_contact_values'] ?? [];
        unset($_SESSION['_contact_errors'], $_SESSION['_contact_values']);

        // Pre-fill inquiry type from query string (?type=demo|sales|onboarding|support|general)
        $allowedTypes = ['demo','sales','onboarding','support','general'];
        $prefillType  = strtolower((string)($_GET['type'] ?? ''));
        if (!in_array($prefillType, $allowedTypes, true)) $prefillType = '';
        if ($prefillType !== '' && empty($values['inquiry_type'])) {
            $values['inquiry_type'] = $prefillType;
        }

        $content = $this->render('public/contact', compact('brand', 'flashType', 'flashMsg', 'errors', 'values'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function submitContact(): void {
        if (!verifyCsrf()) {
            $_SESSION['_contact_flash'] = ['error', 'Invalid form submission. Please reload and try again.'];
            header('Location: /contact');
            exit;
        }

        // Collect every field once into one array so validation, repopulation,
        // and persistence can all read from the same source.
        $values = [
            'contact_name' => trim($_POST['contact_name'] ?? ''),
            'email'        => trim($_POST['email'] ?? ''),
            'phone'        => trim($_POST['phone'] ?? ''),
            'country'      => trim($_POST['country'] ?? ''),
            'company'      => trim($_POST['company'] ?? ''),
            'airline'      => trim($_POST['airline'] ?? ''),
            'operation'    => trim($_POST['operation'] ?? ''),
            'crew_size'    => trim($_POST['crew_size'] ?? ''),
            'inquiry_type' => trim($_POST['inquiry_type'] ?? 'demo'),
            'message'      => trim($_POST['message'] ?? ''),
        ];

        $errors = [];
        if ($values['contact_name'] === '')                                $errors['contact_name'] = 'Contact person name is required.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL))          $errors['email']        = 'A valid work email is required.';
        if ($values['country'] === '')                                     $errors['country']      = 'Country is required.';
        if ($values['company'] === '')                                     $errors['company']      = 'Company name is required.';
        if ($values['airline'] === '')                                     $errors['airline']      = 'Airline name is required.';
        if ($values['message'] === '')                                     $errors['message']      = 'A short message is required.';
        if (strlen($values['message']) > 5000)                             $errors['message']      = 'Message is too long (5000 characters max).';

        $allowedOps = ['', 'commercial_scheduled','charter','cargo','corporate','training','other'];
        if (!in_array($values['operation'], $allowedOps, true))            $errors['operation']    = 'Pick an operation type from the list.';

        $allowedTypes = ['demo','sales','onboarding','support','general'];
        if (!in_array($values['inquiry_type'], $allowedTypes, true))       $errors['inquiry_type'] = 'Pick an inquiry type from the list.';

        if ($errors) {
            $_SESSION['_contact_flash']  = ['error', 'Please correct the highlighted fields below and try again.'];
            $_SESSION['_contact_errors'] = $errors;
            $_SESSION['_contact_values'] = $values;
            header('Location: /contact');
            exit;
        }

        // Persist to a local file (until an email/CRM integration lands) so demo requests are never lost.
        $dir = BASE_PATH . '/storage/contact_submissions';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $payload = array_merge([
            'received_at' => date('c'),
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ], $values);
        @file_put_contents(
            $dir . '/' . date('Ymd_His') . '_' . $values['inquiry_type'] . '_' . substr(md5($values['email'] . microtime()), 0, 8) . '.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $_SESSION['_contact_flash'] = ['success', 'Thanks — your request has been received. We\'ll be in touch within one business day.'];
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

    public function pricing(): void {
        $brand = $this->brand;
        $pageTitle = 'Pricing — ' . $brand['product_name'];
        $content = $this->render('public/pricing', compact('brand'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function requestAssessment(): void {
        $brand = $this->brand;
        $pageTitle = 'Request an Operational Assessment — ' . $brand['product_name'];
        $flashMsg = null;
        $flashType = null;
        if (!empty($_SESSION['_assessment_flash'])) {
            [$flashType, $flashMsg] = $_SESSION['_assessment_flash'];
            unset($_SESSION['_assessment_flash']);
        }
        $content = $this->render('public/request-assessment', compact('brand', 'flashType', 'flashMsg'));
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function submitAssessment(): void {
        if (!verifyCsrf()) {
            $_SESSION['_assessment_flash'] = ['error', 'Invalid form submission. Please reload and try again.'];
            header('Location: /request-assessment');
            exit;
        }

        $name      = trim($_POST['name']        ?? '');
        $email     = trim($_POST['email']       ?? '');
        $airline   = trim($_POST['airline']     ?? '');
        $role      = trim($_POST['role']        ?? '');
        $fleetSize = trim($_POST['fleet_size']  ?? '');
        $crewSize  = trim($_POST['crew_size']   ?? '');
        $current   = trim($_POST['current_tools'] ?? '');
        $painPoint = trim($_POST['pain_point']  ?? '');
        $goLive    = trim($_POST['target_go_live'] ?? '');

        $errors = [];
        if ($name === '')    $errors[] = 'Name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid work email is required.';
        if ($airline === '') $errors[] = 'Airline / operator name is required.';
        if (strlen($painPoint) > 5000) $errors[] = 'Pain point description is too long.';

        if ($errors) {
            $_SESSION['_assessment_flash'] = ['error', implode(' ', $errors)];
            header('Location: /request-assessment');
            exit;
        }

        $dir = BASE_PATH . '/storage/contact_submissions';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $payload = [
            'received_at'    => date('c'),
            'inquiry_type'   => 'operational_assessment',
            'ip'             => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'name'           => $name,
            'email'          => $email,
            'airline'        => $airline,
            'role'           => $role,
            'fleet_size'     => $fleetSize,
            'crew_size'      => $crewSize,
            'current_tools'  => $current,
            'pain_point'     => $painPoint,
            'target_go_live' => $goLive,
        ];
        @file_put_contents(
            $dir . '/' . date('Ymd_His') . '_assessment_' . substr(md5($email . microtime()), 0, 8) . '.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $_SESSION['_assessment_flash'] = ['success', 'Thanks — we\'ll review your operation and get back within two business days with a tailored deployment plan.'];
        header('Location: /request-assessment');
        exit;
    }

    private function render(string $view, array $data = []): string {
        extract($data);
        ob_start();
        require VIEWS_PATH . '/' . $view . '.php';
        return ob_get_clean();
    }
}

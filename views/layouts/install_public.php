<?php $brand = $brand ?? require CONFIG_PATH . '/branding.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Install ' . $brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body { 
            background: var(--bg-color); 
            color: var(--text-color); 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding: 40px 20px; 
            min-height: 100vh;
        }
        .install-container { 
            width: 100%; 
            max-width: 700px; 
        }
        .install-header { 
            text-align: center; 
            margin-bottom: 40px; 
        }
        .install-header h1 { 
            font-size: 32px; 
            color: var(--text-color); 
            margin-bottom: 8px; 
            font-weight: 600;
        }
        .install-header p { 
            color: var(--text-secondary); 
            font-size: 16px;
        }
        .install-logo-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="install-logo-icon">✈</div>
            <h1><?= e($brand['product_name']) ?> Operations App</h1>
            <p>Internal enterprise iOS distribution portal</p>
        </div>
        <?= $content ?? '' ?>
        
        <div style="text-align: center; margin-top: 40px; color: var(--text-muted); font-size: 13px;">
            <p>Strictly for authorized internal airline use.</p>
            <p>&copy; <?= date('Y') ?> <?= e($brand['company_name']) ?></p>
        </div>
    </div>
</body>
</html>

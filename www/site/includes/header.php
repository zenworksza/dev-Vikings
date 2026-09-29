<?php
require_once __DIR__.'/config.php';

$pageTitle ??= SITE_NAME;
$pageDescription ??= '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
<?php if ($pageDescription !== ''): ?>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
<?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="/assets/site.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="brand"><img src="/assets/img/logo-gold.png" alt="<?= SITE_NAME ?>"></a>
            <nav>
                <a href="<?= PORTAL_URL ?>/login">Franchisee login</a>
                <a href="<?= PORTAL_URL ?>/register" class="btn btn-sm">Apply as a franchisee</a>
            </nav>
        </div>
    </header>
    <main>

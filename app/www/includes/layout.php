<?php

function teknobantu_header(string $title): void
{
    ?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> - TeknoBantu</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <a href="/index.php" class="brand">TeknoBantu</a>
        <span class="tagline">Portal Helpdesk IT Internal</span>
    </header>
    <main class="site-main">
    <?php
}

function teknobantu_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> TeknoBantu - Helpdesk IT PT Karya Digital Nusantara</p>
    </footer>
</body>
</html>
    <?php
}

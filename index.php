<?php
/**
 * Main Studio Page
 * Carouselfy - AI Instagram Carousel Studio
 * Hostinger Shared Hosting (PHP + HTML/CSS/JS)
 */

require_once (file_exists(__DIR__ . '/config/config.php') ? __DIR__ . '/config/config.php' : __DIR__ . '/../config/config.php');
require_once (file_exists(__DIR__ . '/includes/functions.php') ? __DIR__ . '/includes/functions.php' : __DIR__ . '/../includes/functions.php');

$pageTitle = 'Carousel Studio';
$currentPage = 'index';
$user = current_user();

// Look for generated studio bundle
$jsPath = '/assets/js/studio.js';
$cssPath = '/assets/css/studio.css';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carouselfy — AI Instagram Carousel Studio</title>
  <meta name="description" content="Generate, design and export scroll-stopping Instagram carousels with an AI content engine, 500+ templates and retina PNG, ZIP and PDF export.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Archivo+Black&family=Fraunces:opsz,wght@9..144,400;9..144,700&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  
  <!-- Production Studio CSS -->
  <link rel="stylesheet" href="<?= $cssPath ?>">
  
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #0b1020; color: #f8fafc; margin: 0; padding: 0; }
    .nav-bar-compact {
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 14px;
      background: #090d16;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      font-size: 12px;
    }
  </style>
</head>
<body class="bg-background text-foreground antialiased h-screen flex flex-col overflow-hidden">

  <!-- Hostinger App Global Nav Header -->
  <header class="nav-bar-compact shrink-0 z-50">
    <div class="flex items-center gap-4">
      <a href="/index.php" class="flex items-center gap-2 text-foreground font-semibold">
        <span class="grid size-6 place-items-center rounded bg-indigo-600 text-white shadow-sm">
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/>
            <path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/>
            <path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/>
          </svg>
        </span>
        <span class="font-['Space_Grotesk'] text-sm tracking-tight">Carouselfy</span>
      </a>

      <div class="hidden md:flex items-center gap-1 text-xs">
        <a href="/index.php" class="px-2.5 py-1 rounded bg-indigo-500/20 text-indigo-300 font-medium">Studio</a>
        <a href="/dashboard.php" class="px-2.5 py-1 rounded text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-colors">Dashboard</a>
        <a href="/schedule.php" class="px-2.5 py-1 rounded text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-colors">Schedule</a>
        <a href="/accounts.php" class="px-2.5 py-1 rounded text-zinc-400 hover:text-white hover:bg-zinc-800/60 transition-colors">Instagram</a>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <?php if ($user): ?>
        <span class="text-zinc-400 text-xs hidden sm:inline-block">Logged in as <strong class="text-zinc-200"><?= e($user['name']) ?></strong></span>
        <a href="/logout.php" class="text-zinc-500 hover:text-red-400 transition-colors text-xs">Logout</a>
      <?php else: ?>
        <a href="/login.php" class="text-zinc-400 hover:text-white text-xs">Sign In</a>
        <a href="/register.php" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs px-2.5 py-1 rounded transition-colors font-medium">Get Started</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- React Canva-Style Studio App Mounting Container -->
  <main id="root" class="flex-1 min-h-0 relative"></main>

  <!-- Production Studio JS Bundle -->
  <script type="module" src="<?= $jsPath ?>"></script>
</body>
</html>

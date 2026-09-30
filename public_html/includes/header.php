<?php
/**
 * Global Navigation Header Component
 * Carouselfy - Instagram Automation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Carouselfy · Instagram Automation</title>
  <meta name="description" content="AI-Powered Instagram Carousel Automation, Scheduling and Canva-Style Design Studio.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <!-- Tailwind & Studio CSS -->
  <link rel="stylesheet" href="/assets/css/studio.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #090d16; color: #f8fafc; }
    .nav-link { transition: all 0.15s ease-in-out; }
    .nav-link.active { background-color: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.4); }
  </style>
</head>
<body class="bg-background text-foreground antialiased min-h-screen flex flex-col">
  <!-- Top Global Navigation Bar -->
  <nav class="sticky top-0 z-50 flex items-center justify-between border-b border-border/80 bg-card/90 backdrop-blur px-4 py-2.5">
    <div class="flex items-center gap-6">
      <a href="/index.php" class="flex items-center gap-2.5 group">
        <span class="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground shadow-sm shadow-primary/30 group-hover:scale-105 transition-transform">
          <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/>
            <path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/>
            <path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/>
          </svg>
        </span>
        <div class="leading-tight">
          <span class="text-sm font-bold tracking-tight text-foreground font-['Space_Grotesk']">Carouselfy</span>
          <span class="block text-[10px] text-muted-foreground uppercase tracking-widest font-semibold">Instagram Studio</span>
        </div>
      </a>

      <!-- Page Links -->
      <div class="hidden md:flex items-center gap-1.5 text-xs font-medium">
        <a href="/index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' ?> px-3 py-1.5 rounded-md border border-transparent flex items-center gap-1.5">
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/><path d="m14 7 3 3"/></svg>
          Studio
        </a>
        <a href="/dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' ?> px-3 py-1.5 rounded-md border border-transparent flex items-center gap-1.5">
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
          Dashboard
        </a>
        <a href="/schedule.php" class="nav-link <?= $currentPage === 'schedule' ? 'active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' ?> px-3 py-1.5 rounded-md border border-transparent flex items-center gap-1.5">
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          Schedule
        </a>
        <a href="/accounts.php" class="nav-link <?= $currentPage === 'accounts' ? 'active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' ?> px-3 py-1.5 rounded-md border border-transparent flex items-center gap-1.5">
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
          Instagram
        </a>
        <a href="/settings.php" class="nav-link <?= $currentPage === 'settings' ? 'active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50' ?> px-3 py-1.5 rounded-md border border-transparent flex items-center gap-1.5">
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Settings
        </a>
      </div>
    </div>

    <!-- Right User Status / Action -->
    <div class="flex items-center gap-3">
      <?php if ($user): ?>
        <div class="hidden sm:flex items-center gap-2 bg-muted/30 border border-border/60 px-2.5 py-1 rounded-full text-xs">
          <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-muted-foreground"><?= e($user['name']) ?></span>
        </div>
        <a href="/logout.php" class="text-xs text-muted-foreground hover:text-destructive transition-colors px-2 py-1">
          Logout
        </a>
      <?php else: ?>
        <a href="/login.php" class="text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-md hover:bg-muted/40 transition-colors">
          Sign In
        </a>
        <a href="/register.php" class="text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary/90 px-3 py-1.5 rounded-md transition-colors shadow-sm">
          Get Started
        </a>
      <?php endif; ?>
    </div>
  </nav>

<?php
/**
 * System Settings & Health Audit
 * Carouselfy - Instagram Automation
 */

$pageTitle = 'Settings & Diagnostics';
require_once (file_exists(__DIR__ . '/includes/header.php') ? __DIR__ . '/includes/header.php' : __DIR__ . '/../includes/header.php');

$pdo = Database::getConnection();
$dbConnected = ($pdo !== null);
$dbError = Database::getLastError();

// Extensions check
$extensions = [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'curl'      => extension_loaded('curl'),
    'gd'        => extension_loaded('gd'),
    'openssl'   => extension_loaded('openssl'),
    'mbstring'  => extension_loaded('mbstring'),
    'json'      => extension_loaded('json'),
];

$allExtOk = !in_array(false, $extensions, true);

// Saved notification
$saved = !empty($_GET['saved']);
?>

<main class="flex-1 max-w-5xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

  <div>
    <h1 class="text-2xl font-bold tracking-tight text-foreground font-['Space_Grotesk']">
      Settings & System Diagnostics
    </h1>
    <p class="text-sm text-muted-foreground">
      Verify Hostinger shared hosting environment, database connectivity, and API credentials.
    </p>
  </div>

  <?php if ($saved): ?>
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs text-emerald-200">
      Settings saved successfully.
    </div>
  <?php endif; ?>

  <!-- Hostinger Server Health Matrix -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-foreground flex items-center gap-2">
        <svg class="size-4.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/></svg>
        Hostinger Environment Health
      </h2>
      <span class="rounded px-2 py-0.5 text-[10px] font-semibold <?= $allExtOk ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' ?>">
        <?= $allExtOk ? 'All Core Extensions Loaded' : 'Some Extensions Missing' ?>
      </span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 text-xs">
      <?php foreach ($extensions as $ext => $loaded): ?>
        <div class="rounded-lg border border-border/60 bg-background/50 p-2.5 flex items-center justify-between">
          <span class="font-mono text-[11px] text-muted-foreground"><?= $ext ?></span>
          <?php if ($loaded): ?>
            <span class="size-2 rounded-full bg-emerald-500" title="Active"></span>
          <?php else: ?>
            <span class="size-2 rounded-full bg-red-500" title="Missing"></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-border/60 text-xs text-muted-foreground">
      <div>
        <span>PHP Version:</span>
        <strong class="text-foreground ml-1"><?= PHP_VERSION ?></strong>
      </div>
      <div>
        <span>Max Upload Size:</span>
        <strong class="text-foreground ml-1"><?= ini_get('upload_max_filesize') ?></strong>
      </div>
      <div>
        <span>Memory Limit:</span>
        <strong class="text-foreground ml-1"><?= ini_get('memory_limit') ?></strong>
      </div>
    </div>
  </div>

  <!-- Database Connection Card -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-foreground flex items-center gap-2">
        <svg class="size-4.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
        MySQL Database (PDO)
      </h2>
      <?php if ($dbConnected): ?>
        <span class="rounded bg-emerald-500/20 text-emerald-400 px-2 py-0.5 text-[10px] font-semibold flex items-center gap-1.5">
          <span class="size-1.5 rounded-full bg-emerald-400"></span> Connected
        </span>
      <?php else: ?>
        <span class="rounded bg-amber-500/20 text-amber-400 px-2 py-0.5 text-[10px] font-semibold">
          Not Connected
        </span>
      <?php endif; ?>
    </div>

    <?php if ($dbConnected): ?>
      <p class="text-xs text-muted-foreground">
        Connected to database <code class="text-zinc-200"><?= e(DB_NAME) ?></code> on host <code class="text-zinc-200"><?= e(DB_HOST) ?></code>. Prepared PDO statements are enabled.
      </p>
    <?php else: ?>
      <div class="text-xs text-muted-foreground space-y-2">
        <p class="text-amber-300">
          Database is not connected. 
          <?= $dbError ? "Error: " . e($dbError) : "No database credentials provided in .env." ?>
        </p>
        <p>To connect Hostinger MySQL:</p>
        <ol class="list-decimal list-inside space-y-1 text-[11px] text-zinc-300">
          <li>Create a MySQL Database in Hostinger hPanel (<strong class="text-foreground">Databases → Management</strong>).</li>
          <li>Open phpMyAdmin and import the <code class="bg-black/30 px-1 py-0.5 rounded text-primary">database.sql</code> file.</li>
          <li>Set <code class="bg-black/30 px-1 py-0.5 rounded text-primary">DB_NAME</code>, <code class="bg-black/30 px-1 py-0.5 rounded text-primary">DB_USER</code>, and <code class="bg-black/30 px-1 py-0.5 rounded text-primary">DB_PASSWORD</code> in your <code class="bg-black/30 px-1 py-0.5 rounded text-primary">.env</code> file.</li>
        </ol>
      </div>
    <?php endif; ?>
  </div>

  <!-- API Credentials Status -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-4">
    <h2 class="text-base font-semibold text-foreground flex items-center gap-2">
      <svg class="size-4.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
      API Integrations Configuration
    </h2>

    <div class="space-y-3 text-xs">
      <div class="flex items-center justify-between p-3 rounded-lg border border-border/60 bg-background/40">
        <div>
          <strong class="text-foreground block">OpenAI / Generative AI Engine</strong>
          <span class="text-muted-foreground text-[11px]">Model: <?= e(OPENAI_MODEL) ?></span>
        </div>
        <div>
          <?php if (!empty(OPENAI_API_KEY) || !empty(LOVABLE_API_KEY)): ?>
            <span class="rounded bg-emerald-500/20 text-emerald-400 px-2 py-0.5 text-[10px] font-semibold">Configured</span>
          <?php else: ?>
            <span class="rounded bg-zinc-800 text-zinc-400 px-2 py-0.5 text-[10px]">Using Built-in Content Bank</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex items-center justify-between p-3 rounded-lg border border-border/60 bg-background/40">
        <div>
          <strong class="text-foreground block">Meta / Instagram Graph API</strong>
          <span class="text-muted-foreground text-[11px]">Version: <?= e(INSTAGRAM_GRAPH_VERSION) ?> · Redirect: <?= e(INSTAGRAM_REDIRECT_URI) ?></span>
        </div>
        <div>
          <?php if (!empty(INSTAGRAM_APP_ID) && !empty(INSTAGRAM_APP_SECRET)): ?>
            <span class="rounded bg-emerald-500/20 text-emerald-400 px-2 py-0.5 text-[10px] font-semibold">Configured</span>
          <?php else: ?>
            <span class="rounded bg-amber-500/20 text-amber-400 px-2 py-0.5 text-[10px]">App ID / Secret Needed</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="flex items-center justify-between p-3 rounded-lg border border-border/60 bg-background/40">
        <div>
          <strong class="text-foreground block">Cron Secret Token</strong>
          <span class="text-muted-foreground text-[11px]">Protects the background automation runner endpoint</span>
        </div>
        <div>
          <?php if (!empty(CRON_SECRET) && CRON_SECRET !== 'change_this_cron_token_for_security'): ?>
            <span class="rounded bg-emerald-500/20 text-emerald-400 px-2 py-0.5 text-[10px] font-semibold">Secured</span>
          <?php else: ?>
            <span class="rounded bg-amber-500/20 text-amber-400 px-2 py-0.5 text-[10px]">Default Token (Change in .env)</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</main>

<?php require_once (file_exists(__DIR__ . '/includes/footer.php') ? __DIR__ . '/includes/footer.php' : __DIR__ . '/../includes/footer.php'); ?>

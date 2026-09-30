<?php
/**
 * Dashboard Overview
 * Carouselfy - Instagram Automation
 */

$pageTitle = 'Dashboard';
require_once (file_exists(__DIR__ . '/includes/header.php') ? __DIR__ . '/includes/header.php' : __DIR__ . '/../includes/header.php');

$pdo = Database::getConnection();
$dbConnected = ($pdo !== null);

$projectCount = 0;
$accountCount = 0;
$scheduledCount = 0;
$publishedCount = 0;

$recentProjects = [];
$recentScheduled = [];

if ($dbConnected) {
    try {
        $userId = current_user_id();

        // Stats
        if ($userId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
            $stmt->execute([$userId]);
            $projectCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM instagram_accounts WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $accountCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM scheduled_posts WHERE user_id = ? AND status = 'scheduled'");
            $stmt->execute([$userId]);
            $scheduledCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM scheduled_posts WHERE user_id = ? AND status = 'published'");
            $stmt->execute([$userId]);
            $publishedCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT 6");
            $stmt->execute([$userId]);
            $recentProjects = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT sp.*, ia.instagram_username FROM scheduled_posts sp 
                                   JOIN instagram_accounts ia ON sp.instagram_account_id = ia.id 
                                   WHERE sp.user_id = ? ORDER BY sp.scheduled_at DESC LIMIT 6");
            $stmt->execute([$userId]);
            $recentScheduled = $stmt->fetchAll();
        } else {
            $projectCount = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
            $accountCount = (int)$pdo->query("SELECT COUNT(*) FROM instagram_accounts WHERE is_active = 1")->fetchColumn();
            $scheduledCount = (int)$pdo->query("SELECT COUNT(*) FROM scheduled_posts WHERE status = 'scheduled'")->fetchColumn();
            $publishedCount = (int)$pdo->query("SELECT COUNT(*) FROM scheduled_posts WHERE status = 'published'")->fetchColumn();

            $recentProjects = $pdo->query("SELECT * FROM projects ORDER BY updated_at DESC LIMIT 6")->fetchAll();
            $recentScheduled = $pdo->query("SELECT sp.*, ia.instagram_username FROM scheduled_posts sp 
                                           JOIN instagram_accounts ia ON sp.instagram_account_id = ia.id 
                                           ORDER BY sp.scheduled_at DESC LIMIT 6")->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Dashboard query error: " . $e->getMessage());
    }
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

  <!-- Welcome Banner -->
  <div class="rounded-2xl border border-border bg-gradient-to-r from-card via-card/80 to-primary/10 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold tracking-tight text-foreground font-['Space_Grotesk']">
        Instagram Automation Dashboard
      </h1>
      <p class="text-sm text-muted-foreground">
        Design viral carousels, connect Meta Instagram accounts, and schedule automatic publishing.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <a href="/index.php" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
        New Carousel
      </a>
      <a href="/schedule.php" class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-xs font-semibold text-foreground hover:bg-accent transition-colors">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
        Schedule Post
      </a>
    </div>
  </div>

  <?php if (!$dbConnected): ?>
    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-200 flex items-start gap-3">
      <svg class="size-5 shrink-0 text-amber-400 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
      <div>
        <strong class="font-semibold text-amber-100">Database not configured yet.</strong>
        <p class="mt-1 text-amber-300/90">
          The studio will store projects in your browser's localStorage. To enable database persistence, user accounts, and scheduled posts, create a MySQL database on Hostinger, import <code class="bg-black/30 px-1 py-0.5 rounded">database.sql</code>, and configure credentials in <code class="bg-black/30 px-1 py-0.5 rounded">.env</code>.
        </p>
        <a href="/settings.php" class="mt-2 inline-block font-semibold underline text-amber-200 hover:text-white">Configure Database in Settings →</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Stats Grid -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="rounded-xl border border-border bg-card/60 p-4">
      <div class="flex items-center justify-between text-muted-foreground mb-2">
        <span class="text-xs font-medium">Saved Projects</span>
        <svg class="size-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/></svg>
      </div>
      <p class="text-2xl font-bold tracking-tight text-foreground"><?= $projectCount ?></p>
      <span class="text-[11px] text-muted-foreground">Multi-slide carousels</span>
    </div>

    <div class="rounded-xl border border-border bg-card/60 p-4">
      <div class="flex items-center justify-between text-muted-foreground mb-2">
        <span class="text-xs font-medium">Instagram Accounts</span>
        <svg class="size-4 text-pink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
      </div>
      <p class="text-2xl font-bold tracking-tight text-foreground"><?= $accountCount ?></p>
      <span class="text-[11px] text-muted-foreground">Connected Meta Pages</span>
    </div>

    <div class="rounded-xl border border-border bg-card/60 p-4">
      <div class="flex items-center justify-between text-muted-foreground mb-2">
        <span class="text-xs font-medium">Scheduled Queue</span>
        <svg class="size-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <p class="text-2xl font-bold tracking-tight text-foreground"><?= $scheduledCount ?></p>
      <span class="text-[11px] text-muted-foreground">Pending publishing</span>
    </div>

    <div class="rounded-xl border border-border bg-card/60 p-4">
      <div class="flex items-center justify-between text-muted-foreground mb-2">
        <span class="text-xs font-medium">Published Posts</span>
        <svg class="size-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <p class="text-2xl font-bold tracking-tight text-foreground"><?= $publishedCount ?></p>
      <span class="text-[11px] text-muted-foreground">Live on Instagram</span>
    </div>
  </div>

  <!-- Recent Projects Section -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-foreground">Recent Carousels</h2>
      <a href="/index.php" class="text-xs text-primary hover:underline">Open Studio →</a>
    </div>

    <?php if (empty($recentProjects)): ?>
      <div class="rounded-lg border border-dashed border-border/80 p-8 text-center text-xs text-muted-foreground">
        <p>No projects saved to database yet.</p>
        <p class="mt-1 text-[11px]">Open the Studio and hit <strong class="text-foreground">Save</strong> to persist projects to MySQL.</p>
        <a href="/index.php" class="mt-3 inline-block rounded bg-primary px-3 py-1.5 text-xs text-primary-foreground font-medium">Create Your First Carousel</a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($recentProjects as $p): ?>
          <div class="rounded-lg border border-border bg-card/60 p-3.5 flex flex-col justify-between hover:border-primary/50 transition-colors">
            <div>
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="rounded bg-primary/20 text-primary px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider">
                  <?= e($p['aspect_ratio']) ?>
                </span>
                <span class="text-[11px] text-muted-foreground">
                  <?= date('M j, Y', strtotime($p['updated_at'])) ?>
                </span>
              </div>
              <h3 class="font-semibold text-sm text-foreground line-clamp-1"><?= e($p['title']) ?></h3>
              <p class="text-xs text-muted-foreground line-clamp-1 mt-0.5"><?= e($p['topic'] ?: 'No topic specified') ?></p>
            </div>
            <div class="mt-4 pt-3 border-t border-border/60 flex items-center justify-between">
              <a href="/index.php?project=<?= urlencode($p['id']) ?>" class="text-xs font-medium text-primary hover:underline flex items-center gap-1">
                Edit in Studio
                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
              <button onclick="deleteProject('<?= e($p['id']) ?>')" class="text-xs text-muted-foreground hover:text-destructive transition-colors">Delete</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Scheduled Posts Queue Section -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-foreground">Upcoming Scheduled Posts</h2>
      <a href="/schedule.php" class="text-xs text-primary hover:underline">Manage All Scheduled →</a>
    </div>

    <?php if (empty($recentScheduled)): ?>
      <div class="rounded-lg border border-dashed border-border/80 p-8 text-center text-xs text-muted-foreground">
        <p>No posts in queue.</p>
        <a href="/schedule.php" class="mt-2 inline-block rounded border border-border px-3 py-1.5 text-xs text-foreground font-medium hover:bg-accent">Schedule a Post</a>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-border text-muted-foreground">
              <th class="py-2.5 px-3">Account</th>
              <th class="py-2.5 px-3">Type</th>
              <th class="py-2.5 px-3">Caption</th>
              <th class="py-2.5 px-3">Scheduled Time</th>
              <th class="py-2.5 px-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/60">
            <?php foreach ($recentScheduled as $s): ?>
              <tr>
                <td class="py-2.5 px-3 font-medium text-foreground">@<?= e($s['instagram_username']) ?></td>
                <td class="py-2.5 px-3 uppercase text-[10px] text-muted-foreground"><?= e($s['post_type']) ?></td>
                <td class="py-2.5 px-3 max-w-xs truncate text-muted-foreground"><?= e($s['caption'] ?: 'No caption') ?></td>
                <td class="py-2.5 px-3 text-muted-foreground"><?= date('M j, Y g:i A', strtotime($s['scheduled_at'])) ?></td>
                <td class="py-2.5 px-3">
                  <?php if ($s['status'] === 'published'): ?>
                    <span class="rounded bg-emerald-500/20 text-emerald-400 px-2 py-0.5 text-[10px] font-semibold">Published</span>
                  <?php elseif ($s['status'] === 'scheduled'): ?>
                    <span class="rounded bg-cyan-500/20 text-cyan-400 px-2 py-0.5 text-[10px] font-semibold">Scheduled</span>
                  <?php elseif ($s['status'] === 'publishing'): ?>
                    <span class="rounded bg-amber-500/20 text-amber-400 px-2 py-0.5 text-[10px] font-semibold">Publishing…</span>
                  <?php else: ?>
                    <span class="rounded bg-red-500/20 text-red-400 px-2 py-0.5 text-[10px] font-semibold" title="<?= e($s['error_message']) ?>">Failed</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

<script>
async function deleteProject(id) {
  if (!confirm('Are you sure you want to delete this project?')) return;
  try {
    const res = await fetch('/api/projects/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (data.ok) {
      location.reload();
    } else {
      alert(data.error || 'Failed to delete');
    }
  } catch (e) {
    alert('Failed to connect to server.');
  }
}
</script>

<?php require_once (file_exists(__DIR__ . '/includes/footer.php') ? __DIR__ . '/includes/footer.php' : __DIR__ . '/../includes/footer.php'); ?>

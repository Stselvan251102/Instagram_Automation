<?php
/**
 * Post Scheduling & Automation Queue
 * Carouselfy - Instagram Automation
 */

$pageTitle = 'Post Scheduling';
require_once __DIR__ . '/../includes/header.php';

$pdo = Database::getConnection();
$accounts = [];
$projects = [];
$scheduledList = [];

if ($pdo) {
    try {
        $userId = current_user_id();
        if ($userId) {
            $accStmt = $pdo->prepare("SELECT id, instagram_username, page_name FROM instagram_accounts WHERE (user_id = ? OR user_id IS NULL) AND is_active = 1");
            $accStmt->execute([$userId]);
            $accounts = $accStmt->fetchAll();

            $projStmt = $pdo->prepare("SELECT id, title, topic FROM projects WHERE (user_id = ? OR user_id IS NULL) ORDER BY updated_at DESC");
            $projStmt->execute([$userId]);
            $projects = $projStmt->fetchAll();

            $schStmt = $pdo->prepare("SELECT sp.*, ia.instagram_username FROM scheduled_posts sp 
                                      JOIN instagram_accounts ia ON sp.instagram_account_id = ia.id 
                                      WHERE (sp.user_id = ? OR sp.user_id IS NULL) 
                                      ORDER BY sp.scheduled_at DESC");
            $schStmt->execute([$userId]);
            $scheduledList = $schStmt->fetchAll();
        } else {
            $accounts = $pdo->query("SELECT id, instagram_username, page_name FROM instagram_accounts WHERE is_active = 1")->fetchAll();
            $projects = $pdo->query("SELECT id, title, topic FROM projects ORDER BY updated_at DESC")->fetchAll();
            $scheduledList = $pdo->query("SELECT sp.*, ia.instagram_username FROM scheduled_posts sp 
                                          JOIN instagram_accounts ia ON sp.instagram_account_id = ia.id 
                                          ORDER BY sp.scheduled_at DESC")->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Schedule error: " . $e->getMessage());
    }
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground font-['Space_Grotesk']">
        Instagram Scheduling & Automation
      </h1>
      <p class="text-sm text-muted-foreground">
        Queue carousels to auto-publish on Instagram at high-engagement hours.
      </p>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="document.getElementById('schedule-form-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
        Schedule New Post
      </button>
    </div>
  </div>

  <!-- Hostinger Cron Job Instructions Banner -->
  <div class="rounded-xl border border-border bg-card/60 p-4 text-xs space-y-2">
    <div class="flex items-center justify-between">
      <span class="font-semibold text-foreground flex items-center gap-2">
        <svg class="size-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Hostinger Cron Automation Setup
      </span>
      <button onclick="runCronNow()" id="btn-cron-now" class="px-2.5 py-1 rounded bg-muted hover:bg-muted/80 text-[11px] font-medium text-foreground transition-colors">
        Run Cron Worker Now
      </button>
    </div>
    <p class="text-muted-foreground leading-relaxed">
      To automatically publish scheduled posts in the background on Hostinger shared hosting, add a Cron Job in your Hostinger hPanel (<strong class="text-foreground">Advanced → Cron Jobs</strong>):
    </p>
    <div class="flex items-center gap-2 bg-black/40 border border-border/80 px-3 py-2 rounded-lg font-mono text-[11px] text-zinc-300 overflow-x-auto">
      <code>* * * * * php <?= e(PUBLIC_PATH) ?>/cron/publish_scheduled.php > /dev/null 2>&1</code>
    </div>
  </div>

  <!-- Scheduled Posts Queue Table -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-4">
    <h2 class="text-base font-semibold text-foreground">Publication Queue (<?= count($scheduledList) ?>)</h2>

    <?php if (empty($scheduledList)): ?>
      <div class="rounded-lg border border-dashed border-border/80 p-8 text-center text-xs text-muted-foreground">
        <p>No posts currently scheduled.</p>
        <p class="mt-1 text-[11px]">Click "Schedule New Post" above or export directly from the Studio.</p>
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
              <th class="py-2.5 px-3">Instagram Post</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/60">
            <?php foreach ($scheduledList as $s): ?>
              <tr>
                <td class="py-3 px-3 font-medium text-foreground">@<?= e($s['instagram_username']) ?></td>
                <td class="py-3 px-3 uppercase text-[10px] text-muted-foreground">
                  <span class="px-1.5 py-0.5 rounded bg-muted"><?= e($s['post_type']) ?></span>
                </td>
                <td class="py-3 px-3 max-w-xs truncate text-muted-foreground"><?= e($s['caption'] ?: 'No caption') ?></td>
                <td class="py-3 px-3 text-muted-foreground">
                  <?= date('M j, Y g:i A', strtotime($s['scheduled_at'])) ?>
                </td>
                <td class="py-3 px-3">
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
                <td class="py-3 px-3">
                  <?php if (!empty($s['instagram_media_id'])): ?>
                    <span class="text-zinc-400 font-mono text-[11px]"><?= e($s['instagram_media_id']) ?></span>
                  <?php elseif (!empty($s['error_message'])): ?>
                    <span class="text-red-400 truncate max-w-xs block text-[11px]"><?= e($s['error_message']) ?></span>
                  <?php else: ?>
                    <span class="text-zinc-500">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal: Schedule Post -->
  <div id="schedule-form-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-card border border-border rounded-xl max-w-lg w-full p-6 space-y-4 shadow-xl">
      <div class="flex items-center justify-between border-b border-border pb-3">
        <h3 class="text-base font-semibold text-foreground">Schedule Carousel Post</h3>
        <button onclick="document.getElementById('schedule-form-modal').classList.add('hidden')" class="text-muted-foreground hover:text-foreground">
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </button>
      </div>

      <form id="schedule-form" onsubmit="handleScheduleSubmit(event)" class="space-y-3.5 text-xs">
        <div>
          <label class="block font-medium text-foreground mb-1">Select Instagram Account</label>
          <select id="sch-account" required class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
            <?php if (empty($accounts)): ?>
              <option value="">No accounts connected — connect one in Instagram page</option>
            <?php else: ?>
              <?php foreach ($accounts as $a): ?>
                <option value="<?= e($a['id']) ?>">@<?= e($a['instagram_username']) ?> (<?= e($a['page_name']) ?>)</option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div>
          <label class="block font-medium text-foreground mb-1">Select Carousel Project</label>
          <select id="sch-project" class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">-- Manual image URLs or upload below --</option>
            <?php foreach ($projects as $pr): ?>
              <option value="<?= e($pr['id']) ?>"><?= e($pr['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block font-medium text-foreground mb-1">Image URLs (comma separated or 1 per line, min 2 for carousel)</label>
          <textarea id="sch-images" rows="3" placeholder="https://domain.com/uploads/slide1.png&#10;https://domain.com/uploads/slide2.png" required class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary font-mono text-[11px]"></textarea>
          <p class="text-[10px] text-muted-foreground mt-0.5">Images must be publicly accessible via HTTPS for Instagram Graph API ingest.</p>
        </div>

        <div>
          <label class="block font-medium text-foreground mb-1">Post Caption</label>
          <textarea id="sch-caption" rows="3" placeholder="Write your hook, caption, and call-to-action..." class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
        </div>

        <div>
          <label class="block font-medium text-foreground mb-1">Hashtags (Appended to caption)</label>
          <input type="text" id="sch-hashtags" placeholder="#webdevelopment #coding #softwareengineer" class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
        </div>

        <div>
          <label class="block font-medium text-foreground mb-1">Schedule Date & Time</label>
          <input type="datetime-local" id="sch-datetime" required class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
        </div>

        <div class="pt-2 flex items-center justify-end gap-2">
          <button type="button" onclick="document.getElementById('schedule-form-modal').classList.add('hidden')" class="px-3 py-1.5 rounded-lg border border-border text-muted-foreground hover:bg-accent">Cancel</button>
          <button type="submit" id="btn-submit-sch" class="px-4 py-1.5 rounded-lg bg-primary text-primary-foreground font-semibold hover:bg-primary/90 shadow-sm">Confirm Schedule</button>
        </div>
      </form>
    </div>
  </div>

</main>

<script>
async function handleScheduleSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-submit-sch');
  btn.disabled = true;
  btn.innerText = 'Scheduling…';

  const accountId = document.getElementById('sch-account').value;
  const rawUrls = document.getElementById('sch-images').value;
  const imageUrls = rawUrls.split(/[\n,]/).map(s => s.trim()).filter(s => s.length > 0);
  const caption = document.getElementById('sch-caption').value;
  const hashtags = document.getElementById('sch-hashtags').value;
  const scheduledAt = document.getElementById('sch-datetime').value;
  const projectId = document.getElementById('sch-project').value;

  if (imageUrls.length < 1) {
    alert('Please provide at least one valid image URL.');
    btn.disabled = false;
    btn.innerText = 'Confirm Schedule';
    return;
  }

  try {
    const res = await fetch('/api/instagram/schedule.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        account_id: accountId,
        image_urls: imageUrls,
        caption: caption,
        hashtags: hashtags,
        scheduled_at: scheduledAt,
        project_id: projectId
      })
    });
    const data = await res.json();
    if (data.ok) {
      alert(data.message || 'Post scheduled successfully!');
      location.reload();
    } else {
      alert(data.error || 'Failed to schedule post.');
      btn.disabled = false;
      btn.innerText = 'Confirm Schedule';
    }
  } catch (err) {
    alert('Connection error: ' + err.message);
    btn.disabled = false;
    btn.innerText = 'Confirm Schedule';
  }
}

async function runCronNow() {
  const btn = document.getElementById('btn-cron-now');
  btn.disabled = true;
  btn.innerText = 'Running worker…';
  try {
    const res = await fetch('/cron/publish_scheduled.php?secret=<?= urlencode(CRON_SECRET) ?>');
    const data = await res.json();
    if (data.ok) {
      alert(`Worker ran successfully! Processed: ${data.processed}, Succeeded: ${data.succeeded}, Failed: ${data.failed}`);
      location.reload();
    } else {
      alert('Cron error: ' + (data.error || 'Unknown error'));
      btn.disabled = false;
      btn.innerText = 'Run Cron Worker Now';
    }
  } catch (e) {
    alert('Failed to trigger cron: ' + e.message);
    btn.disabled = false;
    btn.innerText = 'Run Cron Worker Now';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

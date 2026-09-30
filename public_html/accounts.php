<?php
/**
 * Connected Instagram Accounts Manager
 * Carouselfy - Instagram Automation
 */

$pageTitle = 'Instagram Accounts';
require_once __DIR__ . '/../includes/header.php';

$pdo = Database::getConnection();
$accounts = [];
$errorMsg = $_GET['error'] ?? null;
$connectedCount = $_GET['connected'] ?? null;

if ($pdo) {
    try {
        $userId = current_user_id();
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM instagram_accounts WHERE user_id = ? OR user_id IS NULL ORDER BY updated_at DESC");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM instagram_accounts ORDER BY updated_at DESC");
        }
        $accounts = $stmt->fetchAll();
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground font-['Space_Grotesk']">
        Connected Instagram Accounts
      </h1>
      <p class="text-sm text-muted-foreground">
        Link Instagram Business or Creator accounts via Meta Graph API to automate publishing.
      </p>
    </div>
    <div class="flex items-center gap-2">
      <a href="/api/instagram/connect.php" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 px-4 py-2 text-xs font-semibold text-white hover:opacity-95 transition-opacity shadow-sm">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
        Connect with Facebook / Instagram
      </a>
    </div>
  </div>

  <?php if (!empty($connectedCount)): ?>
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs text-emerald-200 flex items-center justify-between">
      <span>Successfully connected <strong><?= (int)$connectedCount ?></strong> Instagram Business account(s)!</span>
      <a href="/accounts.php" class="text-emerald-400 hover:underline">Dismiss</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($errorMsg)): ?>
    <div class="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-xs text-destructive-foreground">
      <p class="font-semibold">Connection Notice / Error:</p>
      <p class="mt-1 opacity-90"><?= e($errorMsg) ?></p>
    </div>
  <?php endif; ?>

  <!-- Accounts List -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php if (empty($accounts)): ?>
      <div class="col-span-full rounded-xl border border-dashed border-border/80 p-12 text-center text-xs text-muted-foreground bg-card/20 space-y-3">
        <div class="mx-auto size-12 rounded-full bg-gradient-to-tr from-purple-600/20 to-pink-600/20 border border-pink-500/30 grid place-items-center text-pink-400">
          <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
        </div>
        <div class="space-y-1">
          <h3 class="font-semibold text-sm text-foreground">No Instagram Accounts Connected</h3>
          <p class="max-w-md mx-auto text-muted-foreground">
            Connect an Instagram Creator or Business account linked to your Facebook Page to enable automated multi-slide carousel publishing.
          </p>
        </div>
        <div class="pt-2">
          <a href="/api/instagram/connect.php" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 shadow-sm transition-colors">
            Connect First Account
          </a>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($accounts as $acc): ?>
        <div class="rounded-xl border border-border bg-card/60 p-5 space-y-4 hover:border-border/80 transition-colors">
          <div class="flex items-start gap-3.5">
            <?php if (!empty($acc['profile_picture_url'])): ?>
              <img src="<?= e($acc['profile_picture_url']) ?>" alt="@<?= e($acc['instagram_username']) ?>" class="size-12 rounded-full object-cover border border-border">
            <?php else: ?>
              <div class="size-12 rounded-full bg-gradient-to-tr from-purple-600 to-pink-500 text-white grid place-items-center font-bold text-base">
                <?= strtoupper(substr($acc['instagram_username'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-sm text-foreground truncate">@<?= e($acc['instagram_username']) ?></h3>
              <p class="text-xs text-muted-foreground truncate">Page: <?= e($acc['page_name']) ?></p>
              <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center gap-1 rounded bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 text-[10px] font-semibold">
                  <span class="size-1.5 rounded-full bg-emerald-400"></span> Active
                </span>
                <?php if (!empty($acc['followers_count'])): ?>
                  <span class="text-[11px] text-muted-foreground"><?= number_format($acc['followers_count']) ?> followers</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="rounded-lg bg-black/30 border border-border/50 p-2.5 space-y-1 text-[11px]">
            <div class="flex justify-between text-muted-foreground">
              <span>Instagram ID:</span>
              <span class="font-mono text-zinc-300"><?= e($acc['instagram_business_id']) ?></span>
            </div>
            <div class="flex justify-between text-muted-foreground">
              <span>Token Status:</span>
              <span class="text-emerald-400 font-medium">Valid (60-day token)</span>
            </div>
          </div>

          <div class="pt-2 flex items-center justify-between border-t border-border/60 text-xs">
            <button onclick="testPublishModal(<?= e($acc['id']) ?>, '<?= e($acc['instagram_username']) ?>')" class="text-primary hover:underline font-medium">
              Publish Test Carousel
            </button>
            <a href="https://www.instagram.com/<?= urlencode($acc['instagram_username']) ?>/" target="_blank" rel="noopener" class="text-muted-foreground hover:text-foreground">
              View Profile ↗
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Meta Graph API Requirements Guide Card -->
  <div class="rounded-xl border border-border bg-card/40 p-5 space-y-3 text-xs">
    <h3 class="font-semibold text-sm text-foreground flex items-center gap-2">
      <svg class="size-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      Meta Graph API Requirements for Carousel Publishing
    </h3>
    <ol class="list-decimal list-inside space-y-1.5 text-muted-foreground leading-relaxed">
      <li>An Instagram account converted to a <strong class="text-foreground">Business</strong> or <strong class="text-foreground">Creator</strong> account.</li>
      <li>The Instagram account must be linked to a Facebook Page you manage.</li>
      <li>A Meta Developer App with the <strong class="text-foreground">Instagram Graph API</strong> and <strong class="text-foreground">Facebook Login for Business</strong> products added.</li>
      <li>App permissions required: <code class="bg-black/30 px-1 py-0.5 rounded text-zinc-300">instagram_basic</code>, <code class="bg-black/30 px-1 py-0.5 rounded text-zinc-300">instagram_content_publish</code>, <code class="bg-black/30 px-1 py-0.5 rounded text-zinc-300">pages_show_list</code>.</li>
      <li>Configure your <strong class="text-foreground">INSTAGRAM_APP_ID</strong> and <strong class="text-foreground">INSTAGRAM_APP_SECRET</strong> in <a href="/settings.php" class="text-primary underline">Settings</a>.</li>
    </ol>
  </div>

</main>

<!-- Test Publish Modal -->
<div id="test-publish-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="bg-card border border-border rounded-xl max-w-md w-full p-6 space-y-4 shadow-xl text-xs">
    <div class="flex items-center justify-between border-b border-border pb-3">
      <h3 class="text-base font-semibold text-foreground">Publish Test Carousel</h3>
      <button onclick="document.getElementById('test-publish-modal').classList.add('hidden')" class="text-muted-foreground hover:text-foreground">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
      </button>
    </div>

    <form onsubmit="handleDirectPublish(event)" class="space-y-3">
      <input type="hidden" id="pub-account-id">
      <p class="text-muted-foreground">Publishing to: <strong id="pub-account-name" class="text-foreground"></strong></p>

      <div>
        <label class="block font-medium text-foreground mb-1">Image 1 Public HTTPS URL</label>
        <input type="url" id="pub-img-1" required placeholder="https://yourdomain.com/uploads/slide1.png" class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground font-mono text-[11px]">
      </div>

      <div>
        <label class="block font-medium text-foreground mb-1">Image 2 Public HTTPS URL</label>
        <input type="url" id="pub-img-2" required placeholder="https://yourdomain.com/uploads/slide2.png" class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground font-mono text-[11px]">
      </div>

      <div>
        <label class="block font-medium text-foreground mb-1">Caption</label>
        <textarea id="pub-caption" rows="2" placeholder="Test carousel published from Carouselfy Studio" class="w-full bg-background border border-border rounded-lg px-3 py-2 text-foreground"></textarea>
      </div>

      <div class="pt-2 flex items-center justify-end gap-2">
        <button type="button" onclick="document.getElementById('test-publish-modal').classList.add('hidden')" class="px-3 py-1.5 rounded-lg border border-border text-muted-foreground">Cancel</button>
        <button type="submit" id="btn-pub-submit" class="px-4 py-1.5 rounded-lg bg-primary text-primary-foreground font-semibold hover:bg-primary/90">Publish Now</button>
      </div>
    </form>
  </div>
</div>

<script>
function testPublishModal(accountId, username) {
  document.getElementById('pub-account-id').value = accountId;
  document.getElementById('pub-account-name').innerText = '@' + username;
  document.getElementById('test-publish-modal').classList.remove('hidden');
}

async function handleDirectPublish(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-pub-submit');
  btn.disabled = true;
  btn.innerText = 'Publishing to Instagram…';

  const accountId = document.getElementById('pub-account-id').value;
  const img1 = document.getElementById('pub-img-1').value.trim();
  const img2 = document.getElementById('pub-img-2').value.trim();
  const caption = document.getElementById('pub-caption').value.trim();

  try {
    const res = await fetch('/api/instagram/publish.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        account_id: accountId,
        image_urls: [img1, img2],
        caption: caption
      })
    });
    const data = await res.json();
    if (data.ok) {
      alert('Published to Instagram successfully! Media ID: ' + data.post_id);
      location.reload();
    } else {
      alert('Publishing error: ' + (data.error || 'Failed'));
      btn.disabled = false;
      btn.innerText = 'Publish Now';
    }
  } catch (err) {
    alert('Connection error: ' + err.message);
    btn.disabled = false;
    btn.innerText = 'Publish Now';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * User Registration Page
 * Carouselfy - Instagram Automation
 */

$pageTitle = 'Create Account';
require_once (file_exists(__DIR__ . '/config/config.php') ? __DIR__ . '/config/config.php' : __DIR__ . '/../config/config.php');
require_once (file_exists(__DIR__ . '/includes/functions.php') ? __DIR__ . '/includes/functions.php' : __DIR__ . '/../includes/functions.php');
require_once (file_exists(__DIR__ . '/includes/auth.php') ? __DIR__ . '/includes/auth.php' : __DIR__ . '/../includes/auth.php');

if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $res = Auth::register($name, $email, $password);
        if ($res['ok']) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = $res['error'];
        }
    }
}

require_once (file_exists(__DIR__ . '/includes/header.php') ? __DIR__ . '/includes/header.php' : __DIR__ . '/../includes/header.php');
?>

<main class="flex-1 flex items-center justify-center p-4">
  <div class="max-w-md w-full bg-card border border-border rounded-2xl p-6 sm:p-8 space-y-6 shadow-xl">
    <div class="text-center space-y-1.5">
      <h1 class="text-2xl font-bold tracking-tight text-foreground font-['Space_Grotesk']">Create an account</h1>
      <p class="text-xs text-muted-foreground">Start creating and automating Instagram carousels</p>
    </div>

    <?php if ($error): ?>
      <div class="rounded-lg bg-destructive/10 border border-destructive/30 p-3 text-xs text-destructive-foreground">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/register.php" class="space-y-4 text-xs">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

      <div>
        <label class="block font-medium text-foreground mb-1">Your Name</label>
        <input type="text" name="name" required placeholder="Alex Creator" class="w-full bg-background border border-border rounded-lg px-3 py-2.5 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
      </div>

      <div>
        <label class="block font-medium text-foreground mb-1">Email Address</label>
        <input type="email" name="email" required placeholder="alex@creator.com" class="w-full bg-background border border-border rounded-lg px-3 py-2.5 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
      </div>

      <div>
        <label class="block font-medium text-foreground mb-1">Password</label>
        <input type="password" name="password" required placeholder="At least 6 characters" class="w-full bg-background border border-border rounded-lg px-3 py-2.5 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
      </div>

      <button type="submit" class="w-full py-2.5 rounded-lg bg-primary text-primary-foreground font-semibold hover:bg-primary/90 transition-colors shadow-sm text-xs">
        Create Account
      </button>
    </form>

    <div class="text-center text-xs text-muted-foreground pt-2 border-t border-border/60">
      Already have an account? <a href="/login.php" class="text-primary hover:underline font-medium">Sign in</a>
    </div>
  </div>
</main>

<?php require_once (file_exists(__DIR__ . '/includes/footer.php') ? __DIR__ . '/includes/footer.php' : __DIR__ . '/../includes/footer.php'); ?>

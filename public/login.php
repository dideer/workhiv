<?php
require_once __DIR__ . '/../src/Controllers/AuthController.php';

Session::start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$message = (string) ($_GET['message'] ?? '');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $auth = new AuthController();
    $result = $auth->login($email, $password);

    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    }

    $error = $result['message'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Authentication navigation">
            <a class="site-logo" href="index.php" aria-label="WorkHive home">WorkHive</a>
        </nav>
    </header>

    <main class="auth-main auth-split">
        <section class="auth-shell" aria-labelledby="login-title">
            <div class="auth-card">
                <p class="section-kicker">Account access</p>
                <h1 id="login-title">Welcome back</h1>
                <p>Sign in to manage your applications and saved opportunities.</p>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert"><?php echo e($error); ?></div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="form-alert"><?php echo e($message); ?></div>
                <?php endif; ?>

                <form class="auth-form" action="login.php" method="post">
                    <div class="form-field">
                        <label for="login-email">Email address</label>
                        <input type="text" id="login-email" name="email" value="<?php echo e($email); ?>" autocomplete="email" required>
                    </div>

                    <div class="form-field">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" autocomplete="current-password" required>
                    </div>

                    <button class="button-primary" type="submit">Sign in</button>
                </form>

                <p class="auth-switch">New here? <a href="register.php">Create an account</a></p>
            </div>
        </section>
        <aside class="auth-photo auth-photo-login" aria-label="Professional workplace scene"></aside>
    </main>
</body>
</html>

<?php

require_once __DIR__ . '/app/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect(is_admin() ? url('modules/admin/dashboard.php') : url('dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!is_valid_email($email)) {
        flash('error', 'Please enter a valid email address.');
        redirect(url('login.php'));
    }

    $stmt = mysqli_prepare($conn, 'SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && verify_password($password, $user['password'])) {
        if (!user_is_approved($user)) {
            flash('error', 'Your account is pending admin approval. Please try again later.');
            redirect(url('login.php'));
        }

        upgrade_password_if_plain($conn, (int) $user['user_id'], $password, $user['password']);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        redirect((int) $user['role_id'] === 8 ? url('modules/admin/dashboard.php') : url('dashboard.php'));
    }

    flash('error', 'Invalid email or password.');
}

$pageTitle = 'Sign In';
$layout = 'auth';
$showNav = false;
require APP_ROOT . '/app/views/layout/header.php';
?>

<div class="auth-landing">
    <div class="auth-landing-bg" aria-hidden="true">
        <span class="auth-circle auth-circle-1"></span>
        <span class="auth-circle auth-circle-2"></span>
    </div>

    <div class="auth-landing-inner">
        <div class="auth-brand-center">
            <img src="<?= e(asset(LOGO_URL)) ?>" alt="PWMS" class="auth-logo">
            <h1>PWMS</h1>
            <p>Proposal Workflow Management System</p>
        </div>

        <div class="auth-card">
            <h2>Sign in</h2>
            <p class="subtitle">Enter your credentials to continue</p>

            <form method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" name="email" required class="input-field" placeholder="name@company.com" autocomplete="email" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$" title="Enter a valid email address">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <input type="password" name="password" required class="input-field" placeholder="Enter password" autocomplete="current-password">
                        <button type="button" class="password-toggle" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-block">Sign In</button>
            </form>

            <p class="auth-switch">New staff? <a href="<?= e(url('register.php')) ?>">Register here</a></p>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layout/footer.php'; ?>

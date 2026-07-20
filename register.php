<?php

require_once __DIR__ . '/app/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect(is_admin() ? url('modules/admin/dashboard.php') : url('dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $staffId = trim($_POST['staff_id'] ?? '');
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirmation'] ?? '';

    if ($name === '') {
        flash('error', 'Full name is required.');
        redirect(url('register.php'));
    }

    if (!is_valid_email($email)) {
        flash('error', 'Please enter a valid email address.');
        redirect(url('register.php'));
    }

    if (email_exists($conn, $email)) {
        flash('error', 'This email is already registered.');
        redirect(url('register.php'));
    }

    if ($departmentId <= 0) {
        flash('error', 'Please select a department.');
        redirect(url('register.php'));
    }

    if (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
        redirect(url('register.php'));
    }

    if ($password !== $confirmPassword) {
        flash('error', 'Passwords do not match.');
        redirect(url('register.php'));
    }

    $hash = hash_password($password);
    $roleId = 1;
    $isApproved = 0;

    $stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, staff_id, department_id, role_id, password, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssiisi', $name, $email, $staffId, $departmentId, $roleId, $hash, $isApproved);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    notify_admins($conn, 'New staff registration pending approval: ' . $name . ' (' . $email . ')');

    flash('success', 'Registration submitted. You can sign in after an admin approves your account.');
    redirect(url('login.php'));
}

$departments = mysqli_query($conn, 'SELECT * FROM departments ORDER BY department_name');

$pageTitle = 'Register';
$layout = 'auth';
$showNav = false;
require APP_ROOT . '/app/views/layout/header.php';
?>

<div class="auth-landing">
    <div class="auth-landing-bg" aria-hidden="true">
        <span class="auth-circle auth-circle-1"></span>
        <span class="auth-circle auth-circle-2"></span>
    </div>

    <div class="auth-landing-inner auth-landing-wide">
        <div class="auth-brand-center">
            <img src="<?= e(asset(LOGO_URL)) ?>" alt="PWMS" class="auth-logo">
            <h1>PWMS</h1>
            <p>Staff registration</p>
        </div>

        <div class="auth-card">
            <h2>Create account</h2>
            <p class="subtitle">Register as staff. Admin approval is required before login.</p>

            <form method="POST" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Full name</label>
                    <input type="text" name="name" required class="input-field" autocomplete="name">
                </div>

                <div class="form-group">
                    <label>Email address</label>
                    <input type="email" name="email" required class="input-field" placeholder="name@company.com" autocomplete="email" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$" title="Enter a valid email address">
                </div>

                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="text" name="staff_id" class="input-field" autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" required class="input-field">
                        <option value="">Select department</option>
                        <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
                        <option value="<?= e($dept['department_id']) ?>"><?= e($dept['department_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <input type="password" name="password" required class="input-field" minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                        <button type="button" class="password-toggle" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm password</label>
                    <div class="password-field">
                        <input type="password" name="password_confirmation" required class="input-field" minlength="6" autocomplete="new-password" placeholder="Re-enter password">
                        <button type="button" class="password-toggle" aria-label="Show password"><i class="bx bx-show"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block">Register</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layout/footer.php'; ?>

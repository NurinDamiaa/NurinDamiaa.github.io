<?php

require_once __DIR__ . '/app/bootstrap.php';
require_login();

if (is_admin()) {
    redirect(url('modules/admin/dashboard.php'));
}

$roleId = current_role_id();
$uid = current_user_id();
$stats = [];

if ($roleId === 1) {
    $queries = [
        'mine' => ['SELECT COUNT(*) AS c FROM proposals WHERE applicant_id = ?', 'i', $uid],
        'pending' => ["SELECT COUNT(*) AS c FROM proposals WHERE applicant_id = ? AND status = 'Pending'", 'i', $uid],
        'approved' => ["SELECT COUNT(*) AS c FROM proposals WHERE applicant_id = ? AND status = 'Approved'", 'i', $uid],
        'revision' => ["SELECT COUNT(*) AS c FROM proposals WHERE applicant_id = ? AND status = 'Revision Required'", 'i', $uid],
    ];
    foreach ($queries as $key => [$sql, $type, $param]) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $type, $param);
        mysqli_stmt_execute($stmt);
        $stats[$key] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
        mysqli_stmt_close($stmt);
    }

    $recentStmt = mysqli_prepare($conn, 'SELECT proposal_id, title, status, submitted_date FROM proposals WHERE applicant_id = ? ORDER BY submitted_date DESC LIMIT 5');
    mysqli_stmt_bind_param($recentStmt, 'i', $uid);
    mysqli_stmt_execute($recentStmt);
    $recent = mysqli_fetch_all(mysqli_stmt_get_result($recentStmt), MYSQLI_ASSOC);
    mysqli_stmt_close($recentStmt);
} elseif ($roleId >= 2 && $roleId <= 7) {
    $stage = stage_for_role($roleId);
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM proposals WHERE current_stage=? AND status='Pending'");
    mysqli_stmt_bind_param($stmt, 's', $stage);
    mysqli_stmt_execute($stmt);
    $stats['queue'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM proposal_approvals pa JOIN users u ON pa.reviewer_id = u.user_id WHERE u.user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $stats['reviewed'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stageHistory = get_stage_history($conn, $stage, 15);
}

$pageTitle = 'Dashboard';
require APP_ROOT . '/app/views/layout/header.php';

page_heading('Dashboard', 'Welcome, ' . ($_SESSION['name'] ?? ''));
?>

<?php if ($roleId === 1): ?>
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-gold"><i class="bx bx-file"></i></div>
        <div class="stat-info">
            <label>My Proposals</label>
            <div class="stat-value"><?= e($stats['mine'] ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-gold"><i class="bx bx-time-five"></i></div>
        <div class="stat-info">
            <label>Pending</label>
            <div class="stat-value"><?= e($stats['pending'] ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-sky"><i class="bx bx-check-circle"></i></div>
        <div class="stat-info">
            <label>Approved</label>
            <div class="stat-value"><?= e($stats['approved'] ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-coral"><i class="bx bx-edit"></i></div>
        <div class="stat-info">
            <label>Revision</label>
            <div class="stat-value"><?= e($stats['revision'] ?? 0) ?></div>
        </div>
    </div>
</div>

<?php if (!empty($recent)): ?>
<div class="table-panel">
    <div class="card-header"><h3>Recent Submissions</h3></div>
    <table class="pwms-table pwms-table-static">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Date</th>
                <th class="no-sort"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $row): ?>
            <tr>
                <td><?= e($row['proposal_id']) ?></td>
                <td><strong><?= e($row['title']) ?></strong></td>
                <td><span class="<?= status_badge_class($row['status']) ?>"><?= e($row['status']) ?></span></td>
                <td><?= e(date('d M Y', strtotime($row['submitted_date']))) ?></td>
                <td><a href="<?= e(url('modules/proposals/show.php?id=' . $row['proposal_id'])) ?>" class="btn-secondary btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php elseif ($roleId >= 2 && $roleId <= 7): ?>
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-gold"><i class="bx bx-check-shield"></i></div>
        <div class="stat-info">
            <label>Awaiting Review</label>
            <div class="stat-value"><?= e($stats['queue'] ?? 0) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-sky"><i class="bx bx-list-check"></i></div>
        <div class="stat-info">
            <label>Actions Taken</label>
            <div class="stat-value"><?= e($stats['reviewed'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="table-panel">
    <div class="card-header"><h3><?= e($stage) ?> History</h3></div>
    <p style="padding:0 20px 12px;margin:0;font-size:13px;color:var(--text-muted);">Proposals that have passed through your department (approved, rejected, or returned for revision).</p>
    <table class="pwms-table" data-empty="No history yet.">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Applicant</th>
                <th>Your Action</th>
                <th>Overall Status</th>
                <th>Date</th>
                <th class="no-sort"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stageHistory as $row): ?>
            <tr>
                <td><?= e($row['proposal_id']) ?></td>
                <td><strong><?= e($row['title']) ?></strong></td>
                <td><?= e($row['applicant_name']) ?></td>
                <td><span class="<?= status_badge_class($row['stage_action']) ?>"><?= e($row['stage_action']) ?></span></td>
                <td><span class="<?= status_badge_class($row['status']) ?>"><?= e($row['status']) ?></span></td>
                <td><?= e(date('d M Y', strtotime($row['action_date']))) ?></td>
                <td><a href="<?= e(url('modules/proposals/show.php?id=' . $row['proposal_id'])) ?>" class="btn-secondary btn-sm"><i class="bx bx-show"></i> View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require APP_ROOT . '/app/views/layout/footer.php'; ?>

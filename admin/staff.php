<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Staff Team & Access Control Studio
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Models\StaffUser;
use App\Models\AuditLog;
use App\Core\Security;

// Require owner or admin role
$staff = AuthService::requireRole(['owner', 'admin'], '/admin/login.php');

$msg = '';
$err = '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!Security::verifyCsrfToken($csrf)) {
        $err = 'Security session expired. Please refresh.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'create_staff') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $role = (string)($_POST['role'] ?? 'staff');

            $allowedRoles = ['owner', 'admin', 'manager', 'kitchen', 'staff'];

            if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = 'Please provide a valid staff name and email address.';
            } elseif (strlen($password) < 6) {
                $err = 'Password must be at least 6 characters long.';
            } elseif (!in_array($role, $allowedRoles, true)) {
                $err = 'Invalid staff role selected.';
            } else {
                $existing = StaffUser::findBy('email', $email);
                if ($existing) {
                    $err = "A staff account with email '{$email}' already exists.";
                } else {
                    $newId = StaffUser::createStaff($name, $email, $password, $role);
                    AuditLog::log((int)$staff['id'], 'staff_account_created', 'staff_users', $newId, [
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                    ]);
                    $msg = "New staff member '{$name}' ({$role}) added successfully.";
                }
            }
        } elseif ($action === 'toggle_active') {
            $targetId = (int)($_POST['staff_id'] ?? 0);

            if ($targetId === (int)$staff['id']) {
                $err = 'Security restriction: You cannot deactivate your own active session account.';
            } else {
                $targetStaff = StaffUser::find($targetId);
                if ($targetStaff) {
                    $newActive = ((int)$targetStaff['is_active'] === 1) ? 0 : 1;
                    StaffUser::updateById($targetId, ['is_active' => $newActive]);

                    AuditLog::log((int)$staff['id'], 'staff_status_toggled', 'staff_users', $targetId, [
                        'name' => $targetStaff['name'],
                        'new_status' => $newActive ? 'active' : 'inactive',
                    ]);
                    $msg = "Account for '{$targetStaff['name']}' has been " . ($newActive ? 'activated' : 'deactivated') . ".";
                } else {
                    $err = 'Staff account not found.';
                }
            }
        }
    }
}

// Fetch all staff members
$staffList = StaffUser::all('id ASC');

// Fetch recent audit activity
$db = db();
$auditLogs = $db->fetchAll(
    "SELECT a.*, s.name AS staff_name, s.role AS staff_role
     FROM audit_log a
     LEFT JOIN staff_users s ON a.staff_id = s.id
     ORDER BY a.id DESC
     LIMIT 25"
);

$csrfToken = Security::generateCsrfToken();
$activeNav = 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Team & Access Control | Cielo Cafe Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --bg-surface: #FFFFFF;
      --text-espresso: #1A1412;
      --text-muted: #6E6259;
      --accent-terracotta: #C96A52;
      --accent-terracotta-soft: rgba(201, 106, 82, 0.1);
      --accent-sky: #6B9AC4;
      --accent-sky-soft: rgba(107, 154, 196, 0.12);
      --accent-sky-deep: #4A7A9F;
      --border-warm: rgba(26, 20, 18, 0.08);
      --shadow-card: 0 4px 18px rgba(26, 20, 18, 0.04);
      --radius-sm: 8px;
      --radius-md: 14px;
      --radius-lg: 20px;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      background: #F4F1EA;
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
    }

    /* Left Sidebar */
    .admin-sidebar {
      width: 260px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: #1A1412;
      color: #F7F4EE;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid rgba(247, 244, 238, 0.08);
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
    }

    .sidebar-header {
      padding: 1.75rem 1.5rem 1.25rem;
      border-bottom: 1px solid rgba(247, 244, 238, 0.06);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
      color: #F7F4EE;
    }

    .sidebar-logo-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .sidebar-title {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      display: block;
      line-height: 1;
    }

    .sidebar-sub {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-sky);
      font-weight: 600;
      margin-top: 0.25rem;
      display: block;
    }

    .sidebar-nav-container {
      padding: 1.5rem 1rem;
      flex: 1;
      overflow-y: auto;
    }

    .sidebar-section-label {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      color: rgba(247, 244, 238, 0.4);
      font-weight: 700;
      padding: 0 0.75rem;
      margin-bottom: 0.6rem;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 0.85rem;
      border-radius: 12px;
      text-decoration: none;
      color: rgba(247, 244, 238, 0.75);
      font-size: 0.88rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .sidebar-link:hover {
      color: #F7F4EE;
      background: rgba(247, 244, 238, 0.08);
    }

    .sidebar-link.active {
      color: #FFFFFF;
      background: var(--accent-terracotta);
      box-shadow: 0 4px 15px rgba(201, 106, 82, 0.35);
    }

    .sidebar-link-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .sidebar-badge-pulse {
      background: var(--accent-terracotta);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
      border-radius: 9999px;
    }

    .sidebar-footer {
      padding: 1.25rem;
      border-top: 1px solid rgba(247, 244, 238, 0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
    }

    .staff-card {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      overflow: hidden;
    }

    .staff-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: rgba(201, 106, 82, 0.2);
      color: var(--accent-terracotta);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
      flex-shrink: 0;
    }

    .staff-name {
      font-size: 0.82rem;
      font-weight: 600;
      color: #F7F4EE;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .staff-role-badge {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.65rem;
      color: var(--accent-sky);
      text-transform: capitalize;
    }

    .role-indicator {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #10B981;
    }

    .btn-sidebar-signout {
      background: rgba(247, 244, 238, 0.08);
      color: rgba(247, 244, 238, 0.7);
      border: none;
      border-radius: 8px;
      padding: 0.45rem 0.65rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.75rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-sidebar-signout:hover {
      background: rgba(201, 106, 82, 0.2);
      color: var(--accent-terracotta);
    }

    /* Main Content */
    .admin-main {
      margin-left: 260px;
      flex: 1;
      padding: 2.5rem 3rem 4rem;
      max-width: 1350px;
    }

    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-warm);
      flex-wrap: wrap;
      gap: 1rem;
    }

    .page-title {
      font-family: 'Fraunces', serif;
      font-size: 2.2rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin: 0;
      color: var(--text-espresso);
    }

    .page-sub {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin: 0.35rem 0 0;
    }

    .btn-add-staff {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      border-radius: 9999px;
      padding: 0.65rem 1.35rem;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      transition: all 0.2s ease;
    }
    .btn-add-staff:hover {
      background: #000000;
      transform: translateY(-1px);
    }

    /* Roster Table */
    .panel-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      margin-bottom: 2.5rem;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 0.85rem;
      border-bottom: 1px solid var(--border-warm);
    }

    .panel-title {
      font-family: 'Fraunces', serif;
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0;
      color: var(--text-espresso);
    }

    .custom-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
    }

    .custom-table th {
      text-align: left;
      padding: 0.75rem 1rem;
      font-weight: 700;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      background: var(--bg-cream);
      border-bottom: 1px solid var(--border-warm);
    }

    .custom-table td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid var(--border-warm);
      color: var(--text-espresso);
      vertical-align: middle;
    }

    .custom-table tr:last-child td { border-bottom: none; }

    .role-badge {
      display: inline-block;
      padding: 0.2rem 0.65rem;
      border-radius: 9999px;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    .role-owner { background: rgba(26,20,18,0.1); color: var(--text-espresso); border: 1px solid rgba(26,20,18,0.2); }
    .role-admin { background: rgba(201,106,82,0.15); color: var(--accent-terracotta); border: 1px solid rgba(201,106,82,0.3); }
    .role-manager { background: rgba(107,154,196,0.15); color: var(--accent-sky-deep); border: 1px solid rgba(107,154,196,0.3); }
    .role-kitchen { background: rgba(245,158,11,0.15); color: #B45309; border: 1px solid rgba(245,158,11,0.3); }
    .role-staff { background: rgba(100,116,139,0.12); color: #475569; border: 1px solid rgba(100,116,139,0.25); }

    .status-dot {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.78rem;
      font-weight: 600;
    }
    .status-dot.active { color: #059669; }
    .status-dot.inactive { color: #DC2626; }
    .status-dot::before {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: currentColor;
    }

    .btn-toggle-status {
      background: none;
      border: 1px solid var(--border-warm);
      border-radius: 8px;
      padding: 0.35rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      color: var(--text-espresso);
      transition: all 0.15s ease;
    }
    .btn-toggle-status:hover {
      background: var(--bg-cream);
      border-color: var(--accent-terracotta);
    }

    .alert {
      padding: 1rem 1.25rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
    .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

    /* Modal Form Styles */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(26, 20, 18, 0.65);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
    }

    .modal-box {
      background: #ffffff;
      border-radius: var(--radius-lg);
      max-width: 460px;
      width: 100%;
      padding: 2rem;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .form-group {
      margin-bottom: 1.15rem;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .form-group label {
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--text-espresso);
    }

    .form-group input, .form-group select {
      padding: 0.75rem 0.85rem;
      border: 1px solid var(--border-warm);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9rem;
      outline: none;
      background: var(--bg-cream);
    }
    .form-group input:focus, .form-group select:focus {
      border-color: var(--accent-sky);
      background: #FFFFFF;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="admin-main">

    <div class="header-bar">
      <div>
        <h1 class="page-title">Staff Team & Access Control</h1>
        <p class="page-sub">Manage cafe team members, role privileges, and inspect administrative security audit trails.</p>
      </div>

      <button type="button" onclick="openAddStaffModal()" class="btn-add-staff">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Team Member
      </button>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-success">✔ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
      <div class="alert alert-error">✘ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Staff Roster Table -->
    <div class="panel-card">
      <div class="panel-header">
        <h2 class="panel-title">Active Team Roster</h2>
        <span style="font-size:0.8rem; color:var(--text-muted);"><?= count($staffList) ?> Staff Accounts</span>
      </div>

      <table class="custom-table">
        <thead>
          <tr>
            <th>Member</th>
            <th>Email</th>
            <th>Role</th>
            <th>Account Status</th>
            <th>Joined</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($staffList as $st): ?>
            <?php $isSelf = ((int)$st['id'] === (int)$staff['id']); ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:0.65rem;">
                  <div style="width:34px; height:34px; border-radius:50%; background:var(--bg-cream); color:var(--accent-terracotta); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem;">
                    <?= strtoupper(substr($st['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <strong style="font-size:0.92rem;"><?= htmlspecialchars($st['name']) ?></strong>
                    <?php if ($isSelf): ?>
                      <span style="font-size:0.7rem; color:var(--accent-sky); font-weight:700; margin-left:0.35rem;">(You)</span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="font-family:monospace;"><?= htmlspecialchars($st['email']) ?></td>
              <td>
                <span class="role-badge role-<?= htmlspecialchars($st['role']) ?>">
                  <?= ucfirst(htmlspecialchars($st['role'])) ?>
                </span>
              </td>
              <td>
                <span class="status-dot <?= ((int)$st['is_active'] === 1) ? 'active' : 'inactive' ?>">
                  <?= ((int)$st['is_active'] === 1) ? 'Active' : 'Suspended' ?>
                </span>
              </td>
              <td style="color:var(--text-muted); font-size:0.82rem;">
                <?= date('d M Y', strtotime($st['created_at'])) ?>
              </td>
              <td style="text-align:right;">
                <?php if (!$isSelf): ?>
                  <form method="POST" style="margin:0; display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="staff_id" value="<?= (int)$st['id'] ?>">
                    <button type="submit" class="btn-toggle-status" onclick="return confirm('Change account status for <?= htmlspecialchars(addslashes($st['name']), ENT_QUOTES) ?>?')">
                      <?= ((int)$st['is_active'] === 1) ? 'Deactivate' : 'Reactivate' ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span style="font-size:0.78rem; color:var(--text-muted);">Current User</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Security & Audit Trail Section -->
    <div class="panel-card">
      <div class="panel-header">
        <h2 class="panel-title">Recent Administrative Audit Trail</h2>
        <span style="font-size:0.8rem; color:var(--text-muted);">Real-time Activity Log</span>
      </div>

      <?php if (empty($auditLogs)): ?>
        <div style="padding:2rem; text-align:center; color:var(--text-muted);">
          No audit entries logged yet.
        </div>
      <?php else: ?>
        <table class="custom-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Staff Member</th>
              <th>Action Event</th>
              <th>Entity</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($auditLogs as $log): ?>
              <tr>
                <td style="font-size:0.8rem; color:var(--text-muted);">
                  <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars((string)($log['staff_name'] ?? 'System / Patron')) ?></strong>
                  <?php if (!empty($log['staff_role'])): ?>
                    <span style="font-size:0.7rem; color:var(--accent-sky); font-weight:600;">(<?= htmlspecialchars($log['staff_role']) ?>)</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span style="font-family:monospace; font-size:0.82rem; background:var(--bg-cream); padding:2px 8px; border-radius:6px; color:var(--accent-terracotta); font-weight:700;">
                    <?= htmlspecialchars($log['action']) ?>
                  </span>
                </td>
                <td style="color:var(--text-muted); font-size:0.82rem;">
                  <?= htmlspecialchars((string)$log['entity_type']) ?> #<?= htmlspecialchars((string)$log['entity_id']) ?>
                </td>
                <td style="font-family:monospace; font-size:0.8rem; color:var(--text-muted);">
                  <?= htmlspecialchars((string)$log['ip_address']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </main>

  <!-- ADD STAFF MODAL -->
  <div id="modalAddStaff" class="modal-backdrop">
    <div class="modal-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <h2 style="font-family:'Fraunces', serif; font-size:1.4rem; color:var(--text-espresso); margin:0;">Add Staff Team Member</h2>
        <button type="button" onclick="closeAddStaffModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="create_staff">

        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" placeholder="e.g. Chef Marco / Ravi Patel" required>
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="staff@cielocafebistro.in" required>
        </div>

        <div class="form-group">
          <label>Access Role</label>
          <select name="role" required>
            <option value="staff">Staff / Cashier (POS & Billing)</option>
            <option value="kitchen">Kitchen Staff (KDS Screen Only)</option>
            <option value="manager">Manager (POS, Reviews & Reports)</option>
            <option value="admin">Administrator (Full Access)</option>
            <option value="owner">Owner (Complete Authority)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Initial Password (min 6 chars)</label>
          <input type="password" name="password" minlength="6" placeholder="••••••••" required>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
          <button type="button" onclick="closeAddStaffModal()" style="flex:1; background:rgba(26,20,18,0.06); border:none; border-radius:10px; padding:0.85rem; font-weight:600; cursor:pointer;">Cancel</button>
          <button type="submit" style="flex:1.5; background:var(--accent-terracotta); color:#fff; border:none; border-radius:10px; padding:0.85rem; font-weight:700; cursor:pointer;">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openAddStaffModal() {
    document.getElementById('modalAddStaff').style.display = 'flex';
  }
  function closeAddStaffModal() {
    document.getElementById('modalAddStaff').style.display = 'none';
  }
  </script>

</body>
</html>

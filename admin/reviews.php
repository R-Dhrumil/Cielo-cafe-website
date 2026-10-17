<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Customer Reviews & Moderation Hub
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\ReviewService;
use App\Models\Review;
use App\Core\Security;

// Require staff authorization
$staff = AuthService::requireRole(['owner', 'admin', 'manager'], '/admin/login.php');

$msg = '';
$err = '';

// Handle direct POST moderation action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!Security::verifyCsrfToken($csrf)) {
        $err = 'Security token invalid or expired. Please refresh.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? '');

        if ($action === 'moderate' && $reviewId > 0 && in_array($newStatus, ['approved', 'hidden', 'pending'], true)) {
            try {
                ReviewService::moderateReview($reviewId, $newStatus, (int)$staff['id']);
                $msg = "Review #{$reviewId} status updated to " . ucfirst($newStatus) . " successfully.";
            } catch (\Throwable $e) {
                $err = $e->getMessage();
            }
        }
    }
}

// Current filter
$filter = (string)($_GET['status'] ?? 'all');
$statusFilter = in_array($filter, ['pending', 'approved', 'hidden'], true) ? $filter : null;

$reviews = Review::getAllWithDetails(100, $statusFilter);
$stats = ReviewService::getStats();
$pendingCount = Review::countPending();

$csrfToken = Security::generateCsrfToken();
$activeNav = 'reviews';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Reviews Moderation | Cielo Cafe Admin</title>
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

    /* Left Sidebar Styles */
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

    /* Main Content Area */
    .admin-main {
      margin-left: 260px;
      flex: 1;
      padding: 2.5rem 3rem 4rem;
      max-width: 1300px;
    }

    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-warm);
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

    /* Metric Cards */
    .metric-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2.5rem;
    }

    .metric-card {
      background: var(--bg-surface);
      border-radius: var(--radius-md);
      padding: 1.5rem;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
    }

    .metric-label {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
      margin-bottom: 0.5rem;
      display: block;
    }

    .metric-value {
      font-family: 'Fraunces', serif;
      font-size: 2.1rem;
      font-weight: 700;
      color: var(--text-espresso);
      line-height: 1;
      display: flex;
      align-items: baseline;
      gap: 0.35rem;
    }

    /* Filters Bar */
    .filters-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .filter-tabs {
      display: flex;
      background: rgba(26, 20, 18, 0.04);
      padding: 0.35rem;
      border-radius: 9999px;
      gap: 0.35rem;
    }

    .filter-tab {
      padding: 0.45rem 1.15rem;
      border-radius: 9999px;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted);
      text-decoration: none;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .filter-tab.active {
      background: #FFFFFF;
      color: var(--text-espresso);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .badge-count {
      font-size: 0.72rem;
      padding: 0.1rem 0.45rem;
      border-radius: 9999px;
      background: rgba(26, 20, 18, 0.08);
      font-weight: 700;
    }

    .filter-tab.active .badge-count {
      background: var(--accent-terracotta);
      color: #fff;
    }

    /* Review Cards */
    .reviews-list {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .review-item {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      padding: 1.75rem;
      transition: all 0.2s ease;
    }

    .review-item:hover {
      border-color: rgba(107, 154, 196, 0.3);
    }

    .review-item-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .reviewer-meta {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .reviewer-avatar {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: var(--bg-cream);
      color: var(--accent-terracotta);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.1rem;
      font-family: 'Fraunces', serif;
    }

    .reviewer-name {
      font-weight: 700;
      font-size: 1.05rem;
      color: var(--text-espresso);
      margin: 0;
    }

    .reviewer-contact {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 0.15rem;
    }

    .stars-display {
      display: flex;
      align-items: center;
      gap: 0.2rem;
      color: #F59E0B;
    }

    .star-icon {
      width: 18px;
      height: 18px;
      fill: currentColor;
    }

    .star-icon.empty {
      fill: #E5E7EB;
    }

    .order-pill {
      font-size: 0.78rem;
      background: var(--bg-cream);
      padding: 0.35rem 0.75rem;
      border-radius: 8px;
      border: 1px solid var(--border-warm);
      color: var(--text-espresso);
      font-family: monospace;
      font-weight: 600;
    }

    .review-content {
      font-size: 0.95rem;
      line-height: 1.6;
      color: var(--text-espresso);
      margin-bottom: 1.25rem;
      background: #FAFAFA;
      padding: 1.25rem 1.5rem;
      border-radius: 12px;
      border-left: 4px solid var(--accent-terracotta);
    }

    .dish-tag {
      display: inline-block;
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--accent-sky-deep);
      background: var(--accent-sky-soft);
      padding: 0.25rem 0.65rem;
      border-radius: 6px;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
    }

    .status-badge.approved {
      background: rgba(16, 185, 129, 0.12);
      color: #059669;
    }

    .status-badge.pending {
      background: rgba(245, 158, 11, 0.15);
      color: #D97706;
    }

    .status-badge.hidden {
      background: rgba(239, 68, 68, 0.12);
      color: #DC2626;
    }

    .review-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--border-warm);
      padding-top: 1rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .action-group {
      display: flex;
      gap: 0.5rem;
    }

    .btn-action {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .btn-approve {
      background: #10B981;
      color: #ffffff;
    }
    .btn-approve:hover { background: #059669; }

    .btn-hide {
      background: rgba(239, 68, 68, 0.1);
      color: #DC2626;
      border: 1px solid rgba(239, 68, 68, 0.25);
    }
    .btn-hide:hover { background: #DC2626; color: #fff; }

    .btn-restore {
      background: rgba(26, 20, 18, 0.06);
      color: var(--text-espresso);
    }
    .btn-restore:hover { background: rgba(26, 20, 18, 0.12); }

    .alert {
      padding: 1rem 1.25rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
    .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="admin-main">
    
    <div class="header-bar">
      <div>
        <h1 class="page-title">Verified Customer Reviews</h1>
        <p class="page-sub">Patron dining feedback, 5-star ratings, and live social proof moderation.</p>
      </div>
      <div>
        <a href="/index.php#reviews" target="_blank" class="btn-action btn-restore" style="text-decoration: none; padding: 0.65rem 1.25rem;">
          View Live Website Showcase ↗
        </a>
      </div>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-success">✔ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
      <div class="alert alert-error">✘ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="metric-grid">
      <div class="metric-card">
        <span class="metric-label">Approved Reviews</span>
        <div class="metric-value">
          <?= number_format($stats['total_approved']) ?>
        </div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Average Star Score</span>
        <div class="metric-value">
          <?= number_format($stats['average_rating'], 1) ?> <span style="font-size: 1rem; color: #F59E0B;">★</span>
        </div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Pending Moderation</span>
        <div class="metric-value" style="color: <?= $pendingCount > 0 ? 'var(--accent-terracotta)' : 'var(--text-espresso)' ?>;">
          <?= number_format($pendingCount) ?>
        </div>
      </div>
      <div class="metric-card">
        <span class="metric-label">5-Star Love</span>
        <div class="metric-value">
          <?= number_format($stats['distribution'][5] ?? 0) ?>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filters-bar">
      <div class="filter-tabs">
        <a href="?status=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
          All Reviews
          <span class="badge-count"><?= count($reviews) ?></span>
        </a>
        <a href="?status=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">
          Pending Review
          <?php if ($pendingCount > 0): ?>
            <span class="badge-count" style="background: var(--accent-terracotta); color: #fff;"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>
        <a href="?status=approved" class="filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">
          Approved (Live)
        </a>
        <a href="?status=hidden" class="filter-tab <?= $filter === 'hidden' ? 'active' : '' ?>">
          Hidden / Archived
        </a>
      </div>
    </div>

    <!-- Reviews Listing -->
    <?php if (empty($reviews)): ?>
      <div style="background: #fff; border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center; border: 1px dashed var(--border-warm); color: var(--text-muted);">
        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">★</div>
        <h3 style="font-size: 1.15rem; color: var(--text-espresso); margin: 0 0 0.5rem;">No reviews found in this view</h3>
        <p style="font-size: 0.88rem; margin: 0;">Verified reviews will appear here once patrons complete dining orders.</p>
      </div>
    <?php else: ?>
      <div class="reviews-list">
        <?php foreach ($reviews as $rev): ?>
          <div class="review-item">
            <div class="review-item-header">
              <div class="reviewer-meta">
                <div class="reviewer-avatar">
                  <?= strtoupper(substr($rev['user_name'] ?: 'P', 0, 1)) ?>
                </div>
                <div>
                  <h3 class="reviewer-name"><?= htmlspecialchars($rev['user_name'] ?: 'Patron') ?></h3>
                  <div class="reviewer-contact">
                    <span>📱 +91 <?= htmlspecialchars((string)$rev['user_phone']) ?></span>
                    <?php if (!empty($rev['user_email'])): ?>
                      <span> • <?= htmlspecialchars((string)$rev['user_email']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="stars-display">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <svg class="star-icon <?= $s <= (int)$rev['rating'] ? '' : 'empty' ?>" viewBox="0 0 24 24">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                  <?php endfor; ?>
                </div>
                
                <span class="status-badge <?= htmlspecialchars($rev['status']) ?>">
                  <?= ucfirst(htmlspecialchars($rev['status'])) ?>
                </span>
              </div>
            </div>

            <!-- Review Body -->
            <div class="review-content">
              &ldquo;<?= nl2br(htmlspecialchars((string)$rev['review_text'])) ?>&rdquo;
            </div>

            <?php if (!empty($rev['dishes_ordered'])): ?>
              <div style="margin-bottom: 1rem;">
                <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 0.5rem;">Ordered Dishes:</span>
                <?php foreach (explode(',', (string)$rev['dishes_ordered']) as $dish): ?>
                  <span class="dish-tag"><?= htmlspecialchars(trim($dish)) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Footer: Order Info & Actions -->
            <div class="review-footer">
              <div style="font-size: 0.82rem; color: var(--text-muted);">
                <span class="order-pill">Order <?= htmlspecialchars((string)$rev['order_number']) ?></span>
                <span style="margin-left: 0.5rem;">Reviewed on <?= date('d M Y, h:i A', strtotime($rev['created_at'])) ?></span>
              </div>

              <form method="POST" style="margin: 0;" class="action-group">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="moderate">
                <input type="hidden" name="review_id" value="<?= (int)$rev['id'] ?>">

                <?php if ($rev['status'] !== 'approved'): ?>
                  <button type="submit" name="new_status" value="approved" class="btn-action btn-approve">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Approve (Make Live)
                  </button>
                <?php endif; ?>

                <?php if ($rev['status'] !== 'hidden'): ?>
                  <button type="submit" name="new_status" value="hidden" class="btn-action btn-hide">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    Hide Review
                  </button>
                <?php endif; ?>

                <?php if ($rev['status'] === 'hidden'): ?>
                  <button type="submit" name="new_status" value="pending" class="btn-action btn-restore">
                    Move to Pending
                  </button>
                <?php endif; ?>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>
</body>
</html>

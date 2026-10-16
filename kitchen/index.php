<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Kitchen Display System (KDS)
 * Responsive live kitchen order terminal for tablet & touchscreen stations.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;

// Ensure kitchen or admin user
$staff = AuthService::requireRole(['kitchen', 'admin', 'owner'], '/admin/login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kitchen Display System (KDS) | Cielo Cafe</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --kds-bg: #12100E;
      --kds-card: #1E1A17;
      --kds-card-border: rgba(255, 255, 255, 0.1);
      --kds-text: #F7F4EE;
      --kds-muted: #9E948B;
      --kds-yellow: #F59E0B;
      --kds-blue: #38BDF8;
      --kds-green: #22C55E;
      --kds-red: #EF4444;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 0;
      background: var(--kds-bg);
      color: var(--kds-text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
    }
    .kds-header {
      background: #181412;
      border-bottom: 1px solid var(--kds-card-border);
      padding: 0.85rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .kds-brand {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .kds-logo {
      font-family: 'Fraunces', serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #FFFFFF;
      letter-spacing: -0.02em;
    }
    .kds-tag {
      background: var(--kds-yellow);
      color: #000;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 0.2rem 0.6rem;
      border-radius: 999px;
      text-transform: uppercase;
    }
    .kds-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .ticket-count-badge {
      font-size: 0.85rem;
      color: var(--kds-muted);
    }
    .btn-logout {
      background: rgba(255, 255, 255, 0.08);
      color: var(--kds-text);
      border: 1px solid var(--kds-card-border);
      padding: 0.45rem 0.85rem;
      border-radius: 8px;
      font-size: 0.8rem;
      text-decoration: none;
      font-weight: 600;
    }
    .kds-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.25rem;
      padding: 1.5rem;
      max-width: 1800px;
      margin: 0 auto;
    }
    .ticket-card {
      background: var(--kds-card);
      border: 2px solid var(--kds-card-border);
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
      transition: transform 0.15s ease;
    }
    .ticket-card.status-placed {
      border-color: var(--kds-yellow);
    }
    .ticket-card.status-preparing {
      border-color: var(--kds-blue);
    }
    .ticket-card.status-ready {
      border-color: var(--kds-green);
    }
    .ticket-header {
      padding: 1rem 1.25rem;
      background: rgba(255, 255, 255, 0.03);
      border-bottom: 1px solid var(--kds-card-border);
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    .table-callout {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      color: #FFFFFF;
      margin: 0;
    }
    .table-floor {
      font-size: 0.75rem;
      color: var(--kds-muted);
    }
    .ticket-meta {
      text-align: right;
    }
    .timer-badge {
      font-size: 0.85rem;
      font-weight: 800;
      padding: 0.2rem 0.5rem;
      border-radius: 6px;
      display: inline-block;
    }
    .timer-normal { background: rgba(34, 197, 94, 0.2); color: var(--kds-green); }
    .timer-warning { background: rgba(245, 158, 11, 0.2); color: var(--kds-yellow); }
    .timer-alert { background: rgba(239, 68, 68, 0.2); color: var(--kds-red); }

    .ticket-body {
      padding: 1.25rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .ticket-item {
      border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
      padding-bottom: 0.65rem;
    }
    .ticket-item:last-child {
      border-bottom: none;
    }
    .item-main {
      display: flex;
      align-items: baseline;
      gap: 0.5rem;
      font-size: 1.1rem;
      font-weight: 700;
      color: #FFFFFF;
    }
    .item-qty {
      background: rgba(255, 255, 255, 0.15);
      color: #FFFFFF;
      padding: 0.1rem 0.45rem;
      border-radius: 6px;
      font-size: 0.85rem;
    }
    .item-addon-list {
      font-size: 0.85rem;
      color: var(--kds-yellow);
      margin: 0.2rem 0 0 1.75rem;
    }
    .item-note-text {
      font-size: 0.85rem;
      color: var(--kds-blue);
      font-style: italic;
      margin: 0.2rem 0 0 1.75rem;
    }
    .kitchen-note-box {
      background: rgba(245, 158, 11, 0.1);
      border: 1px solid rgba(245, 158, 11, 0.3);
      color: var(--kds-yellow);
      padding: 0.65rem 0.85rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-top: 0.5rem;
    }
    .ticket-footer {
      padding: 1rem 1.25rem;
      background: rgba(255, 255, 255, 0.02);
      border-top: 1px solid var(--kds-card-border);
      display: flex;
      gap: 0.5rem;
    }
    .btn-action {
      flex: 1;
      padding: 0.75rem;
      border-radius: 10px;
      font-size: 0.9rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      transition: opacity 0.15s ease;
    }
    .btn-action:hover { opacity: 0.9; }
    .btn-cook { background: var(--kds-yellow); color: #000; }
    .btn-ready { background: var(--kds-blue); color: #000; }
    .btn-done { background: var(--kds-green); color: #000; }
    .btn-kot {
      background: rgba(255, 255, 255, 0.1);
      color: var(--kds-text);
      padding: 0.75rem;
      border-radius: 10px;
      border: 1px solid var(--kds-card-border);
      cursor: pointer;
    }
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 6rem 1rem;
      color: var(--kds-muted);
    }
  </style>
</head>
<body>

  <header class="kds-header">
    <div class="kds-brand">
      <div class="kds-logo">CIELO KDS</div>
      <span class="kds-tag">Kitchen Station</span>
      <span id="ticketCountBadge" class="ticket-count-badge">0 Active Orders</span>
    </div>

    <div class="kds-controls">
      <span style="font-size: 0.85rem; color: var(--kds-muted);">
        Signed in as: <strong><?= e($staff['name']) ?></strong>
      </span>
      <a href="/admin/logout.php" class="btn-logout">Exit KDS</a>
    </div>
  </header>

  <main class="kds-grid" id="kdsTicketsGrid">
    <div class="empty-state">
      <h2 style="font-family: 'Fraunces', serif; color: #FFF; margin-bottom: 0.5rem;">All Orders Cleared!</h2>
      <p>Listening for new customer table orders...</p>
    </div>
  </main>

  <script>
    let knownOrders = new Set();

    async function pollKitchenOrders() {
      try {
        const res = await fetch('/api/kitchen_orders.php');
        const data = await res.json();

        if (data.success) {
          renderTickets(data.data.tickets);
          document.getElementById('ticketCountBadge').textContent = `${data.data.ticket_count} Active Orders`;
        }
      } catch (e) {
        console.warn('KDS Polling error:', e);
      }
    }

    function renderTickets(tickets) {
      const grid = document.getElementById('kdsTicketsGrid');

      if (!tickets || tickets.length === 0) {
        grid.innerHTML = `
          <div class="empty-state">
            <h2 style="font-family: 'Fraunces', serif; color: #FFF; margin-bottom: 0.5rem;">All Orders Cleared!</h2>
            <p>Listening for new customer table orders in real-time...</p>
          </div>
        `;
        return;
      }

      grid.innerHTML = tickets.map(t => {
        let timerClass = 'timer-normal';
        if (t.elapsed_minutes >= 15) timerClass = 'timer-alert';
        else if (t.elapsed_minutes >= 10) timerClass = 'timer-warning';

        let actionBtn = '';
        if (t.status === 'placed') {
          actionBtn = `<button class="btn-action btn-cook" onclick="updateOrderStatus(${t.id}, 'prepare')">Start Cooking</button>`;
        } else if (t.status === 'preparing') {
          actionBtn = `<button class="btn-action btn-ready" onclick="updateOrderStatus(${t.id}, 'ready')">Mark Ready</button>`;
        } else if (t.status === 'ready') {
          actionBtn = `<button class="btn-action btn-done" onclick="updateOrderStatus(${t.id}, 'complete')">Served & Done</button>`;
        }

        const itemsHtml = t.items.map(it => `
          <div class="ticket-item">
            <div class="item-main">
              <span class="item-qty">${it.quantity}x</span>
              <span>${it.name}</span>
            </div>
            ${it.addons && it.addons.length > 0 ? `<div class="item-addon-list">${it.addons.map(a => '+ ' + a.name).join(', ')}</div>` : ''}
            ${it.note ? `<div class="item-note-text">"${it.note}"</div>` : ''}
          </div>
        `).join('');

        return `
          <article class="ticket-card status-${t.status}">
            <div class="ticket-header">
              <div>
                <h3 class="table-callout">TABLE ${t.table_number}</h3>
                <div class="table-floor">${t.floor} · ${t.order_number}</div>
              </div>
              <div class="ticket-meta">
                <span class="timer-badge ${timerClass}">${t.elapsed_minutes}m ago</span>
                <div style="font-size:0.75rem; color:var(--kds-muted); margin-top:0.2rem;">${t.created_time}</div>
              </div>
            </div>

            <div class="ticket-body">
              ${itemsHtml}
              ${t.kitchen_notes ? `<div class="kitchen-note-box"><strong>Note:</strong> ${t.kitchen_notes}</div>` : ''}
            </div>

            <div class="ticket-footer">
              ${actionBtn}
              <button class="btn-kot" title="Print KOT Ticket" onclick="printKOT(${t.id})">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
              </button>
            </div>
          </article>
        `;
      }).join('');
    }

    async function updateOrderStatus(orderId, action) {
      try {
        const res = await fetch('/api/kitchen_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order_id: orderId, action })
        });
        const data = await res.json();
        if (data.success) {
          pollKitchenOrders();
        }
      } catch (e) {
        console.error('Failed to update order status:', e);
      }
    }

    function printKOT(orderId) {
      window.open(`/kitchen/print_kot.php?order_id=${orderId}&autoprint=1`, 'kot_popup', 'width=380,height=600');
    }

    // Poll every 5 seconds
    pollKitchenOrders();
    setInterval(pollKitchenOrders, 5000);
  </script>

</body>
</html>

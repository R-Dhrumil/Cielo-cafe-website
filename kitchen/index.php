<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Kitchen Display System (KDS)
 * Next-Generation, Touch-Optimized Kitchen Order Terminal
 * 
 * Features:
 * - Dual View: Kanban Station Columns & Sequential FIFO Grid
 * - Real-time Live Elapsed Timers (Fresh, In-Progress, Overdue alerts)
 * - Interactive Tap-to-Check Line Items (Strikethrough checklist)
 * - Consolidated "All-Day" Prep Summary Bar (Item totals across all active orders)
 * - 1-Click Recall / Undo Drawer for accidentally bumped orders
 * - Audio Chime alert on new incoming orders (HTML5 Web Audio API)
 * - Text Size Scaling (Normal, Large, Huge for wall tablets/monitors)
 * - Fullscreen Kiosk Mode & Instant Table/Order Search
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;

// Ensure kitchen, admin or owner role
$staff = AuthService::requireRole(['kitchen', 'admin', 'owner'], '/admin/login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Kitchen Display System (KDS) | Cielo Cafe & Restro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --kds-bg: #0B0E14;
      --kds-surface: #151921;
      --kds-surface-elevated: #1C222D;
      --kds-border: rgba(255, 255, 255, 0.1);
      --kds-border-light: rgba(255, 255, 255, 0.16);
      --kds-text: #F8FAFC;
      --kds-text-dim: #94A3B8;
      --kds-text-muted: #64748B;
      
      --color-new: #F59E0B;
      --color-new-bg: rgba(245, 158, 11, 0.15);
      --color-new-border: #D97706;
      
      --color-cooking: #38BDF8;
      --color-cooking-bg: rgba(56, 189, 248, 0.15);
      --color-cooking-border: #0284C7;
      
      --color-ready: #10B981;
      --color-ready-bg: rgba(16, 185, 129, 0.15);
      --color-ready-border: #059669;
      
      --color-urgent: #EF4444;
      --color-urgent-bg: rgba(239, 68, 68, 0.2);
      
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      
      /* Dynamic Text Scale */
      --kds-font-scale: 1;
    }

    /* Scaling Classes */
    body.scale-large { --kds-font-scale: 1.15; }
    body.scale-huge  { --kds-font-scale: 1.30; }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-tap-highlight-color: transparent;
      user-select: none;
    }

    body {
      background: var(--kds-bg);
      color: var(--kds-text);
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      font-size: calc(15px * var(--kds-font-scale));
      line-height: 1.4;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    /* Top Main App Bar */
    .kds-topbar {
      background: #11151D;
      border-bottom: 1px solid var(--kds-border);
      padding: 0.65rem 1.25rem;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    }

    .kds-brand-group {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .kds-title {
      font-size: 1.35rem;
      font-weight: 900;
      letter-spacing: -0.02em;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .live-dot {
      width: 10px;
      height: 10px;
      background: var(--color-ready);
      border-radius: 50%;
      display: inline-block;
      box-shadow: 0 0 10px var(--color-ready);
      animation: pulseLive 2s infinite ease-in-out;
    }

    @keyframes pulseLive {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }

    .kds-station-badge {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid var(--kds-border-light);
      padding: 0.25rem 0.65rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--kds-text-dim);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .kds-clock {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.95rem;
      font-weight: 700;
      color: #FFFFFF;
      background: rgba(0,0,0,0.3);
      padding: 0.25rem 0.6rem;
      border-radius: var(--radius-sm);
      border: 1px solid rgba(255,255,255,0.06);
    }

    /* Topbar Controls */
    .kds-toolbar {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .btn-pill {
      background: var(--kds-surface-elevated);
      color: var(--kds-text);
      border: 1px solid var(--kds-border);
      padding: 0.45rem 0.85rem;
      border-radius: var(--radius-sm);
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      transition: all 0.15s ease;
      text-decoration: none;
    }

    .btn-pill:hover, .btn-pill:focus-visible {
      background: rgba(255, 255, 255, 0.12);
      border-color: var(--kds-border-light);
    }

    .btn-pill.active {
      background: #FFFFFF;
      color: #000000;
      border-color: #FFFFFF;
    }

    .search-input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-input {
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid var(--kds-border);
      color: #FFFFFF;
      padding: 0.45rem 0.75rem 0.45rem 2rem;
      border-radius: var(--radius-sm);
      font-size: 0.82rem;
      font-family: inherit;
      width: 150px;
      transition: width 0.2s ease, border-color 0.2s ease;
    }

    .search-input:focus {
      outline: none;
      width: 200px;
      border-color: var(--color-cooking);
    }

    .search-icon {
      position: absolute;
      left: 0.65rem;
      color: var(--kds-text-muted);
      pointer-events: none;
    }

    /* Prep Summary Bar (All-Day Items) */
    .prep-summary-bar {
      background: #141820;
      border-bottom: 1px solid var(--kds-border);
      padding: 0.5rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      overflow-x: auto;
      scrollbar-width: thin;
      scrollbar-color: var(--kds-border) transparent;
    }

    .prep-summary-title {
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--kds-text-dim);
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .prep-chips-list {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex: 1;
      overflow-x: auto;
      padding: 0.2rem 0;
    }

    .prep-chip {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--kds-border);
      color: var(--kds-text);
      padding: 0.25rem 0.65rem;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 700;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 0.4rem;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .prep-chip:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: var(--kds-border-light);
    }

    .prep-chip.filter-active {
      background: var(--color-new);
      color: #000000;
      border-color: var(--color-new);
    }

    .prep-chip-qty {
      background: rgba(255, 255, 255, 0.15);
      padding: 0.1rem 0.45rem;
      border-radius: 999px;
      font-size: 0.75rem;
    }

    .prep-chip.filter-active .prep-chip-qty {
      background: #000000;
      color: #FFFFFF;
    }

    /* Filter / Status Tabs Bar (in Flow Grid view) */
    .status-filter-bar {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.85rem 1.25rem 0;
      max-width: 1800px;
      margin: 0 auto;
      width: 100%;
    }

    .filter-tab {
      background: transparent;
      color: var(--kds-text-dim);
      border: 1px solid var(--kds-border);
      padding: 0.5rem 1rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.15s ease;
    }

    .filter-tab:hover {
      color: #FFFFFF;
      background: rgba(255, 255, 255, 0.05);
    }

    .filter-tab.active {
      background: var(--kds-surface-elevated);
      color: #FFFFFF;
      border-color: var(--kds-border-light);
    }

    .filter-badge {
      background: rgba(255, 255, 255, 0.12);
      padding: 0.15rem 0.5rem;
      border-radius: 999px;
      font-size: 0.72rem;
    }

    /* Main Container Views */
    .kds-viewport {
      flex: 1;
      padding: 1.25rem;
      max-width: 1900px;
      margin: 0 auto;
      width: 100%;
    }

    /* KANBAN COLUMNS VIEW */
    .kanban-board {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
      align-items: flex-start;
      min-height: calc(100vh - 160px);
    }

    .kanban-col {
      background: rgba(21, 25, 33, 0.6);
      border: 1px solid var(--kds-border);
      border-radius: var(--radius-lg);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-height: 400px;
    }

    .kanban-col-header {
      padding: 0.85rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 2px solid;
      background: #171C26;
    }

    .kanban-col-header.col-new {
      border-color: var(--color-new);
      color: var(--color-new);
    }

    .kanban-col-header.col-cooking {
      border-color: var(--color-cooking);
      color: var(--color-cooking);
    }

    .kanban-col-header.col-ready {
      border-color: var(--color-ready);
      color: var(--color-ready);
    }

    .kanban-col-title {
      font-size: 0.95rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .kanban-col-count {
      background: rgba(255, 255, 255, 0.12);
      color: #FFFFFF;
      padding: 0.2rem 0.6rem;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 800;
    }

    .kanban-col-body {
      padding: 1rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      min-height: 250px;
    }

    /* GRID FLOW VIEW */
    .grid-board {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 1.25rem;
      align-items: start;
    }

    /* TICKET CARD DESIGN */
    .ticket-card {
      background: var(--kds-surface);
      border: 2px solid var(--kds-border);
      border-radius: var(--radius-lg);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
      transition: transform 0.15s ease, border-color 0.2s ease, box-shadow 0.2s ease;
      position: relative;
    }

    .ticket-card:hover {
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
    }

    /* Card Status Border Accents */
    .ticket-card.status-placed {
      border-color: var(--color-new-border);
    }
    .ticket-card.status-preparing {
      border-color: var(--color-cooking-border);
    }
    .ticket-card.status-ready {
      border-color: var(--color-ready-border);
    }
    .ticket-card.is-overdue {
      border-color: var(--color-urgent) !important;
      animation: pulseAlert 2.5s infinite ease-in-out;
    }

    @keyframes pulseAlert {
      0%, 100% { box-shadow: 0 0 15px rgba(239, 68, 68, 0.3); }
      50% { box-shadow: 0 0 25px rgba(239, 68, 68, 0.65); }
    }

    /* Ticket Header */
    .ticket-header {
      padding: 0.85rem 1rem;
      background: #1C222D;
      border-bottom: 1px solid var(--kds-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
    }

    .ticket-table-info {
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .ticket-table-num {
      font-size: 1.45rem;
      font-weight: 900;
      color: #FFFFFF;
      letter-spacing: -0.02em;
      line-height: 1.1;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .ticket-meta-sub {
      font-size: 0.75rem;
      color: var(--kds-text-dim);
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-weight: 600;
    }

    .ticket-timer-wrap {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.2rem;
    }

    .live-timer-pill {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.92rem;
      font-weight: 800;
      padding: 0.25rem 0.6rem;
      border-radius: var(--radius-sm);
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      white-space: nowrap;
    }

    .timer-fresh {
      background: var(--color-ready-bg);
      color: var(--color-ready);
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .timer-warn {
      background: var(--color-new-bg);
      color: var(--color-new);
      border: 1px solid rgba(245, 158, 11, 0.4);
    }
    .timer-overdue {
      background: var(--color-urgent-bg);
      color: var(--color-urgent);
      border: 1px solid rgba(239, 68, 68, 0.5);
      animation: blinkTimer 1s infinite alternate;
    }

    @keyframes blinkTimer {
      from { opacity: 1; }
      to { opacity: 0.7; }
    }

    /* Ticket Body / Items */
    .ticket-body {
      padding: 1rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.65rem;
    }

    /* Interactive Item Checklist Target */
    .ticket-item-row {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: var(--radius-md);
      padding: 0.65rem 0.75rem;
      display: flex;
      align-items: flex-start;
      gap: 0.65rem;
      cursor: pointer;
      transition: background 0.15s ease, opacity 0.15s ease;
      touch-action: manipulation;
    }

    .ticket-item-row:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .ticket-item-row.is-done {
      background: rgba(16, 185, 129, 0.06);
      border-color: rgba(16, 185, 129, 0.2);
      opacity: 0.55;
    }

    .item-check-box {
      width: 22px;
      height: 22px;
      min-width: 22px;
      border-radius: 6px;
      border: 2px solid var(--kds-border-light);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 2px;
      transition: all 0.15s ease;
      background: rgba(0, 0, 0, 0.2);
    }

    .ticket-item-row.is-done .item-check-box {
      background: var(--color-ready);
      border-color: var(--color-ready);
      color: #000000;
    }

    .item-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }

    .item-headline {
      display: flex;
      align-items: baseline;
      gap: 0.5rem;
      font-size: 1.05rem;
      font-weight: 800;
      color: #FFFFFF;
      line-height: 1.25;
    }

    .ticket-item-row.is-done .item-headline {
      text-decoration: line-through;
      color: var(--kds-text-dim);
    }

    .item-qty-badge {
      background: #FFFFFF;
      color: #000000;
      padding: 0.15rem 0.5rem;
      border-radius: 6px;
      font-size: 0.88rem;
      font-weight: 900;
      line-height: 1;
    }

    .ticket-item-row.is-done .item-qty-badge {
      background: rgba(255, 255, 255, 0.2);
      color: var(--kds-text-dim);
    }

    .item-addon-tag {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--color-new);
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .item-prep-note {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--color-cooking);
      font-style: italic;
      background: rgba(56, 189, 248, 0.08);
      padding: 0.2rem 0.45rem;
      border-radius: 4px;
      display: inline-block;
      margin-top: 0.15rem;
    }

    /* Chef Order Warning Box */
    .chef-note-alert {
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #FBBF24;
      padding: 0.65rem 0.85rem;
      border-radius: var(--radius-md);
      font-size: 0.85rem;
      font-weight: 700;
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      margin-top: 0.25rem;
    }

    /* Ticket Footer & Action Buttons */
    .ticket-footer {
      padding: 0.85rem 1rem;
      background: #141820;
      border-top: 1px solid var(--kds-border);
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }

    /* Big Tactile Action Buttons */
    .btn-bump {
      flex: 1;
      min-height: 52px;
      padding: 0.65rem 1rem;
      border-radius: var(--radius-md);
      font-size: 0.98rem;
      font-weight: 900;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      transition: transform 0.1s ease, filter 0.15s ease;
      touch-action: manipulation;
    }

    .btn-bump:active {
      transform: scale(0.97);
    }

    .btn-bump.action-cook {
      background: var(--color-new);
      color: #000000;
      box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }
    .btn-bump.action-ready {
      background: var(--color-cooking);
      color: #000000;
      box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3);
    }
    .btn-bump.action-done {
      background: var(--color-ready);
      color: #000000;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .btn-kot-icon {
      width: 48px;
      height: 48px;
      min-width: 48px;
      background: var(--kds-surface-elevated);
      color: var(--kds-text);
      border: 1px solid var(--kds-border-light);
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .btn-kot-icon:hover {
      background: rgba(255, 255, 255, 0.15);
      color: #FFFFFF;
    }

    /* Empty State */
    .empty-state-card {
      grid-column: 1 / -1;
      text-align: center;
      padding: 5rem 1.5rem;
      background: var(--kds-surface);
      border: 2px dashed var(--kds-border);
      border-radius: var(--radius-lg);
      margin: 1.5rem 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.85rem;
    }

    .empty-state-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: rgba(16, 185, 129, 0.15);
      color: var(--color-ready);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* SLIDE-OVER RECALL / RECENT ORDERS DRAWER */
    .recall-drawer-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(4px);
      z-index: 1000;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
    }

    .recall-drawer-overlay.is-open {
      opacity: 1;
      pointer-events: auto;
    }

    .recall-drawer {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: 100%;
      max-width: 460px;
      background: #13171F;
      border-left: 1px solid var(--kds-border-light);
      z-index: 1001;
      display: flex;
      flex-direction: column;
      transform: translateX(100%);
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
    }

    .recall-drawer.is-open {
      transform: translateX(0);
    }

    .recall-drawer-header {
      padding: 1.25rem;
      background: #171D27;
      border-bottom: 1px solid var(--kds-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .recall-drawer-body {
      padding: 1.25rem;
      overflow-y: auto;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .recall-card {
      background: var(--kds-surface);
      border: 1px solid var(--kds-border);
      border-radius: var(--radius-md);
      padding: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.65rem;
    }

    .recall-card-header {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
    }

    .btn-restore {
      background: rgba(56, 189, 248, 0.15);
      border: 1px solid var(--color-cooking);
      color: var(--color-cooking);
      padding: 0.5rem 0.85rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      transition: all 0.15s ease;
    }

    .btn-restore:hover {
      background: var(--color-cooking);
      color: #000000;
    }

    /* Sound Alert Unlock Banner */
    .sound-unlock-banner {
      background: #1E293B;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 0.5rem 1.25rem;
      font-size: 0.82rem;
      color: #CBD5E1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
    }

    /* Responsive Queries */
    @media (max-width: 1024px) {
      .kanban-board {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
      .kanban-col {
        min-height: auto;
      }
    }

    @media (max-width: 768px) {
      .kds-topbar {
        padding: 0.65rem 0.85rem;
      }
      .kds-viewport {
        padding: 0.75rem;
      }
      .search-input {
        width: 110px;
      }
      .search-input:focus {
        width: 150px;
      }
    }
  </style>
</head>
<body class="scale-normal">

  <!-- Optional Sound Enable Notice for strict browser autoplay policies -->
  <div id="soundUnlockNotice" class="sound-unlock-banner" style="display: none;" onclick="unlockAudioContext()">
    <span>🔔 <strong>Tap anywhere</strong> to enable audio chimes for new incoming orders</span>
    <span style="font-weight: 800;">[ Dismiss ]</span>
  </div>

  <!-- Top App Bar -->
  <header class="kds-topbar">
    <div class="kds-brand-group">
      <div class="kds-title">
        <span class="live-dot" title="KDS Connected & Live Polling"></span>
        <span>CIELO KDS</span>
      </div>
      <span class="kds-station-badge">Kitchen Station</span>
      <div id="kdsClock" class="kds-clock">--:--:--</div>
    </div>

    <!-- Center/Right Toolbar -->
    <div class="kds-toolbar">
      <!-- Search filter -->
      <div class="search-input-wrap">
        <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" id="kdsSearchInput" class="search-input" placeholder="Table or Order #" oninput="onSearchChange(this.value)">
      </div>

      <!-- View Mode Toggle: Kanban vs Grid -->
      <button type="button" id="btnViewToggle" class="btn-pill" onclick="toggleViewMode()" title="Toggle Kanban Columns or Sequential Flow Grid">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>
        <span id="viewModeLabel">Kanban View</span>
      </button>

      <!-- Font Zoom Scaler -->
      <button type="button" id="btnZoomToggle" class="btn-pill" onclick="cycleFontScale()" title="Toggle Text Scaling for Distance Viewing">
        <span style="font-weight: 800;">A<span style="font-size:0.7em;">A</span></span>
        <span id="zoomScaleLabel">Standard</span>
      </button>

      <!-- Sound Mute/Unmute -->
      <button type="button" id="btnSoundToggle" class="btn-pill" onclick="toggleAudio()" title="Sound Notification for New Orders">
        <span id="soundIcon">🔊</span>
        <span id="soundLabel">Sound ON</span>
      </button>

      <!-- Recall Bumped Orders Drawer Toggle -->
      <button type="button" id="btnRecallToggle" class="btn-pill" onclick="openRecallDrawer()" title="Recall or Undo Recently Cleared Orders">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        <span>Recall</span>
        <span id="recallCountBadge" class="filter-badge" style="background: rgba(255,255,255,0.2);">0</span>
      </button>

      <!-- Fullscreen Kiosk Mode -->
      <button type="button" class="btn-pill" onclick="toggleFullscreen()" title="Fullscreen Kitchen Kiosk Mode">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
      </button>

      <!-- Exit KDS -->
      <a href="/admin/logout.php" class="btn-pill" style="color: var(--kds-text-dim);" title="Sign out / Exit Terminal">Exit</a>
    </div>
  </header>

  <!-- Consolidated Prep Summary ("All-Day" Item Bar) -->
  <section class="prep-summary-bar" id="prepSummarySection">
    <div class="prep-summary-title">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
      <span>Prep Load:</span>
    </div>
    <div class="prep-chips-list" id="prepChipsList">
      <span style="font-size: 0.8rem; color: var(--kds-text-muted);">No active items to prep</span>
    </div>
  </section>

  <!-- Flow Grid Filter Tabs (Visible when in Grid View) -->
  <nav class="status-filter-bar" id="gridFilterBar" style="display: none;">
    <button type="button" class="filter-tab active" data-filter="all" onclick="setGridFilter('all')">
      All Active <span class="filter-badge" id="tabCountAll">0</span>
    </button>
    <button type="button" class="filter-tab" data-filter="placed" onclick="setGridFilter('placed')">
      New / Placed <span class="filter-badge" id="tabCountPlaced">0</span>
    </button>
    <button type="button" class="filter-tab" data-filter="preparing" onclick="setGridFilter('preparing')">
      In Cooking <span class="filter-badge" id="tabCountPreparing">0</span>
    </button>
    <button type="button" class="filter-tab" data-filter="ready" onclick="setGridFilter('ready')">
      Ready / Pickup <span class="filter-badge" id="tabCountReady">0</span>
    </button>
  </nav>

  <!-- Main Viewport -->
  <main class="kds-viewport">
    
    <!-- 1. KANBAN COLUMNS VIEW -->
    <div class="kanban-board" id="kanbanBoard">
      
      <!-- New Column -->
      <section class="kanban-col">
        <div class="kanban-col-header col-new">
          <div class="kanban-col-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>1. New Orders</span>
          </div>
          <span class="kanban-col-count" id="countColNew">0</span>
        </div>
        <div class="kanban-col-body" id="colBodyNew">
          <!-- Populated via JS -->
        </div>
      </section>

      <!-- In Cooking Column -->
      <section class="kanban-col">
        <div class="kanban-col-header col-cooking">
          <div class="kanban-col-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
            <span>2. In Cooking</span>
          </div>
          <span class="kanban-col-count" id="countColCooking">0</span>
        </div>
        <div class="kanban-col-body" id="colBodyCooking">
          <!-- Populated via JS -->
        </div>
      </section>

      <!-- Ready Column -->
      <section class="kanban-col">
        <div class="kanban-col-header col-ready">
          <div class="kanban-col-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>3. Ready for Pass</span>
          </div>
          <span class="kanban-col-count" id="countColReady">0</span>
        </div>
        <div class="kanban-col-body" id="colBodyReady">
          <!-- Populated via JS -->
        </div>
      </section>
    </div>

    <!-- 2. GRID FLOW VIEW (Alternative) -->
    <div class="grid-board" id="gridBoard" style="display: none;">
      <!-- Populated via JS -->
    </div>

  </main>

  <!-- Slide-Out Recall / Undo Drawer -->
  <div class="recall-drawer-overlay" id="recallOverlay" onclick="closeRecallDrawer()"></div>
  <aside class="recall-drawer" id="recallDrawer">
    <div class="recall-drawer-header">
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        <h3 style="font-size: 1.15rem; font-weight: 800; color: #FFF;">Recently Cleared</h3>
      </div>
      <button type="button" class="btn-pill" onclick="closeRecallDrawer()" style="padding: 0.35rem 0.65rem;">✕</button>
    </div>
    <div class="recall-drawer-body" id="recallDrawerBody">
      <div style="color: var(--kds-text-muted); text-align: center; padding: 2rem 1rem;">
        No recently completed orders found.
      </div>
    </div>
  </aside>

  <!-- Application Logic -->
  <script>
    // State management
    let state = {
      tickets: [],
      recentCompleted: [],
      prepSummary: [],
      viewMode: localStorage.getItem('cielo_kds_view') || 'kanban', // 'kanban' or 'grid'
      gridFilter: 'all', // 'all', 'placed', 'preparing', 'ready'
      searchQuery: '',
      prepFilterItem: '',
      soundEnabled: localStorage.getItem('cielo_kds_sound') !== 'false',
      zoomScale: localStorage.getItem('cielo_kds_scale') || 'normal', // 'normal', 'large', 'huge'
      knownTicketIds: new Set(),
      checkedItems: JSON.parse(localStorage.getItem('cielo_kds_checked') || '{}')
    };

    let audioCtx = null;

    // Initialize UI settings
    applyFontScale(state.zoomScale);
    updateSoundUI();
    updateViewModeUI();

    // Digital shift clock
    function updateClock() {
      const now = new Date();
      const clockEl = document.getElementById('kdsClock');
      if (clockEl) {
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour12: false });
      }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ----------------------------------------------------
    // Sound Notifications via Web Audio API (No files needed)
    // ----------------------------------------------------
    function getAudioContext() {
      if (!audioCtx) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
          audioCtx = new AudioContextClass();
        }
      }
      if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume();
      }
      return audioCtx;
    }

    function unlockAudioContext() {
      getAudioContext();
      document.getElementById('soundUnlockNotice').style.display = 'none';
    }

    function playNewOrderChime() {
      if (!state.soundEnabled) return;
      try {
        const ctx = getAudioContext();
        if (!ctx) return;

        // Friendly 2-tone melodic chime: C6 (1046.5Hz) -> E6 (1318.5Hz)
        const now = ctx.currentTime;

        const playTone = (freq, start, duration) => {
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          osc.type = 'sine';
          osc.frequency.setValueAtTime(freq, start);

          gain.gain.setValueAtTime(0.001, start);
          gain.gain.exponentialRampToValueAtTime(0.28, start + 0.04);
          gain.gain.exponentialRampToValueAtTime(0.001, start + duration);

          osc.connect(gain);
          gain.connect(ctx.destination);

          osc.start(start);
          osc.stop(start + duration);
        };

        playTone(1046.5, now, 0.22);
        playTone(1318.5, now + 0.16, 0.40);
      } catch (err) {
        console.warn('Audio alert error:', err);
      }
    }

    function toggleAudio() {
      state.soundEnabled = !state.soundEnabled;
      localStorage.setItem('cielo_kds_sound', state.soundEnabled ? 'true' : 'false');
      updateSoundUI();
      if (state.soundEnabled) {
        getAudioContext();
        playNewOrderChime();
      }
    }

    function updateSoundUI() {
      const icon = document.getElementById('soundIcon');
      const label = document.getElementById('soundLabel');
      if (state.soundEnabled) {
        icon.textContent = '🔊';
        label.textContent = 'Sound ON';
      } else {
        icon.textContent = '🔇';
        label.textContent = 'Muted';
      }
    }

    // ----------------------------------------------------
    // Font Scaling (Standard, Large, Huge)
    // ----------------------------------------------------
    function cycleFontScale() {
      const order = ['normal', 'large', 'huge'];
      const nextIndex = (order.indexOf(state.zoomScale) + 1) % order.length;
      state.zoomScale = order[nextIndex];
      localStorage.setItem('cielo_kds_scale', state.zoomScale);
      applyFontScale(state.zoomScale);
    }

    function applyFontScale(scale) {
      document.body.classList.remove('scale-normal', 'scale-large', 'scale-huge');
      document.body.classList.add(`scale-${scale}`);
      const label = document.getElementById('zoomScaleLabel');
      if (label) {
        label.textContent = scale.charAt(0).toUpperCase() + scale.slice(1);
      }
    }

    // ----------------------------------------------------
    // Fullscreen Kiosk Mode
    // ----------------------------------------------------
    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        }
      }
    }

    // ----------------------------------------------------
    // View Mode Switching (Kanban vs Grid)
    // ----------------------------------------------------
    function toggleViewMode() {
      state.viewMode = state.viewMode === 'kanban' ? 'grid' : 'kanban';
      localStorage.setItem('cielo_kds_view', state.viewMode);
      updateViewModeUI();
      renderAll();
    }

    function updateViewModeUI() {
      const isKanban = state.viewMode === 'kanban';
      document.getElementById('kanbanBoard').style.display = isKanban ? 'grid' : 'none';
      document.getElementById('gridBoard').style.display = isKanban ? 'none' : 'grid';
      document.getElementById('gridFilterBar').style.display = isKanban ? 'none' : 'flex';
      
      const label = document.getElementById('viewModeLabel');
      if (label) {
        label.textContent = isKanban ? 'Kanban View' : 'Grid View';
      }
    }

    function setGridFilter(filter) {
      state.gridFilter = filter;
      document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.toggle('active', tab.getAttribute('data-filter') === filter);
      });
      renderAll();
    }

    function onSearchChange(val) {
      state.searchQuery = val.trim().toLowerCase();
      renderAll();
    }

    // ----------------------------------------------------
    // Line Item Checklist Toggle (Strikethrough)
    // ----------------------------------------------------
    function toggleItemDone(event, orderId, itemKey) {
      event.stopPropagation();
      const storageKey = `${orderId}_${itemKey}`;
      state.checkedItems[storageKey] = !state.checkedItems[storageKey];
      localStorage.setItem('cielo_kds_checked', JSON.stringify(state.checkedItems));
      
      const row = event.currentTarget;
      const isDone = state.checkedItems[storageKey];
      row.classList.toggle('is-done', isDone);
      const icon = row.querySelector('.item-check-icon');
      if (icon) {
        icon.innerHTML = isDone 
          ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>'
          : '';
      }
    }

    // ----------------------------------------------------
    // Live Polling & Data Fetch
    // ----------------------------------------------------
    async function fetchKitchenTickets() {
      try {
        const res = await fetch('/api/kitchen_orders.php');
        const data = await res.json();

        if (data && data.success) {
          const incomingTickets = data.data.tickets || [];
          state.recentCompleted = data.data.recent_completed || [];
          state.prepSummary = data.data.prep_summary || [];

          // Detect brand new tickets for audio alert
          let hasNewIncoming = false;
          incomingTickets.forEach(t => {
            if (!state.knownTicketIds.has(t.id)) {
              if (state.knownTicketIds.size > 0 && t.status === 'placed') {
                hasNewIncoming = true;
              }
              state.knownTicketIds.add(t.id);
            }
          });

          if (hasNewIncoming) {
            playNewOrderChime();
          }

          state.tickets = incomingTickets;
          renderAll();
          renderPrepSummary();
          updateRecallCount();
        }
      } catch (err) {
        console.warn('KDS Polling Error:', err);
      }
    }

    // ----------------------------------------------------
    // Status Action Updates
    // ----------------------------------------------------
    async function updateStatus(orderId, action) {
      try {
        const res = await fetch('/api/kitchen_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order_id: orderId, action })
        });
        const data = await res.json();
        if (data.success) {
          // Immediately re-fetch for responsive UI
          fetchKitchenTickets();
        }
      } catch (err) {
        console.error('Update status failed:', err);
      }
    }

    function printKOT(orderId) {
      window.open(`/kitchen/print_kot.php?order_id=${orderId}&autoprint=1`, 'kot_print_window', 'width=380,height=600');
    }

    // ----------------------------------------------------
    // Rendering Ticket Cards
    // ----------------------------------------------------
    function createTicketCardHTML(t) {
      const nowTs = Math.floor(Date.now() / 1000);
      const elapsedSec = Math.max(0, nowTs - (t.created_ts || nowTs));
      const elapsedMin = Math.floor(elapsedSec / 60);
      const elapsedSecRemainder = elapsedSec % 60;
      const formattedTime = `${String(elapsedMin).padStart(2, '0')}:${String(elapsedSecRemainder).padStart(2, '0')}`;

      // Urgency badge calculation
      let timerClass = 'timer-fresh';
      let isOverdue = false;
      if (elapsedMin >= 15) {
        timerClass = 'timer-overdue';
        isOverdue = true;
      } else if (elapsedMin >= 8) {
        timerClass = 'timer-warn';
      }

      // Action button
      let actionBtnHTML = '';
      if (t.status === 'placed') {
        actionBtnHTML = `
          <button class="btn-bump action-cook" onclick="updateStatus(${t.id}, 'prepare')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            <span>Start Cooking</span>
          </button>
        `;
      } else if (t.status === 'preparing') {
        actionBtnHTML = `
          <button class="btn-bump action-ready" onclick="updateStatus(${t.id}, 'ready')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
            <span>Mark Ready</span>
          </button>
        `;
      } else if (t.status === 'ready') {
        actionBtnHTML = `
          <button class="btn-bump action-done" onclick="updateStatus(${t.id}, 'complete')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>Served & Bump</span>
          </button>
        `;
      }

      // Line items with checklist support
      const itemsHTML = (t.items || []).map((it, idx) => {
        const itemKey = it.id || `idx_${idx}`;
        const storageKey = `${t.id}_${itemKey}`;
        const isDone = !!state.checkedItems[storageKey];

        const addonsHTML = (it.addons && it.addons.length > 0)
          ? `<div class="item-addon-tag">${it.addons.map(a => '+ ' + a.name).join(', ')}</div>`
          : '';

        const noteHTML = it.note
          ? `<div class="item-prep-note">💬 "${escapeHtml(it.note)}"</div>`
          : '';

        return `
          <div class="ticket-item-row ${isDone ? 'is-done' : ''}" onclick="toggleItemDone(event, ${t.id}, '${itemKey}')">
            <div class="item-check-box">
              <span class="item-check-icon">
                ${isDone ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>' : ''}
              </span>
            </div>
            <div class="item-content">
              <div class="item-headline">
                <span class="item-qty-badge">${it.quantity}x</span>
                <span>${escapeHtml(it.name)}</span>
              </div>
              ${addonsHTML}
              ${noteHTML}
            </div>
          </div>
        `;
      }).join('');

      // Chef notes alert box
      const chefNoteHTML = t.kitchen_notes
        ? `
          <div class="chef-note-alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div><strong>CHEF NOTE:</strong> ${escapeHtml(t.kitchen_notes)}</div>
          </div>
        `
        : '';

      const lastFour = t.order_number.length > 4 ? t.order_number.slice(-4) : t.order_number;

      return `
        <article class="ticket-card status-${t.status} ${isOverdue ? 'is-overdue' : ''}" data-ticket-id="${t.id}">
          <header class="ticket-header">
            <div class="ticket-table-info">
              <div class="ticket-table-num">
                <span>TABLE ${escapeHtml(t.table_number)}</span>
              </div>
              <div class="ticket-meta-sub">
                <span>#${escapeHtml(lastFour)}</span>
                <span>•</span>
                <span>${escapeHtml(t.floor || 'Dine-In')}</span>
                <span>•</span>
                <span>${t.created_time}</span>
              </div>
            </div>

            <div class="ticket-timer-wrap">
              <div class="live-timer-pill ${timerClass}" data-created-ts="${t.created_ts || nowTs}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="time-text">${formattedTime}</span>
              </div>
              ${isOverdue ? '<span style="font-size:0.68rem; font-weight:800; color:var(--color-urgent); letter-spacing:0.04em;">OVERDUE</span>' : ''}
            </div>
          </header>

          <div class="ticket-body">
            ${itemsHTML}
            ${chefNoteHTML}
          </div>

          <footer class="ticket-footer">
            ${actionBtnHTML}
            <button class="btn-kot-icon" title="Print KOT Thermal Ticket" onclick="printKOT(${t.id})">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
            </button>
          </footer>
        </article>
      `;
    }

    // ----------------------------------------------------
    // Master Render Method
    // ----------------------------------------------------
    function renderAll() {
      const tickets = state.tickets || [];

      // Filter by search query if any
      let filtered = tickets;
      if (state.searchQuery) {
        filtered = filtered.filter(t => {
          const matchTable = (t.table_number || '').toLowerCase().includes(state.searchQuery);
          const matchOrder = (t.order_number || '').toLowerCase().includes(state.searchQuery);
          return matchTable || matchOrder;
        });
      }

      // Filter by prep chip if selected
      if (state.prepFilterItem) {
        filtered = filtered.filter(t => (t.items || []).some(it => it.name === state.prepFilterItem));
      }

      // Counts
      const countNew = tickets.filter(t => t.status === 'placed').length;
      const countCooking = tickets.filter(t => t.status === 'preparing').length;
      const countReady = tickets.filter(t => t.status === 'ready').length;

      document.getElementById('countColNew').textContent = countNew;
      document.getElementById('countColCooking').textContent = countCooking;
      document.getElementById('countColReady').textContent = countReady;

      document.getElementById('tabCountAll').textContent = tickets.length;
      document.getElementById('tabCountPlaced').textContent = countNew;
      document.getElementById('tabCountPreparing').textContent = countCooking;
      document.getElementById('tabCountReady').textContent = countReady;

      // 1. KANBAN BOARD RENDER
      const colNew = document.getElementById('colBodyNew');
      const colCooking = document.getElementById('colBodyCooking');
      const colReady = document.getElementById('colBodyReady');

      const newTickets = filtered.filter(t => t.status === 'placed');
      const cookingTickets = filtered.filter(t => t.status === 'preparing');
      const readyTickets = filtered.filter(t => t.status === 'ready');

      colNew.innerHTML = newTickets.length > 0 
        ? newTickets.map(createTicketCardHTML).join('')
        : '<div style="color:var(--kds-text-muted); text-align:center; padding:3rem 1rem; font-size:0.85rem;">No new orders</div>';

      colCooking.innerHTML = cookingTickets.length > 0
        ? cookingTickets.map(createTicketCardHTML).join('')
        : '<div style="color:var(--kds-text-muted); text-align:center; padding:3rem 1rem; font-size:0.85rem;">Station clear</div>';

      colReady.innerHTML = readyTickets.length > 0
        ? readyTickets.map(createTicketCardHTML).join('')
        : '<div style="color:var(--kds-text-muted); text-align:center; padding:3rem 1rem; font-size:0.85rem;">No orders ready</div>';

      // 2. GRID FLOW RENDER
      const gridBoard = document.getElementById('gridBoard');
      let gridTickets = filtered;
      if (state.gridFilter !== 'all') {
        gridTickets = gridTickets.filter(t => t.status === state.gridFilter);
      }

      if (gridTickets.length === 0) {
        gridBoard.innerHTML = `
          <div class="empty-state-card">
            <div class="empty-state-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3 style="font-size:1.35rem; font-weight:800; color:#FFF;">All Kitchen Tickets Clear!</h3>
            <p style="color:var(--kds-text-dim); max-width:400px; font-size:0.9rem;">The kitchen board is caught up. New orders placed at customer tables will chime and populate automatically.</p>
          </div>
        `;
      } else {
        gridBoard.innerHTML = gridTickets.map(createTicketCardHTML).join('');
      }
    }

    // ----------------------------------------------------
    // Prep Summary Chip Bar
    // ----------------------------------------------------
    function renderPrepSummary() {
      const container = document.getElementById('prepChipsList');
      const items = state.prepSummary || [];

      if (items.length === 0) {
        container.innerHTML = '<span style="font-size:0.8rem; color:var(--kds-text-muted);">No active items</span>';
        return;
      }

      container.innerHTML = items.map(item => {
        const isSelected = state.prepFilterItem === item.name;
        return `
          <button type="button" class="prep-chip ${isSelected ? 'filter-active' : ''}" onclick="togglePrepFilter('${escapeHtml(item.name)}')">
            <span class="prep-chip-qty">${item.total_qty}x</span>
            <span>${escapeHtml(item.name)}</span>
          </button>
        `;
      }).join('');
    }

    function togglePrepFilter(itemName) {
      state.prepFilterItem = state.prepFilterItem === itemName ? '' : itemName;
      renderPrepSummary();
      renderAll();
    }

    // ----------------------------------------------------
    // Slide-Over Recall / Undo Drawer
    // ----------------------------------------------------
    function openRecallDrawer() {
      document.getElementById('recallOverlay').classList.add('is-open');
      document.getElementById('recallDrawer').classList.add('is-open');
      renderRecallDrawer();
    }

    function closeRecallDrawer() {
      document.getElementById('recallOverlay').classList.remove('is-open');
      document.getElementById('recallDrawer').classList.remove('is-open');
    }

    function updateRecallCount() {
      const badge = document.getElementById('recallCountBadge');
      if (badge) {
        badge.textContent = (state.recentCompleted || []).length;
      }
    }

    function renderRecallDrawer() {
      const body = document.getElementById('recallDrawerBody');
      const items = state.recentCompleted || [];

      if (items.length === 0) {
        body.innerHTML = `
          <div style="color:var(--kds-text-muted); text-align:center; padding:3rem 1rem;">
            No recently completed orders found.
          </div>
        `;
        return;
      }

      body.innerHTML = items.map(t => {
        const itemNames = (t.items || []).map(it => `${it.quantity}x ${it.name}`).join(', ') || 'No line items';
        return `
          <div class="recall-card">
            <div class="recall-card-header">
              <strong style="font-size: 1.15rem; color:#FFF;">TABLE ${escapeHtml(t.table_number)}</strong>
              <span style="font-size: 0.8rem; color:var(--kds-text-dim);">Order #${escapeHtml(t.order_number)}</span>
            </div>
            <div style="font-size: 0.85rem; color:var(--kds-text); line-height: 1.3;">
              ${escapeHtml(itemNames)}
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.35rem;">
              <span style="font-size:0.75rem; color:var(--kds-text-muted);">Cleared: ${t.created_time}</span>
              <button type="button" class="btn-restore" onclick="recallOrder(${t.id})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                <span>Restore to Kitchen</span>
              </button>
            </div>
          </div>
        `;
      }).join('');
    }

    async function recallOrder(orderId) {
      await updateStatus(orderId, 'recall');
      closeRecallDrawer();
    }

    // ----------------------------------------------------
    // Real-Time Second-by-Second Timer Ticker
    // ----------------------------------------------------
    function tickTimers() {
      const nowTs = Math.floor(Date.now() / 1000);
      document.querySelectorAll('.live-timer-pill').forEach(pill => {
        const createdTs = parseInt(pill.getAttribute('data-created-ts'), 10);
        if (!createdTs) return;

        const elapsedSec = Math.max(0, nowTs - createdTs);
        const elapsedMin = Math.floor(elapsedSec / 60);
        const elapsedSecRem = elapsedSec % 60;
        const textSpan = pill.querySelector('.time-text');
        if (textSpan) {
          textSpan.textContent = `${String(elapsedMin).padStart(2, '0')}:${String(elapsedSecRem).padStart(2, '0')}`;
        }

        // Dynamically adjust urgency class
        pill.classList.remove('timer-fresh', 'timer-warn', 'timer-overdue');
        const card = pill.closest('.ticket-card');
        if (elapsedMin >= 15) {
          pill.classList.add('timer-overdue');
          if (card) card.classList.add('is-overdue');
        } else if (elapsedMin >= 8) {
          pill.classList.add('timer-warn');
          if (card) card.classList.remove('is-overdue');
        } else {
          pill.classList.add('timer-fresh');
          if (card) card.classList.remove('is-overdue');
        }
      });
    }
    setInterval(tickTimers, 1000);

    // Helpers
    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // Initial load & Polling every 4 seconds
    fetchKitchenTickets();
    setInterval(fetchKitchenTickets, 4000);

    // One-time click handler to unlock Web Audio context on user gesture
    document.addEventListener('click', function unlockOnce() {
      if (!audioCtx) getAudioContext();
      document.removeEventListener('click', unlockOnce);
    }, { once: true });
  </script>
</body>
</html>

<?php
/**
 * Cielo Cafe and Restro - Patron Loyalty & Profile Hub
 * 
 * Frictionless Mobile OTP Login, Real-time Points Ledger,
 * 5-Visit Milestone Tracker, Personal Coupon Wallet & Itemized Order Receipts.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';

use App\Services\CustomerAuthService;

$pageTitle = "Patron Club & Profile | Cielo Cafe and Restro";
$pageDesc = "Track your Cielo reward points, visit milestones, unlock exclusive vouchers, and manage your artisanal dining history.";
$pageCss = ['home.css'];
$pageJs = [];

$user = CustomerAuthService::getCurrentUser();
$metrics = $user ? CustomerAuthService::getProfileMetrics((int)$user['id']) : null;

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5.5rem; min-height: 85vh; background: var(--bg-cream, #F7F4EE);">
  <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 2rem 1.25rem 5rem;">

    <?php if (!$user): ?>
      <!-- ============================================================== -->
      <!-- GUEST / LOGGED-OUT: MOBILE OTP AUTHENTICATION SPLIT CARD       -->
      <!-- ============================================================== -->
      <div style="background: #ffffff; border-radius: 24px; border: 1px solid rgba(26,20,18,0.08); box-shadow: 0 15px 40px rgba(26,20,18,0.05); overflow: hidden; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
        
        <!-- Left: Luxury Perks Pitch -->
        <div style="background: linear-gradient(135deg, #1A1412 0%, #2A221F 100%); color: #F7F4EE; padding: 3.5rem 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(201,106,82,0.2); color: var(--accent-terracotta, #C96A52); padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.5rem;">
              <span>✦</span> Cielo Patron Club
            </div>
            <h1 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 2.25rem; line-height: 1.2; margin-bottom: 1rem; color: #ffffff;">
              Your Table.<br>Your Sky Rewards.
            </h1>
            <p style="color: rgba(247,244,238,0.75); font-size: 0.95rem; line-height: 1.6; margin-bottom: 2.5rem;">
              Log in with your phone number to unlock automated cashbacks, visit milestone rewards, and instant coupon vouchers.
            </p>

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
              <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(212,175,55,0.15); color: #D4AF37; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">★</div>
                <div>
                  <h2 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.2rem; color: #ffffff;">10 Points per ₹100 Spent</h2>
                  <p style="font-size: 0.85rem; color: rgba(247,244,238,0.65); margin: 0;">Automatically added to your immutable ledger on every order.</p>
                </div>
              </div>

              <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(107,154,196,0.15); color: var(--accent-sky, #6B9AC4); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">◈</div>
                <div>
                  <h2 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.2rem; color: #ffffff;">5-Visit Milestone Gift</h2>
                  <p style="font-size: 0.85rem; color: rgba(247,244,238,0.65); margin: 0;">Complimentary artisanal beverage or chef dessert every 5 visits.</p>
                </div>
              </div>

              <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(201,106,82,0.15); color: var(--accent-terracotta, #C96A52); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">♥</div>
                <div>
                  <h2 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.2rem; color: #ffffff;">One-Tap Guest Order Merge</h2>
                  <p style="font-size: 0.85rem; color: rgba(247,244,238,0.65); margin: 0;">Past orders from this phone number automatically sync to your account.</p>
                </div>
              </div>
            </div>
          </div>

          <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(247,244,238,0.1); font-size: 0.8rem; color: rgba(247,244,238,0.5);">
            No passwords needed • Secure 6-digit SMS OTP
          </div>
        </div>

        <!-- Right: Frictionless OTP Form -->
        <div style="padding: 3.5rem 2.5rem; display: flex; flex-direction: column; justify-content: center;">
          
          <!-- STEP 1: PHONE INPUT -->
          <div id="otpStepPhone">
            <h2 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.75rem; color: var(--text-espresso, #1A1412); margin-bottom: 0.5rem;">
              Mobile Number Login
            </h2>
            <p style="color: rgba(26,20,18,0.65); font-size: 0.9rem; margin-bottom: 2rem;">
              Enter your Indian mobile number. We'll send you an instant verification code.
            </p>

            <form id="formSendOtp" onsubmit="handleSendOtp(event)">
              <div style="margin-bottom: 1.25rem;">
                <label for="inputPhone" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-espresso, #1A1412); margin-bottom: 0.5rem;">
                  Mobile Number
                </label>
                <div style="display: flex; align-items: center; border: 1.5px solid rgba(26,20,18,0.15); border-radius: 12px; overflow: hidden; background: #ffffff; transition: border-color 0.2s;">
                  <span style="padding: 0.85rem 1rem; background: rgba(26,20,18,0.03); color: var(--text-espresso, #1A1412); font-weight: 600; font-size: 0.95rem; border-right: 1px solid rgba(26,20,18,0.1);">
                    🇮🇳 +91
                  </span>
                  <input type="tel" id="inputPhone" required placeholder="98765 43210" maxlength="10" pattern="[6-9][0-9]{9}" style="flex: 1; border: none; padding: 0.85rem 1rem; font-size: 1.05rem; letter-spacing: 0.05em; outline: none; font-weight: 600; color: var(--text-espresso, #1A1412);">
                </div>
                <span id="phoneError" style="display: none; color: #dc2626; font-size: 0.8rem; margin-top: 0.4rem; font-weight: 500;"></span>
              </div>

              <div style="margin-bottom: 1.5rem;">
                <label for="inputName" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-espresso, #1A1412); margin-bottom: 0.5rem;">
                  Full Name <span style="font-weight: 400; color: rgba(26,20,18,0.5);">(Optional)</span>
                </label>
                <input type="text" id="inputName" placeholder="e.g. Aarav Patel" style="width: 100%; border: 1.5px solid rgba(26,20,18,0.15); border-radius: 12px; padding: 0.85rem 1rem; font-size: 0.95rem; outline: none; color: var(--text-espresso, #1A1412); box-sizing: border-box;">
              </div>

              <button type="submit" id="btnSendOtp" style="width: 100%; background: var(--text-espresso, #1A1412); color: #ffffff; border: none; padding: 0.95rem; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: transform 0.15s, background 0.15s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span>Send Verification Code</span>
                <span style="font-size: 1.1rem;">→</span>
              </button>
            </form>
          </div>

          <!-- STEP 2: OTP VERIFICATION -->
          <div id="otpStepVerify" style="display: none;">
            <button type="button" onclick="backToPhoneStep()" style="background: none; border: none; color: var(--accent-terracotta, #C96A52); font-weight: 600; font-size: 0.85rem; cursor: pointer; padding: 0; margin-bottom: 1rem; display: inline-flex; align-items: center; gap: 0.35rem;">
              ← Change Mobile Number
            </button>
            <h2 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.75rem; color: var(--text-espresso, #1A1412); margin-bottom: 0.5rem;">
              Verify Code
            </h2>
            <p style="color: rgba(26,20,18,0.65); font-size: 0.9rem; margin-bottom: 1.75rem;">
              Sent to <strong id="displayTargetPhone" style="color: var(--text-espresso, #1A1412);">+91 98765 43210</strong>
            </p>

            <!-- Dev mode auto-fill alert -->
            <div id="devOtpBox" style="display: none; background: rgba(212,175,55,0.12); border: 1px solid rgba(212,175,55,0.3); border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: #8A6D15;">
              <div style="font-weight: 700; margin-bottom: 0.2rem;">⚡ Test Mode OTP Code:</div>
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <code id="mockOtpCode" style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.15em; color: var(--text-espresso, #1A1412);">123456</code>
                <button type="button" onclick="autoFillMockOtp()" style="background: #1A1412; color: #fff; border: none; border-radius: 6px; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 600; cursor: pointer;">Auto-Fill</button>
              </div>
            </div>

            <form id="formVerifyOtp" onsubmit="handleVerifyOtp(event)">
              <div style="margin-bottom: 1.5rem;">
                <label for="inputOtp" style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-espresso, #1A1412); margin-bottom: 0.5rem;">
                  6-Digit Verification Code
                </label>
                <input type="text" id="inputOtp" required maxlength="6" pattern="[0-9]{6}" placeholder="• • • • • •" style="width: 100%; border: 1.5px solid rgba(26,20,18,0.2); border-radius: 12px; padding: 0.85rem 1rem; font-size: 1.5rem; letter-spacing: 0.35em; text-align: center; font-weight: 700; color: var(--text-espresso, #1A1412); outline: none; box-sizing: border-box;">
                <span id="otpError" style="display: none; color: #dc2626; font-size: 0.8rem; margin-top: 0.4rem; font-weight: 500;"></span>
              </div>

              <button type="submit" id="btnVerifyOtp" style="width: 100%; background: var(--accent-terracotta, #C96A52); color: #ffffff; border: none; padding: 0.95rem; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: transform 0.15s, background 0.15s; margin-bottom: 1.25rem;">
                Verify & Enter Profile Hub
              </button>

              <div style="text-align: center; font-size: 0.85rem; color: rgba(26,20,18,0.6);">
                <span id="resendCountdownText">Resend code in <strong id="resendTimer">30</strong>s</span>
                <button type="button" id="btnResendOtp" onclick="resendOtp()" style="display: none; background: none; border: none; color: var(--accent-sky, #6B9AC4); font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline;">
                  Resend Verification Code
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>

    <?php else: ?>
      <!-- ============================================================== -->
      <!-- LOGGED-IN: PATRON LOYALTY & PROFILE COMMAND HUB               -->
      <!-- ============================================================== -->
      
      <!-- Top Patron Card -->
      <div style="background: #ffffff; border-radius: 20px; border: 1px solid rgba(26,20,18,0.08); padding: 1.75rem 2rem; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(26,20,18,0.03); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.25rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
          <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--text-espresso, #1A1412); color: #F7F4EE; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-family: var(--font-display, serif); font-weight: 700;">
            <?= strtoupper(substr($metrics['user']['name'] ?: 'P', 0, 1)) ?>
          </div>
          <div>
            <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.25rem;">
              <h1 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.6rem; color: var(--text-espresso, #1A1412); margin: 0;">
                <?= e($metrics['user']['name']) ?>
              </h1>
              <span style="display: inline-block; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; background: <?= e($metrics['points']['tier']['color']) ?>18; color: <?= e($metrics['points']['tier']['color']) ?>; border: 1px solid <?= e($metrics['points']['tier']['color']) ?>40;">
                <?= e($metrics['points']['tier']['badge']) ?>
              </span>
            </div>
            <div style="font-size: 0.85rem; color: rgba(26,20,18,0.65); display: flex; gap: 1rem; flex-wrap: wrap;">
              <span>📱 +91 <?= e($metrics['user']['phone']) ?></span>
              <?php if (!empty($metrics['user']['email'])): ?>
                <span>✉ <?= e($metrics['user']['email']) ?></span>
              <?php endif; ?>
              <span>Member since <?= e($metrics['user']['member_since']) ?></span>
            </div>
          </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
          <button type="button" onclick="openEditProfileModal()" style="background: rgba(26,20,18,0.05); color: var(--text-espresso, #1A1412); border: 1px solid rgba(26,20,18,0.1); border-radius: 9999px; padding: 0.6rem 1.25rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.15s;">
            Edit Profile
          </button>
          <button type="button" onclick="handleLogout()" style="background: none; border: 1px solid rgba(201,106,82,0.3); color: var(--accent-terracotta, #C96A52); border-radius: 9999px; padding: 0.6rem 1.25rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.15s;">
            Sign Out
          </button>
        </div>
      </div>

      <!-- Grid of 2 Key Pillars: Loyalty Points + Milestone Progress -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Card 1: Points Ledger -->
        <div style="background: linear-gradient(135deg, #1A1412 0%, #2E2522 100%); border-radius: 20px; padding: 2rem; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 10px 25px rgba(26,20,18,0.08);">
          <div style="position: absolute; right: -20px; top: -20px; font-size: 9rem; opacity: 0.05; font-family: serif; user-select: none;">★</div>
          <span style="font-size: 0.8rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #D4AF37; display: block; margin-bottom: 0.5rem;">
            Authoritative Points Balance
          </span>
          <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.75rem;">
            <span style="font-size: 3.25rem; font-weight: 800; font-family: var(--font-display, serif); line-height: 1; color: #ffffff;">
              <?= number_format($metrics['points']['balance']) ?>
            </span>
            <span style="font-size: 1rem; color: rgba(255,255,255,0.7); font-weight: 600;">pts</span>
          </div>
          <p style="font-size: 0.9rem; color: rgba(255,255,255,0.8); margin-bottom: 1.5rem; line-height: 1.5;">
            Equivalent to <strong style="color: #D4AF37;">₹<?= number_format($metrics['points']['rupee_equivalent']) ?></strong> redeemable on counter billing or online table checkout.
          </p>

          <div style="background: rgba(255,255,255,0.08); border-radius: 12px; padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
            <span>Current Status: <strong style="color: <?= e($metrics['points']['tier']['color']) ?>"><?= e($metrics['points']['tier']['name']) ?></strong></span>
            <a href="/menu" style="color: #D4AF37; text-decoration: none; font-weight: 600;">Order Dishes →</a>
          </div>
        </div>

        <!-- Card 2: 5-Visit Milestone Tracker -->
        <div style="background: #ffffff; border-radius: 20px; border: 1px solid rgba(26,20,18,0.08); padding: 2rem; box-shadow: 0 10px 25px rgba(26,20,18,0.03);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <span style="font-size: 0.8rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent-terracotta, #C96A52);">
              Visit Milestone Progress
            </span>
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-espresso, #1A1412);">
              Cycle: <?= $metrics['visits']['completed_milestones'] + 1 ?>
            </span>
          </div>
          
          <h2 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.5rem; color: var(--text-espresso, #1A1412); margin-bottom: 0.5rem;">
            Visit <?= $metrics['visits']['cycle_progress'] ?> of <?= $metrics['visits']['cycle_target'] ?> Completed
          </h2>
          <p style="font-size: 0.85rem; color: rgba(26,20,18,0.65); margin-bottom: 1.25rem;">
            <?= $metrics['visits']['remaining_for_milestone'] > 0
                ? 'Only ' . $metrics['visits']['remaining_for_milestone'] . ' more visit' . ($metrics['visits']['remaining_for_milestone'] === 1 ? '' : 's') . ' to claim your milestone reward!'
                : 'Milestone reached! Claim your reward on your next order.' ?>
          </p>

          <!-- 5-Step Pill Visualizer -->
          <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.5rem; margin-bottom: 1.25rem;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?php $isDone = $i <= $metrics['visits']['cycle_progress']; ?>
              <div style="text-align: center;">
                <div style="height: 10px; border-radius: 9999px; background: <?= $isDone ? 'var(--accent-terracotta, #C96A52)' : 'rgba(26,20,18,0.1)' ?>; margin-bottom: 0.35rem; transition: background 0.3s;"></div>
                <span style="font-size: 0.75rem; font-weight: 600; color: <?= $isDone ? 'var(--text-espresso, #1A1412)' : 'rgba(26,20,18,0.4)' ?>;">
                  #<?= $i ?>
                </span>
              </div>
            <?php endfor; ?>
          </div>

          <div style="background: rgba(201,106,82,0.08); border-radius: 12px; padding: 0.85rem 1rem; border-left: 3px solid var(--accent-terracotta, #C96A52); font-size: 0.85rem; color: var(--text-espresso, #1A1412);">
            <strong>Reward Goal:</strong> <?= e($metrics['visits']['next_milestone_reward']) ?>
          </div>
        </div>

      <!-- Section: Unlocked Milestone Rewards Wallet -->
      <div style="margin-bottom: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem;">
          <h2 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.4rem; color: var(--text-espresso, #1A1412); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <span>Unlocked Milestone Rewards</span>
            <span style="font-size: 0.72rem; background: var(--accent-terracotta, #C96A52); color: #fff; padding: 2px 8px; border-radius: 999px; font-family: sans-serif; font-weight: 700;">★ Claimable</span>
          </h2>
          <span style="font-size: 0.85rem; color: rgba(26,20,18,0.6);"><?= count($metrics['rewards'] ?? []) ?> active vouchers</span>
        </div>

        <?php if (empty($metrics['rewards'])): ?>
          <div style="background: #ffffff; border-radius: 16px; border: 1px dashed rgba(26,20,18,0.15); padding: 1.75rem; text-align: center; color: rgba(26,20,18,0.6); font-size: 0.9rem;">
            Complete your 5-visit cycle to unlock complimentary artisanal treats and wood-fired sourdough vouchers!
          </div>
        <?php else: ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 1rem;">
            <?php foreach ($metrics['rewards'] as $rw): ?>
              <div style="background: linear-gradient(135deg, #FFFDF9 0%, #F5EFEB 100%); border-radius: 16px; border: 1.5px solid rgba(201,106,82,0.3); padding: 1.25rem 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 15px rgba(201,106,82,0.06); position: relative; overflow: hidden;">
                <div style="position: absolute; right: 10px; top: 10px; font-size: 0.72rem; font-weight: 700; color: #16a34a; background: rgba(22,163,74,0.1); padding: 2px 8px; border-radius: 999px;">
                  Active Voucher
                </div>
                <div>
                  <span style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--accent-terracotta, #C96A52); display: block; margin-bottom: 0.25rem;">
                    Milestone Reward
                  </span>
                  <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin-bottom: 0.35rem;">
                    <?= e($rw['title']) ?>
                  </h3>
                  <div style="font-size: 0.85rem; color: var(--accent-sky-deep, #4A7A9F); font-weight: 600; margin-bottom: 0.5rem;">
                    <?= e($rw['item_name']) ?> (Value ₹<?= number_format($rw['discount_rupees']) ?>)
                  </div>
                  <p style="font-size: 0.78rem; color: rgba(26,20,18,0.6); margin-bottom: 1rem;">
                    Valid until <?= !empty($rw['expires_at']) ? date('d M Y', strtotime($rw['expires_at'])) : 'No expiry' ?>
                  </p>
                </div>
                <a href="/menu" style="display: block; text-align: center; text-decoration: none; width: 100%; background: var(--accent-terracotta, #C96A52); color: #ffffff; border-radius: 8px; padding: 0.65rem; font-size: 0.85rem; font-weight: 600; transition: background 0.15s; box-sizing: border-box;">
                  Claim on Table Order →
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Section: Personal Coupon Wallet -->
      <div style="margin-bottom: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem;">
          <h2 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.4rem; color: var(--text-espresso, #1A1412); margin: 0;">
            Personal Coupon Wallet
          </h2>
          <span style="font-size: 0.85rem; color: rgba(26,20,18,0.6);"><?= count($metrics['coupons']) ?> active vouchers available</span>
        </div>

        <?php if (empty($metrics['coupons'])): ?>
          <div style="background: #ffffff; border-radius: 16px; border: 1px dashed rgba(26,20,18,0.2); padding: 2rem; text-align: center; color: rgba(26,20,18,0.6);">
            No active coupons currently. Place your first order to unlock welcome treats!
          </div>
        <?php else: ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <?php foreach ($metrics['coupons'] as $cpn): ?>
              <div style="background: #ffffff; border-radius: 16px; border: 1px solid rgba(26,20,18,0.08); padding: 1.25rem 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 15px rgba(26,20,18,0.02);">
                <div>
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="display: inline-block; font-size: 1.1rem; font-weight: 800; color: var(--accent-terracotta, #C96A52); font-family: monospace; letter-spacing: 0.05em; background: rgba(201,106,82,0.1); padding: 0.25rem 0.65rem; border-radius: 6px;">
                      <?= e($cpn['code']) ?>
                    </span>
                    <span style="font-size: 0.95rem; font-weight: 700; color: #16a34a;">
                      <?= e($cpn['discount_text']) ?>
                    </span>
                  </div>
                  <h3 style="font-size: 0.95rem; font-weight: 600; color: var(--text-espresso, #1A1412); margin-bottom: 0.35rem;">
                    <?= e($cpn['title']) ?>
                  </h3>
                  <p style="font-size: 0.8rem; color: rgba(26,20,18,0.6); margin-bottom: 1rem;">
                    Min Order: ₹<?= number_format($cpn['min_order_rupees']) ?> • Valid till <?= date('d M Y', strtotime($cpn['end_date'])) ?>
                  </p>
                </div>
                <button type="button" onclick="applyCouponToCart('<?= e($cpn['code']) ?>')" style="width: 100%; background: var(--text-espresso, #1A1412); color: #ffffff; border: none; border-radius: 8px; padding: 0.6rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.15s;">
                  Apply to Cart
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Section: Itemized Past Order Receipts -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem;">
          <h2 style="font-family: var(--font-display, 'Playfair Display', serif); font-size: 1.4rem; color: var(--text-espresso, #1A1412); margin: 0;">
            Order History & Receipts
          </h2>
          <span style="font-size: 0.85rem; color: rgba(26,20,18,0.6);"><?= count($metrics['orders']) ?> orders logged</span>
        </div>

        <?php if (empty($metrics['orders'])): ?>
          <div style="background: #ffffff; border-radius: 16px; border: 1px dashed rgba(26,20,18,0.2); padding: 3rem 2rem; text-align: center; color: rgba(26,20,18,0.6);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🍽️</div>
            <h3 style="font-size: 1.1rem; color: var(--text-espresso, #1A1412); margin-bottom: 0.35rem;">No orders found yet</h3>
            <p style="font-size: 0.9rem; margin-bottom: 1.5rem;">Scan your table QR code or explore our artisanal catalog to place your first order.</p>
            <a href="/menu" style="display: inline-block; background: var(--text-espresso, #1A1412); color: #ffffff; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 9999px; font-size: 0.9rem; font-weight: 600;">Explore Menu</a>
          </div>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($metrics['orders'] as $ord): ?>
              <div style="background: #ffffff; border-radius: 16px; border: 1px solid rgba(26,20,18,0.08); padding: 1.5rem; box-shadow: 0 4px 15px rgba(26,20,18,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(26,20,18,0.06); padding-bottom: 0.85rem;">
                  <div>
                    <span style="font-weight: 700; font-family: monospace; font-size: 1.05rem; color: var(--text-espresso, #1A1412);">
                      <?= e($ord['order_number']) ?>
                    </span>
                    <span style="font-size: 0.8rem; color: rgba(26,20,18,0.5); margin-left: 0.5rem;">
                      • <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?>
                    </span>
                    <span style="font-size: 0.8rem; color: var(--accent-sky, #6B9AC4); font-weight: 600; margin-left: 0.5rem;">
                      (Table <?= e($ord['table_number']) ?>)
                    </span>
                  </div>

                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: <?= match($ord['status']) { 'completed' => '#dcfce7; color: #166534;', 'ready' => '#e0e7ff; color: #3730a3;', 'preparing' => '#fef3c7; color: #92400e;', default => '#f3f4f6; color: #374151;' } ?>">
                      <?= e(ucfirst($ord['status'])) ?>
                    </span>
                    <span style="font-weight: 700; font-size: 1.1rem; color: var(--text-espresso, #1A1412);">
                      <?= e($ord['total_formatted']) ?>
                    </span>
                  </div>
                </div>

                <!-- Items list -->
                <div style="margin-bottom: 1rem;">
                  <?php foreach ($ord['items'] as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem; color: rgba(26,20,18,0.85);">
                      <span><?= (int)$item['quantity'] ?>x <?= e($item['item_name']) ?></span>
                      <span style="font-weight: 600;"><?= e($item['total_price_formatted']) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(26,20,18,0.06); padding-top: 0.75rem;">
                  <span style="font-size: 0.8rem; color: rgba(26,20,18,0.5);">
                    Payment: <?= e(ucfirst(str_replace('_', ' ', $ord['payment_method']))) ?> (<?= e(ucfirst($ord['payment_status'])) ?>)
                  </span>
                  <a href="/order-status?order=<?= urlencode($ord['order_number']) ?>" style="color: var(--accent-terracotta, #C96A52); font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                    View Live Tracker →
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- EDIT PROFILE MODAL -->
      <div id="modalEditProfile" style="display: none; position: fixed; inset: 0; background: rgba(26,20,18,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1.25rem;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-family: var(--font-display, serif); font-size: 1.4rem; color: var(--text-espresso, #1A1412); margin: 0;">Edit Profile</h2>
            <button type="button" onclick="closeEditProfileModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: rgba(26,20,18,0.4); line-height: 1;">×</button>
          </div>

          <form id="formUpdateProfile" onsubmit="handleUpdateProfile(event)">
            <div style="margin-bottom: 1rem;">
              <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Full Name</label>
              <input type="text" id="editName" value="<?= e($metrics['user']['name']) ?>" required style="width: 100%; border: 1.5px solid rgba(26,20,18,0.15); border-radius: 10px; padding: 0.75rem; box-sizing: border-box; font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1rem;">
              <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Email Address</label>
              <input type="email" id="editEmail" value="<?= e($metrics['user']['email']) ?>" placeholder="name@domain.com" style="width: 100%; border: 1.5px solid rgba(26,20,18,0.15); border-radius: 10px; padding: 0.75rem; box-sizing: border-box; font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
              <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Birthday (for birthday gift rewards)</label>
              <input type="date" id="editDob" value="<?= e($metrics['user']['dob']) ?>" style="width: 100%; border: 1.5px solid rgba(26,20,18,0.15); border-radius: 10px; padding: 0.75rem; box-sizing: border-box; font-size: 0.95rem;">
            </div>

            <div style="display: flex; gap: 0.75rem;">
              <button type="button" onclick="closeEditProfileModal()" style="flex: 1; background: rgba(26,20,18,0.06); border: none; border-radius: 10px; padding: 0.75rem; font-weight: 600; cursor: pointer;">Cancel</button>
              <button type="submit" id="btnSaveProfile" style="flex: 1; background: var(--text-espresso, #1A1412); color: #fff; border: none; border-radius: 10px; padding: 0.75rem; font-weight: 600; cursor: pointer;">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

    <?php endif; ?>

  </div>
</main>

<script>
let currentPhone = '';
let resendInterval = null;

async function handleSendOtp(e) {
  e.preventDefault();
  const phoneInput = document.getElementById('inputPhone');
  const nameInput = document.getElementById('inputName');
  const btn = document.getElementById('btnSendOtp');
  const errorEl = document.getElementById('phoneError');
  
  errorEl.style.display = 'none';
  btn.disabled = true;
  btn.innerHTML = '<span>Sending Code...</span>';

  currentPhone = phoneInput.value.trim();
  const patronName = nameInput ? nameInput.value.trim() : '';

  try {
    const res = await fetch('/api/auth_otp.php?action=send_otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: currentPhone, name: patronName })
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('otpStepPhone').style.display = 'none';
      document.getElementById('otpStepVerify').style.display = 'block';
      document.getElementById('displayTargetPhone').textContent = '+91 ' + currentPhone;

      if (data.data && data.data.mock_otp) {
        document.getElementById('devOtpBox').style.display = 'block';
        document.getElementById('mockOtpCode').textContent = data.data.mock_otp;
      }

      startResendCountdown(30);
      document.getElementById('inputOtp').focus();
    } else {
      errorEl.textContent = data.message || 'Failed to send OTP code.';
      errorEl.style.display = 'block';
    }
  } catch (err) {
    errorEl.textContent = 'Network error. Please check your connection.';
    errorEl.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span>Send Verification Code</span> <span>→</span>';
  }
}

function autoFillMockOtp() {
  const code = document.getElementById('mockOtpCode').textContent.trim();
  document.getElementById('inputOtp').value = code;
  document.getElementById('formVerifyOtp').requestSubmit();
}

async function handleVerifyOtp(e) {
  e.preventDefault();
  const otpInput = document.getElementById('inputOtp');
  const btn = document.getElementById('btnVerifyOtp');
  const errorEl = document.getElementById('otpError');

  errorEl.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Verifying...';

  try {
    const res = await fetch('/api/auth_otp.php?action=verify_otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: currentPhone, otp: otpInput.value.trim() })
    });
    const data = await res.json();

    if (data.success) {
      window.location.reload();
    } else {
      errorEl.textContent = data.message || 'Invalid verification code.';
      errorEl.style.display = 'block';
      otpInput.select();
    }
  } catch (err) {
    errorEl.textContent = 'Network error during verification.';
    errorEl.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Verify & Enter Profile Hub';
  }
}

function backToPhoneStep() {
  if (resendInterval) clearInterval(resendInterval);
  document.getElementById('otpStepVerify').style.display = 'none';
  document.getElementById('otpStepPhone').style.display = 'block';
}

function startResendCountdown(seconds) {
  if (resendInterval) clearInterval(resendInterval);
  let remaining = seconds;
  const timerEl = document.getElementById('resendTimer');
  const textEl = document.getElementById('resendCountdownText');
  const btnEl = document.getElementById('btnResendOtp');

  textEl.style.display = 'inline';
  btnEl.style.display = 'none';
  timerEl.textContent = remaining;

  resendInterval = setInterval(() => {
    remaining--;
    timerEl.textContent = remaining;
    if (remaining <= 0) {
      clearInterval(resendInterval);
      textEl.style.display = 'none';
      btnEl.style.display = 'inline';
    }
  }, 1000);
}

async function resendOtp() {
  const btnEl = document.getElementById('btnResendOtp');
  btnEl.textContent = 'Sending...';
  try {
    const res = await fetch('/api/auth_otp.php?action=send_otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: currentPhone })
    });
    const data = await res.json();
    if (data.success) {
      if (data.data && data.data.mock_otp) {
        document.getElementById('mockOtpCode').textContent = data.data.mock_otp;
      }
      startResendCountdown(30);
    }
  } catch (err) {
    alert('Failed to resend code.');
  } finally {
    btnEl.textContent = 'Resend Verification Code';
  }
}

async function handleLogout() {
  if (!confirm('Are you sure you want to sign out of your Cielo Patron profile?')) return;
  try {
    await fetch('/api/auth_otp.php?action=logout', { method: 'POST' });
    window.location.reload();
  } catch (err) {
    window.location.href = '/';
  }
}

function applyCouponToCart(code) {
  try {
    sessionStorage.setItem('cielo_auto_coupon', code);
    alert('Coupon ' + code + ' applied! Redirecting to menu...');
    window.location.href = '/menu';
  } catch (e) {
    window.location.href = '/menu';
  }
}

function openEditProfileModal() {
  const modal = document.getElementById('modalEditProfile');
  if (modal) modal.style.display = 'flex';
}

function closeEditProfileModal() {
  const modal = document.getElementById('modalEditProfile');
  if (modal) modal.style.display = 'none';
}

async function handleUpdateProfile(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSaveProfile');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  const payload = {
    name: document.getElementById('editName').value.trim(),
    email: document.getElementById('editEmail').value.trim(),
    dob: document.getElementById('editDob').value
  };

  try {
    const res = await fetch('/api/auth_otp.php?action=update_profile', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.message || 'Failed to update profile.');
    }
  } catch (err) {
    alert('Network error while updating profile.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Changes';
  }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

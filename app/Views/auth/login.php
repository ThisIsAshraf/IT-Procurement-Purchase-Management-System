<?php

/**
 * @var string|null $error
 */

use App\Services\SettingsService;

$error = $error ?? null;
$s = SettingsService::all();
$appTitle = $s['app_title'] ?? 'NSG Procurement';
$headline = $s['login_headline'] ?? 'Enterprise Procurement Intelligence';
$subtitle = $s['login_subtitle'] ?? 'Streamlined sourcing, multi-branch approvals, vendor governance, and audit compliance for Noor Staffing Group.';
$footerNote = $s['login_footer_note'] ?? 'Authorized Personnel Only. All activities are monitored and audited.';
$showLogo = ($s['show_logo'] ?? '1') === '1';
$logoPath = $s['company_logo_path'] ?? '';
$logoH = (int)($s['logo_max_height'] ?? 38);
$logoW = (int)($s['logo_max_width'] ?? 170);
$googleOn = ($s['sso_google_enabled'] ?? '0') === '1';
$msOn = ($s['sso_microsoft_enabled'] ?? '0') === '1';
?>
<div class="split-login">
    <div class="split-login-brand">
        <div class="brand-top">
            <?php if ($showLogo && $logoPath !== ''): ?>
                <div class="logo-box">
                    <img src="<?= e(url('/' . ltrim($logoPath, '/'))) ?>" alt="<?= e($appTitle) ?>"
                        style="max-height: <?= $logoH ?>px; max-width: <?= $logoW ?>px;">
                </div>
            <?php else: ?>
                <div class="logo-box text-logo">
                    <span class="nsg-mark">NOOR</span>
                    <span class="nsg-sub">STAFFING GROUP</span>
                </div>
            <?php endif; ?>

            <div class="pill-badge">
                <span>&#9679;</span> Enterprise Procurement Management
            </div>

            <h1><?= e($headline) ?></h1>
            <p class="lead"><?= e($subtitle) ?></p>
        </div>

        <div class="brand-bottom">
            <span>&#9638; Multi-Branch Sourcing</span>
            <span>&#9745; 3-Way Invoice Match</span>
            <span>&#128737; Immutable Audit Trail</span>
        </div>
    </div>

    <div class="split-login-form">
        <div class="login-box">
            <h2>Sign In</h2>
            <p class="muted">Sign in with your approved account to access the console.</p>

            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($googleOn || $msOn): ?>
                <div class="sso-stack">
                    <?php if ($googleOn): ?>
                        <a href="<?= e(url('/oauth/google')) ?>" class="sso-btn">
                            <svg width="18" height="18" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                            </svg>
                            Continue with Google
                        </a>
                    <?php endif; ?>
                    <?php if ($msOn): ?>
                        <a href="<?= e(url('/oauth/microsoft')) ?>" class="sso-btn">
                            <svg width="18" height="18" viewBox="0 0 21 21">
                                <rect x="1" y="1" width="9" height="9" fill="#f25022" />
                                <rect x="1" y="11" width="9" height="9" fill="#00a4ef" />
                                <rect x="11" y="1" width="9" height="9" fill="#7fba00" />
                                <rect x="11" y="11" width="9" height="9" fill="#ffb900" />
                            </svg>
                            Continue with Microsoft
                        </a>
                    <?php endif; ?>
                </div>
                <div class="divider"><span>OR SIGN IN WITH PASSWORD</span></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>">
                <?= csrf_field() ?>
                <label>Email Address
                    <div class="input-icon-wrap">
                        <span class="field-icon">&#9993;</span>
                        <input type="email" name="email" placeholder="name@noorstaffing.com" required autocomplete="username">
                    </div>
                </label>

                <label>Password
                    <div class="input-icon-wrap">
                        <span class="field-icon">&#128274;</span>
                        <input type="password" name="password" placeholder="••••••••••••" required autocomplete="current-password">
                    </div>
                </label>

                <button class="btn primary full-btn">Sign In to Portal &rarr;</button>
            </form>

            <p class="security-note">&#128737; <?= e($footerNote) ?></p>
        </div>
    </div>
</div>
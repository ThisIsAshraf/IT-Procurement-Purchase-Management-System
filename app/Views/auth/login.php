<?php

/**
 * @var string|null $error
 */
$error = $error ?? null;
?>
<section class="auth-card">
    <h1>Sign in</h1>
    <p class="muted">Noor Staffing Group · Procurement Management</p>
    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/login')) ?>">
        <?= csrf_field() ?>
        <label>Email address
            <input type="email" name="email" required autocomplete="username">
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn primary">Sign in</button>
    </form>
    <p class="muted small">An administrator account must be created using the CLI setup script.</p>
</section>
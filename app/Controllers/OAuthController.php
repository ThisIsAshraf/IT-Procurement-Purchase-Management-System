<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\SettingsService;

final class OAuthController extends BaseController
{
    public function redirectProvider(Request $r): void
    {
        $provider = (string)($r->params()['provider'] ?? '');
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        if ($provider === 'google' && SettingsService::get('sso_google_enabled') === '1') {
            $params = http_build_query([
                'client_id'     => SettingsService::get('sso_google_client_id'),
                'redirect_uri'  => url('/oauth/callback/google'),
                'response_type' => 'code',
                'scope'         => 'openid email profile',
                'state'         => $state,
                'prompt'        => 'select_account',
            ]);
            redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        }

        if ($provider === 'microsoft' && SettingsService::get('sso_microsoft_enabled') === '1') {
            $tenant = SettingsService::get('sso_microsoft_tenant', 'common');
            $params = http_build_query([
                'client_id'     => SettingsService::get('sso_microsoft_client_id'),
                'redirect_uri'  => url('/oauth/callback/microsoft'),
                'response_type' => 'code',
                'scope'         => 'openid email profile User.Read',
                'state'         => $state,
            ]);
            redirect("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?" . $params);
        }

        flash('error', 'Selected SSO provider is disabled.');
        redirect('/login');
    }

    public function callback(Request $r): void
    {
        $provider = (string)($r->params()['provider'] ?? '');
        $code = (string)$r->input('code', '');
        $state = (string)$r->input('state', '');

        if ($code === '' || $state === '' || !hash_equals((string)($_SESSION['oauth_state'] ?? ''), $state)) {
            flash('error', 'Invalid or expired SSO authentication state.');
            redirect('/login');
        }
        unset($_SESSION['oauth_state']);

        try {
            $email = $provider === 'google'
                ? $this->fetchGoogleEmail($code)
                : $this->fetchMicrosoftEmail($code);

            if ($email === '') {
                throw new \RuntimeException('SSO provider did not return an email address.');
            }

            $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || $user['status'] !== 'active') {
                throw new \RuntimeException("Account '{$email}' is not registered or active in NSG Procurement.");
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
            \audit_log('sso_login_success', 'users', (int)$user['id'], null, ['provider' => $provider]);
            redirect('/');
        } catch (\Throwable $e) {
            flash('error', 'SSO sign-in failed: ' . $e->getMessage());
            redirect('/login');
        }
    }

    private function fetchGoogleEmail(string $code): string
    {
        $tokenResp = $this->httpPostForm('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => SettingsService::get('sso_google_client_id'),
            'client_secret' => SettingsService::get('sso_google_client_secret'),
            'redirect_uri'  => url('/oauth/callback/google'),
            'grant_type'    => 'authorization_code',
        ]);
        $accessToken = (string)($tokenResp['access_token'] ?? '');
        if ($accessToken === '') {
            throw new \RuntimeException('Failed to exchange Google authorization code.');
        }
        $info = $this->httpGetJson('https://www.googleapis.com/oauth2/v2/userinfo', $accessToken);
        return strtolower(trim((string)($info['email'] ?? '')));
    }

    private function fetchMicrosoftEmail(string $code): string
    {
        $tenant = SettingsService::get('sso_microsoft_tenant', 'common');
        $tokenResp = $this->httpPostForm("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
            'code'          => $code,
            'client_id'     => SettingsService::get('sso_microsoft_client_id'),
            'client_secret' => SettingsService::get('sso_microsoft_client_secret'),
            'redirect_uri'  => url('/oauth/callback/microsoft'),
            'grant_type'    => 'authorization_code',
        ]);
        $accessToken = (string)($tokenResp['access_token'] ?? '');
        if ($accessToken === '') {
            throw new \RuntimeException('Failed to exchange Microsoft authorization code.');
        }
        $info = $this->httpGetJson('https://graph.microsoft.com/v1.0/me', $accessToken);
        return strtolower(trim((string)($info['mail'] ?? $info['userPrincipalName'] ?? '')));
    }

    private function httpPostForm(string $url, array $fields): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($fields),
                'timeout' => 15,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        return json_decode((string)$raw, true) ?: [];
    }

    private function httpGetJson(string $url, string $bearer): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer {$bearer}\r\n",
                'timeout' => 15,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        return json_decode((string)$raw, true) ?: [];
    }
}

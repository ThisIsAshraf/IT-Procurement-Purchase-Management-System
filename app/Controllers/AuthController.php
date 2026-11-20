<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Models\AuthModel;

final class AuthController extends BaseController
{
    public function show(Request $r): void
    {
        if (current_user()) {
            redirect('/');
        }
        $this->view('auth/login', ['error' => flash('error')]);
    }

    public function login(Request $r): void
    {
        $this->csrf();
        $email = filter_var(trim((string)$r->input('email')), FILTER_VALIDATE_EMAIL);
        $password = (string)$r->input('password');

        $model = new AuthModel();
        $u = $email ? $model->byEmail($email) : null;

        if (!$u || !password_verify($password, $u['password_hash'])) {
            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        $userId = (int)$u['id'];
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'            => $userId,
            'name'          => $u['full_name'],
            'email'         => $u['email'],
            'employee_id'   => $u['employee_id'] ? (int)$u['employee_id'] : null,
            'branch_id'     => $u['branch_id'] ? (int)$u['branch_id'] : null,
            'department_id' => $u['department_id'] ? (int)$u['department_id'] : null,
            'branch_scopes' => $model->branchScopes($userId),
        ];
        $_SESSION['permissions'] = $model->permissions($userId);
        $_SESSION['roles'] = $model->roles($userId);

        Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$userId]);
        \audit_log('login', 'users', $userId);
        redirect('/');
    }

    public function logout(Request $r): void
    {
        $this->csrf();
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('/login');
    }
}

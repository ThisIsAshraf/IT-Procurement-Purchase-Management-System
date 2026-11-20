<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\ApprovalWorkflowController;
use App\Controllers\ApprovalController;

return static function (Router $r): void {
    $r->get('/approval-workflows', [ApprovalWorkflowController::class, 'index']);
    $r->post('/approval-workflows', [ApprovalWorkflowController::class, 'store']);

    $r->get('/approvals', [ApprovalController::class, 'index']);
    $r->post('/approvals/act', [ApprovalController::class, 'act']);
};

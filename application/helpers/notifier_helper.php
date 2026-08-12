<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Centralized error notifier.
 *
 * Auto-enriches every notification with org, user, branch, and request info
 * pulled from the active JWT session. Call it from any catch block:
 *
 *   notifyError('Invoices::index', $e);
 *   notifyError('Payments::save', $e, ['TransUID' => $transUID]);
 *
 * To disable a channel, comment out its line — no controller code changes needed.
 *
 * @param string         $location  Human-readable label: "Controller::method"
 * @param Exception|null $e         The caught exception (optional)
 * @param array          $context   Extra key→value pairs (e.g. TransUID, OrgUID override)
 * @return void
 */
function notifyError(string $location, ?Exception $e = null, array $context = []): void
{
    $CI  = &get_instance();
    $jwt = $CI->pageData['JwtData'] ?? null;

    $auto = [
        'Organisation' => $jwt ? ('#' . ($jwt->Org->OrgUID ?? '?') . '  ' . ($jwt->Org->OrgName ?? '—')) : '—',
        'Org Branch'   => $jwt ? ('#' . ($jwt->Org->BranchUID ?? '?'))                                    : '—',
        'Triggered By' => $jwt ? ('#' . ($jwt->User->UserUID ?? '?') . '  ' . ($jwt->User->Name ?? '—'))  : '—',
        'HTTP Method'  => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    ];

    // Caller-supplied context wins on key clash
    $merged = array_merge($auto, $context);

    // ── Telegram ──────────────────────────────────────────────────────────────
    $CI->load->library('telegramnotifier');
    Telegramnotifier::error($location, $e, $merged);
    // ─────────────────────────────────────────────────────────────────────────
}


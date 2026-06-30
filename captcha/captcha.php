<?php
// ============================================================
//  captcha/captcha.php  –  Session-based math CAPTCHA helper
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a new math CAPTCHA question, store the answer in
 * the session, and return the question string.
 *
 * @param  string $prefix  Session key prefix (e.g. 'reg' | 'login')
 * @return string           e.g. "7 + 5 = ?"
 */
function captcha_generate(string $prefix = 'captcha'): string
{
    $a = random_int(1, 15);
    $b = random_int(1, 15);
    $ops = ['+', '-', '×'];
    $op  = $ops[random_int(0, 2)];

    switch ($op) {
        case '-':
            // Keep result positive
            if ($b > $a) [$a, $b] = [$b, $a];
            $answer = $a - $b;
            break;
        case '×':
            // Keep numbers small for legibility
            $a = random_int(1, 9);
            $b = random_int(1, 9);
            $answer = $a * $b;
            break;
        default: // '+'
            $answer = $a + $b;
    }

    $_SESSION[$prefix . '_captcha_answer'] = $answer;
    return "{$a} {$op} {$b} = ?";
}

/**
 * Validate the user-submitted answer against the session.
 *
 * @param  string $prefix
 * @param  string $submitted  Raw user input
 * @return bool
 */
function captcha_validate(string $prefix, string $submitted): bool
{
    $key = $prefix . '_captcha_answer';

    if (!isset($_SESSION[$key])) {
        return false;
    }

    $valid = (int) $submitted === (int) $_SESSION[$key];

    // Invalidate after first check (one-time use)
    unset($_SESSION[$key]);

    return $valid;
}
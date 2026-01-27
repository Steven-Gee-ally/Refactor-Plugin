<?php
// simulate_reset.php
// Usage: php simulate_reset.php <user_id> <user_email> <user_login>
if ($argc < 4) {
    echo "Usage: php simulate_reset.php <user_id> <user_email> <user_login>\n";
    exit(1);
}

$user_id = intval($argv[1]);
$user_email = $argv[2];
$user_login = $argv[3];

try {
    $token = bin2hex(random_bytes(16));
} catch (Exception $e) {
    $token = bin2hex(openssl_random_pseudo_bytes(16));
}

$token_key = 'afc_reset_token_' . $token;
$expiry = 15 * 60; // 15 minutes
$wp_home = rtrim(getenv('WP_HOME') ?: 'http://example.test', '/');
$link = $wp_home . '/?afc_reset_token=' . $token;

$subject = sprintf('Set your %s password (expires in 15 minutes)', getenv('AFC_SITE_LABEL') ?: 'AFCGlide');
$body = "Hi {$user_login},\n\nYour broker requested a password reset for your AFCGlide portal. Please click the link below to set a new password (expires in 15 minutes):\n\n{$link}\n\nIf you did not expect this, contact your broker.";

$output = [
    'token' => $token,
    'token_key' => $token_key,
    'user_id' => $user_id,
    'email' => $user_email,
    'link' => $link,
    'subject' => $subject,
    'body' => $body,
    'expiry_seconds' => $expiry,
    'set_transient_php' => "set_transient('{$token_key}', {$user_id}, {$expiry});",
    'wp_mail_call' => "wp_mail('{$user_email}', '{$subject}', <<<EOT\n{$body}\nEOT\n, ['Content-Type: text/plain; charset=UTF-8']);",
];

echo json_encode($output, JSON_PRETTY_PRINT) . PHP_EOL;

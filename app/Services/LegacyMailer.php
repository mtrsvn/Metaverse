<?php

namespace App\Services;

use App\Support\ProductSizeInventory;
use Illuminate\Support\Facades\Mail;

class LegacyMailer
{
    public static function sendOtpEmail(string $toEmail, string $toName, string $otp): array
    {
        if (self::isDevMode()) {
            self::logTo(storage_path('logs/otp_logs.txt'), sprintf(
                "[%s] Email: %s | Name: %s | OTP: %s\n",
                date('Y-m-d H:i:s'),
                $toEmail,
                $toName,
                $otp
            ));
            return ['success' => true, 'error' => null, 'dev_mode' => true];
        }

        $subject = 'Your OTP Code - Metaverse Records';
        $message = "Hello {$toName},\n\n";
        $message .= "Your verification code is: {$otp}\n\n";
        $message .= "This code will expire in 10 minutes.\n\n";
        $message .= "If you did not request this code, please ignore this email.\n\n";
        $message .= "Best regards,\nMetaverse Records Team";
        $sent = self::sendViaMailer($toEmail, $toName, $subject, $message);
        if ($sent['success']) {
            return ['success' => true, 'error' => null];
        }

        self::logTo(storage_path('logs/otp_logs.txt'), sprintf(
            "[%s] OTP send failed, fallback log only. Email: %s | Name: %s | OTP: %s\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $toName,
            $otp
        ));

        if (self::isLocalEnvironment()) {
            return ['success' => true, 'error' => null, 'dev_mode' => 'local-fallback'];
        }

        return ['success' => false, 'error' => $sent['error'] ?: 'Email could not be sent.'];
    }

    public static function sendPurchaseConfirmation(string $toEmail, string $toName, array $items, float $total): array
    {
        if (self::isDevMode()) {
            self::logTo(storage_path('logs/otp_logs.txt'), sprintf(
                "[%s] Purchase Email: %s | Name: %s | Total: $%.2f\n",
                date('Y-m-d H:i:s'),
                $toEmail,
                $toName,
                $total
            ));
            return ['success' => true, 'error' => null, 'dev_mode' => true];
        }

        $subject = 'Your Purchase is Being Shipped - Metaverse Records';
        $message = "Hello {$toName},\n\n";
        $message .= "Great news! Your purchase is being shipped.\n\n";
        $message .= "Order Details:\n";
        foreach ($items as $item) {
            $name = $item['name'] ?? 'Item';
            $size = ProductSizeInventory::normalizeSize((string) ($item['size'] ?? ''));
            $qty = (int)($item['quantity'] ?? 1);
            $price = number_format((float)($item['price'] ?? 0), 2);
            $sizeText = $size ? ' - ' . ProductSizeInventory::label($size) : '';
            $message .= "{$name}{$sizeText} (x{$qty}) - $" . $price . " = $" . number_format(((float)($item['price'] ?? 0)) * $qty, 2) . "\n";
        }
        $message .= "\nTotal: $" . number_format($total, 2) . "\n\n";
        $message .= "Your items will be delivered soon. Thank you for shopping with Metaverse Records!\n\n";
        $message .= "Best regards,\nMetaverse Records Team";

        $sent = self::sendViaMailer($toEmail, $toName, $subject, $message);
        if ($sent['success']) {
            return ['success' => true, 'error' => null];
        }

        return ['success' => false, 'error' => $sent['error'] ?: 'Email could not be sent.'];
    }

    public static function sendPurchaseRejection(string $toEmail, string $toName, array $items, float $total = 0.0, string $reason = ''): array
    {
        if (self::isDevMode()) {
            self::logTo(storage_path('logs/mail_dev.log'), sprintf(
                "[%s] REJECT to %s <%s> | Items: %d | Total: $%0.2f | Reason: %s\n",
                date('Y-m-d H:i:s'),
                $toName,
                $toEmail,
                count($items),
                (float)$total,
                $reason !== '' ? $reason : 'n/a'
            ));
            return ['success' => true, 'error' => null, 'dev_mode' => true];
        }

        $subject = 'Order Update - Metaverse Records';
        $message = "Hello {$toName},\n\nWe are sorry to inform you that your order could not be processed.";
        if ($reason !== '') {
            $message .= "\nReason: {$reason}";
        }
        $message .= "\n\nOrder Details:\n";
        foreach ($items as $item) {
            $name = $item['name'] ?? 'Item';
            $size = ProductSizeInventory::normalizeSize((string) ($item['size'] ?? ''));
            $qty = (int)($item['quantity'] ?? 1);
            $price = number_format((float)($item['price'] ?? 0), 2);
            $sizeText = $size ? ' - ' . ProductSizeInventory::label($size) : '';
            $message .= "{$name}{$sizeText} (x{$qty}) - $" . $price . "\n";
        }
        if ($total > 0) {
            $message .= "\nRequested Total: $" . number_format($total, 2);
        }
        $message .= "\n\nIf you have any questions, please reply to this email.\n\nBest regards,\nCartify Team";
        $message = str_replace('Cartify Team', 'Metaverse Records Team', $message);

        $sent = self::sendViaMailer($toEmail, $toName, $subject, $message);
        if ($sent['success']) {
            return ['success' => true, 'error' => null];
        }

        return ['success' => false, 'error' => $sent['error'] ?: 'Rejection email could not be sent.'];
    }

    private static function sendViaMailer(string $toEmail, string $toName, string $subject, string $body): array
    {
        $fromAddress = config('mail.from.address', 'no-reply@cartify.local');
        $fromName = config('mail.from.name', 'Cartify');

        try {
            Mail::raw($body, function ($message) use ($toEmail, $toName, $subject, $fromAddress, $fromName) {
                $message->to($toEmail, $toName)->subject($subject)->from($fromAddress, $fromName);
            });
            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private static function isDevMode(): bool
    {
        $env = getenv('MAIL_DEV_MODE');
        if ($env === false) {
            return false;
        }
        return filter_var($env, FILTER_VALIDATE_BOOLEAN);
    }

    private static function isLocalEnvironment(): bool
    {
        if (function_exists('app') && app()->environment('local')) {
            return true;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $server = $_SERVER['SERVER_NAME'] ?? '';
        $addr = $_SERVER['REMOTE_ADDR'] ?? '';

        return in_array($host, ['localhost', '127.0.0.1'], true)
            || in_array($server, ['localhost', '127.0.0.1'], true)
            || in_array($addr, ['127.0.0.1', '::1'], true);
    }

    private static function logTo(string $path, string $message): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($path, $message, FILE_APPEND);
    }
}

<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Member;
use Carbon\CarbonImmutable;

class WhatsAppService
{
    public function getBookingUrl(Member $member, ?string $activity = null): ?string
    {
        $phone = $this->formatPhone((string) config('gym.booking_whatsapp'));
        if (! preg_match('/^[1-9][0-9]{7,14}$/D', $phone)) {
            return null;
        }
        $activityLabel = $activity === 'gym' ? 'Gym / PT' : ($activity === 'pilates' ? 'Pilates' : 'Gym / Pilates');
        $message = "Halo admin, saya {$member->full_name} ({$member->member_number}). Saya ingin booking {$activityLabel}.\n".
            "Tanggal dan jam yang diinginkan: ...\nMohon pengecekan paket, ketersediaan jadwal, dan penugasan PT oleh management.";

        return $this->buildUrl($phone, $message);
    }

    /**
     * Format any valid Indonesian phone number into WhatsApp international digit format (e.g. 628123456789).
     */
    public function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '08')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Build standard wa.me URL with pre-filled encoded text.
     */
    public function buildUrl(string $phone, string $message): string
    {
        $cleanPhone = $this->formatPhone($phone);

        return 'https://wa.me/'.$cleanPhone.'?text='.rawurlencode($message);
    }

    /**
     * Generate pre-formatted payment receipt message for WhatsApp.
     */
    public function getReceiptMessage(Invoice $invoice): string
    {
        $member = $invoice->member;
        $gymName = config('gym.name', 'SPARTA Gym & Pilates');
        $tz = config('gym.timezone', 'Asia/Makassar');
        $payment = $invoice->payment;
        $methodLabel = $payment ? (config('gym.payment_methods')[$payment->method] ?? strtoupper($payment->method)) : 'Menunggu Pembayaran';
        $isPaid = $invoice->status === 'paid' && $payment !== null;
        $statusLabel = $isPaid ? 'LUNAS' : 'BELUM DIBAYAR';
        $paidDate = $payment ? $payment->paid_at->timezone($tz)->format('d M Y, H:i') : '—';
        $receiptUrl = route('invoices.show', $invoice);

        return "🧾 *STRUK PEMBAYARAN - {$gymName}*\n".
            "-----------------------------------------\n".
            "No. Invoice : *{$invoice->invoice_number}*\n".
            "Nama Member : {$member->full_name} ({$member->member_number})\n".
            "Paket       : {$invoice->snapshot['name']}\n".
            "Durasi      : {$invoice->snapshot['duration_value']} {$invoice->snapshot['duration_unit']}\n".
            'Total Bayar : *Rp '.number_format($invoice->total_idr, 0, ',', '.')."*\n".
            "Metode      : {$methodLabel}\n".
            "Tanggal     : {$paidDate}\n".
            "Status      : *{$statusLabel}*\n".
            "-----------------------------------------\n".
            "Lihat e-Struk Resmi:\n{$receiptUrl}\n\n".
            "Terima kasih telah berlatih bersama {$gymName}! 💪";
    }

    /**
     * Generate WhatsApp link for payment receipt.
     */
    public function getReceiptUrl(Invoice $invoice): string
    {
        return $this->buildUrl($invoice->member->phone, $this->getReceiptMessage($invoice));
    }

    /**
     * Generate member card / barcode message for WhatsApp.
     */
    public function getMemberPassMessage(Member $member): string
    {
        $gymName = config('gym.name', 'SPARTA Gym & Pilates');
        $tz = config('gym.timezone', 'Asia/Makassar');
        $period = $member->currentPeriod;
        $category = $member->customer_type_label;
        $periodInfo = $period
            ? $period->invoice->snapshot['name'].' (s.d. '.$period->ends_at->timezone($tz)->format('d M Y').')'
            : 'Belum ada paket aktif';
        $passUrl = $member->user_id ? route('portal.card') : null;

        return "🪪 *KARTU ANGGOTA DIGITAL - {$gymName}*\n".
            "-----------------------------------------\n".
            "Halo Kak *{$member->full_name}*,\n".
            "Berikut akses keanggotaan Anda di {$gymName}:\n\n".
            "No. Member : *{$member->member_number}*\n".
            "Kategori   : {$category}\n".
            "Paket      : {$periodInfo}\n".
            "-----------------------------------------\n".
            ($passUrl ? "Akses QR Code (masuk dengan akun Anda):\n{$passUrl}\n\n" : "Sebutkan nomor anggota di front desk. Hubungi staf untuk aktivasi akun portal.\n\n").
            'Tunjukkan nomor atau QR Code Anda di meja frontdesk saat check-in. Selamat berlatih!';
    }

    /**
     * Generate WhatsApp link for member card / barcode.
     */
    public function getMemberPassUrl(Member $member): string
    {
        return $this->buildUrl($member->phone, $this->getMemberPassMessage($member));
    }

    /**
     * Generate expiration reminder message for WhatsApp.
     */
    public function getExpiryReminderMessage(Member $member): string
    {
        $gymName = config('gym.name', 'SPARTA Gym & Pilates');
        $tz = config('gym.timezone', 'Asia/Makassar');
        $period = $member->currentPeriod;

        if (! $period) {
            return "Halo Kak *{$member->full_name}*,\n".
                "Keanggotaan Anda di {$gymName} saat ini belum aktif. Ayo aktifkan kembali paket latihan Anda di frontdesk!";
        }

        $planName = $period->invoice->snapshot['name'] ?? 'Membership';
        $endDate = $period->ends_at->timezone($tz)->format('d M Y');
        $now = CarbonImmutable::now($tz);
        $diffDays = (int) ceil($now->diffInDays($period->ends_at->timezone($tz), false));
        $remainingText = $diffDays > 0 ? "sisa *{$diffDays} hari lagi*" : '*berakhir hari ini*';

        return "⏰ *PENGINGAT MASA KEANGGOTAAN - {$gymName}*\n".
            "-----------------------------------------\n".
            "Halo Kak *{$member->full_name}*,\n\n".
            "Masa aktif paket *{$planName}* Anda di {$gymName} akan berakhir pada:\n".
            "🗓 *{$endDate}* ({$remainingText})\n\n".
            "Yuk lakukan perpanjangan lebih awal agar jadwal dan rutinitas latihan Anda tidak terputus. Sisa hari paket lama Anda tetap tersimpan utuh!\n\n".
            'Hubungi staf resepsionis kami untuk perpanjangan paket. Terima kasih! 🙏';
    }

    /**
     * Generate WhatsApp link for expiration reminder.
     */
    public function getExpiryReminderUrl(Member $member): string
    {
        return $this->buildUrl($member->phone, $this->getExpiryReminderMessage($member));
    }
}

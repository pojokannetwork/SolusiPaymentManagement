<?php

class WhatsAppNotificationService
{
    private const TEMPLATE_KEYS = [
        'registration' => 'whatsapp_template_registration',
        'due' => 'whatsapp_template_invoice_due',
        'general' => 'whatsapp_template_general_message',
    ];

    private const DEFAULT_TEMPLATES = [
        'registration' => "Halo {customer_name},\n\nSelamat! Akun internet Anda dengan ID {customer_code} telah aktif.\nPaket: {package}\nTanggal Aktivasi: {registration_date}\n\nTerima kasih telah memilih {company_name}.",
        'due' => "Hai {customer_name},\n\nIni pengingat tagihan {invoice_number} sebesar {invoice_total} yang jatuh tempo pada {due_date}.\nMohon lakukan pembayaran tepat waktu agar layanan tetap aktif.\n\nTerima kasih,\n{company_name}",
        'general' => "Halo {customer_name},\n\n{custom_message}\n\nSalam,\n{company_name}",
    ];

    /** -------------------- Templates -------------------- */

    public static function getTemplates(): array
    {
        $templates = [];
        foreach (self::TEMPLATE_KEYS as $type => $settingKey) {
            $templates[$type] = getSetting($settingKey, self::DEFAULT_TEMPLATES[$type] ?? '');
        }
        return $templates;
    }

    public static function saveTemplates(array $templates): void
    {
        foreach (self::TEMPLATE_KEYS as $type => $settingKey) {
            if (array_key_exists($type, $templates)) {
                $value = trim((string) $templates[$type]);
                if ($value === '') {
                    $value = self::DEFAULT_TEMPLATES[$type] ?? '';
                }
                setSetting($settingKey, $value);
            }
        }
    }

    public static function getPlaceholders(): array
    {
        return [
            'customer' => [
                '{customer_name}' => 'Nama pelanggan',
                '{customer_code}' => 'Kode pelanggan / ID',
                '{package}' => 'Nama paket',
                '{phone}' => 'Nomor WhatsApp pelanggan',
                '{address}' => 'Alamat pelanggan',
                '{registration_date}' => 'Tanggal registrasi',
            ],
            'invoice' => [
                '{invoice_number}' => 'Nomor faktur',
                '{invoice_date}' => 'Tanggal faktur',
                '{invoice_total}' => 'Total tagihan',
                '{due_date}' => 'Tanggal jatuh tempo',
                '{billing_period}' => 'Periode penagihan',
            ],
            'global' => [
                '{company_name}' => 'Nama perusahaan/aplikasi',
                '{custom_message}' => 'Pesan kustom (untuk notifikasi umum)',
            ],
        ];
    }

    /** -------------------- Preview Helpers -------------------- */

    public static function prepareRegistrationMessage(int $customerId): array
    {
        $payload = self::buildRegistrationPayload($customerId);
        return self::buildPreviewPayload($payload['phone'], $payload['message']);
    }

    public static function prepareDueReminder(int $invoiceId, ?string $customTemplate = null): array
    {
        $payload = self::buildDueReminderPayload($invoiceId, $customTemplate);
        return self::buildPreviewPayload($payload['phone'], $payload['message']);
    }

    public static function prepareGeneralMessage(int $customerId, string $customMessage): array
    {
        $payload = self::buildGeneralPayload($customerId, $customMessage);
        return self::buildPreviewPayload($payload['phone'], $payload['message']);
    }

    /** -------------------- Queue Helpers -------------------- */

    public static function queueRegistrationMessage(int $customerId, ?string $scheduleAt = null): int
    {
        $payload = self::buildRegistrationPayload($customerId);
        return self::enqueueMessage($payload, 'registration', $scheduleAt, ['customer_id' => $customerId]);
    }

    public static function queueDueReminderMessage(int $invoiceId, ?string $scheduleAt = null): int
    {
        $payload = self::buildDueReminderPayload($invoiceId);
        return self::enqueueMessage($payload, 'invoice_due', $scheduleAt, ['invoice_id' => $invoiceId]);
    }

    public static function queueGeneralMessage(int $customerId, string $customMessage, ?string $scheduleAt = null): int
    {
        $payload = self::buildGeneralPayload($customerId, $customMessage);
        return self::enqueueMessage($payload, 'general', $scheduleAt, ['customer_id' => $customerId]);
    }

    /** -------------------- Data Builders -------------------- */

    private static function buildRegistrationPayload(int $customerId): array
    {
        $customer = self::loadCustomer($customerId);
        if (!$customer) {
            throw new InvalidArgumentException('Pelanggan tidak ditemukan.');
        }

        $template = self::getTemplates()['registration'];
        $context = self::buildCustomerContext($customer);
        $message = self::applyTemplate($template, $context);

        return [
            'phone' => $customer['telp'] ?? '',
            'message' => $message,
            'meta' => [
                'customer_id' => $customerId,
                'context' => $context,
            ],
        ];
    }

    private static function buildDueReminderPayload(int $invoiceId, ?string $customTemplate = null): array
    {
        $invoice = self::loadInvoice($invoiceId);
        if (!$invoice) {
            throw new InvalidArgumentException('Invoice tidak ditemukan.');
        }

        $customer = self::loadCustomer((int) $invoice['pelanggan_id']);
        if (!$customer) {
            throw new InvalidArgumentException('Pelanggan untuk invoice ini tidak ditemukan.');
        }

        $template = $customTemplate ?? self::getTemplates()['due'];
        $context = array_merge(
            self::buildCustomerContext($customer),
            self::buildInvoiceContext($invoice)
        );

        $message = self::applyTemplate($template, $context);

        return [
            'phone' => $customer['telp'] ?? '',
            'message' => $message,
            'meta' => [
                'invoice_id' => $invoiceId,
                'customer_id' => $customer['id'],
                'context' => $context,
            ],
        ];
    }

    private static function buildGeneralPayload(int $customerId, string $customMessage): array
    {
        $customer = self::loadCustomer($customerId);
        if (!$customer) {
            throw new InvalidArgumentException('Pelanggan tidak ditemukan.');
        }

        $template = self::getTemplates()['general'];
        $context = self::buildCustomerContext($customer);
        $context['{custom_message}'] = trim($customMessage);

        if ($context['{custom_message}'] === '') {
            throw new InvalidArgumentException('Pesan kustom tidak boleh kosong.');
        }

        $message = self::applyTemplate($template, $context);

        return [
            'phone' => $customer['telp'] ?? '',
            'message' => $message,
            'meta' => [
                'customer_id' => $customerId,
                'context' => $context,
            ],
        ];
    }

    /** -------------------- Persistence Helpers -------------------- */

    private static function enqueueMessage(array $payload, string $type, ?string $scheduleAt, array $meta): int
    {
        WhatsAppQueue::boot();

        return WhatsAppQueue::enqueue([
            'session_name' => 'default',
            'customer_id' => $meta['customer_id'] ?? null,
            'phone' => $payload['phone'] ?? '',
            'message' => $payload['message'] ?? '',
            'message_type' => $type,
            'meta' => $meta,
            'scheduled_at' => $scheduleAt ?? date('Y-m-d H:i:s'),
        ]);
    }

    private static function buildPreviewPayload(string $phone, string $message): array
    {
        return self::buildResponsePayload($phone, $message);
    }

    /** -------------------- Data Loaders -------------------- */

    private static function loadCustomer(int $customerId): ?array
    {
        global $db;
        return $db->fetchOne(
            "SELECT id, kode_pelanggan, nama, email, telp, alamat, paket, status, created_at
             FROM pelanggan WHERE id = ?",
            [$customerId]
        ) ?: null;
    }

    private static function loadInvoice(int $invoiceId): ?array
    {
        global $db;
        return $db->fetchOne(
            "SELECT f.*, p.nama AS customer_name, p.telp AS customer_phone, p.kode_pelanggan
             FROM faktur f
             JOIN pelanggan p ON p.id = f.pelanggan_id
             WHERE f.id = ?",
            [$invoiceId]
        ) ?: null;
    }

    /** -------------------- Context Builders -------------------- */

    private static function buildCustomerContext(array $customer): array
    {
        $companyName = defined('APP_DISPLAY_NAME') ? APP_DISPLAY_NAME : APP_NAME;

        return [
            '{customer_name}' => $customer['nama'] ?? '',
            '{customer_code}' => $customer['kode_pelanggan'] ?? '',
            '{package}' => $customer['paket'] ?? '-',
            '{phone}' => $customer['telp'] ?? '',
            '{address}' => $customer['alamat'] ?? '',
            '{registration_date}' => self::formatDate('d F Y', $customer['created_at'] ?? 'now'),
            '{company_name}' => $companyName,
        ];
    }

    private static function buildInvoiceContext(array $invoice): array
    {
        $period = '';
        if (!empty($invoice['tanggal'])) {
            $period = self::formatDate('F Y', $invoice['tanggal']);
        }

        $dueDate = !empty($invoice['jatuh_tempo'])
            ? self::formatDate('d F Y', $invoice['jatuh_tempo'])
            : '-';

        return [
            '{invoice_number}' => $invoice['nomor'] ?? '',
            '{invoice_date}' => !empty($invoice['tanggal'])
                ? self::formatDate('d F Y', $invoice['tanggal'])
                : '',
            '{invoice_total}' => isset($invoice['total'])
                ? formatCurrency((float) $invoice['total'])
                : formatCurrency(0),
            '{due_date}' => $dueDate,
            '{billing_period}' => $period,
        ];
    }

    /** -------------------- Formatting Helpers -------------------- */

    private static function applyTemplate(string $template, array $context): string
    {
        $message = $template;
        foreach ($context as $placeholder => $value) {
            $message = str_replace($placeholder, (string) $value, $message);
        }
        return preg_replace("/\n{3,}/", "\n\n", trim($message));
    }

    private static function buildResponsePayload(string $rawPhone, string $message): array
    {
        $phone = self::normalizePhone($rawPhone);
        if ($phone === '') {
            throw new InvalidArgumentException('Nomor WhatsApp pelanggan belum tersedia.');
        }

        $link = self::buildWhatsAppLink($phone, $message);

        try {
            $qr = generateQRCode($link, 320);
        } catch (Throwable $e) {
            $qr = null;
        }

        return [
            'phone' => $phone,
            'message' => $message,
            'link' => $link,
            'qr' => $qr,
        ];
    }

    private static function buildWhatsAppLink(string $phone, string $message): string
    {
        $encodedMessage = rawurlencode($message);
        return "https://wa.me/{$phone}?text={$encodedMessage}";
    }

    public static function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        $digits = preg_replace('/[^0-9+]/', '', $phone);
        if ($digits === null) {
            return '';
        }

        if (strpos($digits, '+') === 0) {
            $digits = substr($digits, 1);
        } elseif (strpos($digits, '0') === 0) {
            $digits = '62' . ltrim($digits, '0');
        }

        return trim($digits);
    }

    private static function formatDate(string $format, $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        if (!is_numeric($time)) {
            $timestamp = strtotime((string) $time);
        } else {
            $timestamp = (int) $time;
        }

        if (!$timestamp) {
            $timestamp = time();
        }

        $formatted = date($format, $timestamp);

        $monthsEn = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
        ];
        $monthsId = [
            'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember'
        ];

        $daysEn = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $daysId = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

        $formatted = str_replace($monthsEn, $monthsId, $formatted);
        $formatted = str_replace($daysEn, $daysId, $formatted);

        return $formatted;
    }
}


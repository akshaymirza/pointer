<?php
// includes/voucher_config.php - Multi-template Storage for Voucher Check & Proxy Configuration
require_once __DIR__ . '/../db.php';

const VOUCHER_CONFIG_FILE = __DIR__ . '/../voucher_config.json';

/**
 * Default sample templates if empty
 */
function parseItemsList($input) {
    if (is_array($input)) {
        return array_values(array_unique(array_filter(array_map('trim', $input))));
    }
    if (is_string($input)) {
        $items = preg_split('/[\r\n,]+/', $input);
        return array_values(array_unique(array_filter(array_map('trim', $items))));
    }
    return [];
}

/**
 * Default sample templates if empty
 */
function getDefaultVoucherTemplates() {
    return [
        [
            'id' => 'vt-tri-direct',
            'name' => 'Three (Tri) Voucher Check',
            'description' => 'Pengecekan status voucher Three langsung ke server API/Web.',
            'base_target_url' => 'https://example.com/check-voucher?voucher={voucher}',
            'default_sender' => '089523098721',
            'allowed_usernames' => ['089523098721'],
            'target_numbers' => ['0895639154834'],
            'success_keyword' => 'SUKSES',
            'success_custom_message' => 'Voucher Tri berhasil divalidasi dan aktif.',
            'pending_keyword' => 'menunggu',
            'pending_custom_message' => 'Voucher sedang dalam antrian / proses.',
            'failed_keyword' => 'GAGAL',
            'failed_custom_message' => 'Voucher Tri tidak valid atau sudah kadaluarsa.',
            'is_default' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 'vt-google-search-demo',
            'name' => 'Google Search Demo Target',
            'description' => 'Target demo pencarian web untuk pengujian respons.',
            'base_target_url' => 'https://www.google.com/search?q=kenapa+gemini+pro+3.1+di+{voucher}',
            'default_sender' => '',
            'allowed_usernames' => [],
            'target_numbers' => [],
            'success_keyword' => 'gemini',
            'success_custom_message' => 'Kata kunci berhasil ditemukan pada halaman web!',
            'pending_keyword' => 'menunggu',
            'pending_custom_message' => 'Voucher sedang dalam antrian / proses.',
            'failed_keyword' => 'tidak ditemukan',
            'failed_custom_message' => 'Halaman tidak memuat informasi yang dicari.',
            'is_default' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
}

/**
 * Ensure database table exists with multi-template schema
 */
function ensureVoucherTemplatesTable($pdo) {
    if (!$pdo) return;
    try {
        // Buat tabel jika belum ada
        $pdo->exec("CREATE TABLE IF NOT EXISTS `voucher_templates` (
            `id` VARCHAR(50) PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `description` TEXT NULL,
            `base_target_url` TEXT NOT NULL,
            `default_sender` VARCHAR(50) NULL,
            `allowed_usernames` TEXT NULL,
            `target_numbers` TEXT NULL,
            `success_keyword` VARCHAR(255) NULL,
            `success_custom_message` TEXT NULL,
            `pending_keyword` VARCHAR(255) NULL,
            `pending_custom_message` TEXT NULL,
            `failed_keyword` VARCHAR(255) NULL,
            `failed_custom_message` TEXT NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Pastikan kolom pending_keyword, pending_custom_message, default_sender, allowed_usernames & target_numbers ada jika tabel sudah terlanjur dibuat
        try {
            $pdo->exec("ALTER TABLE `voucher_templates` ADD COLUMN `default_sender` VARCHAR(50) NULL AFTER `base_target_url`");
        } catch (Exception $eCol) {}
        try {
            $pdo->exec("ALTER TABLE `voucher_templates` ADD COLUMN `allowed_usernames` TEXT NULL AFTER `default_sender`");
        } catch (Exception $eCol) {}
        try {
            $pdo->exec("ALTER TABLE `voucher_templates` ADD COLUMN `target_numbers` TEXT NULL AFTER `allowed_usernames`");
        } catch (Exception $eCol) {}
        try {
            $pdo->exec("ALTER TABLE `voucher_templates` ADD COLUMN `pending_keyword` VARCHAR(255) NULL AFTER `success_custom_message`");
            $pdo->exec("ALTER TABLE `voucher_templates` ADD COLUMN `pending_custom_message` TEXT NULL AFTER `pending_keyword`");
        } catch (Exception $eCol) {}

        // Periksa apakah tabel kosong, jika iya seed data
        $cnt = $pdo->query("SELECT COUNT(*) FROM `voucher_templates`")->fetchColumn();
        if ($cnt == 0) {
            $defaults = getDefaultVoucherTemplates();
            $stmt = $pdo->prepare("INSERT INTO `voucher_templates` 
                (`id`, `name`, `description`, `base_target_url`, `default_sender`, `allowed_usernames`, `target_numbers`, `success_keyword`, `success_custom_message`, `pending_keyword`, `pending_custom_message`, `failed_keyword`, `failed_custom_message`, `is_default`, `updated_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            foreach ($defaults as $d) {
                $stmt->execute([
                    $d['id'], $d['name'], $d['description'], $d['base_target_url'],
                    $d['default_sender'] ?? '',
                    json_encode($d['allowed_usernames'] ?? []),
                    json_encode($d['target_numbers'] ?? []),
                    $d['success_keyword'], $d['success_custom_message'],
                    $d['pending_keyword'] ?? 'menunggu', $d['pending_custom_message'] ?? 'Voucher sedang dalam antrian / proses.',
                    $d['failed_keyword'], $d['failed_custom_message'], $d['is_default']
                ]);
            }
        }
    } catch (Exception $e) {
        // Suppress or handle gracefully
    }
}

/**
 * Load all voucher templates
 */
function getAllVoucherTemplates() {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            ensureVoucherTemplatesTable($pdo);
            $stmt = $pdo->query("SELECT * FROM `voucher_templates` ORDER BY is_default DESC, updated_at DESC");
            $rows = $stmt->fetchAll();
            if ($rows && count($rows) > 0) {
                return array_map(function($r) {
                    $allowedUsernames = [];
                    if (!empty($r['allowed_usernames'])) {
                        $decoded = json_decode($r['allowed_usernames'], true);
                        $allowedUsernames = is_array($decoded) ? $decoded : parseItemsList($r['allowed_usernames']);
                    }
                    if (empty($allowedUsernames) && !empty($r['default_sender'])) {
                        $allowedUsernames = [$r['default_sender']];
                    }

                    $targetNumbers = [];
                    if (!empty($r['target_numbers'])) {
                        $decodedTgt = json_decode($r['target_numbers'], true);
                        $targetNumbers = is_array($decodedTgt) ? $decodedTgt : parseItemsList($r['target_numbers']);
                    }

                    return [
                        'id' => $r['id'],
                        'name' => $r['name'],
                        'description' => $r['description'] ?? '',
                        'base_target_url' => $r['base_target_url'],
                        'default_sender' => $r['default_sender'] ?? '',
                        'allowed_usernames' => $allowedUsernames,
                        'target_numbers' => $targetNumbers,
                        'success_keyword' => $r['success_keyword'] ?? '',
                        'success_custom_message' => $r['success_custom_message'] ?? '',
                        'pending_keyword' => $r['pending_keyword'] ?? '',
                        'pending_custom_message' => $r['pending_custom_message'] ?? '',
                        'failed_keyword' => $r['failed_keyword'] ?? '',
                        'failed_custom_message' => $r['failed_custom_message'] ?? '',
                        'is_default' => (bool)$r['is_default'],
                        'updated_at' => $r['updated_at'] ?? date('Y-m-d H:i:s')
                    ];
                }, $rows);
            }
        } catch (Exception $e) {}
    }

    // JSON file fallback
    if (file_exists(VOUCHER_CONFIG_FILE)) {
        $json = @file_get_contents(VOUCHER_CONFIG_FILE);
        if ($json) {
            $data = json_decode($json, true);
            if (is_array($data) && isset($data['templates']) && is_array($data['templates'])) {
                return array_map(function($t) {
                    $allowed = [];
                    if (!empty($t['allowed_usernames'])) {
                        $allowed = parseItemsList($t['allowed_usernames']);
                    } elseif (!empty($t['default_sender'])) {
                        $allowed = [$t['default_sender']];
                    }
                    $t['allowed_usernames'] = $allowed;
                    $t['target_numbers'] = parseItemsList($t['target_numbers'] ?? []);
                    return $t;
                }, $data['templates']);
            } elseif (is_array($data) && isset($data['base_target_url'])) {
                // Konversi format single lama ke multi-template
                $single = [
                    'id' => 'default',
                    'name' => 'Default Target Template',
                    'description' => 'Migrasi dari template sebelumnya',
                    'base_target_url' => $data['base_target_url'],
                    'default_sender' => $data['default_sender'] ?? '',
                    'allowed_usernames' => !empty($data['default_sender']) ? [$data['default_sender']] : [],
                    'target_numbers' => [],
                    'success_keyword' => $data['success_keyword'] ?? '',
                    'success_custom_message' => $data['success_custom_message'] ?? '',
                    'failed_keyword' => $data['failed_keyword'] ?? '',
                    'failed_custom_message' => $data['failed_custom_message'] ?? '',
                    'is_default' => 1,
                    'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s')
                ];
                saveAllVoucherTemplates([$single]);
                return [$single];
            }
        }
    }

    $defaults = getDefaultVoucherTemplates();
    saveAllVoucherTemplates($defaults);
    return $defaults;
}

/**
 * Get a single voucher template by ID or return default template
 */
function getVoucherTemplateById($id = null) {
    $templates = getAllVoucherTemplates();
    if (empty($templates)) {
        return getDefaultVoucherTemplates()[0];
    }

    if ($id) {
        foreach ($templates as $t) {
            if ($t['id'] === $id) return $t;
        }
    }

    // Fallback: cari yang is_default = true, jika tidak ambil pertama
    foreach ($templates as $t) {
        if (!empty($t['is_default'])) return $t;
    }

    return $templates[0];
}

/**
 * Save / Update a voucher template
 */
function saveVoucherTemplate(array $item) {
    $id = trim($item['id'] ?? '');
    if (!$id) {
        $id = 'vt-' . bin2hex(random_bytes(6));
    }

    $name = trim($item['name'] ?? 'Untitled Template');
    $description = trim($item['description'] ?? '');
    $baseTargetUrl = trim($item['base_target_url'] ?? '');
    $defaultSender = trim($item['default_sender'] ?? '');
    $allowedUsernames = parseItemsList($item['allowed_usernames'] ?? []);
    $targetNumbers = parseItemsList($item['target_numbers'] ?? []);

    if ($defaultSender === '' && !empty($allowedUsernames)) {
        $defaultSender = $allowedUsernames[0];
    }
    if (empty($allowedUsernames) && $defaultSender !== '') {
        $allowedUsernames = [$defaultSender];
    }

    $successKeyword = trim($item['success_keyword'] ?? '');
    $successCustomMessage = trim($item['success_custom_message'] ?? '');
    $pendingKeyword = trim($item['pending_keyword'] ?? '');
    $pendingCustomMessage = trim($item['pending_custom_message'] ?? '');
    $failedKeyword = trim($item['failed_keyword'] ?? '');
    $failedCustomMessage = trim($item['failed_custom_message'] ?? '');
    $isDefault = !empty($item['is_default']) ? 1 : 0;
    $now = date('Y-m-d H:i:s');

    $templateObj = [
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'base_target_url' => $baseTargetUrl,
        'default_sender' => $defaultSender,
        'allowed_usernames' => $allowedUsernames,
        'target_numbers' => $targetNumbers,
        'success_keyword' => $successKeyword,
        'success_custom_message' => $successCustomMessage,
        'pending_keyword' => $pendingKeyword,
        'pending_custom_message' => $pendingCustomMessage,
        'failed_keyword' => $failedKeyword,
        'failed_custom_message' => $failedCustomMessage,
        'is_default' => $isDefault,
        'updated_at' => $now
    ];

    // 1. Simpan ke database jika aktif
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            ensureVoucherTemplatesTable($pdo);

            if ($isDefault) {
                // Reset is_default lainnya jika item ini diset jadi default
                $pdo->exec("UPDATE `voucher_templates` SET `is_default` = 0");
            }

            $stmt = $pdo->prepare("INSERT INTO `voucher_templates` 
                (`id`, `name`, `description`, `base_target_url`, `default_sender`, `allowed_usernames`, `target_numbers`, `success_keyword`, `success_custom_message`, `pending_keyword`, `pending_custom_message`, `failed_keyword`, `failed_custom_message`, `is_default`, `updated_at`)
                VALUES (:id, :name, :description, :base_target_url, :default_sender, :allowed_usernames, :target_numbers, :success_keyword, :success_custom_message, :pending_keyword, :pending_custom_message, :failed_keyword, :failed_custom_message, :is_default, NOW())
                ON DUPLICATE KEY UPDATE
                    `name` = VALUES(`name`),
                    `description` = VALUES(`description`),
                    `base_target_url` = VALUES(`base_target_url`),
                    `default_sender` = VALUES(`default_sender`),
                    `allowed_usernames` = VALUES(`allowed_usernames`),
                    `target_numbers` = VALUES(`target_numbers`),
                    `success_keyword` = VALUES(`success_keyword`),
                    `success_custom_message` = VALUES(`success_custom_message`),
                    `pending_keyword` = VALUES(`pending_keyword`),
                    `pending_custom_message` = VALUES(`pending_custom_message`),
                    `failed_keyword` = VALUES(`failed_keyword`),
                    `failed_custom_message` = VALUES(`failed_custom_message`),
                    `is_default` = VALUES(`is_default`),
                    `updated_at` = NOW()");
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':description' => $description,
                ':base_target_url' => $baseTargetUrl,
                ':default_sender' => $defaultSender,
                ':allowed_usernames' => json_encode($allowedUsernames),
                ':target_numbers' => json_encode($targetNumbers),
                ':success_keyword' => $successKeyword,
                ':success_custom_message' => $successCustomMessage,
                ':pending_keyword' => $pendingKeyword,
                ':pending_custom_message' => $pendingCustomMessage,
                ':failed_keyword' => $failedKeyword,
                ':failed_custom_message' => $failedCustomMessage,
                ':is_default' => $isDefault
            ]);
        } catch (Exception $e) {}
    }

    // 2. Simpan ke JSON File
    $all = getAllVoucherTemplates();
    $found = false;
    foreach ($all as &$t) {
        if ($isDefault) {
            $t['is_default'] = 0;
        }
        if ($t['id'] === $id) {
            $t = $templateObj;
            $found = true;
        }
    }
    if (!$found) {
        $all[] = $templateObj;
    }
    saveAllVoucherTemplates($all);

    return $templateObj;
}

/**
 * Add target number to a template (can be called from redeem.php)
 */
function addTemplateTargetNumber($id, $targetNumber) {
    $targetNumber = trim((string)$targetNumber);
    if (!$id || $targetNumber === '') return false;

    $template = getVoucherTemplateById($id);
    if (!$template || !isset($template['id'])) return false;

    $currentTargets = parseItemsList($template['target_numbers'] ?? []);
    if (!in_array($targetNumber, $currentTargets)) {
        $currentTargets[] = $targetNumber;
    }
    $template['target_numbers'] = $currentTargets;
    return saveVoucherTemplate($template);
}

/**
 * Delete a voucher template by ID
 */
function deleteVoucherTemplate($id) {
    if (!$id) return false;

    $pdo = getDBConnection();
    if ($pdo) {
        try {
            ensureVoucherTemplatesTable($pdo);
            $stmt = $pdo->prepare("DELETE FROM `voucher_templates` WHERE `id` = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {}
    }

    $all = getAllVoucherTemplates();
    $filtered = array_values(array_filter($all, function($t) use ($id) {
        return $t['id'] !== $id;
    }));

    if (empty($filtered)) {
        $filtered = getDefaultVoucherTemplates();
    }

    saveAllVoucherTemplates($filtered);
    return true;
}

/**
 * Helper internal to rewrite JSON file
 */
function saveAllVoucherTemplates(array $templates) {
    $payload = [
        'templates' => $templates,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    @file_put_contents(VOUCHER_CONFIG_FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Backward compatibility alias:
function getVoucherConfig($id = null) {
    return getVoucherTemplateById($id);
}
function saveVoucherConfig(array $data) {
    return saveVoucherTemplate($data);
}

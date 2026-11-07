<?php
// redeem_api.php - Backend API for Redeem Transaction & 30-Day History
require_once 'includes/auth.php';
require_once 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$pdo = getDBConnection();

// Auto create tables if not exists (fail-safe)
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `redeem_logs` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT NULL,
          `template_id` VARCHAR(50) NULL,
          `template_name` VARCHAR(150) NULL,
          `trx_no` VARCHAR(100) NULL,
          `pengirim` VARCHAR(50) NOT NULL,
          `tujuan` VARCHAR(50) NOT NULL,
          `voucher` VARCHAR(100) NOT NULL,
          `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
          `saldo` VARCHAR(50) NULL,
          `message` TEXT NULL,
          `raw_response` LONGTEXT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_created_at` (`created_at`),
          INDEX `idx_voucher` (`voucher`),
          INDEX `idx_tujuan` (`tujuan`),
          INDEX `idx_template_id` (`template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Pastikan kolom template_id & template_name ada jika tabel sudah terlanjur dibuat sebelumnya
        try {
            $pdo->exec("ALTER TABLE `redeem_logs` ADD COLUMN `template_id` VARCHAR(50) NULL AFTER `user_id`");
            $pdo->exec("ALTER TABLE `redeem_logs` ADD COLUMN `template_name` VARCHAR(150) NULL AFTER `template_id`");
            $pdo->exec("ALTER TABLE `redeem_logs` ADD INDEX `idx_template_id` (`template_id`)");
        } catch (Exception $eCol) {
            // Kolom sudah ada
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `sender_accounts` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `label` VARCHAR(100) NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Seed default sender jika kosong
        $chk = $pdo->query("SELECT COUNT(*) FROM sender_accounts")->fetchColumn();
        if ($chk == 0) {
            $pdo->exec("INSERT IGNORE INTO sender_accounts (username, label) VALUES ('089523098721', 'Akun Utama Tri')");
        }
    } catch (Exception $e) {
        // ignore if already exists or permission issues
    }
}

if ($action === 'get_next_trx_id') {
    require_once 'includes/voucher_config.php';
    $template_id = trim($_GET['template_id'] ?? ($_POST['template_id'] ?? ''));
    $tujuan = trim($_GET['tujuan'] ?? ($_POST['tujuan'] ?? ''));
    $voucher = trim($_GET['voucher'] ?? ($_POST['voucher'] ?? ''));
    $force_new = !empty($_GET['force_new']) || !empty($_POST['force_new']);

    $config = getVoucherTemplateById($template_id);
    $variable = trim($config['trx_id_variable'] ?? '');
    $prefix = trim($config['trx_id_prefix'] ?? '');
    $lastNumber = intval($config['trx_id_last_number'] ?? 1000);

    if ($variable === '') {
        echo json_encode([
            'status' => 'success',
            'has_trx_id' => false
        ]);
        exit;
    }

    if ($prefix === '') {
        $prefix = 'rt';
    }

    // 1. Cek aturan khusus:
    // "setiap kali redeem itu harus menggunakan id yang berbeda, 
    //  kecuali ingin meredeem voucher ke nomor yang sama dengan voucher sama mungkin karena gangguan"
    if (!$force_new && $voucher !== '' && $tujuan !== '' && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT trx_no FROM redeem_logs 
                WHERE template_id = ? AND voucher = ? AND tujuan = ? AND trx_no IS NOT NULL AND trx_no != '' 
                ORDER BY id DESC LIMIT 1");
            $stmt->execute([$template_id, $voucher, $tujuan]);
            $existingTrx = $stmt->fetchColumn();
            if ($existingTrx) {
                echo json_encode([
                    'status' => 'success',
                    'has_trx_id' => true,
                    'variable' => $variable,
                    'prefix' => $prefix,
                    'trx_id' => $existingTrx,
                    'is_reuse' => true,
                    'message' => 'Menggunakan ID Transaksi sebelumnya karena nomor tujuan & voucher sama (retry gangguan).'
                ]);
                exit;
            }
        } catch (Exception $e) {}
    }

    // 2. Jika bukan retry atau nomor/voucher berbeda: Cari angka tertinggi berikutnya
    $maxNumber = $lastNumber;

    if ($pdo) {
        try {
            $pattern = $prefix . '%';
            $stmt = $pdo->prepare("SELECT trx_no FROM redeem_logs 
                WHERE template_id = ? AND trx_no LIKE ? 
                ORDER BY id DESC LIMIT 50");
            $stmt->execute([$template_id, $pattern]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $trxVal) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/i', $trxVal, $matches)) {
                    $num = intval($matches[1]);
                    if ($num > $maxNumber) {
                        $maxNumber = $num;
                    }
                }
            }
        } catch (Exception $e) {}
    }

    $nextNumber = $maxNumber + 1;
    $nextTrxId = $prefix . $nextNumber;

    echo json_encode([
        'status' => 'success',
        'has_trx_id' => true,
        'variable' => $variable,
        'prefix' => $prefix,
        'trx_id' => $nextTrxId,
        'next_number' => $nextNumber,
        'is_reuse' => false,
        'message' => 'ID Transaksi baru berhasil dibuat.'
    ]);
    exit;
}

if ($action === 'list_senders') {
    if (!$pdo) {
        echo json_encode(['status' => 'success', 'data' => [['id' => 1, 'username' => '089523098721', 'label' => 'Akun Utama Tri']]]);
        exit;
    }
    try {
        $stmt = $pdo->query("SELECT id, username, label FROM sender_accounts ORDER BY id ASC");
        $rows = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'add_sender') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $username = trim($input['username'] ?? '');
    $label = trim($input['label'] ?? '');

    if (!$username) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nomor username wajib diisi!']);
        exit;
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sender_accounts (username, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = ?");
            $stmt->execute([$username, $label ?: $username, $label ?: $username]);
            echo json_encode(['status' => 'success', 'message' => 'Username pengirim berhasil ditambahkan']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Tersimpan di sesi/memory']);
    }
    exit;
}

if ($action === 'delete_sender') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = intval($input['id'] ?? 0);

    if ($pdo && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM sender_accounts WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Username pengirim berhasil dihapus']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'success']);
    }
    exit;
}

if ($action === 'list') {
    // History 1 bulan terakhir (30 hari terakhir)
    $search = trim($_GET['search'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');
    $templateFilter = trim($_GET['template_id'] ?? '');
    $sortFilter = trim($_GET['sort'] ?? 'date_desc');
    $limitFilter = intval($_GET['limit'] ?? 20);

    // Batasi limit yang valid: 10, 20, 50, 100
    $allowedLimits = [10, 20, 50, 100];
    if (!in_array($limitFilter, $allowedLimits)) {
        $limitFilter = 20;
    }
    
    if (!$pdo) {
        echo json_encode([
            'status' => 'success',
            'data' => [],
            'note' => 'Database tidak terhubung'
        ]);
        exit;
    }

    try {
        $sql = "SELECT id, template_id, template_name, trx_no, pengirim, tujuan, voucher, status, saldo, message, raw_response, created_at 
                FROM redeem_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (voucher LIKE ? OR tujuan LIKE ? OR pengirim LIKE ? OR trx_no LIKE ? OR template_name LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }

        if ($templateFilter !== '' && $templateFilter !== 'all') {
            $sql .= " AND template_id = ?";
            $params[] = $templateFilter;
        }

        // Fitur sortir
        switch ($sortFilter) {
            case 'date_asc':
                $sql .= " ORDER BY created_at ASC, id ASC";
                break;
            case 'voucher_asc':
                $sql .= " ORDER BY voucher ASC";
                break;
            case 'voucher_desc':
                $sql .= " ORDER BY voucher DESC";
                break;
            case 'status_asc':
                $sql .= " ORDER BY status ASC, id DESC";
                break;
            case 'date_desc':
            default:
                $sql .= " ORDER BY created_at DESC, id DESC";
                break;
        }

        $sql .= " LIMIT " . $limitFilter;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Hitung ringkasan 1 bulan
        $summaryStmt = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'SUKSES' THEN 1 ELSE 0 END) as total_sukses,
            SUM(CASE WHEN status = 'PENDING' OR status = 'MENUNGGU' THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = 'GAGAL' THEN 1 ELSE 0 END) as total_gagal
            FROM redeem_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $summary = $summaryStmt->fetch() ?: ['total' => 0, 'total_sukses' => 0, 'total_pending' => 0, 'total_gagal' => 0];

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'summary' => $summary
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'recheck_item') {
    // Mengecek ulang satu item voucher dari riwayat
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = intval($input['id'] ?? 0);

    if (!$pdo || $id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID riwayat tidak valid']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM redeem_logs WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
            exit;
        }

        $voucher = $row['voucher'];
        $templateId = $row['template_id'];

        // Panggil helper voucher config untuk mengambil template
        require_once 'includes/voucher_config.php';
        $config = getVoucherTemplateById($templateId);
        $baseUrl = $config['base_target_url'] ?? '';

        if (empty($baseUrl)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Base Target URL template tidak ditemukan']);
            exit;
        }

        $pengirim = $row['pengirim'] ?? '';
        $tujuan = $row['tujuan'] ?? '';

        $targetUrl = $baseUrl;
        if (strpos($targetUrl, '{voucher}') !== false) {
            $targetUrl = str_replace('{voucher}', urlencode($voucher), $targetUrl);
        } else {
            $separator = (strpos($targetUrl, '?') !== false) ? '&' : '?';
            $targetUrl = $targetUrl . $separator . 'voucher=' . urlencode($voucher);
        }

        if (strpos($targetUrl, '{username}') !== false) {
            $targetUrl = str_replace('{username}', urlencode($pengirim), $targetUrl);
        } elseif (strpos($targetUrl, '{user}') !== false) {
            $targetUrl = str_replace('{user}', urlencode($pengirim), $targetUrl);
        } elseif (strpos($targetUrl, '{pengirim}') !== false) {
            $targetUrl = str_replace('{pengirim}', urlencode($pengirim), $targetUrl);
        }

        if (strpos($targetUrl, '{tujuan}') !== false) {
            $targetUrl = str_replace('{tujuan}', urlencode($tujuan), $targetUrl);
        } elseif (strpos($targetUrl, '{target}') !== false) {
            $targetUrl = str_replace('{target}', urlencode($tujuan), $targetUrl);
        }

        // Ganti variable ID Transaksi dengan trx_no yang tersimpan pada item ini
        $trxIdVariable = trim($config['trx_id_variable'] ?? '');
        $savedTrxNo = trim($row['trx_no'] ?? '');
        if ($trxIdVariable !== '' && $savedTrxNo !== '') {
            if (strpos($targetUrl, '{' . $trxIdVariable . '}') !== false) {
                $targetUrl = str_replace('{' . $trxIdVariable . '}', urlencode($savedTrxNo), $targetUrl);
            } elseif (preg_match('/([?&]' . preg_quote($trxIdVariable, '/') . '=)[^&]*/i', $targetUrl)) {
                $targetUrl = preg_replace('/([?&]' . preg_quote($trxIdVariable, '/') . '=)[^&]*/i', '${1}' . urlencode($savedTrxNo), $targetUrl);
            } else {
                $separator = (strpos($targetUrl, '?') !== false) ? '&' : '?';
                $targetUrl .= $separator . urlencode($trxIdVariable) . '=' . urlencode($savedTrxNo);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $responseBody = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno) {
            http_response_code(502);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghubungi server target']);
            exit;
        }

        $htmlText = (string)$responseBody;
        $successKeyword = trim($config['success_keyword'] ?? '');
        $pendingKeyword = trim($config['pending_keyword'] ?? '');
        $failedKeyword = trim($config['failed_keyword'] ?? '');

        $isSuccess = ($successKeyword !== '' && stripos($htmlText, $successKeyword) !== false);
        $isPending = ($pendingKeyword !== '' && stripos($htmlText, $pendingKeyword) !== false);
        $isFailed = ($failedKeyword !== '' && stripos($htmlText, $failedKeyword) !== false);

        if ($isFailed) {
            $newStatus = 'GAGAL';
            $newMessage = !empty($config['failed_custom_message']) ? $config['failed_custom_message'] : 'Voucher gagal atau tidak valid.';
        } elseif ($isPending) {
            $newStatus = 'PENDING';
            $newMessage = !empty($config['pending_custom_message']) ? $config['pending_custom_message'] : 'Voucher masih dalam antrian.';
        } elseif ($isSuccess) {
            $newStatus = 'SUKSES';
            $newMessage = !empty($config['success_custom_message']) ? $config['success_custom_message'] : 'Voucher berhasil divalidasi.';
        } else {
            $newStatus = 'PENDING';
            $newMessage = 'Status belum berubah / menunggu respon target.';
        }

        // Update database
        $upd = $pdo->prepare("UPDATE redeem_logs SET status = ?, message = ?, raw_response = ? WHERE id = ?");
        $upd->execute([$newStatus, $newMessage, $htmlText, $id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Status berhasil diperbarui: ' . $newStatus,
            'new_status' => $newStatus,
            'new_message' => $newMessage
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    // Simpan log hasil redeem
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $template_id = trim($input['template_id'] ?? '');
    $template_name = trim($input['template_name'] ?? '');
    $trx_no = trim($input['trx_no'] ?? '');
    $pengirim = trim($input['pengirim'] ?? '');
    $tujuan = trim($input['tujuan'] ?? '');
    $voucher = trim($input['voucher'] ?? '');
    $status = trim($input['status'] ?? 'PENDING');
    $saldo = trim($input['saldo'] ?? '');
    $message = trim($input['message'] ?? '');
    $raw_response = trim($input['raw_response'] ?? '');
    
    $currentUser = getCurrentUser();
    $user_id = $currentUser ? $currentUser['id'] : null;

    if (!$pdo) {
        echo json_encode(['status' => 'success', 'saved' => false, 'message' => 'DB not connected, skipped persistent storage']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO redeem_logs (user_id, template_id, template_name, trx_no, pengirim, tujuan, voucher, status, saldo, message, raw_response, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            $template_id ?: null,
            $template_name ?: null,
            $trx_no,
            $pengirim,
            $tujuan,
            $voucher,
            $status,
            $saldo,
            $message,
            $raw_response
        ]);

        // Perbarui counter terakhir pada template jika nomor trx_no lebih besar
        if ($template_id && $trx_no) {
            if (preg_match('/(\d+)$/', $trx_no, $numMatch)) {
                $usedNum = intval($numMatch[1]);
                try {
                    $updStmt = $pdo->prepare("UPDATE voucher_templates 
                        SET trx_id_last_number = GREATEST(COALESCE(trx_id_last_number, 0), ?) 
                        WHERE id = ?");
                    $updStmt->execute([$usedNum, $template_id]);
                } catch (Exception $eUpd) {}
            }
        }

        echo json_encode([
            'status' => 'success',
            'saved' => true,
            'id' => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);

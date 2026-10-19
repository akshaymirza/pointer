<?php
// check_voucher.php - Backend API Proxy for Voucher Checking & Verification
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/includes/voucher_config.php';

// Mendukung parameter via GET, POST, maupun JSON Body
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);

$action = $_GET['action'] ?? ($jsonBody['action'] ?? ($_POST['action'] ?? 'check'));

/**
 * Action: List All Templates (Untuk Dropdown di Page 1 & Tabel di Page 2)
 */
if ($action === 'list_templates') {
    $templates = getAllVoucherTemplates();
    echo json_encode([
        'status' => 'success',
        'data' => $templates
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Action: Get Config / Single Template (Untuk Page 1 & Page 2)
 */
if ($action === 'get_config' || $action === 'get_template') {
    $templateId = $_GET['template_id'] ?? ($jsonBody['template_id'] ?? ($_GET['id'] ?? null));
    $config = getVoucherTemplateById($templateId);
    echo json_encode([
        'status' => 'success',
        'data' => $config
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Action: Save Config / Template (Untuk Page 2 Create & Edit)
 */
if ($action === 'save_config' || $action === 'save_template') {
    $inputData = $jsonBody ?: $_POST;
    if (empty($inputData)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Data template tidak boleh kosong.'
        ]);
        exit;
    }

    $saved = saveVoucherTemplate($inputData);
    echo json_encode([
        'status' => 'success',
        'message' => 'Template voucher berhasil disimpan.',
        'data' => $saved
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Action: Delete Template (Untuk Page 2)
 */
if ($action === 'delete_template') {
    $templateId = $_GET['id'] ?? ($jsonBody['id'] ?? ($_POST['id'] ?? ''));
    if (!$templateId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID template wajib diisi.']);
        exit;
    }

    deleteVoucherTemplate($templateId);
    echo json_encode(['status' => 'success', 'message' => 'Template voucher berhasil dihapus.']);
    exit;
}

/**
 * Action: Add Target Number to Template (Dipanggil dari Page 1 Redeem)
 */
if ($action === 'add_target_number') {
    $inputData = $jsonBody ?: $_POST;
    $templateId = trim($inputData['template_id'] ?? ($_GET['template_id'] ?? ''));
    $targetNumber = trim($inputData['target_number'] ?? ($_GET['target_number'] ?? ''));

    if (!$templateId || !$targetNumber) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Template ID dan Nomor Tujuan wajib disertakan.'
        ]);
        exit;
    }

    $updated = addTemplateTargetNumber($templateId, $targetNumber);
    if (!$updated) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Template tidak ditemukan atau nomor gagal ditambahkan.'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Nomor tujuan berhasil ditambahkan ke template.',
        'data' => $updated
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Action: Check Voucher Proxy
 */
$voucher = $_GET['voucher'] ?? ($jsonBody['voucher'] ?? ($_POST['voucher'] ?? ''));
$templateId = $_GET['template_id'] ?? ($jsonBody['template_id'] ?? ($_POST['template_id'] ?? null));
$voucher = trim($voucher);

if ($voucher === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter voucher wajib disertakan.'
    ]);
    exit;
}

// 1. Ambil URL target dari template yang dipilih (atau default jika tidak dispesifikasikan)
$config = getVoucherTemplateById($templateId);
$baseUrl = $config['base_target_url'] ?? '';

if (empty($baseUrl)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Base Target URL belum dikonfigurasi di backend.'
    ]);
    exit;
}

// 2. Gabungkan URL target dengan parameter voucher
// Jika ada placeholder {voucher}, ganti. Jika tidak, tambahkan sebagai query parameter.
if (strpos($baseUrl, '{voucher}') !== false) {
    $targetUrl = str_replace('{voucher}', urlencode($voucher), $baseUrl);
} else {
    $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
    $targetUrl = $baseUrl . $separator . 'voucher=' . urlencode($voucher);
}

// 3. HTTP Fetch ke URL target di latar belakang menggunakan cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,application/json,text/plain,*/*;q=0.8',
    'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: no-cache'
]);

$responseBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($curlErrno) {
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menghubungi server target: ' . $curlError,
        'target_url' => $targetUrl,
        'curl_errno' => $curlErrno
    ]);
    exit;
}

// 4. Analisis & Pemindaian Isi HTML / Text terhadap Keyword Sukses & Gagal
$htmlText = (string)$responseBody;

$successKeyword = trim($config['success_keyword'] ?? '');
$pendingKeyword = trim($config['pending_keyword'] ?? '');
$failedKeyword = trim($config['failed_keyword'] ?? '');

$isSuccess = false;
$isPending = false;
$isFailed = false;

// Cek kecocokan keyword (case-insensitive)
if ($failedKeyword !== '' && stripos($htmlText, $failedKeyword) !== false) {
    $isFailed = true;
}

if ($pendingKeyword !== '' && stripos($htmlText, $pendingKeyword) !== false) {
    $isPending = true;
}

if ($successKeyword !== '' && stripos($htmlText, $successKeyword) !== false) {
    $isSuccess = true;
}

// Logika penentuan status dan pesan sesuai aturan:
// 1. Jika terdeteksi kata kunci gagal: status = "failed"
// 2. Jika terdeteksi kata kunci pending/menunggu: status = "pending"
// 3. Jika terdeteksi kata kunci sukses: status = "success"
// 4. Fallback jika tidak ada kata kunci yang cocok
if ($isFailed) {
    $status = 'failed';
    $message = !empty($config['failed_custom_message']) 
        ? $config['failed_custom_message'] 
        : 'Voucher gagal atau tidak valid.';
} elseif ($isPending) {
    $status = 'pending';
    $message = !empty($config['pending_custom_message']) 
        ? $config['pending_custom_message'] 
        : 'Status voucher masih menunggu / pending.';
} elseif ($isSuccess) {
    $status = 'success';
    $message = !empty($config['success_custom_message']) 
        ? $config['success_custom_message'] 
        : 'Voucher berhasil divalidasi.';
} else {
    // 4. Logika Fallback jika tidak ada kata kunci yang cocok
    $status = 'fallback';
    if ($successKeyword === '' && $pendingKeyword === '' && $failedKeyword === '') {
        $message = ($httpCode >= 200 && $httpCode < 300) 
            ? 'Respon target diterima (HTTP ' . $httpCode . '), namun kata kunci belum diset di Voucher Settings.' 
            : 'Target merespon dengan kode error HTTP ' . $httpCode . '.';
    } else {
        $message = 'Respon diterima, namun kata kunci sukses, pending, maupun gagal tidak ditemukan pada respon target.';
    }
}

// Kembalikan response JSON standar
echo json_encode([
    'status' => $status,
    'message' => $message,
    'template_id' => $config['id'] ?? null,
    'template_name' => $config['name'] ?? null,
    'voucher' => $voucher,
    'http_code' => $httpCode,
    'target_url' => $targetUrl,
    'matched_keywords' => [
        'success' => $isSuccess,
        'pending' => $isPending,
        'failed' => $isFailed
    ],
    'raw_content' => $htmlText
], JSON_UNESCAPED_SLASHES);

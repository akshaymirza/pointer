<?php
require_once 'includes/auth.php';
$pageTitle = "Checking mode - Dynamic Template Parser System";
$currentPage = "workspace";
$pageHeading = "Checking mode";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="page active" id="workspacePage">
  <div class="section-title">
    <p class="eyebrow">Employee Workspace</p>
    <h2>Checking mode</h2>
  </div>

  <!-- Mode Selector Tabs -->
  <div class="mode-tabs">
    <button type="button" class="mode-tab active" id="singleModeTab" data-mode="single">
      <span class="mode-icon">⚡</span> Single Process
    </button>
    <button type="button" class="mode-tab" id="bulkModeTab" data-mode="bulk">
      <span class="mode-icon">📊</span> Bulk Range / Multi Process
    </button>
  </div>

  <section class="workspace-layout">
    <div class="workspace-main">
      <section class="content-card form-card">
        <label>
          <div class="param-header">
            <span>Select Template</span>
            <select id="templateSortSelect" class="param-sort-select" title="Urutkan Daftar Template">
              <option value="date_desc">Terbaru (Tanggal)</option>
              <option value="asc">Nama (A - Z)</option>
              <option value="desc">Nama (Z - A)</option>
              <option value="date_asc">Terlama (Tanggal)</option>
            </select>
          </div>
          <select id="templateSelect"></select>
        </label>

        <!-- Dynamic Form (Single Mode) -->
        <div class="dynamic-form" id="dynamicForm"></div>

        <!-- Bulk Form Options (Hidden in Single Mode) -->
        <div class="bulk-form hidden" id="bulkForm">
          <div class="bulk-config-card" style="border: 1px solid var(--border); border-radius: 14px; padding: 16px; background: #f8fafc; margin: 16px 0;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-weight: 600; font-size: 14px;">Pilih Parameter Target Bulk (Bisa lebih dari 1)</span>
                <span id="bulkSelectedParamsBadge" class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700;">0 parameter dipilih</span>
              </div>
              <div id="bulkParamChecklist" class="bulk-param-checklist" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 8px;">
                <!-- Diisi dinamis oleh script.js -->
              </div>
              <small style="color: var(--muted); display: block; margin-bottom: 12px;">Centang parameter yang ingin diisi banyak nilai secara berulang (bulk). Anda bisa mengisi rentang nomor atau daftar baris untuk masing-masing parameter.</small>
            </div>

            <!-- Kontainer konfigurasi input dinamis untuk tiap parameter yang dicentang -->
            <div id="bulkParamsConfigContainer" style="display: flex; flex-direction: column; gap: 14px;"></div>

            <!-- Ringkasan Total Data yang Siap Diproses -->
            <div id="bulkSummaryCard" style="display:flex; justify-content:space-between; align-items:center; background:#ffffff; border:1px solid #bfdbfe; border-radius:12px; padding:12px 16px; margin-top:14px;">
              <div>
                <strong style="font-size:13px; color:#1e3a8a; display:block;">Ringkasan Data Siap Diproses</strong>
                <span id="bulkSummaryText" style="font-size:12px; color:var(--muted);">0 parameter aktif • 0 data</span>
              </div>
              <span id="bulkTotalRowsBadge" class="badge" style="background:#2563eb; color:white; font-size:13px; padding:4px 10px;">Total: 0 baris</span>
            </div>

            <!-- Limit Jumlah Bulk -->
            <div class="form-grid two-col" style="margin-top:14px;">
              <label>
                <span>Batasi Jumlah Diproses (Limit)</span>
                <select id="bulkLimitSelect">
                  <option value="all" selected>Semua (Tanpa Batas)</option>
                  <option value="10">Maksimal 10 item</option>
                  <option value="20">Maksimal 20 item</option>
                  <option value="50">Maksimal 50 item</option>
                  <option value="100">Maksimal 100 item</option>
                  <option value="200">Maksimal 200 item</option>
                  <option value="500">Maksimal 500 item</option>
                  <option value="custom">Jumlah Kustom...</option>
                </select>
              </label>
              <label id="bulkCustomLimitWrap" class="hidden">
                <span>Jumlah Maksimal Kustom</span>
                <input type="number" id="bulkCustomLimitInput" min="1" max="5000" placeholder="Contoh: 35" />
              </label>
            </div>
          </div>
        </div>

        <div class="action-row" style="display:flex; gap:12px; margin-top:16px;">
          <button class="primary-button process-button" id="processBtn" style="flex:1;">Process</button>
          <button class="danger-button hidden" id="cancelBulkBtn" type="button">Stop Process</button>
        </div>
      </section>
    </div>

    <!-- Right Side / Single Result Card -->
    <aside class="content-card result-card" id="singleResultCard">
      <div class="card-heading">
        <div>
          <h2>Generated Output</h2>
          <p>Final text generated from JSON response and output template.</p>
        </div>
        <span class="badge" id="resultBadge">Ready</span>
      </div>
      <div class="loading-box hidden" id="loadingBox">
        <div class="spinner"></div>
        <span id="loadingMessage">Calling backend proxy...</span>
      </div>
      <textarea id="generatedOutput" readonly rows="12" placeholder="Output will appear here after processing."></textarea>
      <button class="secondary-button copy-button" id="copyBtn">
        <span aria-hidden="true">📋</span>
        Copy Result
      </button>
    </aside>
  </section>

  <!-- Bulk Results Full Card (Visible in Bulk Mode) -->
  <section class="content-card bulk-result-card hidden" id="bulkResultCard" style="margin-top:20px;">
    <div class="card-heading">
      <div>
        <h2>Hasil Bulk Processing (<span id="bulkTotalProcessed">0</span> items)</h2>
        <p>Seluruh hasil output yang berhasil diproses secara otomatis.</p>
      </div>
      <div class="bulk-badges" style="display:flex; gap:8px;">
        <span class="badge success" id="bulkSuccessCount">Sukses: 0</span>
        <span class="badge danger" id="bulkErrorCount" style="background:#fee2e2; color:#991b1b;">Gagal: 0</span>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="bulk-progress-wrap hidden" id="bulkProgressWrap" style="margin-bottom:18px;">
      <div class="progress-bar-bg" style="background:#e2e8f0; border-radius:999px; height:12px; overflow:hidden;">
        <div class="progress-bar-fill" id="bulkProgressBar" style="width:0%; background:var(--primary); height:100%; transition:width 0.2s ease;"></div>
      </div>
      <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:13px; font-weight:700; color:var(--muted);">
        <span id="bulkProgressText">Memproses 0 / 0</span>
        <span id="bulkProgressPercent">0%</span>
      </div>
    </div>

    <!-- Toolbar for Bulk Table -->
    <div class="toolbar" style="margin-bottom:14px; flex-wrap:wrap; gap:10px;">
      <div class="search-box" style="max-width:320px;">
        <span>🔍</span>
        <input type="text" id="bulkSearchInput" placeholder="Cari dalam hasil..." />
      </div>
      <div style="display:flex; gap:10px; margin-left:auto;">
        <button class="secondary-button" id="copyBulkTxtBtn" type="button">📋 Copy Semua Output</button>
        <button class="secondary-button" id="downloadBulkCsvBtn" type="button">⬇️ Download XLSX</button>
      </div>
    </div>

    <!-- Bulk Table -->
    <div class="table-wrap">
      <table id="bulkResultTable">
        <thead>
          <tr id="bulkResultTableHeaderRow">
            <th style="width:50px;">#</th>
            <th id="bulkParamColumnHeader">Parameter</th>
            <th style="width:100px;">Status</th>
            <th>Generated Output</th>
            <th style="width:100px;">Aksi</th>
          </tr>
        </thead>
        <tbody id="bulkTableBody">
          <tr>
            <td colspan="5" style="text-align:center; padding:32px; color:var(--muted);">Belum ada data bulk yang diproses. Silakan klik tombol Process.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</section>

<?php include 'includes/footer.php'; ?>

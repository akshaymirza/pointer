<?php
require_once 'includes/auth.php';
$pageTitle = "Redeeming mode - Dynamic Template Parser System";
$currentPage = "redeem";
$pageHeading = "Redeeming mode";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="page active" id="redeemPage">
  <div class="section-title">
    <p class="eyebrow">Direct Service / Three Tools</p>
    <h2>Redeeming mode</h2>
  </div>

  <!-- Alert / Banner Status Redeem (Hijau = Sukses, Merah = Gagal) -->
  <div id="redeemStatusBanner" style="display: none; padding: 16px 20px; border-radius: 14px; margin-bottom: 20px; font-size: 14px; line-height: 1.5; box-shadow: 0 4px 16px rgba(0,0,0,0.06); animation: fadeIn 0.25s ease;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
      <div style="display: flex; align-items: flex-start; gap: 12px;">
        <span id="bannerIcon" style="font-size: 22px; line-height: 1;">⚡</span>
        <div>
          <strong id="bannerTitle" style="display: block; font-size: 15px; margin-bottom: 3px;">Status Redeem</strong>
          <span id="bannerMessage">Pesan kustom akan tampil di sini.</span>
        </div>
      </div>
      <button type="button" onclick="closeRedeemBanner()" style="border: 0; background: transparent; font-size: 18px; cursor: pointer; color: inherit; opacity: 0.7; padding: 0;" title="Tutup Banner">
        ✕
      </button>
    </div>
  </div>

  <!-- Workspace Grid Layout (Mengikuti gaya Checking mode) -->
  <section class="workspace-layout">
    <!-- Kolom Kiri: Form Input Bersih & Ringkas -->
    <div class="workspace-main">
      <section class="content-card form-card">
        <div class="card-heading" style="margin-bottom: 14px;">
          <div>
            <h2>Form Redeem Voucher</h2>
            <p>Masukkan kode voucher untuk diverifikasi dan dieksekusi.</p>
          </div>
        </div>

        <form id="redeemForm" onsubmit="handleRedeemSubmit(event)">
          <!-- Select Voucher Template (Sama seperti Checking Mode) -->
          <label style="margin-bottom: 16px;">
            <div class="param-header">
              <span>Select Voucher Template</span>
              <div style="display: flex; gap: 8px; align-items: center;">
                <select id="voucherTemplateSortSelect" class="param-sort-select" onchange="sortVoucherTemplates(this.value)" title="Urutkan Template">
                  <option value="default_first">Default / Rekomendasi</option>
                  <option value="name_asc">Nama (A - Z)</option>
                  <option value="name_desc">Nama (Z - A)</option>
                  <option value="date_desc">Terbaru (Tanggal)</option>
                </select>
              </div>
            </div>
            <select id="voucherTemplateSelect" onchange="handleTemplateSelectChange(this.value)" style="height: 46px; font-weight: 600;">
              <option value="">Memuat template voucher...</option>
            </select>
            <!-- Badge Info Template Aktif -->
            <div id="activeTemplateInfoBar" style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: var(--muted); margin-top: 4px; padding: 0 4px;">
              <span id="activeTemplateDesc">Memuat info template...</span>
              <span id="activeTemplateKwBadge" style="background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-family: monospace;">-</span>
            </div>
          </label>

          <div class="dynamic-form" style="margin: 0 0 16px 0;">
            <!-- Parameter Pengirim & Nomor Tujuan (Tersusun ke bawah) -->
            <div style="display: flex; flex-direction: column; gap: 14px;">
              <label>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                  <span style="font-weight: 600;">Nomor Pengirim (Username) Khusus Template</span>
                  <a href="voucher_settings.php" target="_blank" style="font-size: 11px; text-decoration: none; color: var(--primary); font-weight: 600;" title="Kelola username untuk setiap template di Voucher Settings">
                    ⚙️ Kelola di Voucher Settings
                  </a>
                </div>
                <div style="display: flex; gap: 6px;">
                  <select id="usernameSelect" onchange="handleSenderChange(this.value)" disabled style="flex: 1; height: 46px; font-family: monospace; font-weight: 600;">
                    <option value="">-- Pilih Template Terlebih Dahulu --</option>
                  </select>
                </div>
                <!-- Hidden input untuk menjaga kompatibilitas nilai username -->
                <input type="hidden" id="usernameInput" value="" />
              </label>

              <label>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                  <span style="font-weight: 600;">Nomor Tujuan *</span>
                  <span style="font-size:11px; font-weight:normal; color:var(--muted);">Diisi oleh user</span>
                </div>
                <input 
                  type="text" 
                  id="tujuanInput" 
                  value="" 
                  placeholder="Contoh: 0895639154834 (kosong secara default)" 
                  oninput="handleTujuanInput(this.value)"
                  required 
                  style="height: 46px; font-family: monospace; font-size: 14px; font-weight: 600;"
                />
                <small style="color: var(--muted); font-size: 11px;">Nomor tujuan wajib diisi sebelum memproses redeem.</small>
              </label>
            </div>

            <!-- Baris 2: Input Utama Nomor Voucher -->
            <label style="margin-top: 6px;">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size: 15px; font-weight: 700; color: #1e293b;">Nomor / Kode Voucher</span>
                <span id="activeTargetBadge" style="font-size: 12px; color: var(--muted); font-weight: 600; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">
                  Tujuan: (Belum diisi)
                </span>
              </div>
              <input 
                type="text" 
                id="voucherInput" 
                value="" 
                placeholder="Masukkan 16 digit voucher (contoh: 8601067074260624)" 
                autofocus
                autocomplete="off"
                required 
                style="height: 50px; font-size: 15px; font-family: monospace; letter-spacing: 1px; font-weight: 600;"
              />
            </label>
          </div>

          <div class="action-row">
            <button class="primary-button process-button" id="submitBtn" type="submit">
              <span id="submitIcon">⚡</span>
              <span id="submitText">Process Redeem</span>
            </button>
          </div>
        </form>
      </section>
    </div>

    <!-- Kolom Kanan: Hasil Pengecekan Voucher -->
    <aside class="content-card result-card" id="singleResultCard">
      <div class="card-heading">
        <div>
          <h2>Hasil Pengecekan Voucher</h2>
          <p>Status verifikasi dan kode voucher yang diproses.</p>
        </div>
        <span class="badge" id="resultBadge">Ready</span>
      </div>

      <div class="loading-box hidden" id="loadingBox">
        <div class="spinner"></div>
        <span id="loadingMessage">Memeriksa status voucher...</span>
      </div>

      <!-- Preview Kartu Ringkasan: Status Voucher & No Voucher Saja -->
      <div id="trxInfoPill" style="display: none; margin-bottom: 14px; padding: 14px 16px; border-radius: 12px; background: #f8fafc; border: 1px solid var(--border);">
        <div style="font-size: 14px; margin-bottom: 8px;">
          Nomor Voucher: <strong id="pillTrxNo" style="font-family: monospace; font-size: 15px; color: var(--primary);">-</strong>
        </div>
        <div style="font-size: 14px;">
          Status Voucher: <strong id="pillStatus" style="font-size: 15px;">-</strong>
        </div>
      </div>

      <textarea 
        id="generatedOutput" 
        readonly 
        rows="6" 
        style="font-family: monospace; font-size: 14px; font-weight: 600; line-height: 1.6; resize: vertical;"
        placeholder="Hasil status voucher dan nomor voucher akan muncul di sini setelah diproses."
      ></textarea>

      <button class="secondary-button copy-button" id="copyBtn" onclick="copyResultOutput()">
        <span aria-hidden="true">📋</span>
        Copy Result
      </button>
    </aside>
  </section>

  <!-- Bagian Bawah: History Redeem Selama 1 Bulan -->
  <section class="content-card" style="margin-top: 24px;">
    <div class="card-heading" style="flex-wrap: wrap; gap: 12px;">
      <div>
        <h2>Riwayat Redeem (30 Hari Terakhir)</h2>
        <p>Semua voucher yang telah diredeem selama 1 bulan terakhir.</p>
      </div>

      <!-- Filter, Pencarian, Template, Limit, Sortir -->
      <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div class="search-box" style="width: 220px; height: 38px;">
          <span>🔍</span>
          <input 
            type="text" 
            id="historySearchInput" 
            placeholder="Cari voucher / tujuan / TRX..." 
            oninput="debouncedFetchHistory()"
            style="height: 36px; font-size: 13px;"
          />
        </div>

        <!-- Filter Template -->
        <select id="historyTemplateFilterSelect" onchange="fetchRedeemHistory()" style="height: 38px; width: 170px; padding: 6px 10px; font-size: 13px;" title="Filter berdasarkan Template">
          <option value="all">Semua Template</option>
        </select>

        <!-- Filter Status -->
        <select id="statusFilterSelect" onchange="fetchRedeemHistory()" style="height: 38px; width: 140px; padding: 6px 10px; font-size: 13px;" title="Filter Status">
          <option value="all">Semua Status</option>
          <option value="SUKSES">Sukses</option>
          <option value="PENDING">Pending (Menunggu)</option>
          <option value="GAGAL">Gagal</option>
        </select>

        <!-- Fitur Sortir -->
        <select id="historySortSelect" onchange="fetchRedeemHistory()" style="height: 38px; width: 150px; padding: 6px 10px; font-size: 13px;" title="Urutkan Riwayat">
          <option value="date_desc">Waktu Terbaru</option>
          <option value="date_asc">Waktu Terlama</option>
          <option value="voucher_asc">Voucher (A - Z)</option>
          <option value="voucher_desc">Voucher (Z - A)</option>
          <option value="status_asc">Status</option>
        </select>

        <!-- Pilihan Maksimal yang Ditampilkan: 10, 20, 50, 100 -->
        <select id="historyLimitSelect" onchange="fetchRedeemHistory()" style="height: 38px; width: 120px; padding: 6px 10px; font-size: 13px; font-weight: 600;" title="Jumlah Tampilan">
          <option value="10">10 Data</option>
          <option value="20" selected>20 Data</option>
          <option value="50">50 Data</option>
          <option value="100">100 Data</option>
        </select>

        <button class="secondary-button" onclick="fetchRedeemHistory()" style="height: 38px; min-height: 38px; padding: 0 14px; font-size: 13px;" title="Refresh Data">
          🔄 Refresh
        </button>

        <button class="secondary-button" onclick="exportRedeemHistoryXlsx()" style="height: 38px; min-height: 38px; padding: 0 14px; font-size: 13px;" title="Download Excel (.xlsx)">
          📥 Download XLSX
        </button>
      </div>
    </div>

    <!-- Ringkasan Statistik 1 Bulan -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px;">
      <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px;">
        <span style="font-size: 12px; color: var(--muted); text-transform: uppercase;">Total Transaksi (30 Hari)</span>
        <strong id="statTotal" style="display: block; font-size: 20px; margin-top: 4px;">0</strong>
      </div>
      <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px;">
        <span style="font-size: 12px; color: #166534; text-transform: uppercase;">Total Berhasil</span>
        <strong id="statSukses" style="display: block; font-size: 20px; color: #15803d; margin-top: 4px;">0</strong>
      </div>
      <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px 14px;">
        <span style="font-size: 12px; color: #92400e; text-transform: uppercase;">Total Pending</span>
        <strong id="statPending" style="display: block; font-size: 20px; color: #b45309; margin-top: 4px;">0</strong>
      </div>
      <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 12px 14px;">
        <span style="font-size: 12px; color: #991b1b; text-transform: uppercase;">Total Gagal</span>
        <strong id="statGagal" style="display: block; font-size: 20px; color: #b91c1c; margin-top: 4px;">0</strong>
      </div>
    </div>

    <!-- Table Wrap -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width: 130px;">Waktu</th>
            <th>Template</th>
            <th>No TRX</th>
            <th>Nomor Voucher</th>
            <th>Tujuan</th>
            <th>Pengirim</th>
            <th>Status</th>
            <th>Saldo</th>
            <th>Respon Keterangan</th>
            <th style="width: 110px; text-align: center;">Aksi</th>
          </tr>
        </thead>
        <tbody id="historyTableBody">
          <tr>
            <td colspan="10" style="text-align: center; padding: 32px; color: var(--muted);">
              Memuat data riwayat 30 hari...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
</section>

<script>
let historyData = [];
let searchDebounceTimer = null;

// Voucher Templates State (Sama seperti Checking Mode)
let voucherTemplates = [];
let currentVoucherTemplateId = '';
let currentTemplateSort = 'default_first';

async function fetchVoucherTemplates() {
  try {
    const res = await fetch('check_voucher.php?action=list_templates');
    const json = await res.json();
    if (json.status === 'success' && json.data) {
      voucherTemplates = json.data;
    } else {
      voucherTemplates = [];
    }
  } catch (err) {
    console.warn('Gagal memuat daftar template voucher:', err);
    voucherTemplates = [];
  }
  renderVoucherTemplateDropdown();
  populateHistoryTemplateFilter();
}

function sortVoucherTemplates(sortType) {
  currentTemplateSort = sortType;
  renderVoucherTemplateDropdown();
}

function getSortedVoucherTemplates(items, sortType) {
  if (!items || !items.length) return [];
  const list = [...items];

  if (sortType === 'name_asc') {
    list.sort((a, b) => (a.name || '').localeCompare(b.name || '', undefined, { numeric: true, sensitivity: 'base' }));
  } else if (sortType === 'name_desc') {
    list.sort((a, b) => (b.name || '').localeCompare(a.name || '', undefined, { numeric: true, sensitivity: 'base' }));
  } else if (sortType === 'date_desc') {
    list.sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
  } else {
    // default_first: yang is_default = 1 berada di paling atas
    list.sort((a, b) => {
      const aDef = a.is_default ? 1 : 0;
      const bDef = b.is_default ? 1 : 0;
      if (bDef !== aDef) return bDef - aDef;
      return new Date(b.updated_at || 0) - new Date(a.updated_at || 0);
    });
  }
  return list;
}

function renderVoucherTemplateDropdown() {
  const select = document.getElementById('voucherTemplateSelect');
  if (!select) return;

  const sorted = getSortedVoucherTemplates(voucherTemplates, currentTemplateSort);

  if (!sorted.length) {
    select.innerHTML = '<option value="">Belum ada template. Silakan buat di Voucher Settings.</option>';
    document.getElementById('activeTemplateDesc').textContent = 'Tidak ada template aktif';
    document.getElementById('activeTemplateKwBadge').textContent = '-';
    currentVoucherTemplateId = '';
    return;
  }

  // User minta: defaultnya itu nggak milih apa-apa
  let html = '<option value="">-- Pilih Template Voucher --</option>';
  html += sorted.map(t => {
    const defaultTag = t.is_default ? ' ★ (Default)' : '';
    return `<option value="${t.id}">${t.name}${defaultTag}</option>`;
  }).join('');
  select.innerHTML = html;

  // Cek apakah sebelumnya user sudah memilih template di sesi browser
  const savedTemplateId = localStorage.getItem('redeem_active_template_id');
  if (savedTemplateId && sorted.some(t => t.id === savedTemplateId)) {
    select.value = savedTemplateId;
    handleTemplateSelectChange(savedTemplateId);
  } else {
    // Default: Tidak memilih apa-apa
    select.value = '';
    handleTemplateSelectChange('');
  }
}

function handleTemplateSelectChange(templateId) {
  currentVoucherTemplateId = templateId;
  localStorage.setItem('redeem_active_template_id', templateId);
  updateActiveTemplateUI(templateId);

  // Perbaikan 1: Terapkan username khusus template (username template lain hilang)
  applyTemplateUsernames(templateId);
}

function updateActiveTemplateUI(templateId) {
  const descEl = document.getElementById('activeTemplateDesc');
  const kwBadge = document.getElementById('activeTemplateKwBadge');

  if (!templateId) {
    if (descEl) descEl.textContent = 'Silakan pilih template voucher untuk memulai.';
    if (kwBadge) kwBadge.textContent = 'Belum Dipilih';
    return;
  }

  const t = voucherTemplates.find(item => item.id === templateId);
  if (!t) return;

  if (descEl) {
    descEl.textContent = t.description ? t.description : `Target: ${t.base_target_url.slice(0, 45)}...`;
  }
  if (kwBadge) {
    const sKw = t.success_keyword || '-';
    const fKw = t.failed_keyword || '-';
    kwBadge.textContent = `Sukses: [${sKw}] | Gagal: [${fKw}]`;
  }
}

// PERBAIKAN 1: Pengaturan Username/Pengirim Eksklusif per Template
function applyTemplateUsernames(templateId) {
  const select = document.getElementById('usernameSelect');
  const hiddenInput = document.getElementById('usernameInput');
  if (!select || !hiddenInput) return;

  // Bersihkan semua username sebelumnya (jika ganti template, username template lama hilang)
  select.innerHTML = '';

  if (!templateId) {
    select.innerHTML = '<option value="">-- Pilih Template Terlebih Dahulu --</option>';
    select.disabled = true;
    hiddenInput.value = '';
    return;
  }

  const activeTpl = voucherTemplates.find(item => item.id === templateId);
  if (!activeTpl) {
    select.innerHTML = '<option value="">-- Template Tidak Ditemukan --</option>';
    select.disabled = true;
    hiddenInput.value = '';
    return;
  }

  const usernames = Array.isArray(activeTpl.allowed_usernames) && activeTpl.allowed_usernames.length > 0
    ? activeTpl.allowed_usernames
    : (activeTpl.default_sender ? [activeTpl.default_sender] : []);

  if (usernames.length === 0) {
    select.innerHTML = '<option value="">(Belum ada username untuk template ini)</option>';
    select.disabled = true;
    hiddenInput.value = '';
    return;
  }

  select.disabled = false;
  usernames.forEach(u => {
    const opt = document.createElement('option');
    opt.value = u;
    opt.textContent = u;
    select.appendChild(opt);
  });

  // Tentukan username terpilih (prioritaskan default_sender jika ada di list)
  const defaultUser = (activeTpl.default_sender && usernames.includes(activeTpl.default_sender))
    ? activeTpl.default_sender
    : usernames[0];

  select.value = defaultUser;
  hiddenInput.value = defaultUser;
}

function handleSenderChange(val) {
  const hiddenInput = document.getElementById('usernameInput');
  if (hiddenInput) hiddenInput.value = val;
}

// Handler nomor tujuan yang dimasukkan oleh user
function handleTujuanInput(val) {
  const badgeEl = document.getElementById('activeTargetBadge');
  if (badgeEl) {
    const clean = (val || '').trim();
    badgeEl.textContent = clean ? 'Tujuan: ' + clean : 'Tujuan: (Belum diisi)';
    badgeEl.style.color = clean ? 'var(--primary)' : 'var(--muted)';
  }
}

// Inisialisasi: nomor tujuan selalu kosong secara default sesuai permintaan user
function loadSavedConfig() {
  const tujuanEl = document.getElementById('tujuanInput');
  const badgeEl = document.getElementById('activeTargetBadge');
  if (tujuanEl) tujuanEl.value = '';
  if (badgeEl) badgeEl.textContent = 'Tujuan: (Belum diisi)';
}

function saveConfigToStorage() {
  // Disimpan jika diperlukan untuk sesi aktif
}

// Banner Alert Controller (Hijau = Sukses, Merah = Gagal, Kuning = Fallback)
function showRedeemBanner(status, message) {
  const banner = document.getElementById('redeemStatusBanner');
  const bannerIcon = document.getElementById('bannerIcon');
  const bannerTitle = document.getElementById('bannerTitle');
  const bannerMessage = document.getElementById('bannerMessage');

  bannerMessage.textContent = message || '-';
  banner.style.display = 'block';

  if (status === 'success') {
    // Hijau
    banner.style.background = '#dcfce7';
    banner.style.color = '#14532d';
    banner.style.border = '1px solid #86efac';
    bannerIcon.textContent = '✅';
    bannerTitle.textContent = 'Berhasil! (Success)';
  } else if (status === 'failed') {
    // Merah
    banner.style.background = '#fee2e2';
    banner.style.color = '#7f1d1d';
    banner.style.border = '1px solid #fca5a5';
    bannerIcon.textContent = '❌';
    bannerTitle.textContent = 'Gagal! (Failed)';
  } else {
    // Fallback / Warning
    banner.style.background = '#fef3c7';
    banner.style.color = '#78350f';
    banner.style.border = '1px solid #fde68a';
    bannerIcon.textContent = '⚠️';
    bannerTitle.textContent = 'Info Respon Target';
  }

  // Scroll lembut ke banner jika posisi layar di bawah
  banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeRedeemBanner() {
  const banner = document.getElementById('redeemStatusBanner');
  if (banner) banner.style.display = 'none';
}

async function handleRedeemSubmit(e) {
  e.preventDefault();

  saveConfigToStorage();

  if (!currentVoucherTemplateId) {
    alert('Silakan pilih salah satu Template Voucher terlebih dahulu!');
    document.getElementById('voucherTemplateSelect').focus();
    return;
  }

  const username = document.getElementById('usernameInput').value.trim();
  const tujuan = document.getElementById('tujuanInput').value.trim();
  const voucher = document.getElementById('voucherInput').value.trim();

  if (!username) {
    alert('Template ini belum memiliki username/pengirim yang diatur admin. Silakan atur di Voucher Settings!');
    return;
  }

  if (!tujuan) {
    alert('Nomor tujuan tidak boleh kosong! Silakan masukkan nomor tujuan atau pilih dari template.');
    document.getElementById('tujuanInput').focus();
    return;
  }

  if (!voucher) {
    alert('Silakan masukkan nomor voucher!');
    document.getElementById('voucherInput').focus();
    return;
  }

  const submitBtn = document.getElementById('submitBtn');
  const submitText = document.getElementById('submitText');
  const submitIcon = document.getElementById('submitIcon');
  const loadingBox = document.getElementById('loadingBox');
  const resultBadge = document.getElementById('resultBadge');
  const outputBox = document.getElementById('generatedOutput');
  const trxInfoPill = document.getElementById('trxInfoPill');

  // 2. Set Indikator Loading / Spinner
  closeRedeemBanner();
  submitBtn.disabled = true;
  submitIcon.textContent = '⏳';
  submitText.textContent = 'Memproses & Membaca Target...';
  loadingBox.classList.remove('hidden');
  document.getElementById('loadingMessage').textContent = 'Menghubungi target & memindai konten voucher...';
  resultBadge.className = 'badge';
  resultBadge.textContent = 'Processing';
  trxInfoPill.style.display = 'none';

  try {
    // 3. Panggil API Proxy dari Tahap 3 (check_voucher.php) dengan template yang dipilih
    const tplParam = `&template_id=${encodeURIComponent(currentVoucherTemplateId)}`;
    const proxyEndpoint = `check_voucher.php?voucher=${encodeURIComponent(voucher)}${tplParam}`;
    const res = await fetch(proxyEndpoint);
    const json = await res.json();

    // Sembunyikan Loading State
    loadingBox.classList.add('hidden');

    const status = json.status || 'fallback';
    const message = json.message || 'Respon telah diterima dari server.';

    // 4. Tampilkan Komponen Alert/Banner Status (Merah jika failed, Hijau jika success)
    showRedeemBanner(status, message);

    // Update Badge & Generated Output Box
    if (status === 'success') {
      resultBadge.className = 'badge success';
      resultBadge.textContent = 'SUKSES';
    } else if (status === 'pending') {
      resultBadge.className = 'badge warning';
      resultBadge.textContent = 'PENDING';
    } else if (status === 'failed') {
      resultBadge.className = 'badge danger';
      resultBadge.textContent = 'GAGAL';
    } else {
      resultBadge.className = 'badge warning';
      resultBadge.textContent = (status).toUpperCase();
    }

    // Tampilkan status voucher dan no voucher saja untuk hasilnya
    outputBox.value = `Status Voucher : ${(status).toUpperCase()}\nNo Voucher     : ${voucher}`;

    // Update Pill Info
    trxInfoPill.style.display = 'block';
    document.getElementById('pillTrxNo').textContent = voucher;
    document.getElementById('pillStatus').textContent = (status).toUpperCase();
    if (status === 'success') {
      document.getElementById('pillStatus').style.color = 'var(--success)';
    } else if (status === 'pending') {
      document.getElementById('pillStatus').style.color = 'var(--warning)';
    } else if (status === 'failed') {
      document.getElementById('pillStatus').style.color = 'var(--danger)';
    } else {
      document.getElementById('pillStatus').style.color = 'var(--warning)';
    }

    // Dapatkan nama template yang sedang aktif
    const activeTpl = voucherTemplates.find(t => t.id === currentVoucherTemplateId);
    const templateName = activeTpl ? activeTpl.name : '';

    let dbStatus = 'PENDING';
    if (status === 'success') dbStatus = 'SUKSES';
    else if (status === 'failed') dbStatus = 'GAGAL';

    // Simpan Log ke Database dengan data template
    await saveToDatabase({
      template_id: currentVoucherTemplateId,
      template_name: templateName,
      trx_no: voucher,
      pengirim: username || 'Web User',
      tujuan: tujuan || '-',
      voucher: voucher,
      status: dbStatus,
      saldo: '-',
      message: message,
      raw_response: JSON.stringify(json)
    });

    // Kosongkan voucher input agar siap scan/input voucher berikutnya
    document.getElementById('voucherInput').value = '';
    document.getElementById('voucherInput').focus();

    // Reload Riwayat Transaksi 1 bulan
    fetchRedeemHistory();

  } catch (err) {
    loadingBox.classList.add('hidden');
    resultBadge.className = 'badge danger';
    resultBadge.textContent = 'Error';
    showRedeemBanner('failed', 'Gagal memproses voucher: ' + err.message);
    outputBox.value = 'Request Failed:\n' + err.message;
  } finally {
    submitBtn.disabled = false;
    submitIcon.textContent = '⚡';
    submitText.textContent = 'Process Redeem';
  }
}

async function saveToDatabase(data) {
  try {
    await fetch('redeem_api.php?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
  } catch (err) {
    console.warn('Gagal menyimpan ke DB:', err);
  }
}

function debouncedFetchHistory() {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(fetchRedeemHistory, 300);
}

// Mengisi dropdown filter template di bagian riwayat
function populateHistoryTemplateFilter() {
  const sel = document.getElementById('historyTemplateFilterSelect');
  if (!sel) return;

  const currentVal = sel.value;
  let html = '<option value="all">Semua Template</option>';
  voucherTemplates.forEach(t => {
    html += `<option value="${t.id}">${t.name}</option>`;
  });
  sel.innerHTML = html;
  if (currentVal) sel.value = currentVal;
}

async function fetchRedeemHistory() {
  const search = document.getElementById('historySearchInput').value.trim();
  const status = document.getElementById('statusFilterSelect').value;
  const templateId = document.getElementById('historyTemplateFilterSelect') ? document.getElementById('historyTemplateFilterSelect').value : 'all';
  const sort = document.getElementById('historySortSelect') ? document.getElementById('historySortSelect').value : 'date_desc';
  const limit = document.getElementById('historyLimitSelect') ? document.getElementById('historyLimitSelect').value : '20';

  const url = `redeem_api.php?action=list&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&template_id=${encodeURIComponent(templateId)}&sort=${encodeURIComponent(sort)}&limit=${encodeURIComponent(limit)}`;

  try {
    const res = await fetch(url);
    const json = await res.json();

    if (json.status === 'success') {
      historyData = json.data || [];
      renderHistoryTable(historyData);

      if (json.summary) {
        document.getElementById('statTotal').textContent = json.summary.total || 0;
        document.getElementById('statSukses').textContent = json.summary.total_sukses || 0;
        if (document.getElementById('statPending')) {
          document.getElementById('statPending').textContent = json.summary.total_pending || 0;
        }
        document.getElementById('statGagal').textContent = json.summary.total_gagal || 0;
      }
    }
  } catch (e) {
    console.error('Error load history:', e);
  }
}

// Cek ulang item tertentu di riwayat (recheck)
async function recheckHistoryItem(id, btnElement) {
  if (!id) return;
  const originalHtml = btnElement ? btnElement.innerHTML : '';
  if (btnElement) {
    btnElement.disabled = true;
    btnElement.innerHTML = '⏳ Cek...';
  }

  try {
    const res = await fetch('redeem_api.php?action=recheck_item', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchRedeemHistory();
    } else {
      alert(json.message || 'Gagal mengecek ulang.');
    }
  } catch (err) {
    alert('Error: ' + err.message);
  } finally {
    if (btnElement) {
      btnElement.disabled = false;
      btnElement.innerHTML = originalHtml;
    }
  }
}

function renderHistoryTable(items) {
  const tbody = document.getElementById('historyTableBody');
  if (!items || items.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align: center; padding: 28px; color: var(--muted);">
          Tidak ada data transaksi redeem yang sesuai dengan filter.
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = items.map(item => {
    let badgeClass = 'badge';
    let badgeText = item.status || 'UNKNOWN';

    if (badgeText === 'SUKSES') {
      badgeClass = 'badge success';
    } else if (badgeText === 'PENDING' || badgeText === 'MENUNGGU') {
      badgeClass = 'badge warning';
      badgeText = 'PENDING';
    } else if (badgeText === 'GAGAL') {
      badgeClass = 'badge danger';
    }

    const cleanDate = item.created_at ? item.created_at.replace('T', ' ') : '-';
    const formattedSaldo = item.saldo && item.saldo !== '-' ? 'Rp ' + Number(item.saldo).toLocaleString('id-ID') : '-';
    const tplLabel = item.template_name || (item.template_id ? item.template_id : '-');

    return `
      <tr>
        <td style="white-space: nowrap; font-size: 12px; color: var(--muted);">${cleanDate}</td>
        <td>
          <span style="font-size: 12px; font-weight: 600; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; display: inline-block;">
            ${tplLabel}
          </span>
        </td>
        <td style="font-family: monospace; font-weight: 600; color: var(--primary);">${item.trx_no ? '#' + item.trx_no : '-'}</td>
        <td style="font-family: monospace; font-weight: 600;">${item.voucher}</td>
        <td style="font-family: monospace;">${item.tujuan}</td>
        <td style="font-size: 12px; color: var(--muted);">${item.pengirim}</td>
        <td><span class="${badgeClass}">${badgeText}</span></td>
        <td style="font-weight: 600; color: var(--success);">${formattedSaldo}</td>
        <td style="max-width: 260px; font-size: 12px; color: #475569;" title="${(item.message || '').replace(/"/g, '&quot;')}">
          <span class="truncate">${item.message || '-'}</span>
        </td>
        <td style="text-align: center;">
          <button type="button" class="secondary-button" onclick="recheckHistoryItem(${item.id}, this)" style="height: 28px; min-height: 28px; padding: 0 8px; font-size: 11px; font-weight: 600;" title="Cek ulang respon target sekarang">
            🔄 Cek
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function copyResultOutput() {
  const output = document.getElementById('generatedOutput').value;
  if (!output) {
    alert('Tidak ada teks untuk disalin!');
    return;
  }
  navigator.clipboard.writeText(output).then(() => {
    alert('Hasil berhasil disalin ke clipboard!');
  }).catch(() => {
    alert('Gagal menyalin text');
  });
}

function exportRedeemHistoryXlsx() {
  if (!historyData || historyData.length === 0) {
    alert('Belum ada data riwayat untuk didownload!');
    return;
  }

  if (typeof XLSX === 'undefined') {
    alert('Library Excel (XLSX) sedang dimuat, silakan coba beberapa detik lagi.');
    return;
  }

  const headers = [
    'Waktu',
    'Template',
    'No TRX',
    'Nomor Voucher',
    'Nomor Tujuan',
    'Nomor Pengirim',
    'Status',
    'Saldo',
    'Pesan Keterangan'
  ];

  const rows = [headers];

  historyData.forEach(item => {
    rows.push([
      String(item.created_at || ''),
      String(item.template_name || item.template_id || ''),
      String(item.trx_no || ''),
      String(item.voucher || ''),
      String(item.tujuan || ''),
      String(item.pengirim || ''),
      String(item.status || ''),
      String(item.saldo || ''),
      String(item.message || '')
    ]);
  });

  const ws = XLSX.utils.aoa_to_sheet(rows);

  // Pastikan tipe cell untuk TRX, Voucher, Tujuan, Pengirim diset sebagai string ('s')
  // agar Excel tidak mengubahnya menjadi notasi ilmiah (e.g. 8.6E+15) atau menghilangkan angka 0 di depan
  const range = XLSX.utils.decode_range(ws['!ref'] || 'A1:I1');
  for (let R = range.s.r + 1; R <= range.e.r; ++R) {
    for (let C = range.s.c; C <= range.e.c; ++C) {
      const cellAddress = XLSX.utils.encode_cell({ r: R, c: C });
      const cell = ws[cellAddress];
      if (cell) {
        // C=2 (No TRX), C=3 (Nomor Voucher), C=4 (Nomor Tujuan), C=5 (Nomor Pengirim)
        if (C === 2 || C === 3 || C === 4 || C === 5) {
          cell.t = 's';
          cell.z = '@';
        }
      }
    }
  }

  // Hitung lebar kolom otomatis yang aman tanpa call stack error
  const colWidths = headers.map((h, colIdx) => {
    let maxLen = h.length;
    for (let r = 0; r < rows.length; r++) {
      const val = rows[r][colIdx] ? String(rows[r][colIdx]) : '';
      if (val.length > maxLen) {
        maxLen = val.length;
      }
    }
    return { wch: Math.min(Math.max(maxLen + 3, 12), 50) };
  });
  ws['!cols'] = colWidths;

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Riwayat Redeem');

  const fileName = `riwayat_redeem_${new Date().toISOString().slice(0, 10)}.xlsx`;
  XLSX.writeFile(wb, fileName);
}

// Alias untuk kompatibilitas jika dipanggil dari tempat lain
window.exportRedeemHistory = exportRedeemHistoryXlsx;

// Inisialisasi halaman saat dibuka
document.addEventListener('DOMContentLoaded', () => {
  fetchVoucherTemplates();
  loadSavedConfig();

  const sec = document.getElementById('configSection');
  if (sec) sec.style.display = 'none';

  fetchRedeemHistory();
});
</script>

<?php
include 'includes/footer.php';
?>

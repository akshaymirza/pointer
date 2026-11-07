<?php
// voucher_settings.php - Page 2: Halaman Pengaturan & Manajemen Template Voucher
require_once 'includes/auth.php';
$pageTitle = "Pengaturan Template Voucher - Dynamic Template Parser System";
$currentPage = "voucher_settings";
$pageHeading = "Kelola Template Target Voucher";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="page active" id="voucherSettingsPage">
  <div class="section-title">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
      <div>
        <p class="eyebrow">Direct Service / Voucher Template Manager</p>
        <h2>Page 2: Kelola & Atur Template Target Voucher</h2>
        <p style="margin-top: 4px; color: var(--muted); font-size: 14px;">
          Buat dan kelola banyak template target sekaligus (seperti pada Checking Mode) untuk berbagai jenis provider atau endpoint pengecekan.
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button type="button" class="primary-button" onclick="createNewTemplateForm()" style="height: 42px; font-size: 13px;">
          + Buat Template Baru
        </button>
        <a href="redeem.php" class="secondary-button" style="text-decoration: none; height: 42px; font-size: 13px;">
          ← Ke Halaman Redeem
        </a>
      </div>
    </div>
  </div>

  <!-- Notification Toast / Alert -->
  <div id="alertBox" style="display: none; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 600; animation: fadeIn 0.2s ease;"></div>

  <!-- DAFTAR SEMUA TEMPLATE (Tabel mirip Dashboard/Checking Mode) -->
  <section class="content-card table-card" style="margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
    <div class="card-heading" style="flex-wrap: wrap; gap: 12px;">
      <div>
        <h2>Daftar Template Target Aktif</h2>
        <p>Pilih template untuk diedit atau tambahkan template baru.</p>
      </div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <input 
          type="text" 
          id="templateSearchInput" 
          placeholder="Cari nama template / URL..." 
          oninput="filterTemplateTable()"
          style="height: 38px; width: 220px; font-size: 13px;"
        />
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width: 220px;">Nama Template</th>
            <th style="width: 180px;">Username / Pengirim</th>
            <th>Base Target URL</th>
            <th style="width: 120px;">Keyword Sukses</th>
            <th style="width: 120px;">Keyword Gagal</th>
            <th style="width: 80px; text-align: center;">Default</th>
            <th style="width: 120px; text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody id="templateTableBody">
          <tr>
            <td colspan="7" style="text-align: center; padding: 24px; color: var(--muted);">Memuat daftar template...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- FORM EDITOR -->
  <div class="workspace-layout" style="grid-template-columns: 1fr; align-items: start; gap: 24px;">
    
    <!-- Form Editor Template -->
    <div class="workspace-main">
      <section class="content-card form-card" id="formCardSection" style="box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <div class="card-heading">
          <div>
            <h2 id="formTitle">Edit Template</h2>
            <p id="formSubtitle">Konfigurasi parameter dan kondisi respon untuk template yang dipilih.</p>
          </div>
          <span class="badge" id="formModeBadge">Editing</span>
        </div>

        <form id="voucherConfigForm" onsubmit="handleSaveTemplate(event)">
          <input type="hidden" id="templateIdInput" name="id" value="" />

          <!-- Nama & Deskripsi Template -->
          <div class="form-grid two-col" style="grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
            <label>
              <span>Nama Template *</span>
              <input 
                type="text" 
                id="templateName" 
                name="name" 
                placeholder="Contoh: Three API Direct / Google Search" 
                required 
                style="height: 46px; font-weight: 600;"
              />
            </label>

            <label>
              <span>Deskripsi Singkat</span>
              <input 
                type="text" 
                id="templateDescription" 
                name="description" 
                placeholder="Keterangan fungsi template ini" 
                style="height: 46px;"
              />
            </label>
          </div>

          <!-- Pengaturan Username & Nomor Pengirim Khusus Template Ini -->
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 16px 18px; margin-bottom: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
              <span style="font-size: 14px; font-weight: 700; color: #1e3a8a;">📱 Pengaturan Username / Pengirim Khusus Template Ini</span>
              <span style="font-size: 11px; color: var(--primary); font-weight: 600;">Eksklusif per Template</span>
            </div>

            <div class="form-grid two-col" style="grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 8px;">
              <label>
                <span style="font-size: 13px; font-weight: 600; color: #1e40af;">Daftar Username Diberikan Admin (Bisa Lebih Dari 1) *</span>
                <textarea 
                  id="templateAllowedUsernames" 
                  name="allowed_usernames" 
                  rows="3" 
                  placeholder="Contoh:&#10;089523098721&#10;089512345678" 
                  oninput="syncDefaultSenderDropdown()"
                  style="height: 80px; font-family: monospace; font-size: 13px; font-weight: 600; line-height: 1.5; resize: vertical;"
                ></textarea>
                <small style="color: #1e40af; font-size: 11px;">1 username per baris atau pisahkan dengan koma.</small>
              </label>

              <label>
                <span style="font-size: 13px; font-weight: 600; color: #1e40af;">Username Utama (Default Terpilih)</span>
                <select 
                  id="templateDefaultSender" 
                  name="default_sender" 
                  style="height: 46px; font-family: monospace; font-size: 14px; font-weight: 600; background: white;"
                >
                  <option value="">-- Masukkan daftar username di sebelah kiri dahulu --</option>
                </select>
                <small style="color: #1e40af; font-size: 11px;">Pilih 1 username dari daftar di kiri yang otomatis terpilih di Halaman Redeem.</small>
              </label>
            </div>
            <p style="margin: 0; font-size: 12px; color: #1e40af; line-height: 1.4;">
              💡 <em>Catatan:</em> Di halaman Redeem, saat template ini dipilih, hanya daftar username di atas yang muncul. Jika user berganti template, username template lama otomatis hilang dan digantikan oleh username milik template baru.
            </p>
          </div>

          <!-- Jadikan Default Checkbox -->
          <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; cursor: pointer; font-size: 13px;">
            <input type="checkbox" id="templateIsDefault" name="is_default" style="width: 18px; height: 18px;" />
            <span>Jadikan template default yang langsung terpilih di Halaman Redeem</span>
          </label>

          <!-- 1. BASE TARGET URL -->
          <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 18px; margin-bottom: 20px;">
            <label style="display: grid; gap: 8px;">
              <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 8px;">
                <span style="font-size: 15px; font-weight: 700; color: #1e293b;">1. Base Target URL *</span>
                <span style="font-size: 12px; color: #1e40af; font-weight: 600; background: #eff6ff; padding: 2px 8px; border-radius: 6px;">
                  Parameter Tetap: <code>{username}</code> & <code>{voucher}</code>
                </span>
              </div>
              <input 
                type="text" 
                id="baseTargetUrl" 
                name="base_target_url"
                placeholder="Contoh: https://example.com/check-voucher?username={username}&voucher={voucher}&idtrx={idtrx}" 
                required
                oninput="handleBaseTargetUrlInput()"
                style="font-family: monospace; font-size: 13px; height: 48px;"
              />
              <small style="color: var(--muted); font-size: 12px; line-height: 1.5;">
                Link utama yang akan dieksekusi di latar belakang oleh Proxy.<br>
                • <code>{username}</code>: Parameter tetap untuk username/nomor pengirim terpilih di Halaman Redeem.<br>
                • <code>{voucher}</code>: Parameter tetap untuk nomor kode voucher yang diinput user.<br>
                • <code>{tujuan}</code>: (Opsional) Parameter untuk nomor tujuan jika URL target membutuhkannya.<br>
                • Parameter ID Transaksi (opsional): otomatis diisi nilai unik baru (contoh: <code>rt1001</code>) jika diaktifkan di bawah.
              </small>

              <!-- PENGATURAN ID TRANSAKSI OTOMATIS PADA TARGET URL -->
              <div style="margin-top: 14px; padding: 14px 16px; background: white; border: 1.5px solid #cbd5e1; border-radius: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 0;">
                  <input type="checkbox" id="enableTrxId" name="enable_trx_id" onchange="toggleTrxIdSettings()" style="width: 17px; height: 17px;" />
                  <span>🆔 Sertakan ID Transaksi Otomatis pada Target URL (Kasus Spesial Server API)</span>
                </label>

                <div id="trxIdSettingsContainer" style="display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #cbd5e1;">
                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                    
                    <!-- 1. Pilihan Variable URL -->
                    <label style="display: grid; gap: 4px;">
                      <span style="font-size: 12px; font-weight: 600; color: #1e40af;">Pilih Variable pada URL Target *</span>
                      <select id="trxIdVariableSelect" onchange="handleTrxIdVariableSelectChange()" style="height: 42px; font-family: monospace; font-size: 13px; font-weight: 600;">
                        <option value="">-- Ketik URL dahulu / Pilih Variable --</option>
                      </select>
                      <input type="text" id="trxIdVariableCustom" placeholder="Ketik nama variable (contoh: idtrx)" style="margin-top: 6px; height: 38px; font-family: monospace; font-size: 12px; display: none;" oninput="updateTrxIdPreview()" />
                      <small style="color: var(--muted); font-size: 11px;">Variable di Target URL yang akan diisi ID transaksi (contoh: <code>idtrx</code> atau <code>refid</code>).</small>
                    </label>

                    <!-- 2. Prefix ID Transaksi -->
                    <label style="display: grid; gap: 4px;">
                      <span style="font-size: 12px; font-weight: 600; color: #1e40af;">Prefix ID Transaksi (Huruf/Kode) *</span>
                      <input 
                        type="text" 
                        id="trxIdPrefix" 
                        name="trx_id_prefix" 
                        placeholder="Contoh: rt (Redeem Telkomsel)" 
                        oninput="updateTrxIdPreview()"
                        style="height: 42px; font-family: monospace; font-size: 13px; font-weight: 600;" 
                      />
                      <small style="color: var(--muted); font-size: 11px;">Kode awalan ID (contoh: <code>rt</code>, <code>rx</code>, <code>ra</code>).</small>
                    </label>

                    <!-- 3. Nomor Urut Terakhir / Awal (Counter) -->
                    <label style="display: grid; gap: 4px;">
                      <span style="font-size: 12px; font-weight: 600; color: #1e40af;">Nomor Urut Terakhir / Awal</span>
                      <input 
                        type="number" 
                        id="trxIdLastNumber" 
                        name="trx_id_last_number" 
                        value="1000" 
                        min="1" 
                        oninput="updateTrxIdPreview()"
                        style="height: 42px; font-family: monospace; font-size: 13px; font-weight: 600;" 
                      />
                      <small style="color: var(--muted); font-size: 11px;">Counter angka (contoh: isi <code>1000</code> agar transaksi pertama = <code>1001</code>).</small>
                    </label>
                  </div>

                  <!-- Preview Box -->
                  <div style="margin-top: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #1e40af; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <span>💡 Contoh ID yang akan di-generate otomatis: <strong id="trxIdPreviewBadge" style="font-family: monospace; font-size: 13px; background: white; padding: 2px 8px; border-radius: 4px; border: 1px solid #93c5fd;">rt1001</strong> (lalu rt1002, dst.)</span>
                    <span style="font-size: 11px; color: #2563eb;">Otomatis berbeda setiap kali redeem, kecuali retry voucher & nomor sama.</span>
                  </div>
                </div>
              </div>
            </label>
          </div>

          <!-- 2. ATURAN KATA KUNCI & PESAN SUKSES -->
          <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
              <span style="font-size: 18px;">✅</span>
              <div>
                <strong style="font-size: 15px; color: #166534;">2. Aturan Kata Kunci & Pesan Sukses</strong>
                <p style="margin: 2px 0 0; font-size: 12px; color: #15803d;">Jika kata kunci ini ditemukan di respon target, voucher dianggap Sukses / Aktif.</p>
              </div>
            </div>

            <div class="form-grid" style="gap: 14px;">
              <label>
                <span style="font-size: 13px; color: #166534;">Kata Kunci Sukses (Success Keyword)</span>
                <input 
                  type="text" 
                  id="successKeyword" 
                  name="success_keyword"
                  placeholder="Contoh: berhasil / SUKSES / aktif / unlock" 
                  style="border-color: #86efac; height: 44px; font-weight: 600;"
                />
                <small style="color: #15803d; font-size: 11px;">Pencarian tidak sensitif huruf besar/kecil (case-insensitive).</small>
              </label>

              <label>
                <span style="font-size: 13px; color: #166534;">Pesan Kustom Sukses (Tampil di Page 1)</span>
                <textarea 
                  id="successCustomMessage" 
                  name="success_custom_message"
                  rows="2" 
                  placeholder="Contoh: Voucher berhasil divalidasi dan saldo telah masuk ke akun."
                  style="border-color: #86efac; min-height: 70px; font-size: 13px;"
                ></textarea>
              </label>
            </div>
          </div>

          <!-- 3. ATURAN KATA KUNCI & PESAN PENDING / MENUNGGU -->
          <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
              <span style="font-size: 18px;">⏳</span>
              <div>
                <strong style="font-size: 15px; color: #92400e;">3. Aturan Kata Kunci & Pesan Pending (Menunggu)</strong>
                <p style="margin: 2px 0 0; font-size: 12px; color: #b45309;">Jika kata kunci ini terdeteksi pada respon target, voucher ditandai Pending / Menunggu antrian.</p>
              </div>
            </div>

            <div class="form-grid" style="gap: 14px;">
              <label>
                <span style="font-size: 13px; color: #92400e;">Kata Kunci Pending (Pending Keyword)</span>
                <input 
                  type="text" 
                  id="pendingKeyword" 
                  name="pending_keyword"
                  placeholder="Contoh: menunggu / pending / antrian / proses" 
                  style="border-color: #fcd34d; height: 44px; font-weight: 600;"
                />
                <small style="color: #b45309; font-size: 11px;">Pencarian tidak sensitif huruf besar/kecil (case-insensitive).</small>
              </label>

              <label>
                <span style="font-size: 13px; color: #92400e;">Pesan Kustom Pending (Tampil di Page 1)</span>
                <textarea 
                  id="pendingCustomMessage" 
                  name="pending_custom_message"
                  rows="2" 
                  placeholder="Contoh: Voucher sedang dalam antrian atau sedang diproses oleh sistem."
                  style="border-color: #fcd34d; min-height: 70px; font-size: 13px;"
                ></textarea>
              </label>
            </div>
          </div>

          <!-- 4. ATURAN KATA KUNCI & PESAN GAGAL -->
          <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 18px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
              <span style="font-size: 18px;">❌</span>
              <div>
                <strong style="font-size: 15px; color: #991b1b;">4. Aturan Kata Kunci & Pesan Gagal</strong>
                <p style="margin: 2px 0 0; font-size: 12px; color: #b91c1c;">Jika kata kunci ini terdeteksi pada respon target, voucher ditandai Gagal.</p>
              </div>
            </div>

            <div class="form-grid" style="gap: 14px;">
              <label>
                <span style="font-size: 13px; color: #991b1b;">Kata Kunci Gagal (Failed Keyword)</span>
                <input 
                  type="text" 
                  id="failedKeyword" 
                  name="failed_keyword"
                  placeholder="Contoh: gagal / tidak ditemukan / expired / sudah digunakan" 
                  style="border-color: #fca5a5; height: 44px; font-weight: 600;"
                />
                <small style="color: #b91c1c; font-size: 11px;">Pencarian tidak sensitif huruf besar/kecil (case-insensitive).</small>
              </label>

              <label>
                <span style="font-size: 13px; color: #991b1b;">Pesan Kustom Gagal (Tampil di Page 1)</span>
                <textarea 
                  id="failedCustomMessage" 
                  name="failed_custom_message"
                  rows="2" 
                  placeholder="Contoh: Voucher tidak valid atau masa berlaku telah habis."
                  style="border-color: #fca5a5; min-height: 70px; font-size: 13px;"
                ></textarea>
              </label>
            </div>
          </div>

          <!-- Tombol Simpan -->
          <div class="action-row" style="display: flex; gap: 12px; align-items: center; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 18px;">
            <div style="font-size: 12px; color: var(--muted);">
              Terakhir diperbarui: <span id="lastUpdatedLabel" style="font-weight: 600;">-</span>
            </div>
            <div style="display: flex; gap: 10px;">
              <button type="button" class="secondary-button" onclick="createNewTemplateForm()" style="height: 46px;">
                + Reset / Form Baru
              </button>
              <button type="submit" class="primary-button" id="saveTemplateBtn" style="height: 46px; min-width: 170px;">
                <span id="saveBtnIcon">💾</span>
                <span id="saveBtnText">Simpan Template</span>
              </button>
            </div>
          </div>

        </form>
      </section>
    </div>
  </div>
</section>

<script>
// Logic & State Handler Page 2 (Multi-Template Manager)
let allTemplates = [];
let activeEditingId = null;

// Tampilkan alert banner
function showAlert(message, type = 'success') {
  const alertBox = document.getElementById('alertBox');
  alertBox.textContent = message;
  alertBox.style.display = 'block';

  if (type === 'success') {
    alertBox.style.background = '#dcfce7';
    alertBox.style.color = '#15803d';
    alertBox.style.border = '1px solid #86efac';
  } else {
    alertBox.style.background = '#fee2e2';
    alertBox.style.color = '#b91c1c';
    alertBox.style.border = '1px solid #fca5a5';
  }

  setTimeout(() => {
    alertBox.style.display = 'none';
  }, 4000);
}

// 1. Muat Semua Template dari Backend
async function loadAllTemplates() {
  try {
    const res = await fetch('check_voucher.php?action=list_templates');
    const json = await res.json();
    if (json.status === 'success' && json.data) {
      allTemplates = json.data;
      renderTemplateTable(allTemplates);

      // Jika belum ada yang diedit, pilih template pertama
      if (allTemplates.length > 0 && !activeEditingId) {
        selectTemplateForEdit(allTemplates[0].id);
      }
    }
  } catch (err) {
    showAlert('Gagal memuat template: ' + err.message, 'error');
  }
}

// Render Tabel Template
function renderTemplateTable(items) {
  const tbody = document.getElementById('templateTableBody');
  if (!items || !items.length) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--muted);">Belum ada template. Silakan buat baru.</td></tr>`;
    return;
  }

  tbody.innerHTML = items.map(t => {
    const isSelected = (t.id === activeEditingId);
    const rowBg = isSelected ? 'background: #eff6ff;' : '';
    const defaultBadge = t.is_default 
      ? '<span class="badge success" style="font-size:10px;">★ DEFAULT</span>' 
      : '<span style="color:var(--muted); font-size:12px;">-</span>';

    // Rangkum username
    const usernames = Array.isArray(t.allowed_usernames) ? t.allowed_usernames : (t.default_sender ? [t.default_sender] : []);
    let senderDisplay = '<span style="color:var(--muted); font-size:11px;">Belum diatur</span>';
    if (usernames.length > 0) {
      senderDisplay = usernames.map(u => {
        const isMain = (u === t.default_sender) ? ' style="border: 1px solid #2563eb; font-weight:700;" title="Username Utama (Default)"' : '';
        return `<span style="font-family:monospace; font-size:12px; color:#1e3a8a; background:#e0f2fe; padding:2px 6px; border-radius:4px; margin-right:4px; display:inline-block; margin-bottom:2px;"${isMain}>${escapeHtml(u)}${u === t.default_sender ? ' ★' : ''}</span>`;
      }).join('');
    }

    return `
      <tr style="${rowBg} border-bottom: 1px solid var(--border);">
        <td>
          <strong style="color: #1e293b;">${escapeHtml(t.name)}</strong>
          ${t.description ? `<br><small style="color:var(--muted); font-size:11px;">${escapeHtml(t.description)}</small>` : ''}
        </td>
        <td>
          <div>${senderDisplay}</div>
        </td>
        <td>
          <span class="truncate" style="max-width: 250px; font-family: monospace; font-size: 11px; color: var(--primary);" title="${escapeHtml(t.base_target_url)}">
            ${escapeHtml(t.base_target_url)}
          </span>
          ${t.trx_id_variable ? `<br><span class="badge" style="background:#e0e7ff; color:#3730a3; font-size:10px; margin-top:3px; display:inline-block;" title="ID Transaksi Otomatis aktif">🆔 ${escapeHtml(t.trx_id_variable)}: ${escapeHtml(t.trx_id_prefix || 'rt')}...</span>` : ''}
        </td>
        <td><span style="font-weight: 600; color: #166534; font-size: 12px;">${escapeHtml(t.success_keyword || '-')}</span></td>
        <td><span style="font-weight: 600; color: #991b1b; font-size: 12px;">${escapeHtml(t.failed_keyword || '-')}</span></td>
        <td style="text-align: center;">${defaultBadge}</td>
        <td style="text-align: right;">
          <div class="action-group" style="justify-content: flex-end;">
            <button type="button" class="text-button" onclick="selectTemplateForEdit('${t.id}')">Edit</button>
            <button type="button" class="text-button danger" onclick="handleDeleteTemplate('${t.id}', '${escapeHtml(t.name)}')">Hapus</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function filterTemplateTable() {
  const query = document.getElementById('templateSearchInput').value.toLowerCase().trim();
  if (!query) {
    renderTemplateTable(allTemplates);
    return;
  }
  const filtered = allTemplates.filter(t => {
    const uList = (Array.isArray(t.allowed_usernames) ? t.allowed_usernames.join(' ') : (t.default_sender || '')).toLowerCase();
    return (t.name && t.name.toLowerCase().includes(query)) ||
      uList.includes(query) ||
      (t.base_target_url && t.base_target_url.toLowerCase().includes(query)) ||
      (t.description && t.description.toLowerCase().includes(query));
  });
  renderTemplateTable(filtered);
}

// Helper sinkronisasi dropdown Username Utama dari isi textarea
function syncDefaultSenderDropdown(selectedValue = '') {
  const textarea = document.getElementById('templateAllowedUsernames');
  const select = document.getElementById('templateDefaultSender');
  if (!textarea || !select) return;

  const currentVal = selectedValue || select.value;
  const usernames = textarea.value
    .split(/[\r\n,]+/)
    .map(s => s.trim())
    .filter(s => s.length > 0);

  select.innerHTML = '';

  if (usernames.length === 0) {
    select.innerHTML = '<option value="">-- Masukkan daftar username di sebelah kiri dahulu --</option>';
    return;
  }

  usernames.forEach(u => {
    const opt = document.createElement('option');
    opt.value = u;
    opt.textContent = u;
    select.appendChild(opt);
  });

  if (currentVal && usernames.includes(currentVal)) {
    select.value = currentVal;
  } else {
    select.value = usernames[0];
  }
}

// Helper Pengaturan ID Transaksi Otomatis
function toggleTrxIdSettings() {
  const enabled = document.getElementById('enableTrxId').checked;
  const container = document.getElementById('trxIdSettingsContainer');
  if (container) {
    container.style.display = enabled ? 'block' : 'none';
  }
  if (enabled) {
    const url = document.getElementById('baseTargetUrl').value;
    syncTrxIdVariableDropdown(url, document.getElementById('trxIdVariableSelect').value);
    updateTrxIdPreview();
  }
}

function extractUrlVariables(url) {
  const vars = new Set();
  if (!url) return [];

  // 1. Ekstrak dari query string (?a=b&c=d)
  try {
    const qIndex = url.indexOf('?');
    if (qIndex !== -1) {
      const queryString = url.substring(qIndex + 1);
      const pairs = queryString.split('&');
      pairs.forEach(pair => {
        const parts = pair.split('=');
        const k = parts[0].trim();
        if (k && !k.startsWith('{')) {
          vars.add(k);
        }
      });
    }
  } catch (e) {}

  // 2. Ekstrak placeholder {var}
  const matches = url.matchAll(/\{([a-zA-Z0-9_.-]+)\}/g);
  for (const match of matches) {
    if (match[1]) vars.add(match[1]);
  }

  return Array.from(vars);
}

function syncTrxIdVariableDropdown(url, selectedVar = '') {
  const select = document.getElementById('trxIdVariableSelect');
  if (!select) return;

  const currentVal = selectedVar || select.value;
  const vars = extractUrlVariables(url);

  select.innerHTML = '';

  if (vars.length === 0) {
    select.innerHTML = '<option value="">-- Ketik URL Target di atas dahulu --</option>';
  } else {
    vars.forEach(v => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v + (['idtrx', 'trxid', 'refid', 'id'].includes(v.toLowerCase()) ? ' (Direkomendasikan)' : '');
      select.appendChild(opt);
    });
  }

  // Tambahkan opsi kustom
  const customOpt = document.createElement('option');
  customOpt.value = '__custom__';
  customOpt.textContent = '+ Ketik nama variable lain...';
  select.appendChild(customOpt);

  if (currentVal && vars.includes(currentVal)) {
    select.value = currentVal;
    document.getElementById('trxIdVariableCustom').style.display = 'none';
  } else if (currentVal && currentVal !== '__custom__') {
    select.value = '__custom__';
    const customInp = document.getElementById('trxIdVariableCustom');
    customInp.value = currentVal;
    customInp.style.display = 'block';
  } else {
    // Cari yang bernuansa trx jika ada
    const candidate = vars.find(v => ['idtrx', 'trxid', 'refid', 'trx_id'].includes(v.toLowerCase()));
    if (candidate) {
      select.value = candidate;
    } else if (vars.length > 0) {
      select.value = vars[0];
    }
    document.getElementById('trxIdVariableCustom').style.display = 'none';
  }
}

function handleTrxIdVariableSelectChange() {
  const select = document.getElementById('trxIdVariableSelect');
  const customInp = document.getElementById('trxIdVariableCustom');
  if (!select || !customInp) return;
  const isCustom = select.value === '__custom__';
  customInp.style.display = isCustom ? 'block' : 'none';
  if (isCustom) {
    customInp.focus();
  }
  updateTrxIdPreview();
}

function handleBaseTargetUrlInput() {
  const url = document.getElementById('baseTargetUrl').value;
  const currentVar = document.getElementById('trxIdVariableSelect').value;
  syncTrxIdVariableDropdown(url, currentVar);

  // Auto-detect prefix jika ada parameter idtrx=rt1001 di URL
  const prefixInput = document.getElementById('trxIdPrefix');
  if (prefixInput && !prefixInput.value.trim()) {
    const match = url.match(/[?&](?:idtrx|trxid|refid)=([a-zA-Z]+)(\d+)/i);
    if (match) {
      prefixInput.value = match[1];
      const startNum = parseInt(match[2], 10);
      if (!isNaN(startNum)) {
        document.getElementById('trxIdLastNumber').value = Math.max(1, startNum - 1);
      }
      document.getElementById('enableTrxId').checked = true;
      toggleTrxIdSettings();
    }
  }

  updateTrxIdPreview();
}

function updateTrxIdPreview() {
  const badge = document.getElementById('trxIdPreviewBadge');
  if (!badge) return;
  const prefix = document.getElementById('trxIdPrefix')?.value.trim() || 'rt';
  const lastNum = parseInt(document.getElementById('trxIdLastNumber')?.value, 10) || 1000;
  badge.textContent = prefix + (lastNum + 1);
}

// 2. Pilih Template untuk Diedit
function selectTemplateForEdit(id) {
  const t = allTemplates.find(item => item.id === id);
  if (!t) return;

  activeEditingId = id;
  document.getElementById('formTitle').textContent = 'Edit: ' + t.name;
  document.getElementById('formSubtitle').textContent = 'ID: ' + t.id;
  document.getElementById('formModeBadge').className = 'badge';
  document.getElementById('formModeBadge').textContent = 'Editing';

  document.getElementById('templateIdInput').value = t.id;
  document.getElementById('templateName').value = t.name || '';
  document.getElementById('templateDescription').value = t.description || '';
  
  // Format daftar username untuk textarea
  const uList = Array.isArray(t.allowed_usernames) && t.allowed_usernames.length 
    ? t.allowed_usernames 
    : (t.default_sender ? [t.default_sender] : []);
  document.getElementById('templateAllowedUsernames').value = uList.join('\n');
  
  // Sinkronkan dropdown Username Utama
  syncDefaultSenderDropdown(t.default_sender || (uList[0] || ''));

  document.getElementById('templateIsDefault').checked = !!t.is_default;
  document.getElementById('baseTargetUrl').value = t.base_target_url || '';

  // Sinkronkan pengaturan ID Transaksi
  const hasTrxId = !!t.trx_id_variable;
  document.getElementById('enableTrxId').checked = hasTrxId;
  toggleTrxIdSettings();
  syncTrxIdVariableDropdown(t.base_target_url || '', t.trx_id_variable || '');
  document.getElementById('trxIdPrefix').value = t.trx_id_prefix || '';
  document.getElementById('trxIdLastNumber').value = t.trx_id_last_number || 1000;
  updateTrxIdPreview();

  document.getElementById('successKeyword').value = t.success_keyword || '';
  document.getElementById('successCustomMessage').value = t.success_custom_message || '';
  document.getElementById('pendingKeyword').value = t.pending_keyword || '';
  document.getElementById('pendingCustomMessage').value = t.pending_custom_message || '';
  document.getElementById('failedKeyword').value = t.failed_keyword || '';
  document.getElementById('failedCustomMessage').value = t.failed_custom_message || '';
  document.getElementById('lastUpdatedLabel').textContent = t.updated_at || '-';

  renderTemplateTable(allTemplates);

  // Scroll ke form jika layar kecil
  document.getElementById('formCardSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// 3. Reset Form untuk Template Baru
function createNewTemplateForm() {
  activeEditingId = null;
  document.getElementById('formTitle').textContent = 'Buat Template Target Baru';
  document.getElementById('formSubtitle').textContent = 'Isi formulir untuk menambahkan konfigurasi target voucher baru.';
  document.getElementById('formModeBadge').className = 'badge warning';
  document.getElementById('formModeBadge').textContent = 'New Template';

  document.getElementById('templateIdInput').value = '';
  document.getElementById('templateName').value = '';
  document.getElementById('templateDescription').value = '';
  document.getElementById('templateAllowedUsernames').value = '';
  syncDefaultSenderDropdown('');
  document.getElementById('templateIsDefault').checked = false;
  document.getElementById('baseTargetUrl').value = 'https://example.com/check-voucher?username={username}&voucher={voucher}';

  document.getElementById('enableTrxId').checked = false;
  toggleTrxIdSettings();
  syncTrxIdVariableDropdown('');
  document.getElementById('trxIdPrefix').value = '';
  document.getElementById('trxIdLastNumber').value = '1000';
  updateTrxIdPreview();

  document.getElementById('successKeyword').value = 'SUKSES';
  document.getElementById('successCustomMessage').value = 'Voucher berhasil divalidasi dan aktif.';
  document.getElementById('pendingKeyword').value = 'menunggu';
  document.getElementById('pendingCustomMessage').value = 'Voucher sedang dalam antrian atau sedang diproses.';
  document.getElementById('failedKeyword').value = 'GAGAL';
  document.getElementById('failedCustomMessage').value = 'Voucher tidak valid atau sudah kadaluarsa.';
  document.getElementById('lastUpdatedLabel').textContent = 'Baru';

  renderTemplateTable(allTemplates);
  document.getElementById('templateName').focus();
}

// 4. Simpan Template ke Backend
async function handleSaveTemplate(e) {
  e.preventDefault();

  const id = document.getElementById('templateIdInput').value.trim();
  const name = document.getElementById('templateName').value.trim();
  const description = document.getElementById('templateDescription').value.trim();
  const allowedUsernamesRaw = document.getElementById('templateAllowedUsernames').value;
  const defaultSender = document.getElementById('templateDefaultSender').value.trim();
  const isDefault = document.getElementById('templateIsDefault').checked ? 1 : 0;
  const baseTargetUrl = document.getElementById('baseTargetUrl').value.trim();
  const successKeyword = document.getElementById('successKeyword').value.trim();
  const successCustomMessage = document.getElementById('successCustomMessage').value.trim();
  const pendingKeyword = document.getElementById('pendingKeyword').value.trim();
  const pendingCustomMessage = document.getElementById('pendingCustomMessage').value.trim();
  const failedKeyword = document.getElementById('failedKeyword').value.trim();
  const failedCustomMessage = document.getElementById('failedCustomMessage').value.trim();

  if (!name || !baseTargetUrl) {
    showAlert('Nama Template dan Base Target URL wajib diisi!', 'error');
    return;
  }

  // Parse allowed_usernames (baris / koma)
  const allowedUsernames = allowedUsernamesRaw
    .split(/[\r\n,]+/)
    .map(s => s.trim())
    .filter(s => s.length > 0);

  const saveBtn = document.getElementById('saveTemplateBtn');
  const saveBtnText = document.getElementById('saveBtnText');
  const saveBtnIcon = document.getElementById('saveBtnIcon');

  saveBtn.disabled = true;
  saveBtnIcon.textContent = '⏳';
  saveBtnText.textContent = 'Menyimpan...';

  const enableTrxId = document.getElementById('enableTrxId').checked;
  let trxIdVariable = '';
  let trxIdPrefix = '';
  let trxIdLastNumber = 1000;

  if (enableTrxId) {
    const varSelect = document.getElementById('trxIdVariableSelect').value;
    trxIdVariable = (varSelect === '__custom__')
      ? document.getElementById('trxIdVariableCustom').value.trim()
      : varSelect.trim();
    trxIdPrefix = document.getElementById('trxIdPrefix').value.trim() || 'rt';
    trxIdLastNumber = parseInt(document.getElementById('trxIdLastNumber').value, 10) || 1000;
  }

  const payload = {
    id: id,
    name: name,
    description: description,
    default_sender: defaultSender || (allowedUsernames[0] || ''),
    allowed_usernames: allowedUsernames,
    is_default: isDefault,
    base_target_url: baseTargetUrl,
    trx_id_variable: trxIdVariable,
    trx_id_prefix: trxIdPrefix,
    trx_id_last_number: trxIdLastNumber,
    success_keyword: successKeyword,
    success_custom_message: successCustomMessage,
    pending_keyword: pendingKeyword,
    pending_custom_message: pendingCustomMessage,
    failed_keyword: failedKeyword,
    failed_custom_message: failedCustomMessage
  };

  try {
    const res = await fetch('check_voucher.php?action=save_template', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const json = await res.json();
    if (json.status === 'success') {
      showAlert(`Template "${name}" berhasil disimpan!`);
      await loadAllTemplates();
      if (json.data && json.data.id) {
        selectTemplateForEdit(json.data.id);
      }
    } else {
      showAlert(json.message || 'Gagal menyimpan template.', 'error');
    }
  } catch (err) {
    showAlert('Terjadi kesalahan saat menyimpan: ' + err.message, 'error');
  } finally {
    saveBtn.disabled = false;
    saveBtnIcon.textContent = '💾';
    saveBtnText.textContent = 'Simpan Template';
  }
}

// 6. Hapus Template
async function handleDeleteTemplate(id, name) {
  if (!confirm(`Hapus template "${name}"?`)) return;

  try {
    const res = await fetch('check_voucher.php?action=delete_template', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    });
    const json = await res.json();
    if (json.status === 'success') {
      showAlert(`Template "${name}" telah dihapus.`);
      if (activeEditingId === id) {
        activeEditingId = null;
      }
      await loadAllTemplates();
    } else {
      showAlert(json.message || 'Gagal menghapus template.', 'error');
    }
  } catch (err) {
    showAlert('Error: ' + err.message, 'error');
  }
}



function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
  loadAllTemplates();
});
</script>

<?php include 'includes/footer.php'; ?>

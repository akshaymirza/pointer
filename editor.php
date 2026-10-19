<?php
require_once 'includes/auth.php';
requireLogin();

$pageTitle = "Template Editor - Dynamic Template Parser System";
$currentPage = "editor";
$pageHeading = "Create Template";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="page active" id="editorPage">
  <form class="editor-grid" id="templateForm">
    <section class="content-card form-card">
      <div class="card-heading">
        <div>
          <h2>Template Information</h2>
          <p>Give the template a clear name for non-technical users.</p>
        </div>
      </div>
      <div class="form-grid">
        <label>
          <span>Template Name</span>
          <input id="templateName" required placeholder="Account Status Check" />
        </label>
        <label>
          <span>Description <em>optional</em></span>
          <textarea id="templateDescription" rows="3" placeholder="Used by operators to generate account unlock status."></textarea>
        </label>
      </div>
    </section>

    <section class="content-card form-card">
      <div class="card-heading">
        <div>
          <h2>API Configuration</h2>
          <p>Use parameters to create dynamic employee input fields.</p>
        </div>
      </div>
      <div class="form-grid two-col">
        <label>
          <span>HTTP Method</span>
          <select id="httpMethod">
            <option>GET</option>
            <option>POST</option>
          </select>
        </label>
        <label class="wide-field">
          <span>Base API URL</span>
          <textarea id="apiUrl" required rows="4" placeholder="https://server/api/check?serialNumber={serialNumber}"></textarea>
          <small>Gunakan {parameter} untuk membuat input dinamis.</small>
        </label>
        <label class="wide-field">
          <span>Preset Values per Parameter <em>optional</em></span>
          <textarea id="paramOptions" rows="4" placeholder="serialNumber: 111111, 222222, 333333&#10;invoice: INV-001, INV-002"></textarea>
          <small>Format: nama_parameter: nilai1, nilai2, ... (satu baris per parameter). Di halaman Generate Output, parameter ini tampil sebagai dropdown pilihan, plus opsi "Nomor lain" untuk isi manual. Kalau parameter tidak didaftarkan di sini, tetap muncul sebagai input teks biasa.</small>
        </label>
      </div>
    </section>

    <section class="content-card form-card">
      <div class="card-heading">
        <div>
          <h2>⚡ JSON Helper / Auto Generator</h2>
          <p>Tempelkan contoh JSON respons API di bawah ini untuk melihat dan membuat daftar placeholder secara otomatis.</p>
        </div>
      </div>
      <div class="form-grid">
        <label>
          <span>Contoh JSON Response</span>
          <textarea id="jsonHelperInput" rows="5" placeholder='{"success":true,"data":{"tujuan":"6285924864110","status":"4G","owner":"XL","packageInfo":[[{"packages":{"name":"Paket 3GB"}}]]}}'></textarea>
        </label>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" class="secondary-button" id="generateJsonPathsBtn">🔍 Parse & Generate Placeholder</button>
          <button type="button" class="secondary-button" id="applyJsonToOutputBtn" style="background:#eff6ff; color:var(--primary); border-color:var(--primary);">✨ Terapkan ke Output Format</button>
        </div>
        <div id="jsonHelperResults" class="hidden" style="margin-top:10px; background:#f8fafc; border:1px solid var(--border); border-radius:12px; padding:14px;">
          <strong style="font-size:13px; color:var(--text); display:block; margin-bottom:8px;">Daftar Placeholder yang Dihasilkan (Klik untuk Salin / Sisipkan):</strong>
          <div id="jsonPathsList" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
        </div>
      </div>
    </section>

    <section class="content-card form-card">
      <div class="card-heading">
        <div>
          <h2>Output Template</h2>
          <p>Map JSON response data or parsed web text into the final output.</p>
        </div>
      </div>
      <div class="parser-grid">
        <label>
          <span>Input Template <em>optional</em></span>
          <textarea id="inputTemplate" rows="4" placeholder="tes: 123"></textarea>
          <small>Contoh bentuk isi web yang ingin diambil.</small>
        </label>
        <label>
          <span>Input Parameter <em>optional</em></span>
          <textarea id="inputParameter" rows="4" placeholder="tes: &lt;angka&gt;"></textarea>
          <small>Gunakan &lt;parameter&gt; untuk menangkap nilai dari isi web.</small>
        </label>
        <label class="wide-field">
          <span>Sample Web Response <em>prototype only</em></span>
          <textarea id="sampleWebResponse" rows="4" placeholder="tes: 127"></textarea>
          <small>Dipakai untuk simulasi respons web pada prototype.</small>
        </label>
      </div>
      <label>
        <span>Output Format</span>
        <textarea id="outputTemplate" class="large-textarea" required rows="8" placeholder="Serial Number : {data.serialNumber}&#10;Status : {data.status}&#10;Nama Paket : {data.packageInfo.0.0.packages.name}"></textarea>
        <small style="display: block; line-height: 1.5; margin-top: 6px;">
          <strong>Cara Pemanggilan JSON Data:</strong><br>
          • Objek biasa: <code>{data.status}</code> atau <code>{data.tujuan}</code><br>
          • Data Array: Gunakan indeks angka mulai dari 0, contoh <code>{data.items.0.name}</code><br>
          • Array bersarang (nested): Gunakan angka beruntun, contoh <code>{data.packageInfo.0.0.packages.name}</code><br>
          • Hasil Web Parser: <code>{parsed.parameter}</code>
        </small>
      </label>
    </section>

    <div class="form-actions">
      <a class="secondary-button" href="dashboard.php" id="cancelEdit" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
      <button class="primary-button" type="submit">Save Template</button>
    </div>
  </form>
</section>

<?php include 'includes/footer.php'; ?>

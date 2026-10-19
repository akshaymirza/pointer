const API_URL = "templates_api.php";

let templates = [];
let editingId = null;
let deletingId = null;

const labels = {
  serialNumber: "Serial Number",
  invoice: "Invoice",
  channel: "Channel",
};

const qs = (selector) => document.querySelector(selector);
const qsa = (selector) => [...document.querySelectorAll(selector)];

const elements = {
  sidebar: qs("#sidebar"),
  menuToggle: qs("#menuToggle"),
  pageTitle: qs("#pageTitle"),
  templateTable: qs("#templateTable"),
  searchInput: qs("#searchInput"),
  emptyState: qs("#emptyState"),
  totalTemplates: qs("#totalTemplates"),
  activeTemplates: qs("#activeTemplates"),
  templateForm: qs("#templateForm"),
  templateName: qs("#templateName"),
  templateDescription: qs("#templateDescription"),
  httpMethod: qs("#httpMethod"),
  apiUrl: qs("#apiUrl"),
  paramOptions: qs("#paramOptions"),
  inputTemplate: qs("#inputTemplate"),
  inputParameter: qs("#inputParameter"),
  sampleWebResponse: qs("#sampleWebResponse"),
  outputTemplate: qs("#outputTemplate"),
  templateSelect: qs("#templateSelect"),
  dynamicForm: qs("#dynamicForm"),
  processBtn: qs("#processBtn"),
  generatedOutput: qs("#generatedOutput"),
  loadingBox: qs("#loadingBox"),
  resultBadge: qs("#resultBadge"),
  deleteModal: qs("#deleteModal"),
  toastStack: qs("#toastStack"),
};

async function loadTemplates() {
  try {
    const response = await fetch(API_URL);
    if (!response.ok) {
      const err = await response.json().catch(() => ({}));
      throw new Error(err.error || `HTTP ${response.status}`);
    }
    return await response.json();
  } catch (e) {
    console.error("Error loading templates from database:", e);
    showToast("Gagal memuat template dari database: " + e.message, "error");
    return [];
  }
}

async function persistTemplate(payload) {
  const response = await fetch(API_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error || `HTTP ${response.status}`);
  }
  return result;
}

async function deleteTemplateApi(id) {
  const response = await fetch(`${API_URL}?id=${encodeURIComponent(id)}`, {
    method: "DELETE",
  });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error || `HTTP ${response.status}`);
  }
  return result;
}

function renderDashboard() {
  if (!elements.templateTable) return;

  const keyword = elements.searchInput ? elements.searchInput.value.trim().toLowerCase() : "";
  const filtered = templates.filter((template) =>
    template.name.toLowerCase().includes(keyword)
  );

  if (elements.totalTemplates) elements.totalTemplates.textContent = String(templates.length);
  if (elements.activeTemplates) {
    elements.activeTemplates.textContent = String(
      templates.filter((template) => template.active).length
    );
  }

  elements.templateTable.innerHTML = filtered
    .map(
      (template) => `
        <tr>
          <td><strong>${escapeHtml(template.name)}</strong></td>
          <td><span class="truncate" title="${escapeHtml(template.apiUrl)}">${escapeHtml(template.apiUrl)}</span></td>
          <td><span class="truncate" title="${escapeHtml(template.outputTemplate)}">${escapeHtml(template.outputTemplate)}</span></td>
          <td>${template.updatedAt}</td>
          <td>
            <div class="action-group">
              <a href="editor.php?id=${template.id}" class="text-button" style="text-decoration:none;">Edit</a>
              <button class="text-button danger" data-delete="${template.id}">Delete</button>
            </div>
          </td>
        </tr>
      `
    )
    .join("");

  if (elements.emptyState) {
    elements.emptyState.classList.toggle("hidden", filtered.length > 0);
  }
}

let templateSortOrder = "date_desc";

function getSortedTemplates(items, sortOrder = "date_desc") {
  if (!items) return [];
  const list = [...items];

  if (sortOrder === "asc") {
    list.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: "base" }));
  } else if (sortOrder === "desc") {
    list.sort((a, b) => b.name.localeCompare(a.name, undefined, { numeric: true, sensitivity: "base" }));
  } else if (sortOrder === "date_asc") {
    list.sort((a, b) => new Date(a.updatedAt || a.createdAt || 0) - new Date(b.updatedAt || b.createdAt || 0));
  } else {
    // date_desc (default - Terbaru)
    list.sort((a, b) => new Date(b.updatedAt || b.createdAt || 0) - new Date(a.updatedAt || a.createdAt || 0));
  }

  return list;
}

function renderTemplateSelect() {
  if (!elements.templateSelect) return;

  const currentSelectedId = elements.templateSelect.value;
  const sortedTemplates = getSortedTemplates(templates, templateSortOrder);

  elements.templateSelect.innerHTML = sortedTemplates
    .map((template) => `<option value="${template.id}">${escapeHtml(template.name)}</option>`)
    .join("");

  if (!sortedTemplates.length) {
    elements.templateSelect.innerHTML = `<option value="">No templates available</option>`;
    if (elements.dynamicForm) elements.dynamicForm.innerHTML = "";
    return;
  }

  if (currentSelectedId && sortedTemplates.some((t) => t.id === currentSelectedId)) {
    elements.templateSelect.value = currentSelectedId;
  }

  renderDynamicForm();
}

function parseParamOptions(text) {
  // Format tiap baris: nama_parameter: nilai1, nilai2, nilai3
  const map = {};
  if (!text) return map;

  text.split("\n").forEach((line) => {
    const idx = line.indexOf(":");
    if (idx === -1) return;
    const key = line.slice(0, idx).trim();
    const values = line
      .slice(idx + 1)
      .split(",")
      .map((v) => v.trim())
      .filter((v) => v.length > 0);
    if (key && values.length) map[key] = values;
  });

  return map;
}

let currentMode = "single";
let paramSortOrders = {};
let isBulkRunning = false;
let stopBulkRequested = false;
let bulkResultsData = [];
let bulkTargetParams = []; // array nama parameter yang dipilih untuk bulk
let bulkParamConfigs = {}; // config per param: { [param]: { type: 'range'|'list', start, end, listText } }

function getSortedOptions(options, sortOrder = "original") {
  if (!options) return [];
  const list = [...options];
  if (sortOrder === "asc") {
    list.sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
  } else if (sortOrder === "desc") {
    list.sort((a, b) => b.localeCompare(a, undefined, { numeric: true, sensitivity: 'base' }));
  }
  return list;
}

function updateBulkSelectionBadges() {
  const badge = qs("#bulkSelectedParamsBadge");
  if (badge) {
    badge.textContent = `${bulkTargetParams.length} parameter dipilih`;
  }

  // Update badge jumlah nilai di tiap item checklist
  bulkTargetParams.forEach((param) => {
    const itemEl = qs(`#bulkParamItem_${param}`);
    const countBadge = qs(`#checkItemCount_${param}`);
    const vals = getParamValues(param);
    if (countBadge) {
      countBadge.textContent = `${vals.length} item`;
      countBadge.style.display = vals.length > 0 ? "inline-block" : "none";
    }
  });
}

function renderBulkParamChecklist(params) {
  const container = qs("#bulkParamChecklist");
  if (!container) return;

  if (!params || !params.length) {
    container.innerHTML = `<p style="color:var(--muted); font-size:13px; margin:0;">Template ini tidak memiliki parameter di URL API.</p>`;
    updateBulkSelectionBadges();
    renderBulkParamsConfigCards();
    return;
  }

  // Bersihkan bulkTargetParams dari param yang sudah tidak ada
  bulkTargetParams = bulkTargetParams.filter((p) => params.includes(p));
  if (bulkTargetParams.length === 0 && params.length > 0) {
    bulkTargetParams = [params[0]]; // default pilih param pertama
  }

  updateBulkSelectionBadges();

  container.innerHTML = params
    .map((p) => {
      const isChecked = bulkTargetParams.includes(p) ? "checked" : "";
      const activeClass = isChecked ? "active" : "";
      return `
        <label class="bulk-param-check-item ${activeClass}" id="bulkParamItem_${p}">
          <input type="checkbox" data-bulk-param="${p}" ${isChecked} />
          <span>${escapeHtml(labelFor(p))}</span>
          <span id="checkItemCount_${p}" class="badge" style="display:none; font-size:11px; padding:2px 7px; background:#dbeafe; color:#1e40af; border-radius:999px;">0 item</span>
          <code style="font-size:11px; color:var(--muted); font-weight:normal; margin-left:auto;">{${p}}</code>
        </label>
      `;
    })
    .join("");

  // Pasang event listener untuk checkbox
  container.querySelectorAll('input[data-bulk-param]').forEach((cb) => {
    cb.addEventListener("change", (e) => {
      const p = e.target.dataset.bulkParam;
      const labelEl = qs(`#bulkParamItem_${p}`);
      if (e.target.checked) {
        if (!bulkTargetParams.includes(p)) bulkTargetParams.push(p);
        if (labelEl) labelEl.classList.add("active");
      } else {
        bulkTargetParams = bulkTargetParams.filter((item) => item !== p);
        if (labelEl) labelEl.classList.remove("active");
      }
      updateBulkSelectionBadges();
      renderBulkParamsConfigCards();
      renderDynamicForm(); // update dynamicForm (sembunyikan field yang sedang di-bulk)
      updateBulkCounts();
    });
  });

  renderBulkParamsConfigCards();
}

function renderBulkParamsConfigCards() {
  const container = qs("#bulkParamsConfigContainer");
  if (!container) return;

  if (!bulkTargetParams.length) {
    container.innerHTML = `
      <div style="background:#fff; border:1px dashed var(--border); border-radius:12px; padding:16px; text-align:center; color:var(--muted); font-size:13px;">
        Pilih minimal satu parameter di atas untuk memasukkan data bulk.
      </div>
    `;
    return;
  }

  // Buat panel input untuk masing-masing parameter yang dicentang
  container.innerHTML = bulkTargetParams
    .map((param) => {
      const label = escapeHtml(labelFor(param));
      const config = bulkParamConfigs[param] || { type: "range", start: "", end: "", listText: "" };
      const isRange = config.type !== "list";

      return `
        <div class="bulk-param-config-card" data-param-card="${param}">
          <div class="bulk-param-config-header">
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="bulk-param-badge">{${param}}</span>
              <strong style="font-size:14px; color:#1e293b;">${label}</strong>
            </div>
            <div class="bulk-type-toggle" style="display:flex; gap:12px; font-size:13px;">
              <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                <input type="radio" name="bulkType_${param}" value="range" ${isRange ? "checked" : ""} data-param-type="${param}" />
                <span>Rentang Nomor</span>
              </label>
              <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                <input type="radio" name="bulkType_${param}" value="list" ${!isRange ? "checked" : ""} data-param-type="${param}" />
                <span>Daftar Baris Teks</span>
              </label>
            </div>
          </div>

          <!-- Range fields -->
          <div id="bulkRangeFields_${param}" class="form-grid two-col ${isRange ? "" : "hidden"}" style="margin-top:10px;">
            <label>
              <span>${label} Awal</span>
              <input type="text" data-bulk-start="${param}" value="${escapeHtml(config.start || "")}" placeholder="Contoh: 089323949777" />
            </label>
            <label>
              <span>${label} Akhir</span>
              <input type="text" data-bulk-end="${param}" value="${escapeHtml(config.end || "")}" placeholder="Contoh: 089323949888" />
            </label>
            <div class="wide-field" style="margin-top:-4px;">
              <span class="badge" id="rangeCountBadge_${param}" style="background:#e0f2fe; color:#0369a1;">Total: 0 nilai</span>
            </div>
          </div>

          <!-- List field -->
          <div id="bulkListFields_${param}" class="${!isRange ? "" : "hidden"}" style="margin-top:10px;">
            <label>
              <span>Daftar Nilai ${label} (Satu per baris)</span>
              <textarea data-bulk-list="${param}" rows="4" placeholder="Nilai 1&#10;Nilai 2&#10;Nilai 3">${escapeHtml(config.listText || "")}</textarea>
              <small id="listCountBadge_${param}" style="color:var(--muted); font-size:12px;">Total: 0 baris</small>
            </label>
          </div>
        </div>
      `;
    })
    .join("");

  // Pasang event listener per card
  bulkTargetParams.forEach((param) => {
    if (!bulkParamConfigs[param]) {
      bulkParamConfigs[param] = { type: "range", start: "", end: "", listText: "" };
    }

    const radios = container.querySelectorAll(`input[name="bulkType_${param}"]`);
    radios.forEach((r) => {
      r.addEventListener("change", (e) => {
        const val = e.target.value;
        bulkParamConfigs[param].type = val;
        const rangeEl = qs(`#bulkRangeFields_${param}`);
        const listEl = qs(`#bulkListFields_${param}`);
        if (rangeEl && listEl) {
          rangeEl.classList.toggle("hidden", val !== "range");
          listEl.classList.toggle("hidden", val !== "list");
        }
        updateBulkCounts();
      });
    });

    const startInput = container.querySelector(`[data-bulk-start="${param}"]`);
    const endInput = container.querySelector(`[data-bulk-end="${param}"]`);
    const listInput = container.querySelector(`[data-bulk-list="${param}"]`);

    startInput?.addEventListener("input", (e) => {
      bulkParamConfigs[param].start = e.target.value.trim();
      updateBulkCounts();
    });
    endInput?.addEventListener("input", (e) => {
      bulkParamConfigs[param].end = e.target.value.trim();
      updateBulkCounts();
    });
    listInput?.addEventListener("input", (e) => {
      bulkParamConfigs[param].listText = e.target.value;
      updateBulkCounts();
    });

    updateBulkCounts();
  });
}

function renderDynamicForm() {
  if (!elements.dynamicForm) return;

  const template = getSelectedTemplate();
  const params = extractParams(template?.apiUrl ?? "");
  const optionsMap = parseParamOptions(template?.paramOptions ?? "");
  const CUSTOM_VALUE = "__custom__";

  renderBulkParamChecklist(params);

  // Jika sedang mode bulk, parameter yang dicentang di bulk target tidak perlu diinput lagi di dynamic form
  const visibleParams = currentMode === "bulk"
    ? params.filter((p) => !bulkTargetParams.includes(p))
    : params;

  elements.dynamicForm.innerHTML = visibleParams.length
    ? visibleParams
      .map((param) => {
        const options = optionsMap[param];
        const label = escapeHtml(labelFor(param));
        const sortOrder = paramSortOrders[param] || "original";

        if (options && options.length) {
          const sortedOptions = getSortedOptions(options, sortOrder);
          const optionTags = sortedOptions
            .map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`)
            .join("");
          return `
            <label>
              <div class="param-header">
                <span>${label}</span>
                <select class="param-sort-select" data-param-sort="${param}" title="Urutkan Pilihan Preset">
                  <option value="original" ${sortOrder === 'original' ? 'selected' : ''}>Urutan Asli</option>
                  <option value="asc" ${sortOrder === 'asc' ? 'selected' : ''}>Sort A - Z</option>
                  <option value="desc" ${sortOrder === 'desc' ? 'selected' : ''}>Sort Z - A</option>
                </select>
              </div>
              <select data-param="${param}" data-has-options="true">
                ${optionTags}
                <option value="${CUSTOM_VALUE}">Nomor lain (isi manual)...</option>
              </select>
              <input data-param-custom="${param}" class="hidden" placeholder="Masukkan ${label.toLowerCase()} lain" style="margin-top: 6px;" />
            </label>
          `;
        }

        return `
            <label>
              <span>${label}</span>
              <input data-param="${param}" placeholder="Enter ${label.toLowerCase()}" />
            </label>
          `;
      })
      .join("")
    : (currentMode === "bulk" && params.length > 0)
      ? `<div style="padding:10px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; color:#166534; font-size:13px; font-weight:500;">✓ Seluruh parameter ({${params.join(", ")}}) diisi via konfigurasi bulk di bawah.</div>`
      : `<div class="empty-state"><div class="empty-icon">+</div><h3>No dynamic fields</h3><p>This template has no URL parameters.</p></div>`;

  // Toggle input manual saat opsi "Nomor lain (isi manual)" dipilih pada dropdown
  qsa('[data-has-options="true"]').forEach((select) => {
    select.addEventListener("change", () => {
      const customInput = qs(`[data-param-custom="${select.dataset.param}"]`);
      if (!customInput) return;
      const isCustom = select.value === CUSTOM_VALUE;
      customInput.classList.toggle("hidden", !isCustom);
      if (isCustom) customInput.focus();
    });
  });

  // Listener ganti sort per parameter
  qsa('[data-param-sort]').forEach((sortSelect) => {
    sortSelect.addEventListener("change", (e) => {
      const paramName = e.target.dataset.paramSort;
      paramSortOrders[paramName] = e.target.value;
      renderDynamicForm();
    });
  });

  // ── Keyboard flow: Tab / Enter antar param → proses (Single mode) ─────────
  // Batasi ke #dynamicForm agar tidak menangkap elemen di luar form
  const paramInputs = qsa('#dynamicForm [data-param]').filter(
    (el) => el.tagName === 'INPUT' && !el.classList.contains('hidden')
  );

  paramInputs.forEach((input, idx) => {
    const isLast = idx === paramInputs.length - 1;

    input.addEventListener('keydown', (e) => {
      if (e.key === ',') {
        // Koma → pindah ke param berikutnya tanpa insert koma ke field
        e.preventDefault();
        const next = paramInputs[idx + 1];
        if (next) {
          next.focus();
          next.select();
        } else {
          qs('#processBtn')?.focus();
        }
      } else if (e.key === 'Tab' && e.shiftKey) {
        // Shift+Tab mundur ke param sebelumnya
        const prev = paramInputs[idx - 1];
        if (prev) {
          e.preventDefault();
          prev.focus();
          prev.select();
        }
      } else if (e.key === 'Enter') {
        if (currentMode === 'single') {
          e.preventDefault();
          if (isLast) {
            // Enter di param terakhir → proses
            processTemplate();
          } else {
            // Enter di param non-terakhir → maju ke param berikutnya (seperti koma)
            const next = paramInputs[idx + 1];
            if (next) {
              next.focus();
              next.select();
            }
          }
        }
      }
    });
  });
  // ─────────────────────────────────────────────────────────────────────────
}

function initEditorPage() {
  if (!elements.templateForm) return;

  const urlParams = new URLSearchParams(window.location.search);
  const id = urlParams.get("id");

  if (id) {
    const template = templates.find((item) => item.id === id);
    if (template) {
      editingId = id;
      if (elements.pageTitle) elements.pageTitle.textContent = "Edit Template";
      const submitBtn = elements.templateForm.querySelector("button[type='submit']");
      if (submitBtn) submitBtn.textContent = "Update Template";

      elements.templateName.value = template.name;
      elements.templateDescription.value = template.description || "";
      elements.httpMethod.value = template.method;
      elements.apiUrl.value = template.apiUrl;
      if (elements.paramOptions) elements.paramOptions.value = template.paramOptions ?? "";
      elements.inputTemplate.value = template.inputTemplate ?? "";
      elements.inputParameter.value = template.inputParameter ?? "";
      elements.sampleWebResponse.value = template.sampleWebResponse ?? "";
      elements.outputTemplate.value = template.outputTemplate;
      return;
    }
  }

  editingId = null;
  if (elements.pageTitle) elements.pageTitle.textContent = "Create Template";
}

async function saveTemplate(event) {
  event.preventDefault();
  const form = qs("#templateForm");
  if (!form) return;

  const templateName = qs("#templateName");
  const templateDescription = qs("#templateDescription");
  const httpMethod = qs("#httpMethod");
  const apiUrl = qs("#apiUrl");
  const paramOptions = qs("#paramOptions");
  const inputTemplate = qs("#inputTemplate");
  const inputParameter = qs("#inputParameter");
  const sampleWebResponse = qs("#sampleWebResponse");
  const outputTemplate = qs("#outputTemplate");

  const payload = {
    id: editingId || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now())),
    name: templateName ? templateName.value.trim() : "",
    description: templateDescription ? templateDescription.value.trim() : "",
    method: httpMethod ? httpMethod.value : "GET",
    apiUrl: apiUrl ? apiUrl.value.trim() : "",
    paramOptions: paramOptions ? paramOptions.value.trim() : "",
    inputTemplate: inputTemplate ? inputTemplate.value.trim() : "",
    inputParameter: inputParameter ? inputParameter.value.trim() : "",
    sampleWebResponse: sampleWebResponse ? sampleWebResponse.value.trim() : "",
    outputTemplate: outputTemplate ? outputTemplate.value : "",
    active: true,
  };

  const submitBtn = form.querySelector("button[type='submit']");
  if (submitBtn) submitBtn.disabled = true;

  try {
    await persistTemplate(payload);
    window.location.href = editingId ? "dashboard.php" : "index.php";
  } catch (e) {
    console.error("Save template error:", e);
    showToast("Gagal menyimpan template: " + e.message, "error");
    if (submitBtn) submitBtn.disabled = false;
  }
}

function requestDelete(id) {
  deletingId = id;
  if (elements.deleteModal) {
    elements.deleteModal.classList.remove("hidden");
  }
}

async function confirmDelete() {
  if (!deletingId) return;
  const idToDelete = deletingId;

  try {
    await deleteTemplateApi(idToDelete);
    templates = templates.filter((template) => template.id !== idToDelete);
    renderDashboard();
    renderTemplateSelect();
    showToast("Template deleted.", "success");
  } catch (e) {
    console.error("Delete template error:", e);
    showToast("Gagal menghapus template: " + e.message, "error");
  } finally {
    deletingId = null;
    if (elements.deleteModal) {
      elements.deleteModal.classList.add("hidden");
    }
  }
}

function autoResizeOutputTextarea() {
  const el = elements.generatedOutput || qs("#generatedOutput");
  if (!el) return;
  el.style.height = "auto";
  el.style.height = `${Math.max(el.scrollHeight, 220)}px`;
}

async function processTemplate() {
  if (!elements.processBtn) return;

  const template = getSelectedTemplate();
  if (!template) {
    showToast("Please select a template first.", "error");
    return;
  }

  const params = {};
  qsa("[data-param]").forEach((el) => {
    const key = el.dataset.param;
    if (el.tagName === "SELECT") {
      if (el.value === "__custom__") {
        const customInput = qs(`[data-param-custom="${key}"]`);
        params[key] = customInput ? customInput.value.trim() : "";
      } else {
        params[key] = el.value.trim();
      }
    } else {
      params[key] = el.value.trim();
    }
  });

  const missing = Object.entries(params).find(([, value]) => !value);
  if (missing) {
    showToast(`Please fill ${labelFor(missing[0])}.`, "error");
    return;
  }

  if (elements.loadingBox) elements.loadingBox.classList.remove("hidden");
  if (elements.generatedOutput) elements.generatedOutput.value = "";
  if (elements.resultBadge) {
    elements.resultBadge.textContent = "Loading";
    elements.resultBadge.classList.remove("success");
  }
  elements.processBtn.disabled = true;

  const targetUrl = buildApiUrl(template.apiUrl, params);

  try {
    const proxyRequestUrl = `proxy.php?url=${encodeURIComponent(targetUrl)}&method=${template.method}`;
    const response = await fetch(proxyRequestUrl);
    const rawText = await response.text();

    if (!response.ok) {
      let errorDetail = rawText;
      try {
        const errJson = JSON.parse(rawText);
        if (errJson.error) errorDetail = errJson.error;
      } catch (e) { }
      throw new Error(`Proxy/API error (HTTP ${response.status}): ${errorDetail}`);
    }

    let json = {};

    try {
      const parsedJson = JSON.parse(rawText);
      // Kalau hasil parse bukan object (misalnya angka/string polos seperti "123456"),
      // bungkus supaya json.raw / json.parsed tetap bisa nempel (properti tidak bisa
      // ditambahkan ke primitive number/string di JS).
      json = (parsedJson && typeof parsedJson === "object") ? parsedJson : { value: parsedJson };
    } catch (e) {
      const preMatch = rawText.match(/<pre[^>]*>([\s\S]*?)<\/pre>/i);
      if (preMatch) {
        const preContent = decodeHtmlEntities(preMatch[1].trim());
        try {
          const parsedPre = JSON.parse(preContent);
          json = (parsedPre && typeof parsedPre === "object") ? parsedPre : { value: parsedPre };
        } catch (e2) { }
      }
    }

    json.raw = rawText;
    json.parsed = parseWebResponse(rawText, template.inputParameter);
    // Inject parameter URL (e.g. {serialNumber}) agar bisa dipanggil langsung di Output Format
    Object.assign(json, params);

    if (elements.generatedOutput) {
      elements.generatedOutput.value = parseOutput(template.outputTemplate, json);
      autoResizeOutputTextarea();
    }

    if (elements.loadingBox) elements.loadingBox.classList.add("hidden");
    if (elements.resultBadge) {
      // Deteksi jika response mengandung indikasi pending
      const statusStr = String(json?.status || json?.state || json?.message || rawText).toLowerCase();
      if (statusStr.includes("pending") || statusStr.includes("menunggu") || statusStr.includes("antrian")) {
        elements.resultBadge.textContent = "Pending";
        elements.resultBadge.className = "badge warning";
      } else {
        elements.resultBadge.textContent = "Generated";
        elements.resultBadge.className = "badge success";
      }
    }
    showToast("Output generated successfully.", "success");
  } catch (error) {
    console.error("Fetch error:", error);
    if (elements.loadingBox) elements.loadingBox.classList.add("hidden");
    if (elements.generatedOutput) {
      elements.generatedOutput.value = "Error fetching data from API.\nPlease make sure your PHP server is running (e.g. via XAMPP or 'php -S localhost:8000') and proxy.php is accessible.\n\nDetails: " + error.message;
      autoResizeOutputTextarea();
    }
    if (elements.resultBadge) elements.resultBadge.textContent = "Error";
    showToast("Failed to fetch data.", "error");
  } finally {
    elements.processBtn.disabled = false;
    // Setelah proses selesai, auto-fokus kembali ke param pertama & clear semua field
    if (currentMode === 'single') {
      // Clear semua text input param agar siap input berikutnya
      qsa('#dynamicForm [data-param]').forEach((el) => {
        if (el.tagName === 'INPUT') el.value = '';
      });
      // Fokus ke param pertama
      const firstParamInput = qs('#dynamicForm [data-param]');
      if (firstParamInput) {
        firstParamInput.focus();
        if (firstParamInput.tagName === 'INPUT') firstParamInput.select();
      }
    }
  }
}

async function copyResult() {
  if (!elements.generatedOutput || !elements.generatedOutput.value.trim()) {
    showToast("There is no result to copy yet.", "error");
    return;
  }

  try {
    await navigator.clipboard.writeText(elements.generatedOutput.value);
    showToast("Copy success notification: result copied.", "success");
  } catch {
    elements.generatedOutput.select();
    document.execCommand("copy");
    showToast("Copy success notification: result copied.", "success");
  }
}

function extractParams(url) {
  return [...new Set([...url.matchAll(/\{([a-zA-Z0-9_.-]+)\}/g)].map((match) => match[1]))];
}

function labelFor(param) {
  if (labels[param]) return labels[param];
  return param
    .replace(/[-_.]+/g, " ")
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function buildApiUrl(url, params) {
  return Object.entries(params).reduce(
    (nextUrl, [key, value]) => nextUrl.replaceAll(`{${key}}`, encodeURIComponent(value)),
    url
  );
}

function decodeHtmlEntities(text) {
  const textarea = document.createElement("textarea");
  textarea.innerHTML = text;
  return textarea.value;
}

function parseWebResponse(rawText, parameterPattern) {
  if (!rawText || !parameterPattern) return {};

  const names = [];
  let regexText = "";
  let cursor = 0;
  const placeholderRegex = /<([a-zA-Z0-9_-]+)>/g;
  let match;

  while ((match = placeholderRegex.exec(parameterPattern)) !== null) {
    regexText += escapeRegex(parameterPattern.slice(cursor, match.index));
    regexText += "([^\\r\\n]+)";
    names.push(match[1]);
    cursor = match.index + match[0].length;
  }

  regexText += escapeRegex(parameterPattern.slice(cursor));
  const normalizedRegex = regexText.replace(/[ \t]+/g, "\\s*");
  const result = rawText.match(new RegExp(normalizedRegex, "i"));

  if (!result) return {};

  return names.reduce((parsed, name, index) => {
    parsed[name] = result[index + 1].trim();
    return parsed;
  }, {});
}

function parseOutput(templateText, json) {
  return templateText.replace(/\{([a-zA-Z0-9_.-]+)\}/g, (_, path) => {
    const value = path.split(".").reduce((current, key) => current?.[key], json);
    return value ?? "";
  });
}

function getSelectedTemplate() {
  if (!elements.templateSelect) return null;
  return templates.find((template) => template.id === elements.templateSelect.value);
}

function showToast(message, type = "success") {
  if (!elements.toastStack) return;
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.textContent = message;
  elements.toastStack.append(toast);
  setTimeout(() => toast.remove(), 3200);
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function escapeRegex(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function getBulkLimit() {
  const selectVal = qs("#bulkLimitSelect")?.value;
  if (!selectVal || selectVal === "all") return null;
  if (selectVal === "custom") {
    const customVal = parseInt(qs("#bulkCustomLimitInput")?.value, 10);
    return customVal && customVal > 0 ? customVal : null;
  }
  const parsed = parseInt(selectVal, 10);
  return parsed && parsed > 0 ? parsed : null;
}

function getParamValues(param) {
  const config = bulkParamConfigs[param] || { type: "range", start: "", end: "", listText: "" };
  let values = [];

  if (config.type !== "list") {
    // Range
    const startVal = (config.start || "").trim();
    const endVal = (config.end || "").trim();
    if (!startVal || !endVal) return [];

    try {
      const startNum = BigInt(startVal);
      const endNum = BigInt(endVal);
      if (endNum < startNum) return [];

      const count = endNum - startNum + 1n;
      if (count > 5000n) {
        showToast(`Rentang parameter ${labelFor(param)} maksimal 5000 nomor.`, "error");
        return [];
      }

      const len = Math.max(startVal.length, endVal.length);
      for (let num = startNum; num <= endNum; num++) {
        values.push(num.toString().padStart(len, "0"));
      }
    } catch (e) {
      console.error(`Range error for ${param}:`, e);
    }
  } else {
    // List
    const rawText = config.listText || "";
    rawText
      .split("\n")
      .map((line) => line.trim())
      .filter((line) => line.length > 0)
      .forEach((val) => values.push(val));
  }

  return values;
}

function updateParamBulkCounts(param) {
  const rangeBadge = qs(`#rangeCountBadge_${param}`);
  const listBadge = qs(`#listCountBadge_${param}`);
  const config = bulkParamConfigs[param] || { type: "range", start: "", end: "", listText: "" };

  if (rangeBadge) {
    const startVal = (config.start || "").trim();
    const endVal = (config.end || "").trim();
    if (startVal && endVal) {
      try {
        const startNum = BigInt(startVal);
        const endNum = BigInt(endVal);
        if (endNum >= startNum) {
          const total = Number(endNum - startNum + 1n);
          rangeBadge.textContent = `Total: ${total.toLocaleString()} nomor`;
        } else {
          rangeBadge.textContent = `Nomor akhir harus lebih besar`;
        }
      } catch (e) {
        rangeBadge.textContent = `Format angka tidak valid`;
      }
    } else {
      rangeBadge.textContent = `Total: 0 nilai`;
    }
  }

  if (listBadge) {
    const rawText = config.listText || "";
    const total = rawText.split("\n").map((s) => s.trim()).filter(Boolean).length;
    listBadge.textContent = `Total: ${total} baris`;
  }
}

function updateBulkCounts() {
  const paramCounts = [];
  let maxCount = 0;

  bulkTargetParams.forEach((p) => {
    updateParamBulkCounts(p);
    const vals = getParamValues(p);
    const count = vals.length;
    paramCounts.push(`${labelFor(p)}: ${count}`);
    if (count > maxCount) maxCount = count;

    // Update badge di list item checklist
    const countBadge = qs(`#checkItemCount_${p}`);
    if (countBadge) {
      countBadge.textContent = `${count} item`;
      countBadge.style.display = count > 0 ? "inline-block" : "none";
    }
  });

  // Terapkan limit jika aktif
  const limit = getBulkLimit();
  const effectiveRows = limit && maxCount > limit ? limit : maxCount;
  const limitSuffix = limit && maxCount > limit ? ` (Limit diproses: ${effectiveRows})` : "";

  const summaryText = qs("#bulkSummaryText");
  const totalRowsBadge = qs("#bulkTotalRowsBadge");

  if (summaryText) {
    if (bulkTargetParams.length === 0) {
      summaryText.textContent = "0 parameter dipilih • 0 data";
    } else {
      summaryText.textContent = `${bulkTargetParams.length} parameter aktif (${paramCounts.join(", ")})`;
    }
  }

  if (totalRowsBadge) {
    totalRowsBadge.textContent = `Total: ${maxCount.toLocaleString()} baris${limitSuffix}`;
    totalRowsBadge.style.background = maxCount > 0 ? "var(--primary)" : "#94a3b8";
  }

  updateBulkSelectionBadges();
}

function getMultiBulkCombinations() {
  if (!bulkTargetParams.length) return [];

  const paramMap = {};
  for (const param of bulkTargetParams) {
    const vals = getParamValues(param);
    if (!vals.length) {
      showToast(`Harap isi nilai untuk parameter ${labelFor(param)}.`, "error");
      return [];
    }
    paramMap[param] = vals;
  }

  const maxLen = Math.max(...bulkTargetParams.map((p) => paramMap[p].length));
  let combinations = [];

  for (let i = 0; i < maxLen; i++) {
    const combo = {};
    for (const param of bulkTargetParams) {
      const arr = paramMap[param];
      combo[param] = i < arr.length ? arr[i] : arr[arr.length - 1];
    }
    combinations.push(combo);
  }

  const limit = getBulkLimit();
  if (limit && combinations.length > limit) {
    combinations = combinations.slice(0, limit);
  }

  return combinations;
}

async function processBulkTemplate() {
  const template = getSelectedTemplate();
  if (!template) {
    showToast("Please select a template first.", "error");
    return;
  }

  if (!bulkTargetParams.length) {
    showToast("Pilih minimal satu parameter target bulk terlebih dahulu.", "error");
    return;
  }

  const combinations = getMultiBulkCombinations();
  if (!combinations.length) {
    return;
  }

  // Kumpulkan parameter dasar (non-bulk) dari dynamicForm
  const baseParams = {};
  qsa("#dynamicForm [data-param]").forEach((el) => {
    const key = el.dataset.param;
    if (bulkTargetParams.includes(key)) return;
    if (el.tagName === "SELECT") {
      if (el.value === "__custom__") {
        const customInput = qs(`[data-param-custom="${key}"]`);
        baseParams[key] = customInput ? customInput.value.trim() : "";
      } else {
        baseParams[key] = el.value.trim();
      }
    } else {
      baseParams[key] = el.value.trim();
    }
  });

  isBulkRunning = true;
  stopBulkRequested = false;
  bulkResultsData = [];

  const processBtn = qs("#processBtn");
  const cancelBtn = qs("#cancelBulkBtn");
  const progressWrap = qs("#bulkProgressWrap");
  const progressBar = qs("#bulkProgressBar");
  const progressText = qs("#bulkProgressText");
  const progressPercent = qs("#bulkProgressPercent");
  const totalProcessedEl = qs("#bulkTotalProcessed");
  const successCountEl = qs("#bulkSuccessCount");
  const errorCountEl = qs("#bulkErrorCount");

  if (processBtn) processBtn.disabled = true;
  if (cancelBtn) cancelBtn.classList.remove("hidden");
  if (progressWrap) progressWrap.classList.remove("hidden");

  let successCount = 0;
  let errorCount = 0;

  renderBulkTable();

  for (let i = 0; i < combinations.length; i++) {
    if (stopBulkRequested) {
      showToast("Proses bulk dihentikan oleh pengguna.", "error");
      break;
    }

    const combo = combinations[i];
    const currentParams = { ...baseParams, ...combo };
    const targetUrl = buildApiUrl(template.apiUrl, currentParams);
    const proxyRequestUrl = `proxy.php?url=${encodeURIComponent(targetUrl)}&method=${template.method}`;

    let itemResult = {
      index: i + 1,
      params: combo,
      status: "Processing",
      output: "",
      error: null,
    };

    try {
      const response = await fetch(proxyRequestUrl);
      const rawText = await response.text();

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      let json = {};
      try {
        const parsedJson = JSON.parse(rawText);
        json = parsedJson && typeof parsedJson === "object" ? parsedJson : { value: parsedJson };
      } catch (e) {
        const preMatch = rawText.match(/<pre[^>]*>([\s\S]*?)<\/pre>/i);
        if (preMatch) {
          const preContent = decodeHtmlEntities(preMatch[1].trim());
          try {
            const parsedPre = JSON.parse(preContent);
            json = parsedPre && typeof parsedPre === "object" ? parsedPre : { value: parsedPre };
          } catch (e2) {}
        }
      }

      json.raw = rawText;
      json.parsed = parseWebResponse(rawText, template.inputParameter);
      Object.assign(json, currentParams);

      const statusStr = String(json?.status || json?.state || json?.message || rawText).toLowerCase();
      if (statusStr.includes("pending") || statusStr.includes("menunggu") || statusStr.includes("antrian")) {
        itemResult.status = "Pending";
      } else {
        itemResult.status = "Success";
        successCount++;
      }
      itemResult.output = parseOutput(template.outputTemplate, json);
    } catch (err) {
      itemResult.status = "Error";
      itemResult.output = "Error fetching data: " + err.message;
      itemResult.error = err.message;
      errorCount++;
    }

    bulkResultsData.push(itemResult);

    // Update progress UI
    const pct = Math.round(((i + 1) / combinations.length) * 100);
    const comboPreview = Object.values(combo).join(", ");
    if (progressBar) progressBar.style.width = `${pct}%`;
    if (progressText) progressText.textContent = `Memproses ${i + 1} / ${combinations.length} (${comboPreview})`;
    if (progressPercent) progressPercent.textContent = `${pct}%`;
    if (totalProcessedEl) totalProcessedEl.textContent = String(bulkResultsData.length);
    if (successCountEl) successCountEl.textContent = `Sukses: ${successCount}`;
    if (errorCountEl) errorCountEl.textContent = `Gagal: ${errorCount}`;

    renderBulkTable();
  }

  isBulkRunning = false;
  if (processBtn) processBtn.disabled = false;
  if (cancelBtn) cancelBtn.classList.add("hidden");
  if (progressWrap) progressWrap.classList.add("hidden");

  showToast(`Bulk selesai! Sukses: ${successCount}, Gagal: ${errorCount}`, successCount > 0 ? "success" : "error");
}

function updateBulkTableHeader() {
  const headerRow = qs("#bulkResultTableHeaderRow");
  if (!headerRow) return;

  const paramThs = bulkTargetParams.length
    ? bulkTargetParams.map((p) => `<th>${escapeHtml(labelFor(p))}</th>`).join("")
    : `<th id="bulkParamColumnHeader">Parameter</th>`;

  headerRow.innerHTML = `
    <th style="width:50px;">#</th>
    ${paramThs}
    <th style="width:100px;">Status</th>
    <th>Generated Output</th>
    <th style="width:100px;">Aksi</th>
  `;
}

function renderBulkTable() {
  updateBulkTableHeader();

  const tbody = qs("#bulkTableBody");
  if (!tbody) return;

  const keyword = qs("#bulkSearchInput")?.value.trim().toLowerCase() || "";
  const totalCols = bulkTargetParams.length + 4; // # + params + status + output + aksi

  const filtered = bulkResultsData.filter((item) => {
    const paramMatch = item.params
      ? Object.values(item.params).some((val) => String(val).toLowerCase().includes(keyword))
      : false;
    return paramMatch || item.output.toLowerCase().includes(keyword);
  });

  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="${totalCols}" style="text-align:center; padding:32px; color:var(--muted);">${
      bulkResultsData.length
        ? "Tidak ada data yang cocok dengan pencarian."
        : "Belum ada data bulk yang diproses. Silakan klik tombol Process."
    }</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered
    .map((item) => {
      let statusBadge = `<span class="badge danger" style="background:#fee2e2; color:#991b1b;">Error</span>`;
      if (item.status === "Success") {
        statusBadge = `<span class="badge success">Success</span>`;
      } else if (item.status === "Pending") {
        statusBadge = `<span class="badge warning" style="background:#fef3c7; color:#92400e;">Pending</span>`;
      }

      const canRetry = item.status === "Error" || item.status === "Pending";
      const retryBtn = canRetry
        ? `<button class="text-button" type="button" style="color:#d97706;" onclick="window.retrySingleBulkRow(${item.index - 1})" title="Coba ulang item ini">🔄 Retry</button>`
        : "";

      const paramTds = bulkTargetParams.length
        ? bulkTargetParams
            .map((p) => `<td><code>${escapeHtml(item.params?.[p] ?? "")}</code></td>`)
            .join("")
        : `<td><code>-</code></td>`;

      return `
        <tr>
          <td><strong>${item.index}</strong></td>
          ${paramTds}
          <td>${statusBadge}</td>
          <td><div class="bulk-output-cell">${escapeHtml(item.output)}</div></td>
          <td style="display:flex; gap:6px; flex-wrap:wrap;">
            <button class="text-button" type="button" onclick="window.copySingleBulkRow(${item.index - 1})">Copy</button>
            ${retryBtn}
          </td>
        </tr>
      `;
    })
    .join("");
}

window.copySingleBulkRow = function (index) {
  const item = bulkResultsData[index];
  if (!item || !item.output) return;
  navigator.clipboard.writeText(item.output);
  const info = item.params ? Object.values(item.params).join(", ") : `#${item.index}`;
  showToast(`Output ${info} disalin.`, "success");
};

window.retrySingleBulkRow = async function (index) {
  const item = bulkResultsData[index];
  if (!item) return;

  const template = getSelectedTemplate();
  if (!template) {
    showToast("Template tidak ditemukan.", "error");
    return;
  }

  // Base parameters for non-bulk fields
  const baseParams = {};
  qsa("#dynamicForm [data-param]").forEach((el) => {
    const key = el.dataset.param;
    if (bulkTargetParams.includes(key)) return;
    if (el.tagName === "SELECT") {
      if (el.value === "__custom__") {
        const customInput = qs(`[data-param-custom="${key}"]`);
        baseParams[key] = customInput ? customInput.value.trim() : "";
      } else {
        baseParams[key] = el.value.trim();
      }
    } else {
      baseParams[key] = el.value.trim();
    }
  });

  // Update status to retrying
  bulkResultsData[index].status = "Processing";
  bulkResultsData[index].output = "Mencoba ulang...";
  bulkResultsData[index].error = null;
  renderBulkTable();

  const currentParams = { ...baseParams, ...(item.params || {}) };
  const targetUrl = buildApiUrl(template.apiUrl, currentParams);
  const proxyRequestUrl = `proxy.php?url=${encodeURIComponent(targetUrl)}&method=${template.method}`;

  try {
    const response = await fetch(proxyRequestUrl);
    const rawText = await response.text();

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    let json = {};
    try {
      const parsedJson = JSON.parse(rawText);
      json = parsedJson && typeof parsedJson === "object" ? parsedJson : { value: parsedJson };
    } catch (e) {
      const preMatch = rawText.match(/<pre[^>]*>([\s\S]*?)<\/pre>/i);
      if (preMatch) {
        const preContent = decodeHtmlEntities(preMatch[1].trim());
        try {
          const parsedPre = JSON.parse(preContent);
          json = parsedPre && typeof parsedPre === "object" ? parsedPre : { value: parsedPre };
        } catch (e2) {}
      }
    }

    json.raw = rawText;
    json.parsed = parseWebResponse(rawText, template.inputParameter);
    Object.assign(json, currentParams);

    const statusStr = String(json?.status || json?.state || json?.message || rawText).toLowerCase();
    if (statusStr.includes("pending") || statusStr.includes("menunggu") || statusStr.includes("antrian")) {
      bulkResultsData[index].status = "Pending";
    } else {
      bulkResultsData[index].status = "Success";
    }
    bulkResultsData[index].output = parseOutput(template.outputTemplate, json);
    showToast(`Retry item #${item.index} berhasil.`, "success");
  } catch (err) {
    bulkResultsData[index].status = "Error";
    bulkResultsData[index].output = "Error fetching data: " + err.message;
    bulkResultsData[index].error = err.message;
    showToast(`Retry item #${item.index} gagal: ${err.message}`, "error");
  }

  renderBulkTable();
};

function copyBulkTxt() {
  if (!bulkResultsData.length) {
    showToast("Belum ada data bulk untuk disalin.", "error");
    return;
  }
  const fullText = bulkResultsData
    .map((item) => {
      const header = item.params ? Object.entries(item.params).map(([k, v]) => `${labelFor(k)}: ${v}`).join(", ") : `#${item.index}`;
      return `--- ${header} ---\n${item.output}`;
    })
    .join("\n\n");
  navigator.clipboard.writeText(fullText);
  showToast("Seluruh hasil bulk disalin ke clipboard.", "success");
}

function extractJsonPaths(obj, prefix = "") {
  let paths = [];

  if (obj === null || typeof obj !== "object") {
    return prefix ? [prefix] : [];
  }

  if (Array.isArray(obj)) {
    obj.forEach((item, index) => {
      const currentPrefix = prefix ? `${prefix}.${index}` : `${index}`;
      paths = paths.concat(extractJsonPaths(item, currentPrefix));
    });
  } else {
    Object.keys(obj).forEach((key) => {
      const currentPrefix = prefix ? `${prefix}.${key}` : `${key}`;
      paths = paths.concat(extractJsonPaths(obj[key], currentPrefix));
    });
  }

  return paths;
}

function handleJsonHelperParse() {
  const jsonInput = qs("#jsonHelperInput")?.value.trim();
  const resultsDiv = qs("#jsonHelperResults");
  const listDiv = qs("#jsonPathsList");

  if (!jsonInput) {
    showToast("Tempelkan teks JSON respons terlebih dahulu.", "error");
    return [];
  }

  let parsedObj;
  try {
    parsedObj = JSON.parse(jsonInput);
  } catch (e) {
    showToast("Format JSON tidak valid: " + e.message, "error");
    return [];
  }

  const paths = extractJsonPaths(parsedObj);

  if (resultsDiv) resultsDiv.classList.remove("hidden");
  if (listDiv) {
    listDiv.innerHTML = paths
      .map(
        (path) => `
          <button type="button" class="badge" style="cursor:pointer; border:1px solid #bfdbfe; background:#eff6ff; color:#1e40af; font-family:monospace; font-size:12px; padding:6px 10px;" onclick="window.copyOrInsertPlaceholder('{${path}}')">
            {${escapeHtml(path)}}
          </button>
        `
      )
      .join("");
  }

  showToast(`Berhasil menemukan ${paths.length} placeholder!`, "success");
  return paths;
}

window.copyOrInsertPlaceholder = function (placeholder) {
  const outputEl = qs("#outputTemplate");
  if (outputEl) {
    const start = outputEl.selectionStart;
    const end = outputEl.selectionEnd;
    const text = outputEl.value;
    outputEl.value = text.substring(0, start) + placeholder + text.substring(end);
    outputEl.focus();
    outputEl.selectionStart = outputEl.selectionEnd = start + placeholder.length;
    showToast(`Disisipkan ${placeholder} ke Output Format`, "success");
  } else {
    navigator.clipboard.writeText(placeholder);
    showToast(`Disalin: ${placeholder}`, "success");
  }
};

function handleApplyJsonToOutput() {
  const paths = handleJsonHelperParse();
  if (!paths || !paths.length) return;

  const outputEl = qs("#outputTemplate");
  if (!outputEl) return;

  const lines = paths.map((path) => {
    const parts = path.split(".");
    const lastKey = parts[parts.length - 1];
    const cleanLabel = lastKey
      .replace(/[-_.]+/g, " ")
      .replace(/\b\w/g, (letter) => letter.toUpperCase());
    return `${cleanLabel} : {${path}}`;
  });

  outputEl.value = lines.join("\n");
  showToast("Output Format berhasil dibuat secara otomatis!", "success");
}

function downloadBulkXlsx() {
  if (!bulkResultsData.length) {
    showToast("Belum ada data bulk untuk di-download.", "error");
    return;
  }

  if (typeof XLSX === "undefined") {
    showToast("Library XLSX belum siap, coba lagi sebentar.", "error");
    return;
  }

  const paramHeaders = bulkTargetParams.map((p) => labelFor(p));
  const headers = ["#", ...paramHeaders, "Status", "Generated Output"];

  const rows = bulkResultsData.map((item) => {
    const rowObj = { "#": item.index };
    bulkTargetParams.forEach((p) => {
      rowObj[labelFor(p)] = String(item.params?.[p] ?? "");
    });
    rowObj["Status"] = String(item.status || "");
    rowObj["Generated Output"] = String(item.output || "");
    return rowObj;
  });

  const ws = XLSX.utils.json_to_sheet(rows, { header: headers, raw: true });

  // Format kolom parameter, status, dan output sebagai text/string ('s')
  // agar nomor voucher atau no HP tidak diubah menjadi notasi ilmiah atau kehilangan angka 0 di depan
  const range = XLSX.utils.decode_range(ws["!ref"] || "A1:A1");
  for (let R = range.s.r + 1; R <= range.e.r; ++R) {
    for (let C = range.s.c; C <= range.e.c; ++C) {
      if (C > 0) { // Selain kolom index '#'
        const cellAddress = XLSX.utils.encode_cell({ r: R, c: C });
        const cell = ws[cellAddress];
        if (cell) {
          cell.t = "s";
          cell.z = "@";
        }
      }
    }
  }

  // Auto-width kolom yang aman untuk jumlah baris banyak
  const colWidths = headers.map((h) => {
    let maxLen = h.length;
    for (let r = 0; r < rows.length; r++) {
      const val = rows[r][h] != null ? String(rows[r][h]) : "";
      if (val.length > maxLen) {
        maxLen = val.length;
      }
    }
    return { wch: Math.min(Math.max(maxLen + 3, 10), 60) };
  });
  ws["!cols"] = colWidths;

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Bulk Results");

  const fileName = `bulk_results_${Date.now()}.xlsx`;
  XLSX.writeFile(wb, fileName);

  showToast("File XLSX berhasil terdownload.", "success");
}

document.addEventListener("DOMContentLoaded", async () => {
  const form = qs("#templateForm");
  if (form) {
    form.addEventListener("submit", saveTemplate);
  }

  const sidebar = elements.sidebar || qs('#sidebar');
  const closeBtn = qs('#closeSidebar');
  const expandBtn = qs('#expandSidebar');
  const menuToggle = elements.menuToggle || qs('#menuToggle');

  // Tombol close pada sidebar (mobile: tutup penuh, desktop: ciutkan)
  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', () => {
      if (window.innerWidth <= 980) {
        sidebar.classList.remove('open');
      }
      sidebar.classList.add('collapsed');
    });
  }

  // Tombol panah kecil untuk buka lagi saat sidebar diciutkan (desktop)
  if (expandBtn && sidebar) {
    expandBtn.addEventListener('click', () => {
      sidebar.classList.remove('collapsed');
    });
  }

  // Tombol hamburger untuk membuka/menutup
  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
      if (window.innerWidth <= 980) {
        sidebar.classList.toggle('open');
      } else {
        sidebar.classList.toggle('collapsed');
      }
    });
  }

  if (elements.searchInput) {
    elements.searchInput.addEventListener("input", renderDashboard);
  }

  if (elements.templateSelect) {
    elements.templateSelect.addEventListener("change", renderDynamicForm);
  }

  const templateSortSelect = qs("#templateSortSelect");
  if (templateSortSelect) {
    templateSortSelect.addEventListener("change", (e) => {
      templateSortOrder = e.target.value;
      renderTemplateSelect();
    });
  }

  if (elements.processBtn) {
    elements.processBtn.addEventListener("click", () => {
      if (currentMode === "bulk") {
        processBulkTemplate();
      } else {
        processTemplate();
      }
    });
  }

  const cancelBulkBtn = qs("#cancelBulkBtn");
  if (cancelBulkBtn) {
    cancelBulkBtn.addEventListener("click", () => {
      stopBulkRequested = true;
    });
  }

  // Setup mode tabs (Single / Bulk)
  const singleModeTab = qs("#singleModeTab");
  const bulkModeTab = qs("#bulkModeTab");
  const bulkForm = qs("#bulkForm");
  const singleResultCard = qs("#singleResultCard");
  const bulkResultCard = qs("#bulkResultCard");

  if (singleModeTab && bulkModeTab) {
    singleModeTab.addEventListener("click", () => {
      currentMode = "single";
      singleModeTab.classList.add("active");
      bulkModeTab.classList.remove("active");
      if (bulkForm) bulkForm.classList.add("hidden");
      if (singleResultCard) singleResultCard.classList.remove("hidden");
      if (bulkResultCard) bulkResultCard.classList.add("hidden");
    });

    bulkModeTab.addEventListener("click", () => {
      currentMode = "bulk";
      bulkModeTab.classList.add("active");
      singleModeTab.classList.remove("active");
      if (bulkForm) bulkForm.classList.remove("hidden");
      if (singleResultCard) singleResultCard.classList.add("hidden");
      if (bulkResultCard) bulkResultCard.classList.remove("hidden");
      renderDynamicForm();
    });
  }



  // Bulk Limit listeners
  const bulkLimitSelect = qs("#bulkLimitSelect");
  const bulkCustomLimitWrap = qs("#bulkCustomLimitWrap");
  if (bulkLimitSelect) {
    bulkLimitSelect.addEventListener("change", () => {
      if (bulkCustomLimitWrap) {
        bulkCustomLimitWrap.classList.toggle("hidden", bulkLimitSelect.value !== "custom");
      }
      updateBulkCounts();
    });
  }
  qs("#bulkCustomLimitInput")?.addEventListener("input", updateBulkCounts);

  // Bulk table actions
  qs("#bulkSearchInput")?.addEventListener("input", renderBulkTable);
  qs("#copyBulkTxtBtn")?.addEventListener("click", copyBulkTxt);
  qs("#downloadBulkCsvBtn")?.addEventListener("click", downloadBulkXlsx);

  const copyBtn = qs("#copyBtn");
  if (copyBtn) {
    copyBtn.addEventListener("click", copyResult);
  }

  const cancelDeleteBtn = qs("#cancelDelete");
  if (cancelDeleteBtn && elements.deleteModal) {
    cancelDeleteBtn.addEventListener("click", () => elements.deleteModal.classList.add("hidden"));
  }

  const confirmDeleteBtn = qs("#confirmDelete");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", confirmDelete);
  }

  if (elements.templateTable) {
    elements.templateTable.addEventListener("click", (event) => {
      const deleteId = event.target.dataset.delete;
      if (deleteId) requestDelete(deleteId);
    });
  }

  // JSON Helper listeners
  qs("#generateJsonPathsBtn")?.addEventListener("click", handleJsonHelperParse);
  qs("#applyJsonToOutputBtn")?.addEventListener("click", handleApplyJsonToOutput);

  // Initialize current page state: ambil data template dari database dulu
  templates = await loadTemplates();
  renderDashboard();
  initEditorPage();
  renderTemplateSelect();
});
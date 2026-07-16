const STORAGE_KEY = "dynamic_parser_templates";

const defaultTemplates = [
  {
    id: crypto.randomUUID(),
    name: "Voucher Unlock Status",
    description: "Check and parse unlock status for vouchers.",
    method: "GET",
    apiUrl: "https://server/api/check?serialNumber={serialNumber}",
    inputTemplate: "",
    inputParameter: "",
    sampleWebResponse: "",
    outputTemplate:
      "Serial Number : {data.serialNumber}\nStatus : {data.status}\nUnlock Date : {data.unlockDate}\nRetailer MSISDN : {data.unlockRetMSISDN}\nUnlock Price : {data.unlockPrice}\nProduct Name : {data.productName}\nVoucher Status : {data.voucherStatus}",
    updatedAt: "2026-07-16",
    active: true,
  },
  {
    id: crypto.randomUUID(),
    name: "Payment Verification",
    description: "Parse billing verification data.",
    method: "POST",
    apiUrl: "https://server/api/payment?invoice={invoice}&channel={channel}",
    inputTemplate: "",
    inputParameter: "",
    sampleWebResponse: "",
    outputTemplate:
      "Invoice : {data.invoice}\n\nStatus : {data.status}\n\nChannel : {data.channel}\n\nAmount : {data.amount}",
    updatedAt: "2026-07-06",
    active: true,
  }
];

let templates = loadTemplates();
let editingId = null;
let deletingId = null;

const pageTitles = {
  dashboard: "Template Management",
  editor: "Create Template",
  workspace: "Generate Output",
  flow: "Activity Flow",
};

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

function loadTemplates() {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored) {
    try {
      return JSON.parse(stored);
    } catch (e) {
      console.error("Error loading templates from localStorage:", e);
    }
  }
  return [...defaultTemplates];
}

function saveTemplatesToStorage() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(templates));
}

function showPage(page) {
  qsa(".page").forEach((item) => item.classList.remove("active"));
  qs(`#${page}Page`).classList.add("active");
  
  if (page === "editor") {
    elements.pageTitle.textContent = editingId ? "Edit Template" : "Create Template";
    const submitBtn = elements.templateForm.querySelector("button[type='submit']");
    if (submitBtn) {
      submitBtn.textContent = editingId ? "Update Template" : "Save Template";
    }
  } else {
    elements.pageTitle.textContent = pageTitles[page] || "Console";
  }

  qsa(".nav-item").forEach((item) => {
    item.classList.toggle("active", item.dataset.page === page);
  });

  if (window.innerWidth <= 980) {
    elements.sidebar.classList.remove("open");
  }

  if (page === "workspace") {
    renderTemplateSelect();
  }
}

function renderDashboard() {
  const keyword = elements.searchInput.value.trim().toLowerCase();
  const filtered = templates.filter((template) =>
    template.name.toLowerCase().includes(keyword)
  );

  elements.totalTemplates.textContent = String(templates.length);
  elements.activeTemplates.textContent = String(
    templates.filter((template) => template.active).length
  );

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
              <button class="text-button" data-edit="${template.id}">Edit</button>
              <button class="text-button danger" data-delete="${template.id}">Delete</button>
            </div>
          </td>
        </tr>
      `
    )
    .join("");

  elements.emptyState.classList.toggle("hidden", filtered.length > 0);
}

function renderTemplateSelect() {
  elements.templateSelect.innerHTML = templates
    .map((template) => `<option value="${template.id}">${escapeHtml(template.name)}</option>`)
    .join("");

  if (!templates.length) {
    elements.templateSelect.innerHTML = `<option value="">No templates available</option>`;
    elements.dynamicForm.innerHTML = "";
    return;
  }

  renderDynamicForm();
}

function renderDynamicForm() {
  const template = getSelectedTemplate();
  const params = extractParams(template?.apiUrl ?? "");

  elements.dynamicForm.innerHTML = params.length
    ? params
        .map(
          (param) => `
            <label>
              <span>${escapeHtml(labelFor(param))}</span>
              <input data-param="${param}" placeholder="Enter ${escapeHtml(labelFor(param).toLowerCase())}" />
            </label>
          `
        )
        .join("")
    : `<div class="empty-state"><div class="empty-icon">+</div><h3>No dynamic fields</h3><p>This template has no URL parameters.</p></div>`;
}

function startNewTemplate() {
  editingId = null;
  elements.templateForm.reset();
  elements.httpMethod.value = "GET";
  elements.apiUrl.value = "";
  elements.inputTemplate.value = "";
  elements.inputParameter.value = "";
  elements.sampleWebResponse.value = "";
  elements.outputTemplate.value = "";
  showPage("editor");
}

function editTemplate(id) {
  const template = templates.find((item) => item.id === id);
  if (!template) return;

  editingId = id;
  elements.templateName.value = template.name;
  elements.templateDescription.value = template.description;
  elements.httpMethod.value = template.method;
  elements.apiUrl.value = template.apiUrl;
  elements.inputTemplate.value = template.inputTemplate ?? "";
  elements.inputParameter.value = template.inputParameter ?? "";
  elements.sampleWebResponse.value = template.sampleWebResponse ?? "";
  elements.outputTemplate.value = template.outputTemplate;
  showPage("editor");
}

function saveTemplate(event) {
  event.preventDefault();

  const payload = {
    id: editingId ?? crypto.randomUUID(),
    name: elements.templateName.value.trim(),
    description: elements.templateDescription.value.trim(),
    method: elements.httpMethod.value,
    apiUrl: elements.apiUrl.value.trim(),
    inputTemplate: elements.inputTemplate.value.trim(),
    inputParameter: elements.inputParameter.value.trim(),
    sampleWebResponse: elements.sampleWebResponse.value.trim(),
    outputTemplate: elements.outputTemplate.value,
    updatedAt: new Date().toISOString().slice(0, 10),
    active: true,
  };

  if (editingId) {
    templates = templates.map((template) =>
      template.id === editingId ? payload : template
    );
    showToast("Template updated successfully.", "success");
  } else {
    templates = [payload, ...templates];
    showToast("Template created successfully.", "success");
  }

  saveTemplatesToStorage();
  editingId = null;
  renderDashboard();
  renderTemplateSelect();
  showPage("dashboard");
}

function requestDelete(id) {
  deletingId = id;
  elements.deleteModal.classList.remove("hidden");
}

function confirmDelete() {
  templates = templates.filter((template) => template.id !== deletingId);
  deletingId = null;
  elements.deleteModal.classList.add("hidden");
  saveTemplatesToStorage();
  renderDashboard();
  renderTemplateSelect();
  showToast("Template deleted.", "success");
}

async function processTemplate() {
  const template = getSelectedTemplate();
  if (!template) {
    showToast("Please select a template first.", "error");
    return;
  }

  const params = Object.fromEntries(
    qsa("[data-param]").map((input) => [input.dataset.param, input.value.trim()])
  );

  const missing = Object.entries(params).find(([, value]) => !value);
  if (missing) {
    showToast(`Please fill ${labelFor(missing[0])}.`, "error");
    return;
  }

  elements.loadingBox.classList.remove("hidden");
  elements.generatedOutput.value = "";
  elements.resultBadge.textContent = "Loading";
  elements.resultBadge.classList.remove("success");
  elements.processBtn.disabled = true;

  const targetUrl = buildApiUrl(template.apiUrl, params);

  try {
    const proxyRequestUrl = `proxy.php?url=${encodeURIComponent(targetUrl)}&method=${template.method}`;
    const response = await fetch(proxyRequestUrl);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const rawText = await response.text();
    let json = {};
    
    try {
      json = JSON.parse(rawText);
    } catch (e) {
      // If it's not valid JSON, we still have the raw text
    }

    json.raw = rawText;
    json.parsed = parseWebResponse(rawText, template.inputParameter);
    
    elements.generatedOutput.value = parseOutput(template.outputTemplate, json);

    elements.loadingBox.classList.add("hidden");
    elements.resultBadge.textContent = "Generated";
    elements.resultBadge.classList.add("success");
    showToast("Output generated successfully.", "success");
  } catch (error) {
    console.error("Fetch error:", error);
    elements.loadingBox.classList.add("hidden");
    elements.generatedOutput.value = "Error fetching data from API.\nPlease make sure your PHP server is running (e.g. via XAMPP or 'php -S localhost:8000') and proxy.php is accessible.\n\nDetails: " + error.message;
    elements.resultBadge.textContent = "Error";
    showToast("Failed to fetch data.", "error");
  } finally {
    elements.processBtn.disabled = false;
  }
}

async function copyResult() {
  if (!elements.generatedOutput.value.trim()) {
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
  return templates.find((template) => template.id === elements.templateSelect.value);
}

function showToast(message, type = "success") {
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

qsa(".nav-item").forEach((button) => {
  button.addEventListener("click", () => {
    if (button.dataset.page === "editor") {
      startNewTemplate();
    } else {
      showPage(button.dataset.page);
    }
  });
});

elements.menuToggle.addEventListener("click", () => {
  if (window.innerWidth <= 980) {
    elements.sidebar.classList.toggle("open");
  } else {
    elements.sidebar.classList.toggle("collapsed");
  }
});

qs("#newTemplateBtn").addEventListener("click", startNewTemplate);
qs("#cancelEdit").addEventListener("click", () => showPage("dashboard"));
elements.templateForm.addEventListener("submit", saveTemplate);
elements.searchInput.addEventListener("input", renderDashboard);
elements.templateSelect.addEventListener("change", renderDynamicForm);
elements.processBtn.addEventListener("click", processTemplate);
qs("#copyBtn").addEventListener("click", copyResult);
qs("#cancelDelete").addEventListener("click", () => elements.deleteModal.classList.add("hidden"));
qs("#confirmDelete").addEventListener("click", confirmDelete);

elements.templateTable.addEventListener("click", (event) => {
  const editId = event.target.dataset.edit;
  const deleteId = event.target.dataset.delete;
  if (editId) editTemplate(editId);
  if (deleteId) requestDelete(deleteId);
});

renderDashboard();
renderTemplateSelect();

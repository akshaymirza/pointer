<?php
require_once 'includes/auth.php';
requireLogin();

$pageTitle = "Admin Console - Dynamic Template Parser System";
$currentPage = "dashboard";
$pageHeading = "Template Management";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="page active" id="dashboardPage">
  <div class="stats-grid">
    <article class="stat-card">
      <span>Total Templates</span>
      <strong id="totalTemplates">0</strong>
      <small>Ready for production use</small>
    </article>
    <article class="stat-card">
      <span>Active Templates</span>
      <strong id="activeTemplates">0</strong>
      <small>Available to operators</small>
    </article>
  </div>

  <div class="toolbar">
    <label class="search-box">
      <span aria-hidden="true">Search</span>
      <input id="searchInput" type="search" placeholder="Search templates" />
    </label>
    <a href="editor.php" class="primary-button" id="newTemplateBtn" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">+ New Template</a>
  </div>

  <section class="content-card table-card">
    <div class="card-heading">
      <div>
        <h2>Template List</h2>
        <p>Manage API parsing templates used by employee workspaces.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Template Name</th>
            <th>API Endpoint Preview</th>
            <th>Output Format Preview</th>
            <th>Last Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="templateTable"></tbody>
      </table>
      <div class="empty-state hidden" id="emptyState">
        <div class="empty-icon">0</div>
        <h3>No templates found</h3>
        <p>Try another keyword or create a new template.</p>
      </div>
    </div>
  </section>
</section>

<?php include 'includes/footer.php'; ?>

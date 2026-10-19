<?php
// includes/footer.php
?>
</main>
</div>

<div class="modal-backdrop hidden" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <div class="modal-icon">!</div>
    <h2 id="modalTitle">Delete template?</h2>
    <p>This action removes the template from the employee workspace.</p>
    <div class="modal-actions">
      <button class="secondary-button" id="cancelDelete">Cancel</button>
      <button class="danger-button" id="confirmDelete">Delete</button>
    </div>
  </div>
</div>

<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="script.js"></script>
</body>
</html>

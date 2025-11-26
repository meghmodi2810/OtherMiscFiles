<?php
// Global modal include — placed in header so it is available on every page.
// Uses the same markup/classes as the manage-groups "View Members" modal
// so styling is identical and JS can target the IDs below.
?>
<!-- Global reusable modal (hidden by default) -->
<div id="globalModal" class="modal" role="dialog" aria-modal="true" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <div style="display:flex; align-items:center; gap:12px;">
        <div id="globalModalIcon" class="modal-icon" style="display:none;"><i data-feather="info"></i></div>
        <h2 id="globalModalTitle"></h2>
      </div>
      <button id="globalModalClose" class="close-modal" aria-label="Close">&times;</button>
    </div>

    <div id="globalModalBody" class="modal-body"></div>

    <div id="globalModalActions" class="modal-actions">
      <!-- Buttons will be injected here by JS (cancel/confirm) -->
    </div>
  </div>
</div>

<?php
/**
 * Transaction Modals Component
 * Implements:
 * 1. Spent Popup (Figma Node 8124:219)
 * 2. Deposit Popup (Figma Node 8124:319)
 */
?>

<!-- 1. SPENT MODAL (Figma Node 8124:219) -->
<div class="trans-modal-overlay" id="spentModal" style="display: none;" aria-hidden="true">
  <div class="trans-modal-backdrop" onclick="closeSpentModal()"></div>
  <div class="trans-modal-container trans-modal-spent">
    <!-- Modal Header -->
    <div class="trans-modal-header">
      <div class="trans-modal-title-group">
        <h3 class="trans-modal-id" id="spentModalId">#TRC123456</h3>
        <span class="trans-badge trans-badge-spent">Spent</span>
      </div>
      <button type="button" class="trans-modal-close" onclick="closeSpentModal()" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="trans-modal-body">
      <!-- Customer Info & Date Bar -->
      <div class="trans-modal-infobar">
        <div class="trans-user-info">
          <img src="assets/images/user-avatar.png" alt="" class="trans-user-avatar" id="spentModalAvatar" />
          <span class="trans-user-name" id="spentModalCustomer">Earl Turner</span>
        </div>
        <div class="trans-datetime-info">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#545454" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span class="trans-datetime-text" id="spentModalDateTime">22 JUN 2026 | 03:30 PM</span>
        </div>
      </div>

      <!-- Service Breakdown Section -->
      <div class="trans-breakdown-section">
        <h4 class="trans-breakdown-title">Service Breakdown</h4>
        <div class="trans-table-card">
          <div class="trans-breakdown-table">
            <div class="trans-breakdown-row trans-breakdown-thead">
              <div class="col-service">Service</div>
              <div class="col-category">Category</div>
              <div class="col-price">Price</div>
            </div>
            <!-- Dynamic Items Container -->
            <div id="spentModalItemsList">
              <div class="trans-breakdown-row">
                <div class="col-service">Hair cut</div>
                <div class="col-category">Haircut</div>
                <div class="col-price">₹ 250.00</div>
              </div>
            </div>
            <!-- Total Billed Amount Row -->
            <div class="trans-breakdown-row trans-breakdown-total">
              <div class="col-total-label">Billed Amount</div>
              <div class="col-total-price" id="spentModalTotal">₹ 250.00</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 2. DEPOSIT MODAL (Figma Node 8124:319) -->
<div class="trans-modal-overlay" id="depositModal" style="display: none;" aria-hidden="true">
  <div class="trans-modal-backdrop" onclick="closeDepositModal()"></div>
  <div class="trans-modal-container trans-modal-deposit">
    <!-- Modal Header -->
    <div class="trans-modal-header">
      <div class="trans-modal-title-group">
        <h3 class="trans-modal-id" id="depositModalId">#TRC123456</h3>
        <span class="trans-badge trans-badge-deposit">Deposit</span>
      </div>
      <button type="button" class="trans-modal-close" onclick="closeDepositModal()" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="trans-modal-body">
      <!-- Deposit Info & Date Bar -->
      <div class="trans-modal-infobar">
        <div class="trans-source-info">
          <span class="trans-source-by">By</span>
          <span class="trans-source-brand">ScutS</span>
        </div>
        <div class="trans-datetime-info">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#545454" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span class="trans-datetime-text" id="depositModalDateTime">22 JUN 2026 | 03:30 PM</span>
        </div>
      </div>

      <!-- Deposit Receipt Preview -->
      <div class="trans-receipt-section">
        <div class="trans-receipt-card">
          <img src="assets/images/deposit_sample.png" alt="Transaction Successful Receipt" class="trans-receipt-img" id="depositModalReceiptImg" />
        </div>
      </div>
    </div>
  </div>
</div>

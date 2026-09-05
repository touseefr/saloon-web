<?php
/**
 * Product Modals Component
 * Contains Add Product, Edit Product, and Delete Confirmation Popups
 * Figma Designs:
 * - Add: Node 8130:1796
 * - Edit: Node 8130:1959
 * - Delete: Node 8130:2070
 */
?>

<!-- 1. ADD PRODUCT MODAL (Figma Node 8130:1796) -->
<div class="product-modal-backdrop" id="addProductModal" role="dialog" aria-modal="true" aria-labelledby="addProductModalTitle" style="display: none;">
  <div class="product-modal-dialog" style="max-width: 608px;">
    <!-- Header -->
    <div class="product-modal-header">
      <h2 class="product-modal-title" id="addProductModalTitle">Add Product</h2>
      <button type="button" class="product-modal-close-btn" onclick="closeProductModal('addProductModal')" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#707070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Body -->
    <form id="addProductForm" onsubmit="handleProductSubmit(event, 'add')" novalidate>
      <div class="product-modal-body">
        <!-- Product Image -->
        <div class="prod-form-group">
          <label class="prod-form-label">Product image</label>
          <div class="prod-upload-zone" id="addUploadZone" onclick="document.getElementById('addProductImageInput').click()">
            <input type="file" id="addProductImageInput" name="image" accept="image/*" style="display: none;" onchange="handleProductImagePreview(this, 'add')" />
            
            <div class="prod-upload-placeholder" id="addUploadPlaceholder">
              <div class="prod-upload-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
              </div>
              <p class="prod-upload-hint">Upload an image that best represents your product.</p>
            </div>

            <div class="prod-upload-preview-wrap" id="addUploadPreviewWrap" style="display: none;">
              <img id="addUploadPreviewImg" src="" alt="Preview" class="prod-preview-img" />
              <button type="button" class="prod-preview-change-btn" onclick="event.stopPropagation(); document.getElementById('addProductImageInput').click();" title="Change Image">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                  <circle cx="12" cy="13" r="4"></circle>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Product Name -->
        <div class="prod-form-group">
          <label class="prod-form-label" for="addProductName">Product name</label>
          <input type="text" id="addProductName" name="name" class="prod-form-input" placeholder="Enter product name" required />
        </div>

        <!-- Description -->
        <div class="prod-form-group">
          <label class="prod-form-label" for="addProductDescription">Description</label>
          <textarea id="addProductDescription" name="description" class="prod-form-textarea" rows="4" placeholder="Write about product"></textarea>
        </div>

        <!-- Product Category -->
        <div class="prod-form-group">
          <label class="prod-form-label" for="addProductCategory">Product category</label>
          <div class="prod-select-wrap">
            <select id="addProductCategory" name="serviceCategoryId" class="prod-form-select" required>
              <option value="" disabled selected>Select category</option>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['id']) ?>" data-profession="<?= htmlspecialchars($cat['profession'] ?? 'hair') ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="default_hair" data-profession="hair">Hair Colouring</option>
                <option value="default_hair_care" data-profession="hair">Hair Care</option>
                <option value="default_beard" data-profession="beauty">Beard Oil</option>
                <option value="default_face" data-profession="beauty">Facial Care</option>
              <?php endif; ?>
            </select>
            <span class="prod-select-arrow" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </span>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="product-modal-footer">
        <button type="button" class="prod-btn-outline" onclick="closeProductModal('addProductModal')">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          <span>CANCEL</span>
        </button>
        <button type="submit" class="prod-btn-primary" id="addProductSubmitBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <span>SAVE</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 2. EDIT PRODUCT MODAL (Figma Node 8130:1959) -->
<div class="product-modal-backdrop" id="editProductModal" role="dialog" aria-modal="true" aria-labelledby="editProductModalTitle" style="display: none;">
  <div class="product-modal-dialog" style="max-width: 608px;">
    <!-- Header -->
    <div class="product-modal-header">
      <h2 class="product-modal-title" id="editProductModalTitle">Edit Product</h2>
      <button type="button" class="product-modal-close-btn" onclick="closeProductModal('editProductModal')" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#707070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Body -->
    <form id="editProductForm" onsubmit="handleProductSubmit(event, 'edit')" novalidate>
      <input type="hidden" id="editProductId" name="productId" value="" />
      <div class="product-modal-body">
        <!-- Product Image -->
        <div class="prod-form-group">
          <label class="prod-form-label">Product image</label>
          <div class="prod-upload-zone has-image" id="editUploadZone" onclick="document.getElementById('editProductImageInput').click()">
            <input type="file" id="editProductImageInput" name="image" accept="image/*" style="display: none;" onchange="handleProductImagePreview(this, 'edit')" />
            
            <div class="prod-upload-preview-wrap" id="editUploadPreviewWrap">
              <img id="editUploadPreviewImg" src="assets/images/portfolio_sample1.png" alt="Preview" class="prod-preview-img" />
              <button type="button" class="prod-preview-change-btn" onclick="event.stopPropagation(); document.getElementById('editProductImageInput').click();" title="Change Image">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                  <circle cx="12" cy="13" r="4"></circle>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Product Name -->
        <div class="prod-form-group">
          <label class="prod-form-label" for="editProductName">Product name</label>
          <input type="text" id="editProductName" name="name" class="prod-form-input" placeholder="Enter product name" required />
        </div>

        <!-- Description -->
        <div class="prod-form-group">
          <label class="prod-form-label" for="editProductDescription">Description</label>
          <textarea id="editProductDescription" name="description" class="prod-form-textarea" rows="4" placeholder="Write about product"></textarea>
        </div>

        <!-- Product Category -->
        <div class="prod-form-group">
          <label class="prod-form-label" for="editProductCategory">Product category</label>
          <div class="prod-select-wrap">
            <select id="editProductCategory" name="serviceCategoryId" class="prod-form-select" required>
              <option value="" disabled>Select category</option>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['id']) ?>" data-profession="<?= htmlspecialchars($cat['profession'] ?? 'hair') ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="default_hair" data-profession="hair">Hair Colouring</option>
                <option value="default_hair_care" data-profession="hair">Hair Care</option>
                <option value="default_beard" data-profession="beauty">Beard Oil</option>
                <option value="default_face" data-profession="beauty">Facial Care</option>
              <?php endif; ?>
            </select>
            <span class="prod-select-arrow" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </span>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="product-modal-footer">
        <button type="button" class="prod-btn-outline" onclick="closeProductModal('editProductModal')">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          <span>DISCARD</span>
        </button>
        <button type="submit" class="prod-btn-primary" id="editProductSubmitBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <span>SAVE</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 3. DELETE PRODUCT CONFIRMATION MODAL (Figma Node 8130:2070) -->
<div class="product-modal-backdrop" id="deleteProductModal" role="dialog" aria-modal="true" aria-labelledby="deleteProductModalTitle" style="display: none;">
  <div class="product-modal-dialog" style="max-width: 460px;">
    <div class="product-delete-body">
      <h2 class="product-delete-title" id="deleteProductModalTitle">Remove Product Permanently?</h2>
      <p class="product-delete-subtitle">Once removed, the product and its related details will no longer be retrievable.</p>
      <input type="hidden" id="deleteProductId" value="" />
    </div>

    <!-- Footer Actions (Cancel / Remove) -->
    <div class="product-delete-footer">
      <button type="button" class="prod-btn-cancel-fixed" onclick="closeProductModal('deleteProductModal')">
        Cancel
      </button>
      <button type="button" class="prod-btn-remove-fixed" id="confirmDeleteProductBtn" onclick="handleConfirmDeleteProduct()">
        Remove
      </button>
    </div>
  </div>
</div>

<!-- Toast Notification Container -->
<div id="productToast" class="product-toast" style="display: none;">
  <span id="productToastMsg"></span>
</div>

<!-- Complete Scoped Styles for Product Modals (Figma Node 8130:1796, 8130:1959, 8130:2070) -->
<style>
/* Modal Backdrop Overlay */
.product-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(13, 10, 21, 0.45);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
  box-sizing: border-box;
  animation: prodFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes prodFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes prodScaleUp {
  from { opacity: 0; transform: scale(0.95) translateY(8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Modal Card Dialog */
.product-modal-dialog {
  width: 100%;
  background-color: #FCFCFC;
  border: 1px solid #EDE8F8;
  border-radius: 16px;
  box-shadow: 0 16px 40px rgba(133, 102, 206, 0.2), 0 4px 16px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  box-sizing: border-box;
  animation: prodScaleUp 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
}

/* Header */
.product-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  border-bottom: 1px solid #EDE8F8;
}

.product-modal-title {
  margin: 0;
  font-family: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif;
  font-size: 1.25rem; /* 20px */
  font-weight: 600;
  color: #000000;
  line-height: 1.4;
}

.product-modal-close-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s ease;
}

.product-modal-close-btn:hover {
  background-color: #EDE8F8;
}

/* Modal Body */
.product-modal-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-sizing: border-box;
  max-height: calc(85vh - 140px);
  overflow-y: auto;
}

.prod-form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
  box-sizing: border-box;
}

.prod-form-label {
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #000000;
  line-height: 18px;
}

/* Upload Zone (Figma Node 8130:1865) */
.prod-upload-zone {
  position: relative;
  width: 100%;
  height: 160px;
  border: 1.5px dashed #EDE8F8;
  border-radius: 12px;
  background-color: #F9F7FD;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow: hidden;
}

.prod-upload-zone:hover {
  border-color: #8466CF;
  background-color: #F5F1FD;
}

.prod-upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
  text-align: center;
  box-sizing: border-box;
}

.prod-upload-icon-circle {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background-color: #EDE8F8;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.prod-upload-zone:hover .prod-upload-icon-circle {
  transform: scale(1.05);
}

.prod-upload-hint {
  margin: 0;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 400;
  color: #000000;
  line-height: 18px;
  text-align: center;
}

/* Image Preview in Zone */
.prod-upload-preview-wrap {
  position: relative;
  width: 100%;
  height: 100%;
}

.prod-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.prod-preview-change-btn {
  position: absolute;
  bottom: 12px;
  right: 12px;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background-color: #EDE8F8;
  border: 1px solid #8466CF;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transition: all 0.15s ease;
}

.prod-preview-change-btn:hover {
  background-color: #8466CF;
}

.prod-preview-change-btn:hover svg {
  stroke: #FFFFFF;
}

/* Inputs & Textarea */
.prod-form-input {
  width: 100%;
  height: 46px;
  padding: 12px 16px;
  border: 1px solid #EDE8F8;
  border-radius: 12px;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #000000;
  background-color: #FCFCFC;
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.prod-form-input::placeholder {
  color: #8C8C8C;
  font-weight: 400;
}

.prod-form-input:focus {
  border-color: #8466CF;
  box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
}

.prod-form-textarea {
  width: 100%;
  min-height: 100px;
  padding: 12px 16px;
  border: 1px solid #EDE8F8;
  border-radius: 12px;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #000000;
  background-color: #FCFCFC;
  box-sizing: border-box;
  outline: none;
  resize: vertical;
  line-height: 1.5;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.prod-form-textarea::placeholder {
  color: #8C8C8C;
  font-weight: 400;
}

.prod-form-textarea:focus {
  border-color: #8466CF;
  box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
}

/* Custom Select Wrap */
.prod-select-wrap {
  position: relative;
  width: 100%;
}

.prod-form-select {
  width: 100%;
  height: 46px;
  padding: 12px 40px 12px 16px;
  border: 1px solid #EDE8F8;
  border-radius: 12px;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #000000;
  background-color: #FCFCFC;
  box-sizing: border-box;
  outline: none;
  appearance: none;
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.prod-form-select:focus {
  border-color: #8466CF;
  box-shadow: 0 0 0 3px rgba(132, 102, 207, 0.12);
}

.prod-select-arrow {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Modal Footer */
.product-modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #EDE8F8;
  box-sizing: border-box;
}

.prod-btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  border: 1px solid #707070;
  border-radius: 22px;
  background-color: transparent;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #707070;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}

.prod-btn-outline:hover {
  background-color: #F4F4F5;
  color: #18181B;
  border-color: #18181B;
}

.prod-btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 28px;
  border: none;
  border-radius: 22px;
  background-color: #8466CF;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #FCFCFC;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
  box-shadow: 0 4px 12px rgba(132, 102, 207, 0.25);
}

.prod-btn-primary:hover {
  background-color: #7354BF;
  box-shadow: 0 6px 16px rgba(132, 102, 207, 0.35);
}

.prod-btn-primary:disabled,
.prod-btn-outline:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Delete Confirmation Modal Styles (Figma Node 8130:2070) */
.product-delete-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-sizing: border-box;
}

.product-delete-title {
  margin: 0;
  font-family: 'Manrope', sans-serif;
  font-size: 1.25rem; /* 20px */
  font-weight: 600;
  color: #000000;
  line-height: 1.4;
}

.product-delete-subtitle {
  margin: 0;
  font-family: 'Manrope', sans-serif;
  font-size: 1rem; /* 16px */
  font-weight: 400;
  color: #8C8C8C;
  line-height: 20px;
}

.product-delete-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #EDE8F8;
  box-sizing: border-box;
}

.prod-btn-cancel-fixed {
  width: 120px;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #707070;
  border-radius: 22px;
  background-color: transparent;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #707070;
  cursor: pointer;
  transition: all 0.15s ease;
}

.prod-btn-cancel-fixed:hover {
  background-color: #F4F4F5;
  color: #18181B;
  border-color: #18181B;
}

.prod-btn-remove-fixed {
  width: 120px;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 22px;
  background-color: #EF4444;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem; /* 14px */
  font-weight: 500;
  color: #FCFCFC;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

.prod-btn-remove-fixed:hover {
  background-color: #DC2626;
  box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
}

/* Toast Message */
.product-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background-color: #18181B;
  color: #FFFFFF;
  padding: 12px 20px;
  border-radius: 10px;
  font-family: 'Manrope', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  z-index: 10000;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: prodToastIn 0.25s ease;
}

@keyframes prodToastIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

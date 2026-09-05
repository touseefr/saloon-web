<?php
/**
 * ScutS Stylist Modals Component
 * Implements pixel-perfect popups matching Figma specifications:
 * 1. Add Stylist Popup (Figma Node 8163:661)
 * 2. Edit Stylist Popup (Figma Node 8129:672)
 * 3. Discard Unsaved Changes Popup (Figma Node 8130:876)
 * 4. Save Changes Popup (Figma Node 8129:803)
 * 5. Manage Availability Popup (Figma Nodes 8130:966 & 8130:1175)
 * 6. Remove Stylist Popup (Figma Node 8130:950)
 */
?>

<!-- ========================================================================= -->
<!-- 1. ADD STYLIST POPUP (Figma Node 8163:661) -->
<!-- ========================================================================= -->
<div class="stylist-modal-overlay" id="addStylistModal" style="display: none;" aria-hidden="true">
  <div class="stylist-modal-backdrop" onclick="confirmDiscardAdd()"></div>
  <div class="stylist-modal-container stylist-form-modal">
    <!-- Modal Header (Figma 8163:662) -->
    <div class="stylist-modal-header">
      <h3 class="stylist-modal-title">Add Stylist</h3>
      <button type="button" class="stylist-modal-close" onclick="confirmDiscardAdd()" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Modal Form Body (Figma 8163:665) -->
    <form id="addStylistForm" onsubmit="handleAddStylistSubmit(event)">
      <div class="stylist-modal-body">
        <!-- Avatar Upload (Figma 8163:666) -->
        <div class="stylist-avatar-upload-wrap">
          <label for="addStylistAvatarInput" class="stylist-avatar-uploader" title="Click to upload profile photo">
            <img src="assets/images/user-avatar.png" id="addStylistAvatarPreview" alt="Upload Avatar" class="stylist-avatar-preview-img" style="display:none;" />
            <div class="stylist-avatar-placeholder" id="addStylistAvatarPlaceholder">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
              </svg>
            </div>
            <input type="file" id="addStylistAvatarInput" name="image" accept="image/*" style="display:none;" onchange="previewAddAvatar(this)" />
          </label>
        </div>

        <!-- Row 1: Name & Mobile (Figma 8163:672) -->
        <div class="stylist-form-row">
          <div class="stylist-form-group">
            <label class="stylist-label" for="addStylistName">Name</label>
            <div class="stylist-input-wrap">
              <input type="text" name="name" id="addStylistName" class="stylist-input" placeholder="Enter stylist name" required />
            </div>
          </div>
          <div class="stylist-form-group">
            <label class="stylist-label" for="addStylistMobile">Mobile Number</label>
            <div class="stylist-input-wrap">
              <span class="stylist-input-prefix">+91</span>
              <input type="tel" name="mobile" id="addStylistMobile" class="stylist-input" placeholder="Enter stylist mobile number" pattern="[0-9]{10}" maxlength="10" required />
            </div>
          </div>
        </div>

        <!-- Row 2: Gender & Serviceable Gender (Figma 8163:682) -->
        <div class="stylist-form-row">
          <div class="stylist-form-group">
            <label class="stylist-label" for="addStylistGender">Gender</label>
            <div class="stylist-select-wrap">
              <select name="gender" id="addStylistGender" class="stylist-select" required>
                <option value="" disabled selected>Select stylist gender</option>
                <option value="FEMALE">Female</option>
                <option value="MALE">Male</option>
                <option value="UNISEX">Unisex</option>
              </select>
              <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
          <div class="stylist-form-group">
            <label class="stylist-label" for="addStylistServiceGender">Serviceable gender</label>
            <div class="stylist-select-wrap">
              <select name="serviceableGender" id="addStylistServiceGender" class="stylist-select" required>
                <option value="" disabled selected>Select serviceable gender</option>
                <option value="UNISEX">Unisex</option>
                <option value="FEMALE">Female</option>
                <option value="MALE">Male</option>
              </select>
              <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
        </div>

        <!-- Row 3: Profession (Figma 8313:3348) -->
        <div class="stylist-form-group-full">
          <label class="stylist-label">Profession</label>
          <div class="stylist-checkbox-row">
            <label class="stylist-checkbox-label">
              <input type="checkbox" name="profession[]" value="Hair stylist" checked />
              <span class="custom-checkbox"></span>
              <span class="checkbox-text">Hair stylist</span>
            </label>
            <label class="stylist-checkbox-label">
              <input type="checkbox" name="profession[]" value="Beautician" />
              <span class="custom-checkbox"></span>
              <span class="checkbox-text">Beautician</span>
            </label>
          </div>
        </div>

        <!-- Row 4: Known Languages (Figma 8163:697) -->
        <div class="stylist-form-group-full">
          <label class="stylist-label">Known Languages</label>
          <div class="stylist-chips-wrap" id="addStylistLanguagesWrap">
            <?php
              $availableLanguages = ['English', 'Kannada', 'Hindi', 'Telugu', 'Tamil', 'Assamese', 'Bengali'];
              foreach ($availableLanguages as $lang):
            ?>
              <button type="button" class="stylist-chip" data-lang="<?= htmlspecialchars($lang) ?>" onclick="toggleLanguageChip(this)">
                <?= htmlspecialchars($lang) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="stylist-form-divider"></div>

        <!-- Row 5: Share your work (Portfolio) (Figma 8163:708) -->
        <div class="stylist-form-group-full">
          <div class="stylist-section-heading">
            <span class="heading-main">Share your work</span>
            <span class="heading-sub">Drop images or videos of stylist’s work(Max 3)</span>
          </div>
          <div class="stylist-portfolio-row">
            <div class="portfolio-slot" onclick="triggerPortfolioUpload('addPort1')">
              <div class="portfolio-slot-inner" id="addPortPreview1">
                <span class="slot-add-icon">+</span>
              </div>
              <input type="file" id="addPort1" accept="image/*,video/*" style="display:none;" onchange="previewPortfolio(this, 'addPortPreview1')" />
            </div>
            <div class="portfolio-slot" onclick="triggerPortfolioUpload('addPort2')">
              <div class="portfolio-slot-inner" id="addPortPreview2">
                <span class="slot-add-icon">+</span>
              </div>
              <input type="file" id="addPort2" accept="image/*,video/*" style="display:none;" onchange="previewPortfolio(this, 'addPortPreview2')" />
            </div>
            <div class="portfolio-slot" onclick="triggerPortfolioUpload('addPort3')">
              <div class="portfolio-slot-inner" id="addPortPreview3">
                <span class="slot-add-icon">+</span>
              </div>
              <input type="file" id="addPort3" accept="image/*,video/*" style="display:none;" onchange="previewPortfolio(this, 'addPortPreview3')" />
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer (Figma 8163:740) -->
      <div class="stylist-modal-footer">
        <button type="button" class="btn-stylist-outline" onclick="confirmDiscardAdd()">DISCARD</button>
        <button type="submit" class="btn-stylist-primary" id="addStylistSubmitBtn">ADD STYLIST</button>
      </div>
    </form>
  </div>
</div>


<!-- ========================================================================= -->
<!-- 2. EDIT STYLIST POPUP (Figma Node 8129:672) -->
<!-- ========================================================================= -->
<div class="stylist-modal-overlay" id="editStylistModal" style="display: none;" aria-hidden="true">
  <div class="stylist-modal-backdrop" onclick="confirmDiscardEdit()"></div>
  <div class="stylist-modal-container stylist-form-modal">
    <!-- Modal Header (Figma 8129:673) -->
    <div class="stylist-modal-header">
      <h3 class="stylist-modal-title">Edit stylist</h3>
      <button type="button" class="stylist-modal-close" onclick="confirmDiscardEdit()" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Modal Form Body (Figma 8129:680) -->
    <form id="editStylistForm" onsubmit="handleEditStylistSubmit(event)">
      <input type="hidden" id="editStylistId" name="stylistId" value="" />

      <div class="stylist-modal-body">
        <!-- Avatar Upload with Edit Pen Badge (Figma 8129:727) -->
        <div class="stylist-avatar-upload-wrap">
          <label for="editStylistAvatarInput" class="stylist-avatar-uploader has-badge" title="Click to change photo">
            <img src="assets/images/user-avatar.png" id="editStylistAvatarPreview" alt="Stylist Avatar" class="stylist-avatar-preview-img" />
            <div class="stylist-avatar-edit-badge" title="Edit avatar">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </div>
            <input type="file" id="editStylistAvatarInput" name="image" accept="image/*" style="display:none;" onchange="previewEditAvatar(this)" />
          </label>
        </div>

        <!-- Row 1: Name & Mobile (Figma 8129:752) -->
        <div class="stylist-form-row">
          <div class="stylist-form-group">
            <label class="stylist-label" for="editStylistName">Name</label>
            <div class="stylist-input-wrap">
              <input type="text" name="name" id="editStylistName" class="stylist-input" required />
            </div>
          </div>
          <div class="stylist-form-group">
            <label class="stylist-label" for="editStylistMobile">Mobile Number</label>
            <div class="stylist-input-wrap">
              <span class="stylist-input-prefix">+91</span>
              <input type="tel" name="mobile" id="editStylistMobile" class="stylist-input" pattern="[0-9]{10}" maxlength="10" required />
            </div>
          </div>
        </div>

        <!-- Row 2: Gender & Serviceable Gender (Figma 8321:6307) -->
        <div class="stylist-form-row">
          <div class="stylist-form-group">
            <label class="stylist-label" for="editStylistGender">Gender</label>
            <div class="stylist-select-wrap">
              <select name="gender" id="editStylistGender" class="stylist-select" required>
                <option value="MALE">Male</option>
                <option value="FEMALE">Female</option>
                <option value="UNISEX">Unisex</option>
              </select>
              <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
          <div class="stylist-form-group">
            <label class="stylist-label" for="editStylistServiceGender">Serviceable gender</label>
            <div class="stylist-select-wrap">
              <select name="serviceableGender" id="editStylistServiceGender" class="stylist-select" required onchange="isEditFormDirty = true;">
                <option value="UNISEX">Unisex</option>
                <option value="FEMALE">Female</option>
                <option value="MALE">Male</option>
              </select>
              <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </div>
        </div>

        <!-- Row 3: Profession (Figma 8321:6318) -->
        <div class="stylist-form-group-full">
          <label class="stylist-label">Profession</label>
          <div class="stylist-checkbox-row">
            <label class="stylist-checkbox-label">
              <input type="checkbox" name="profession[]" id="editProfHair" value="Hair stylist" checked onchange="isEditFormDirty = true;" />
              <span class="custom-checkbox"></span>
              <span class="checkbox-text">Hair stylist</span>
            </label>
            <label class="stylist-checkbox-label">
              <input type="checkbox" name="profession[]" id="editProfBeautician" value="Beautician" onchange="isEditFormDirty = true;" />
              <span class="custom-checkbox"></span>
              <span class="checkbox-text">Beautician</span>
            </label>
          </div>
        </div>

        <!-- Row 4: Known Languages (Figma 8161:749) -->
        <div class="stylist-form-group-full">
          <label class="stylist-label">Known Languages</label>
          <div class="stylist-chips-wrap" id="editStylistLanguagesWrap">
            <?php foreach ($availableLanguages as $lang): ?>
              <button type="button" class="stylist-chip" data-lang="<?= htmlspecialchars($lang) ?>" onclick="toggleLanguageChip(this)">
                <?= htmlspecialchars($lang) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="stylist-form-divider"></div>

        <!-- Row 5: Share your work (Portfolio with delete buttons matching Figma 8163:530) -->
        <div class="stylist-form-group-full">
          <div class="stylist-section-heading">
            <span class="heading-main">Share your work</span>
            <span class="heading-sub">Drop images or videos of stylist’s work</span>
          </div>
          <div class="stylist-portfolio-row">
            <!-- Slot 1 -->
            <div class="portfolio-slot" onclick="triggerPortfolioUpload('editPort1')">
              <div class="portfolio-slot-inner" id="editPortPreview1">
                <span class="slot-add-icon">+</span>
              </div>
              <input type="file" id="editPort1" accept="image/*,video/*" style="display:none;" onchange="previewPortfolio(this, 'editPortPreview1')" />
            </div>

            <!-- Slot 2 -->
            <div class="portfolio-slot" onclick="triggerPortfolioUpload('editPort2')">
              <div class="portfolio-slot-inner" id="editPortPreview2">
                <span class="slot-add-icon">+</span>
              </div>
              <input type="file" id="editPort2" accept="image/*,video/*" style="display:none;" onchange="previewPortfolio(this, 'editPortPreview2')" />
            </div>

            <!-- Slot 3 -->
            <div class="portfolio-slot" onclick="triggerPortfolioUpload('editPort3')">
              <div class="portfolio-slot-inner" id="editPortPreview3">
                <span class="slot-add-icon">+</span>
              </div>
              <input type="file" id="editPort3" accept="image/*,video/*" style="display:none;" onchange="previewPortfolio(this, 'editPortPreview3')" />
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer (Figma 8129:783) -->
      <div class="stylist-modal-footer">
        <button type="button" class="btn-stylist-outline" onclick="confirmDiscardEdit()">DISCARD</button>
        <button type="submit" class="btn-stylist-primary" id="editStylistSubmitBtn">SAVE</button>
      </div>
    </form>
  </div>
</div>


<!-- ========================================================================= -->
<!-- 3. DISCARD UNSAVED CHANGES POPUP (Figma Node 8130:876) -->
<!-- ========================================================================= -->
<div class="stylist-modal-overlay stylist-dialog-overlay" id="discardModal" style="display: none;" aria-hidden="true">
  <div class="stylist-modal-backdrop" onclick="closeDiscardModal()"></div>
  <div class="stylist-modal-container stylist-dialog-modal" style="max-width: 400px;">
    <div class="stylist-dialog-content">
      <h4 class="stylist-dialog-title">Discard unsaved changes?</h4>
      <p class="stylist-dialog-text">Any unsaved edits will be permanently lost and cannot be recovered.</p>
    </div>
    <div class="stylist-dialog-footer">
      <button type="button" class="btn-dialog-cancel" style="width: 86px;" onclick="closeDiscardModal()">No</button>
      <button type="button" class="btn-dialog-confirm-primary" style="width: 86px;" onclick="executeDiscard()">Yes</button>
    </div>
  </div>
</div>


<!-- ========================================================================= -->
<!-- 4. SAVE CHANGES POPUP (Figma Node 8129:803) -->
<!-- ========================================================================= -->
<div class="stylist-modal-overlay stylist-dialog-overlay" id="saveChangesModal" style="display: none;" aria-hidden="true">
  <div class="stylist-modal-backdrop" onclick="closeSaveChangesModal()"></div>
  <div class="stylist-modal-container stylist-dialog-modal" style="max-width: 400px;">
    <div class="stylist-dialog-content">
      <h4 class="stylist-dialog-title">Save your changes?</h4>
      <p class="stylist-dialog-text">Your updates are ready to be saved and applied to the website.</p>
    </div>
    <div class="stylist-dialog-footer">
      <button type="button" class="btn-dialog-cancel" style="width: 86px;" onclick="closeSaveChangesModal()">No</button>
      <button type="button" class="btn-dialog-confirm-primary" id="confirmSaveEditBtn" style="width: 86px;" onclick="executeSaveEdit()">Yes</button>
    </div>
  </div>
</div>


<!-- ========================================================================= -->
<!-- 5. MANAGE AVAILABILITY POPUP (Figma Nodes 8130:966 & 8130:1175) -->
<!-- ========================================================================= -->
<div class="stylist-modal-overlay" id="availabilityModal" style="display: none;" aria-hidden="true">
  <div class="stylist-modal-backdrop" onclick="closeAvailabilityModal()"></div>
  <div class="stylist-modal-container stylist-avail-modal">
    <!-- Modal Header (Figma 8130:967) -->
    <div class="stylist-modal-header">
      <h3 class="stylist-modal-title">Manage availability</h3>
      <button type="button" class="stylist-modal-close" onclick="closeAvailabilityModal()" aria-label="Close modal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Modal Body (Figma 8130:970) -->
    <div class="stylist-modal-body">
      <!-- Stylist Info Bar & Today's Availability (Figma 8130:1027) -->
      <div class="avail-stylist-bar">
        <div class="avail-stylist-user">
          <img src="assets/images/user-avatar.png" id="availStylistAvatar" alt="" class="avail-avatar-img" />
          <span class="avail-stylist-name" id="availStylistName">Sidney Gulgowski</span>
        </div>
        <div class="avail-today-toggle-wrap">
          <span class="avail-today-label">Today’s Availability</span>
          <label class="switch-ios">
            <input type="checkbox" id="availTodaySwitch" checked onchange="handleTodayAvailToggle(this)" />
            <span class="slider round"></span>
          </label>
        </div>
      </div>

      <!-- Time-offs Bar (Figma 8130:1145 & 8130:1175) -->
      <div class="avail-timeoff-bar" id="availTimeOffBar">
        <!-- Empty State (Figma 8130:966) -->
        <div class="timeoff-status-wrap" id="timeOffEmptyState">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#707070" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span class="timeoff-status-text">No time-off added</span>
        </div>

        <!-- Active Time-off State (Figma 8130:1175) -->
        <div class="timeoff-active-card" id="timeOffActiveState" style="display: none;">
          <div class="timeoff-active-info">
            <span class="timeoff-active-title">Time-offs</span>
            <div class="timeoff-active-dates">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              <span id="availTimeOffDatesText">JUN 29, JUL 30</span>
            </div>
          </div>
        </div>

        <button type="button" class="btn-add-timeoff" id="btnTimeOffAction" onclick="promptAddTimeOff()">ADD TIME OFF</button>
      </div>

      <!-- Weekly Schedule Card (Figma 8130:1072) -->
      <div class="avail-schedule-card">
        <div class="schedule-header">
          <span>Weekly Schedule</span>
        </div>
        <div class="schedule-days-grid">
          <!-- Sunday: Full width row (Figma 8130:1080) -->
          <div class="schedule-day-item full-width-day">
            <span class="day-name">Sunday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_sunday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
          <!-- Monday -->
          <div class="schedule-day-item">
            <span class="day-name">Monday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_monday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
          <!-- Tuesday -->
          <div class="schedule-day-item">
            <span class="day-name">Tuesday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_tuesday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
          <!-- Wednesday -->
          <div class="schedule-day-item">
            <span class="day-name">Wednesday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_wednesday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
          <!-- Thursday -->
          <div class="schedule-day-item">
            <span class="day-name">Thursday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_thursday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
          <!-- Friday -->
          <div class="schedule-day-item">
            <span class="day-name">Friday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_friday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
          <!-- Saturday -->
          <div class="schedule-day-item">
            <span class="day-name">Saturday</span>
            <label class="switch-ios">
              <input type="checkbox" id="availDay_saturday" class="schedule-day-switch" checked />
              <span class="slider round"></span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer (Figma 8130:1002) -->
    <div class="stylist-modal-footer">
      <button type="button" class="btn-stylist-outline" onclick="closeAvailabilityModal()">DISCARD</button>
      <button type="button" class="btn-stylist-primary" id="saveAvailabilityBtn" onclick="executeSaveAvailability()">UPDATE</button>
    </div>
  </div>
</div>


<!-- ========================================================================= -->
<!-- 6. REMOVE STYLIST POPUP (Figma Node 8130:950) -->
<!-- ========================================================================= -->
<div class="stylist-modal-overlay stylist-dialog-overlay" id="removeStylistModal" style="display: none;" aria-hidden="true">
  <div class="stylist-modal-backdrop" onclick="closeRemoveStylistModal()"></div>
  <div class="stylist-modal-container stylist-dialog-modal" style="max-width: 460px;">
    <div class="stylist-dialog-content">
      <h4 class="stylist-dialog-title">Remove stylist permanently?</h4>
      <p class="stylist-dialog-text">The stylist's profile and associated information may no longer be accessible after removal.</p>
    </div>
    <div class="stylist-dialog-footer">
      <button type="button" class="btn-dialog-cancel" style="width: 120px;" onclick="closeRemoveStylistModal()">Cancel</button>
      <button type="button" class="btn-dialog-confirm-danger" style="width: 120px;" id="confirmRemoveStylistBtn" onclick="executeRemoveStylist()">Remove</button>
    </div>
  </div>
</div>

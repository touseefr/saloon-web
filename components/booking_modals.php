<!-- Shared Booking Modals Component -->

<!-- 1. Main Booking Details Modal (Upcoming, Confirmed, Completed, Cancelled) -->
<div class="booking-modal-overlay" id="bookingDetailsModal" style="display: none;">
    <div class="booking-modal-card">
        <!-- Modal Top Bar -->
        <div class="booking-modal-header">
            <div class="modal-header-left">
                <span class="modal-booking-id" id="modalBookingIdx">#BI123456</span>
                <span class="status-badge" id="modalBookingStatusBadge">Upcoming</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="BookingModalManager.closeAll()" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M13 1L1 13M1 1L13 13" stroke="#545454" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="booking-modal-body">
            <!-- Loading Indicator -->
            <div id="modalLoadingState" class="modal-loading-spinner" style="display: none;">
                <div class="spinner"></div>
                <p>Loading booking details...</p>
            </div>

            <div id="modalMainContent">
                <!-- Date Divider -->
                <div class="modal-datetime-divider">
                    <div class="divider-line"></div>
                    <div class="datetime-pill">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#545454" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span id="modalDateTimeText">22 JUN 2026 | 03:30 PM</span>
                    </div>
                    <div class="divider-line"></div>
                </div>

                <!-- Customer Card -->
                <div class="modal-customer-card">
                    <div class="customer-info-left">
                        <img id="modalCustomerAvatar" src="assets/images/user-avatar.png" alt="Customer Avatar" class="customer-avatar">
                        <div class="customer-text">
                            <h4 id="modalCustomerName">Customer Name</h4>
                            <div class="customer-gender-row">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8466CF" stroke-width="2">
                                    <circle cx="12" cy="8" r="5"/>
                                    <path d="M20 21a8 8 0 0 0-16 0"/>
                                </svg>
                                <span id="modalCustomerGender">Male</span>
                            </div>
                        </div>
                    </div>
                    <a href="tel:" id="modalCustomerContactBtn" class="customer-contact-btn">Contact</a>
                </div>

                <!-- Slot Selector Card (shown when slots exist) -->
                <div class="modal-slot-card" id="modalSlotSection">
                    <div class="slot-info-col">
                        <span class="slot-title">Slot</span>
                        <span class="slot-date" id="modalSlotDate">29 JUL 2026</span>
                    </div>
                    <div class="slot-chips-wrapper" id="modalSlotsContainer">
                        <!-- Dynamic slot pills -->
                        <button type="button" class="slot-chip active">11:00 AM</button>
                        <button type="button" class="slot-chip">02:00 PM</button>
                        <button type="button" class="slot-chip">05:00 PM</button>
                    </div>
                </div>

                <!-- Stylist / Beautician Selectors (shown for upcoming/pending) -->
                <div class="modal-stylist-selection-row" id="modalStylistSection">
                    <div class="select-field-group">
                        <label class="field-label">Stylist</label>
                        <div class="custom-select-box">
                            <select id="modalStylistSelect" class="form-select-styled">
                                <option value="">Select stylist</option>
                            </select>
                            <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="select-field-group" id="modalBeauticianGroup" style="display: none;">
                        <label class="field-label">Beautician</label>
                        <div class="custom-select-box">
                            <select id="modalBeauticianSelect" class="form-select-styled">
                                <option value="">Select beautician</option>
                            </select>
                            <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8C8C8C" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="modal-services-table-wrap">
                    <table class="modal-services-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Service</th>
                                <th style="width: 35%;">Category</th>
                                <th style="width: 25%; text-align: right;">Price</th>
                            </tr>
                        </thead>
                        <tbody id="modalServicesTableBody">
                            <tr>
                                <td>Hair cut</td>
                                <td>Haircut</td>
                                <td style="text-align: right;">₹ 250.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-highlight-row">
                                <td colspan="2" id="modalTotalLabel">Estimated Total</td>
                                <td id="modalTotalAmount" style="text-align: right;">₹ 250.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Rejection Note if cancelled/rejected -->
                <div id="modalRejectionNoteCard" class="modal-rejection-note" style="display: none;">
                    <strong>Reason for rejection:</strong>
                    <span id="modalRejectionNoteText"></span>
                </div>
            </div>
        </div>

        <!-- Modal Footer Actions (shown for Upcoming/Pending bookings) -->
        <div class="booking-modal-footer" id="modalActionFooter">
            <div class="modal-actions-right">
                <button type="button" class="btn-modal-action btn-modal-reject" id="btnTriggerRejectModal" onclick="BookingModalManager.openRejectReasonModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>REJECT</span>
                </button>
                <button type="button" class="btn-modal-action btn-modal-accept" id="btnTriggerAcceptModal" onclick="BookingModalManager.acceptCurrentBooking()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>ACCEPT</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Rejection Reason Modal (Node 8313:3184) -->
<div class="booking-modal-overlay" id="bookingRejectReasonModal" style="display: none;">
    <div class="booking-modal-card" style="max-width: 580px;">
        <div class="booking-modal-header">
            <div class="modal-header-left">
                <h3 class="modal-title-bold">Reject Booking</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="BookingModalManager.closeAll()" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M13 1L1 13M1 1L13 13" stroke="#545454" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="booking-modal-body" style="padding: 24px;">
            <div class="form-group-block">
                <label class="field-label-bold">Rejection Reason</label>
                <div class="reason-options-list" id="rejectionReasonsList">
                    <!-- Dynamic radio options loaded from API -->
                    <label class="reason-radio-card">
                        <input type="radio" name="rejection_reason_id" value="73414a8c-f270-4cfd-bb98-bdd7cc625b7b" checked>
                        <span class="reason-label-text">Stylist Slot Not Available</span>
                    </label>
                    <label class="reason-radio-card">
                        <input type="radio" name="rejection_reason_id" value="7874d27e-1990-4fb0-a1ae-524b2460a4f0">
                        <span class="reason-label-text">Salon is Fully Occupied</span>
                    </label>
                    <label class="reason-radio-card">
                        <input type="radio" name="rejection_reason_id" value="f65a4f55-bc56-48e6-a764-429b460f9601">
                        <span class="reason-label-text">Other</span>
                    </label>
                </div>

                <div style="margin-top: 16px;">
                    <label class="field-label">Additional Remark (Optional)</label>
                    <textarea id="rejectionCustomReason" class="form-textarea-styled" placeholder="Write a reason..."></textarea>
                </div>
            </div>
        </div>

        <div class="booking-modal-footer">
            <div class="modal-actions-right">
                <button type="button" class="btn-modal-action btn-modal-back" onclick="BookingModalManager.backToDetails()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>BACK</span>
                </button>
                <button type="button" class="btn-modal-action btn-modal-reject" id="btnSubmitReject" onclick="BookingModalManager.submitRejection()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>REJECT</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 3. Booking Confirmed Success Popup (Node 8313:3173) -->
<div class="booking-modal-overlay" id="bookingConfirmedModal" style="display: none;">
    <div class="booking-status-feedback-card">
        <img src="assets/images/booking-confirmed.png" alt="Booking Confirmed" class="status-feedback-img">
        <div class="status-feedback-content">
            <h2 class="status-feedback-title">Booking Confirmed!</h2>
            <p class="status-feedback-desc">Your booking has been successfully confirmed. The customer and stylist have been notified.</p>
        </div>
        <button type="button" class="btn-feedback-action btn-feedback-primary" onclick="BookingModalManager.closeAllAndRefresh()">
            Done
        </button>
    </div>
</div>

<!-- 4. Booking Rejected Success Popup (Node 8312:2903) -->
<div class="booking-modal-overlay" id="bookingRejectedModal" style="display: none;">
    <div class="booking-status-feedback-card">
        <img src="assets/images/booking-rejected.png" alt="Booking Rejected" class="status-feedback-img">
        <div class="status-feedback-content">
            <h2 class="status-feedback-title" style="color: #EF4444;">Booking Rejected!</h2>
            <p class="status-feedback-desc">The booking has been rejected and the customer has been notified with the reason.</p>
        </div>
        <button type="button" class="btn-feedback-action btn-feedback-danger" onclick="BookingModalManager.closeAllAndRefresh()">
            Close
        </button>
    </div>
</div>

<script>
/**
 * Booking Modals Manager
 * Handles opening, status updates, API interactions, and transitions between modals
 */
const BookingModalManager = {
    currentBookingId: null,
    currentBookingData: null,
    selectedSlotTime: null,

    // Open booking details modal
    openBooking: function(bookingId, fallbackData = null) {
        if (!bookingId) return;
        this.currentBookingId = bookingId;

        // Reset UI elements
        const detailsModal = document.getElementById('bookingDetailsModal');
        const loadingState = document.getElementById('modalLoadingState');
        const mainContent = document.getElementById('modalMainContent');
        const footer = document.getElementById('modalActionFooter');

        detailsModal.style.display = 'flex';
        loadingState.style.display = 'flex';
        mainContent.style.opacity = '0.3';

        // Fetch details via AJAX
        fetch(`api/booking_action.php?action=get_details&id=${encodeURIComponent(bookingId)}`)
            .then(res => res.json())
            .then(res => {
                loadingState.style.display = 'none';
                mainContent.style.opacity = '1';

                if (res.success && res.data) {
                    this.renderDetails(res.data);
                } else if (fallbackData) {
                    this.renderDetails(fallbackData);
                } else {
                    alert(res.message || 'Unable to load booking details');
                    this.closeAll();
                }
            })
            .catch(err => {
                loadingState.style.display = 'none';
                mainContent.style.opacity = '1';
                console.error('Failed to load booking details:', err);
                if (fallbackData) {
                    this.renderDetails(fallbackData);
                } else {
                    alert('Network error while loading booking details.');
                    this.closeAll();
                }
            });
    },

    // Populate the modal with data
    renderDetails: function(data) {
        this.currentBookingData = data;
        this.selectedSlotTime = null;

        // 1. Header
        document.getElementById('modalBookingIdx').textContent = data.idx || '#' + (data.id || '').substring(0, 8);
        const badge = document.getElementById('modalBookingStatusBadge');
        const status = (data.status || 'upcoming').toLowerCase();
        
        badge.className = 'status-badge';
        if (status === 'upcoming' || status === 'pending') {
            badge.classList.add('badge-upcoming');
            badge.textContent = data.statusLabel || 'Upcoming';
        } else if (status === 'confirmed') {
            badge.classList.add('badge-confirmed');
            badge.textContent = data.statusLabel || 'Confirmed';
        } else if (status === 'completed' || status === 'served') {
            badge.classList.add('badge-completed');
            badge.textContent = data.statusLabel || 'Completed';
        } else {
            badge.classList.add('badge-cancelled');
            badge.textContent = data.statusLabel || 'Cancelled';
        }

        // 2. Date Time
        document.getElementById('modalDateTimeText').textContent = data.dateTime || 'N/A';

        // 3. Customer Info
        document.getElementById('modalCustomerName').textContent = data.user?.name || 'Customer';
        document.getElementById('modalCustomerGender').textContent = data.user?.gender || 'Male';
        const avatar = document.getElementById('modalCustomerAvatar');
        avatar.src = data.user?.avatar || 'assets/images/user-avatar.png';
        avatar.onerror = () => { avatar.src = 'assets/images/user-avatar.png'; };

        const contactBtn = document.getElementById('modalCustomerContactBtn');
        if (data.user?.mobile) {
            contactBtn.href = `tel:${data.user.mobile}`;
            contactBtn.style.display = 'inline-flex';
        } else {
            contactBtn.style.display = 'none';
        }

        // 4. Slots Section
        const slotSection = document.getElementById('modalSlotSection');
        const slotDate = document.getElementById('modalSlotDate');
        const slotsContainer = document.getElementById('modalSlotsContainer');
        slotDate.textContent = data.date || '';

        slotsContainer.innerHTML = '';
        if (data.slots && data.slots.length > 0) {
            slotSection.style.display = 'flex';
            data.slots.forEach((slot, index) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'slot-chip' + (index === 0 || slot.selected ? ' active' : '');
                btn.textContent = slot.time;
                if (index === 0 || slot.selected) {
                    this.selectedSlotTime = slot.raw || slot.time;
                }
                btn.onclick = () => {
                    document.querySelectorAll('.slot-chip').forEach(c => c.classList.remove('active'));
                    btn.classList.add('active');
                    this.selectedSlotTime = slot.raw || slot.time;
                };
                slotsContainer.appendChild(btn);
            });
        } else {
            slotSection.style.display = 'none';
        }

        // 5. Stylist / Beautician Section (shown for upcoming/pending)
        const stylistSection = document.getElementById('modalStylistSection');
        const stylistSelect = document.getElementById('modalStylistSelect');
        const beauticianGroup = document.getElementById('modalBeauticianGroup');
        const beauticianSelect = document.getElementById('modalBeauticianSelect');

        if (status === 'upcoming' || status === 'pending') {
            stylistSection.style.display = 'flex';
            stylistSelect.innerHTML = '<option value="">Select stylist</option>';

            if (data.stylists && data.stylists.length > 0) {
                data.stylists.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    if (data.selectedStylistId && s.id == data.selectedStylistId) {
                        opt.selected = true;
                    }
                    stylistSelect.appendChild(opt);
                });
            }

            if (data.beauticians && data.beauticians.length > 0) {
                beauticianGroup.style.display = 'flex';
                beauticianSelect.innerHTML = '<option value="">Select beautician</option>';
                data.beauticians.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.name;
                    beauticianSelect.appendChild(opt);
                });
            } else {
                beauticianGroup.style.display = 'none';
            }
        } else {
            stylistSection.style.display = 'none';
        }

        // 6. Services Table
        const tbody = document.getElementById('modalServicesTableBody');
        tbody.innerHTML = '';
        if (data.services && data.services.length > 0) {
            data.services.forEach(s => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${s.name}</td>
                    <td>${s.category || 'General'}</td>
                    <td style="text-align: right;">${s.price}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#8C8C8C; padding:16px;">No services listed</td></tr>';
        }

        document.getElementById('modalTotalLabel').textContent = data.totalLabel || ((status === 'completed') ? 'Billed Amount' : 'Estimated Total');
        document.getElementById('modalTotalAmount').textContent = data.totalAmount || '₹ 0.00';

        // 7. Rejection Note
        const rejCard = document.getElementById('modalRejectionNoteCard');
        if (data.rejectionReason) {
            rejCard.style.display = 'block';
            document.getElementById('modalRejectionNoteText').textContent = data.rejectionReason;
        } else {
            rejCard.style.display = 'none';
        }

        // 8. Footer actions (Accept/Reject only shown if pending/upcoming)
        const footer = document.getElementById('modalActionFooter');
        if (status === 'upcoming' || status === 'pending') {
            footer.style.display = 'flex';
        } else {
            footer.style.display = 'none';
        }
    },

    // Accept Booking flow
    acceptCurrentBooking: function() {
        if (!this.currentBookingId) return;

        const stylistId = document.getElementById('modalStylistSelect')?.value || '';
        const startsAt = this.selectedSlotTime || '';

        const btnAccept = document.getElementById('btnTriggerAcceptModal');
        btnAccept.disabled = true;
        btnAccept.innerHTML = '<span class="spinner-small"></span> Accepting...';

        const formData = new FormData();
        formData.append('action', 'accept');
        formData.append('id', this.currentBookingId);
        formData.append('stylistId', stylistId);
        formData.append('startsAt', startsAt);

        fetch('api/booking_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            btnAccept.disabled = false;
            btnAccept.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>ACCEPT</span>
            `;

            if (res.success) {
                // Hide details modal and open Confirmed Modal
                document.getElementById('bookingDetailsModal').style.display = 'none';
                document.getElementById('bookingConfirmedModal').style.display = 'flex';
            } else {
                alert(res.message || 'Failed to accept booking');
            }
        })
        .catch(err => {
            btnAccept.disabled = false;
            btnAccept.innerHTML = '<span>ACCEPT</span>';
            console.error('Accept error:', err);
            alert('Network error while confirming booking.');
        });
    },

    // Open Rejection Reason modal
    openRejectReasonModal: function() {
        document.getElementById('bookingDetailsModal').style.display = 'none';
        const rejectModal = document.getElementById('bookingRejectReasonModal');
        rejectModal.style.display = 'flex';

        // Load predefined rejection reasons if not loaded
        this.loadRejectionReasons();
    },

    // Load reasons from API
    loadRejectionReasons: function() {
        const container = document.getElementById('rejectionReasonsList');
        fetch('api/booking_action.php?action=get_reasons')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data && res.data.length > 0) {
                    container.innerHTML = '';
                    res.data.forEach((r, idx) => {
                        const label = document.createElement('label');
                        label.className = 'reason-radio-card';
                        label.innerHTML = `
                            <input type="radio" name="rejection_reason_id" value="${r.id}" ${idx === 0 ? 'checked' : ''}>
                            <span class="reason-label-text">${r.label || r.code}</span>
                        `;
                        container.appendChild(label);
                    });
                }
            })
            .catch(err => console.error('Error fetching reasons:', err));
    },

    // Back to details modal from reject modal
    backToDetails: function() {
        document.getElementById('bookingRejectReasonModal').style.display = 'none';
        document.getElementById('bookingDetailsModal').style.display = 'flex';
    },

    // Submit Rejection flow
    submitRejection: function() {
        if (!this.currentBookingId) return;

        const reasonRadio = document.querySelector('input[name="rejection_reason_id"]:checked');
        const reasonId = reasonRadio ? reasonRadio.value : '';
        const customReason = document.getElementById('rejectionCustomReason')?.value || '';

        const btnReject = document.getElementById('btnSubmitReject');
        btnReject.disabled = true;
        btnReject.innerHTML = '<span class="spinner-small"></span> Rejecting...';

        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('id', this.currentBookingId);
        formData.append('reasonId', reasonId);
        formData.append('reason', customReason);

        fetch('api/booking_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            btnReject.disabled = false;
            btnReject.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>REJECT</span>
            `;

            if (res.success) {
                document.getElementById('bookingRejectReasonModal').style.display = 'none';
                document.getElementById('bookingRejectedModal').style.display = 'flex';
            } else {
                alert(res.message || 'Failed to reject booking');
            }
        })
        .catch(err => {
            btnReject.disabled = false;
            btnReject.innerHTML = '<span>REJECT</span>';
            console.error('Reject error:', err);
            alert('Network error while rejecting booking.');
        });
    },

    // Close all modals
    closeAll: function() {
        document.querySelectorAll('.booking-modal-overlay').forEach(modal => {
            modal.style.display = 'none';
        });
    },

    // Close all and refresh page data
    closeAllAndRefresh: function() {
        this.closeAll();
        window.location.reload();
    }
};

// Global click listener for view booking buttons
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.btn-view-booking, [data-booking-id]');
        if (trigger) {
            e.preventDefault();
            const bookingId = trigger.getAttribute('data-booking-id') || trigger.dataset.bookingId;
            let fallbackData = null;
            if (trigger.dataset.bookingData) {
                try {
                    fallbackData = JSON.parse(trigger.dataset.bookingData);
                } catch(err) {}
            }
            BookingModalManager.openBooking(bookingId, fallbackData);
        }
    });

    // Close modal when clicking outside card
    document.querySelectorAll('.booking-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                BookingModalManager.closeAll();
            }
        });
    });

    // ESC key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            BookingModalManager.closeAll();
        }
    });
});
</script>

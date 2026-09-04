# ScutS Salon Web Dashboard

A modern, responsive salon management dashboard built with PHP, HTML5, Vanilla CSS, and JavaScript, integrated with the ScutS backend REST API.

## Features

- **Salon Authentication**:
  - Secure login via mobile & password or mobile & OTP.
  - Auto-reauthentication on token expiry.
  - Session management with protected routes.
- **Dashboard Overview (`index.php`)**:
  - Live salon profile, wallet balance, and aggregate customer ratings.
  - Real-time stylist cards with individual metrics and avatars.
  - Timeframe distribution filtering (Today, This Week, This Month, All Time).
  - Latest Bookings section with live API appointments and server-side pagination.
- **Bookings Management (`bookings.php`)**:
  - Dynamic bookings table with stylist info, date & time, price, GST toggle switch, and status badges.
  - Multi-criteria filtering by Stylist, Date Distribution, and Status (Upcoming, Confirmed, Completed, Cancelled).
  - Search by booking ID, customer name, or stylist name.
  - Pagination controls.
- **Interactive Status Popups (`components/booking_modals.php`)**:
  - **Upcoming / Pending Details**: Time slot selection chips, stylist dropdown, services table, Estimated Total, and Accept / Reject buttons.
  - **Booking Confirmed**: Success illustration and confirmation state.
  - **Rejection Reason**: Dynamic reasons loaded from `GET salon/appointments/rejection-reason` with remark field.
  - **Booking Rejected**: Rejection confirmation state with illustration.
  - **Completed View**: Customer info, services table with Billed Amount.
  - **Cancelled View**: Customer info, cancellation reason note, and Estimated Total.

## Project Structure

```
├── api/
│   └── booking_action.php       # AJAX controller for booking status actions
├── assets/
│   ├── css/style.css            # Custom CSS & design system tokens
│   ├── js/main.js               # Client-side UI scripts & mobile menu
│   └── images/                  # Icons, avatars, and illustration assets
├── components/
│   ├── booking_modals.php       # Shared booking status popups & manager
│   ├── navbar.php               # Top navigation component with salon profile
│   └── sidebar.php              # Responsive sidebar navigation
├── includes/
│   ├── api.php                  # ScutsApiClient service with auto-healing tokens
│   └── auth_check.php           # Authentication & session gatekeeper
├── API_DOCUMENTATION (1).md     # Backend API specifications
├── bookings.php                 # Bookings screen
├── config.php                   # App configuration & credentials
├── index.php                    # Dashboard screen
├── login.php                    # Login page
└── logout.php                   # Logout handler
```

## Setup & Running Locally

1. Place the repository in your WAMP/XAMPP `www` or `htdocs` directory:
   ```bash
   cd e:/wamp64/www/dash
   ```
2. Start Apache and ensure cURL and OpenSSL are enabled.
3. Access in your browser:
   ```
   http://localhost/dash/index.php
   ```

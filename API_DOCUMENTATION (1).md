# Salon App — API Documentation

## Base Configuration

| Key | Value |
|-----|-------|
| Base URL | `https://api.Scuts.in/api/v1/` |
| Image Base URL | `https://api.Scuts.in/` |
| HTTP Client | Dio |

---

## Authentication

### Headers

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {accessToken}` |
| `x-access-token` | `{accessToken}` |

### Token Refresh Flow

- Refresh token is sent via `Cookie: refresh-token={refreshToken}`
- 401 / 403 responses automatically trigger a token refresh, then retry the original request
- Tokens are stored in `SharedPreferences` (`PrefConstants.token`, `PrefConstants.userModel`)

### Device Info

`fcmToken` and `deviceId` are included in all authentication requests.

---

## Interceptors

| Interceptor | Purpose |
|-------------|---------|
| `PrettyDioLogger` | Logs requests / responses with headers and body |
| Auth + Refresh | Handles token expiration and refresh |
| Connectivity | Retries requests when connection is restored |

---

## Authentication APIs

### POST `auth/salon/signup`
Register a new salon. No auth required.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| name | string | Salon name |
| describe | string | |
| email | string | |
| countryCode | string | |
| mobile | string | |
| address | string | |
| ownerName | string | |
| ownerEmail | string | |
| ownerCountryCode | string | |
| ownerMobile | string | |
| password | string | |
| verificationCode | string | |
| fcmToken | string | |
| deviceId | string | |
| serviceOfferSlab | string | |
| employeeSlab | string | |
| geolocationLat | double | |
| geolocationLng | double | |
| description | string | |
| homeService | boolean | |
| serviceGender | string | |
| image | file | Multipart |

**Response:** `SalonResponseModel`

---

### POST `auth/salon/send/verification-code`
Send OTP to a salon mobile number. No auth required.

**Request**
```json
{ "mobile": "...", "countryCode": "..." }
```

**Response:** `{ "isRegistered": boolean }`

---

### POST `auth/salon/verify/verification-code`
Verify OTP for a salon. No auth required.

**Request**
```json
{ "mobile": "...", "countryCode": "...", "verificationCode": "..." }
```

**Response:** `OtpVerifyModel` — `{ isRegistered, isVerificationCodeValid }`

---

### POST `auth/salon/login/password`
Login with mobile + password. No auth required.

**Request**
```json
{ "mobile": "...", "countryCode": "...", "password": "...", "fcmToken": "...", "deviceId": "..." }
```

**Response:** `SalonResponseModel`

---

### POST `auth/salon/login/mobile-verification-code`
Login via OTP. No auth required.

**Request**
```json
{ "mobile": "...", "countryCode": "...", "verificationCode": "...", "fcmToken": "...", "deviceId": "..." }
```

**Response:** `SalonResponseModel`

---

### GET `auth/refresh`
Refresh the access token. Uses Cookie header, no bearer token.

- Refresh token sent via `Cookie: refresh-token={refreshToken}`.
- A rotated refresh token may be returned either in the JSON body or via a `Set-Cookie: refresh-token=...` header; the client reads both (body takes priority).
- Outcomes: `success`, `rejected` (401/403 → logout), `noCredentials` (→ logout), `transient` (network / parse error → session kept).

**Response:** Updated `{ accessToken, accessTokenValidTill, refreshToken, refreshTokenValidTill }`

---

### POST `auth/fcm-token`
Re-register the current FCM token with the backend. Requires auth (access token injected automatically).

Called on every FCM token rotation and once per launch. Skipped (no-op) when the user is not logged in or the token is empty. Never throws — a failed sync must not crash startup.

**Request**
```json
{ "fcmToken": "..." }
```

**Response:** `boolean`

---

### POST `auth/artist/send/verification-code`
Send OTP to an artist mobile number.

**Request**
```json
{ "mobile": "...", "countryCode": 91 }
```

**Response:** `boolean`

---

### POST `auth/artist/verify/verification-code`
Verify OTP for an artist.

**Request**
```json
{ "mobile": "...", "countryCode": 91, "verificationCode": "..." }
```

**Response:** `boolean`

---

### POST `auth/artist/login/password`
Artist login with password.

**Request**
```json
{ "mobile": "...", "countryCode": "...", "password": "...", "fcmToken": "...", "deviceId": "..." }
```

**Response:** `SalonArtistResponseModel`

---

### POST `auth/salon/reset-password-otp`
Reset salon password via OTP.

**Request**
```json
{ "mobile": "...", "countryCode": "91", "password": "...", "otp": "..." }
```

**Response:** `boolean`

---

## Salon Profile APIs

### GET `salon/profile`
Get the logged-in salon's profile.

**Response:** `GetSalonProfile`

---

### PATCH `salon/profile`
Update salon profile.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| name | string | |
| email | string | |
| countryCode | string | |
| address | string | |
| ownerCountryCode | string | |
| ownerMobile | string | |
| geolocationLat | double | |
| geolocationLng | double | |
| description | string | |
| homeService | boolean | |
| mobile | string | Optional |
| verificationCode | string | Optional |
| image | file | Optional, multipart |

**Response:** `boolean`

---

## Service Management APIs

### GET `salon/service/category/list`
Get all service categories.

**Response:** `CategoryListModel`

---

### POST `salon/service/add`
Add a new service.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| name | string | |
| description | string | |
| price | number | |
| duration | number | |
| gender | string | |
| homeService | boolean | |
| categoryIds[] | array | Category IDs |
| salonProductIds[] | array | Product IDs |
| image | file | Multipart |

**Response:** Service ID (`string`)

---

### PATCH `salon/service/{serviceId}/update`
Update an existing service. Same fields as Add Service.

**Response:** `boolean`

---

### GET `salon/service/list?status[]=active`
Get all active salon services.

**Response:** `SalonServiceListModel`

---

### GET `salon/service/{salonServiceId}/details`
Get full details of a specific service.

**Response:** `ServicePreviewModel`

---

### PATCH `salon/service/{salonServiceId}/status`
Update service status.

**Request**
```json
{ "status": "active" }
```

**Response:** `boolean`

---

### GET `salon/service/categorized-list`
Get salon services grouped by category.

**Response:** `SettingSalonServiceListModel`

---

## Product Management APIs

### GET `salon/service/product/list`
Get all products.

**Response:** `ProductListModel`

---

### POST `salon/product/add`
Add a new product.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| serviceCategoryId | string | |
| name | string | |
| describe | string | |
| price | number | |
| image | file | Multipart |

**Response:** `boolean`

---

### PATCH `salon/product/{productId}/update`
Update a product.

**Request (FormData):** name, description, price, image (optional)

**Response:** `boolean`

---

### DELETE `salon/product/{productId}/delete`
Delete a product.

**Response:** `boolean`

---

## Stylist / Artist Management APIs

### POST `salon/artist/add`
Add a new stylist to the salon.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| name | string | |
| mobile | string | |
| countryCode | string | |
| experience | string | |
| whatsapp | string | |
| homeService | boolean | |
| password | string | |
| gender | string | |
| address | string | |
| salonService[i][id] | array | Service IDs |
| salonService[i][serviceableGender] | array | Gender per service |
| image | file | Multipart |

**Response:** `boolean` (409 = duplicate/conflict)

---

### GET `salon/artist/list`
Get all stylists for the salon.

**Response:** `SalonArtistListModel`

---

### GET `salon/artist/all-salon-list`
Get all salon staff (across salons) for selection.

**Response:** `AllSalonStaffModel`

---

### GET `salon/artist/details-by-sid/{sId}`
Look up an existing artist by their SID (Scuts ID).

**Response:** `StaffData` (inside `data`), or `null` if no match.

---

### PUT `salon/artist/add-with-sid`
Add an existing artist to the salon by SID.

**Request**
```json
{ "sId": "..." }
```

**Response:** `boolean`

---

### GET `salon/artist/{artistId}/details`
Get details of a specific stylist.

**Response:** `ArtiestDetailsModel`

---

### DELETE `salon/artist/{artistId}/delete`
Delete a stylist.

**Response:** `boolean`

---

### PATCH `salon/artist/{artistId}/update`
Update stylist services or basic info.

**Request (FormData):** salonService array *or* name/mobile/experience/image fields.

**Response:** `boolean`

---

## Appointment APIs (Salon-side)

### GET `salon/appointments/pending-appointments`
Get all pending appointments.

**Query Params:** `{ distribution }` — defaults to `today`

**Response:** `PendingAppointmentsListModel`

---

### GET `salon/appointments/{appointmentId}`
Get details of a specific appointment.

**Response:** `AppointmentDetailsModel`

---

### PUT `salon/appointments/{appointmentId}/status`
Update appointment status.

**Request**

| Field | Type | Notes |
|-------|------|-------|
| status | string | `accepted`, `rejected`, etc. |
| artistId | string | Optional |
| startsAt | string | Optional, ISO8601 |
| endsAt | string | Optional, ISO8601 |

**Response:** `boolean`


accept API schema

export const changeAppointmentConfirmationSalonSchema = yup.object({
  status: yup
    .string()
    .oneOf([ORDER_STATUS.CONFIRMED, ORDER_STATUS.SALON_REJECTED])
    .required(),

  stylistIds: yup
    .array()
    .of(yup.string().uuid())
    .optional()
    .nullable(),

  startsAt: yup
    .date()
    .typeError('startsAt must be a valid date')
    .optional()
    .nullable(),
});


Backend APIs

Check if balance API integrated
Update Accept Booking API
Check whether Salon Reject API exist, If exist, Update API to receive reason for salon rejection
Check How can you send Scuts 7000 rs in transaction history from backend
~~API to check if SID is available~~ ✅ Done — GET `salon/artist/details-by-sid/{sId}` + PUT `salon/artist/add-with-sid`
API to check if number linked any of SID
Update existing API to receive profession, languages known and portfolio
Update Product API
~~Update content API to receive image and video~~ ✅ Done — salon/blog routes now accept image + video
~~New API for menu change~~ ✅ Done — POST/GET `salon/account/menu/change-request`

---

### GET `salon/appointments/rejection-reason`
Get the list of predefined salon rejection reasons.

**Response:** `RejectionReasonModel`

---

### GET `salon/appointments/cancel-appointments`
Get cancelled appointments.

**Query Params:** `{ distribution }`

**Response:** `PendingAppointmentsListModel`

---

### GET `salon/appointments/served-appointments`
Get served/completed appointments.

**Query Params:** `{ distribution }`

**Response:** `PendingAppointmentsListModel`

---

### PUT `salon/appointments/complete-with-completion-token`
Complete an appointment via QR code scan.

**Request**
```json
{ "appointmentId": "..." }
```

**Response:** `AllowPortfolioUploadModel`

---

### PUT `salon/appointments/{appointmentId}/portfolio-upload`
Upload portfolio images/videos after appointment.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| images[] | files | Multiple allowed |
| videos[] | files | Multiple allowed |

**Response:** `string` (message)

---

## Appointment APIs (Artist-side)

### GET `artist/appointments/pending-appointments`
Get upcoming bookings for the artist.

**Response:** `PendingAppointmentsListModel`

---

### GET `artist/appointments/upcoming/confirm-appointments`
Get confirmed upcoming bookings.

**Response:** `PendingAppointmentsListModel`

---

### GET `artist/appointments/{appointmentId}`
Get appointment details (artist view).

**Response:** `AppointmentsDetailsModel`

---

### PUT `artist/appointments/{appointmentId}/status`
Update appointment status (artist).

**Request**
```json
{ "status": "..." }
```

**Response:** `boolean`

---

### GET `artist/appointments/served-appointments`
Get served appointments for the artist.

**Query Params:** `{ distribution }`

**Response:** `PendingAppointmentsListModel`

---

### PUT `artist/appointments/complete-with-completion-token`
Complete appointment via QR scan (artist).

**Request**
```json
{ "completionToken": "..." }
```

**Response:** `AllowPortfolioUploadModel`

---

### PUT `artist/portfolio/appointments/{appointmentId}/portfolio-upload`
Upload portfolio media (artist).

**Request (FormData):** images[], videos[]

**Response:** `string` (message)

---

### PATCH `artist/appointments/{appointmentId}/time-slot`
Update a booking time slot.

**Request**
```json
{ "changeMinutes": 30 }
```

**Response:** `boolean`

---

## Blog / Portfolio APIs

### POST `artist/blog/create`
Create a blog post.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| title | string | |
| body | string | |
| description | string | |
| externalLink | string | |
| image | file | Multipart |
| video | file | Multipart |

**Response:** `boolean`

---

### GET `artist/blog/list`
Get artist blog list.

**Response:** `BlogDataGetModel`

---

### GET `artist/portfolio/`
Get artist portfolio.

**Response:** `ArtistPortfolioModel`

---

### PATCH `artist/portfolio/{portfolioId}`
Update a portfolio item.

**Request (FormData):** image or video file.

**Response:** `boolean`

---

## Salon Blog / Content APIs

### GET `salon/blog/salon-blogs`
List all salon content/blog items.

**Response:** `BlogDataGetModel`

---

### POST `salon/blog/add`
Create a new salon content item.

**Request (FormData)**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| description | string | Yes | |
| file | file | No | Multipart — image or video |

**MIME detection:** images use `lookupMimeType(path, headerBytes: [0xFF, 0xD8])`; videos use `lookupMimeType(path)` (no magic bytes).

**Response:** `boolean`

---

### PUT `salon/blog/{id}`
Update an existing salon content item. All fields optional.

**Request (FormData)**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| description | string | No | |
| file | file | No | Multipart — image or video |

**Response:** `boolean`

---

### DELETE `salon/blog/{id}`
Delete a salon content item.

**Response:** `boolean`

---

## Review APIs

### GET `salon/review/overall/list`
Get overall salon reviews.

**Response:** `OverallReviewListModel`

---

### GET `salon/review/by-artist`
Get salon reviews grouped by artist.

**Response:** `SalonReviewByArtiestModel`

---

### GET `artist/review/overall/list`
Get overall artist reviews.

**Response:** `OverallReviewListModel`

---

## Availability APIs

### GET `salon/availability/artist/{artistId}/availability`
Get artist availability schedule.

**Response:** `ArtiestAvailabilityGetModel`

---

### POST `salon/availability/artist/{artistId}/block-slot`
Block a time slot for an artist.

**Request**
```json
{ "start": "2025-01-01T10:00:00Z", "end": "2025-01-01T11:00:00Z" }
```

**Response:** `ArtiestAvailabilityGetModel`

---

### DELETE `salon/availability/artist/{artistId}/block-slot/{blockId}`
Delete a blocked slot.

**Response:** `boolean`

---

### PATCH `salon/availability/artist/{artistId}/availability`
Update artist availability.

**Request:** availability map

**Response:** `boolean`

---

### GET `salon/availability`
Get salon-level availability.

**Response:** `SalonAvailability`

---

### PATCH `salon/availability`
Update salon-level availability.

**Request:** availability map

**Response:** `boolean`

---

## Dashboard & Analytics APIs

### GET `salon/dashboard/analytics`
Get salon analytics dashboard data.

**Query Params:** `{ distribution }`

**Response:** `SalonDashboardModel`

---

### GET `artist/dashboard/analytics`
Get artist analytics dashboard data.

**Query Params:** `{ distribution }`

**Response:** `ArtiestDashboardModel`

---

### POST `salon/dashboard/request-recharge`
Request a salon wallet recharge.

**Request:** empty body `{}`

**Response:** `string` (server `message`) or `null`

---

## Transaction / Settlement APIs

### GET `salon/transactions/settled`
Get settled transaction history.

**Query Params:** `{ distribution }`

**Response:** `TransactionsHistoryModel`

---

### GET `salon/transactions/unsettled`
Get unsettled transaction history.

**Query Params:** `{ distribution }`

**Response:** `TransactionsHistoryModel`

---

## Category Management APIs

### GET `salon/category/list`
Get all salon categories.

**Response:** `SalonCategoryListModel`

---

### POST `salon/category/create`
Create a new category.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| name | string | |
| description | string | |
| serviceableGender | string | |
| profession | string | |
| imageMale | file | Optional, multipart |
| imageFemale | file | Optional, multipart |

**Response:** `boolean`

---

### PATCH `salon/category/{salonCategoryId}/update`
Update a category.

**Request (FormData):** name, description, serviceableGender, imageMale (optional), imageFemale (optional)

**Response:** `boolean`

---

### DELETE `salon/category/{salonCategoryId}/delete`
Delete a category.

**Response:** `boolean`

---

## Bank Account APIs

### GET `salon/account`
Get salon bank accounts.

**Response:** `SalonBankAccountList`

---

### POST `salon/account`
Add a bank account.

**Request:** account details map

**Response:** `boolean`

---

### DELETE `salon/account/{accountId}`
Delete a bank account.

**Response:** `boolean`

---

## Onboarding / Document APIs

### GET `salon/onboarding/eligibility`
Check onboarding eligibility.

**Response:** `EligibilityModel`

---

### GET `salon/onboarding/document`
Get submitted documents.

**Response:** `SalonDocumentGetModel`

---

### PUT `salon/onboarding/document/upload`
Upload an onboarding document.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| documentId | string | |
| image | file | Multipart |

**Response:** `boolean`

---

## Menu Change APIs

### POST `salon/account/menu/change-request`
Submit a salon menu change request with an attached file.

**Request (FormData)**

| Field | Type | Notes |
|-------|------|-------|
| file | file | Multipart — MIME auto-detected via `lookupMimeType` |

**Response:** `boolean`

---

### GET `salon/account/menu/change-request`
Check whether a menu change request is pending.

**Response:** `boolean` (`data == true`)

---

## Common APIs

### GET `common/app/config`
Get app config / update info.

**Response:** `AppUpdateModel`

---

## Notes

- **MIME detection:** `lookupMimeType` is used to dynamically set content-type for file uploads.
- **Array parameters:** Use indexed notation — `salonService[0][id]`, `categoryIds[]`, etc.
- **`distribution` query param:** Used across analytics and history endpoints to filter by time range.
- **Image URLs:** Prefix relative paths with `https://api.Scuts.in/` to form full image URLs.

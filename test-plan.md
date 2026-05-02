# AHNet ISP Dashboard — Test Plan (UI Redesign)

## What changed
PR #1 redesigns the UI from blue/slate to a cream/gold/ink "Homies Lab" theme across all 15 Blade templates. No backend logic changed. Functional flows (PPPoE create, Hotspot batch) were already verified in the previous session — this round focuses on visual proof of the redesign.

## Primary flow tested

1. **Login page** — Visit `/login`. Page must show:
   - Cream/beige background (NOT slate/blue).
   - Yellow blur-blob decorations behind the card.
   - Login card uses glassmorphism (`bg-white/85 backdrop-blur`, rounded-3xl).
   - Submit button is dark/ink (`#1a1a1a`), NOT blue/emerald.
2. **Login submit** — `admin@ahnet.local / password` → redirects to `/dashboard`.
3. **Dashboard renders** with the new theme:
   - Hero greeting "Halo, Admin AHNet 👋" visible at top.
   - Sidebar is light/cream with rounded-3xl card; active nav item ("Dashboard") has dark/ink background and white text (NOT blue).
   - Sidebar bottom shows user profile pill (white rounded card with admin name + email).
   - 4 stat tiles: 3 white + 1 dark/ink card (Layanan Isolir).
   - Yellow gauge ring (SVG) showing % active services.
   - Bar chart "Pelanggan Tahunan" rendered with yellow gradient bars (NOT blue).
   - Donut chart "Komposisi Status Layanan" uses palette `#f5c542 / #1a1a1a / #fde79a / #cfcfcf` (NOT blue/emerald).
   - "Kesehatan Layanan" panel is dark/ink card with white text.
4. **Inner page (PPPoE list)** — Navigate to `/pppoe`:
   - Background still cream.
   - Page card is `rounded-3xl` with soft shadow (NOT rounded-xl with hard border).
   - "Buat User PPPoE" button is dark ink, NOT emerald/blue.
5. **Inner page (Hotspot create form)** — Navigate to `/hotspot/create`:
   - Form card `rounded-3xl shadow-card`.
   - Submit button is dark ink.

## Key adversarial assertions

- **Sidebar active state must be dark, not blue.** If the redesign were not applied, the active item would still have `bg-[#0f4d8a]` (the old blue). A broken redesign would visibly show blue here.
- **Donut chart must use yellow/ink colors.** A broken color update would still render Chart.js's default blue/red palette.
- **Bar chart bars must be yellow gradient.** Default Chart.js bars are blue — a broken gradient setup would show blue.
- **Login button color** — `#1a1a1a` rendered. If old `bg-[#0f4d8a]` survived in any template, the button would be visibly blue.
- **Plus Jakarta Sans font** — page text must use the new font. If the Google Fonts CDN link were missing, the browser would fall back to a generic sans-serif (visibly different letterforms).

## Functional regression (already proven previous session — not re-running)

- PPPoE form auto-generates `ahnet_*` username, password, writes radcheck/radusergroup/radreply/customer_profiles
- Hotspot batch generates 5-char codes, code = password in radcheck

## What is NOT tested
- Mikrotik/SNMP/GenieACS device-side actions (no real infra)
- CI checks (none configured)

## Pass/fail summary

| Test | Pass criteria |
|------|---------------|
| Login page theme | cream bg + yellow blobs + glass card + dark button visible |
| Login redirects | submit → /dashboard, 200 |
| Dashboard hero | "Halo, Admin AHNet 👋" visible top of page |
| Sidebar active state | active nav item has dark bg + white text (not blue) |
| Sidebar profile pill | user name + email rendered at bottom of sidebar |
| 4 stat tiles | 3 white + 1 dark ink card |
| Gauge ring | yellow SVG ring rendered |
| Bar chart | bars are yellow gradient (not blue) |
| Donut chart | palette yellow / ink / soft-yellow / grey (not blue) |
| Kesehatan Layanan | dark ink card with white text |
| /pppoe page | rounded-3xl card + dark "Buat User PPPoE" button |
| /hotspot/create | rounded-3xl form card + dark submit button |

# Phase 3: Sun Son Solar public landing page

Completed locally on 20 October 2026 after approval of Phase 3. The existing CodeIgniter project, registration/authentication routes and database records are preserved. This phase does not deploy to external hosting or start customer booking implementation.

## Delivered

- `/` now displays the public landing page instead of the CodeIgniter welcome screen.
- Responsive navigation with Home, About us, Products, Services, FAQs, Contact us, Login, Register and Book a service links.
- Hero with the supplied company logo and solar-home artwork; service and product calls to action.
- Company introduction limited to supplied facts, solar benefits with qualified savings language, five product category guides and seven expandable service guides.
- Getting-started steps that match current functionality: explore, create/sign into an account, and understand that booking is not available yet.
- Company service-focus section, seven native expandable FAQs, booking-availability section, contact location and working footer navigation.
- Local SVG category illustrations, native product/service details and keyboard-accessible FAQ summaries. No image-generation service, external stock imagery, font CDN or JavaScript dependency was introduced.
- Mobile menu supports Escape, returns focus on Escape, closes on selection/outside click/focus exit, and moves focus to section destinations. Without JavaScript, navigation stays visible and native details remain functional. Reduced-motion preferences are respected.
- Existing brand palette remains #FFF9D0, #CAF4FF, #A0DEFF and #5AB2FF, supplemented only by the approved dark text colors #17324D and #34546F (plus white surfaces).

## Honest availability and data handling

`app/Config/PublicSite.php` holds descriptive categories from the supplied brief, not product records, inventory, prices, testimonials or operational statistics. The MySQL database has no product/service tables, so the homepage does not query it and can render when those modules are absent. Empty category lists show fallback messages and category text is escaped in the view.

No schemas, SQL scripts or migrations were added; no database records were created or changed. Future catalog integration should load real records through models while preserving the current empty/error states and output escaping.

The booking CTA targets `#booking`, not an absent booking route. Its accessible description and target section explain that online service booking is not available. Register/login links use existing working routes. Creating an account does not submit or reserve a service. Booking submission, review, scheduling, rescheduling and tracking remain Phase 4 work.

Testimonials are omitted until verified customer quotes exist. Only Pasig City, Philippines is displayed as location. Official email, phone and exact office address were not supplied; no such details or contact form were invented. Privacy/terms pages do not exist and have no dead links in the footer.

## Changed files

Added:

- `app/Config/PublicSite.php` — category/service descriptions and FAQs.
- `app/Views/home.php` — landing sections and valid navigation.
- `app/Views/partials/solar_icon.php` — local decorative SVG illustrations.
- `public/css/home.css` — responsive styling using the approved palette.
- `public/js/home.js` — progressive mobile navigation.
- `tests/feature/LandingPageTest.php` — database-independent rendering, empty guides and escaped content checks.
- `tools/Test-LandingPage.ps1` — repeatable isolated headless Chrome checks and screenshots.
- This implementation note and four PNG screenshots in `docs/screenshots/`.

Updated:

- `app/Controllers/Home.php` — supplies category information and renders the landing view.
- `README.md` — homepage setup, availability, test commands, data limitations and browser QA instructions.
- `.gitignore` — excludes the browser QA profile under `writable/`.

Registration views, registration CSS/JavaScript, authentication behavior and route definitions were not changed in this phase. The old `welcome_message.php` remains as an unused starter reference.

## Verification

- PHP syntax checks passed for the controller, landing view, category configuration and SVG partial.
- Full PHPUnit suite: **40 tests, 215 assertions passed**, including existing registration/authentication checks.
- HTTP smoke checks returned 200 for `/`, `/register`, `/login`, `css/home.css` and `js/home.js`.
- Headless Chrome checked widths 1440, 1024, 768, 390 and 320 with no horizontal overflow.
- All rendered image resources loaded; every internal section link resolved to an existing ID; no browser JavaScript/runtime/resource errors occurred during the normal page checks.
- Mobile menu open, Escape close/focus return and section-link close/focus movement passed.
- FAQ expansion via Enter and product/service details expansion passed.
- Navigation and FAQ operation without JavaScript passed. Deliberately blocking the hero image retained descriptive alternative text, image-frame dimensions and the primary action.
- Screenshots were visually inspected at desktop and mobile viewport sizes; full-page versions are also saved. The images depict the local test server, not external deployment.
- No real account submission, booking, database mutation, Git commit, push or PR update occurred.

## Screenshots

- [Desktop viewport](screenshots/home-desktop.png) — 1440 × 900.
- [Mobile viewport](screenshots/home-mobile.png) — 390 × 900.
- [Full desktop page](screenshots/home-desktop-full.png).
- [Full mobile page](screenshots/home-mobile-full.png).

The temporary QA server uses port 8087 with a process-only base URL override. For normal development, run `php spark serve --host localhost --port 8080` and open `http://localhost:8080/` using the existing local configuration. The isolated QA browser profile is ignored and contains no signed-in user account.

## Remaining work

Connect real product/service data after reviewed migrations exist; provide verified company contacts and testimonials; implement booking/customer features in Phase 4; create policy pages when approved content is available. Git branch/commit/push/PR status cannot be determined because this workspace has no Git metadata. Recover the original checkout before the GitHub phase; no replacement repository was initialized.

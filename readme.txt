=== Fleetwire – Car Rental Booking & Website Builder ===
Contributors: fleetwire
Tags: car rental, rent a car, rental booking, vehicle rental, booking calendar
Requires at least: 6.3
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Build a car rental website in minutes: one-click starter pages, car rental blocks and patterns, live availability search and online booking.

== Description ==

**Fleetwire turns WordPress into a car rental website.** Connect your Fleetwire account, click **Build my rental site**, and get draft Home, Our Fleet, FAQ, Contact and vehicle pages filled with your own fleet — photos, prices, live availability search and booking buttons included. Review the drafts, edit the text, and publish.

Whether you rent cars, trucks, vans, RVs, trailers or equipment, visitors browse your vehicles, pick dates and book on your own site instead of a marketplace.

= Build your rental website in one click =

* **Starter pages from your fleet:** Home, Our Fleet, FAQ, Contact and a page for each vehicle you pick, created as drafts. Nothing is published or overwritten, and pages you already have (like an existing FAQ) are reused instead of duplicated.
* **Publish when you're ready:** publish pages one by one, or all at once with **Publish all draft pages**.
* **Menu and front page:** optionally add the pages to your site menu and make the new Home your front page once you publish it.

= Car rental blocks =

Six blocks in a **Fleetwire** category, with previews of your real vehicles right in the editor:

* **Vehicles** — your bookable fleet as a responsive grid of cards, with a limit, paging and price toggle.
* **Search** — trip dates search that sends renters to available vehicles.
* **Vehicle Card**, **Book Button**, **Vehicle Gallery** and **Vehicle Details** — pick a vehicle from a searchable list.

= Ready-made car rental patterns =

Ten sections in the **Fleetwire – Car Rental** pattern category: hero with date search, fleet grid, how renting works, why book direct, rental FAQ, rental requirements, book-now banner, contact and hours, vehicle spotlight and three featured vehicles. Patterns use core blocks, so they pick up your theme's fonts and colors, and work with block themes like Twenty Twenty-Five as well as classic themes.

= Booking handled by Fleetwire =

Renters book through Fleetwire's secure hosted checkout, with real-time availability, online payments and security deposits, rental agreements and order management in your Fleetwire dashboard. Your vehicles, prices and photos stay in sync automatically — update them once in Fleetwire and your site follows.

= Built for rental businesses =

* **Car rental companies:** a branded car rental website with real-time availability and online booking.
* **Independent hosts:** take direct bookings instead of relying only on marketplaces.
* **Truck, van and trailer rentals:** box trucks, cargo vans, trailers and haulers with per-day pricing.
* **RV and camper rentals:** let travelers check dates and reserve motorhomes and campers online.
* **Equipment rentals:** tools, machines and other rental inventory.

= Also included =

* **Shortcodes** for every embed (`[fleetwire_list]`, `[fleetwire_search]`, `[fleetwire_listing_card]`, `[fleetwire_button]` and more) for page builders and classic editors, with a shortcode builder in WordPress admin.
* **Connection status** to confirm WordPress can reach your Fleetwire account.
* **Fleetwire dashboard** in WordPress admin with setup progress, connection status, and your upcoming pickups and returns (with an optional read-only integration token).
* **Translation ready.**

> **Note:** A Fleetwire account is required. Visit [fleetwire.io](https://fleetwire.io/) to learn more or start your **free 14-day trial**.

== Installation ==

1. In WordPress, go to **Plugins > Add New**, search for **Fleetwire**, then install and activate it.
2. In your Fleetwire dashboard, open **Settings > Online reservations > Installation > WordPress plugin** and copy your Company ID.
3. In WordPress, open **Fleetwire** from the admin menu, paste your Company ID, save, and click **Check connection**.
4. Under **Build your rental website**, choose your pages and vehicles and click **Build my rental site**. Or add Fleetwire blocks and patterns to any page from the block editor.
5. Optional: create a WordPress integration token in Fleetwire and paste it into the plugin settings to see the host dashboard.

== External services ==

This plugin connects your site to [Fleetwire](https://fleetwire.io/), the rental platform that stores your vehicles and handles bookings. A Fleetwire account is required.

* **Fleetwire API** (`{your-company}.fleetwire.io`): in WordPress admin, the plugin requests your public vehicle list (names, photos, prices) to fill the vehicle picker, previews, patterns and starter pages, and checks the connection with your Company ID. If you add an integration token, it also requests your private dashboard summary. Your Company ID, and the token when set, are sent with these requests.
* **Fleetwire embed script** (`{your-company}.fleetwire.io/tenant/v2/fleetwire.js`): loaded on your public pages to show vehicles, search and booking buttons. It requests your vehicles and company details from the Fleetwire API, and sends the page address and plugin version so Fleetwire can show where your embeds run. When a visitor books, they continue to Fleetwire's hosted checkout.
* **Vehicle photos** are served from Fleetwire's image service (`media.fleetwire.io`).
* **Date picker styles** are loaded from jsDelivr (`cdn.jsdelivr.net`) by the embed script.

Fleetwire [terms of service](https://fleetwire.io/terms/) and [privacy policy](https://fleetwire.io/privacy-policy/). jsDelivr [privacy policy](https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net).

== Diagnostic Logs ==

Fleetwire keeps diagnostic files outside the WordPress web roots in a site-specific system temporary directory. Unix directory/file permissions are restricted to the PHP worker. System cleanup may remove temporary logs. For persistent storage, define FLEETWIRE_LOG_DIR in wp-config.php as an absolute private directory outside all web-served paths, writable only by the PHP worker.

On upgrade, existing uploads/fleetwire-logs/fleetwire.log records are copied to private storage before the public file is emptied and removed. If storage or migration is unavailable, Fleetwire reports the problem through the host-managed PHP error log; remove any remaining legacy upload log from public access and configure private storage. Keep the host's PHP error-log destination private as well. Logging level filters and the log-file, size and clear helpers remain available; the file helper returns an empty string when private storage cannot be used.

== Development Checks ==

Run `php tests/blocks.php /path/to/wordpress`, `php tests/patterns.php /path/to/wordpress` and `php tests/site-builder.php /path/to/wordpress` against a local WordPress install with the plugin active to check block registration, block and shortcode markup parity, escaping and the editor listings endpoint. `php tests/playground/seed-playground.php /path/to/wordpress` builds a page showing every shortcode and option rendered live.

Run `python3 tests/security-regressions.py` with PHP, Bash, Subversion 1.9+ and svnadmin installed. The tests use dummy credentials, mock all release publication commands, and use only a local SVN fixture and a temporary localhost HTTP server. They never publish a release or contact GitHub/WordPress.org.

== Frequently Asked Questions ==

= Does this plugin require a Fleetwire subscription? =
Yes. Visit [fleetwire.io](https://fleetwire.io) to create an account or start a free 14-day trial.

= Will Build my rental site change my existing pages? =
No. It only creates draft pages and never publishes or overwrites anything. If you already have a page at /faq/, /contact/ or /fleet/, it reuses yours instead of making a duplicate. Your current home page stays live until you publish the new Home.

= Does it work with my theme? =
The blocks and patterns use WordPress core blocks, so they take on your theme's fonts and colors. They work with block themes such as Twenty Twenty-Five and with classic themes. Shortcodes work in any theme and page builder.

= Do I need to know how to code? =
No. Use Build my rental site, or add Fleetwire blocks and patterns from the block editor and pick your vehicles from a list.

= Can I use shortcodes instead of blocks? =
Yes. Every embed has a shortcode, and the Fleetwire settings page builds them for you: pick a vehicle and copy the shortcode into any page, post or page builder.

= Do customers book directly on my website? =
Visitors browse vehicles and check availability on your WordPress pages, then complete their reservation in Fleetwire's secure hosted checkout, with real-time inventory, payment processing and order management.

= Does it work for RV, trailer or equipment rentals? =
Yes. Fleetwire supports any rental fleet — cars, trucks, vans, RVs, campers, trailers and equipment.

== Screenshots ==

1. A rental home page built by the plugin: hero with trip date search and your fleet.
2. Live availability search with a date picker on your own site.
3. Build my rental site: pick pages and vehicles and get draft pages in one click.
4. Car rental patterns in the block editor's Patterns tab.
5. Fleetwire blocks with previews of your real vehicles and a vehicle picker.
6. A vehicle page with photo gallery, details and a book button.
7. The Fleetwire dashboard: setup progress, your connection and the next step.

== Changelog ==
= 1.1.0 =
* New: Build my rental site — creates draft Home, Our Fleet, FAQ, Contact and vehicle pages from your fleet, reuses pages you already have, and can publish them all at once.
* New: six Fleetwire blocks (Vehicles, Search, Vehicle Card, Book Button, Vehicle Gallery, Vehicle Details) with editor previews of your real vehicles.
* New: ten car rental block patterns in the "Fleetwire – Car Rental" category.
* New: vehicle photo gallery with thumbnails.
* New: redesigned Fleetwire admin with a dashboard, setup progress and separate Build site, Shortcodes and Settings sections. Once you are connected, settings stay out of the way.
* New: pickups and returns load automatically when an integration token is set, and the shortcode builder loads your vehicles when opened.
* Improved: the vehicle list respects limit, paging and price settings, shows only bookable vehicles in your fleet order, and lays out as a responsive grid.
* Improved: the date picker is no longer cut off inside cover blocks and page-builder sections.
* Fixed: listing picker thumbnails.
* Includes the 1.0.24 security changes below.
* Requires WordPress 6.3 or later.

= 1.0.24 =
* Moved diagnostic logs outside public web directories and added migration of existing upload logs to private storage.
* Required HTTPS and validated SVN working-copy destinations before release updates and commits.
* Prevented release previews from exposing credentials or changing release files.
* Added security regression checks and synchronized plugin and asset versions.

= 1.0.23 =
* Renamed for better clarity and consistency
* Code refactoring

= 1.0.22 =
* Added Fleetwire connection status, scoped integration token storage, and a host dashboard for active listings, upcoming pickups, and upcoming returns.
* Added a listing picker and shortcode builder to load Fleetwire listings and generate supported embed shortcodes from WordPress admin.
* Updated shortcode examples and aligned supported shortcode classes with Fleetwire's current embed script.

= 1.0.21 =
* **Major Refactoring**: Complete code restructure with class-based architecture and proper namespaces
* **Enhanced Security**: Comprehensive input validation, sanitization, and AJAX request validation
* **Professional Logging**: Added enterprise-level logging system with error tracking
* **Configuration Management**: Centralized plugin settings and constants
* **Performance Improvements**: Conditional loading and optimized asset management
* **Code Standards**: Updated to follow modern WordPress coding standards
* **Developer Experience**: Enhanced documentation and maintainability
* **Backward Compatibility**: All existing functionality preserved

= 1.0.20 =
* Improved security by sanitizing shortcode attributes to prevent potential XSS.
* Updated compatibility with latest WordPress release.

= 1.0.19 =
* Code refactoring

= 1.0.18 =
* Refactored code to align with WordPress coding standards.

= 1.0.17 =
* Refactored code to align with WordPress coding standards.

= 1.0.16 =
* Tweaked Fleetwire Logos and readme.txt

= 1.0.15 =
* Verified compatibility with WordPress 6.8.1
* Updated readme.txt

= 1.0.14 =
* Updated readme.txt

= 1.0.13 =
* Code tweaks
* Updated readme.txt

= 1.0.12 =
* Verified compatibility with WordPress 6.5
* Updated Fleetwire Logos

= 1.0.11 =
* Verified compatibility with WordPress 6.4.3
* Updated Fleetwire Logos

= 1.0.10 =
* Verified compatibility with WordPress 6.1.1

= 1.0.9 =
* Added fleet availability shortcode to search for available vehicles
* Code styling

= 1.0.8 =
* Added two more shortcodes for vehicle seat and door count.
* Up next: vehicle photo gallery and feature list.

= 1.0.7 =
* Shortcode name corrected

= 1.0.6 =
* Added shortcode for single listing description paragraph
* Added more shortcode examples on the settings page

= 1.0.5 =
* Update help article links
* Verified compatibility with WordPress 6.0

= 1.0.4 =
* Corrected filename capitalization
* Put changelog in reverse chronological order

= 1.0.2 =
* Added Banner


= 1.0.0 =
* First release


== Upgrade Notice ==

= 1.1.0 =
Adds Build my rental site, Fleetwire blocks and car rental patterns. Requires WordPress 6.3 or later. Existing shortcodes keep working.

= 1.0.24 =
Security update for diagnostic log storage and release tooling. Existing upload logs migrate to private storage when writable. If migration fails, remove the old public log and configure FLEETWIRE_LOG_DIR; see Diagnostic Logs above.

= 1.0.22 =
Adds Fleetwire admin connection tools, listing picker, and host dashboard. No shortcode changes are required for existing supported embeds.

= 1.0.15 =
Verified compatibility with WordPress 6.8.1. No functionality changes.
Readme.txt improvements and metadata updates. No breaking changes.

= 1.0.12 =
Verified compatibility with WordPress 6.5. No functionality changes.

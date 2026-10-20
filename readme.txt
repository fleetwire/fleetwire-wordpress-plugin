=== Fleetwire – Car Rental Booking & Fleet Management ===
Contributors: fleetwire
Tags: car rental, vehicle rental, rental booking, fleet management, booking calendar
Requires at least: 6.3
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 1.0.24
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Build a car rental website in minutes: blocks and ready-made patterns for your fleet, availability search and online booking.


== Description ==

Fleetwire is a car rental booking plugin that connects WordPress to your Fleetwire rental business. Whether you rent cars, trucks, RVs, trailers, or equipment, visitors can browse your vehicle listings, check availability, and book online — right from your own pages.

Use the plugin settings page to connect your Fleetwire Company ID, verify the connection, load listings, and generate supported shortcodes without manually copying listing IDs. Hosts can also add a scoped Fleetwire WordPress integration token to view private dashboard data, including active listing counts, upcoming pickups, and upcoming returns.

## Built for Car Rental and Vehicle Rental Businesses:

Fleetwire powers online reservations for rental operators of every size, including:

- **Car rental companies:** Run a branded car rental website with real-time availability and online booking.
- **Independent hosts:** Take direct bookings for your rental fleet on your own site instead of relying only on marketplaces.
- **Truck, van, and trailer rentals:** Rent out box trucks, cargo vans, trailers, and haulers with per-day pricing.
- **RV and camper rentals:** Let travelers check dates and reserve motorhomes and campers online.
- **Equipment rentals:** Manage bookings for tools, machines, and other rental inventory.

## Key Features:

- **Car rental patterns:** Drop in ready-made sections — hero with search, fleet grid, how it works, FAQ, requirements, contact, vehicle spotlight — from the "Fleetwire – Car Rental" pattern category. Works with any block theme.
- **Fleetwire blocks:** Vehicles grid, Search, Vehicle Card, Book Button, Vehicle Gallery and Vehicle Details blocks, with live previews of your own fleet in the editor.
- **Fleetwire embeds:** Add all listings, availability search, single listing cards, booking buttons, listing descriptions, titles, prices, and images with shortcodes.
- **Listing picker:** Load Fleetwire listings in WordPress admin and generate shortcodes for the selected vehicle.
- **Connection status:** Verify that WordPress can reach the connected Fleetwire Company ID before publishing embeds.
- **Host dashboard:** View active listings, upcoming pickups, and upcoming returns from WordPress when a scoped Fleetwire integration token is configured.
- **Fleetwire checkout:** Send visitors into Fleetwire's hosted reservation flow with real-time inventory, availability, payment, and order management handled by Fleetwire.

## Getting Started:

1. **Install the plugin:** Download, upload, and activate the plugin on your WordPress site.
2. **Connect Fleetwire:** Paste your Fleetwire Company ID into the plugin settings and check the connection.
3. **Load listings:** Use the listing picker to choose a vehicle and generate a shortcode.
4. **Build your pages:** In the block editor, open the inserter's Patterns tab and pick from "Fleetwire – Car Rental", or add Fleetwire blocks. Shortcodes still work in any page, post, or compatible builder block.
5. **Optional host dashboard:** Add a scoped Fleetwire WordPress integration token to load private host dashboard data.


> **Note:** A Fleetwire account is required. Visit [fleetwire.io](https://fleetwire.io/) to learn more or start your **free 14-day trial**.

== Installation ==

## Installation Guide:

1. **Fleetwire Setup:**
   - Open your Fleetwire dashboard.
   - Navigate to **Settings > Online reservations > Installation > WordPress plugin**.
   - Copy your Fleetwire Company ID.
   - If you want private host dashboard data in WordPress, generate a scoped WordPress integration token in Fleetwire.
2. **WordPress Configuration:**
   - In WordPress admin, open **Fleetwire** from the main menu.
   - Paste your Company ID and save.
   - Click **Check connection** to verify the Fleetwire API is reachable.
   - Add your integration token if you want the host dashboard.
   - Use **Load listings** to build shortcodes for your pages or posts.

== Diagnostic Logs ==

Fleetwire keeps diagnostic files outside the WordPress web roots in a site-specific system temporary directory. Unix directory/file permissions are restricted to the PHP worker. System cleanup may remove temporary logs. For persistent storage, define FLEETWIRE_LOG_DIR in wp-config.php as an absolute private directory outside all web-served paths, writable only by the PHP worker.

On upgrade, existing uploads/fleetwire-logs/fleetwire.log records are copied to private storage before the public file is emptied and removed. If storage or migration is unavailable, Fleetwire reports the problem through the host-managed PHP error log; remove any remaining legacy upload log from public access and configure private storage. Keep the host's PHP error-log destination private as well. Logging level filters and the log-file, size and clear helpers remain available; the file helper returns an empty string when private storage cannot be used.

== Development Checks ==

Run `php tests/blocks.php /path/to/wordpress` and `php tests/patterns.php /path/to/wordpress` against a local WordPress install with the plugin active to check block registration, block and shortcode markup parity, escaping and the editor listings endpoint. `php tests/playground/seed-playground.php /path/to/wordpress` builds a page showing every shortcode and option rendered live.

Run `python3 tests/security-regressions.py` with PHP, Bash, Subversion 1.9+ and svnadmin installed. The tests use dummy credentials, mock all release publication commands, and use only a local SVN fixture and a temporary localhost HTTP server. They never publish a release or contact GitHub/WordPress.org.

== Frequently Asked Questions ==

= Does this plugin require a Fleetwire subscription? =
Yes. Visit [fleetwire.io](https://fleetwire.io) to create an account or start a 14-day trial.

= Can I embed listings with a shortcode? =
Yes. Use `[fleetwire_list]` for all listings or use the listing picker in WordPress admin to generate shortcodes for a single listing card, booking button, description, title, price, or image.

= Can I use this for my car rental business? =
Yes. Fleetwire is built for car rental businesses. Connect your Fleetwire account and your WordPress site becomes a car rental booking website with live availability, online reservations, and payments handled through Fleetwire's hosted checkout.

= Does it work for RV, trailer, or equipment rentals? =
Yes. Fleetwire supports any rental fleet — cars, trucks, vans, RVs, campers, trailers, and equipment. The same shortcodes and availability search work for every listing type in your Fleetwire account.

= Do customers book directly on my website? =
Visitors browse listings and check availability on your WordPress pages, then complete their reservation through Fleetwire's secure hosted checkout with real-time inventory, payment processing, and order management.

== Screenshots ==

1. Browse and book inventory online.
2. Add listings easily with shortcodes.
3. Display rental availability.
4. Online payment and quote requests.

== Changelog ==
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

= 1.0.24 =
Security update for diagnostic log storage and release tooling. Existing upload logs migrate to private storage when writable. If migration fails, remove the old public log and configure FLEETWIRE_LOG_DIR; see Diagnostic Logs above.

= 1.0.22 =
Adds Fleetwire admin connection tools, listing picker, and host dashboard. No shortcode changes are required for existing supported embeds.

= 1.0.15 =
Verified compatibility with WordPress 6.8.1. No functionality changes.
Readme.txt improvements and metadata updates. No breaking changes.

= 1.0.12 =
Verified compatibility with WordPress 6.5. No functionality changes.

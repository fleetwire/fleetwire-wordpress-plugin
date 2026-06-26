=== Fleetwire Fleet Management ===
Contributors: fleetwire
Donate Link: https://coff.ee/fleetwire
Tags: rental, booking, vehicle rentals, fleet management, calendar
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 7.0.0
Stable tag: 1.0.22
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Embed Fleetwire listings, availability search, booking buttons, and host dashboard tools directly inside WordPress.


== Description ==

Fleetwire Fleet Management connects your WordPress site to your Fleetwire rental business so visitors can browse listings, check availability, and start bookings from your own pages.

Use the plugin settings page to connect your Fleetwire Company ID, verify the connection, load listings, and generate supported shortcodes without manually copying listing IDs. Hosts can also add a scoped Fleetwire WordPress integration token to view private dashboard data, including active listing counts, upcoming pickups, and upcoming returns.

## Key Features:

- **Fleetwire embeds:** Add all listings, availability search, single listing cards, booking buttons, listing descriptions, titles, prices, and images with shortcodes.
- **Listing picker:** Load Fleetwire listings in WordPress admin and generate shortcodes for the selected vehicle.
- **Connection status:** Verify that WordPress can reach the connected Fleetwire Company ID before publishing embeds.
- **Host dashboard:** View active listings, upcoming pickups, and upcoming returns from WordPress when a scoped Fleetwire integration token is configured.
- **Fleetwire checkout:** Send visitors into Fleetwire's hosted reservation flow with real-time inventory, availability, payment, and order management handled by Fleetwire.

## Getting Started:

1. **Install the plugin:** Download, upload, and activate the plugin on your WordPress site.
2. **Connect Fleetwire:** Paste your Fleetwire Company ID into the plugin settings and check the connection.
3. **Load listings:** Use the listing picker to choose a vehicle and generate a shortcode.
4. **Publish embeds:** Paste the generated shortcode into any page, post, or compatible builder block.
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

== Frequently Asked Questions ==

= Does this plugin require a Fleetwire subscription? =
Yes. Visit [fleetwire.io](https://fleetwire.io) to create an account or start a 14-day trial.

= Can I embed listings with a shortcode? =
Yes. Use `[fleetwire_list]` for all listings or use the listing picker in WordPress admin to generate shortcodes for a single listing card, booking button, description, title, price, or image.

== Screenshots ==

1. Browse and book inventory online.
2. Add listings easily with shortcodes.
3. Display rental availability.
4. Online payment and quote requests.

== Changelog ==
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

= 1.0.22 =
Adds Fleetwire admin connection tools, listing picker, and host dashboard. No shortcode changes are required for existing supported embeds.

= 1.0.15 =
Verified compatibility with WordPress 6.8.1. No functionality changes.
Readme.txt improvements and metadata updates. No breaking changes.

= 1.0.12 =
Verified compatibility with WordPress 6.5. No functionality changes.

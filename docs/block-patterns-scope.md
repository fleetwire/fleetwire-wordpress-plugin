# Block Patterns & Starter Site — Scope

Drafted 2026-10-20. Not started.

## Why

The plugin's job today is to embed listings using shortcodes. A host still has to design every page themselves, which
is where WordPress setups stall. On prod, 3 WordPress integration tokens exist and none has ever been used. This work
turns the plugin into a "rental website in one click" without building a theme:

1. **Native blocks** so hosts stop copying shortcodes.
2. **Block patterns** for the sections every rental site needs.
3. **A starter-pages importer** that builds a complete draft site from the host's real listings.

The goal is the "car rental template" story for wordpress.org search and the marketing site, without the support
burden of a theme. Every pattern uses core blocks plus Fleetwire blocks, so it works with any block theme (Twenty
Twenty-Five, Astra, Kadence, GeneratePress) and is styled by that theme.

## Constraints found in the code

- **The embed script renders once.** `fleetwire.js` (`resources/js/tenant/v2/tenant-app.ts` in fleetwire-core) mounts
  on `DOMContentLoaded` and never rescans. Blocks inserted in the editor won't render live, so phase 1 shows static
  previews in the editor (see Blocks). Live preview requires an app-side `mount(el)` hook (phase 4).
- **The client script only loads on the front end.** `Assets::enqueue_client_script` runs on `wp_enqueue_scripts`.
  Patterns need no editor-side script in phase 1.
- **Shortcodes are thin.** Each `Shortcode_Base::handle()` outputs an empty wrapper with `data-*` attributes. A block's
  `render_callback` can reuse the same classes, so blocks and shortcodes produce identical markup.
- **Ten shortcodes are dead.** Some subclass `Unsupported_Shortcode` and output nothing: cart variants, sidebar, sort,
  bar, categories, features, reviews. No block or pattern may use them.
- **Patterns need WordPress 5.5+; this plan needs 6.0+.** `Requires at least: 5.0` (`Config::MIN_WP_VERSION`) predates
  `register_block_pattern` (5.5) and block.json `render` (6.1). Raise the minimum to **6.1**; anything older is
  unsupported core anyway.
- **Listing IDs are tenant-specific.** Patterns are static content, so a vehicle-specific pattern can't ship a working
  `id`. Fleet-wide sections (list, search, availability) need no ID. Vehicle sections get their IDs from the importer
  or from a picker in the block's sidebar.
- **Release script ships everything not deleted.** `release.sh` strips `tests/` but not `docs/`. Add `rm -Rf docs`
  there before the next release so this file doesn't reach SVN.

## fleetwire.js changes (fleetwire-core — ship before plugin 1.1.0)

The plugin only outputs `<div class="fleetwire-…" data-…>` wrappers; `fleetwire.js` decides what actually renders.
Several attributes the plugin already emits are ignored today, which would break the patterns as designed:

| Gap | Where | Impact on this plan | Fix |
|---|---|---|---|
| `fleetwire_list` attributes ignored | `tenant-app.ts` mounts `ListingList` with no props; `TenantListingController@index` validates `limit` but only reads `perPage`; no tags/categories filter exists | "Fleet grid (6)" shows the whole fleet; Featured/filtered lists impossible | **Fixed 2026-10-20 (uncommitted):** `per`, `limit`, `showprice` honored, list sorted by host order. Listings have no tags/categories, so the block drops those attributes |
| Inactive listings shown | `TenantListingController@index` returns `$company->listings()` unfiltered (no active/paused/approved scope); `ListingList` renders all of them | Fleet grid and starter Fleet page show paused/unapproved/draft vehicles | **Fixed 2026-10-20 (uncommitted):** `Listing::publiclyListed()` on both GET and POST — active, approved, not paused, not archived |
| Gallery = single image | `fleetwire_listing_image_gallery` uses the same `fleetwire-listing-image` class as the single image | Vehicle spotlight gets one photo | **Fixed 2026-10-20 (uncommitted):** fleetwire.js renders `.fleetwire-listing-gallery`; plugin shortcode emits both classes |
| Availability = search | both shortcodes use `fleetwire-search` | Patterns 1 and 2 are identical | Either merge into one pattern (cheap) or give availability its own widget |
| Card `showprice` ignored | `mountListingCards` passes only `{id, listing}` | Price toggle on the card block does nothing | **Fixed 2026-10-20 (uncommitted)**, plus list cards no longer get remounted by `mountListingCards` |
| Mount once, no guard | `bootstrap()` runs on `DOMContentLoaded`; `mountWidget` appends a fresh div every call | Blocks in the editor stay blank; a naive rescan would double-mount | Phase 4: export `window.Fleetwire.mount(root)` that skips elements already marked `data-fw-mounted` |

The script is served live from `{slug}.fleetwire.io/tenant/v2/fleetwire.js`, so these fixes reach every plugin version
(and non-WordPress embeds) on app deploy — deploy the app first, then release the plugin. Honoring `limit`/`tags` is a
behavior change for any live page already passing those attributes; grep embed referrers or accept it (they asked for
it).

## Local test site

`fw-wordpress.test` (Herd, `~/Sites/fw-wordpress`) has this repo symlinked as the plugin and the API environment set
to local FrunkYea, so the front end loads `fleetwire.js`, the app bundle and the API from the local checkout
(`Config::get_local_asset_base_url()`, added 2026-10-20). Pages: **/fleetwire-playground/** (every shortcode and option,
rebuilt by `tests/playground/seed-playground.php`) and **/fleetwire-blocks-check/** (every block). The in-app browser
pane blocks the cross-origin API calls; use your own browser or headless Chrome.

**Found while testing:** list embeds stack one card per row. `.fw-listing-card { display: flex }` overrides the
legacy `.fleetwire-listing-card { display: inline-block; width: 33% }` grid on the same element, and
`UtilService.setWidth()` stamped inline widths. **Fixed and pushed 2026-10-20** (fleetwire 216a4d10c + bundle c1e006dfb):
lists render in a `.fw-listing-grid` CSS grid.

## Phase 1 — Blocks

**Built 2026-10-20 (uncommitted).** Six blocks — availability was dropped (same widget as search). `includes/Blocks.php`,
`includes/Listings.php` (shared fetch + 12h transient, also used by the admin picker), `blocks/*/block.json`,
`assets/blocks/editor.{js,css}`, REST route `fleetwire/v1/listings` (`edit_posts`). Tests: `tests/blocks.php`.
Minimum WordPress raised to 6.1.


The blocks are thin wrappers over existing shortcodes, written in plain JS (`wp.element.createElement`) with
`block.json`, so no npm or build step is added to the plugin.

| Block | Wraps | Attributes | Needs listing ID |
|---|---|---|---|
| `fleetwire/listings` | `fleetwire_list` | tags, categories, per, limit, showPrice | no |
| `fleetwire/search` | `fleetwire_search` | — | no |
| `fleetwire/availability` | `fleetwire_availability` | — | no |
| `fleetwire/listing-card` | `fleetwire_listing_card` | id, showPrice | yes |
| `fleetwire/book-button` | `fleetwire_button` | id, customTitle, showPrice | yes |
| `fleetwire/listing-gallery` | `fleetwire_listing_image_gallery` | id | yes |
| `fleetwire/listing-details` | title + price + seats + doors + description | id | yes |

- **Front-end render:** a `render_callback` that calls the matching `Shortcode_Base` instance, giving the same markup
  as the shortcode.
- **Editor:** a static skeleton preview (grey cards, "Your Fleetwire vehicles appear here on the live site") so
  patterns look intentional in the inserter, not like raw shortcode text.
- **Inspector picker:** blocks that need an ID get a listing dropdown filled from the existing
  `wp_ajax_fleetwire_fetch_listings` endpoint (cache the result in a 12-hour transient). A block with no ID shows "Pick a
  vehicle" in the editor and renders nothing on the front end.
- **Not connected:** with no Company ID, the editor preview links to the plugin settings page.
- **Category:** register a block category named "Fleetwire".

## Phase 2 — Patterns

Register a pattern category named **"Fleetwire – Car Rental"**. Each pattern is a PHP file in `patterns/`, registered
with `register_block_pattern`, using core blocks (group, cover, columns, heading, paragraph, buttons, details) plus the
Fleetwire blocks. Copy is placeholder text written for car rental, editable in place.

**Fleet-wide (work on insert, no ID):**

1. **Hero + search** — cover image, headline, subhead, `fleetwire/search`.
2. **Hero + availability** — the same layout with date-first availability search.
3. **Fleet grid** — heading, intro, `fleetwire/listings` (limit 6), "See all vehicles" button.
4. **How it works** — three columns: Choose a car → Book and sign online → Pick up or get delivered.
5. **Why book direct** — trust and benefit columns: no marketplace fees, direct support, local pickup.
6. **FAQ** — core `details` blocks covering deposit, age, insurance, mileage, delivery, cancellation.
7. **Requirements strip** — license, age, deposit, insurance in icon columns.
8. **CTA band** — a "Ready to roll?" banner with a button to the fleet page.
9. **Contact + hours** — two columns: address, phone, hours | map placeholder.

**Vehicle-specific (shown in the inserter only after listings have loaded once):**

10. **Vehicle spotlight** — gallery left; title, price, seats, doors, description and book button right.
11. **Featured three** — three `fleetwire/listing-card` blocks side by side.

Patterns 10 and 11 are registered at `init` from the cached listings transient, filled with the host's first active
listings. With no cache, they are left out rather than shipped with broken IDs.

Images: ship 3–4 royalty-free vehicle and road photos in `assets/patterns/` (CC0 only; under 150KB each, since the
plugin zip size matters for wordpress.org). Hosts replace them.

## Phase 3 — Starter pages importer

Add a "Build my rental site" card to the plugin admin page, visible once the connection check passes.

- **Creates draft pages** (never published, never overwrites):
  - **Home** — patterns 1, 3, 4, 5, 8.
  - **Our Fleet** — fleet grid with no limit.
  - **One page per active listing** (up to 12, as a checkbox list from the picker) — the vehicle spotlight pattern.
  - **FAQ** — patterns 6 and 7.
  - **Contact** — pattern 9.
- **Optional:** set Home as the static front page and add the pages to the primary navigation (block themes: append to
  the `wp_navigation` post; classic themes: create a menu). Each is a checkbox, unchecked by default.
- **Records what it created** in an option, so a second run offers "Update vehicle pages" (adds pages for new listings)
  instead of making duplicates.
- **Ends on a summary:** links to each draft page and a "Preview home page" button.

## Phase 4 — Later / out of scope for now

- **Live editor preview:** fleetwire-core exposes `window.Fleetwire.mount(el)` (or makes `tenant-app.ts` rescan on a
  custom event). The plugin then loads `fleetwire.js` through `enqueue_block_assets` so blocks render real listings in
  the editor iframe.
- **Elementor widgets** wrapping the same shortcodes, plus one Elementor kit, if phase 1–3 installs justify it.
- **Submitting to the wordpress.org Patterns Directory** is not possible: it accepts core blocks only.
- **Bringing back the dead shortcodes** (cart, categories, reviews) needs v2 renderers in fleetwire-core first.

## wordpress.org listing changes (ship with phase 2)

- **Readme:** lead with "Build a car rental website in minutes"; add a Patterns section and a "Build my rental site"
  step to Getting Started.
- **Screenshots:** pattern inserter showing the Fleetwire category, the starter Home page on Twenty Twenty-Five, a
  vehicle page, and the importer card.
- **Tags** (max 5 count for search): `car rental`, `rental booking`, `booking calendar`, `vehicle rental`,
  `rental website`.
- **Reviews:** after the release, ask active WordPress customers (676 Rental, Deshun) for one.

## Testing

- Extend `tests/security-regressions.py` (or add a PHP test file in the same style):
  - every block's `render_callback` output matches its shortcode output for the same attributes;
  - attributes are escaped (`id="x\" onload=…"`);
  - the importer requires `manage_options` + nonce;
  - a second importer run creates no duplicates.
- Manual: insert every pattern on Twenty Twenty-Five and on one classic theme (Astra); check the editor preview, the
  front-end render against a real tenant, and mobile width.

## Rough size

| Phase | Effort |
|---|---|
| fleetwire.js + products endpoint fixes | 0.5–1 day |
| 1 — Blocks | 1–1.5 days |
| 2 — Patterns + images + readme | 1–1.5 days |
| 3 — Importer | 1 day |
| 4 — Live preview (app + plugin) | 1 day, later |

Ship phases 1–2 as 1.1.0 and phase 3 as 1.2.0, so wordpress.org gets a visible update twice.

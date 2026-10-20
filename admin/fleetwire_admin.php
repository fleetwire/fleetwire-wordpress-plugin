<div class="wrap fw-admin-wrap">
    <?php
    $fleetwire_company_id = \Fleetwire\Config::get_company_id();
    $fleetwire_store_url = $fleetwire_company_id ? \Fleetwire\Config::get_company_store_url( $fleetwire_company_id ) : '';
    $fleetwire_api_base_url = \Fleetwire\Config::get_api_base_url( $fleetwire_company_id );
    $fleetwire_local_company_id = \Fleetwire\Config::get_option( 'local_company_id', '' );
    $fleetwire_local_api_base_url = \Fleetwire\Config::get_option( 'api_base_url', '' );
    $fleetwire_api_environment = \Fleetwire\Config::get_api_environment();
    $fleetwire_is_local_wordpress = \Fleetwire\Config::is_local_wordpress_environment();
    $fleetwire_integration_token = \Fleetwire\Config::get_integration_token();
    $fleetwire_shortcodes = [
        [
            'title' => __( 'All products', 'fleetwire' ),
            'code' => '[fleetwire_list]',
            'description' => __( 'Display all rental listings from your Fleetwire store.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Availability search', 'fleetwire' ),
            'code' => '[fleetwire_availability]',
            'description' => __( 'Show the date and time search widget for fleet availability.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Single product card', 'fleetwire' ),
            'code' => '[fleetwire_listing_card id="l_ABCDEFG"]',
            'description' => __( 'Display a full card for one rental listing.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Booking button', 'fleetwire' ),
            'code' => '[fleetwire_button id="l_ABCDEFG" custom-title="Book now" show-price=1]',
            'description' => __( 'Display a Fleetwire booking button for one listing.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Description', 'fleetwire' ),
            'code' => '[fleetwire_listing_description id="l_ABCDEFG" length=100]',
            'description' => __( 'Show a listing description with an optional character limit.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Listing title', 'fleetwire' ),
            'code' => '[fleetwire_listing_title id="l_ABCDEFG"]',
            'description' => __( 'Show only the listing title.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Listing price', 'fleetwire' ),
            'code' => '[fleetwire_listing_price id="l_ABCDEFG"]',
            'description' => __( 'Show only the listing price.', 'fleetwire' ),
        ],
        [
            'title' => __( 'Listing image', 'fleetwire' ),
            'code' => '[fleetwire_listing_image id="l_ABCDEFG"]',
            'description' => __( 'Show the primary listing image.', 'fleetwire' ),
        ],
    ];
    ?>
    <div class="card fleetwire-card fw-admin-header">
        <div class="fw-admin-pull-left">
            <img src="<?php echo esc_url( plugins_url( '../assets/FleetWire_Logo-B.png', __FILE__ ) ); ?>"
                 alt="Fleetwire logo"/>
        </div>
        <div class="fw-admin-pull-right">
            <a href="https://help.fleetwire.io/en/" target="_blank" rel="noopener" class="button fw-admin-help">
                <span class="dashicons dashicons-sos"></span>Help
            </a>
        </div>
    </div>

    <div class="card fleetwire-card fleetwire-form-section">
        <h2><?php esc_html_e( "1. Connect to your Fleetwire account", 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle">
            <?php 
            printf(
                esc_html__( "Don't have a Fleetwire account? %s", 'fleetwire' ),
                '<a href="https://tenant.fleetwire.io/register" target="_blank" rel="noopener">' .
                esc_html__( 'Get started for free', 'fleetwire' ) . '</a>'
            );
            ?>
        </p>
        <hr/>
        <form id="fleetwire_form" name="fleetwire_form">
			<?php wp_nonce_field( 'fleetwire_company_name_form_process', 'fleetwire_nonce' ); ?>
            <input type="hidden" name="action" value="fleetwire_company_name_form_process"/>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="fleetwire_company_name"><?php esc_html_e( "Company ID", 'fleetwire' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="fleetwire_company_name" id="fleetwire_company_name"
                               value="<?php echo esc_attr( \Fleetwire\Config::get_company_id() ); ?>" size="20"
                               class="regular-text fleetwire-company-uuid"
                               placeholder="<?php esc_attr_e('e.g. d686ca64-51bb-482f-af15-bbb5f05b692d', 'fleetwire'); ?>"
                               title="<?php esc_attr_e('Enter your Fleetwire Company ID', 'fleetwire'); ?>">
                        <p class="description">
                            <?php 
                            printf(
                                esc_html__( "Find your %s in your Fleetwire account under %s", 'fleetwire' ),
                                '<b>' . esc_html__( 'Company ID', 'fleetwire' ) . '</b>',
                                '<b>' . esc_html__( 'Settings > Online reservations > Installation > WordPress plugin', 'fleetwire' ) . '</b>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <?php if ( $fleetwire_is_local_wordpress ) : ?>
                <tr class="fleetwire-api-environment-row">
                    <th scope="row">
                        <?php esc_html_e( "Fleetwire API Target", 'fleetwire' ); ?>
                    </th>
                    <td>
                        <fieldset class="fleetwire-api-environment-options">
                            <label>
                                <input type="radio"
                                       name="fleetwire_api_environment"
                                       value="live"
                                       class="fleetwire-api-environment"
                                    <?php checked( $fleetwire_api_environment, 'live' ); ?>>
                                <?php esc_html_e( 'Live', 'fleetwire' ); ?>
                            </label>
                            <label>
                                <input type="radio"
                                       name="fleetwire_api_environment"
                                       value="local"
                                       class="fleetwire-api-environment"
                                    <?php checked( $fleetwire_api_environment, 'local' ); ?>>
                                <?php esc_html_e( 'Local', 'fleetwire' ); ?>
                            </label>
                        </fieldset>
                        <p class="description">
                            <?php esc_html_e( 'Use Live for production Fleetwire data or Local when testing against your local Fleetwire app.', 'fleetwire' ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="fleetwire-local-api-url-row">
                    <th scope="row">
                        <label for="fleetwire_local_company_id"><?php esc_html_e( "Local Company ID", 'fleetwire' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="fleetwire_local_company_id" id="fleetwire_local_company_id"
                               value="<?php echo esc_attr( $fleetwire_local_company_id ); ?>" size="40"
                               class="regular-text fleetwire-local-company-uuid"
                               placeholder="<?php esc_attr_e('e.g. ac98952c-030b-42c8-909a-3abee97caf6a', 'fleetwire'); ?>"
                               title="<?php esc_attr_e('Use the Company ID from your local Fleetwire database.', 'fleetwire'); ?>">
                        <p class="description">
                            <?php esc_html_e( 'Only used when the API target is Local. Leave Live Company ID set to the production Fleetwire UUID.', 'fleetwire' ); ?>
                        </p>
                    </td>
                </tr>
                <tr class="fleetwire-local-api-url-row">
                    <th scope="row">
                        <label for="fleetwire_api_base_url"><?php esc_html_e( "Local API URL", 'fleetwire' ); ?></label>
                    </th>
                    <td>
                        <input type="url" name="fleetwire_api_base_url" id="fleetwire_api_base_url"
                               value="<?php echo esc_attr( $fleetwire_local_api_base_url ); ?>" size="40"
                               class="regular-text fleetwire-api-base-url"
                               placeholder="<?php esc_attr_e('http://your-company.fleetwire.test', 'fleetwire'); ?>"
                               title="<?php esc_attr_e('Use the local Fleetwire URL that serves the API for this Company ID.', 'fleetwire'); ?>">
                        <p class="description">
                            <?php esc_html_e( 'Only used when the API target is Local. Live always uses the Fleetwire production UUID subdomain.', 'fleetwire' ); ?>
                        </p>
                    </td>
                </tr>
                <?php else : ?>
                    <input type="hidden" name="fleetwire_api_environment" value="live">
                <?php endif; ?>
                <tr>
                    <th scope="row">
                        <label for="fleetwire_integration_token"><?php esc_html_e( "Integration Token", 'fleetwire' ); ?></label>
                    </th>
                    <td>
                        <input type="password" name="fleetwire_integration_token" id="fleetwire_integration_token"
                               value="<?php echo esc_attr( $fleetwire_integration_token ); ?>" size="40"
                               class="regular-text fleetwire-integration-token"
                               placeholder="<?php esc_attr_e('fw_wp_...', 'fleetwire'); ?>"
                               autocomplete="off"
                               title="<?php esc_attr_e('Scoped Fleetwire token used only for private WordPress dashboard data.', 'fleetwire'); ?>">
                        <p class="description">
                            <?php esc_html_e( 'This token is stored in WordPress and sent server-side to Fleetwire for private host data such as upcoming pickups.', 'fleetwire' ); ?>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="Submit" value="<?php esc_attr_e( 'Update Options', 'fleetwire' ) ?>"
                       class="button button-primary fleetwire-update-company-uuid"/>
            </p>
        </form>
        <div class="fleetwire-connection-panel"
             data-store-url="<?php echo esc_url( $fleetwire_store_url ); ?>"
             data-company-id="<?php echo esc_attr( $fleetwire_company_id ); ?>">
            <div class="fleetwire-connection-copy">
                <h3><?php esc_html_e( 'Connection status', 'fleetwire' ); ?></h3>
                <p class="fleetwire-connection-message">
                    <?php echo $fleetwire_company_id ? esc_html__( 'Checking Fleetwire connection...', 'fleetwire' ) : esc_html__( 'Enter your Company ID to connect this site.', 'fleetwire' ); ?>
                </p>
            </div>
            <div class="fleetwire-connection-actions">
                <button type="button" class="button button-secondary fleetwire-check-connection">
                    <span class="dashicons dashicons-update"></span><?php esc_html_e( 'Check connection', 'fleetwire' ); ?>
                </button>
                <a href="<?php echo esc_url( $fleetwire_store_url ?: '#' ); ?>"
                   target="_blank"
                   rel="noopener"
                   class="button button-secondary fleetwire-preview-store<?php echo $fleetwire_store_url ? '' : ' disabled'; ?>">
                    <span class="dashicons dashicons-external"></span><?php esc_html_e( 'Preview store', 'fleetwire' ); ?>
                </a>
            </div>
        </div>
    </div>

    <?php
    $fleetwire_builder = \Fleetwire\Fleetwire::get_instance()->site_builder;
    $fleetwire_builder_state = $fleetwire_builder->state();
    $fleetwire_built_pages = array_filter( array_map( [ $fleetwire_builder, 'existing_page' ], $fleetwire_builder_state['pages'] ) );
    $fleetwire_vehicle_pages = array_filter( array_map( [ $fleetwire_builder, 'existing_page' ], $fleetwire_builder_state['vehicles'] ) );
    $fleetwire_bookable = \Fleetwire\Config::is_configured() ? \Fleetwire\Listings::bookable() : [];
    $fleetwire_checked = 0;
    $fleetwire_plan = $fleetwire_builder->plan();
    $fleetwire_default_pages = $fleetwire_builder->default_page_keys();
    $fleetwire_drafts = $fleetwire_builder->draft_count();
    ?>
    <div class="card fleetwire-card fleetwire-site-builder">
        <h2><?php esc_html_e( '2. Build your rental website', 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle">
            <?php esc_html_e( 'Create draft pages from your fleet: Home, Our Fleet, FAQ, Contact and a page for each vehicle you pick. Nothing is published or overwritten — review the drafts, edit the text, then publish.', 'fleetwire' ); ?>
        </p>
        <hr/>
        <?php if ( ! \Fleetwire\Config::is_configured() ) : ?>
            <p><?php esc_html_e( 'Connect your Fleetwire account above first.', 'fleetwire' ); ?></p>
        <?php else : ?>
            <?php if ( $fleetwire_built_pages ) : ?>
                <p class="fleetwire-site-builder-existing">
                    <?php esc_html_e( 'Pages built earlier:', 'fleetwire' ); ?>
                    <?php foreach ( array_merge( $fleetwire_built_pages, $fleetwire_vehicle_pages ) as $fleetwire_page ) : ?>
                        <a href="<?php echo esc_url( get_edit_post_link( $fleetwire_page->ID ) ); ?>"><?php echo esc_html( get_the_title( $fleetwire_page ) ); ?></a>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>

            <h3><?php esc_html_e( 'Pages', 'fleetwire' ); ?></h3>
            <ul class="fleetwire-site-builder-pages">
                <?php foreach ( [ 'home', 'fleet', 'faq', 'contact' ] as $fleetwire_key ) :
                    $fleetwire_core = $fleetwire_plan[ $fleetwire_key ];
                    $fleetwire_taken = $fleetwire_core['taken_by'];
                    ?>
                    <li>
                        <label>
                            <input type="checkbox" name="fleetwire_build_pages[]"
                                   value="<?php echo esc_attr( $fleetwire_key ); ?>"
                                <?php checked( $fleetwire_core['ours'] || in_array( $fleetwire_key, $fleetwire_default_pages, true ) ); ?>
                                <?php disabled( (bool) $fleetwire_core['ours'] ); ?>/>
                            <?php echo esc_html( $fleetwire_core['title'] ); ?>
                        </label>
                        <?php if ( $fleetwire_core['ours'] ) : ?>
                            <span class="description"><?php esc_html_e( '(built)', 'fleetwire' ); ?></span>
                        <?php elseif ( $fleetwire_taken && $fleetwire_key === 'home' ) : ?>
                            <span class="description">
                                <?php
                                echo esc_html(
                                    (int) get_option( 'page_on_front' ) === $fleetwire_taken->ID
                                        /* translators: %s: page title */
                                        ? sprintf( __( 'Your current front page, "%s", stays live until you publish the new Home.', 'fleetwire' ), get_the_title( $fleetwire_taken ) )
                                        /* translators: %s: page title */
                                        : sprintf( __( 'You already have a "%s" page. It stays as it is; the new Home gets its own address.', 'fleetwire' ), get_the_title( $fleetwire_taken ) )
                                );
                                ?>
                            </span>
                        <?php elseif ( $fleetwire_taken ) : ?>
                            <span class="description">
                                <?php
                                /* translators: 1: page title, 2: page path such as /faq/ */
                                echo esc_html( sprintf( __( 'You already have "%1$s" at %2$s, so it is used instead. Tick to create ours as well.', 'fleetwire' ), get_the_title( $fleetwire_taken ), wp_make_link_relative( get_permalink( $fleetwire_taken ) ) ) );
                                ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h3><?php esc_html_e( 'Vehicle pages', 'fleetwire' ); ?></h3>
            <?php if ( empty( $fleetwire_bookable ) ) : ?>
                <p><?php esc_html_e( 'Your vehicles could not be loaded. Check the connection above, then reload this page.', 'fleetwire' ); ?></p>
            <?php else : ?>
                <p class="description">
                    <?php
                    /* translators: %d: maximum number of vehicle pages */
                    echo esc_html( sprintf( __( 'Pick up to %d vehicles per run.', 'fleetwire' ), \Fleetwire\SiteBuilder::MAX_VEHICLE_PAGES ) );
                    ?>
                </p>
                <ul class="fleetwire-site-builder-vehicles">
                    <?php foreach ( $fleetwire_bookable as $fleetwire_listing ) :
                        $fleetwire_has_page = isset( $fleetwire_vehicle_pages[ $fleetwire_listing['id'] ] );
                        $fleetwire_check = ! $fleetwire_has_page && $fleetwire_checked < \Fleetwire\SiteBuilder::MAX_VEHICLE_PAGES;
                        $fleetwire_checked += $fleetwire_check ? 1 : 0;
                        ?>
                        <li>
                            <label>
                                <input type="checkbox" name="fleetwire_build_listings[]"
                                       value="<?php echo esc_attr( $fleetwire_listing['id'] ); ?>"
                                    <?php checked( $fleetwire_check ); ?>
                                    <?php disabled( $fleetwire_has_page ); ?>/>
                                <?php echo esc_html( $fleetwire_listing['name'] ); ?>
                                <?php if ( $fleetwire_has_page ) : ?>
                                    <span class="description"><?php esc_html_e( '(page exists)', 'fleetwire' ); ?></span>
                                <?php endif; ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3><?php esc_html_e( 'Options', 'fleetwire' ); ?></h3>
            <p>
                <label>
                    <input type="checkbox" class="fleetwire-build-front-page"/>
                    <?php esc_html_e( 'Use Home as my front page once I publish it', 'fleetwire' ); ?>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" class="fleetwire-build-menu"/>
                    <?php esc_html_e( 'Add Home, Our Fleet, FAQ and Contact to my site menu (links appear as each page is published)', 'fleetwire' ); ?>
                </label>
            </p>
            <p class="submit">
                <button type="button" class="button button-primary fleetwire-build-site">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php echo $fleetwire_built_pages ? esc_html__( 'Update vehicle pages', 'fleetwire' ) : esc_html__( 'Build my rental site', 'fleetwire' ); ?>
                </button>
            </p>
            <div class="fleetwire-site-builder-result" aria-live="polite"></div>
            <div class="fleetwire-site-builder-publish"<?php echo $fleetwire_drafts ? '' : ' hidden'; ?>>
                <hr/>
                <p>
                    <?php esc_html_e( 'Happy with the drafts? Publish them all at once — or publish pages one by one from the editor.', 'fleetwire' ); ?>
                </p>
                <button type="button" class="button button-secondary fleetwire-publish-site"
                        data-count="<?php echo esc_attr( $fleetwire_drafts ); ?>">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e( 'Publish all draft pages', 'fleetwire' ); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="card fleetwire-card fleetwire-dashboard-section">
        <div class="fleetwire-dashboard-header">
            <div>
                <h2><?php esc_html_e( "3. Host dashboard", 'fleetwire' ); ?></h2>
                <p class="fw-admin-subtitle">
                    <?php esc_html_e( 'Review private host data from Fleetwire without leaving WordPress. This requires the scoped WordPress integration token.', 'fleetwire' ); ?>
                </p>
            </div>
            <button type="button" class="button button-secondary fleetwire-load-dashboard">
                <span class="dashicons dashicons-update"></span><?php esc_html_e( 'Load dashboard', 'fleetwire' ); ?>
            </button>
        </div>
        <hr/>
        <div class="fleetwire-dashboard-summary">
            <div class="fleetwire-dashboard-metric">
                <span><?php esc_html_e( 'Active listings', 'fleetwire' ); ?></span>
                <strong data-fleetwire-dashboard-field="active_listings">-</strong>
            </div>
            <div class="fleetwire-dashboard-metric">
                <span><?php esc_html_e( 'Upcoming pickups', 'fleetwire' ); ?></span>
                <strong data-fleetwire-dashboard-field="upcoming_pickups_count">-</strong>
            </div>
            <div class="fleetwire-dashboard-metric">
                <span><?php esc_html_e( 'Upcoming returns', 'fleetwire' ); ?></span>
                <strong data-fleetwire-dashboard-field="upcoming_returns_count">-</strong>
            </div>
        </div>
        <div class="fleetwire-dashboard-lists">
            <div>
                <h3><?php esc_html_e( 'Next pickups', 'fleetwire' ); ?></h3>
                <ul class="fleetwire-dashboard-list fleetwire-dashboard-pickups">
                    <li><?php esc_html_e( 'Load dashboard data to view upcoming pickups.', 'fleetwire' ); ?></li>
                </ul>
            </div>
            <div>
                <h3><?php esc_html_e( 'Next returns', 'fleetwire' ); ?></h3>
                <ul class="fleetwire-dashboard-list fleetwire-dashboard-returns">
                    <li><?php esc_html_e( 'Load dashboard data to view upcoming returns.', 'fleetwire' ); ?></li>
                </ul>
            </div>
        </div>
        <p class="fleetwire-dashboard-message"></p>
    </div>

    <div class="card fleetwire-card fleetwire-shortcode-section">
        <h2><?php esc_html_e( "4. Add your products", 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle">
            <?php esc_html_e( 'Copy a supported shortcode into any page or post. The ID attribute is your Fleetwire listing ID, such as l_ABCDEFG.', 'fleetwire' ); ?>
        </p>
        <hr/>
        <div class="fleetwire-listing-picker"
             data-empty-message="<?php esc_attr_e( 'No listings loaded yet.', 'fleetwire' ); ?>">
            <div class="fleetwire-listing-picker-header">
                <div>
                    <h3><?php esc_html_e( 'Listing picker', 'fleetwire' ); ?></h3>
                    <p><?php esc_html_e( 'Load your active Fleetwire listings, choose one, and generate a shortcode without manually copying listing IDs.', 'fleetwire' ); ?></p>
                </div>
                <button type="button" class="button button-secondary fleetwire-load-listings">
                    <span class="dashicons dashicons-update"></span><?php esc_html_e( 'Load listings', 'fleetwire' ); ?>
                </button>
            </div>
            <div class="fleetwire-listing-picker-body">
                <div class="fleetwire-picker-field">
                    <label for="fleetwire_listing_filter"><?php esc_html_e( 'Find listing', 'fleetwire' ); ?></label>
                    <input type="search"
                           id="fleetwire_listing_filter"
                           class="fleetwire-listing-filter"
                           placeholder="<?php esc_attr_e( 'Search by listing name or ID', 'fleetwire' ); ?>">
                </div>
                <div class="fleetwire-picker-field">
                    <label for="fleetwire_listing_select"><?php esc_html_e( 'Listing', 'fleetwire' ); ?></label>
                    <select id="fleetwire_listing_select" class="fleetwire-listing-select" disabled>
                        <option value=""><?php esc_html_e( 'Load listings to choose one', 'fleetwire' ); ?></option>
                    </select>
                </div>
                <div class="fleetwire-picker-field">
                    <label for="fleetwire_embed_type"><?php esc_html_e( 'Embed type', 'fleetwire' ); ?></label>
                    <select id="fleetwire_embed_type" class="fleetwire-embed-type">
                        <option value="card"><?php esc_html_e( 'Product card', 'fleetwire' ); ?></option>
                        <option value="button"><?php esc_html_e( 'Booking button', 'fleetwire' ); ?></option>
                        <option value="description"><?php esc_html_e( 'Description', 'fleetwire' ); ?></option>
                        <option value="title"><?php esc_html_e( 'Title', 'fleetwire' ); ?></option>
                        <option value="price"><?php esc_html_e( 'Price', 'fleetwire' ); ?></option>
                        <option value="image"><?php esc_html_e( 'Image', 'fleetwire' ); ?></option>
                    </select>
                </div>
                <div class="fleetwire-picker-field fleetwire-button-title-field">
                    <label for="fleetwire_button_title"><?php esc_html_e( 'Button text', 'fleetwire' ); ?></label>
                    <input type="text"
                           id="fleetwire_button_title"
                           class="fleetwire-button-title"
                           value="<?php esc_attr_e( 'Book now', 'fleetwire' ); ?>">
                </div>
                <div class="fleetwire-picker-field fleetwire-description-length-field">
                    <label for="fleetwire_description_length"><?php esc_html_e( 'Description length', 'fleetwire' ); ?></label>
                    <input type="number"
                           id="fleetwire_description_length"
                           class="fleetwire-description-length"
                           min="0"
                           step="1"
                           value="100">
                </div>
            </div>
            <div class="fleetwire-generated-shortcode">
                <div>
                    <label><?php esc_html_e( 'Generated shortcode', 'fleetwire' ); ?></label>
                    <code class="fleetwire-generated-shortcode-code"><?php esc_html_e( 'Select a listing to generate a shortcode.', 'fleetwire' ); ?></code>
                </div>
                <button type="button" class="button button-secondary fleetwire-copy-generated-shortcode" disabled>
                    <?php esc_html_e( 'Copy', 'fleetwire' ); ?>
                </button>
            </div>
            <p class="fleetwire-listing-picker-message"></p>
        </div>
        <hr/>
        <div class="fleetwire-shortcode-grid">
            <?php foreach ( $fleetwire_shortcodes as $fleetwire_shortcode ) : ?>
                <div class="fleetwire-shortcode-card">
                    <h3><?php echo esc_html( $fleetwire_shortcode['title'] ); ?></h3>
                    <p><?php echo esc_html( $fleetwire_shortcode['description'] ); ?></p>
                    <div class="fleetwire-shortcode-copy-row">
                        <code><?php echo esc_html( $fleetwire_shortcode['code'] ); ?></code>
                        <button type="button"
                                class="button button-secondary fleetwire-copy-shortcode"
                                data-shortcode="<?php echo esc_attr( $fleetwire_shortcode['code'] ); ?>">
                            <?php esc_html_e( 'Copy', 'fleetwire' ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="fleetwire-mt-2">
            <a href="https://help.fleetwire.io/en/article/how-to-embed-listings-on-your-wordpress-website-u975ix/?bust=1657639589869"
               target="_blank" rel="noopener" class="button button-secondary">
                <span class="dashicons dashicons-external"></span> <?php esc_html_e( "More embed options", 'fleetwire' ); ?>
            </a>
        </div>
    </div>

    <div class="card fleetwire-card">
        <h2><?php esc_html_e( "5. Customize your online store", 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle"><?php esc_html_e( "Configure branding, rental periods, opening hours, online payments and more.", 'fleetwire' ); ?></p>
        <hr/>
        <a href="https://tenant.fleetwire.io/settings/online-reservations" target="_blank"
           rel="noopener" class="button button-primary">
            <span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e( "Customize online store", 'fleetwire' ); ?>
        </a>
    </div>
</div>

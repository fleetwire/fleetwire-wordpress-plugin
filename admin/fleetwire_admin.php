<div class="wrap fw-admin-wrap">
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
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="Submit" value="<?php esc_attr_e( 'Update Options', 'fleetwire' ) ?>"
                       class="button button-primary fleetwire-update-company-uuid"/>
            </p>
        </form>
    </div>

    <div class="card fleetwire-card fleetwire-shortcode-section">
        <h2><?php esc_html_e( "2. Add your products", 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle">
            <?php 
            printf(
                esc_html__( "To get started, simply %s and %s this shortcode to any %s or %s. The ID attribute is your listing ID. ex. l_ABCDEFG", 'fleetwire' ),
                '<b>' . esc_html__( 'copy', 'fleetwire' ) . '</b>',
                '<b>' . esc_html__( 'paste', 'fleetwire' ) . '</b>',
                '<b>' . esc_html__( 'Page', 'fleetwire' ) . '</b>',
                '<b>' . esc_html__( 'Post', 'fleetwire' ) . '</b>'
            );
            ?>
        </p>
        <hr/>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed all products", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_list]</code>
                    <p class="description"><?php esc_html_e( 'Displays all your rental listings', 'fleetwire' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed fleet availability", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_availability]</code>
                    <p class="description"><?php esc_html_e( 'Shows availability calendar and booking form', 'fleetwire' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed a single product", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_listing_card id="l_ABCDEFG" showprice=1]</code>
                    <p class="description"><?php esc_html_e( 'Options: showprice (1 or 0)', 'fleetwire' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed a single product's description", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_listing_description id="l_ABCDEFG" length=100]</code>
                    <p class="description"><?php esc_html_e( 'Options: length (character limit)', 'fleetwire' ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="fleetwire-mt-2">
            <a href="https://help.fleetwire.io/en/article/how-to-embed-listings-on-your-wordpress-website-u975ix/?bust=1657639589869"
               target="_blank" rel="noopener" class="button button-secondary">
                <span class="dashicons dashicons-external"></span> <?php esc_html_e( "More embed options", 'fleetwire' ); ?>
            </a>
        </div>
    </div>

    <div class="card fleetwire-card">
        <h2><?php esc_html_e( "3. Customize your online store", 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle"><?php esc_html_e( "Configure branding, rental periods, opening hours, online payments and more.", 'fleetwire' ); ?></p>
        <hr/>
        <a href="https://tenant.fleetwire.io/settings/online-reservations" target="_blank"
           rel="noopener" class="button button-primary">
            <span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e( "Customize online store", 'fleetwire' ); ?>
        </a>
    </div>
</div>

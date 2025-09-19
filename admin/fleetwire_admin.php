<div class="wrap fw-admin-wrap">
    <div class="card fleetwire-card fw-admin-header" style="box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 2rem;">
        <div class="fw-admin-pull-left">
            <img style="max-height: 55px"
                 src="<?php echo esc_url( plugins_url( '../assets/FleetWire_Logo-B.png', __FILE__ ) ); ?>"
                 alt="Fleetwire logo"/>
        </div>
        <div class="fw-admin-pull-right">
            <a href="https://help.fleetwire.io/en/" target="_blank" rel="noopener" class="button fw-admin-help">
                <span class="dashicons dashicons-sos" style="vertical-align: middle; margin-right: 4px; font-size: 16px"></span>Help
            </a>
        </div>
    </div>

    <div class="card fleetwire-card" style="box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 2rem;">
        <h2><?php esc_html_e( "1. Connect to your Fleetwire account" ); ?></h2>
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
                               value="<?php echo esc_attr( get_option( 'fleetwire_company_name' ) ); ?>" size="20"
                               class="regular-text fleetwire-company-uuid"
                               placeholder="<?php esc_attr_e('e.g. d686ca64-51bb-482f-af15-bbb5f05b692d'); ?>">
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
        <div class="updated fleetwire-d-none"><p><strong><?php esc_html_e( 'Options saved.', 'fleetwire' ); ?></strong></p></div>
    </div>

    <div class="card fleetwire-card" style="box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 2rem;">
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
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed fleet availability", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_availability]</code>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed a single product", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_listing_card id="" showprice=1]</code>
                    <p><?php esc_html_e( 'Options: showprice', 'fleetwire' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( "Embed a single product's description", 'fleetwire' ); ?></label>
                </th>
                <td>
                    <code>[fleetwire_listing_description id=""]</code>
                    <p><?php esc_html_e( 'Options: length=100', 'fleetwire' ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <a href="https://help.fleetwire.io/en/article/how-to-embed-listings-on-your-wordpress-website-u975ix/?bust=1657639589869"
           target="_blank" rel="noopener">
			<?php esc_html_e( "More embed options", 'fleetwire' ); ?><span class="dashicons dashicons-external" style="font-size: 14px; text-decoration: none"></span>
        </a>
    </div>

    <div class="card fleetwire-card" style="box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 2rem;">
        <h2><?php esc_html_e( "3. Customize your online store", 'fleetwire' ); ?></h2>
        <p class="fw-admin-subtitle"><?php esc_html_e( "Configure branding, rental periods, opening hours, online payments and more.", 'fleetwire' ); ?></p>
        <hr/>
        <a href="https://tenant.fleetwire.io/settings/online-reservations" target="_blank"
           rel="noopener">
            <span class="dashicons dashicons-admin-customizer" style="font-size: 14px; text-decoration: none"></span> <?php esc_html_e( "Customize online store", 'fleetwire' ); ?>
        </a>
    </div>
</div>

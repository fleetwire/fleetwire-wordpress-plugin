
<div class="wrap fw-admin-wrap">
  <div class="card fw-admin-header">
    <div class="fw-admin-pull-left">
      <img style="max-height: 55px" src="<?php echo esc_url( plugins_url( '../assets/FleetWire_Logo-B.png', __FILE__ ) ); ?>" alt="Fleetwire logo" />
    </div>
    <div class="fw-admin-pull-right">
      <a href="https://help.fleetwire.io/en/" target="_blank" rel="noopener" class="button fw-admin-help">Help</a>
    </div>
  </div>

  <div class="card fleetwire-card">
    <h2><?php _e("1. Connect to your Fleetwire account"); ?></h2>
    <p class="fw-admin-subtitle"><?php _e("Don't have a Fleetwire account? <a href=\"https://fleetwire.io/register\" target=\"_blank\" rel=\"noopener\">Get started for free</a>."); ?></p>
    <hr />
    <form id="fleetwire_form" name="fleetwire_form">
      <input type="hidden" name="action" value="fleetwire_company_name_form_process"/>
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label id for="fleetwire_company_name"><?php _e("Company ID"); ?></label>
            </th>
            <td>
              <input type="text" name="fleetwire_company_name" id="fleetwire_company_name" value="<?php echo esc_attr(get_option('fleetwire_company_name')); ?>" size="20" class="regular-text fleetwire-company-uuid">
              <p class="description"><?php _e("Find your <b>Company ID</b> in your Fleetwire account under <b>Settings > Online reservations > Installation > WordPress plugin</b>" ); ?></p>
            </td>
          </tr>
        </tbody>
      </table>
      <p class="submit">
          <input type="submit" name="Submit" value="<?php _e('Update Options') ?>" class="button button-primary fleetwire-update-company-uuid" />
      </p>
    </form>
    <div class="updated fleetwire-d-none"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
  </div>

  <div class="card">
      <h2><?php _e("2. Add your products"); ?></h2>
      <p class="fw-admin-subtitle"><?php _e("To get started, simply <b>copy</b> and <b>paste</b> this shortcode to any <b>Page</b> or <b>Post</b>."); ?></p>
      <hr />
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="embed_code"><?php _e("Embed all products" ); ?></label>
            </th>
            <td>
              <code>[fleetwire_list]</code>
            </td>
          </tr>
        </tbody>
      </table>
      <a href="https://help.fleetwire.io/en/article/how-to-embed-listings-on-your-wordpress-website-u975ix/?bust=1657639589869" target="_blank" rel="noopener"><?php _e("More embed options"); ?></a>
  </div>

  <div class="card">
    <h2><?php _e("3. Customize your online store"); ?></h2>
    <p class="fw-admin-subtitle"><?php _e("Configure branding, rental periods, opening hours, online payments and more."); ?></p>
    <hr />
    <a href="https://fleetwire.io/settings/online-reservations" target="_blank" rel="noopener"><?php _e("Customize online store"); ?></a>
  </div>
</div>

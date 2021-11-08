<?php
  if($_POST['fleetwire_hidden'] == 'Y') {
    //Form data sent
    $company = sanitize_text_field($_POST['fleetwire_company_name']);
    update_option('fleetwire_company_name', $company);

    ?>
    <div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
    <?php
  } else {
    $company = get_option('fleetwire_company_name');
  }
?>

<div class="wrap fw-admin-wrap">
  <div class="card fw-admin-header">
    <div class="fw-admin-pull-left">
      <img style="max-height: 55px" src="<?php echo esc_url( plugins_url( '../assets/FleetWire_Logo-B.png', __FILE__ ) ); ?>" alt="Fleetwire logo" />
    </div>
    <div class="fw-admin-pull-right">
      <a href="https://fleetwire.freshdesk.com/support/solutions" target="_blank" rel="noopener" class="button fw-admin-help">Help</a>
    </div>
  </div>

  <div class="card">
    <h2><?php _e("1. Connect to your Fleetwire account"); ?></h2>
    <p class="fw-admin-subtitle"><?php _e("Don't have a Fleetwire account? <a href=\"https://fleetwire.io/register\" target=\"_blank\" rel=\"noopener\">Get started for free</a>."); ?></p>
    <hr />
    <form name="fleetwire_form" method="post" action="<?php echo esc_url(str_replace( '%7E', '~', $_SERVER['REQUEST_URI'])); ?>">
      <input type="hidden" name="fleetwire_hidden" value="Y">
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="fleetwire_company_name"><?php _e("Company ID"); ?></label>
            </th>
            <td>
              <input type="text" name="fleetwire_company_name" value="<?php echo esc_attr($company); ?>" size="20" class="regular-text">
              <p class="description"><?php _e("Find your <b>Company ID</b> in your Fleetwire account under <b>Settings > Online reservations > Installation > WordPress plugin</b>" ); ?></p>
            </td>
          </tr>
        </tbody>
      </table>

      <p class="submit">
        <input type="submit" name="Submit" value="<?php _e('Update Options', 'fleetwire_trdom') ?>" class="button button-primary" />
      </p>
    </form>
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
      <a href="https://fleetwire.freshdesk.com/support/solutions/articles/61000298645-how-to-embed-listings-on-your-wordpress-website-" target="_blank" rel="noopener"><?php _e("More embed options"); ?></a>
  </div>

  <div class="card">
    <h2><?php _e("3. Customize your online store"); ?></h2>
    <p class="fw-admin-subtitle"><?php _e("Configure branding, rental periods, opening hours, online payments and more."); ?></p>
    <hr />
    <a href="https://fleetwire.io/settings/online-reservations" target="_blank" rel="noopener"><?php _e("Customize online store"); ?></a>
  </div>
</div>

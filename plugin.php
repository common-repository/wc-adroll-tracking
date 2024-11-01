<?php
/*
 *
 * Plugin Name: WooCommerce AdRoll tracking
 * Description: This plugin enables adding AdRoll tracking script to WooCommerce product pages.
 *
 * Version: 1.1
 * Author: Plugin Territory
 * Author URI: http://pluginterritory.com
 * Text Domain: wc-adroll-pluginterritory
 * Domain Path: /languages
 *
 * Copyright: 2015 Plugin Territory
 * License: GNU General Public License v2.0 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

// i18n
add_action( 'plugins_loaded', 'pt_wc_adroll_load_plugin_textdomain' );
function pt_wc_adroll_load_plugin_textdomain() {
    load_plugin_textdomain( 'wc-adroll-pluginterritory', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

// our init actions
add_action( 'plugins_loaded', 'pt_wc_adroll_init' );
function pt_wc_adroll_init() {

	// Init vars for our admin menu page
	add_action( 'admin_init', 'pt_wc_adroll_admin_init' );

	// Show our admin menu entry
	add_action( 'admin_menu', 'pt_wc_adroll_admin_menu' );

	// add a 'Settings' link to the plugin action links
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pt_wc_adroll_add_plugin_setup_link' );

	// ask for rating
	add_filter( 'admin_footer_text', 'pt_wc_adroll_admin_footer_text', 1, 2 );


}

function pt_wc_adroll_add_plugin_setup_link( $actions ) {
	$manage_url = admin_url( 'options-general.php?page=pt_wc_adroll_page' );
	$setup      = array( 'settings' => sprintf( '<a href="%s">%s</a>', $manage_url, __( 'Settings', 'wc-adroll-pluginterritory' ) ) );
	// add the link to the front of the actions list
	return ( array_merge( $setup, $actions ) );
}

/*
 * Lets create the infrastructure for our plugin
 */
function pt_wc_adroll_admin_init() {

	register_setting( 'pt_wc_adroll_settings',
		'pt_wc_adroll_settings' );

	add_settings_section( 'pt_wc_adroll_settings_section',
		__('AdRoll Settings', 'wc-adroll-pluginterritory') ,
		'pt_wc_adroll_fields',
		'pt_wc_adroll_settings_page' );
}

function pt_wc_adroll_fields() {

	$default_values = array(
							'adroll_adv_id' => '',
							'adroll_pix_id' => '',
						);

	$settings = wp_parse_args( get_option( 'pt_wc_adroll_settings' ), $default_values );

?>
<p><?php printf( __( 'Get your <a href="%1$s">AdRoll SmartPixel</a>\'s  <strong>adroll_adv_id</strong> and <strong>adroll_pix_id</strong> from your <a href="%1$s">AdRoll dashboard</a>.', 'wc-adroll-pluginterritory' ), 'https://app.adroll.com/dashboard' ) ?></p>
<div class="options_group">
  <table class="form-table">
    <tr>
      <th scope="row" valign="top"> <label for="adroll_adv_id">
        <?php _e( 'Advertisable ID', 'wc-adroll-pluginterritory' ) ?>
        </label>
      </th>
      <td><input type="text" name="pt_wc_adroll_settings[adroll_adv_id]" id="adroll_adv_id" class="regular-text"  value="<?php echo $settings['adroll_adv_id']?>" />        <p class="description">
        <?php _e( 'Something like adroll_adv_id = "JDNHIFU7MJUMZKUUK5G5WJ"', 'wc-adroll-pluginterritory' )?>
        </p> </td>
    </tr>
    <tr>
      <th scope="row" valign="top"> <label for="adroll_pix_id">
        <?php _e( 'Pixel ID', 'wc-adroll-pluginterritory' ) ?>
        </label>
      </th>
      <td><input type="text" name="pt_wc_adroll_settings[adroll_pix_id]" id="adroll_pix_id" class="regular-text"  value="<?php echo $settings['adroll_pix_id']?>" />        <p class="description">
        <?php _e( 'Something like adroll_pix_id = "X3JCDLAW262GNLIYYEZ7RF"', 'wc-adroll-pluginterritory' )?>
        </p> </td>
    </tr>
  </table>
</div>
<?php
}

function pt_wc_adroll_admin_menu() {
	if ( current_user_can( 'manage_options' ) ) {
		add_options_page( __('AdRoll', 'wc-adroll-pluginterritory'),
			 __('AdRoll Integration', 'wc-adroll-pluginterritory') ,
			 'manage_options',
			 'pt_wc_adroll_page',
			 'pt_wc_adroll_show_page');
	}
}

function pt_wc_adroll_show_page() {
?>
<div class="wrap">
  <div id="icon" class="icon32"></div>
	<div id="pt_wc_adroll_options" class="options_panel">
	  <form method="post" action="options.php">
		<?php
			settings_fields( 'pt_wc_adroll_settings' );
			do_settings_sections( 'pt_wc_adroll_settings_page' );
			submit_button();
		?>
	  </form>
	  <p><?php printf( __( 'Not working? It sometimes takes up to 24 hours for your pixel to be recognized.<br /> <a href="%s">Click here for more help</a>.', 'wc-adroll-pluginterritory' ), 'https://help.adroll.com/hc/en-us/articles/203377810#content-anchor');?></p>
	</div>
</div>
<?php
}

// lets add our code to WooCommerce product pages
add_action( 'wp', 'pt_wc_adroll_tracking_code' );
function pt_wc_adroll_tracking_code() {

	if ( ! is_product() )
		return;

	// get settings
	$settings = get_option( 'pt_wc_adroll_settings', true );

	$adv_id = $settings[ 'adroll_adv_id' ];
	$pix_id = $settings[ 'adroll_pix_id' ];

	if ( empty( $adv_id ) || empty( $pix_id ) ) {

		$code = '

		// Missing AdRoll settings :-(


		';

	} else {


	// AdRoll javascript code
	$code = '

adroll_adv_id = "' . $adv_id . '";
adroll_pix_id = "' . $pix_id . '";
(function () {
var oldonload = window.onload;
window.onload = function(){
   __adroll_loaded=true;
   var scr = document.createElement("script");
   var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
   scr.setAttribute("async", "true");
   scr.type = "text/javascript";
   scr.src = host + "/j/roundtrip.js";
   ((document.getElementsByTagName("head") || [null])[0] ||
    document.getElementsByTagName("script")[0].parentNode).appendChild(scr);
   if(oldonload){oldonload()}};
}());
';
	}
	// enqueue code
	wc_enqueue_js( $code );

}

function pt_wc_adroll_admin_footer_text( $footer_text ) {
	global $current_screen;

	// list of admin pages we want this to appear on
	$pages = array(
		'settings_page_pt_wc_adroll_page',
	);

	if ( isset( $current_screen->id ) && in_array( $current_screen->id, $pages ) ) {
		$footer_text = sprintf( __( 'Enoying this plugin? Then why not rate <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> for <strong>WooCommerce AdRoll tracking</strong> on <a href="%1$s" target="_blank">WordPress.org</a> and make the developer happy too? <a href="%1$s" target="_blank" title="Click and rate &#9733;&#9733;&#9733;&#9733;&#9733;, we both know it will make you feel good inside.">:-)</a>', 'wc-adroll-pluginterritory' ), 'https://wordpress.org/support/view/plugin-reviews/wc-adroll-tracking/?filter=5#postform' );
	}

	return $footer_text;
}

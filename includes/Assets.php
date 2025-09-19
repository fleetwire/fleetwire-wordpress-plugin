<?php
namespace Fleetwire;

/**
 * Fleetwire Assets Class
 * 
 * Handles script and style enqueuing
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Assets Class
 * 
 * @since 1.0.21
 */
class Assets {
    
    /**
     * Constructor
     * 
     * @since 1.0.21
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     * 
     * @since 1.0.21
     * @return void
     */
    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_client_script' ] );
        add_action( 'wp_head', [ $this, 'add_client_configuration' ] );
    }
    
    /**
     * Load Fleetwire client script
     * 
     * @since 1.0.21
     * @return void
     */
    public function enqueue_client_script() {
        $company_id = Config::get_company_id();

        if ( $company_id ) {
            $asset_url = Config::get_company_script_url( $company_id );
            //    $asset_url = 'http://fleetwire.test/tenant/v2/fleetwire.js';
            wp_enqueue_script( 
                'fleetwire_client', 
                $asset_url, 
                [], 
                Config::SCRIPT_VERSION, 
                true 
            );
            
            Logger::debug( 'Fleetwire client script enqueued', [
                'company_id' => $company_id,
                'script_url' => $asset_url
            ] );
        } else {
            Logger::warning( 'Fleetwire client script not enqueued - no company ID configured' );
        }
    }
    
    /**
     * Insert client configuration script
     * 
     * @since 1.0.21
     * @return void
     */
    public function add_client_configuration() {
        $company_id = Config::get_company_id();
        if ( ! $company_id ) {
            Logger::warning( 'Fleetwire client configuration not added - no company ID configured' );
            return;
        }
        ?>
        <script>window.fleetwireOptions = {
                company: '<?php echo esc_js( $company_id ); ?>',
                storeProvider: 'wordpress'
            };</script>
        <?php
        
        Logger::debug( 'Fleetwire client configuration added', [
            'company_id' => $company_id
        ] );
    }
}

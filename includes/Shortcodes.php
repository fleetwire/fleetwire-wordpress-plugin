<?php
namespace Fleetwire;

/**
 * Fleetwire Shortcodes Class
 * 
 * Handles all Fleetwire shortcode functionality
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for Fleetwire shortcodes
 * 
 * @since 1.0.21
 */
abstract class Shortcode_Base {
    
    /**
     * Shortcode tag
     * 
     * @var string
     */
    protected $tag;
    
    /**
     * Default attributes
     * 
     * @var array
     */
    protected $defaults = [];
    
    /**
     * CSS class for the wrapper element
     * 
     * @var string
     */
    protected $css_class;
    
    /**
     * HTML tag for the wrapper element
     * 
     * @var string
     */
    protected $wrapper_tag = 'div';
    
    /**
     * Handle shortcode
     * 
     * @param array $atts Shortcode attributes
     *
     * @return string
     */
    public function handle( array $atts ) {
        $options = shortcode_atts( $this->defaults, $atts );
        $data_attrs = $this->options_to_data_attributes( $options );
        
        return sprintf(
            '<%s class="%s" %s></%s>',
            $this->wrapper_tag,
            esc_attr( $this->css_class ),
            $data_attrs,
            $this->wrapper_tag
        );
    }
    
    /**
     * Convert options to data attributes
     * 
     * @param array $options
     *
     * @return string
     */
    protected function options_to_data_attributes( array $options ) {
        $attributes = [];
        
        foreach ( $options as $key => $value ) {
            if ( ! empty( $value ) ) {
                $key = sanitize_key( $key );
                $value = sanitize_text_field( $value );
                $attributes[] = sprintf( 'data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
            }
        }
        
        return implode( ' ', $attributes );
    }
}

/**
 * Registered legacy shortcode with no current Fleetwire v2 renderer.
 *
 * Keeping these tags registered prevents WordPress from displaying raw shortcode
 * text on older pages while avoiding stale Fleetwire DOM hooks.
 *
 * @since 1.0.21
 */
abstract class Unsupported_Shortcode extends Shortcode_Base {

    /**
     * Handle shortcode
     *
     * @param array $atts Shortcode attributes
     *
     * @return string
     */
    public function handle( array $atts ) {
        return '';
    }
}

/**
 * Fleetwire listing card shortcode
 * 
 * @since 1.0.21
 */
class Card_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_listing_card';
    protected $css_class = 'fleetwire-listing-card';
    protected $defaults = [
        'id' => null,
        'showprice' => null,
    ];
}

/**
 * Fleetwire button shortcode
 * 
 * @since 1.0.21
 */
class Button_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_button';
    protected $css_class = 'fleetwire-listing-button';
    protected $defaults = [
        'id' => null,
        'custom-title' => null,
        'show-price' => null,
    ];
}

/**
 * Fleetwire detail shortcode
 * 
 * @since 1.0.21
 */
class Detail_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_detail';
    protected $css_class = 'fleetwire-listing-card';
    protected $defaults = [
        'id' => null
    ];
}

/**
 * Fleetwire vehicle doors shortcode
 * 
 * @since 1.0.21
 */
class Vehicle_Doors_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_listing_doors';
    protected $css_class = 'fleetwire-listing-doors';
    protected $wrapper_tag = 'span';
    protected $defaults = [
        'id' => null
    ];
}

/**
 * Fleetwire vehicle seats shortcode
 * 
 * @since 1.0.21
 */
class Vehicle_Seats_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_listing_seats';
    protected $css_class = 'fleetwire-listing-seats';
    protected $wrapper_tag = 'span';
    protected $defaults = [
        'id' => null
    ];
}

/**
 * Fleetwire vehicle features shortcode
 * 
 * @since 1.0.21
 */
class Vehicle_Features_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_listing_features';
    protected $css_class = 'fleetwire-listing-features';
    protected $wrapper_tag = 'span';
    protected $defaults = [
        'id' => null
    ];
}

/**
 * Fleetwire vehicle reviews shortcode
 * 
 * @since 1.0.21
 */
class Vehicle_Reviews_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_listing_reviews';
    protected $css_class = 'fleetwire-listing-reviews';
    protected $wrapper_tag = 'span';
    protected $defaults = [
        'id' => null,
        'showsource' => null,
    ];
}

/**
 * Fleetwire vehicle image gallery shortcode
 * 
 * @since 1.0.21
 */
class Vehicle_Image_Gallery_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_listing_image_gallery';
    // Both classes: fleetwire.js renders a gallery into `fleetwire-listing-gallery`;
    // scripts that predate it still show the first photo via `fleetwire-listing-image`.
    protected $css_class = 'fleetwire-listing-image fleetwire-listing-gallery';
    protected $defaults = [
        'id' => null,
    ];
}

/**
 * Fleetwire vehicle image shortcode
 *
 * @since 1.0.21
 */
class Vehicle_Image_Shortcode extends Shortcode_Base {

    protected $tag = 'fleetwire_listing_image';
    protected $css_class = 'fleetwire-listing-image';
    protected $defaults = [
        'id' => null,
    ];
}

/**
 * Fleetwire vehicle availability calendar shortcode
 *
 * A month calendar for one vehicle with booked days greyed out; the renter picks
 * dates and continues to booking. `months` forces one or two months side by side
 * (otherwise it follows the container width); `button-text` relabels the button.
 *
 * @since 1.1.0
 */
class Vehicle_Calendar_Shortcode extends Shortcode_Base {

    protected $tag = 'fleetwire_listing_calendar';
    protected $css_class = 'fleetwire-listing-calendar';
    protected $defaults = [
        'id'          => null,
        'months'      => null,
        'button-text' => null,
    ];
}

/**
 * Fleetwire list shortcode
 * 
 * `filters="true"` adds a renter filter bar (vehicle type, seats, price, sort);
 * `sort` sets the starting order: `price_asc`, `price_desc`, or the host order.
 *
 * @since 1.0.21
 */
class List_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_list';
    protected $css_class = 'fleetwire-listing-list';
    protected $defaults = [
        'tags' => null,
        'categories' => null,
        'per' => null,
        'limit' => null,
        'show-search' => null,
        'showprice' => null,
        'search-key' => null,
        'filters' => null,
        'sort' => null
    ];
}

/**
 * Fleetwire search shortcode
 * 
 * @since 1.0.21
 */
class Search_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_search';
    protected $css_class = 'fleetwire-search';
    protected $defaults = [
        'show-location-picker' => null,
    ];
}

/**
 * Fleetwire availability shortcode
 * 
 * @since 1.0.21
 */
class Availability_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_availability';
    protected $css_class = 'fleetwire-search';
    protected $defaults = [
        'show-location-picker' => null
    ];
}

/**
 * Fleetwire datepicker shortcode
 * 
 * @since 1.0.21
 */
class Datepicker_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_datepicker';
    protected $css_class = 'fleetwire-datepicker';
    protected $defaults = [];
}

/**
 * Fleetwire cart button shortcode
 * 
 * @since 1.0.21
 */
class Cart_Button_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_cart_button';
    protected $css_class = 'fleetwire-cart-button';
    protected $defaults = [
        'href' => null
    ];
}

/**
 * Fleetwire embeddable cart shortcode
 * 
 * @since 1.0.21
 */
class Embeddable_Cart_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_embeddable_cart';
    protected $css_class = 'fleetwire-embeddable-cart';
    protected $defaults = [];
}

/**
 * Fleetwire embeddable cart lines shortcode
 * 
 * @since 1.0.21
 */
class Embeddable_Cart_Lines_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_embeddable_cart_lines';
    protected $css_class = 'fleetwire-embeddable-cart-lines';
    protected $defaults = [
        'compact' => null
    ];
}

/**
 * Fleetwire embeddable cart sidebar shortcode
 * 
 * @since 1.0.21
 */
class Embeddable_Cart_Sidebar_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_embeddable_cart_sidebar';
    protected $css_class = 'fleetwire-embeddable-cart-sidebar';
    protected $defaults = [
        'continue-shopping' => null,
        'datepicker' => null
    ];
}

/**
 * Fleetwire sidebar shortcode
 * 
 * @since 1.0.21
 */
class Sidebar_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_sidebar';
    protected $css_class = 'fleetwire-sidebar';
    protected $defaults = [
        'search-key' => null
    ];
}

/**
 * Fleetwire sort shortcode
 * 
 * @since 1.0.21
 */
class Sort_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_sort';
    protected $css_class = 'fleetwire-sort';
    protected $defaults = [
        'search-key' => null
    ];
}

/**
 * Fleetwire bar shortcode
 * 
 * @since 1.0.21
 */
class Bar_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_bar';
    protected $css_class = 'fleetwire-bar';
    protected $defaults = [
        'search-key' => null,
    ];
}

/**
 * Fleetwire categories shortcode
 * 
 * @since 1.0.21
 */
class Categories_Shortcode extends Unsupported_Shortcode {
    
    protected $tag = 'fleetwire_categories';
    protected $css_class = 'fleetwire-categories';
    protected $defaults = [
        'search-key' => null
    ];
}

/**
 * Fleetwire description shortcode
 * 
 * @since 1.0.21
 */
class Description_Shortcode extends Shortcode_Base {
    
    protected $tag = 'fleetwire_listing_description';
    protected $css_class = 'fleetwire-listing-description';
    protected $defaults = [
        'id' => null,
        'length' => null,
    ];
}

/**
 * Fleetwire title shortcode
 *
 * @since 1.0.21
 */
class Title_Shortcode extends Shortcode_Base {

    protected $tag = 'fleetwire_listing_title';
    protected $css_class = 'fleetwire-listing-title';
    protected $defaults = [
        'id' => null,
        'append-label' => null,
        'prepend-label' => null,
    ];
}

/**
 * Fleetwire price shortcode
 *
 * @since 1.0.21
 */
class Price_Shortcode extends Shortcode_Base {

    protected $tag = 'fleetwire_listing_price';
    protected $css_class = 'fleetwire-listing-price';
    protected $defaults = [
        'id' => null,
        'append-label' => null,
        'prepend-label' => null,
    ];
}

/**
 * Main Fleetwire Shortcodes Manager
 * 
 * @since 1.0.21
 */
class Shortcodes {
    
    /**
     * Shortcode instances
     * 
     * @var array
     */
    private $shortcodes = [];
    
    /**
     * Constructor
     * 
     * @since 1.0.21
     */
    public function __construct() {
        $this->init_shortcodes();
    }
    
    /**
     * Initialize all shortcodes
     * 
     * @since 1.0.21
     * @return void
     */
    private function init_shortcodes() {
        $this->shortcodes = [
            'fleetwire_listing_card' => new Card_Shortcode(),
            'fleetwire_button' => new Button_Shortcode(),
            'fleetwire_product' => new Button_Shortcode(), // Alias
            'fleetwire_detail' => new Detail_Shortcode(),
            'fleetwire_list' => new List_Shortcode(),
            'fleetwire_search' => new Search_Shortcode(),
            'fleetwire_availability' => new Availability_Shortcode(),
            'fleetwire_cart_button' => new Cart_Button_Shortcode(),
            'fleetwire_embeddable_cart' => new Embeddable_Cart_Shortcode(),
            'fleetwire_embeddable_cart_sidebar' => new Embeddable_Cart_Sidebar_Shortcode(),
            'fleetwire_embeddable_cart_lines' => new Embeddable_Cart_Lines_Shortcode(),
            'fleetwire_sidebar' => new Sidebar_Shortcode(),
            'fleetwire_sort' => new Sort_Shortcode(),
            'fleetwire_bar' => new Bar_Shortcode(),
            'fleetwire_categories' => new Categories_Shortcode(),
            'fleetwire_listing_description' => new Description_Shortcode(),
            'fleetwire_listing_title' => new Title_Shortcode(),
            'fleetwire_listing_price' => new Price_Shortcode(),
            'fleetwire_listing_doors' => new Vehicle_Doors_Shortcode(),
            'fleetwire_listing_seats' => new Vehicle_Seats_Shortcode(),
            'fleetwire_listing_reviews' => new Vehicle_Reviews_Shortcode(),
            'fleetwire_listing_image_gallery' => new Vehicle_Image_Gallery_Shortcode(),
            'fleetwire_listing_image' => new Vehicle_Image_Shortcode(),
            'fleetwire_listing_calendar' => new Vehicle_Calendar_Shortcode(),
            'fleetwire_listing_features' => new Vehicle_Features_Shortcode(),
            'fleetwire_datepicker' => new Datepicker_Shortcode(),
        ];
    }
    
    /**
     * Register all shortcodes with WordPress
     * 
     * @since 1.0.21
     * @return void
     */
    public function register_shortcodes() {
        foreach ( $this->shortcodes as $tag => $shortcode ) {
            add_shortcode( $tag, [ $shortcode, 'handle' ] );
        }
    }
    
    /**
     * Get a shortcode instance
     * 
     * @since 1.0.21
     * @param string $tag Shortcode tag
     * @return Shortcode_Base|null
     */
    public function get_shortcode( $tag ) {
        return $this->shortcodes[ $tag ] ?? null;
    }
    
    /**
     * Get all registered shortcode tags
     * 
     * @since 1.0.21
     * @return array
     */
    public function get_shortcode_tags() {
        return array_keys( $this->shortcodes );
    }
}

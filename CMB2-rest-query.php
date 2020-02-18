<?php
/**
 * Flare Guide event listings
 *
 * @package      CMB2-rest-query
 * @author       Igor Honhoff
 * @copyright    2020 Igor Honhoff
 * @license      GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Query parameters in REST API for CMB2 fields
 * Plugin URI:        https://github.com/dutchigor/CMB2-rest-query
 * Description:       Adds a query parameter to the REST API for any CMB2 field that has the property rest_query set on it.
 * Version:           0.1.0
 * Author:            Igor Honhoff
 * Author URI:        https://github.com/dutchigor
 * License:           GPL-3.0-or-later
 * Requires PHP:      7.0
 * Requires at least: 5.3
 */

class FG_REST_query {
    /** @var $instance Hold the class instance */
    private static $instance = null;

    /**
     * Get the single instance of this class
     * Create it if it does not exist
     *
     * @return FG_REST_query
     **/
    public static function getInstance()
    {
        if (self::$instance == null)
        {
        self::$instance = new FG_REST_query();
        }
    
        return self::$instance;
    }

    /** @var Array $post_types List of post types with CMB2 boxes and the boxes that they include */
    protected $post_types = [];

    /**
     * The constructor hooks the class functions in to wordpress
     **/
    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_cpt_query_param_hooks' ] );
    }

    /**
     * Hooks in to rest_api_init.
     * 
     * Stores the list of post types with a CMB2 metabos and the metaboxes registered to them.
     * For each post type a filter is registered to add query parameters
     **/
    public function add_cpt_query_param_hooks() {
        // Go through all CMB2 metaboxes and store the post type and ID for each box on this instance
        foreach ( CMB2_Boxes::get_all() as $cmb ) {
            foreach ($cmb->box_types() as $cpt ) {
                $meta_box = $cmb->prop( 'id' );
                $this->post_types[ $cpt ][] = $meta_box;
            }
        }

        // For each found post type, register a filter to add query parameters
        foreach ($this->post_types as $cpt => $cmbs ) {
            add_filter( "rest_{$cpt}_query", [ $this, 'add_cmb2_query_params' ], 20, 2 );
        }
    }
    
    /**
     * Hooks in to the rest_{$this->post_type}_query filter.
     * 
     * Adds a meta_query entry in the WP_Query arguments for each query parameter
     * that matches a CMB2 field which has rest_query set and is a member of a metabox
     * registered to the queried post type.
     *
     * @param Array $args Key value array of query var to query value.
     * @param WP_REST_Request $request The request used in REST 
     * @return Array $args
     **/
    public function add_cmb2_query_params( $args, $request ) {
        // Get metaboxes registered to queried post type
        $boxes = $this->post_types[ $args['post_type'] ];
        foreach ($boxes as $box ) {
            $cmb = CMB2_Boxes::get( $box );

            // Check all fields of each metabox if the rest_query property is set
            $fields = $cmb->prop( 'fields' );
            foreach ($fields as $field ) {
                if ( $field['rest_query'] ) {

                    // Determine query parameter to check for
                    $param = ( $field['rest_query'] === true ) ? $field['id'] : $field['rest_query'];

                    // If the request includes a query for the given parameter
                    if ( $request[$param] ) {
                        // Add an entry into the query args' meta_query
                        // using the related compare parameter if it is set
                        $args['meta_query'][] = [
                            'key'       => $field['id'],
                            'value'     => $request[ $param ],
                            'compare'   => $request["{$param}_compare"] ?: '=',
                        ];
                    }
                }
            }
        }

        // Let WP Carry on with it's business
        return $args;
    }
}

FG_REST_query::getInstance();

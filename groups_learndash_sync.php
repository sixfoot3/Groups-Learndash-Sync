<?php
/**
 * Plugin Name: Groups Learndash Sync
 * Plugin URI:  http://sixfoot3.com
 * Description: Sync Groups with Learndash Groups
 * Version:     0.1.0
 * Author:      Tom Morton
 * Author URI:  http://sixfoot3.com
 * License:     GPLv2+
 */

/**
 * Copyright 2015  Tom Morton  (email : Tom@sixfoot3.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Useful global constants
define( 'SF3GLS_VERSION', '0.1.0' );
define( 'SF3GLS_URL',     plugin_dir_url( __FILE__ ) );
define( 'SF3GLS_PATH',    dirname( __FILE__ ) . '/' );

//Lets Go!

if (!class_exists("sf3_GroupsLearndashSync")) {
    class sf3_GroupsLearndashSync {

        public static $instance;
        private $options;

        const OPTIONS      = 'sf3_glsync';
        const MENU_SLUG   = 'sf3-glsync';

        public function sf3_GroupsLearndashSync() {
            $this->__construct();
        }

        function __construct() {
            self::$instance = $this;

            add_action( 'init',         array( $this, 'init'        ) );
            add_action( 'admin_init',   array( $this, 'admin_init'  ) );

            add_action( 'admin_init',   array( $this, 'settings_page_init'  ) );
            add_action( 'admin_menu',   array( $this, 'admin_menu'  ) );

            add_filter( 'the_content',  array( $this, 'sf3gls_filter_content'      ) );

        }

        public function init() {

        }

        public function admin_init() {

        }

        public function admin_menu() {
            $settings_page = add_menu_page( 'Groups Learndash Sync', 'Groups Learndash Sync', 'manage_options', self::MENU_SLUG, array( $this, 'render_settings_page' ), null, 3);

            $settings_page_edit = add_submenu_page(self::MENU_SLUG, 'Settings', 'Settings', 'manage_options', self::MENU_SLUG, array( $this, 'render_settings_page' ));

            $settings_child_page = add_submenu_page( self::MENU_SLUG, 'Child Settings Page', 'Child Settings Page', 'manage_options', 'child-settings-page', array( $this, 'render_test_page' ));


            add_action( "load-{$settings_page}", array( $this, 'enqueue' ) );
            add_action( "load-{$settings_child_page}", array( $this, 'enqueue' ) );

        }

        public function settings_page_init(){
            register_setting('sf3_glsync', 'sf3_glsync', array($this,'validate_settings'));

            add_settings_section(
                $id         = 'settings_section_id',
                $title      = __('Settings Section','sf3-glsync'),
                $callback   = array($this,'settings_section_description'),
                $page       = 'settings_area'
            );

            add_settings_field(
                $id         = 'radio_id',
                $title      = __('Radio Section','sf3-glsync'),
                $callback   = array( $this, 'radio_input' ),
                $page       = 'settings_area',
                $section    = 'settings_section_id',
                $args       = array(
                    'name'        => 'settings_radio',
                    'type'        => 'radio',
                    'description' => 'Settings Radio',
                    'options'     => array(
                        'option_one'  => _('Option One'),
                        'option_two'  => _('Option Two'),
                    ),
                    'settings_name' => 'sf3_glsync'

                )
            );

            add_settings_field(
                $id         = 'text_id',
                $title      = __('Text Section','sf3-glsync'),
                $callback   = array( $this, 'text_input' ),
                $page       = 'settings_area',
                $section    = 'settings_section_id',
                $args       = array(
                    'name'        => 'settings_text',
                    'type'        => 'text',
                    'description' => 'Settings Text',
                    'options'     => array(
                        'option_one'  => _(''),
                    ),
                    'settings_name' => 'sf3_glsync'

                )
            );

        }

        public function settings_section_description() {
            _e('Settings Section description');
        }

        public function text_input( $args ){

            $option = (array) get_option($args['settings_name']);

            if ( empty( $args['name'] ) || ! is_array( $args['options'] ) )
                return false;

            $option_value = ( isset( $option[$args['name']] ) ) ? $option[$args['name']] : '';


            echo '<label for="' . esc_attr( $args['name'] ) . '">';
                foreach ( (array) $args['options'] as $value => $label ){
                    echo '<input name="'.$args['settings_name'] .'['. esc_attr( $args['name'] ) . ']" type="' . esc_attr( $args['type'] ) . '" id="' . esc_attr( $args['name'] ) . '" value="'.esc_attr($option_value).'" > ' . $label;
                }
            echo '</label>';
            if ( ! empty( $args['description'] ) )
                echo ' <p class="description">' . $args['description'] . '</p>';

        }

        public function radio_input( $args ){

            $option = (array) get_option($args['settings_name']);

            if ( empty( $args['name'] ) || ! is_array( $args['options'] ) )
                return false;

            $option_value = ( isset( $option[$args['name']] ) ) ? $option[$args['name']] : '';

            echo '<label for="' . esc_attr( $args['name'] ) . '">';
                foreach ( (array) $args['options'] as $value => $label ){

                    $checked = ( $option_value == $value ) ? 'checked' : '';

                    echo '<input name="'.$args['settings_name'] .'['. esc_attr( $args['name'] ) . ']" type="' . esc_attr( $args['type'] ) . '" id="' . esc_attr( $args['name'] ) . '" value="'.esc_attr($value).'" '.$checked.'> ' . $label;
                    echo '<br />';
                }
            echo '</label>';
            if ( ! empty( $args['description'] ) )
                echo ' <p class="description">' . $args['description'] . '</p>';

        }

        public function validate_settings($input){
            return $input;
        }

        public function render_settings_page() {
            include( dirname( __FILE__ ) . '/templates/settings-page.php' );
        }

        public function enqueue() {
            wp_enqueue_style(   'settings-css', plugins_url( "css/plugin.css", __FILE__ ), array(), '' );

            wp_enqueue_script(  'settings-js',  plugins_url( "js/src/plugin.js", __FILE__ ), array( 'jquery'), '' );
            wp_localize_script( 'settings-js', 'ajax', array( 'url' => admin_url( 'admin-ajax.php' ) ) );

            do_action( 'sf3gls_enqueue' );
        }

        public function sf3gls_filter_content( $content ) {

            return $content;

        }

    } //end sf3_GroupsLearndashSync
} //end if exists

new sf3_GroupsLearndashSync;

/**
 * Activate the plugin
 */
function sf3gls_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sf3gls_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function sf3gls_deactivate() {

}
register_deactivation_hook( __FILE__, 'sf3gls_deactivate' );
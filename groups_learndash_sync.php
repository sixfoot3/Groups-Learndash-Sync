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
define( 'SF3GLS_URL', plugin_dir_url( __FILE__ ) );
define( 'SF3GLS_PATH', dirname( __FILE__ ) . '/' );

//Lets Go!
if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) && ! function_exists( 'new_cmb2_box' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
}

if ( ! class_exists( "sf3_GroupsLearndashSync" ) ) {
	class sf3_GroupsLearndashSync {

		public static $instance;
		private $options;

		const OPTIONS = 'sf3_glsync';
		const MENU_SLUG = 'sf3-glsync';

		function __construct() {
			self::$instance = $this;

			add_action( 'cmb2_init', array( $this, 'sf3gls_ld_groups_meta' ), 10 );
			add_action( 'groups_created_user_group', array( $this, 'groups_created_user_group' ), 10, 2 );
			add_action( 'groups_deleted_user_group', array( $this, 'groups_deleted_user_group' ), 10, 2 );

		}

		public function sf3_GroupsLearndashSync() {
			$this->__construct();
		}

		function sf3gls_get_groups() {
			if ( ! class_exists( 'Groups_Group' ) ) {
				return false;
			}

			$result         = array();
			$args['fields'] = 'group_id,name';
			$groups         = Groups_Group::get_groups( $args );

			if ( sizeof( $groups ) > 0 ) {
				foreach ( $groups as $group ) {
					$result[ $group->group_id ] = $group->name;
				}
			}

			return $result;

		}

		function sf3gls_ld_groups_meta() {

			$prefix = '_sf3gls_';

			$groups = self::sf3gls_get_groups();

			if ( function_exists( 'new_cmb2_box' ) && ! empty( $groups ) ) {
				$sync_meta = new_cmb2_box( array(
					'id'           => $prefix . 'sync_metabox',
					'title'        => __( 'Sync With Groups', 'sf3gls' ),
					'object_types' => array( 'groups', ),
					'context'      => 'side',
					'priority'     => 'high',
				) );

				$sync_meta->add_field( array(
					'name'             => __( 'Choose Group to Sync', 'sf3gls' ),
					'desc'             => __( 'When chosen, this group will sync with the Itthinx group.', 'sf3gls' ),
					'id'               => $prefix . 'group_sync_id',
					'type'             => 'select',
					'show_option_none' => 'Select A Group',
					'options'          => $groups
				) );
			}
		}

		function get_learndash_group_from_groups_plugin( $groups_plugin_group_id ) {
			if ( ! class_exists( 'Groups_Group' ) ) {
				return false;
			}

			$args  = array(
				'meta_key'   => '_sf3gls_group_sync_id',
				'meta_value' => $groups_plugin_group_id,
				'post_type'  => 'groups',
				'fields'     => 'ids'
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				return $query->posts;
			}

			return false;
		}

		function groups_created_user_group( $user_id, $groups_plugin_group_id ) {

			if ( ! class_exists( 'Groups_Group' ) ) {
				return false;
			}

			$learndash_group_ids = $this->get_learndash_group_from_groups_plugin( $groups_plugin_group_id );

			foreach ( $learndash_group_ids as $ld_id ) {

				$learndash_group_users = array( $user_id );
				$group_users           = learndash_get_groups_user_ids( $ld_id );

				foreach ( $learndash_group_users as $ga ) {
					if ( ! in_array( $ga, $group_users ) ) {
						update_user_meta( $ga, "learndash_group_users_" . $ld_id, $ld_id );
					}
				}

				$group_enrolled_courses = learndash_group_enrolled_courses( $ld_id );

				if ( $group_enrolled_courses ) {
					foreach ( $group_enrolled_courses as $course_id ) {
						$meta = ld_update_course_access( $user_id, $course_id );
					}
				}

			}
		}

		function groups_deleted_user_group( $user_id, $groups_plugin_group_id ) {

			if ( ! class_exists( 'Groups_Group' ) ) {
				return false;
			}

			$learndash_group_ids = $this->get_learndash_group_from_groups_plugin( $groups_plugin_group_id );

			foreach ( $learndash_group_ids as $ld_id ) {
				delete_user_meta( $user_id, "learndash_group_users_" . $ld_id, null );

				$group_enrolled_courses = learndash_group_enrolled_courses( $ld_id );

				if ( $group_enrolled_courses ) {
					foreach ( $group_enrolled_courses as $course_id ) {
						$meta = ld_update_course_access( $user_id, $course_id, $remove = true );
					}
				}
			}
		}

	} //end class
} //end if

$GLOBALS['sf3_GroupsLearndashSync'] = new sf3_GroupsLearndashSync;
<?php
/*
 Plugin Name: BEA ACF terms fields
 Version: 1.1.0
 Plugin URI: https://github.com/BeAPI/bea-acf-term-fields
 Description: Simple class for adding the ACF values to the term object automatically
 Author: BeAPI
 Author URI: http://www.beapi.fr

 ----

 Copyright 2015 Beapi Technical team (human@beapi.fr)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class BEA_ACF_Term_Fields {

	/**
	 * @var BEA_ACF_Term_Fields
	 */
	private static $instance;

	/**
	 * Taxonomies to handle
	 *
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * ACF fields for the taxonomies
	 *
	 * @var array|null
	 */
	private $fields = null;

	/**
	 * Add all the filters for the terms filling
	 */
	protected function __construct() {
		// Append fields to the term
		add_filter( 'get_terms', array( $this, 'add_terms_get_terms' ), 9, 3 );
		add_filter( 'get_the_terms', array( $this, 'add_terms_get_the_terms' ), 9 );
		add_filter( 'wp_get_object_terms', array( $this, 'add_terms' ), 9, 4 );
		add_filter( 'get_term', array( $this, 'add_term' ), 9 );
	}

	/**
	 * @return BEA_ACF_Term_Fields
	 * @author Nicolas Juen
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the fields for the given taxonomies
	 *
	 * @return array|null
	 * @author Nicolas Juen
	 */
	private function get_fields() {
		if( !is_null( $this->fields ) ) {
			return $this->fields;
		}

		// Empty the fields on the taxonomies
		if( empty( $this->taxonomies ) ) {
			$this->fields = null;
		}

		foreach( $this->taxonomies as $taxonomy_name => $taxonomy ) {
			$groups = acf_get_field_groups( array( 'taxonomy' => $taxonomy_name ) );

			if( empty( $groups ) ) {
				continue;
			}

			$fields = array();
			foreach( $groups as $group ) {
				$fields += acf_get_fields( $group );
			}

			foreach( $fields as $field ) {
				$this->fields[$taxonomy_name][$field['name']] = $field['key'];
			}
		}

		return $this->fields;
	}

	/**
	 * Get all the taxonomy fields
	 *
	 * @param $taxonomy
	 *
	 * @return array
	 * @author Nicolas Juen
	 */
	private function get_taxonomy_fields( $taxonomy ) {
		if( !$this->is_taxonomy( $taxonomy ) ) {
			return array();
		}

		$fields = $this->get_fields();

		return isset( $fields[$taxonomy] ) && !empty( $fields[$taxonomy] ) ? $fields[$taxonomy] : array() ;
	}

	/**
	 * Add a taxonomy to the taxonomies to get the fields from
	 *
	 * @param $taxonomy
	 *
	 * @return $this
	 * @author Nicolas Juen
	 */
	public function add_taxonomy( $taxonomy ) {
		if( !taxonomy_exists( $taxonomy ) ) {
			return $this;
		}

		// Empty the fields on taxonomy added
		$this->fields = null;
		$this->taxonomies[$taxonomy] = get_taxonomy( $taxonomy );

		return $this;
	}

	/**
	 * Check there is taxonomies to get from
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function have_taxonomies() {
		return !empty( $this->taxonomies );
	}

	/**
	 * Check the taxonomy is on the list of taxonomies to make
	 *
	 * @param $taxonomy string or array
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function is_taxonomy( $taxonomy ) {
		if( !is_array( $taxonomy ) ) {
			$taxonomy = explode( ', ', str_replace( "'", "", $taxonomy ) );
		}

		foreach( $taxonomy as $tax ) {
			if( isset( $this->taxonomies[$tax] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $terms
	 * @param $taxonomies
	 * @param $args
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public function add_terms_get_terms( $terms, $taxonomies, $args ) {
		if( is_wp_error( $terms ) || !$this->is_taxonomy( $taxonomies ) ) {
			return $terms;
		}

		return $this->add_terms( $terms, '', $taxonomies, $args );
	}

	/**
	 * @param $term
	 *
	 * @return $term a term object
	 * @author Nicolas Juen
	 */
	public function add_term( $term ) {

		if( is_wp_error( $term ) || !$this->is_taxonomy( $term->taxonomy ) ) {
			return $term;
		}

		// Get the fields
		$fields = $this->get_taxonomy_fields( $term->taxonomy );

		// Check there is fields
		if( empty( $fields ) ) {
			return $term;
		}

		/**
		 * Get all the fields for the term of the taxonomy
		 */
		foreach( $fields as $field_name => $field_key ) {
			$term->{$field_name} = get_field( $field_key, $term );
		}

		return $term;
	}

	/**
	 * @param $terms
	 * @param $objects
	 * @param $taxonomies
	 * @param $args
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public function add_terms( $terms, $objects, $taxonomies, $args ) {
		if( is_wp_error( $terms ) || !$this->is_taxonomy( $taxonomies ) ) {
			return $terms;
		}

		if( $args['fields'] == 'all' ) {
			foreach( $terms as &$term ) {
				if( !$this->is_taxonomy( $term->taxonomy ) ) {
					continue;
				}
				$term = $this->add_term( $term );
			}
		}

		return $terms;
	}

	/**
	 * @param $terms
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public function add_terms_get_the_terms( $terms ) {

		if( is_wp_error( $terms ) || !$this->have_taxonomies() ) {
			return $terms;
		}

		foreach( $terms as &$term ) {
			if( !$this->is_taxonomy( $term->taxonomy ) ) {
				continue;
			}

			$term = $this->add_term( $term );
		}
		return $terms;
	}

}
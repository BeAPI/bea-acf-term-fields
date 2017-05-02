# bea-acf-term-fields
Simple class for adding the ACF values to the term object automatically

# Usage 

The usage is very simple, just add taxonomies you want to get the ACF fields from and the script does everything else.

```php
add_action( 'init',  'init_taxonomies_fields', 11 );

/**
 * Add taxonomies to the API for getting the fields for each term
 *
 * @author BeAPI
 */
public function init_taxonomies_fields() {
	BEA_ACF_Term_Fields::get_instance()
		->add_taxonomy( 'post_tag' )
		->add_taxonomy( 'category' );
}


## Changelog ##

### 1.1.2
* 02 May 2017
* Fix fatal error on empty field name

### 1.1.1
* 19 Apr 2017
* Fix admin notice

### 1.0.0
* Initial Release
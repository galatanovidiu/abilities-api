<?php
/**
 * Abilities API
 *
 * Defines functions for managing abilities in WordPress.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.1.0
 */

declare( strict_types = 1 );

/**
 * Registers a new ability using Abilities API.
 *
 * Note: Do not use before the {@see 'abilities_api_init'} hook.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::register()
 *
 * @param string              $name The name of the ability. The name must be a string containing a namespace
 *                                  prefix, i.e. `my-plugin/my-ability`. It can only contain lowercase
 *                                  alphanumeric characters, dashes and the forward slash.
 * @param array<string,mixed> $args An associative array of arguments for the ability. This should include
 *                                  `label`, `description`, `input_schema`, `output_schema`, `execute_callback`,
 *                                  `permission_callback`, `meta`, and `ability_class`.
 * @return ?\WP_Ability An instance of registered ability on success, null on failure.
 *
 * @phpstan-param array{
 *   label?: string,
 *   description?: string,
 *   execute_callback?: callable( mixed $input= ): (mixed|\WP_Error),
 *   permission_callback?: callable( mixed $input= ): (bool|\WP_Error),
 *   input_schema?: array<string,mixed>,
 *   output_schema?: array<string,mixed>,
 *   meta?: array<string,mixed>,
 *   ability_class?: class-string<\WP_Ability>,
 *   ...<string, mixed>
 * } $args
 */
function wp_register_ability( string $name, array $args ): ?WP_Ability {
	if ( ! did_action( 'abilities_api_init' ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: abilities_api_init, 2: string value of the ability name. */
				esc_html__( 'Abilities must be registered on the %1$s action. The ability %2$s was not registered.' ),
				'<code>abilities_api_init</code>',
				'<code>' . esc_html( $name ) . '</code>'
			),
			'0.1.0'
		);
		return null;
	}

	return WP_Abilities_Registry::get_instance()->register( $name, $args );
}

/**
 * Unregisters an ability using Abilities API.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::unregister()
 *
 * @param string $name The name of the registered ability, with its namespace.
 * @return ?\WP_Ability The unregistered ability instance on success, null on failure.
 */
function wp_unregister_ability( string $name ): ?WP_Ability {
	return WP_Abilities_Registry::get_instance()->unregister( $name );
}

/**
 * Retrieves a registered ability using Abilities API.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::get_registered()
 *
 * @param string $name The name of the registered ability, with its namespace.
 * @return ?\WP_Ability The registered ability instance, or null if it is not registered.
 */
function wp_get_ability( string $name ): ?WP_Ability {
	return WP_Abilities_Registry::get_instance()->get_registered( $name );
}

/**
 * Retrieves all registered abilities using Abilities API.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::get_all_registered()
 *
 * @return \WP_Ability[] The array of registered abilities.
 */
function wp_get_abilities(): array {
	return WP_Abilities_Registry::get_instance()->get_all_registered();
}

/**
 * Retrieves abilities filtered by category.
 *
 * @since 0.4.0
 *
 * @see WP_Abilities_Registry::get_abilities_by_category()
 *
 * @param string|string[] $categories The category slug(s) to filter by. Can be a single string or an array of strings.
 * @return \WP_Ability[] The array of abilities in the specified category or categories.
 */
function wp_get_abilities_by_category( $categories ): array {
	return WP_Abilities_Registry::get_instance()->get_abilities_by_category( $categories );
}

/**
 * Retrieves the default ability categories.
 *
 * @since 0.4.0
 *
 * @return array[] Array of default ability categories.
 *
 * @phpstan-return array<int, array{slug: string, label: string, description: string}>
 */
function get_default_ability_categories(): array {
	return array(
		array(
			'slug'        => 'content',
			'label'       => __( 'Content', 'abilities-api' ),
			'description' => __( 'Abilities related to content management', 'abilities-api' ),
		),
		array(
			'slug'        => 'system',
			'label'       => __( 'System', 'abilities-api' ),
			'description' => __( 'System and configuration abilities', 'abilities-api' ),
		),
		array(
			'slug'        => 'user',
			'label'       => __( 'User', 'abilities-api' ),
			'description' => __( 'User management abilities', 'abilities-api' ),
		),
	);
}

/**
 * Retrieves all available ability categories.
 *
 * This function returns the default categories and allows them to be modified
 * via the 'ability_categories_all' filter.
 *
 * @since 0.4.0
 *
 * @return array[] Array of ability categories.
 *
 * @phpstan-return array<int, array{slug: string, label: string, description: string}>
 */
function get_ability_categories(): array {
	$categories = get_default_ability_categories();

	/**
	 * Filters the available ability categories.
	 *
	 * Allows plugins and themes to add, remove, or modify ability categories.
	 *
	 * @since 0.4.0
	 *
	 * @param array[] $categories Array of ability categories. Each category should have
	 *                            'slug', 'label', and 'description' keys.
	 */
	$categories = apply_filters( 'ability_categories_all', $categories );

	// Validate that the filter returned an array.
	if ( ! is_array( $categories ) ) {
		_doing_it_wrong(
			'ability_categories_all',
			__( 'The ability_categories_all filter must return an array.', 'abilities-api' ),
			'0.4.0'
		);
		return get_default_ability_categories();
	}

	// Validate each category has the required structure.
	$valid_categories = array();
	foreach ( $categories as $index => $category ) {
		if ( ! is_array( $category ) ) {
			_doing_it_wrong(
				'ability_categories_all',
				sprintf(
					/* translators: %d: Category index. */
					__( 'Invalid category at index %d. Each category must be an array.', 'abilities-api' ),
					$index
				),
				'0.4.0'
			);
			continue;
		}

		if ( ! isset( $category['slug'] ) || ! is_string( $category['slug'] ) ) {
			_doing_it_wrong(
				'ability_categories_all',
				sprintf(
					/* translators: %d: Category index. */
					__( 'Invalid category at index %d. Each category must have a "slug" property that is a string.', 'abilities-api' ),
					$index
				),
				'0.4.0'
			);
			continue;
		}

		// Validate slug format matches the pattern required by WP_Ability.
		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $category['slug'] ) ) {
			_doing_it_wrong(
				'ability_categories_all',
				sprintf(
					/* translators: %s: Category slug. */
					__( 'Invalid category slug "%s". Category slugs must contain only lowercase alphanumeric characters and dashes.', 'abilities-api' ),
					$category['slug']
				),
				'0.4.0'
			);
			continue;
		}

		// Validate label is present and is a string.
		if ( ! isset( $category['label'] ) || ! is_string( $category['label'] ) ) {
			_doing_it_wrong(
				'ability_categories_all',
				sprintf(
					/* translators: %s: Category slug. */
					__( 'Invalid category "%s". Each category must have a "label" property that is a string.', 'abilities-api' ),
					$category['slug']
				),
				'0.4.0'
			);
			continue;
		}

		// Validate description is present and is a string.
		if ( ! isset( $category['description'] ) || ! is_string( $category['description'] ) ) {
			_doing_it_wrong(
				'ability_categories_all',
				sprintf(
					/* translators: %s: Category slug. */
					__( 'Invalid category "%s". Each category must have a "description" property that is a string.', 'abilities-api' ),
					$category['slug']
				),
				'0.4.0'
			);
			continue;
		}

		$valid_categories[] = $category;
	}

	return $valid_categories;
}

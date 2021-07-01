<?php
/**
 *  Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\Materials\Taxonomies;

use TMS\Plugin\Materials\PostTypes\Material;

/**
 * Class MaterialType
 *
 * @package TMS\MaterialsPlugin\Materials\Taxonomies
 */
class MaterialType {

    /**
     * This defines the slug of this post type.
     */
    public const SLUG = 'material_type-tax';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', \Closure::fromCallable( [ $this, 'register' ] ), 15 );
    }

    /**
     * This registers the post type.
     *
     * @return void
     */
    private function register() {
        $labels = [
            'name'                       => 'Materiaalityyppi',
            'singular_name'              => 'Materiaalityyppi',
            'menu_name'                  => 'Materiaalityypit',
            'all_items'                  => 'Kaikki materiaalityypit',
            'new_item_name'              => 'Lisää uusi materiaalityyppi',
            'add_new_item'               => 'Lisää uusi materiaalityyppi',
            'edit_item'                  => 'Muokkaa materiaalityyppiä',
            'update_item'                => 'Päivitä materiaalityyppi',
            'view_item'                  => 'Näytä materiaalityyppi',
            'separate_items_with_commas' => \__( 'Separate departments with commas', 'geniem-contacts' ),
            'add_or_remove_items'        => \__( 'Add or remove departments', 'geniem-contacts' ),
            'choose_from_most_used'      => \__( 'Choose from most used departments', 'geniem-contacts' ),
            'popular_items'              => \__( 'Popular departments', 'geniem-contacts' ),
            'search_items'               => 'Etsi materiaalityyppiä',
            'not_found'                  => 'Ei tuloksia',
            'no_terms'                   => 'Ei tuloksia',
            'items_list'                 => 'Materiaalityypit',
            'items_list_navigation'      => 'Materiaalityypit',
        ];

        $filter_prefix = 'tms/taxonomy/' . static::SLUG;

        $labels = \apply_filters(
            $filter_prefix . '/labels',
            $labels
        );

        $capabilities = \apply_filters(
            $filter_prefix . '/capabilities',
            [
                'manage_terms' => 'manage_material_types',
                'edit_terms'   => 'edit_material_types',
                'delete_terms' => 'delete_material_types',
                'assign_terms' => 'assign_material_types',
            ]
        );

        $args = [
            'labels'            => $labels,
            'capabilities'      => $capabilities,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'show_in_rest'      => false,
        ];

        $args = \apply_filters( $filter_prefix . '/args', $args );
        $slug = \apply_filters( $filter_prefix . '/slug', static::SLUG );

        \register_taxonomy( $slug, [ Material::SLUG ], $args );
    }
}

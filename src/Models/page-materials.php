<?php
/**
 * Template Name: Materiaalikirjasto
 */

use TMS\Plugin\Materials\Taxonomies\MaterialType;

/**
 * The model class for Materials
 */
class PageMaterials extends BaseModel {

    /**
     * Template
     */
    const TEMPLATE = 'page-materials.php';

    /**
     * Search input name.
     */
    const SEARCH_QUERY_VAR = 'material_search';

    /**
     * Return translated strings.
     *
     * @return array[]
     */
    public function strings() : array {
        return [
            'search'     => [
                'label'             => __( 'Search for materials', 'tms-plugin-materials' ),
                'submit_value'      => __( 'Search', 'tms-plugin-materials' ),
                'input_placeholder' => __( 'Search query', 'tms-plugin-materials' ),
            ],
            'no_results' => __( 'No results', 'tms-plugin-materials' ),
        ];
    }

    /**
     * Return page description.
     *
     * @return string
     */
    public function description() : string {
        return get_field( 'description' );
    }

    /**
     * Return current search data.
     *
     * @return string[]
     */
    public function search() : array {
        $this->search_data        = new stdClass();
        $this->search_data->query = get_query_var( self::SEARCH_QUERY_VAR );

        return [
            'input_search_name' => self::SEARCH_QUERY_VAR,
            'current_search'    => $this->search_data->query,
            'action'            => get_the_permalink(),
        ];
    }

    /**
     * Return relevant material type terms.
     *
     * @return array
     */
    public function terms() : array {
        $terms = ! empty( get_field( 'material_types' ) )
            ? get_field( 'material_types' )
            : $this->get_relevant_material_type_terms();

        if ( empty( $terms ) ) {
            return [];
        }

        $current_term = $this->get_queried_material_type_term();

        return array_map( function ( $term_id ) use ( $current_term ) {
            $term = get_term( $term_id, MaterialType::SLUG );

            return [
                'name'      => $term->name,
                'permalink' => add_query_arg(
                    MaterialType::SLUG,
                    $term->term_id,
                    get_the_permalink()
                ),
                'is_active' => $term->term_id === (int) $current_term,
            ];
        }, $terms );
    }

    /**
     * Material post type items.
     *
     * @return array
     */
    public function items() : array {
        $items = $this->query_items();

        if ( empty( $items ) ) {
            return [];
        }

        return MaterialsPlugin::format_file_items( $items );
    }

    /**
     * Return queried items.
     *
     * @return int[]|WP_Post[]
     */
    protected function query_items() {
        $per_page = 12;
        $paged    = ! empty( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

        $args = [
            'post_type'      => Material::SLUG,
            'posts_per_page' => $per_page,
            'offset'         => ( $paged - 1 ) * $per_page,
            'fields'         => 'ids',
        ];

        if ( ! empty( get_field( 'materials' ) ) ) {
            $args['post__in'] = get_field( 'materials' );
        }

        $query_terms = $this->get_query_terms();

        if ( ! empty( $query_terms ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => MaterialType::SLUG,
                    'terms'    => $query_terms,
                ],
            ];
        }

        if ( isset( $this->search_data->query ) && ! empty( $this->search_data->query ) ) {
            $args['s'] = $this->search_data->query;
        }

        $wp_query = new WP_Query( $args );

        $this->setup_pagination( $per_page, $paged, $wp_query->found_posts );

        return $wp_query->get_posts();
    }

    /**
     * Get queried material type term.
     *
     * @return string|bool
     */
    protected function get_queried_material_type_term() : string {
        return get_query_var( MaterialType::SLUG, false );
    }

    /**
     * Setup pagination.
     *
     * @param int $per_page   Number of posts per page.
     * @param int $paged      Current page.
     * @param int $post_count Total number of posts.
     */
    protected function setup_pagination( $per_page, $paged, $post_count ) {
        $this->pagination           = new stdClass();
        $this->pagination->page     = $paged;
        $this->pagination->per_page = $per_page;
        $this->pagination->items    = $post_count;
        $this->pagination->max_page = ceil( $post_count / $per_page );
    }

    /**
     * Get relevant taxonomy material type terms based on selected material items.
     *
     * @return void[]
     */
    protected function get_relevant_material_type_terms() : array {
        $items = get_field( 'materials' );

        if ( empty( $items ) ) {
            return [];
        }

        $taxonomy_terms = [];

        foreach ( $items as $item ) {
            $terms = wp_get_post_terms( $item, MaterialType::SLUG, [ 'fields' => 'ids' ] );

            foreach ( $terms as $term_id ) {
                if ( ! in_array( $term_id, $taxonomy_terms, true ) ) {
                    $taxonomy_terms[] = $term_id;
                }
            }
        }

        return $taxonomy_terms;
    }

    /**
     * Returns pagination data.
     *
     * @return mixed|void
     */
    public function pagination() {
        if ( isset( $this->pagination->page ) && isset( $this->pagination->max_page ) ) {
            if ( $this->pagination->page <= $this->pagination->max_page ) {
                return $this->pagination;
            }
        }
    }

    /**
     * Get relevant taxonomy terms for WP Query.
     *
     * @return array|bool[]|string[]
     */
    protected function get_query_terms() : array {
        $query_terms = [];

        if ( ! empty( get_field( 'material_types' ) ) ) {
            $query_terms = get_field( 'material_types' );
        }

        $queried_term = $this->get_queried_material_type_term();

        if ( $queried_term ) {
            $query_terms = [ $queried_term ];
        }

        return $query_terms;
    }
}

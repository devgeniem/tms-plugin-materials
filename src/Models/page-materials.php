<?php
/**
 * Template Name: Materiaalikirjasto
 */

use TMS\Plugin\Materials\MaterialsPlugin;
use TMS\Plugin\Materials\PostTypes\Material;
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
            'terms'      => [
                'show_all'   => __( 'Show All', 'tms-plugin-materials' ),
                'aria_label' => __( 'Filter materials by taxonomy', 'tms-plugin-materials' ),
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

        $search_clause = $this->search_data->query;
        $result_count  = '3';

        if ( $result_count > 0 ) {
            $results_text = sprintf(
            // translators: 1. placeholder is number of search results, 2. placeholder contains the search term(s).
                _nx(
                    '%1$1s result found for "%2$2s"',
                    '%1$1s results found for "%2$2s"',
                    $result_count,
                    'search results summary',
                    'tms-plugin-materials'
                ),
                $result_count,
                $search_clause
            );
        }
        else {
            $results_text = __( 'No search results', 'tms-plugin-materials' );
        }

        return [
            'input_search_name' => self::SEARCH_QUERY_VAR,
            'current_search'    => $this->search_data->query,
            'action'            => get_the_permalink(),
            'summary'           => $results_text,
        ];
    }

    /**
     * Return relevant material type terms.
     *
     * @return array
     */
    public function terms() : array {
        $tax_terms = ! empty( get_field( 'material_types' ) )
            ? get_field( 'material_types' )
            : $this->get_relevant_material_type_terms();

        if ( empty( $tax_terms ) ) {
            return [];
        }

        $current_term = $this->get_queried_material_type_term();

        $terms = array_map( function ( $term_id ) use ( $current_term ) {
            $term      = get_term( $term_id, MaterialType::SLUG );
            $is_active = $term->term_id === (int) $current_term;

            return [
                'name'            => $term->name,
                'permalink'       => add_query_arg(
                    MaterialType::SLUG,
                    $term->term_id,
                    get_the_permalink()
                ),
                'is_active'       => $is_active,
                'link_classes'    => $is_active ? 'is-active' : '',
                'link_attributes' => $is_active ? 'aria-current="page"' : '',
            ];
        }, $tax_terms );

        $no_active_filter = empty( $current_term ) && empty( $this->search_data->query );

        array_unshift( $terms, [
            'name'            => __( 'Show All', 'tms-plugin-materials' ),
            'permalink'       => get_the_permalink(),
            'is_active'       => $no_active_filter,
            'link_classes'    => $no_active_filter ? 'is-active' : '',
            'link_attributes' => $no_active_filter ? 'aria-current="page"' : '',
        ] );

        return $terms;
    }

    /**
     * Material post type items.
     *
     * @return array
     */
    public function results() : array {
        $items = $this->query_items();

        $search_clause = $this->search_data->query;
        $result_count  = $this->pagination->items;

        if ( $result_count > 0 ) {
            $results_text = sprintf(
            // translators: 1. placeholder is number of search results, 2. placeholder contains the search term(s).
                _nx(
                    '%1$1s result found for "%2$2s"',
                    '%1$1s results found for "%2$2s"',
                    $result_count,
                    'search results summary',
                    'tms-plugin-materials'
                ),
                $result_count,
                $search_clause
            );
        }
        else {
            $results_text = __( 'No search results', 'tms-plugin-materials' );
        }

        if ( empty( $items ) ) {
            return [];
        }

        return [
            'items'   => MaterialsPlugin::format_file_items( $items ),
            'summary' => $results_text,
        ];
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
        ];

        $selected_materials = get_field( 'materials' );

        if ( ! empty( $selected_materials ) ) {
            $args['post__in'] = $selected_materials;
        }

        $query_terms = $this->get_query_terms();

        // Selected materials bypass taxonomy selection
        if ( ! empty( $query_terms ) && empty( $selected_materials ) ) {
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

        return $wp_query->posts;
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
     * Return page template classes.
     *
     * @return array
     */
    public function layout_classes() {
        $submit_classes = apply_filters( 'tms/plugin-materials/page_materials/submit_button_classes', '' );

        $button_classes = apply_filters(
            'tms/plugin-materials/page_materials/material_page_item_button_classes',
            'is-primary is-outlined'
        );

        $page_item_text_classes = apply_filters(
            'tms/plugin-materials/page_materials/material_page_item_text_classes',
            'has-text-black'
        );

        $page_item_classes = apply_filters(
            'tms/plugin-materials/page_materials/material_page_item_classes',
            'has-border-secondary has-border-1 has-border-radius-small'
        );

        return [
            'submit_classes'   => $submit_classes,
            'page_item'        => $page_item_classes,
            'page_item_text'   => $page_item_text_classes,
            'page_item_button' => $button_classes,
        ];
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

    /**
     * View's flexible layouts
     *
     * @return array
     */
    public function components() : array {
        $content = get_field( 'components' ) ?? [];

        if ( empty( $content ) || ! is_array( $content ) ) {
            return [];
        }

        return $this->handle_layouts( $content );
    }

    /**
     * Format layout data
     *
     * @param array $fields Array of Layout fields.
     *
     * @return array
     */
    protected function handle_layouts( array $fields ) : array {
        $handled = [];

        if ( empty( $fields ) ) {
            return $handled;
        }

        foreach ( $fields as $layout ) {
            if ( empty( $layout['acf_fc_layout'] ) ) {
                continue;
            }

            $acf_layout        = $layout['acf_fc_layout'];
            $layout_name       = str_replace( '_', '-', $acf_layout );
            $layout['partial'] = 'layout-' . $layout_name . '.dust';

            $handled[] = apply_filters(
                "tms/acf/layout/${acf_layout}/data",
                $layout
            );
        }

        return $handled;
    }
}

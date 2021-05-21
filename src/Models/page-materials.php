<?php
/**
 * Template Name: Materiaalikirjasto
 */

/**
 * The extendable model class for Materials
 */
class PageMaterials extends \BaseModel {

    /**
     * Template
     */
    const TEMPLATE = 'page-materials.php';

    /**
     * Search input name.
     */
    const SEARCH_QUERY_VAR = 'material_search';

    /**
     * Return the content.
     *
     * @return array|object|WP_Post|null
     * @throws Exception
     */
    public function content() {
        return \DustPress\Query::get_acf_post( get_the_ID() );
    }

    /**
     * Return translated strings.
     *
     * @return array[]
     */
    public function strings() : array {
        return [
            'search' => [
                'label'             => __( 'Search for materials', 'tms-plugin-materials' ),
                'submit_value'      => __( 'Search', 'tms-plugin-materials' ),
                'input_placeholder' => __( 'Search query', 'tms-plugin-materials' ),
            ],
        ];
    }

    /**
     * Return current search value.
     *
     * @return string[]
     */
    public function search() : array {
        $this->search       = new stdClass();
        $this->search->term = get_query_var( self::SEARCH_QUERY_VAR, false );

        return [
            'input_search_name' => self::SEARCH_QUERY_VAR,
            'current_term'      => $this->search->term,
            'action'            => get_the_permalink(),
        ];
    }

    /**
     * Return relevant material type terms.
     *
     * @return array
     */
    public function terms() : array {
        $terms_field = get_field( 'material_types' );

        $this->terms = new stdClass();

        if ( empty( $terms_field ) ) {
            return [];
        }

        return array_map( function ( $term ) {
            return [
                'name'      => $term->name,
                'permalink' => add_query_arg(
                    \TMS\Plugin\Materials\Taxonomies\MaterialType::SLUG,
                    $term->slug,
                    get_the_permalink()
                ),
            ];
        }, $terms_field );
    }

    /**
     * Material post type items.
     *
     * @return array
     */
    public function items() : array {
        $per_page = 12;
        $paged    = \get_query_var( 'paged', 1 );

        $args = [
            'post_type'      => TMS\Plugin\Materials\PostTypes\Material::SLUG,
            'posts_per_page' => $per_page,
            'offset'         => ( $paged - 1 ) * $per_page,
        ];

        if ( isset( $this->search->term ) && ! empty( $this->search->term ) ) {
            $args['s'] = $this->search->term;
        }

        $wp_query = new \WP_Query( $args );

        if ( empty( $wp_query ) ) {
            return [];
        }

        // Store pagination data
        $this->pagination           = new stdClass();
        $this->pagination->page     = $paged;
        $this->pagination->per_page = $per_page;
        $this->pagination->items    = $wp_query->found_posts;
        $this->pagination->max_page = ceil( $wp_query->found_posts / $per_page );

        $items = array_filter(
            array_map( function ( $id ) {
                $file = get_field( 'file', $id );

                if ( empty( $file ) ) {
                    return false;
                }

                return [
                    'url'         => $file['url'],
                    'title'       => get_the_title( $id ),
                    'filesize'    => size_format( $file['filesize'], 2 ),
                    'filetype'    => $file['subtype'],
                    'description' => wp_kses_post( get_field( 'description', $id ) ),
                    'image'       => get_field( 'image', $id ),
                    'button_text' => __( 'Open', 'tms-plugin-materials' ),
                ];
            }, $wp_query->get_posts() )
        );

        return $items;
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
}

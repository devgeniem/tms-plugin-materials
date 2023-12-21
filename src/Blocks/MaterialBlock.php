<?php
/**
 * Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\Materials\Blocks;

use Geniem\ACF\Block;
use Geniem\ACF\Field;
use Geniem\ACF\Renderer\CallableRenderer;
use Geniem\ACF\Renderer\Dust;
use TMS\Plugin\Materials\MaterialsPlugin;
use TMS\Plugin\Materials\PostTypes\Material;

/**
 * Class MaterialBlock
 *
 * @package TMS\MaterialsPlugin\Materials\Blocks
 */
class MaterialBlock {

    /**
     * The block name (slug, not shown in admin).
     *
     * @var string
     */
    const NAME = 'material';

    /**
     * The block title.
     *
     * @var string
     */
    const BLOCK_TITLE = 'Tiedostot';

    /**
     * The block description. Used in WP block navigation.
     *
     * @var string
     */
    protected $description = '';

    /**
     * The block category. Used in WP block navigation.
     *
     * @var string
     */
    protected $category = 'common';

    /**
     * The block acf-key.
     *
     * @var string
     */
    const KEY = 'material';

    /**
     * The block icon
     *
     * @var string
     */
    protected $icon = 'image-filter';

    /**
     * The block mode. ACF has a few different options.
     * Edit opens the block always in edit mode for example.
     *
     * @var string
     */
    protected $mode = 'edit';

    /**
     * The block supports. You can add all ACF support attributes here.
     *
     * @var array
     */
    protected $supports = [
        'align'  => false,
        'anchor' => true,
    ];


    /**
     * Getter for block name.
     *
     * @return string
     */
    public function get_name() {
        return static::NAME;
    }

    /**
     * Create the block and register it.
     */
    public function __construct() {
        $block = new Block( static::BLOCK_TITLE, static::KEY );
        $block->set_category( $this->category );
        $block->set_icon( $this->icon );
        $block->set_description( $this->description );
        $block->set_mode( $this->mode );
        $block->set_supports( $this->supports );
        $block->set_renderer( $this->get_renderer() );

        if ( method_exists( static::class, 'fields' ) ) {
            $block->add_fields( $this->fields() );
        }

        if ( method_exists( static::class, 'filter_data' ) ) {
            $block->add_data_filter( [ $this, 'filter_data' ] );
        }

        $block->register();
    }

    /**
     * Get the renderer.
     * If dust partial is not found in child theme, we will use the parent theme partial.
     *
     * @param string $name Dust partial name, defaults to block name.
     *
     * @return Dust|CallableRenderer
     * @throws Exception Thrown if template is not found.
     */
    protected function get_renderer( string $name = '' ) {
        $name              = $name ?: $this->get_name();
        $partial_file_name = 'block-' . $name . '.dust';
        $file              = get_theme_file_path( '/partials/blocks/' . $partial_file_name );

        if ( ! file_exists( $file ) ) {
            $file = MaterialsPlugin::get_instance()->get_plugin_path() . '/src/Partials/' . $partial_file_name;
        }

        return new Dust( $file );
    }

    /**
     * Create block fields.
     *
     * @return array
     */
    protected function fields() : array {
        $strings = [
            'materials' => [
                'label'        => 'Tiedostot',
                'instructions' => '',
            ],
            'layout'    => [
                'label'        => 'Asettelu',
                'instructions' => '',
            ],
        ];

        $key = self::KEY;

        $materials_field = ( new Field\Relationship( $strings['materials']['label'] ) )
            ->set_key( "{$key}_materials" )
            ->set_name( 'materials' )
            ->set_post_types( [ Material::SLUG ] )
            ->redipress_include_search( function ( $materials ) {
                if ( empty( $materials ) ) {
                    return '';
                }

                $results = [];

                foreach ( $materials as $material_id ) {
                    $results[] = get_the_title( $material_id );
                }

                return implode( ' ', $results );
            } )
            ->set_filters( [ 'search' ] )
            ->set_return_format( 'id' )
            ->set_min( 1 )
            ->set_max( 20 )
            ->set_instructions( $strings['materials']['instructions'] );

        $layout_field = ( new Field\Radio( $strings['layout']['label'] ) )
            ->set_key( "{$key}_layout" )
            ->set_name( 'layout' )
            ->set_choices( [
                'simple' => 'Yksinkertainen',
                'rich'   => 'Rikas',
            ] )
            ->set_instructions( $strings['layout']['instructions'] );

        return apply_filters(
            'tms/block/' . self::KEY . '/fields',
            [
                $materials_field,
                $layout_field,
            ]
        );
    }

    /**
     * This filters the block ACF data.
     *
     * @param array  $data       Block's ACF data.
     * @param Block  $instance   The block instance.
     * @param array  $block      The original ACF block array.
     * @param string $content    The HTML content.
     * @param bool   $is_preview A flag that shows if we're in preview.
     * @param int    $post_id    The parent post's ID.
     *
     * @return array The block data.
     */
    public function filter_data( $data, $instance, $block, $content, $is_preview, $post_id ) : array { // phpcs:ignore
        if ( isset( $this->supports['anchor'] ) && $this->supports['anchor'] ) {
            $data['anchor'] = $block['anchor'] ?? '';
        }

        if ( ! empty( $data['materials'] ) ) {
            $data['items'] = MaterialsPlugin::format_file_items( $data['materials'] );
        }

        $data['is_full_view']   = $data['layout'] === 'rich';
        $data['title_classes']  = 'has-text-paragraph';
        $data['button_classes'] = 'is-primary is-outlined';

        return apply_filters( 'tms/acf/block/' . self::KEY . '/data', $data );
    }
}

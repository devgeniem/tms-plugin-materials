<?php
/**
 *  Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\Materials\PostTypes;

use Geniem\ACF\Exception;
use Geniem\ACF\Group;
use Geniem\ACF\Field;
use Geniem\ACF\RuleGroup;
use TMS\Theme\Base\Logger;

/**
 * Class Material
 *
 * @package TMS\MaterialsPlugin\Materials\PostTypes
 */
class Material {

    /**
     * This defines the slug of this post type.
     */
    public const SLUG = 'material-cpt';

    /**
     * This defines what is shown in the url. This can
     * be different than the slug which is used to register the post type.
     *
     * @var string
     */
    private $url_slug = '';

    /**
     * Define the CPT description
     *
     * @var string
     */
    private $description = '';

    /**
     * This is used to position the post type menu in admin.
     *
     * @var int
     */
    private $menu_order = 40;

    /**
     * This defines the CPT icon.
     *
     * @var string
     */
    private $icon = 'dashicons-image-filter';

    /**
     * Constructor
     */
    public function __construct() {
        // Make url slug translatable
        $this->url_slug = _x( 'materials', 'theme CPT slugs', 'tms-plugin-materials' );

        // Make possible description text translatable.
        $this->description = _x( 'CPT Description', 'theme CPT', 'tms-plugin-materials' );

        add_action( 'init', \Closure::fromCallable( [ $this, 'register' ] ), 100, 0 );
        add_action( 'acf/init', \Closure::fromCallable( [ $this, 'fields' ] ), 50, 0 );
    }

    /**
     * Add hooks and filters from this controller
     *
     * @return void
     */
    public function hooks() : void {
    }

    /**
     * This registers the post type.
     *
     * @return void
     */
    private function register() {
        $labels = [
            'name'                  => 'Materiaalit',
            'singular_name'         => 'Materiaali',
            'menu_name'             => 'Materiaalit',
            'name_admin_bar'        => 'Materiaali',
            'archives'              => 'Arkistot',
            'attributes'            => 'Ominaisuudet',
            'parent_item_colon'     => 'Vanhempi:',
            'all_items'             => 'Kaikki',
            'add_new_item'          => 'Lisää uusi',
            'add_new'               => 'Lisää uusi',
            'new_item'              => 'Uusi',
            'edit_item'             => 'Muokkaa',
            'update_item'           => 'Päivitä',
            'view_item'             => 'Näytä',
            'view_items'            => 'Näytä kaikki',
            'search_items'          => 'Etsi',
            'not_found'             => 'Ei löytynyt',
            'not_found_in_trash'    => 'Ei löytynyt roskakorista',
            'featured_image'        => 'Kuva',
            'set_featured_image'    => 'Aseta kuva',
            'remove_featured_image' => 'Poista kuva',
            'use_featured_image'    => 'Käytä kuvana',
            'insert_into_item'      => 'Aseta julkaisuun',
            'uploaded_to_this_item' => 'Lisätty tähän julkaisuun',
            'items_list'            => 'Listaus',
            'items_list_navigation' => 'Listauksen navigaatio',
            'filter_items_list'     => 'Suodata listaa',
        ];

        $args = [
            'label'               => $labels['name'],
            'description'         => '',
            'labels'              => $labels,
            'supports'            => [ 'title', 'revisions' ],
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => $this->menu_order,
            'menu_icon'           => $this->icon,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'material',
            'query_var'           => true,
            'map_meta_cap'        => true,
            'show_in_rest'        => true,
        ];

        $args = apply_filters(
            'tms/post_type/' . static::SLUG . '/args',
            $args
        );

        register_post_type( static::SLUG, $args );
    }

    /**
     * Register fields
     */
    protected function fields() {
        try {
            $field_group = ( new Group( 'Materiaalin tiedot' ) )
                ->set_key( 'fg_material' );

            $rule_group = ( new RuleGroup() )
                ->add_rule( 'post_type', '==', static::SLUG );

            $field_group
                ->add_rule_group( $rule_group )
                ->set_position( 'normal' )
                ->set_hidden_elements(
                    [
                        'discussion',
                        'comments',
                        'format',
                        'send-trackbacks',
                    ]
                );

            $strings = [
                'image'       => [
                    'label'        => 'Kuva',
                    'instructions' => 'Tiedoston esikatselukuva listauksia varten',
                ],
                'description' => [
                    'label'        => 'Kuvaus',
                    'instructions' => '',
                ],
                'file'        => [
                    'label'        => 'Tiedosto',
                    'instructions' => '',
                ],
            ];

            $key = $field_group->get_key();

            $image_field = ( new Field\Image( $strings['image']['label'] ) )
                ->set_key( "${key}_image" )
                ->set_name( 'image' )
                ->set_return_format( 'id' )
                ->set_wrapper_width( 50 )
                ->set_instructions( $strings['image']['instructions'] );

            $file_field = ( new Field\File( $strings['file']['label'] ) )
                ->set_key( "${key}_file" )
                ->set_name( 'file' )
                ->set_wrapper_width( 50 )
                ->set_instructions( $strings['file']['instructions'] );

            $description_field = ( new Field\ExtendedWysiwyg( $strings['description']['label'] ) )
                ->set_key( "${key}_description" )
                ->set_name( 'description' )
                ->set_tabs( 'visual' )
                ->set_toolbar(
                    [
                        'bold',
                        'italic',
                        'link',
                        'pastetext',
                        'removeformat',
                    ]
                )
                ->disable_media_upload()
                ->set_instructions( $strings['description']['instructions'] );

            $field_group->add_fields(
                apply_filters(
                    'tms/acf/group/' . $field_group->get_key() . '/fields',
                    [
                        $image_field,
                        $file_field,
                        $description_field,
                    ]
                )
            );

            $field_group = apply_filters(
                'tms/acf/group/' . $field_group->get_key(),
                $field_group
            );

            $field_group->register();
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTraceAsString() );
        }
    }
}

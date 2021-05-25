<?php
/**
 * Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\Materials\Fields;

use Geniem\ACF\Exception;
use Geniem\ACF\Group;
use Geniem\ACF\RuleGroup;
use Geniem\ACF\Field;
use TMS\Plugin\Materials\PostTypes\Material;
use TMS\Theme\Base\Logger;

/**
 * Class FrontPageGroup
 *
 * @package TMS\Theme\Base\ACF
 */
class PageMaterialsGroup {

    /**
     * PageGroup constructor.
     */
    public function __construct() {
        add_action(
            'init',
            \Closure::fromCallable( [ $this, 'register_fields' ] )
        );
    }

    /**
     * Register fields
     */
    protected function register_fields() : void {
        try {
            $group_title = _x( 'Materiaalit', 'plugin ACF', 'tms-plugin-materials' );

            $field_group = ( new Group( $group_title ) )
                ->set_key( 'fg_materials_page_fields' );

            $rule_group = ( new RuleGroup() )
                ->add_rule( 'page_template', '==', \PageMaterials::TEMPLATE );

            $field_group
                ->add_rule_group( $rule_group )
                ->set_hidden_elements(
                    [
                        'discussion',
                        'comments',
                        'format',
                        'send-trackbacks',
                    ]
                );

            $field_group->add_fields(
                apply_filters(
                    'tms/acf/group/' . $field_group->get_key() . '/fields',
                    [
                        $this->get_material_page_description( $field_group->get_key() ),
                        $this->get_materials_field( $field_group->get_key() ),
                        $this->get_material_types_field( $field_group->get_key() ),
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

    /**
     * Get material page descripton field
     *
     * @param string $key Field group key.
     *
     * @return Field\Textarea
     * @throws Exception In case of invalid option.
     */
    protected function get_material_page_description( string $key ) : Field\Textarea {
        $strings = [
            'description' => [
                'label'        => _x( 'Kuvausteksti', 'plugin ACF', 'tms-plugin-materials' ),
                'instructions' => '',
            ],
        ];

        $description_field = ( new Field\Textarea( $strings['description']['label'] ) )
            ->set_key( "${key}_description" )
            ->set_name( 'description' )
            ->set_instructions( $strings['description']['instructions'] );

        return $description_field;
    }

    /**
     * Get header fields
     *
     * @param string $key Field group key.
     *
     * @return Field\Relationship
     * @throws Exception In case of invalid option.
     */
    protected function get_materials_field( string $key ) : Field\Relationship {
        $strings = [
            'materials' => [
                'label'        => _x( 'Materiaalit', 'plugin ACF', 'tms-plugin-materials' ),
                'instructions' => '',
            ],
        ];

        $materials_field = ( new Field\Relationship( $strings['materials']['label'] ) )
            ->set_key( "${key}_materials" )
            ->set_name( 'materials' )
            ->set_post_types( [ Material::SLUG ] )
            ->set_return_format( 'id' )
            ->set_instructions( $strings['materials']['instructions'] );

        return $materials_field;
    }

    /**
     * Get header fields
     *
     * @param string $key Field group key.
     *
     * @return Field\Taxonomy
     * @throws Exception In case of invalid option.
     */
    protected function get_material_types_field( string $key ) : Field\Taxonomy {
        $strings = [
            'material_types' => [
                'label'        => _x( 'Materiaalityypit', 'plugin ACF', 'tms-plugin-materials' ),
                'instructions' => '',
            ],
        ];

        $material_types_field = ( new Field\Taxonomy( $strings['material_types']['label'] ) )
            ->set_key( "${key}_material_types" )
            ->set_name( 'material_types' )
            ->set_instructions( $strings['material_types']['instructions'] )
            ->set_taxonomy( 'material_type-tax' )
            ->set_return_format( 'id' );

        return $material_types_field;
    }
}

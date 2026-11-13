<?php
/**
 * Copyright (c) 2021. Geniem Oy
 */

namespace TMS\Plugin\Materials\Layouts;

use Geniem\ACF\Exception;
use Geniem\ACF\Field;
use Geniem\ACF\Field\Flexible\Layout;
use TMS\Plugin\Materials\PostTypes\Material;
use TMS\Theme\Base\Logger;

/**
 * Class AccordionFileLayout
 *
 * @package TMS\Theme\Base\ACF\Layouts
 */
class AccordionFileLayout extends Layout {

    /**
     * Layout key
     */
    const KEY = '_accordion_file';

    /**
     * Create the layout
     *
     * @param string $key Key from the flexible content.
     */
    public function __construct( string $key ) {
        parent::__construct(
            'Tiedosto',
            $key . self::KEY,
            'accordion_file'
        );

        $this->add_layout_fields();
    }

    /**
     * Add layout fields
     *
     * @return void
     */
    private function add_layout_fields() : void {
        $strings = [
            'materials' => [
                'label'        => 'Tiedostot',
                'instructions' => '',
            ],
        ];

        $key = $this->get_key();

        try {
            $materials_field = ( new Field\Relationship( $strings['materials']['label'] ) )
                ->set_key( "{$key}_materials" )
                ->set_name( 'materials' )
                ->set_post_types( [ Material::SLUG ] )
                ->set_filters( [ 'search' ] )
                ->set_return_format( 'id' )
                ->set_min( 1 )
                ->set_max( 20 )
                ->set_instructions( $strings['materials']['instructions'] );

            $this->add_fields(
                apply_filters(
                    'tms/acf/layout/' . $this->get_key() . '/fields',
                    [ $materials_field ]
                )
            );
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTrace() );
        }
    }
}

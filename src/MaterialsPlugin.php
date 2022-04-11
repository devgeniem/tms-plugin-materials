<?php

namespace TMS\Plugin\Materials;

use TMS\Plugin\Materials\Blocks\MaterialBlock;
use TMS\Plugin\Materials\Layouts\AccordionFileLayout;
use TMS\Plugin\Materials\PostTypes\Material;
use TMS\Plugin\Materials\Taxonomies\MaterialType;
use TMS\Plugin\Materials\Fields\PageMaterialsFieldGroup;

/**
 * Class MaterialsPlugin
 *
 * @package TMS\MaterialsPlugin\Materials
 */
final class MaterialsPlugin {

    /**
     * Holds the singleton.
     *
     * @var MaterialsPlugin
     */
    protected static $instance;

    /**
     * Current plugin version.
     *
     * @var string
     */
    protected $version = '';
    /**
     * Path to assets distribution versions.
     *
     * @var string
     */
    protected string $dist_path = '';
    /**
     * Uri to assets distribution versions.
     *
     * @var string
     */
    protected string $dist_uri = '';

    /**
     * Get the instance.
     *
     * @return MaterialsPlugin
     */
    public static function get_instance() : MaterialsPlugin {
        return self::$instance;
    }

    /**
     * The plugin directory path.
     *
     * @var string
     */
    protected $plugin_path = '';

    /**
     * The plugin root uri without trailing slash.
     *
     * @var string
     */
    protected $plugin_uri = '';

    /**
     * Get the version.
     *
     * @return string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function get_plugin_path() : string {
        return $this->plugin_path;
    }

    /**
     * Get the plugin directory uri.
     *
     * @return string
     */
    public function get_plugin_uri() : string {
        return $this->plugin_uri;
    }

    /**
     * Initialize the plugin by creating the singleton.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    public static function init( $version = '', $plugin_path = '' ) {
        if ( empty( self::$instance ) ) {
            self::$instance = new self( $version, $plugin_path );
        }
    }

    /**
     * Get the plugin instance.
     *
     * @return MaterialsPlugin
     */
    public static function plugin() {
        return self::$instance;
    }

    /**
     * Initialize the plugin functionalities.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    protected function __construct( $version = '', $plugin_path = '' ) {
        $this->version     = $version;
        $this->plugin_path = $plugin_path;
        $this->plugin_uri  = plugin_dir_url( $plugin_path ) . basename( $this->plugin_path );
        $this->dist_path   = $this->plugin_path . '/assets/dist/';
        $this->dist_uri    = $this->plugin_uri . '/assets/dist/';

        $this->hooks();
    }

    /**
     * Add plugin hooks and filters.
     */
    protected function hooks() {
        add_action( 'init', \Closure::fromCallable( [ $this, 'load_localization' ] ), 0 );
        add_action( 'init', \Closure::fromCallable( [ $this, 'init_classes' ] ), 0 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_filter( 'pll_get_post_types', \Closure::fromCallable( [ $this, 'add_cpt_to_polylang' ] ), 10, 2 );
        add_filter( 'pll_get_taxonomies', \Closure::fromCallable( [ $this, 'add_tax_to_polylang' ] ), 10, 2 );
        add_filter( 'dustpress/models', \Closure::fromCallable( [ $this, 'dustpress_models' ] ) );
        add_filter( 'dustpress/partials', \Closure::fromCallable( [ $this, 'dustpress_partials' ] ) );
        add_filter( 'page_template', \Closure::fromCallable( [ $this, 'register_page_template_path' ] ) );
        add_filter( 'theme_page_templates', \Closure::fromCallable( [ $this, 'register_page_template' ] ) );
        add_filter( 'query_vars', \Closure::fromCallable( [ $this, 'add_material_search_query_var' ] ) );
        add_filter(
            'tms/acf/field/accordion_section_content/layouts',
            \Closure::fromCallable( [ $this, 'append_accordion_file_layout' ] )
        );
        add_filter(
            'tms/acf/layout/accordion_file/data',
            \Closure::fromCallable( [ $this, 'format_accordion_file_data' ] )
        );

        add_filter(
            'upload_mimes',
            \Closure::fromCallable( [ $this, 'modify_upload_mimes' ] ),
        );

        add_filter(
            'tms/theme/gutenberg/excluded_templates',
            \Closure::fromCallable( [ $this, 'exclude_gutenberg' ] ),
        );
    }

    /**
     * Load plugin localization
     */
    public function load_localization() {
        load_plugin_textdomain( 'tms-plugin-materials', false, dirname( plugin_basename( __DIR__ ) ) . '/languages/' );
    }

    /**
     * Init classes
     */
    protected function init_classes() {
        ( new Material() );
        ( new MaterialType() );
        ( new MaterialBlock() );
        ( new PageMaterialsFieldGroup() );
    }

    /**
     * Enqueue public side scripts if they exist.
     */
    public function enqueue_public_scripts() {
        if ( file_exists( $this->dist_path . 'public.js' ) ) {
            wp_enqueue_script(
                'tms-plugin-materials-public-js',
                $this->dist_uri . 'public.js',
                [ 'jquery' ],
                $this->mod_time( 'public.js' ),
                true
            );
        }
    }

    /**
     * Enqueue admin side scripts if they exist.
     */
    public function enqueue_admin_scripts() {
        if ( file_exists( $this->dist_path . 'admin.css' ) ) {
            wp_enqueue_style(
                'tms-plugin-materials-admin-css',
                $this->dist_uri . 'admin.css',
                [],
                $this->mod_time( 'admin.css' ),
                'all'
            );
        }

        if ( file_exists( $this->dist_path . 'admin.js' ) ) {
            wp_enqueue_script(
                'tms-plugin-materials-admin-js',
                $this->dist_uri . 'admin.js',
                [ 'jquery' ],
                $this->mod_time( 'admin.js' ),
                true
            );
        }
    }

    /**
     * Get cache busting modification time or plugin version.
     *
     * @param string $file File inside assets/dist/ folder.
     *
     * @return int|string
     */
    private function mod_time( $file = '' ) {
        return file_exists( $this->dist_path . $file )
            ? (int) filemtime( $this->dist_path . $file )
            : $this->version;
    }

    /**
     * Add plugin post types to Polylang
     *
     * @param array $post_types Registered post types.
     *
     * @return array
     */
    protected function add_cpt_to_polylang( $post_types ) {
        $post_types[ Material::SLUG ] = Material::SLUG;

        return $post_types;
    }

    /**
     * This adds the taxonomies that are not public to Polylang translation.
     *
     * @param array   $tax_types   The taxonomy type array.
     * @param boolean $is_settings A not used boolean flag to see if we're in settings.
     *
     * @return array The modified tax_types -array.
     */
    protected function add_tax_to_polylang( $tax_types, $is_settings ) : array { // phpcs:ignore
        $tax_types[ MaterialType::SLUG ] = MaterialType::SLUG;

        return $tax_types;
    }

    /**
     * Add this plugin's models directory to DustPress.
     *
     * @param array $models The original array.
     *
     * @return array
     */
    protected function dustpress_models( array $models = [] ) : array {
        $models[] = $this->plugin_path . '/src/Models/';

        return $models;
    }

    /**
     * Add this plugin's partials directory to DustPress.
     *
     * @param array $partials The original array.
     *
     * @return array
     */
    protected function dustpress_partials( array $partials = [] ) : array {
        $partials[] = $this->plugin_path . '/src/Partials/';

        return $partials;
    }

    /**
     * Register page-materials.php template path.
     *
     * @param string $template Page template name.
     *
     * @return string
     */
    private function register_page_template_path( string $template ) : string {
        if ( get_page_template_slug() === 'page-materials.php' ) {
            $template = $this->plugin_path . '/src/Models/page-materials.php';
        }

        return $template;
    }

    /**
     * Register page-materials.php making it accessible via page template picker.
     *
     * @param array $templates Page template choices.
     *
     * @return array
     */
    private function register_page_template( $templates ) : array {
        $templates['page-materials.php'] = __( 'Materiaalikirjasto' );

        return $templates;
    }

    /**
     * Add Materials search query var to query_vars.
     *
     * @param array $query_vars WP Query variables.
     *
     * @return mixed
     */
    protected function add_material_search_query_var( $query_vars ) {
        $query_vars[] = \PageMaterials::SEARCH_QUERY_VAR;

        return $query_vars;
    }

    /**
     * Append accordion file layout
     *
     * @param array $layouts Flexible Content layouts.
     *
     * @return array
     */
    protected function append_accordion_file_layout( array $layouts ) : array {
        $layouts[] = AccordionFileLayout::class;

        return $layouts;
    }

    /**
     * Format accordion file data.
     *
     * @param array $data Layout data.
     *
     * @return array
     */
    protected function format_accordion_file_data( array $data ) : array {
        $materials     = ! empty( $data['materials'] ) ? $data['materials'] : [];
        $data['items'] = static::format_file_items( $materials );

        return $data;
    }

    /**
     * Format files.
     *
     * @param array $material_ids Material IDs.
     *
     * @return array
     */
    public static function format_file_items( array $material_ids ) : array {
        return array_filter(
            array_map( function ( $id ) {
                $file = get_field( 'file', $id );

                if ( empty( $file ) ) {
                    return false;
                }

                $button_sr_text = sprintf(
                    ' <span class="is-sr-only">%s %s</span>',
                    __( 'Clicking the link will download file', 'tms-plugin-materials' ),
                    $file['title']
                );
                $button_text    = __( 'Open', 'tms-plugin-materials' ) . $button_sr_text;

                return [
                    'url'         => $file['url'],
                    'title'       => get_the_title( $id ),
                    'filesize'    => size_format( $file['filesize'], 2 ),
                    'filetype'    => $file['subtype'],
                    'description' => wp_kses_post( get_field( 'description', $id ) ),
                    'image'       => ! empty( get_field( 'image', $id ) )
                        ? get_field( 'image', $id )
                        : apply_filters( 'tms/theme/settings/material_default_image', '' ),
                    'button_text' => $button_text,
                ];
            }, $material_ids )
        );
    }

    /**
     * Allow zip upload
     *
     * @param array $mimes Array of mime types.
     *
     * @return array
     */
    protected function modify_upload_mimes( array $mimes = [] ) : array {
        $mimes['zip'] = 'application/zip';
        $mimes['gz']  = 'application/x-gzip';

        return $mimes;
    }

    /**
     * Exclude Gutenberg editor
     *
     * @param array $templates Array of templates.
     *
     * @return array
     */
    protected function exclude_gutenberg( array $templates ) : array {
        $templates[] = \PageMaterials::TEMPLATE;

        return $templates;
    }
}

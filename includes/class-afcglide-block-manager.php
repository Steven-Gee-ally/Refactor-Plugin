<?php
namespace AFCGlide\Listings;

/**
 * AFCGlide Block Manager
 * Registers Gutenberg blocks that connect to our shortcode system
 * * @package AFCGlide\Listings
 * @since 3.6.6
 */


use AFCGlide\Listings\Helpers\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Block_Manager {

    const BLOCK_SCRIPT_HANDLE = 'afcglide-block-editor';
    const BLOCK_NAMESPACE = 'afcglide';

    /**
     * Initialize the block manager
     * Called by Main Plugin File during 'init'
     */
    public static function init() {
        // Fire registration immediately since we are already on the 'init' hook
        self::register_blocks();
    }

    /**
     * Register all AFCGlide Gutenberg blocks
     */
    public static function register_blocks() {
        // 1. Setup the Script (Editor only)
        wp_register_script(
            self::BLOCK_SCRIPT_HANDLE,
            false, 
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ],
            AFCG_VERSION,
            false
        );

        wp_add_inline_script(
            self::BLOCK_SCRIPT_HANDLE,
            self::get_listings_grid_js()
        );

        // 2. Register the Block Type
        register_block_type( self::BLOCK_NAMESPACE . '/listings-grid', [
            'editor_script'   => self::BLOCK_SCRIPT_HANDLE,
            'render_callback' => [ __CLASS__, 'render_listings_grid' ],
            'attributes'      => self::get_listings_grid_attributes(),
        ]);
    }

    /**
     * Attributes Schema
     */
    private static function get_listings_grid_attributes() {
        return [
            'postsToShow' => [ 'type' => 'number', 'default' => 6 ],
            'columns'     => [ 'type' => 'number', 'default' => 3 ],
        ];
    }

    /**
     * JavaScript for the Block Editor
     */
    private static function get_listings_grid_js() {
        // We use a HEREDOC for cleaner JS formatting inside PHP
        return <<<JS
(function(blocks, element, components) {
    'use strict';
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = wp.editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    
    blocks.registerBlockType('afcglide/listings-grid', {
        title: 'AFCGlide Listings Grid',
        description: 'Display a grid of real estate listings',
        icon: 'admin-home',
        category: 'widgets',
        attributes: {
            postsToShow: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 }
        },
        edit: function(props) {
            var attrs = props.attributes;
            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Grid Settings', initialOpen: true },
                        el(RangeControl, {
                            label: 'Posts to Show',
                            value: attrs.postsToShow,
                            onChange: function(val) { props.setAttributes({ postsToShow: val }); },
                            min: 1, max: 24
                        }),
                        el(RangeControl, {
                            label: 'Columns',
                            value: attrs.columns,
                            onChange: function(val) { props.setAttributes({ columns: val }); },
                            min: 1, max: 4
                        })
                    )
                ),
                el('div', { className: 'afcglide-block-preview', style: { padding: '20px', border: '2px dashed #0073aa', background: '#f0f6fc', textAlign: 'center' } },
                    el('div', { style: { fontSize: '30px' } }, '🏠'),
                    el('h3', { style: { color: '#0073aa' } }, 'AFCGlide Listings Grid'),
                    el('p', {}, 'Showing ' + attrs.postsToShow + ' listings in ' + attrs.columns + ' columns')
                )
            );
        },
        save: function() { return null; }
    });
})(window.wp.blocks, window.wp.element, window.wp.components);
JS;
    }

    /**
     * Server-side Render
     */
    public static function render_listings_grid( $attributes ) {
        $posts_to_show = isset( $attributes['postsToShow'] ) ? Sanitizer::int( $attributes['postsToShow'] ) : 6;
        $columns = isset( $attributes['columns'] ) ? Sanitizer::int( $attributes['columns'] ) : 3;
        
        // Final sanity check on ranges
        $posts_to_show = max( 1, min( 24, $posts_to_show ) );
        $columns = max( 1, min( 4, $columns ) );
        
        return do_shortcode( sprintf(
            '[afcglide_listings_grid posts_per_page="%d" columns="%d"]',
            $posts_to_show,
            $columns
        ));
    }
}
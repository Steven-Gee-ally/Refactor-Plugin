<?php
/**
 * AFCGlide Block Manager
 * Registers Gutenberg blocks that connect to our shortcode system
 * 
 * @package AFCGlide\Listings
 * @since 3.6.6
 */

namespace AFCGlide\Listings;

use AFCGlide\Listings\Helpers\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Block_Manager {

    /**
     * Block handle for script registration
     */
    const BLOCK_SCRIPT_HANDLE = 'afcglide-block-editor';
    
    /**
     * Block namespace
     */
    const BLOCK_NAMESPACE = 'afcglide';

    /**
     * Initialize the block manager
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_blocks' ] );
    }

    /**
     * Register all AFCGlide Gutenberg blocks
     */
    public static function register_blocks() {
        self::register_listings_grid_block();
    }

    /**
     * Register the listings grid block
     */
    private static function register_listings_grid_block() {
        // Register inline-only script with proper dependencies
        wp_register_script(
            self::BLOCK_SCRIPT_HANDLE,
            false, // No external file - inline only
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ],
            AFCG_VERSION ?? '3.6.6',
            false
        );

        // Add inline JavaScript for block registration
        wp_add_inline_script(
            self::BLOCK_SCRIPT_HANDLE,
            self::get_listings_grid_js()
        );

        // Register the block type
        register_block_type( self::BLOCK_NAMESPACE . '/listings-grid', [
            'editor_script'   => self::BLOCK_SCRIPT_HANDLE,
            'render_callback' => [ __CLASS__, 'render_listings_grid' ],
            'attributes'      => self::get_listings_grid_attributes(),
        ]);
    }

    /**
     * Get attributes schema for listings grid block
     * 
     * @return array Block attributes configuration
     */
    private static function get_listings_grid_attributes() {
        return [
            'postsToShow' => [
                'type'    => 'number',
                'default' => 6,
            ],
            'columns' => [
                'type'    => 'number',
                'default' => 3,
            ],
        ];
    }

    /**
     * Generate JavaScript for listings grid block
     * 
     * @return string JavaScript code
     */
    private static function get_listings_grid_js() {
        return "
(function(blocks, element, components) {
    'use strict';
    
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = wp.editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    
    blocks.registerBlockType('" . self::BLOCK_NAMESPACE . "/listings-grid', {
        title: 'AFCGlide Listings Grid',
        description: 'Display a grid of real estate listings',
        icon: 'admin-home',
        category: 'widgets',
        keywords: ['listings', 'real estate', 'properties', 'afcglide'],
        
        attributes: {
            postsToShow: { 
                type: 'number', 
                default: 6 
            },
            columns: { 
                type: 'number', 
                default: 3 
            }
        },
        
        edit: function(props) {
            var attrs = props.attributes;
            var setAttrs = props.setAttributes;
            
            return el(
                Fragment,
                {},
                // Inspector Controls (Sidebar)
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { 
                            title: 'Grid Settings',
                            initialOpen: true 
                        },
                        el(RangeControl, {
                            label: 'Posts to Show',
                            value: attrs.postsToShow,
                            onChange: function(val) { 
                                setAttrs({ postsToShow: val }); 
                            },
                            min: 1,
                            max: 24,
                            step: 1
                        }),
                        el(RangeControl, {
                            label: 'Columns',
                            value: attrs.columns,
                            onChange: function(val) { 
                                setAttrs({ columns: val }); 
                            },
                            min: 1,
                            max: 4,
                            step: 1
                        })
                    )
                ),
                // Block Preview
                el(
                    'div',
                    { 
                        className: 'afcglide-block-preview',
                        style: { 
                            padding: '30px 20px',
                            border: '2px dashed #0073aa',
                            borderRadius: '8px',
                            background: '#f0f6fc',
                            textAlign: 'center',
                            color: '#0073aa'
                        }
                    },
                    el('div', { 
                        style: { 
                            fontSize: '48px', 
                            marginBottom: '10px' 
                        } 
                    }, '🏠'),
                    el('h3', { 
                        style: { 
                            margin: '0 0 10px 0',
                            color: '#0073aa'
                        } 
                    }, 'AFCGlide Listings Grid'),
                    el('p', { 
                        style: { 
                            margin: '0',
                            opacity: '0.8'
                        } 
                    }, 'Displaying ' + attrs.postsToShow + ' properties in ' + attrs.columns + ' columns')
                )
            );
        },
        
        save: function() {
            // Dynamic block - rendered server-side
            return null;
        }
    });
    
})(window.wp.blocks, window.wp.element, window.wp.components);
        ";
    }

    /**
     * Render the listings grid block on the frontend
     * 
     * @param array $attributes Block attributes
     * @return string Rendered HTML
     */
    public static function render_listings_grid( $attributes ) {
        // Sanitize inputs
        $posts_to_show = isset( $attributes['postsToShow'] ) 
            ? Sanitizer::int( $attributes['postsToShow'] ) 
            : 6;
            
        $columns = isset( $attributes['columns'] ) 
            ? Sanitizer::int( $attributes['columns'] ) 
            : 3;
        
        // Validate ranges
        $posts_to_show = max( 1, min( 24, $posts_to_show ) );
        $columns = max( 1, min( 4, $columns ) );
        
        // Build shortcode with sanitized attributes
        $shortcode = sprintf(
            '[afcglide_listings_grid posts_per_page="%d" columns="%d"]',
            $posts_to_show,
            $columns
        );
        
        return do_shortcode( $shortcode );
    }
}
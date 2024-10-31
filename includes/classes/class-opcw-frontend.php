<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'OPCW_Frontend' ) ) :

	/**
	 * Main OPCW_Frontend Class.
	 *
	 * @package		OPCW
	 * @subpackage	Classes/OPCW_Frontend
	 * @since		1.0.0
	 * @author		WPOPAL
	 */
	final class OPCW_Frontend {

        /**
		 * OPCW settings object.
		 *
		 * @access	private
		 * @since	1.0.0
		 */
		private $settings;
        
        /**
		 * OPCW settings_data.
		 *
		 * @access	private
		 * @since	1.0.0
		 */
		private $settings_data;

        public function __construct($settings) {
            // Settings object
			$this->settings = $settings;
            $this->settings_data = $this->settings->opcw_get_settings_data();
            
            // Run in frontend
            $this->opcw_add_filter();
            $this->opcw_add_action();

            // Add shortcode
            add_shortcode( 'opcw', [ $this, 'opcw_shortcode' ] );
        }

        /**
		 *  Call View Fontend Template
		 */
		public static function view($view, $data = array()) {
			extract($data);
			$path_view = apply_filters('opcw_path_view_fontend', OPCW_PLUGIN_DIR . 'views/frontend/' . $view . '.php', $view, $data);
			include($path_view);
		}
        
        /**
		 * OPCW add action hook.
		 *
		 * @access	private
		 * @since	1.0.0
		 */
        private function opcw_add_action() {
            $render_hook = $this->opcw_handle_render_hook_product();
            if ($render_hook) {
                add_action( $render_hook['hook_action'], [$this, 'opcw_woocommerce_output_related_products_collection'], absint($render_hook['prioty']) ); 
            }

            add_action( 'wp_head', [$this, 'opcw_collection_opengraph'], 5 );
            add_action( 'woocommerce_product_meta_end', [$this, 'opcw_show_collection_in_product_meta'] );
        }
        
        /**
         * OPCW add filter hook.
		 *
         * @access	private
		 * @since	1.0.0
		 */
        private function opcw_add_filter() {
            add_filter('opcw_trigger_shortcode_related_collections', [$this, 'opcw_custom_atts_shorcode_related_collections']);
            add_filter('opcw_related_products_collections_heading', [$this, 'opcw_custom_heading_related_collections']);
        }

        /**
         * OPCW add shortcode
         *
         * @access  public
         * @since   1.0.0
         */
        public function opcw_shortcode($atts) {
            $atts = shortcode_atts(
                array(
                    'collection-id'  => '',
                    'limit'          => '4', 
                    'excludes'       => '',
                    'columns'        => '', 
                    'orderby'        => '', 
                    'order'          => '', 
                    'class'          => '', 
                    'page'           => 1,
                    'paginate'       => false,
                    'cache'          => false,
                ),
                $atts,
                'opcw'
            );

            if (empty($atts['collection-id'])) {
                if (is_tax( OPCW_TAXONOMY )) {
                    $term = get_queried_object();
                    $term_id = $term->term_id;

                }
                else {
                    return;
                }
            }
            else {
                $term_id = $atts['collection-id'];
            }

            $excludes = [];
            if (!empty($atts['excludes'])) {
                $excludes = explode(',', $atts['excludes']);
            }

            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'product',
                'post_status' => 'publish',
                'post__not_in' => $excludes,
                'tax_query' => array(
                    array(
                        'taxonomy' => OPCW_TAXONOMY,
                        'terms' => $term_id
                    ),
                ),
            );
            $c_query = new WP_Query( $args );

            if ($c_query->have_posts()) {
                $post_ids = wp_list_pluck( $c_query->posts, 'ID' );
                $post_ids = implode(",", $post_ids);
            }
            else {
                wc_no_products_found();
                return;
            }
            wp_reset_postdata();
            wc_reset_loop();

            $atts['ids'] = $post_ids;

            $atts = apply_filters('opcw_trigger_attributes_shortcode', $atts);

            $shortcode = new WC_Shortcode_Products( (array) $atts, 'product' );

            return $shortcode->get_content();
        }

        public function opcw_woocommerce_output_related_products_collection() {
            if (!is_singular('product')) {
                return;
            }

            global $product;
            $product_id = $product->get_id();
            $terms = wp_get_post_terms($product_id, OPCW_TAXONOMY);

            if (is_wp_error($terms) || empty($terms)) {
                return;
            }
            

            $collection_id = $terms[0]->term_id;

            $settings_data = $this->settings_data;
			$wrap_into_container = opcw_get_option('wrap_into_container', false, $settings_data);

            self::view('related-collection', [
                'collection_id' => $collection_id, 
                'product_id' => $product_id,
                'wrap_into_container' => $wrap_into_container,
            ]);
        }

        public function opcw_collection_opengraph() {
			$settings_data = $this->settings_data;
			$show_seo_data = opcw_get_option('show_seo_data', false, $settings_data);
			$module_in_taxs = $this->opcw_get_taxonomies_settings();
			if ($show_seo_data) {
				$term = get_queried_object();
				if (isset($term->taxonomy) && in_array($term->taxonomy, $module_in_taxs)) {
					// opcw_p($term);
					$og_logo_size = opcw_get_option('og_logo_size', 'large', $settings_data);
					$og_logo_link = wp_get_attachment_image( get_term_meta( $term->term_id, 'opcw_logo', true ), $og_logo_size );
					?>
					<meta property="og:title" content="<?php echo esc_html($term->name); ?>"/>
					<meta property="og:description" content="<?php echo esc_html($term->description); ?>"/>
					<meta property="og:type" content="og:product"/>
					<meta property="og:url" content="<?php echo esc_url(get_term_link($term)); ?>"/>
					<meta property="og:site_name" content="<?php echo esc_html(get_bloginfo()); ?>"/>
					<meta property="og:image" content="<?php echo esc_url($og_logo_link); ?>"/>
					<?php
				}
			}
		}
        
        public function opcw_custom_atts_shorcode_related_collections($atts) {
            $settings_data = $this->settings_data;

            $limit = opcw_get_option('product_limit_display', false, $settings_data);
            if ($limit && absint($limit) > 0) {
                $atts .= ' limit="'.$limit.'"';
            }

            return $atts;
		}
        
        public function opcw_custom_heading_related_collections($heading) {
            $settings_data = $this->settings_data;

            $heading = opcw_get_option('title_more_in_collection', '', $settings_data);

            return $heading;
		}
        
        public function opcw_show_collection_in_product_meta() {
            $settings_data = $this->settings_data;
            $show_collection_in_meta = opcw_get_option('show_collection_in_meta', false, $settings_data);

            if (!$show_collection_in_meta) {
                return;
            }

            global $product;
            $terms = get_the_terms( $product->get_id(), OPCW_TAXONOMY );
            if (is_wp_error($terms) || empty($terms)) return;

            echo wp_kses_post(opcw_get_product_collections_list( 
                $product->get_id(), 
                ', ', 
                '<span class="posted_in"><span class="meta-label">' . _n( 'Collection:', 'Collections:', 
                count( $terms ), 'opal-product-collection-woocommerce' ) . '</span> ', 
                '</span>' ));
		}

        private function opcw_handle_render_hook_product() {
            $settings_data = $this->settings_data;
            $render_hook = opcw_get_option('product_render_position', '', $settings_data);

            if (empty($render_hook)) return false;

            $options = explode( '-', $render_hook ) ;
            $hook_action = $options[0];
            $prioty = isset($options[1]) ? $options[1] : 20;

            $render_prioty = opcw_get_option('render_position_prioty', false, $settings_data);
            if ($render_prioty && $render_prioty != '') {
                $prioty = $render_prioty;
            }

            return [
                'hook_action' => $hook_action,
                'prioty' => $prioty
            ];
        }

        private function opcw_get_taxonomies_settings() {
			$settings_data = $this->settings_data;
			$module_in_taxs = opcw_get_option('module_in_taxs', false, $settings_data);
			return (!$module_in_taxs) ? [] : $module_in_taxs;
		}
    }
endif;
<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'OPCW_Admin' ) ) :

	/**
	 * Main OPCW_Admin Class.
	 *
	 * @package		OPCW
	 * @subpackage	Classes/OPCW_Admin
	 * @since		1.0.0
	 * @author		WPOPAL
	 */
	final class OPCW_Admin {

		/**
		 * OPCW settings object.
		 *
		 * @access	private
		 * @since	1.0.0
		 */
		private $settings;

		private $product_query;

        public function __construct($settings)
        {
            // Create product meta
            OPCW_Meta::instance();

			// Settings object
			$this->settings = $settings;

			add_action( OPCW_CRON_HOOK, array($this, 'opcw_run_schedule_scan') );

			add_action( 'woocommerce_init', [ $this, 'opcw_tax_init' ] );
			add_action( 'widgets_init', [$this, 'opcw_register_widgets'] );
			add_filter( 'admin_body_class', [ $this, 'opcw_admin_body_classes' ] );
			
			$module_in_taxs = $this->opcw_get_taxonomies_settings();
			if (!empty($module_in_taxs)) {
				foreach ($module_in_taxs as $tax) {
					add_action( $tax.'_add_form_fields', [ $this, 'opcw_add_form_fields' ], 99 );
					add_action( $tax.'_edit_form_fields', [ $this, 'opcw_edit_form_fields' ], 99 );
					add_action( 'edit_'.$tax, [ $this, 'opcw_save_collections' ] );
					add_action( 'create_'.$tax, [ $this, 'opcw_save_collections' ] );
					add_action( "after-{$tax}-table", [$this, 'opcw_add_custom_script_tax_table'] );
					add_filter( $tax.'_row_actions', [ $this, 'opcw_collection_row_actions' ], 10, 3 );
					add_filter( 'bulk_actions-edit-'.$tax, [$this, 'opcw_add_custom_bulk_action'] );
					add_filter( 'handle_bulk_actions-edit-'.$tax, [ $this, 'opcw_handle_custom_bulk_action' ], 10, 3 );

					// Exclude Product Cat
					if ($tax != 'product_cat') {
						add_filter( 'manage_edit-'.$tax.'_columns', [ $this, 'opcw_collection_columns' ] );
						add_filter( 'manage_'.$tax.'_custom_column', [ $this, 'opcw_collection_columns_content' ], 10, 3 );
					}
				}
			}

			add_action(	'save_post_product', [$this, 'opcw_trigger_save_product'], 10 );
			add_action( 'admin_notices', array( $this, 'opcw_remove_output_custom_notices' ), 999 );
			add_filter( 'manage_edit-product_columns', [$this, 'opcw_add_cpt_columns'] );

			add_action( 'admin_footer', [$this, 'opcw_mask_loading' ] );

			add_action( 'wp_ajax_opcw_load_rule_apply_ajax', [$this, 'opcw_load_rule_apply_ajax'] ); // wp_ajax_{action}
			
			add_action( 'wp_ajax_opcw_rescan_collection', [$this, 'opcw_rescan_collection'] );
			add_action( 'wp_ajax_opcw_stop_scanning_collection', [$this, 'opcw_stop_scanning_collection'] );
			
			add_action( 'wp_ajax_opcw_collection_export', [$this, 'opcw_collection_export'] ); // wp_ajax_{action}
			add_action( 'wp_ajax_opcw_handle_import_collection', [$this, 'opcw_handle_import_collection'] );

			add_filter( 'opcw_backend_data_localize', [$this, 'opcw_custom_backend_data_localize'] );
        }

		public function opcw_custom_backend_data_localize($datas_localize) {
			global $pagenow;
			if ($pagenow == 'edit-tags.php' && !empty($_GET['taxonomy'])) {
				$module_in_taxs = $this->opcw_get_taxonomies_settings();
				if (in_array($_GET['taxonomy'], $module_in_taxs)) {
					$datas_localize['export_link'] = $this->opcw_get_export_term_link('all', $_GET['taxonomy']);
				}
			}
			return $datas_localize;
		}

        /**
		 *  Call View Admin Template
		 */
		public static function view($view, $data = array()) {
			extract($data);
			$path_view = apply_filters('opcw_path_view_admin', OPCW_PLUGIN_DIR . 'views/backend/' . $view . '.php', $view, $data);
			include($path_view);
		}

		public static function convert_conditions(array $conditions) {
			if (empty($conditions)) return $conditions;

			foreach ($conditions as $i => $item) {
				if ($item['rule_item'] == 'product_category') {
					$conditions[$i]['rule_item'] = 'product_cat';
				}
			}

			return $conditions;
		}

		public function opcw_run_schedule_scan() {
			// update_option( 'opcw_scan_now', date('Y-m-d H:i:s', time()) );
			$is_scan = get_option('opcw_is_schedule_scan', 0);
			if ($is_scan) return;
			
			update_option( 'opcw_is_schedule_scan', 1 );

			$module_in_taxs = $this->opcw_get_taxonomies_settings();
			if (empty($module_in_taxs)) return;

			foreach ($module_in_taxs as $tax) {
				$terms = get_terms( array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
				) );

				if (is_wp_error($terms) || empty($terms)) continue;

				$taxonomy = $tax;
				foreach ($terms as $term) {
					$term_id = $term->term_id;

					$relation  = get_term_meta( $term_id, 'opcw_condition_relation', true );
					$relation  = $relation ? $relation : 'all';
		
					$conditions  = get_term_meta( $term_id, 'opcw_conditions', true );
					$conditions  = $conditions ? (array) $conditions : [];
					$conditions  = self::convert_conditions($conditions);
		
					$include     = get_term_meta( $term_id, 'opcw_include_products', true );
					$include  = $include ? $include : [];
					
					$exclude     = get_term_meta( $term_id, 'opcw_exclude_products', true );
					$exclude  = $exclude ? $exclude : [];

					$term_data = [
						'relation' => $relation,
						'conditions' => $conditions,
						'include' => $include,
						'exclude' => $exclude,
					];

					$paged = 1;
					$this->opcw_handler_schedule_scan($term_id, $taxonomy, $paged, $term_data);
				}
			}

			update_option( 'opcw_is_schedule_scan', 0 );
		}

		private function opcw_handler_schedule_scan($term_id, $taxonomy, $paged, $term_data) {
			if (opcw_get_option('scan_option_schedule', 'new') == 'all') {
				$query = self::opcw_get_query_product($paged);
			} 
			else {
				$query = self::opcw_get_query_product($paged, $term_id, $taxonomy);
			}
			
			wc_set_time_limit();
			if ( $query->have_posts() ) {
				
				extract($term_data);
				$i = 0;
				while ( $query->have_posts() ) {
					$query->the_post();
					$post_id = get_the_ID();

					$product = wc_get_product($post_id);
					$this->opcw_check_tax_contain_product_by_condition($product, $term_id, $relation, $include, $exclude, $conditions, $taxonomy);

					$i++;
				}
				
				wp_reset_postdata();

				$max_paged = $query->max_num_pages;
				if ($max_paged >= $paged) {
					return;
				}
				
				// Run next paged
				$next_paged = $paged + 1;
				$this->opcw_handler_schedule_scan($term_id, $taxonomy, $next_paged, $term_data);
			}
			else {
				return;
			}
		}

		public function opcw_tax_init() {
			$settings_data = $this->settings->opcw_get_settings_data();
			$slug = opcw_get_option('collection_slug', 'collection', $settings_data);

			$labels = [
				'name'                       => esc_html__( 'Collections', 'opal-product-collection-woocommerce' ),
				'singular_name'              => esc_html__( 'Collection', 'opal-product-collection-woocommerce' ),
				'menu_name'                  => esc_html__( 'OPCW Collections', 'opal-product-collection-woocommerce' ),
				'all_items'                  => esc_html__( 'All Collections', 'opal-product-collection-woocommerce' ),
				'edit_item'                  => esc_html__( 'Edit Collection', 'opal-product-collection-woocommerce' ),
				'view_item'                  => esc_html__( 'View Collection', 'opal-product-collection-woocommerce' ),
				'update_item'                => esc_html__( 'Update Collection', 'opal-product-collection-woocommerce' ),
				'add_new_item'               => esc_html__( 'Add New Collection', 'opal-product-collection-woocommerce' ),
				'new_item_name'              => esc_html__( 'New Collection Name', 'opal-product-collection-woocommerce' ),
				'parent_item'                => esc_html__( 'Parent Collection', 'opal-product-collection-woocommerce' ),
				'parent_item_colon'          => esc_html__( 'Parent Collection:', 'opal-product-collection-woocommerce' ),
				'search_items'               => esc_html__( 'Search Collections', 'opal-product-collection-woocommerce' ),
				'popular_items'              => esc_html__( 'Popular Collections', 'opal-product-collection-woocommerce' ),
				'back_to_items'              => esc_html__( '&larr; Go to Collections', 'opal-product-collection-woocommerce' ),
				'separate_items_with_commas' => esc_html__( 'Separate collections with commas', 'opal-product-collection-woocommerce' ),
				'add_or_remove_items'        => esc_html__( 'Add or remove collections', 'opal-product-collection-woocommerce' ),
				'choose_from_most_used'      => esc_html__( 'Choose from the most used collections', 'opal-product-collection-woocommerce' ),
				'not_found'                  => esc_html__( 'No collections found', 'opal-product-collection-woocommerce' )
			];

			$args = [
				'hierarchical'       => true,
				'labels'             => $labels,
				'show_ui'            => true,
				'query_var'          => true,
				'public'             => true,
				'publicly_queryable' => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'rewrite'            => [
					'slug'         => apply_filters( 'opcw_taxonomy_slug', $slug ),
					'hierarchical' => true,
					'with_front'   => apply_filters( 'opcw_taxonomy_with_front', true )
				]
			];

			register_taxonomy( OPCW_TAXONOMY, [ 'product' ], $args );

			if (get_option('opcw_flush_permalink', false) === 'yes') {
				global $wp_rewrite; 
				$wp_rewrite->flush_rules( true );
				update_option('opcw_flush_permalink', 0);
			}

			require_once dirname( WC_PLUGIN_FILE ) . '/includes/abstracts/abstract-wc-widget.php';
			require_once OPCW_PLUGIN_DIR.'/includes/widgets/class-opcw-widget-product-collections.php';

		}

		public function opcw_register_widgets() {
			register_widget( 'OPCW_Widget_Product_Collections' );
		}

		public function opcw_admin_body_classes( $classes ) {
			global $current_screen;
		
			$module_in_taxs = $this->opcw_get_taxonomies_settings();
			if (isset($current_screen->taxonomy) && in_array($current_screen->taxonomy, $module_in_taxs)) {
				$classes .= ' opcw_show_module_scan';
			}
		
			return $classes;
		}

		public function opcw_trigger_save_product($post_id) {
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
			if (empty($_REQUEST['action'])) return;
			if ($_REQUEST['action'] == 'editpost') {
				check_admin_referer( 'update-post_' . $post_id );
			}
			else {
				return;
			}
			if (!isset($_REQUEST['_wp_http_referer']) || !opcw_check_string_has_char(wc_clean($_REQUEST['_wp_http_referer']), '/wp-admin/post-new.php')) return;

			$module_in_taxs = $this->opcw_get_taxonomies_settings();
			if (!empty($module_in_taxs)) {
				foreach ($module_in_taxs as $tax) {
					$terms = get_terms( array(
						'taxonomy'   => $tax,
						'hide_empty' => false,
					) );

					if (!is_wp_error($terms) && !empty($terms)) {
						foreach ($terms as $term) {
							$term_id = $term->term_id;
							$taxonomy = $tax;
							
							$relation = get_term_meta( $term_id, 'opcw_condition_relation', true );
							$relation = $relation ? $relation : 'all';

							$conditions = get_term_meta( $term_id, 'opcw_conditions', true );
							$conditions = $conditions ? (array) $conditions : [];
							$conditions = self::convert_conditions($conditions);

							$include     = get_term_meta( $term_id, 'opcw_include_products', true );
							$include  = $include ? $include : [];
							
							$exclude     = get_term_meta( $term_id, 'opcw_exclude_products', true );
							$exclude  = $exclude ? $exclude : [];

							$product = wc_get_product($post_id);

							// Handle whether this product match the term's conditions or not?
							$match = $this->opcw_check_tax_contain_product_by_condition($product, $term_id, $relation, $include, $exclude, $conditions, $taxonomy);
							if ($match) {
								/* translators: %s: Term name. */
								$message = sprintf(__('<p>This product matchs the condition of term: %s</p>', 'opal-product-collection-woocommerce'), esc_html($term->name));
								$adminnotice = new WC_Admin_Notices();
								$adminnotice->add_custom_notice("term-match-".$post_id.'-'.$term_id, $message);
								$adminnotice->output_custom_notices();
							}
						}
					}
				}				
			}

			// Keep the notice is created above still alive
			add_filter( 'redirect_post_location', array( $this, 'opcw_add_notice_query_var' ), 99 );
		}

		public function opcw_add_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'opcw_add_notice_query_var' ), 99 );
			return add_query_arg( array( 'opcw-show-notice' => 'yes' ), $location );
		}

		public function opcw_remove_output_custom_notices() {
			if (!isset($_GET['opcw-show-notice']) || wc_clean($_GET['opcw-show-notice']) != 'yes') {
				$notices = WC_Admin_Notices::get_notices();

				if(!empty($notices)) {
					foreach ($notices as $notice_key) {
						if (opcw_check_string_start_with_char($notice_key, 'term-match-')) {
							WC_Admin_Notices::remove_notice($notice_key);
						}
					}
				}
			}

			if (isset($_GET['opcw-show-notice-term']) && wc_clean($_GET['opcw-show-notice-term']) == 'yes') {
				$notices = WC_Admin_Notices::output_custom_notices();
			}
			else {
				$notices = WC_Admin_Notices::get_notices();
				if(!empty($notices)) {
					foreach ($notices as $notice_key) {
						if (opcw_check_string_start_with_char($notice_key, 'term-exists-')) {
							WC_Admin_Notices::remove_notice($notice_key);
						}
					}
				}
			}
			// opcw_p($notices);
		}

		public function opcw_collection_columns( $columns ) {
			return [
				'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : 'cb',
				'logo'        => esc_html__( 'Logo', 'opal-product-collection-woocommerce' ),
				'name'        => esc_html__( 'Name', 'opal-product-collection-woocommerce' ),
				'description' => esc_html__( 'Description', 'opal-product-collection-woocommerce' ),
				'slug'        => esc_html__( 'Slug', 'opal-product-collection-woocommerce' ),
				'posts'       => esc_html__( 'Count', 'opal-product-collection-woocommerce' ),
			];
		}

		public function opcw_collection_columns_content( $column, $column_name, $term_id ) {
			if ( $column_name === 'logo' ) {
				$image = wp_get_attachment_image( get_term_meta( $term_id, 'opcw_logo', true ), [
					'40',
					'40'
				] );

				return $image ?: wc_placeholder_img( [ '40', '40' ] );
			}

			return $column;
		}

		public function opcw_add_cpt_columns( $columns ) {
            opcw_swapPos($columns, 'taxonomy-opcw-collection', 'featured');
            return $columns;
        }
        
		public function opcw_collection_row_actions( $actions, $tag ) {
			$tax = get_taxonomy($tag->taxonomy);
			$actions['rescan'] = '<a class="opcw_scan_action" data-name="'.$tag->name.'" data-tax-name="'.$tax->labels->singular_name.'" data-term="'.$tag->term_id.'" href="javascript:void(0)">'.__('Rescan', 'opal-product-collection-woocommerce').'</a>';
			$actions['export'] = '<a href="'.$this->opcw_get_export_term_link($tag->term_id, $tag->taxonomy).'">'.__('Export', 'opal-product-collection-woocommerce').'</a>';
            return $actions;
        }

		private function opcw_get_export_term_link($term_id, $taxonomy, $print = false) {
            if ($print) {
                echo esc_url(admin_url( 'admin-ajax.php' ).'?action=opcw_collection_export&taxonomy='.$taxonomy.'&term_id='.$term_id.'&ajax_nonce_parameter='.wp_create_nonce( "opcw-nonce-ajax" ));
            } else {
                return esc_url(admin_url( 'admin-ajax.php' ).'?action=opcw_collection_export&taxonomy='.$taxonomy.'&term_id='.$term_id.'&ajax_nonce_parameter='.wp_create_nonce( "opcw-nonce-ajax" )); 
            }
        }

		public function opcw_add_custom_bulk_action($actions) {
			$actions['opcw_scan'] = __('Scan', 'opal-product-collection-woocommerce');
			$actions['opcw_export'] = __('Export', 'opal-product-collection-woocommerce');
			
			return $actions;
		}

		public function opcw_handle_custom_bulk_action( $redirect_to, $action, $term_ids ) {
			if ( 'opcw_export' === $action ) {
				$file_data = $this->prepare_term_export( $term_ids );

				if ( is_wp_error( $file_data ) ) {
					return $file_data;
				}

				opcw_send_file_headers( $file_data['name'], strlen( $file_data['content'] ) );

				// Clear buffering just in case.
				@ob_end_clean();

				flush();

				add_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
				// Output file contents.
				echo esc_html($file_data['content']);

				remove_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);

				die;
			}
		}

		public function opcw_add_form_fields() {
			wp_enqueue_media();
			
			global $current_screen;
			$show_logo = !(isset($current_screen->taxonomy) && $current_screen->taxonomy == 'product_cat');

			self::view('collection-field', ['show_logo' => $show_logo]);
		}

		public function opcw_edit_form_fields( $term ) {
			wp_enqueue_media();

			$show_logo = !(isset($term->taxonomy) && $term->taxonomy == 'product_cat');

			self::view('collection-field', ['term' => $term, 'show_logo' => $show_logo] );

			$tax = get_taxonomy($term->taxonomy);
			printf('<input type="hidden" id="opcw_term_name" value="%s">', esc_html($tax->labels->singular_name));
		}

		public function opcw_save_collections( $term_id ) {
			$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );
			$action = $wp_list_table->current_action();
			if ($action == 'add-tag') {
				check_admin_referer( 'add-tag', '_wpnonce_add-tag' );

				$term = get_term($term_id);
				if (!$term || is_wp_error($term)) {
					return;
				}

				$tax = get_taxonomy($term->taxonomy);
				if ( ! current_user_can( $tax->cap->edit_terms ) ) {
					wp_die(
						'<h1>' . esc_html__( 'You need a higher level of permission.', 'opal-product-collection-woocommerce' ) . '</h1>' .
						'<p>' . esc_html__( 'Sorry, you are not allowed to create terms in this taxonomy.', 'opal-product-collection-woocommerce' ) . '</p>',
						403
					);
				}
			}
			elseif ($action == 'editedtag') {
				check_admin_referer( 'update-tag_' . $term_id );
				if ( ! current_user_can( 'edit_term', $term_id ) ) {
					wp_die(
						'<h1>' . esc_html__( 'You need a higher level of permission.', 'opal-product-collection-woocommerce' ) . '</h1>' .
						'<p>' . esc_html__( 'Sorry, you are not allowed to edit this item.', 'opal-product-collection-woocommerce' ) . '</p>',
						403
					);
				}
			}
			else {
				return;
			}
			// Verify


			// opcw_p($_POST); die();
			$taxonomy = get_term($term_id)->taxonomy;
			
			$rule_apply_data = [];
			$i = 0;
			while (isset($_POST['rule_item_'.$i])) {
				$check = true;
				if (empty($_POST['rule_item_'.$i])) $check = false;
				if (empty($_POST['rule_relation_'.$i])) $check = false;

				if (empty($_POST['rule_value_'.$i])) {
					if ($_POST['rule_item_'.$i] != 'product_title') {
						$check = false;
					}
					else {
						$_POST['rule_value_'.$i] = get_term( $term_id )->name;
					}
				}

				if ($check) {
					$rule_apply_data[$i] = [
						'rule_item' => sanitize_text_field($_POST['rule_item_'.$i]),
						'rule_relation' => sanitize_text_field($_POST['rule_relation_'.$i]),
						'rule_value' => (is_array($_POST['rule_value_'.$i])) ? opcw_sanitize_array($_POST['rule_value_'.$i]) : sanitize_text_field($_POST['rule_value_'.$i]),
					];
				}

				$i++;
			}

			update_term_meta( $term_id, 'opcw_conditions', $rule_apply_data );

			if ( isset( $_POST['opcw_logo'] ) ) {
				update_term_meta( $term_id, 'opcw_logo', sanitize_text_field( $_POST['opcw_logo'] ) );
			}
			
			if ( isset( $_POST['opcw_condition_relation'] ) ) {
				update_term_meta( $term_id, 'opcw_condition_relation', sanitize_text_field( $_POST['opcw_condition_relation'] ) );
			}

			if ( isset( $_POST['opcw_include_products'] ) ) {
				$include = opcw_sanitize_array( $_POST['opcw_include_products'] );
				update_term_meta( $term_id, 'opcw_include_products', $include );

				// update products
				if ( is_array( $include ) && count( $include ) ) {
					foreach ( $include as $product_id ) {
						$terms   = wp_get_post_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );
						$terms[] = (int) $term_id;

						wp_set_post_terms( $product_id, $terms, $taxonomy );
					}
				}
			}

			if ( isset( $_POST['opcw_exclude_products'] ) ) {
				$exclude = opcw_sanitize_array( $_POST['opcw_exclude_products'] );
				update_term_meta( $term_id, 'opcw_exclude_products', $exclude );

				// update products
				if ( is_array( $exclude ) && count( $exclude ) ) {
					foreach ( $exclude as $product_id ) {
						wp_remove_object_terms( $product_id, [ (int) $term_id ], $taxonomy);
					}
				}
			}

			// $this->opcw_scan_update_product_collections($term_id);
			// if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// 	if (isset($_POST['action']) && $_POST['action'] == 'add-tag') {
			// 		$this->opcw_scan_update_product_collections($term_id);
			// 	}
			// }
		}

		public function opcw_add_custom_script_tax_table($taxonomy) {
			$tax = get_taxonomy($taxonomy);
			?>
			<div id="opcw_import_collection" style="display: none;">
                <form id="opcw-form-import-collection" class="options_group" method="post" action="<?php echo esc_url(admin_url( 'admin-ajax.php' )) ?>" enctype="multipart/form-data">
                    <div class="opcw_group_option">
                        <img src="<?php echo esc_url(OPCW_PLUGIN_URL.'/assets/images/file-import-solid.svg') ?>" width="50" alt="">
                        <div>
                            <h3>
							<?php 
							/* translators: %s: Taxonomy label. */
							printf(esc_html__('Import %s', 'opal-product-collection-woocommerce'), esc_html($tax->labels->singular_name)); 
							?>
							</h3>
                            <fieldset id="opcw-import-form-collection">
                                <input type="hidden" name="action" value="opcw_handle_import_collection">
                                <input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy) ?>">
                                <?php wp_nonce_field('opcw-nonce-ajax', 'ajax_nonce_parameter');  ?>
                                <div class="opcw_field_wrap">
                                    <input type="file" name="opcw_collection_import" accept=".json,application/json" required="">
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    <div class="opcw_action_button">
                        <button id="opcw_btn_import_collection" type="submit" class="button button-primary"><?php esc_html_e('Upload file and import', 'opal-product-collection-woocommerce') ?></button>
                        <a href="#" id="close_import_collection" class="button"><?php esc_html_e('Close', 'opal-product-collection-woocommerce') ?></a>
                    </div>
                </form>
            </div>
			<?php
		}

		private static function opcw_get_query_product($paged = 1, $term_id = false, $taxonomy = false) {
			$args = [
				'post_type'      => 'product',
				'post_status'    => [ 'publish', 'draft' ],
				'posts_per_page' => apply_filters('opcw_number_product_handle_each_process', 50),
				'paged' => $paged
			];

			if ($term_id !== false && $taxonomy !== false) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy,
						'terms' => [$term_id],
						'operator' => 'NOT IN',
					),
				);	
			}

			$query = new WP_Query( $args );

			return $query;
		}

		private function opcw_check_tax_contain_product_by_condition($product, $term_id, $relation, $include, $exclude, $conditions, $taxonomy = false) {
			$product_id  = $product->get_id();
			$match = false;
			// exclude
			if ( empty( $exclude ) || ! in_array( $product_id, $exclude ) ) {
				// include
				if ( ! empty( $include ) && in_array( $product_id, $include ) ) {
					$match = true;
				} else {
					// conditions
					if ( ! empty( $conditions ) ) {
						$condition_pass = [];
						foreach ( $conditions as $condition ) {
							$item_pass = false;
							$rule_item = $condition['rule_item'];
							$rule_relation = $condition['rule_relation'];
							$rule_value = $condition['rule_value'];

							if ($rule_item == 'stock_status') {
								$stock_status = $product->get_stock_status();
								if ($rule_relation == 'is') {
									$item_pass = $stock_status == $rule_value;
								}
								elseif ($rule_relation == 'is_not') {
									$item_pass = $stock_status != $rule_value;
								}
							}
							if ($rule_item == 'attribute') {
								if ( $product->is_type( 'variable' ) ) {
									$var_arr = opcw_get_variations_of_product($product);
									if ($rule_relation == 'have') {
										$item_pass = in_array($rule_value, $var_arr);
									}
									elseif ($rule_relation == 'not_have') {
										$item_pass = !in_array($rule_value, $var_arr);
									}
								}
							}
							if ($rule_item == 'product_type') {
								$type = $product->get_type();
								if ($product->get_type()) {
									if ($rule_relation == 'is') {
										$item_pass = $type == $rule_value;
									}
									elseif ($rule_relation == 'is_not') {
										$item_pass = $type != $rule_value;
									}
								}
							}
							if ($rule_item == 'product_title') {
								$title = $product->get_title();
								if (empty($rule_value)) {
									$rule_value = get_term( $term_id )->name;
								}
								switch ($rule_relation) {
									case 'contains':
										$item_pass = opcw_check_string_has_char($title, $rule_value);
										break;
									case 'not_contains':
										$item_pass = !opcw_check_string_has_char($title, $rule_value);
										break;
									case 'starts_with':
										$item_pass = opcw_check_string_start_with_char($title, $rule_value);
										break;
									case 'ends_with':
										$item_pass = opcw_check_string_end_with_char($title, $rule_value);
										break;
									default:
										break;
								}
							}
							if ($rule_item == 'price') {
								$rule_value = floatval($rule_value);
								if($product->is_type('variable')){
									$price = $product->get_variation_price();
								}
								elseif($product->is_type('grouped')){
									$children = $product->get_children();
									if (!empty($children)) {
										foreach ( $product->get_children() as $child_id ) {
											$child_price = get_post_meta( $child_id, '_price', true );
											if ($child_price) {
												$child_prices[] = floatval($child_price);
											}
										}
										$price = min( $child_prices );
									}
									else {
										$price = false;
									}
								}
								else {
									$price = $product->get_price();
								}
								if ($price && !is_wp_error($price) && !empty($price)) {
									switch ($rule_relation) {
										case 'is':
											$item_pass = floatval($price) == $rule_value;
											break;
										case 'is_not':
											$item_pass = floatval($price) != $rule_value;
											break;
										case 'is_greater':
											$item_pass = floatval($price) > $rule_value;
											break;
										case 'is_lessthan':
											$item_pass = floatval($price) < $rule_value;
											break;
										case 'is_greater_or_equal':
											$item_pass = floatval($price) >= $rule_value;
											break;
										case 'is_lessthan_or_equal':
											$item_pass = floatval($price) <= $rule_value;
											break;
										default:
											break;
									}
								}
							}
							if ($rule_item == 'product_cat') {
								if ($rule_relation == 'is_in') {
									$item_pass = opcw_check_product_belong_cat_ids($product, $rule_value);
								}
								elseif ($rule_relation == 'is_not_in') {
									$item_pass = !opcw_check_product_belong_cat_ids($product, $rule_value);
								}
							}
							if ($rule_item == 'product_tag') {
								if ($rule_relation == 'is_in') {
									$item_pass = opcw_check_product_belong_tag_ids($product, $rule_value);
								}
								elseif ($rule_relation == 'is_not_in') {
									$item_pass = !opcw_check_product_belong_tag_ids($product, $rule_value);
								}
							}

							$condition_pass[] = $item_pass;
							if ($relation == 'any' && $item_pass) {
								$match = true;
								break;
							}
						}
						if ($relation == 'all') {
							$match = !in_array(false, $condition_pass);
						}
					}
				}
			}

			if ( $match ) {
				// add to collection if matching
				$terms   = wp_get_post_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );
				$terms[] = (int) $term_id;
				wp_set_post_terms( $product_id, $terms, $taxonomy );
			} else {
				// remove from collection
				wp_remove_object_terms( $product_id, [ (int) $term_id ], $taxonomy );
			}

			return $match;
		}

		private function opcw_quick_scan_update_product_collections($term_id, $paged, $taxonomy, $query_condition, $query_params, $scan = 'all', $quick_by = 'product_title') {
			global $wpdb;

			$limit = apply_filters('opcw_number_product_quick_handle_each_process', 50);

			$join = '';
			$and = '';

			if ($quick_by == 'tax') {
				$join .= "INNER JOIN {$wpdb->term_relationships} tr_cat ON p.ID = tr_cat.object_id";
				$join .= " INNER JOIN {$wpdb->term_taxonomy} tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id";
				$join .= " INNER JOIN {$wpdb->terms} t_cat ON tt_cat.term_id = t_cat.term_id";
				$join .= " INNER JOIN {$wpdb->term_relationships} tr_tag ON p.ID = tr_tag.object_id";
				$join .= " INNER JOIN {$wpdb->term_taxonomy} tt_tag ON tr_tag.term_taxonomy_id = tt_tag.term_taxonomy_id";
				$join .= " INNER JOIN {$wpdb->terms} t_tag ON tt_tag.term_id = t_tag.term_id";
			}

			if ($scan == 'new') {
				if ($quick_by == 'product_title') {
					$join .= "LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id";
					$join .= " LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
				}
				
				$and = " AND p.ID NOT IN (
					SELECT tr2.object_id
					FROM {$wpdb->term_relationships} tr2
					INNER JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
					WHERE tt2.term_id = %d
				)";
				$query_params[] = $term_id;
			}

			if ($paged == 1 && $scan == 'all') {
				// Execute the query
				$wpdb->query(
					$wpdb->prepare("
						DELETE tr
						FROM {$wpdb->term_relationships} tr
						INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
						WHERE tt.term_id = %d
						AND tt.taxonomy = %s
					", $term_id, $taxonomy)
				);

				wp_cache_set_terms_last_changed();
				wp_update_term_count( [$term_id], $taxonomy );
			}

			$total_items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT(p.ID)) as count
					FROM {$wpdb->posts} AS p
					{$join}
					WHERE 1=1
					AND p.post_type = 'product'
					AND p.post_status = 'publish'
					AND ({$query_condition})
					{$and}
					", 
					$query_params
				)
			); //db call ok; no-cache ok
			
			// echo "SELECT COUNT(DISTINCT(p.ID)) as count
			// 		FROM {$wpdb->posts} AS p
			// 		{$join}
			// 		WHERE 1=1
			// 		AND p.post_type = 'product'
			// 		AND p.post_status = 'publish'
			// 		AND ({$query_condition})
			// 		{$and}
			// 		";
			
			// echo '<pre>'; print_r($query_params); echo '</pre>'; die();


			// Calculate total number of pages
			$total_items = $total_items[0]->count;
			if (!$total_items || $total_items <= 0) {
				wp_send_json_success( array(
					'message'         => __('Finished Scanning!', 'opal-product-collection-woocommerce'),
					'is_finished'     => 1,
					'percentage'      => 100,
					'match_count'	  => 0,
					'term_count'	  => opcw_get_count_product_by_term($term_id, $taxonomy),
				) );
			}
			$max_paged = ceil($total_items / $limit);
			if ($paged > $max_paged) {
				wp_send_json_error( array(
					'message'         => __('Page number is not valid!', 'opal-product-collection-woocommerce'),
					'is_finished'     => 1,
					'term_count'	  => opcw_get_count_product_by_term($term_id, $taxonomy),
				) );
			}
			$is_finished = $max_paged == $paged;
			$next_paged = ($is_finished) ? false : $paged+1;

			$offset = ($paged - 1) * $limit;

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT(p.ID)
					FROM $wpdb->posts AS p
					{$join}
					WHERE 1=1
					AND p.post_type = 'product' 
					AND p.post_status = 'publish'
					AND ({$query_condition})
					{$and}
					ORDER BY p.ID ASC
					LIMIT $limit 
					OFFSET $offset
					",
					$query_params
				)
			); //db call ok; no-cache ok

			// opcw_p($results); die();

			$match_count = 0;
			if ($results && !empty($results)) {
				wc_set_time_limit();

				foreach ($results as $result) {
					// Check action stop scanning
					if (get_term_meta( $term_id, 'scan_status', true ) == 'stop') {
						$is_stop = true;
						break;
					}

					$product_id = $result->ID;
					$terms   = wp_get_post_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );
					$terms[] = (int) $term_id;
					wp_set_post_terms( $product_id, $terms, $taxonomy );

				}
				$message = ($is_finished) ? __('Finished Scanning!', 'opal-product-collection-woocommerce') : __('Scanning...', 'opal-product-collection-woocommerce');

				$match_count = count($results);
				$total_processed = ($limit * ($paged - 1)) + $match_count;
				$percentage = $total_processed / $total_items * 100;

				if (isset($is_stop) && $is_stop) {
					$message = __('Stopped Scanning!', 'opal-product-collection-woocommerce');
					$is_finished = true;
				}
			}
			else {
				$message = __('Finished Scanning!', 'opal-product-collection-woocommerce');
				$is_finished = true;
				$percentage = 100;
			}

			wp_send_json_success( array(
				'quick_by'		=> $quick_by,
				'message'       => $message,
				'is_finished'   => $is_finished,
				'percentage'    => round($percentage, 2),
				'match_count'	=> $match_count,
				'next_paged'	=> $next_paged,
				'term_count'	=> opcw_get_count_product_by_term($term_id, $taxonomy)
			) );
		}

		private static function get_quick_query_title($relation, $conditions, $term_id) {
			$quick_scan_title = true;
			$query_condition = '';
			$query_params = [];
			foreach ($conditions as $item) {
				if ($item['rule_item'] != 'product_title') {
					$quick_scan_title = false;
					break;
				}
				else {
					if (!empty($query_condition)) {
						$query_condition .= ($relation == 'all') ? ' AND ' : ' OR ';
					}

					if (empty($item['rule_value'])) {
						$rule_value = get_term( $term_id )->name;
					}
					else {
						$rule_value = $item['rule_value'];
					}

					switch ($item['rule_relation']) {
						case 'contains':
							$query_condition .=  'p.post_title LIKE %s';
							$query_params[] = "%$rule_value%";
							break;
						case 'not_contains':
							$query_condition .=  'p.post_title NOT LIKE %s';
							$query_params[] = "%$rule_value%";
							break;
						case 'starts_with':
							$query_condition .=  'p.post_title LIKE %s';
							$query_params[] = "$rule_value%";
							break;
						case 'ends_with':
							$query_condition .=  'p.post_title LIKE %s';
							$query_params[] = "%$rule_value";
							break;
						default:
							break;
					}
					
				}
			}

			if (!$quick_scan_title) {
				return false;
			}
			else {
				return [
					'query_condition' => $query_condition,
					'query_params' => $query_params,
				];
			}
		}

		private static function get_quick_query_tax($relation, $conditions, $term_id) {
			$quick_scan_tax = true;
			$query_condition = '';
			$query_params = [];
			foreach ($conditions as $item) {
				if (empty($item['rule_value'])) {
					continue;
				}
				if (!in_array($item['rule_item'], ['product_cat', 'product_tag'])) {
					$quick_scan_tax = false;
					break;
				}
				else {
					if (!empty($query_condition)) {
						$query_condition .= ($relation == 'all') ? ' AND ' : ' OR ';
					}

					$rule_tax = $item['rule_item'];
					$tax = str_replace('product_', '', $rule_tax);
					$rule_value = implode(', ',  array_map('intval', $item['rule_value']));

					switch ($item['rule_relation']) {
						case 'is_not_in':
							$query_condition .= "(tt_$tax.taxonomy = %s AND t_$tax.term_id NOT IN ($rule_value))";
							$query_params[] = $rule_tax;
							break;
						case 'is_in':
							$query_condition .= "(tt_$tax.taxonomy = %s AND t_$tax.term_id IN ($rule_value))";
							$query_params[] = $rule_tax;
							break;
						default:
							break;
					}
					
				}
			}

			if (!$quick_scan_tax) {
				return false;
			}
			else {
				return [
					'query_condition' => $query_condition,
					'query_params' => $query_params,
				];
			}
		}

		private function opcw_scan_update_product_collections($term_id, $paged, $scan = 'all') {
			if ($paged === 1) {
				update_term_meta( $term_id, 'scan_status', 'scanning' );
			}

			$term = get_term($term_id);
			if (!$term || is_wp_error($term)) {
				wp_send_json_error( array(
					'message'         => __('Taxonomy is not valid!', 'opal-product-collection-woocommerce'),
					'is_finished'     => 1
				) );
			}
			$taxonomy = $term->taxonomy;
			
			$relation = get_term_meta( $term_id, 'opcw_condition_relation', true );
    		$relation = $relation ? $relation : 'all';

			$conditions = get_term_meta( $term_id, 'opcw_conditions', true );
			$conditions = $conditions ? (array) $conditions : [];
			$conditions = self::convert_conditions($conditions);

			// Keep scan without conditions
			// Create default conditions
			if (empty($conditions)) {
				$conditions = [
					[
						'rule_item' => 'product_title',
						'rule_relation' => 'contains',
						'rule_value' => get_term( $term_id )->name
					]
				];
			}

			$include     = get_term_meta( $term_id, 'opcw_include_products', true );
			$include  = $include ? $include : [];
			
			$exclude     = get_term_meta( $term_id, 'opcw_exclude_products', true );
			$exclude  = $exclude ? $exclude : [];

			// Quick scan if only filter title
			
			if (!empty($conditions)) {
				$quick_scan_title = self::get_quick_query_title($relation, $conditions, $term_id);
				$quick_scan_tax = self::get_quick_query_tax($relation, $conditions, $term_id);
			}
			else {
				wp_send_json_error( array(
					/* translators: %s: Term name. */
					'message'         => sprintf(__('There are no scanning conditions for this term: %s', 'opal-product-collection-woocommerce'), $term->name),
					'is_finished'     => 1,
					'percentage'      => 100,
					'match_count'	  => 0,
				) );
			}

			if ($quick_scan_title) {
				$query_condition = $quick_scan_title['query_condition'];
				$query_params = $quick_scan_title['query_params'];
				$this->opcw_quick_scan_update_product_collections($term_id, $paged, $taxonomy, $query_condition, $query_params, $scan);
				die();
			}
			elseif ($quick_scan_tax) {
				$query_condition = $quick_scan_tax['query_condition'];
				$query_params = $quick_scan_tax['query_params'];
				$this->opcw_quick_scan_update_product_collections($term_id, $paged, $taxonomy, $query_condition, $query_params, $scan, 'tax');
				die();
			}

			if ($scan == 'all') {
				$query = self::opcw_get_query_product($paged);
			}
			else {
				$query = self::opcw_get_query_product($paged, $term_id, $taxonomy);
			}
			
			$max_paged = $query->max_num_pages;
			if ($paged > $max_paged) {
				wp_send_json_error( array(
					'message'         => __('Page number is not valid!', 'opal-product-collection-woocommerce'),
					'is_finished'     => 1,
				) );
			}
			$is_finished = $max_paged == $paged;
			$next_paged = ($is_finished) ? false : $paged+1;

			// opcw_p($conditions); die();

			$match_count = 0;

			wc_set_time_limit();
			if ( $query->have_posts() ) {
				$i = 0;
				while ( $query->have_posts() ) {
					$query->the_post();

					// Check action stop scanning
					if (get_term_meta( $term_id, 'scan_status', true ) == 'stop') {
						$is_stop = true;
						break;
					}

					global $product;

					$match = $this->opcw_check_tax_contain_product_by_condition($product, $term_id, $relation, $include, $exclude, $conditions, $taxonomy);

					if ( $match ) {
						$match_count++;
					}

					$i++;
				}
				
				wp_reset_postdata();

				$message = ($is_finished) ? __('Finished Scanning!', 'opal-product-collection-woocommerce') : __('Scanning...', 'opal-product-collection-woocommerce');

				$posts_per_page = $query->query['posts_per_page'];
				$total_processed = ($posts_per_page * ($paged - 1)) + $i;
				$percentage = $total_processed / $query->found_posts * 100;

				if (isset($is_stop) && $is_stop) {
					$message = __('Stopped Scanning!', 'opal-product-collection-woocommerce');
					$is_finished = true;
				}
			}
			else {
				$message = __('Finished Scanning!', 'opal-product-collection-woocommerce');
				$is_finished = true;
				$percentage = 100;

				update_term_meta( $term_id, 'scan_status', 'stop' );
			}

			wp_send_json_success( array(
				'message'         => $message,
				'is_finished'     => $is_finished,
				'percentage'      => round($percentage, 2),
				'match_count'	  => $match_count,
				'next_paged'	  => $next_paged,
				'term_count'	  => opcw_get_count_product_by_term($term_id, $taxonomy),
			) );
			die();
		}

		public function opcw_rescan_collection() {
			if ( !check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' ) ) {
				wp_send_json_error( array(
					'message' => 'Permission denied.',
				) );
				exit();
			}

			if (empty($_REQUEST['collection']) || empty($_REQUEST['paged'])) {
				wp_send_json_error( array(
					'message' => __('Request data is not valid!', 'opal-product-collection-woocommerce'),
				) );
				exit();
			}
			
			$this->opcw_scan_update_product_collections(absint($_REQUEST['collection']), absint($_REQUEST['paged']), wc_clean($_REQUEST['scan']));

			die();
		}

		public function opcw_stop_scanning_collection() {
			if ( !check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' ) ) {
				wp_send_json_error( array(
					'message' => 'Permission denied.',
				) );
				exit();
			}

			if (empty($_REQUEST['collection'])) {
				wp_send_json_error( array(
					'message' => __('Request data is not valid!', 'opal-product-collection-woocommerce'),
				) );
				exit();
			}
			
			update_term_meta( wc_clean($_REQUEST['collection']), 'scan_status', 'stop' );

			wp_send_json_success( array(
				'message'         => __('Scanning has stopped', 'opal-product-collection-woocommerce'),
			) );

			die();
		}

		public function opcw_load_rule_apply_ajax(){
            check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' );

            if(empty($_GET['q'])) return false;
            if(empty($_GET['term'])) return false;

            $kw = wc_clean($_GET['q']);
            $term = wc_clean($_GET['term']);
            $func_search = 'opcw_get_'.$term.'_by_keyword';

            $return = $this->$func_search($kw);

            if (!$return) return false;
            echo wp_json_encode( $return );
            die;
        }

        private function opcw_get_product_by_keyword($kw) {
        	$return = false;

        	$search_results = new WP_Query( array( 
        	    's'=> wc_clean($kw), // the search query
        	    'post_status' => 'publish', // if you don't want drafts to be returned
        	    'post_type' => 'product',
        	    'posts_per_page' => -1 // how much to show at once
        	) );

        	if( $search_results->have_posts() ) {
        		$return = [];
        	    while( $search_results->have_posts() ) : $search_results->the_post();	
        	        // shorten the title a little
        	        $title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
        	        $return[] = array( $search_results->post->ID, $title );
        	    endwhile;
        	}
        	
        	return $return;
        }

        private function opcw_get_product_category_by_keyword($kw) {
        	global $wpdb;
        	$taxonomy = 'product_cat';
        	$return = false;

        	$results = $wpdb->get_results(
        	    $wpdb->prepare(
        	        "SELECT t.*, tt.*
        	        FROM $wpdb->terms AS t
        	        INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
        	        WHERE tt.taxonomy = %s
        	        AND t.name LIKE %s",
        	        $taxonomy,
        	        '%' . $wpdb->esc_like($kw) . '%'
        	    )
        	); //db call ok; no-cache ok

        	// In kết quả
        	if ($results && !empty($results)) {
    			$return = [];
    		    foreach ($results as $term) {
    		        // shorten the title a little
    		        $title = ( mb_strlen( $term->name ) > 50 ) ? mb_substr( $term->name, 0, 49 ) . '...' : $term->name;
    		        $return[] = array( $term->term_id, $title );
    		    }
        	}
        	return $return;
        }

        private function opcw_get_product_tag_by_keyword($kw) {
        	global $wpdb;
        	$taxonomy = 'product_tag';
        	$return = false;

        	$results = $wpdb->get_results(
        	    $wpdb->prepare(
        	        "SELECT t.*, tt.*
        	        FROM $wpdb->terms AS t
        	        INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
        	        WHERE tt.taxonomy = %s
        	        AND t.name LIKE %s",
        	        $taxonomy,
        	        '%' . $wpdb->esc_like($kw) . '%'
        	    )
        	); //db call ok; no-cache ok

        	// In kết quả
        	if ($results && !empty($results)) {
    			$return = [];
    		    foreach ($results as $term) {
    		        // shorten the title a little
    		        $title = ( mb_strlen( $term->name ) > 50 ) ? mb_substr( $term->name, 0, 49 ) . '...' : $term->name;
    		        $return[] = array( $term->term_id, $title );
    		    }
        	}
        	return $return;
        }

		public function opcw_collection_export() {
            check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' );

			if (empty($_REQUEST['term_id']) || empty($_REQUEST['taxonomy'])) {
				_default_wp_die_handler( 'Choose a term', 'OPCW' );
			}
			if ($_REQUEST['term_id'] == 'all') {
				$terms = get_terms( array(
					'taxonomy' => wc_clean($_REQUEST['taxonomy']),
					'hide_empty' => false
				) );
				$term_ids = wp_list_pluck($terms, 'term_id');
				$file_data = $this->prepare_term_export( $term_ids );
			}
			else {
				$term_id = absint($_REQUEST['term_id']);
				if (empty($term_id)) {
					_default_wp_die_handler( 'Choose a term', 'OPCW' );
				}
				$file_data = $this->prepare_term_export( $term_id );
			}


            if ( is_wp_error( $file_data ) ) {
                return $file_data;
            }

            opcw_send_file_headers( $file_data['name'], strlen( $file_data['content'] ) );

            // Clear buffering just in case.
            @ob_end_clean();

            flush();

            add_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);
            // Output file contents.
            echo esc_html($file_data['content']);

            remove_filter('esc_html', 'opcw_prevent_escape_html', 99, 2);

            die;
        }

		private function prepare_term_export($term_ids) {
			$term_ids = (is_array($term_ids)) ? $term_ids : [$term_ids];

			$name = 'opcw-terms-export-' . gmdate( 'Y-m-d' ) . '.json';
			$export_content = [];
			foreach ($term_ids as $term_id) {
				$term = get_term($term_id);
				if (is_wp_error($term) || !$term) {
					continue;
				}
	
				$logo        = get_term_meta( $term_id, 'opcw_logo', true ) ? : '';
				$relation  = get_term_meta( $term_id, 'opcw_condition_relation', true );
				$conditions  = get_term_meta( $term_id, 'opcw_conditions', true );
				$conditions  = $conditions ? (array) $conditions : [];
				$include     = get_term_meta( $term_id, 'opcw_include_products', true );
				$include  = $include ? $include : [];
				$exclude     = get_term_meta( $term_id, 'opcw_exclude_products', true );
				$exclude  = $exclude ? $exclude : [];
				
				$export_content[$term_id] = [
					'data_term' => $term,
					'logo' => $logo,
					'relation' => $relation,
					'conditions' => $conditions,
					'include' => $include,
					'exclude' => $exclude,
				];

				if (count($term_ids) == 1) {
					$taxonomy = $term->taxonomy;
					$name = 'opcw-' . $taxonomy . '-' . $term_id . '-' . gmdate( 'Y-m-d' ) . '.json';
				}
			}

            $file_data = [
                'name' => $name,
                'content' =>  wp_json_encode( $export_content ),
            ];

            return $file_data;
        }

		public function opcw_handle_import_collection() {
			check_ajax_referer( 'opcw-nonce-ajax', 'ajax_nonce_parameter' );

			$notices = WC_Admin_Notices::get_notices();
			if(!empty($notices)) {
				foreach ($notices as $notice_key) {
					if (opcw_check_string_start_with_char($notice_key, 'term-exists-')) {
						WC_Admin_Notices::remove_notice($notice_key);
					}
				}
			}
	
			if (isset($_FILES['opcw_collection_import']["error"]) && $_FILES['opcw_collection_import']["error"] != 4) {
				if ($_FILES['opcw_collection_import']["error"] == UPLOAD_ERR_INI_SIZE) {
					$error_message = esc_html__('The uploaded file exceeds the maximum upload limit', 'opal-product-collection-woocommerce');
				} else if (in_array($_FILES['opcw_collection_import']["error"], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE))) {
					$error_message = esc_html__('The uploaded file exceeds the maximum upload limit', 'opal-product-collection-woocommerce');
				}
				$ext = pathinfo(wc_clean($_FILES['opcw_collection_import']['name']), PATHINFO_EXTENSION);
				if ($ext != 'json' || $_FILES['opcw_collection_import']['type'] != 'application/json') {
					$error_message = esc_html__('Only allow upload Json(.json) file', 'opal-product-collection-woocommerce');
				}
			}
			else {
				$error_message = esc_html__('Please upload a file to import', 'opal-product-collection-woocommerce');
			}
			
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			$filesystem = new \WP_Filesystem_Direct( true );
			$data_upload = $filesystem->get_contents(wc_clean($_FILES['opcw_collection_import']['tmp_name']));
			// $data_upload = json_decode($data_upload, true);
			if (empty($data_upload)) {
				$error_message = esc_html__('File upload is empty', 'opal-product-collection-woocommerce');
			}
	
			if (isset($error_message)) {
				$error = new \WP_Error( 'file_error', $error_message );
				if ( is_wp_error( $error ) ) {
					_default_wp_die_handler( $error->get_error_message(), 'OPCW' );
				}
			}
	
			$data_raw = json_decode($data_upload, true);
			if ($data_raw) {
				foreach ($data_raw as $term) {
					if ( empty($term['data_term']) ) continue;
					if ( empty($term['data_term']['slug']) ) continue;
					
					$data_term = $term['data_term'];
					$name = $data_term['name'];
					$description = $data_term['description'];
					$taxonomy = (!empty($_POST['taxonomy'])) ? wc_clean($_POST['taxonomy']) : $data_term['taxonomy'];
					$slug = $data_term['slug'];

					if (term_exists( $slug, $taxonomy )) {
						/* translators: %s: Taxonomy name. */
						$message = sprintf(__('<p>Term <strong>%s</strong> already exists, cannot be overwritten</p>', 'opal-product-collection-woocommerce'), esc_html($name));
						$adminnotice = new WC_Admin_Notices();
						$adminnotice->add_custom_notice("term-exists-".$slug, $message);
						continue;
					}

					$insert_term = wp_insert_term(
						$name,   // the term 
						$taxonomy, // the taxonomy
						array(
							'description' => $description,
							'slug'        => $slug,
						)
					);

					if (!is_wp_error($insert_term) && !empty($insert_term['term_id'])) {
						$term_id = $insert_term['term_id'];

						$relation = (isset($term['relation'])) ? $term['relation'] : 'any';
						$conditions = (isset($term['conditions'])) ? $term['conditions'] : [];
						$include = (isset($term['include'])) ? $term['include'] : [];
						$exclude = (isset($term['exclude'])) ? $term['exclude'] : [];
						
						update_term_meta( $term_id, 'opcw_condition_relation', $relation );
						update_term_meta( $term_id, 'opcw_conditions', $conditions );
						update_term_meta( $term_id, 'opcw_include_products', $include );
						update_term_meta( $term_id, 'opcw_exclude_products', $exclude );
					}

				}
			}
			
			$redirect = wc_clean($_REQUEST['_wp_http_referer']).'&opcw-show-notice-term=yes';
			header("Location: $redirect");
			exit;
			// wp_safe_redirect($redirect);
			// wp_die();
		}

		public function opcw_mask_loading() {
			?>
			<div id="opcw_process_box" style="display: none;">
				<div class="opcw_wrapper_process">
					<h2 class="opcw_title_process"><?php echo esc_html__('Scanning Collection', 'opal-product-collection-woocommerce') ?></h2>
					<p><?php echo esc_html__("Please do not reload the page during scanning!", 'opal-product-collection-woocommerce') ?></p>
					<div class="opcw_inner_process">
						<div class="opcw_header_process">
							<label><input type="radio" class="opcw_option_scan" name="option_scan" value="all" checked><span><?php echo esc_html__('Scan all product', 'opal-product-collection-woocommerce') ?></span></label>
							<label><input type="radio" class="opcw_option_scan" name="option_scan" value="new"><span><?php echo esc_html__('Scan new products', 'opal-product-collection-woocommerce') ?></span></label>
						</div>
						<div class="opcw_main_process">
							<ul id="opcw_list_process"></ul>
						</div>
						<div class="opcw_action_process">
							<a id="opcw_start_process" class="button button-primary" href="javascript:void(0)"><?php echo esc_html__('Start scan', 'opal-product-collection-woocommerce') ?></a>
							<a id="opcw_close_process" class="button" href="javascript:void(0)"><?php echo esc_html__('Close', 'opal-product-collection-woocommerce') ?></a>
						</div>
					</div>
				</div>
				<!-- <div class="triple-spinner"></div> -->
			</div>
			<div id="opcw_default_process" class="opcw_hidden">
				<li class="opcw_item_process">
					<div class="opcw_resuilt_scan">
						<h4 class="opcw_term_scan"></h4>
						<span><strong><?php echo esc_html__('Products match: ', 'opal-product-collection-woocommerce') ?></strong><span class="opcw_products_match">0</span></span>
					</div>
					<div class="opcw_wrap_scan">
						<div class="opcw_process_bar">
							<span class="opcw_process_active" style="width:0%"></span>
							<span class="opcw_data_process">0%</span>
						</div>
						<a class="opcw_stop_process" class="button" href="javascript:void(0)" title="<?php echo esc_html__('Stop', 'opal-product-collection-woocommerce') ?>"><i class="dashicons dashicons-no-alt"></i></a>		
					</div>
				</li>
			</div>
			<?php
		}

		private function opcw_get_taxonomies_settings() {
			$settings_data = $this->settings->opcw_get_settings_data();
			$module_in_taxs = opcw_get_option('module_in_taxs', false, $settings_data);
			return (!$module_in_taxs) ? [] : $module_in_taxs;
		}
    }

endif;


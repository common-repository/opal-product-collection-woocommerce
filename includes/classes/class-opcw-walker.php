<?php
/**
 * OPCW_Product_Collections_List_Watker class
 *
 * @extends    Walker
 * @class        OPCW_Product_Collections_List_Watker
 * @version        2.3.0
 * @package        WooCommerce/Classes/Walkers
 * @author        WPOPAL
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('OPCW_Product_Collections_List_Watker', false)) :

	class OPCW_Product_Collections_List_Watker extends Walker {

		/**
		 * What the class handles.
		 *
		 * @var string
		 */
		public $tree_type = OPCW_TAXONOMY;

		/**
		 * DB fields to use.
		 *
		 * @var array
		 */
		public $db_fields
			= array(
				'parent' => 'parent',
				'id'     => 'term_id',
				'slug'   => 'slug',
			);

		/**
		 * Starts the list before the elements are added.
		 *
		 * @see Walker::start_lvl()
		 * @since 2.1.0
		 *
		 * @param string $output Passed by reference. Used to append additional content.
		 * @param int $depth Depth of category. Used for tab indentation.
		 * @param array $args Will only append content if style argument value is 'list'.
		 */
		public function start_lvl(&$output, $depth = 0, $args = array()) {
			if ('list' !== $args['style']) {
				return;
			}

			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
		}

		/**
		 * Ends the list of after the elements are added.
		 *
		 * @see Walker::end_lvl()
		 * @since 2.1.0
		 *
		 * @param string $output Passed by reference. Used to append additional content.
		 * @param int $depth Depth of category. Used for tab indentation.
		 * @param array $args Will only append content if style argument value is 'list'.
		 */
		public function end_lvl(&$output, $depth = 0, $args = array()) {
			if ('list' !== $args['style']) {
				return;
			}

			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
		}


		/**
		 * Start the element output.
		 *
		 * @see Walker::start_el()
		 * @since 2.1.0
		 *
		 * @param string $output Passed by reference. Used to append additional content.
		 * @param object $cat
		 * @param int $depth Depth of category in reference to parents.
		 * @param array $args
		 * @param integer $current_object_id
		 */
		public function start_el(&$output, $cat, $depth = 0, $args = array(), $current_object_id = 0) {

			$output .= '<li class="cat-item cat-item-' . $cat->term_id;

			if (!empty($args['current_cat']) && $args['current_cat'] == $cat->term_id) {
				$output .= ' current-cat chosen';
			}

			if ($args['has_children'] && $args['hierarchical'] && (empty($args['max_depth']) || $args['max_depth'] > $depth + 1)) {
				$output .= ' cat-parent';
			}

			if ($args['current_cat_ancestors'] && $args['current_cat'] && in_array($cat->term_id, $args['current_cat_ancestors'])) {
				$output .= ' current-brand-parent chosen';
			}


			$output .= '">';
			$output .= '<a href="' . get_term_link((int)$cat->term_id, $this->tree_type) . '">';

			if (isset($args['show_logo']) && $args['show_logo']) {
				$image_logo = get_term_meta((int)$cat->term_id, 'opcw-collection_logo', true);

				$image_logo = (!empty($image_logo)) ? wp_get_attachment_image_src($image_logo) : wc_placeholder_img_src();
				$output     .= '<img class="product-cat-logo" src="' . esc_url_raw($image_logo[0]) . '" alt="' . esc_attr($cat->name) . '"  title="' . esc_attr($cat->name) . '">';
			} else {
				$output .= esc_html($cat->name);
			}
			$output .= '</a>';

			if (isset($args['show_count']) && $args['show_count']) {
				$output .= ' <span class="count">(' . $cat->count . ')</span>';
			}
		}

		public function end_el(&$output, $cat, $depth = 0, $args = array()) {
			$output .= "</li>\n";
		}

		public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output) {
			if (!$element || (0 === $element->count && !empty($args[0]['hide_empty']))) {
				return;
			}
			parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
		}
	}

endif;

<?php
/** 
 * OPCW Related Products Collection
 * 
 * @uses collection_id
 * @uses product_id
 * @uses wrap_into_container
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   

?>
<section class="related-colllections products">
    <?php if ( $wrap_into_container ) : ?>
        <div class="<?php echo esc_attr(apply_filters('opcw_container_class_related_collection', 'container')) ?>">
    <?php endif; ?>
        <?php
        $heading = apply_filters( 'opcw_related_products_collections_heading', __( 'More in Collection', 'opal-product-collection-woocommerce' ) );

        if ( $heading ) : ?>
            <h2><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>
        
        <?php 
        $atts = ' collection-id="'.$collection_id.'" columns="4" excludes="'.$product_id.'"';
        $related_shortcode = apply_filters('opcw_trigger_shortcode_related_collections', $atts);
        echo do_shortcode('[opcw'.$related_shortcode.']');
        ?>
    <?php if ( $wrap_into_container ) : ?>
        </div>
    <?php endif; ?>
</section>
<?php

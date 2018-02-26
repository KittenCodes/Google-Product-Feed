<?php

unlink('httpdocs/googleVariations.xml') ;

require 'wp-config.php';

$argsParent = array(
    'post_type'             => array('product'),
    'post_status'           => 'publish',
    'posts_per_page'        => '-1',
    'meta_query'            => array(
        array(
            'key'           => '_visibility',
            'value'         => array('catalog', 'visible'),
            'compare'       => 'IN'
        )
    ),
    'meta_query' => array(
    array(
        'key' => '_stock_status',
        'value' => 'instock',
        'compare' => '=',
    	)  
    ),  
    'tax_query'             => array(
        array(
            'taxonomy'      => 'product_cat',
            'field' 		=> 'term_id', //This is optional, as it defaults to 'term_id'
            'terms'         => array('3265', '3324'),
            'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
        )
    )
);

$has_variable_ids = [];

$pubished_post = new WP_Query($argsParent);
if (!empty($pubished_post->posts))
{
    foreach ($pubished_post->posts as $post)
    {
        $_product = wc_get_product($post->ID);
        if ($_product->is_type('variable'))
        {
            // Product has variations
            $has_variable_ids[] = $post->ID;
        }
    }
}

$argsVariation = [
    'post_type' => ['product_variation'],
    'post_status' => 'publish',
    'posts_per_page'        => '-1',
    'post_parent__in' => $has_variable_ids,
];

//Turn on output buffering
ob_start();

//Create header info
header( 'Content-Type: application/xml; charset=UTF-8' );
if ( isset ( $_REQUEST['feeddownload'] ) ) {
	header( 'Content-Disposition: attachment; filename="E-Commerce_Product_List.xml"' );
} else {
	header( 'Content-Disposition: inline; filename="E-Commerce_Product_List.xml"' );
}

// Core feed information
echo "<?xml version='1.0' encoding='UTF-8' ?>\n";
echo "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom' xmlns:g='http://base.google.com/ns/1.0'>\n";
echo "  <channel>\n";
echo "    <title><![CDATA[Sibbons Products]]></title>\n";
echo "    <link>https://www.sibbons.co.uk</link>\n";
echo "    <description>This is the WooCommerce Product List RSS feed</description>\n";
echo "    <generator>Mackman Group</generator>\n";
//echo "    <atom:link href='" . esc_url( $this->store_info->feed_url )."' rel='self' type='application/rss+xml' />\n";

$loopVariation = new WP_Query( $argsVariation );
if ( $loopVariation->have_posts() ) {
	while ( $loopVariation->have_posts() ) : $loopVariation->the_post();

		$_product = wc_get_product( $post->ID );

		$gtin = get_post_meta( $post->ID, '_woocommerce_gpf_data', true);
		if (!empty($gtin)) {

		//Open the item
		echo "    <item>\n";

		//create g:ID
		echo '      <g:id>' . $post->ID . "</g:id>\n";

		$parentID = wp_get_post_parent_id( $post_ID );
		$childID = $post->ID;
		$colour = get_post_meta($childID, 'attribute_pa_colour', true);
		$queryStr = "SELECT name FROM $wpdb->terms WHERE slug = '$colour'";
		$colourNames =  $wpdb->get_results($queryStr, OBJECT);		
		$sizeShoe = get_post_meta($childID, 'attribute_pa_size-shoe-size', true);
		$size = get_post_meta($childID, 'attribute_pa_size', true);
		$sizeWaist = get_post_meta($childID, 'attribute_pa_size-waist', true);

		//create title
		echo '      <title><![CDATA[' . get_the_title($parentID) . " " . $colourName->name . " " . $sizeShoe . $size . $sizeWaist . "]]></title>\n";

		//create description
		echo '      <description><![CDATA[' . get_post_field('post_content', $parentID) . "]]></description>\n";

		//Create parent ID
		echo '      <g:item_group_id>' . $parentID . "</g:item_group_id>\n";

		//create link
		echo '      <link><![CDATA[' . $_product->get_permalink() . "]]></link>\n";

		//get the image
		$attachment_ids = $_product->get_gallery_attachment_ids();
		//create image
		echo '      <g:image_link><![CDATA[' . wp_get_attachment_url($_product->get_image_id()) . "]]></g:image_link>\n";
		//Get additional images
	    foreach( $attachment_ids as $attachment_id ) 
	        {
	        //create additional image
	        echo '      <g:additional_image_link><![CDATA[' . $Original_image_url = wp_get_attachment_url( $attachment_id ) . "]]></g:additional_image_link>\n";
	        }
 
		//create price
	    //echo '      <g:price>' . $_product->get_price() . " GBP</g:price>\n";
	    // SS - 20/10/2017 - modified to add VAT prices for Google feed
		echo '      <g:price>' . $_product->get_price_including_tax() . " GBP</g:price>\n";

		//create availability
		echo "      <g:availability>in stock</g:availability>\n";

		//get the brand
		$brands = wp_get_post_terms( $parentID, 'product_brand', array("fields" => "all") );
		    foreach( $brands as $brand ) {
			//create brand
		    echo '      <g:brand><![CDATA[' . $brand->name . "]]></g:brand>\n";	
		}

		//get the colour
		foreach( $colourNames as $colourName ) {
			//create colour
		    echo '      <g:color><![CDATA[' . $colourName->name . "]]></g:color>\n";	
		}

		//get the size
		echo '      <g:size><![CDATA[' . $sizeShoe . $size . $sizeWaist . "]]></g:size>\n";
		
		//create mpn
		echo '      <g:mpn><![CDATA[' . $_product->get_sku() . "]]></g:mpn>\n";
		
		//get the product type (subcategory)
		$types = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'all'));
		    foreach( $types as $type ) {
			//create type
		    echo '      <g:product_type><![CDATA[' . $type->name . "]]></g:product_type>\n";	  
		}

		//get the GTIN
		$gtin2 = ($gtin["gtin"]);
		//create GTIN
		echo '      <g:gtin>' . $gtin2 . "</g:gtin>\n";
		
		//create condition
		echo "      <g:condition>new</g:condition>\n";

		//create weight
		echo '      <g:shipping_weight>' . $_product->get_weight() . "</g:shipping_weight>\n";

		//Close the item
		echo "    </item>\n";

		} else {

}
		
	endwhile;
} else {
	//echo __( 'No products found' );
}

//add footer info
echo "  </channel>\n";
echo '</rss>';

//  Return the contents of the output buffer
$htmlStr = ob_get_contents();
// Clean (erase) the output buffer and turn off output buffering
ob_end_clean(); 
// Write final string to file
file_put_contents('httpdocs/googleVariations.xml', $htmlStr);

wp_reset_postdata();
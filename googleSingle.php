<?php

unlink('httpdocs/googleSingle.xml') ;

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

$loop = new WP_Query( $argsParent );
if ( $loop->have_posts() ) {
	while ( $loop->have_posts() ) : $loop->the_post();	

		//IF PRODUCT IS SIMPLE THEN LOOP BELOW
		$_product = wc_get_product( $post->ID );
		if ($_product->is_type('simple')) {

		//Open the item
		echo "    <item>\n";

		//create g:ID
		echo '      <g:id>' . $post->ID . "</g:id>\n";

		//create title
		echo '      <title><![CDATA[' . get_the_title() . "]]></title>\n";

		//create description
		echo '      <description><![CDATA[' . $post->post_content . "]]></description>\n";

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
		$brands = wp_get_post_terms( $post->ID, 'product_brand', array("fields" => "all") );
		    foreach( $brands as $brand ) {
			//create brand
		    echo '      <g:brand><![CDATA[' . $brand->name . "]]></g:brand>\n";	
		}
		
		//create mpn
		echo '      <g:mpn><![CDATA[' . $_product->get_sku() . "]]></g:mpn>\n";
		
		//get the product type (subcategory)
			$types = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'all'));
		    foreach( $types as $type ) {
			//create type
		    echo '      <g:product_type><![CDATA[' . $type->name . "]]></g:product_type>\n";	  
		}

		//get the GTIN
		$gtin = get_post_meta( $post->ID, '_woocommerce_gpf_data', true);
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
		//echo __( 'No products found' );
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
file_put_contents('httpdocs/googleSingle.xml', $htmlStr);

wp_reset_postdata();
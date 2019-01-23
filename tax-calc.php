<?php
/**
 * Plugin Name: Tax Calculation
 * Plugin URI: https://github.com/gfrice/wp-tax-calc
 * Description: The very first plugin that I have ever created.
 * Version: 0.1
 * Author: Gene Rice
 * Author URI: https://github.com/gfrice
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
          

// add_filter( 'woocommerce_checkout_fields' , 'print_all_fields' );
add_filter('woocommerce_cart_calculate_fees', 'calc_tax');
// add_action('woocommerce_calculate_totals', 'calc_tax');

// add_filter( 'woocommerce_calc_tax', 'override_woocommerce_tax_rates' );
          
// our hooked in function 
function calc_tax($cart_object) {
     // if outside of cart and checkout or in mini-cart, skip calculations
     if (( ! is_cart() && ! is_checkout() ) || ( is_cart() && is_ajax() )) {
          return;
     }
     
     // var_dump($cart_object);
     $cart_taxes = array();
     $cart_tax_total = 0;
     
     // $line_items = get_line_items( $cart_object );
     
     /**
      * This is where I begin "connecting to taxware" to get the data
     */
     $url = "https://sstwsuat.taxware.net:7443/Twe/api/rest/calcTax/doc";   // taxware gtd api endpoint
     $usrname = "restuat@ACBJ";    // taxware gtd api username (test or prod)
     $pswrd = "password";
     $hmacKey = "d6e6e761-bfbc-453f-a751-863524d13da4";      // 000xx0xx-000x-00x0-x00x-0xx0xxx0000x
     $orgCd = "BIZBOOKS";   // ORGCD (UCASE)
     // $isoDate = date(DateTime::ISO8601);
     $today = gmdate('Y-m-d H:i:s.Z', time());
     $isoDate = date('c', strtotime($today));

     $grTime = gmdate('YmdHis', time());
     $trnDocNum = "ybk" . $grTime;
     
     $hmacInput = "POSTapplication/json" . $isoDate . "/Twe/api/rest/calcTax/doc" . $usrname . $pswrd;
     $hmacSig = base64_encode(hash_hmac("sha1", $hmacInput, $hmacKey, true));

     $wooAmt = WC()->cart->get_cart_contents_total();
     $headers = array(
          'Content-Type' => 'application/json',
          'Date' => $isoDate,
          'Authorization' => 'TAX ' . $usrname . ':' . $hmacSig
     );
     // $body['lines'] = get_line_items($cart_object);
     
     $body = array(
          'usrname' => $usrname,
          'pswrd' => $pswrd,
          'rsltLvl' => '5',
          'trnSrc' => 'ybk',
          'currn' => 'USD',
          'txCalcTp' => '1',
          'trnDocNum' => $trnDocNum,
          'lines' => get_line_items($cart_object)
     );

     $args = array(
          'headers' => $headers,
          'body' => json_encode($body, true)
     );
     // var_dump($args);
     $response = wp_remote_post($url, $args);
     
     if (is_wp_error($response)) {
          $error_message = $response->get_error_message();
          echo "Something went wrong: $error_message";
     } else {
          // var_dump($cart_object);
          /**
           * This is where I "use" the data in the response
          */
          
          // uncomment the following line to dump the json decoded response.body
          // var_dump(json_decode($response['body'],true));exit;

          // create an object out of the response body
          // $bodyObj = json_decode($response['body'],true);
          $bodyObj = json_decode($response['body'], true);
          
          // create var for tax rate returned
          $taxTot = $bodyObj['txAmt'];

          // get current cart/ship totals
          $currTotal = WC()->cart->get_cart_contents_total();
          $shipTotal = WC()->cart->get_shipping_total();

          // get any current tax in the cart
          $currCartTax = WC()->cart->get_total_tax();

          // do the math
          $newTot = $currTotal + $taxTot + $currCartTax + $shipTotal;

          echo "<br>New Total: $newTot";
     }

     
}
function get_line_items( $cart_object ) {
     $line_items = array();

     foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
          // var_dump($cart_item);
          $orgCd = "BIZBOOKS";
          $product = $cart_item['data'];
          $id = $product->get_id();
          $quantity = $cart_item['quantity'];
          $unit_price = wc_format_decimal( $product->get_price() );
          $line_subtotal = wc_format_decimal( $cart_item['line_subtotal'] );
          $discount = wc_format_decimal( $cart_item['line_subtotal'] - $cart_item['line_total'] );
          $tax_class = explode( '-', $product->get_tax_class() );
          $tax_code = '';

          if ( isset( $tax_class ) && is_numeric( end( $tax_class ) ) ) {
               $tax_code = end( $tax_class );
          }

          if ( ! $product->is_taxable() || 'zero-rate' == sanitize_title( $product->get_tax_class() ) ) {
               $tax_code = '99999';
          }

          if ( $unit_price && $line_subtotal ) {
               array_push($line_items, array(
                    'lnItmId' => $id,
                    'qnty' => $quantity,
                    'trnTp' => '1',
                    'orgCd' => $orgCd,
                    'goodSrvCd' => '2049677',
                    'grossAmt' => $line_subtotal,
                    'bTStNameNum' => '120 W MOREHEAD ST',
                    'bTCity' => 'CHARLOTTE',
                    'bTStateProv' => 'NC',
                    'bTPstlCd' => '28202',
                    'bTCountry' => 'USA',
                    'bTPstlCdExt' => '1800',
                    'bTCounty' => '',
                    'sFStNameNum' => '120 W MOREHEAD ST',
                    'sFCity' => 'CHARLOTTE',
                    'sFStateProv' => 'NC',
                    'sFPstlCd' => '28202',
                    'sFCountry' => 'USA',
                    'sTStNameNum' => '188 Eller Cove Rd',
                    'sTCity' => 'Charlotte',
                    'sTStateProv' => 'NC',
                    'sTPstlCd' => '28202',
                    'sTCountry' => 'USA'
               ));
          }
     }

     return $line_items;
}
?>

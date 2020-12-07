<?php
function wp_stripe_shortcode( $atts )
{
    $options            = get_option( 'wp_stripe_options' );
    $current_user       = wp_get_current_user();
    $currentuserid      = $current_user->ID;
    $stripe_customer_id = get_user_meta( $currentuserid, 'stripeCustId', true );
    $url                = add_query_arg( array(
        'wp-stripe-iframe' => 'true',
        'keepThis' => 'true',
        'TB_iframe' => 'true',
        'height' => 580,
        'width' => 400 
    ), home_url() );
    $count              = 1;
    if ( isset( $options[ 'stripe_modal_ssl' ] ) && $options[ 'stripe_modal_ssl' ] === 'Yes' ) {
        $url = str_replace( 'http://', 'https://', $url, $count );
    }
    extract( shortcode_atts( array(
         'cards' => 'true' 
    ), $atts ) );
    if ( $cards === 'true' ) {
        $payments = '<div id="wp-stripe-types"></div>';
    }
    return $payments . '<a class="thickbox" id="wp-stripe-modal-button" title="' . esc_attr( $options[ 'stripe_header' ] ) . '" href="' . esc_url( $url ) . '"><span>' . esc_html( $options[ 'stripe_header' ] ) . '</span></a>';
}

/********************************************************************************************************************************************/
//
add_shortcode( 'wp-stripe', 'wp_stripe_shortcode' );
function wp_stripe_shortcode_legacy( $atts )
{
    return wp_stripe_form();
}

add_shortcode( 'wp-legacy-stripe', 'wp_stripe_shortcode_legacy' );
function wp_stripe_shortcode_authorize()
{
    return wp_stripe_form_authorize();
}

add_shortcode( 'wp-authorize-stripe', 'wp_stripe_shortcode_authorize' );
function wp_stripe_token( $data )
{
    $response = Stripe_Token::create( $data );
    return $response;
}

/********************************************************************************************************************************************/
//

function wp_stripe_charge( $amount, $description, $token )
{
    $options  = get_option( 'wp_stripe_options' );
    $currency = $options[ 'stripe_currency' ];
    $charge   = array(
        'amount' => $amount,
        'currency' => $currency,
        'card' => $token 
    );
    if ( $description ) {
        $charge[ 'description' ] = $description;
    } else
        $charge[ 'description' ] = "PrintAura Deposit";
    $response = Stripe_Charge::create( $charge );
    return $response;
}

function after_stripe_charge( $data )
{
    GLOBAL $wpdb;
    $userid        = $data[ 'user_id' ];
    $amount        = $data[ 'amount' ];
    $transactionid = $data[ 'charge_id' ];
    $check         = $wpdb->get_results( "SELECT `id` 
	                                  FROM `wp_transactions` 
					  WHERE `transactionid` = '$transactionid'" );
    $num           = $wpdb->num_rows;
    if ( $num == 0 ) {
	$query      = $wpdb->get_results( "SELECT `balance`,`user_email` 
		                           FROM `wp_users` 
					   WHERE `id` = $userid" );
        $balancerow = $wpdb->get_row( $query );
        $balance    = $balancerow[ 0 ];
        $payeremail = $balancerow[ 1 ];
        $newbalance = $balance + $amount;
        $newbalance = number_format( $newbalance, 2, '.', '' );
	$wpdb->query( "UPDATE `wp_users` 
		       SET `balance` = $newbalance 
		       WHERE `ID` = $userid" );
	$query   = $wpdb->query( "INSERT INTO `wp_transactions` (`id`, `userid`, `payeremail`, `amount`, `transactionid`,`balance`,`type`, `timestamp`) 
		                  VALUES (NULL, '$userid', '$payeremail', '$amount', '$transactionid','$newbalance' , '1', CURRENT_TIMESTAMP);" );
        $subject = "Your deposit has been successful.";
        $message = "Your payment has been successful and $" . number_format( $amount, 2, '.', '' ) . " has been added to your account. You may view your current balance by visiting https://printaura.com/billing/ or your transaction history at https://printaura.com/transactions-history/\n\nLet us know if you have any questions, \n\nPrintAura Team";
        $headers = 'From: PrintAura <team@printaura.com>' . "\r\n" . 'Reply-To: PrintAura <team@printaura.com>' . "\r\n" . 'Bcc: team@printaura.com' . "\r\n";
        wp_mail( $payeremail, $subject, $message, $headers );
        $getonholdorders = $wpdb->get_results( "SELECT `order_id`,`order_total` 
		                                FROM `wp_rmproductmanagement_orders` 
				                WHERE `user_id` = $userid 
                                                AND `status` = 'ON HOLD'  
                                                ORDER BY `order_id` ASC", ARRAY_A );
        $onholdnumbers   = $wpdb->num_rows;
        if ( $onholdnumbers > 0 ) {
            foreach ( $getonholdorders as $row ) {
                $order_id    = $orderrow[ 0 ];
                $order_total = $orderrow[ 1 ];
                $comp        = (float) ( $order_total - $newbalance );
                if ( $comp <= 0.0000001 ) {
                    $newbalance = (float) $newbalance - $order_total;
		    $wpdb->query( "UPDATE `wp_rmproductmanagement_orders` 
			           SET `status` = 'New'
				   WHERE `order_id` = $order_id" );
		   
		    $wpdb->query( "INSERT INTO `wp_rmproductmanagement_order_notes` (`note_id`, `order_id`, `user_id`, `changed_time`, `changed_field`)
			           VALUES (NULL, '$order_id', '$userid', now(), 'Order Paid for from Balance');" );
		   
		    $wpdb->query( "INSERT INTO `wp_rmproductmanagement_order_notes` (`note_id`, `order_id`, `user_id`, `changed_time`, `changed_field`) 
			           VALUES (NULL, '$order_id', '$userid', now(), 'Status Marked as New');" );
		   
		    $wpdb->query( "INSERT INTO `wp_transactions` (`id`, `userid`, `payeremail`, `amount`, `transactionid`,`balance`,`type`, `timestamp`) 
			           VALUES (NULL, '$userid', '', '$order_total', '$order_id','$newbalance' , '2', CURRENT_TIMESTAMP);" );
		   
		    $wpdb->query( "UPDATE `wp_users` SET `balance` = '$newbalance' WHERE `ID` = $userid" );
                }
                //else
                //  break;
            }
        }
    }
}

function wp_stripe_charge_initiate()
{
    // Security Check
    if ( !wp_verify_nonce( $_POST[ 'nonce' ], 'wp-stripe-nonce' ) ) {
        wp_die( __( 'Nonce verification failed!', 'wp-stripe' ) );
    }
    $current_user  = wp_get_current_user();
    $currentuserid = $current_user->ID;
    $has_customer  = true;
    // Define/Extract Variables
    $public        = sanitize_text_field( $_POST[ 'wp_stripe_public' ] );
    $name          = sanitize_text_field( $_POST[ 'wp_stripe_name' ] );
    $email         = sanitize_email( $_POST[ 'wp_stripe_email' ] );
    $card_number   = sanitize_text_field( $_POST[ 'wp_stripe_card_number' ] );
    $card_cvc      = sanitize_text_field( $_POST[ 'wp_stripe_card_cvc' ] );
    $exp_month     = sanitize_text_field( $_POST[ 'wp_stripe_expiry_month' ] );
    $exp_year      = sanitize_text_field( $_POST[ 'wp_stripe_expiry_year' ] );
    // Strip any comments from the amount
    $amount        = str_replace( ',', '', sanitize_text_field( $_POST[ 'wp_stripe_amount' ] ) );
    $amount        = str_replace( '$', '', $amount ) * 100;
    $customer_id   = get_user_meta( $currentuserid, 'stripeCustId', true );
    if ( $customer_id == "" ) {
        $has_customer = false;
        $customer     = Stripe_Customer::create( array(
            "card" => $_POST[ 'stripeToken' ],
            "email" => $email 
        ) );
        $customer_id  = $customer->id;
        update_user_meta( $currentuserid, 'stripeCustId', $customer_id );
    }
    // Create Charge
    try {
        if ( !$has_customer ) {
            $card    = array(
                 "card" => array(
                    'name' => $name,
                    'number' => $card_number,
                    'cvc' => $card_cvc,
                    'exp_month' => $exp_month,
                    'exp_year' => $exp_year 
                ) 
            );
            $token   = wp_stripe_token( $card );
            $tokenid = $token->id;
        } else
            $tokenid = $_POST[ 'stripeToken' ];
            $stripe_description = 'PrintAura Deposit';
            $response           = wp_stripe_charge( $amount, $stripe_description, $tokenid );
            $id                 = $response->id;
            $amount             = $response->amount / 100;
            $currency           = $response->currency;
            $created            = $response->created;
            $live               = $response->livemode;
            $paid               = $response->paid;
            if ( isset( $response->fee ) ) {
                $fee = $response->fee;
            }
        $result = '<div class="wp-stripe-notification wp-stripe-success"> ' . sprintf( __( 'Success, you just transferred %s', 'wp-stripe' ), '<span class="wp-stripe-currency">' . esc_html( $currency ) . '</span> ' . esc_html( $amount ) ) . ' !</div>';
        // Save Charge
        if ( $paid === true ) {
            if ( $currentuserid != 479 ) {
                $post_id = wp_insert_post( array(
                    'post_type' => 'wp-stripe-trx',
                    'post_author' => 1,
                    'post_content' => $stripe_description,
                    'post_title' => $id,
                    'post_status' => 'publish' 
                ) );
                // Define Livemode
                if ( $live ) {
                    $live = 'LIVE';
                } else {
                    $live = 'TEST';
                }
                // Define Public (for Widget)
                if ( $public === 'public' ) {
                    $public = 'YES';
                } else {
                    $public = 'NO';
                }
                // Update Meta
                update_post_meta( $post_id, 'wp-stripe-public', $public );
                update_post_meta( $post_id, 'wp-stripe-name', $name );
                update_post_meta( $post_id, 'wp-stripe-email', $email );
                update_post_meta( $post_id, 'wp-stripe-live', $live );
                update_post_meta( $post_id, 'wp-stripe-date', $created );
                update_post_meta( $post_id, 'wp-stripe-amount', $amount );
                update_post_meta( $post_id, 'wp-stripe-currency', strtoupper( $currency ) );
                if ( isset( $fee ) )
                    update_post_meta( $post_id, 'wp-stripe-fee', $fee );
            }
            $success_charge = array(
                'charge_id' => $id,
                'amount' => $amount,
                'user_id' => $currentuserid 
            );
            do_action( 'wp_stripe_successful_charge', $success_charge, $email );
            //do_action( 'wp_stripe_post_successful_charge', $response, $email, $stripe_comment );
            // Update Project
            // wp_stripe_update_project_transactions( 'add', $project_id , $post_id );
        }
        // Error
    }
    catch ( Exception $e ) {
        $result = '<div class="wp-stripe-notification wp-stripe-failure">' . 'Oops, something went wrong (' . $e->getMessage() . ') </div>';
        do_action( 'wp_stripe_post_fail_charge', $email, $e->getMessage() );
    }
    // Return Results to JS
    header( 'Content-Type: application/json' );
    echo json_encode( $result );
    exit;
}
/********************************************************************************************************************************************/
//
add_action( 'wp_ajax_wp_stripe_charge_initiate', 'wp_stripe_charge_initiate' );
add_action( 'wp_ajax_nopriv_wp_stripe_charge_initiate', 'wp_stripe_charge_initiate' );
add_action( 'wp_stripe_successful_charge', 'after_stripe_charge' );


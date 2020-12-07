<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">
    <title><?php _e('Stripe Payment', 'wp-stripe'); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(WP_STRIPE_URL) . 'css/wp-stripe-display.css'; ?>">
    <?php
    $current_user = wp_get_current_user();
    $currentuserid = $current_user->ID;
    $stripe_customer_id = get_user_meta($currentuserid, 'stripeCustId', true);
    $customer_id = ($stripe_customer_id != "") ? $stripe_customer_id : '';
    ?>
    <script type="text/javascript">
        //<![CDATA[
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var wpstripekey = '<?php echo esc_js(WP_STRIPE_KEY); ?>';
        var strip_customer_id = '<?php echo esc_js($customer_id);?>';
        //]]>;
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js#asyncload"></script>
    <script src="https://js.stripe.com/v1/"></script>
    <script src="<?php echo esc_js(WP_STRIPE_URL) . 'js/wp-stripe.js#asyncload'; ?>"></script>
    <script src="<?php echo esc_js(WP_STRIPE_URL) . 'js/ccvalidations.js#asyncload'; ?>"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('body').on('click', '#cvc_help_link', function () {
                if (jQuery("#cvc_help").is(":visible"))
                    jQuery("#cvc_help").hide("slow");
                else
                    jQuery("#cvc_help").show("slow");
            });
        });
    </script>

</head>

<body>

<?php echo wp_stripe_form(); ?>

</body>

</html>

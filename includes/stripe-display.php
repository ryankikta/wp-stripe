<?php

function wp_stripe_form()
{

    $options = get_option('wp_stripe_options');
    $currency = $options['stripe_currency'];
    $labels_on = $options['stripe_labels_on'];
    $placeholders_on = $options['stripe_placeholders_on'];
    $current_user = wp_get_current_user();
    $currentuserid = $current_user->ID;
    $user_name = $current_user->user_login;
    $user_email = $current_user->user_email;
    ob_start(); ?>
<!--script src='https://www.google.com/recaptcha/api.js' async defer></script-->

    <div id="wp-stripe-wrap">

        <form id="wp-stripe-payment-form">

            <input type="hidden" name="action" value="wp_stripe_charge_initiate"/>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp-stripe-nonce'); ?>"/>

            <div class="wp-stripe-details">

                <div class="wp-stripe-notification wp-stripe-failure payment-errors" style="display:none"></div>

                <input type="hidden" id="wp_stripe_name" rel="uyu" name="wp_stripe_name" class="wp-stripe-name"
                       value="<?php echo $user_name; ?>"/>


                <input type="hidden" id="wp_stripe_email" name="wp_stripe_email" class="wp-stripe-email"
                       value="<?php echo $user_email; ?>"/>


            </div>

            <div class="wp-stripe-card">
                <div class="stripe-row">
                    <?php $plugin_url = plugins_url() . '/wp-stripe/'; ?>
                    <input type="radio" class="lft-field" value="M" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide M"
                         src="<?php echo $plugin_url; ?>images/ico_mc.jpg">
                    <input type="radio" class="lft-field" value="V" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide V"
                         src="<?php echo $plugin_url; ?>images/ico_visa.jpg">
                    <input type="radio" class="lft-field" value="D" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide D"
                         src="<?php echo $plugin_url; ?>images/ico_disc.jpg">
                    <input type="radio" class="lft-field" value="A" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide A"
                         src="<?php echo $plugin_url; ?>images/ico_amex.jpg">
                    <div class="clr"></div>
                </div>
                <div class="stripe-row">
                    <?php if ($labels_on == 'Yes') : ?>
                        <label for="wp_stripe_amount"><?php printf(__('Amount (%s)', 'wp-stripe'), esc_html($currency)); ?></label>
                    <?php endif; ?>
                    <input type="text" id="wp_stripe_amount" name="wp_stripe_amount" autocomplete="off"
                           class="wp-stripe-card-amount" id="wp-stripe-card-amount"
                           <?php if ($placeholders_on == 'Yes') : ?>placeholder="<?php printf(__('Amount (%s)', 'wp-stripe'), $currency); ?> *"<?php endif; ?>
                           required/>
                </div>

                <div class="stripe-row">
                    <?php if ($labels_on == 'Yes') : ?>
                        <label for="card-number"><?php _e('Card Number', 'wp-stripe'); ?></label>
                    <?php endif; ?>
                    <input type="text" maxlength="16" onchange="checkNumHighlight(this.value);"
                           onblur="checkNumHighlight(this.value);"
                           onkeypress="checkNumHighlight(this.value);noAlpha(this);"
                           onkeyup="checkNumHighlight(this.value);checkFieldBack(this);noAlpha(this);" id="card-number"
                           name="wp_stripe_card_number" autocomplete="off" class="card-number"
                           <?php if ($placeholders_on == 'Yes') : ?>placeholder="<?php _e('Card Number', 'wp-stripe'); ?> *"<?php endif; ?>
                           required/>
                    <span class="ccresult"></span>
                </div>

                <div class="stripe-row">
                    <div class="stripe-row-left">
                        <?php if ($labels_on == 'Yes') : ?>
                            <label for="card-cvc"><?php _e('CVC Number', 'wp-stripe'); ?></label>
                        <?php endif; ?>
                        <input type="text" id="card-cvc" name="wp_stripe_card_cvc" autocomplete="off" class="card-cvc"
                               <?php if ($placeholders_on == 'Yes') : ?>placeholder="<?php _e('CVC Number', 'wp-stripe'); ?> *"<?php endif; ?>
                               maxlength="4" required/>
                        <span id="cvc_help_link"><img border="0" align="absmiddle"
                                                      src="<?php echo $plugin_url; ?>images/ico_question.jpg"></span>
                    </div>
                    <div class="stripe-row-right">
                        <label for="card-expiry" class="stripe-expiry">Expiry</label>
                        <select id="card-expiry" class="card-expiry-month" name="wp_stripe_expiry_month">
                            <option value="1">01</option>
                            <option value="2">02</option>
                            <option value="3">03</option>
                            <option value="4">04</option>
                            <option value="5">05</option>
                            <option value="6">06</option>
                            <option value="7">07</option>
                            <option value="8">08</option>
                            <option value="9">09</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                        <span></span>

                        <select class="card-expiry-year" name="wp_stripe_expiry_year">

                            <?php $year = date('Y', time());
                            $num = 1;

                            while ($num <= 17) { ?>

                                <option value="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></option>

                                <?php
                                $year++;
                                $num++;
                            } ?>

                        </select>
                    </div>

                </div>
                <div id="cvc_help" style="display:none;font-size:12px;" class="stripe-row">
                    <p>- For <strong>Visa</strong>, <strong>MasterCard</strong>, and <strong>Discover</strong> cards,
                        the card code is the last 3 digit number located on the BACK of your card on or above your
                        signature line.</p>
                    <p>- For <strong>American Express</strong> card, it is the 4 digits on the FRONT above the end of
                        your card number</p>
                    <img src="<?php echo $plugin_url; ?>images/cvv_info.jpg">
                </div>
            </div>

            <?php /*$options = get_option( 'wp_stripe_options' );

			if ( isset( $options['stripe_recent_switch'] ) && $options['stripe_recent_switch'] === 'Yes' ) { ?>

				<div class="wp-stripe-meta">

					<div class="stripe-row">

						<input type="checkbox" name="wp_stripe_public" value="public" checked="checked" /> <label><?php _e( 'Display on Website?', 'wp-stripe' ); ?></label>

						<p class="stripe-display-comment"><?php _e( 'If you check this box, the name as you enter it (including the avatar from your e-mail) and comment will be shown in recent donations. Your e-mail address and donation amount will not be shown.', 'wp-stripe' ); ?></p>

					</div>

				</div>

			<?php }; */ ?>

            <div style="clear:both"></div>
            <br>
            <div id="stripe-html"></div>
            <br>
            <input type="hidden" name="wp_stripe_form" value="1"/>
                <button id="stripe-manual-deposit" type="submit" class="stripe-submit-button" disabled="true"><span><?php _e('Submit Payment', 'wp-stripe'); ?></span></button>
            <br>
               <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
               <script>
               var onloadCallback = function() {
               grecaptcha.render('stripe-html', {
               'sitekey' : '6LcAndgUAAAAAPyETCBW4TJpi4Fx9BWZYstYN7ST',
               'callback' : correctCaptcha
               });
               };
               var i = 256;
               var correctCaptcha = function(response) {
               var Validator = response;
               //console.log(Validator);
               if (i > Validator.length) {
                   alert (Validator);
                   } else {
                   document.getElementById("stripe-manual-deposit").disabled = false;
               }
               //console.log(Validator.length);
               };
               </script>
            <div class="stripe-spinner"></div>
        </form>
    </div>

    <!--<div class="wp-stripe-poweredby"><?php printf(__('Payments powered by %s. No card information is stored on this server.', 'wp-stripe'), '<a href="http://wordpress.org/extend/plugins/wp-stripe" target="_blank">WP-Stripe</a>'); ?></div>-->

    <?php $output = apply_filters('wp_stripe_filter_form', ob_get_contents());

    ob_end_clean();

    return $output;

}

function wp_stripe_form_authorize()
{

    $current_user = wp_get_current_user();
    $currentuserid = $current_user->ID;
    $user_name = $current_user->user_login;
    $user_email = $current_user->user_email;
    $plugin_url = plugins_url() . '/wp-stripe/';
    ob_start(); ?>

    <div id="wp-stripe-wrap">

        <form id="wp-stripe-authorize-form">

            <input type="hidden" name="action" value="stripe_autopayment"/>

            <div class="wp-stripe-details">
                <div class="wp-stripe-notification wp-stripe-failure payment-errors" style="display:none"></div>
                <div class="wp-stripe-notification wp-stripe-success" style="display:none"></div>
                <input type="hidden" id="wp_stripe_name" name="wp_stripe_name" class="wp-stripe-name"
                       value="<?php echo $user_name; ?>"/>
                <input type="hidden" id="wp_stripe_email" name="wp_stripe_email" class="wp-stripe-email"
                       value="<?php echo $user_email; ?>"/>
            </div>

            <div class="wp-stripe-card">
                <div class="stripe-row">
                    <input type="radio" class="lft-field" value="M" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide M"
                         src="<?php echo $plugin_url; ?>images/ico_mc.jpg">
                    <input type="radio" class="lft-field" value="V" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide V"
                         src="<?php echo $plugin_url; ?>images/ico_visa.jpg">
                    <input type="radio" class="lft-field" value="D" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide D"
                         src="<?php echo $plugin_url; ?>images/ico_disc.jpg">
                    <input type="radio" class="lft-field" value="A" name="cctype">
                    <img align="absmiddle" class="lft-field cardhide A"
                         src="<?php echo $plugin_url; ?>images/ico_amex.jpg">
                    <div class="clr"></div>
                </div>

                <div class="stripe-row">
                    <label for="card-number"><?php _e('Card Number', 'wp-stripe'); ?></label>
                    <input type="text" maxlength="16" onchange="checkNumHighlight(this.value);"
                           onblur="checkNumHighlight(this.value);"
                           onkeypress="checkNumHighlight(this.value);noAlpha(this);"
                           onkeyup="checkNumHighlight(this.value);checkFieldBack(this);noAlpha(this);" id="card-number"
                           name="wp_stripe_card_number" autocomplete="off" class="card-number"
                           placeholder="Card Number *" required/>
                    <span class="ccresult"></span>
                </div>

                <div class="stripe-row">
                    <div class="stripe-row-left">
                        <label for="card-cvc"><?php _e('CVC Number', 'wp-stripe'); ?></label>
                        <input type="text" id="card-cvc" name="wp_stripe_card_cvc" autocomplete="off" class="card-cvc"
                               placeholder="CVC Number *" maxlength="4" required/>
                        <span id="cvc_help_link"><img border="0" align="absmiddle"
                                                      src="<?php echo $plugin_url; ?>images/ico_question.jpg"></span>
                    </div>
                    <div class="stripe-row-right">
                        <label for="card-expiry" class="stripe-expiry">Expiry</label>
                        <select id="card-expiry" class="card-expiry-month" name="wp_stripe_expiry_month">
                            <option value="1">01</option>
                            <option value="2">02</option>
                            <option value="3">03</option>
                            <option value="4">04</option>
                            <option value="5">05</option>
                            <option value="6">06</option>
                            <option value="7">07</option>
                            <option value="8">08</option>
                            <option value="9">09</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                        <span></span>

                        <select class="card-expiry-year" name="wp_stripe_expiry_year">

                            <?php $year = date('Y', time());
                            $num = 1;

                            while ($num <= 17) { ?>

                                <option value="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></option>

                                <?php
                                $year++;
                                $num++;
                            } ?>

                        </select>
                    </div>
                </div>
                <div id="cvc_help" style="display:none;font-size:12px;" class="stripe-row">
                    <p>- For <strong>Visa</strong>, <strong>MasterCard</strong>, and <strong>Discover</strong> cards,
                        the card code is the last 3 digit number located on the BACK of your card on or above your
                        signature line.</p>
                    <p>- For <strong>American Express</strong> card, it is the 4 digits on the FRONT above the end of
                        your card number</p>
                    <img src="<?php echo $plugin_url; ?>images/cvv_info.jpg">
                </div>
            </div>

            <div style="clear:both"></div>
            <br>
            <button type="submit" class="stripe-submit-button" id="stripe_authorize_form"><span><div class="spinner">&nbsp;</div>Authorize Automatic Billing</span>
            </button>
            <div class="stripe-spinner"></div>
        </form>
    </div>

    <?php $output = apply_filters('wp_stripe_filter_form', ob_get_contents());

    ob_end_clean();

    return $output;
}


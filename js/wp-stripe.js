Stripe.setPublishableKey(wpstripekey);

jQuery(document).ready(function ($) {

    var resetStripeForm = function () {
        $("#wp-stripe-payment-form").get(0).reset();
        $('input').removeClass('stripe-valid stripe-invalid');
    }

    function stripeResponseHandler(status, response) {
        if (response.error) {
            $('.stripe-submit-button').prop("disabled", false).css("opacity", "1.0");
            $(".payment-errors").show().html(response.error.message);
            $('.stripe-submit-button .spinner').fadeOut("slow");
            $('.stripe-submit-button span').removeClass('spinner-gap');

        } else {
            var $form = $("#wp-stripe-payment-form");
            var token = response['id'];
            $form.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
            var newStripeForm = $form.serialize();
            $.ajax({
                type: "post",
                dataType: "json",
                url: ajaxurl,
                data: newStripeForm,
                cache: false,
                success: function (response) {
                    $('.wp-stripe-details').prepend(response);
                    $('.stripe-submit-button').prop("disabled", false).css("opacity", "1.0");
                    $('.stripe-submit-button .spinner').fadeOut("slow");
                    $('.stripe-submit-button span').removeClass('spinner-gap');
                    resetStripeForm();

                }

            });

        }
    }

    $("#wp-stripe-payment-form").submit(function (event) {

        event.preventDefault();
        $(".wp-stripe-notification").hide();
        var amount = jQuery("#wp_stripe_amount").val();
        if (amount < 10) {
            $("#wp-stripe-payment-form .payment-errors").show().html("Minimum deposit amount is $10.00 Please try again");
            return false;
        }
        $('.stripe-submit-button').prop("disabled", true).css("opacity", "0.4");
        $('.stripe-submit-button .spinner').fadeIn("slow");
        $('.stripe-submit-button span').addClass('spinner-gap');

        Stripe.createToken({
            name: $('.wp-stripe-name').val(),
            number: $('.card-number').val(),
            cvc: $('.card-cvc').val(),
            exp_month: $('.card-expiry-month').val(),
            exp_year: $('.card-expiry-year').val()
        }, stripeResponseHandler);

        return false;

    });
});

// Form Validation & Enhancement

jQuery(document).ready(function ($) {

    $('.card-number').focusout(function () {

        var cardValid = Stripe.validateCardNumber($(this).val());
        var cardType = Stripe.cardType($(this).val());

        // Card Number Validation

        if (cardValid) {
            $(this).removeClass('stripe-invalid').addClass('stripe-valid');
        } else {
            $(this).removeClass('stripe-valid').addClass('stripe-invalid');
        }

        // Card Type Information

        /*
        if ( cardType && cardValid  ) {
            // Display Card Logo
        }
        */

    });

    // CVC Validation

    $('.card-cvc').focusout(function () {

        if (Stripe.validateCVC($(this).val())) {
            $(this).removeClass('stripe-invalid').addClass('stripe-valid');
        } else {
            $(this).removeClass('stripe-valid').addClass('stripe-invalid');
        }

    });

});


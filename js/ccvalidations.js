/******************************************************************************
 #                      PHP Stripe Payment Terminal v1.0
 #******************************************************************************
 #      Author:     Convergine.com
 #      Email:      info@convergine.com
 #      Website:    http://www.convergine.com
 #
 #
 #      Version:    1.0
 #      Copyright:  (c) 2012 - Convergine.com
 #
 #*******************************************************************************/
function isValidCardNumber(strNum) {
    var nCheck = 0;
    var nDigit = 0;
    var bEven = false;

    for (n = strNum.length - 1; n >= 0; n--) {
        var cDigit = strNum.charAt(n);
        if (isDigit(cDigit)) {
            var nDigit = parseInt(cDigit, 10);
            if (bEven) {
                if ((nDigit *= 2) > 9)
                    nDigit -= 9;
            }
            nCheck += nDigit;
            bEven = !bEven;
        } else if (cDigit != ' ' && cDigit != '.' && cDigit != '-') {
            return false;
        }
    }
    return (nCheck % 10) == 0;
}

function isExpiryDate(year, month) {
    today = new Date();
    expiry = new Date(year, month);
    if (today.getTime() > expiry.getTime()) return false;
    else return true;
}

function isNum(argvalue) {
    argvalue = argvalue.toString();

    if (argvalue.length == 0)
        return false;

    for (var n = 0; n < argvalue.length; n++)
        if (argvalue.substring(n, n + 1) < "0" || argvalue.substring(n, n + 1) > "9")
            return false;

    return true;
}

function isDigit(c) {
    var strAllowed = "1234567890";
    return (strAllowed.indexOf(c) != -1);
}

function isCardTypeCorrect(strNum, type) {
    var nLen = 0;
    for (n = 0; n < strNum.length; n++) {
        if (isDigit(strNum.substring(n, n + 1)))
            ++nLen;
    }

    if (type == 'V')
        return ((strNum.substring(0, 1) == '4') && (nLen == 13 || nLen == 16));
    else if (type == 'A')
        return ((strNum.substring(0, 2) == '34' || strNum.substring(0, 2) == '37') && (nLen == 15));
    else if (type == 'M')
        return ((strNum.substring(0, 2) == '51' || strNum.substring(0, 2) == '52'
            || strNum.substring(0, 2) == '53' || strNum.substring(0, 2) == '54'
            || strNum.substring(0, 2) == '55') && (nLen == 16));
    else if (type == 'D')
        return ((strNum.substring(0, 4) == '6011' || strNum.substring(0, 3) == '622'
            || strNum.substring(0, 2) == '64' || strNum.substring(0, 2) == '65') && (nLen == 16));
    else if (type == 'DI')
        return ((strNum.substring(0, 3) == '300' || strNum.substring(0, 3) == '301' || strNum.substring(0, 3) == '302' || strNum.substring(0, 3) == '303' || strNum.substring(0, 3) == '304'
            || strNum.substring(0, 3) == '305' || strNum.substring(0, 2) == '36' || strNum.substring(0, 2) == '38') && (nLen == 14));
    else
        return false;

}

function highlightCard(strNum) {

    if ((strNum.substring(0, 1) == '4')) {
        return "V";
    } else if ((strNum.substring(0, 2) == '34' || strNum.substring(0, 2) == '37')) {
        return "A";
    } else if ((strNum.substring(0, 2) == '51' || strNum.substring(0, 2) == '52'
        || strNum.substring(0, 2) == '53' || strNum.substring(0, 2) == '54'
        || strNum.substring(0, 2) == '55')) {
        return "M";
    } else if ((strNum.substring(0, 4) == '6011' || strNum.substring(0, 3) == '622'
        || strNum.substring(0, 2) == '64' || strNum.substring(0, 2) == '65')) {
        return "D";
    } else if ((strNum.substring(0, 3) == '300' || strNum.substring(0, 3) == '301' || strNum.substring(0, 3) == '302' || strNum.substring(0, 3) == '303' || strNum.substring(0, 3) == '304'
        || strNum.substring(0, 3) == '305' || strNum.substring(0, 2) == '36' || strNum.substring(0, 2) == '38')) {
        return "DI";
    } else {
        return false;
    }
}

var selectedCard = "";

function checkNumHighlight(strNum) {
    previewCCResult(strNum);
    if (selectedCard == "") {
        var cctype = highlightCard(strNum);
        if (cctype == false) {
        } else {
            //jQuery("img.cardhide:not([class*="+cctype+"]").fadeTo("fast",0.1);
            selectedCard = cctype;
            jQuery(":radio[value=" + cctype + "]").attr("checked", "checked");
            jQuery("img.cardhide:not([class*=" + cctype + "])").each(function () {
                jQuery(this).fadeTo("fast", 0.1);
            });

        }
    } else if (strNum == "") {
        jQuery("img.cardhide").fadeTo("fast", 1);
        selectedCard = "";
        jQuery(":radio[name=cctype]").attr("checked", "");
    }
}

function resetCCHightlight() {
    selectedCard = "";
    jQuery("img.cardhide").fadeTo("fast", 1);
}

function previewCCResult(strNum) {
    if (isValidCardNumber(strNum) && strNum.length > 13) {
        jQuery(".ccresult").html("");
    } else {
        jQuery(".ccresult").html("<span class='error'>Invalid Number</span>");
    }
}

function checkFieldBack(fieldObj) {
    if (fieldObj.value != 0) {
        fieldObj.style.backgroundColor = '#F8F8F8';
    }
}

function noAlpha(obj) {
    reg = /[^0-9.,]/g;
    obj.value = obj.value.replace(reg, "");
}

jQuery(document).ready(function () {
    jQuery("input[name=wp_stripe_card_number]").bind('paste', function (e) {
        var el = jQuery(this);
        setTimeout(function () {
            var text = el.val();
            resetCCHightlight();
            checkNumHighlight(text);
        }, 100);
    });

});
const BANKVAL_ACTION = 'bankval_form';

jQuery(document).ready(function() {

    var form = jQuery('#bankval-form');
    form.find('.response-group #closeButton').click(function() {
        jQuery(this).parent().css('display', 'none');
    });

    form.find('#accno').on('input', function() {
        var value = jQuery(this).val();
        jQuery(this).val(value.replace(/\D/g, ''));
    });

    form.find('#sortcode').on('input', function() {
        var value = jQuery(this).val();
        jQuery(this).val(value.replace(/\D/g, ''));
    });

    form.on("submit", function(e) {
        
        var formData = {};
        formData['action'] = BANKVAL_ACTION;
        jQuery.each(jQuery(this).serializeArray(), function() {
            formData[this.name] = this.value;
        });

        var jqxhr = jQuery.post({
            url: BANKVAL_DATA.ajaxUrl,
            data: formData,
            error: function(jqxhr, textStatus, error) {
                var responseGroup = jQuery('#bankval-form .response-group');
                responseGroup.find('#message').text('Server error');
                responseGroup.css('display', 'flex')
                    .addClass('failure-response')
                    .removeClass('valid-response')
                    .removeClass('invalid-response');

            },
            success: function(data, textStatus, jqxhr) {
                var status = data['data']['status'];
                var result = data['data']['result'];
                
                var responseGroup = jQuery('#bankval-form .response-group');
                responseGroup.find('#message').text(result);
                if (result.toLowerCase().includes('invalid')) {
                    responseGroup.css('display', 'flex')
                        .removeClass('valid-response')
                        .addClass('invalid-response');
                } else {
                    responseGroup.css('display', 'flex')
                        .removeClass('invalid-response')
                        .addClass('valid-response');
                }

            }
        });

        jqxhr.always(function() {
            jQuery('#bankval-form .submit-group button').prop('disabled', false);
        });

        e.preventDefault();
        jQuery(this).find('.submit-group button').prop('disabled', true);
    });
});
/**
 * Fleetwire Admin JavaScript
 * 
 * @since 1.0.21
 */
jQuery(document).ready(function ($) {
    $('#fleetwire_form').on('submit', function (e) {
        e.preventDefault();
        
        const companyUuid = $('.fleetwire-company-uuid').val();
        const $form = $(this);
        const $submitButton = $form.find('input[type="submit"]');
        const $message = $('.fleetwire-card .updated');
        
        // Disable submit button during request
        $submitButton.prop('disabled', true);
        
        // Hide any existing messages
        $message.removeClass('fleetwire-d-block').addClass('fleetwire-d-none');

        $.ajax({
            type: 'POST',
            url: fleetwireAdmin.ajaxUrl,
            data: {
                'fleetwire_company_name': companyUuid,
                'fw_nonce': fleetwireAdmin.nonce,
                'action': 'fleetwire_company_name_form_process'
            },
            success: function (result) {
                if (result.success) {
                    $message
                        .removeClass('fleetwire-d-none')
                        .addClass('fleetwire-d-block');
                } else {
                    alert(result.data.message || fleetwireAdmin.strings.error);
                }
            },
            error: function (xhr, status, error) {
                console.error('Fleetwire AJAX Error:', error);
                alert(fleetwireAdmin.strings.error);
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false);
            }
        });
    });
});

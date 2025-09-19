/**
 * Fleetwire Admin JavaScript
 * 
 * @since 1.0.21
 */

jQuery(document).ready(function ($) {
    'use strict';
    
    // Initialize the admin interface
    initFleetwireAdmin();
    
    function initFleetwireAdmin() {
        initFormSubmission();
        initCopyToClipboard();
        initConnectionStatus();
        initTooltips();
    }
    
    // Enhanced form submission with better UX
    function initFormSubmission() {
        $('#fleetwire_form').on('submit', function (e) {
            e.preventDefault();
            
            const companyUuid = $('.fleetwire-company-uuid').val().trim();
            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');
            // Clear any existing toast notifications
            $('.fleetwire-toast').remove();
            
            // Validate input
            if (!companyUuid) {
                showToast('Company ID is required.', 'error');
                return;
            }
            
            // Show loading state
            $submitButton.addClass('fleetwire-loading').prop('disabled', true);
            $submitButton.val('Saving...');

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
                        showToast(result.data.message || 'Options saved successfully!', 'success');
                        // Update connection status
                        updateConnectionStatus(true);
                    } else {
                        // Handle "no changes" as info, not error
                        const message = result.data.message || fleetwireAdmin.strings.error;
                        const type = message.includes('No changes made') ? 'info' : 'error';
                        showToast(message, type);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Fleetwire AJAX Error:', error);
                    showToast(fleetwireAdmin.strings.error, 'error');
                },
                complete: function() {
                    // Reset button state
                    $submitButton.removeClass('fleetwire-loading').prop('disabled', false);
                    $submitButton.val('Update Options');
                }
            });
        });
    }
    
    // Copy to clipboard functionality
    function initCopyToClipboard() {
        // Add copy buttons to shortcodes
        $('code').each(function() {
            const $code = $(this);
            const shortcode = $code.text();
            
            if (shortcode.startsWith('[') && shortcode.endsWith(']')) {
                $code.addClass('fleetwire-shortcode');
                $code.attr('title', 'Click to copy');
                
                $code.on('click', function() {
                    copyToClipboard(shortcode);
                });
            }
        });
    }
    
    // Copy text to clipboard
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            // Modern clipboard API
            navigator.clipboard.writeText(text).then(function() {
                showToast('Shortcode copied to clipboard!', 'success');
            }).catch(function() {
                fallbackCopyToClipboard(text);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyToClipboard(text);
        }
    }
    
    // Fallback copy method
    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showToast('Shortcode copied to clipboard!', 'success');
        } catch (err) {
            showToast('Failed to copy shortcode', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Connection status indicator
    function initConnectionStatus() {
        const companyId = $('.fleetwire-company-uuid').val();
        updateConnectionStatus(!!companyId);
    }
    
    function updateConnectionStatus(isConnected) {
        const $statusContainer = $('.fleetwire-connection-status');
        
        if ($statusContainer.length === 0) {
            // Create a status container if it doesn't exist
            $('.fleetwire-company-uuid').after('<div class="fleetwire-connection-status"></div>');
        }
        
        const statusHtml = isConnected 
            ? '<div class="fleetwire-status connected">Connected to Fleetwire</div>'
            : '<div class="fleetwire-status disconnected">Not connected</div>';
            
        $('.fleetwire-connection-status').html(statusHtml);
    }
    
    // Simple tooltips
    function initTooltips() {
        $('[title]').each(function() {
            const $element = $(this);
            const title = $element.attr('title');
            
            if (title) {
                $element.removeAttr('title');
                $element.on('mouseenter', function() {
                    showTooltip($element, title);
                }).on('mouseleave', function() {
                    hideTooltip();
                });
            }
        });
    }
    
    function showTooltip($element, text) {
        const tooltip = $('<div class="fleetwire-tooltip">' + text + '</div>');
        $('body').append(tooltip);
        
        const offset = $element.offset();
        tooltip.css({
            position: 'absolute',
            top: offset.top - tooltip.outerHeight() - 5,
            left: offset.left + ($element.outerWidth() / 2) - (tooltip.outerWidth() / 2),
            zIndex: 9999
        });
    }
    
    function hideTooltip() {
        $('.fleetwire-tooltip').remove();
    }
    
    
    // Toast notifications
    function showToast(message, type = 'info') {
        const toast = $(`
            <div class="fleetwire-toast fleetwire-toast-${type}">
                ${message}
            </div>
        `);
        
        $('body').append(toast);
        
        // Animate in
        setTimeout(function() {
            toast.addClass('show');
        }, 50);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            toast.removeClass('show');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }
});

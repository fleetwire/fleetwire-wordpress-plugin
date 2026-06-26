/**
 * Fleetwire Admin JavaScript
 *
 * @since 1.0.21
 */

jQuery(document).ready(function ($) {
    'use strict';

    const $companyInput = $('.fleetwire-company-uuid');
    const $apiBaseUrlInput = $('.fleetwire-api-base-url');
    const $apiEnvironmentInput = $('.fleetwire-api-environment');
    const $localApiUrlRow = $('.fleetwire-local-api-url-row');
    const $localCompanyInput = $('.fleetwire-local-company-uuid');
    const $integrationTokenInput = $('.fleetwire-integration-token');
    const $connectionPanel = $('.fleetwire-connection-panel');
    const $connectionMessage = $('.fleetwire-connection-message');
    const $checkConnectionButton = $('.fleetwire-check-connection');
    const $previewStore = $('.fleetwire-preview-store');
    const $loadDashboardButton = $('.fleetwire-load-dashboard');
    const $dashboardMessage = $('.fleetwire-dashboard-message');
    const $dashboardPickups = $('.fleetwire-dashboard-pickups');
    const $dashboardReturns = $('.fleetwire-dashboard-returns');
    const $loadListingsButton = $('.fleetwire-load-listings');
    const $listingFilter = $('.fleetwire-listing-filter');
    const $listingSelect = $('.fleetwire-listing-select');
    const $embedType = $('.fleetwire-embed-type');
    const $buttonTitle = $('.fleetwire-button-title');
    const $descriptionLength = $('.fleetwire-description-length');
    const $buttonTitleField = $('.fleetwire-button-title-field');
    const $descriptionLengthField = $('.fleetwire-description-length-field');
    const $generatedShortcode = $('.fleetwire-generated-shortcode-code');
    const $copyGeneratedShortcode = $('.fleetwire-copy-generated-shortcode');
    const $listingPickerMessage = $('.fleetwire-listing-picker-message');
    var fleetwireListings = [];
    var generatedShortcode = '';

    initFormSubmission();
    initCopyToClipboard();
    initApiEnvironment();
    initDashboard();
    initListingPicker();
    initConnectionStatus();
    initTooltips();

    function initFormSubmission() {
        $('#fleetwire_form').on('submit', function (e) {
            e.preventDefault();

            const companyUuid = $companyInput.val().trim();
            const apiEnvironment = getApiEnvironment();
            const localCompanyUuid = $localCompanyInput.length ? $localCompanyInput.val().trim() : '';
            const apiBaseUrl = $apiBaseUrlInput.length ? $apiBaseUrlInput.val().trim() : '';
            const integrationToken = $integrationTokenInput.val().trim();
            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');

            $('.fleetwire-toast').remove();

            if (!companyUuid) {
                setConnectionStatus('disconnected', 'Enter your Company ID to connect this site.');
                showToast('Company ID is required.', 'error');
                return;
            }

            $submitButton.addClass('fleetwire-loading').prop('disabled', true).val('Saving...');
            setConnectionStatus('checking', 'Saving Company ID...');

            $.ajax({
                type: 'POST',
                url: fleetwireAdmin.ajaxUrl,
                data: {
                    fleetwire_company_name: companyUuid,
                    fleetwire_api_environment: apiEnvironment,
                    fleetwire_local_company_id: localCompanyUuid,
                    fleetwire_api_base_url: apiBaseUrl,
                    fleetwire_integration_token: integrationToken,
                    fw_nonce: fleetwireAdmin.nonce,
                    action: 'fleetwire_company_name_form_process'
                },
                success: function (result) {
                    if (result.success) {
                        if (result.data.apiBaseUrl) {
                            if (result.data.apiEnvironment === 'local') {
                                $apiBaseUrlInput.val(result.data.apiBaseUrl);
                            }
                        }
                        fleetwireAdmin.apiEnvironment = result.data.apiEnvironment || apiEnvironment;
                        fleetwireAdmin.hasIntegrationToken = !!result.data.hasIntegrationToken;
                        updatePreviewLink(result.data.storeUrl || '');
                        showToast(result.data.message || fleetwireAdmin.strings.success, 'success');
                        verifyConnection(companyUuid);
                        return;
                    }

                    const message = result.data && result.data.message ? result.data.message : fleetwireAdmin.strings.error;
                    setConnectionStatus('error', message);
                    showToast(message, 'error');
                },
                error: function () {
                    setConnectionStatus('error', fleetwireAdmin.strings.error);
                    showToast(fleetwireAdmin.strings.error, 'error');
                },
                complete: function () {
                    $submitButton.removeClass('fleetwire-loading').prop('disabled', false).val('Update Options');
                }
            });
        });

        $checkConnectionButton.on('click', function () {
            verifyConnection($companyInput.val().trim());
        });

        $companyInput.on('input', function () {
            updatePreviewLink('');
            if ($(this).val().trim()) {
                setConnectionStatus('pending', 'Save or check this Company ID to verify the Fleetwire connection.');
            } else {
                setConnectionStatus('disconnected', 'Enter your Company ID to connect this site.');
            }
        });
    }

    function initApiEnvironment() {
        updateApiEnvironmentFields();

        $apiEnvironmentInput.on('change', function () {
            updateApiEnvironmentFields();
            if ($companyInput.val().trim()) {
                setConnectionStatus('pending', 'Save this API target before checking the Fleetwire connection.');
            }
        });

        $localCompanyInput.on('input', function () {
            const localCompanyUuid = $(this).val().trim();
            if (localCompanyUuid && !$apiBaseUrlInput.val().trim()) {
                $apiBaseUrlInput.val('https://' + localCompanyUuid + '.fleetwire.test');
            }
        });
    }

    function getApiEnvironment() {
        const selected = $apiEnvironmentInput.filter(':checked').val();
        return selected || fleetwireAdmin.apiEnvironment || 'live';
    }

    function updateApiEnvironmentFields() {
        const isLocal = getApiEnvironment() === 'local';
        $localApiUrlRow.toggle(isLocal);
    }

    function initDashboard() {
        $loadDashboardButton.on('click', function () {
            loadDashboard();
        });
    }

    function loadDashboard() {
        if (!$companyInput.val().trim()) {
            setDashboardMessage('Enter and save your Company ID before loading dashboard data.', 'error');
            return;
        }

        if (!$integrationTokenInput.val().trim() && !fleetwireAdmin.hasIntegrationToken) {
            setDashboardMessage('Add and save a Fleetwire WordPress integration token before loading dashboard data.', 'error');
            return;
        }

        setDashboardMessage('Loading dashboard...', 'pending');
        $loadDashboardButton.prop('disabled', true).addClass('fleetwire-loading');

        $.ajax({
            type: 'POST',
            url: fleetwireAdmin.ajaxUrl,
            data: {
                fw_nonce: fleetwireAdmin.dashboardNonce,
                action: 'fleetwire_fetch_dashboard_summary'
            },
            success: function (result) {
                if (!result.success) {
                    const message = result.data && result.data.message ? result.data.message : 'Could not load dashboard data.';
                    setDashboardMessage(message, 'error');
                    return;
                }

                renderDashboard(result.data.dashboard || {});
                setDashboardMessage('Dashboard loaded.', 'success');
            },
            error: function () {
                setDashboardMessage('Could not load dashboard data from Fleetwire.', 'error');
            },
            complete: function () {
                $loadDashboardButton.prop('disabled', false).removeClass('fleetwire-loading');
            }
        });
    }

    function renderDashboard(dashboard) {
        const summary = dashboard.summary || {};

        $('[data-fleetwire-dashboard-field]').each(function () {
            const $field = $(this);
            const key = $field.data('fleetwire-dashboard-field');
            const value = summary[key];
            $field.text(typeof value === 'number' ? value : '-');
        });

        renderDashboardList($dashboardPickups, dashboard.upcoming_pickups || [], 'No pickups in the next 7 days.');
        renderDashboardList($dashboardReturns, dashboard.upcoming_returns || [], 'No returns in the next 7 days.');
    }

    function renderDashboardList($list, items, emptyMessage) {
        $list.empty();

        if (!items.length) {
            $list.append($('<li></li>').text(emptyMessage));
            return;
        }

        items.forEach(function (booking) {
            const listing = booking.listing_name || booking.listing_id || 'Listing';
            const dates = formatBookingDates(booking);
            const status = booking.status ? ' - ' + booking.status : '';
            $list.append($('<li></li>').text(listing + (dates ? ' - ' + dates : '') + status));
        });
    }

    function formatBookingDates(booking) {
        const start = formatDateTime(booking.trip_start);
        const end = formatDateTime(booking.trip_end);

        if (start && end) {
            return start + ' to ' + end;
        }

        return start || end || '';
    }

    function formatDateTime(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function setDashboardMessage(message, status) {
        $dashboardMessage
            .removeClass('is-success is-error is-warning is-pending')
            .addClass(status ? 'is-' + status : '')
            .text(message || '');
    }

    function initCopyToClipboard() {
        $('.fleetwire-copy-shortcode').on('click', function () {
            const $button = $(this);
            const shortcode = $button.data('shortcode');

            copyTextWithButtonState(shortcode, $button);
        });

        $copyGeneratedShortcode.on('click', function () {
            if (!generatedShortcode) {
                return;
            }

            copyTextWithButtonState(generatedShortcode, $(this));
        });
    }

    function initListingPicker() {
        $buttonTitleField.hide();
        $descriptionLengthField.hide();

        $loadListingsButton.on('click', function () {
            loadListings();
        });

        $listingFilter.on('input', function () {
            renderListingOptions($(this).val());
        });

        $listingSelect.on('change', updateGeneratedShortcode);
        $embedType.on('change', function () {
            updateEmbedOptionFields();
            updateGeneratedShortcode();
        });
        $buttonTitle.on('input', updateGeneratedShortcode);
        $descriptionLength.on('input', updateGeneratedShortcode);
    }

    function loadListings() {
        const companyUuid = $companyInput.val().trim();

        if (!companyUuid) {
            setListingPickerMessage('Enter and save your Company ID before loading listings.', 'error');
            return;
        }

        setListingPickerMessage('Loading listings...', 'pending');
        $loadListingsButton.prop('disabled', true).addClass('fleetwire-loading');

        $.ajax({
            type: 'POST',
            url: fleetwireAdmin.ajaxUrl,
            data: {
                fleetwire_company_name: companyUuid,
                fw_nonce: fleetwireAdmin.listingsNonce,
                action: 'fleetwire_fetch_listings'
            },
            success: function (result) {
                if (!result.success) {
                    const message = result.data && result.data.message ? result.data.message : 'Could not load listings.';
                    setListingPickerMessage(message, 'error');
                    return;
                }

                fleetwireListings = result.data.listings || [];
                renderListingOptions($listingFilter.val());

                if (fleetwireListings.length === 0) {
                    setListingPickerMessage('No listings were returned for this Fleetwire company.', 'warning');
                    return;
                }

                setListingPickerMessage('Loaded ' + fleetwireListings.length + ' listings.', 'success');
            },
            error: function () {
                setListingPickerMessage('Could not load listings from Fleetwire.', 'error');
            },
            complete: function () {
                $loadListingsButton.prop('disabled', false).removeClass('fleetwire-loading');
            }
        });
    }

    function renderListingOptions(filter) {
        const query = (filter || '').toLowerCase().trim();
        const selectedId = $listingSelect.val();
        const filteredListings = fleetwireListings.filter(function (listing) {
            const haystack = (listing.name + ' ' + listing.id).toLowerCase();
            return !query || haystack.includes(query);
        });

        $listingSelect.empty();

        if (filteredListings.length === 0) {
            $listingSelect.append($('<option></option>').val('').text(fleetwireListings.length ? 'No matching listings' : 'Load listings to choose one'));
            $listingSelect.prop('disabled', true);
            updateGeneratedShortcode();
            return;
        }

        filteredListings.forEach(function (listing) {
            const status = listing.paused ? 'paused' : (listing.active ? 'active' : 'inactive');
            const label = listing.name + ' (' + listing.id + ') - ' + status;
            $listingSelect.append($('<option></option>').val(listing.id).text(label));
        });

        $listingSelect.prop('disabled', false);

        if (selectedId && filteredListings.some(function (listing) { return listing.id === selectedId; })) {
            $listingSelect.val(selectedId);
        }

        updateGeneratedShortcode();
    }

    function updateEmbedOptionFields() {
        const type = $embedType.val();
        $buttonTitleField.toggle(type === 'button');
        $descriptionLengthField.toggle(type === 'description');
    }

    function updateGeneratedShortcode() {
        const listingId = $listingSelect.val();
        const type = $embedType.val();

        if (!listingId) {
            generatedShortcode = '';
            $generatedShortcode.text('Select a listing to generate a shortcode.');
            $copyGeneratedShortcode.prop('disabled', true);
            return;
        }

        generatedShortcode = buildShortcode(type, listingId);
        $generatedShortcode.text(generatedShortcode);
        $copyGeneratedShortcode.prop('disabled', false);
    }

    function buildShortcode(type, listingId) {
        switch (type) {
            case 'button':
                return '[fleetwire_button id="' + listingId + '" custom-title="' + escapeShortcodeAttribute($buttonTitle.val() || 'Book now') + '" show-price=1]';
            case 'description':
                return '[fleetwire_listing_description id="' + listingId + '" length=' + parseInteger($descriptionLength.val(), 100) + ']';
            case 'title':
                return '[fleetwire_listing_title id="' + listingId + '"]';
            case 'price':
                return '[fleetwire_listing_price id="' + listingId + '"]';
            case 'image':
                return '[fleetwire_listing_image id="' + listingId + '"]';
            case 'card':
            default:
                return '[fleetwire_listing_card id="' + listingId + '"]';
        }
    }

    function escapeShortcodeAttribute(value) {
        return String(value).replace(/"/g, '&quot;');
    }

    function parseInteger(value, fallback) {
        const parsed = parseInt(value, 10);
        return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
    }

    function setListingPickerMessage(message, status) {
        $listingPickerMessage
            .removeClass('is-success is-error is-warning is-pending')
            .addClass(status ? 'is-' + status : '')
            .text(message || '');
    }

    function copyTextWithButtonState(text, $button) {
        copyToClipboard(text).then(function () {
            const originalText = $button.text();
            $button.text('Copied').addClass('is-copied');

            setTimeout(function () {
                $button.text(originalText).removeClass('is-copied');
            }, 1600);
        }).catch(function () {
            showToast('Failed to copy shortcode.', 'error');
        });
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
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
                resolve();
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(textArea);
            }
        });
    }

    function initConnectionStatus() {
        const companyId = $companyInput.val().trim();

        if (!companyId) {
            setConnectionStatus('disconnected', 'Enter your Company ID to connect this site.');
            updatePreviewLink('');
            return;
        }

        updatePreviewLink($connectionPanel.data('store-url') || fleetwireAdmin.storeUrl || '');
        verifyConnection(companyId);
    }

    function verifyConnection(companyId) {
        if (!companyId) {
            setConnectionStatus('disconnected', 'Enter your Company ID to connect this site.');
            updatePreviewLink('');
            return;
        }

        setConnectionStatus('checking', 'Checking Fleetwire connection...');
        $checkConnectionButton.prop('disabled', true).addClass('fleetwire-loading');

        $.ajax({
            type: 'POST',
            url: fleetwireAdmin.ajaxUrl,
            data: {
                fleetwire_company_name: companyId,
                fw_nonce: fleetwireAdmin.verifyNonce,
                action: 'fleetwire_verify_company_connection'
            },
            success: function (result) {
                if (result.success) {
                    const companyName = result.data.companyName ? ' ' + result.data.companyName : '';
                    setConnectionStatus('connected', 'Connected to Fleetwire.' + companyName);
                    updatePreviewLink(result.data.storeUrl || '');
                    return;
                }

                const message = result.data && result.data.message ? result.data.message : 'Fleetwire could not verify that Company ID.';
                const status = result.data && result.data.status === 'unreachable' ? 'warning' : 'error';
                setConnectionStatus(status, message);
            },
            error: function () {
                setConnectionStatus('warning', 'Could not reach Fleetwire. Check your site connection and try again.');
            },
            complete: function () {
                $checkConnectionButton.prop('disabled', false).removeClass('fleetwire-loading');
            }
        });
    }

    function setConnectionStatus(status, message) {
        $connectionPanel
            .removeClass('is-connected is-disconnected is-checking is-warning is-error is-pending')
            .addClass('is-' + status);
        $connectionMessage.text(message);
    }

    function updatePreviewLink(storeUrl) {
        if (storeUrl) {
            $previewStore.attr('href', storeUrl).removeClass('disabled').attr('aria-disabled', 'false');
            return;
        }

        $previewStore.attr('href', '#').addClass('disabled').attr('aria-disabled', 'true');
    }

    function initTooltips() {
        $('[title]').each(function () {
            const $element = $(this);
            const title = $element.attr('title');

            if (!title) {
                return;
            }

            $element.removeAttr('title');
            $element.on('mouseenter', function () {
                showTooltip($element, title);
            }).on('mouseleave', hideTooltip);
        });
    }

    function showTooltip($element, text) {
        const tooltip = $('<div class="fleetwire-tooltip"></div>').text(text);
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

    function showToast(message, type = 'info') {
        const toast = $('<div></div>')
            .addClass('fleetwire-toast fleetwire-toast-' + type)
            .text(message);

        $('body').append(toast);

        setTimeout(function () {
            toast.addClass('show');
        }, 50);

        setTimeout(function () {
            toast.removeClass('show');
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 3000);
    }
});

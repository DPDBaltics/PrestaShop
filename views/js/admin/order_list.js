/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

$(document).ready(function () {
    $('.js-bulk-action-submit-btn-dpd-labels').on('click', function (e){
        var form = $('form[name="order"]');
        form.submit(function() {
            form.attr('target', '_blank');
            return true;
        });
    })

    $(document).on('click', '.print-dpd-label', function () {
        toggleMessageBoxPrinted();
        $.ajax(dpdHookAjaxShipmentController, {
            data: {
                id_order: $(this).data('id-order'),
                action: 'printLabelFromList',
                ajax: 1
            },
            success: function (response) {
                response = JSON.parse(response);
                // toggleMessageBoxResult(response.success, response.message);

                // $.each(response.icon_replacers, function (key, value) {
                //     replaceIcon(value.id_order, value.icon_replacer);
                // });

                if (response.status) {
                    var location = window.location +
                        '&print_label=1' +
                        '&id_dpd_shipment=' + encodeURIComponent(response.id_dpd_shipment);
                    if (!is_label_download_option) {
                        window.open(location, '_blank');
                    } else {
                        window.location.href = location;
                    }
                    hideMessageBoxPrinted()
                }

                if (!response.status) {
                    toggleMessageBoxResult(false, response.message);
                }
                return;
            },
            error: function(xhr, ajaxOptions, thrownError) {
                toggleMessageBoxResult(false, xhr.statusText);
            }
        });
        return false;
    });

    $(document).on('click', '#download-selected-labels', function (event) {
        event.preventDefault();
        var selectedOrderIdArray = [];

        $(".row-selector input:checked").each(function()
        {
            selectedOrderIdArray.push($(this).val());
        });

        toggleMessageBoxPrinted();
        $.ajax(dpdHookAjaxShipmentController, {
            data: {
                order_ids: JSON.stringify(selectedOrderIdArray),
                action: 'printMultipleLabelsFromList',
                ajax: 1
            },
            success: function (response) {
                response = JSON.parse(response);
                toggleMessageBoxResult(response.status, response.message);
                if (response.shipment_ids) {
                    var location = window.location +
                        '&print_multiple_labels=1' +
                        '&shipment_ids=' + encodeURIComponent(response.shipment_ids);
                    window.open(location, '_blank');
                    hideMessageBoxPrinted();
                    return;
                }

                if (!response.status) {
                    toggleMessageBoxResult(false, response.message);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                toggleMessageBoxResult(false, xhr.statusText);
            }
        });
    });

    function toggleMessageBoxPrinted()
    {
        var messageBox = $('.message-text-box');
        messageBox.html(shipmentIsBeingPrintedMessage);
        messageBox.addClass('alert-warning');
        messageBox.removeClass('alert-danger');
        messageBox.removeClass('alert-success');
        $("html, body").animate({ scrollTop: 0 }, "slow");
        $('.label-printing-message').show();
    }

    function hideMessageBoxPrinted() {
        $('.label-printing-message').hide();
    }

    function toggleMessageBoxResult(success, message) {
        var messageBox = $('.message-text-box');
        messageBox.text(toPlainText(message));
        if (success) {
            messageBox.removeClass('alert-warning');
            messageBox.addClass('alert-success');
        }

        if (!success) {
            messageBox.removeClass('alert-warning');
            messageBox.addClass('alert-danger');
        }
    }

    // Server messages arrive HTML-escaped; a native textarea decodes entities without creating elements.
    // jQuery .html() is not used here because it builds elements for <script> or <td> input.
    function toPlainText(message) {
        var textarea = document.createElement('textarea');
        textarea.innerHTML = message || '';

        return textarea.value;
    }

    function replaceIcon(idOrder, iconReplacer)
    {
        $('.dpd-icon-container[data-id-order="' + idOrder + '"]').html(iconReplacer);
    }

    $('.bulk-actions .dropdown-menu').append(downloadSelectedLabelsButton);
});

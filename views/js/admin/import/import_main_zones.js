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
$(window).load(function () {
  if (typeof onBoard === 'undefined') {
    $("#import-modal-confirm-latvia").modal();
    $("#import-modal-confirm-lithuania").modal();
  }

  $('.import-zones-button').click(function () {
    $.ajax(dpdAjaxUrl, {
      method: 'POST',
      data: {
        ajax: 1,
        country: $(this).data('country'),
        action: 'importZones'
      },
      beforeSend: function () {
        // Show image container
        $(".import-modal-confirm").modal('hide');
        $("#import-modal").modal();
        loadImport();
      },
      success: function(response) {
        response = JSON.parse(response);
        if (response && response.success === false) {
          showErrorMessage(response.error || failMessage);
          return;
        }
        if (response) {
          showSuccessMessage(successMessage);
        } else {
          showErrorMessage(failMessage);
        }
      },
      complete: function () {
        // Hide image container
        $("#import-modal-confirm").modal('hide');
        $("#import-modal").modal('hide');
        stopLoadImport();

        if (typeof onBoard !== 'undefined') {
          nextOnBoardStep('DPD_MANUAL_ZONES_AFTER_IMPORT_STEP');
        }
      }
    });
  });

  function loadImport() {
    toggleInterval = setInterval(function () {
      $('#js-loading').toggle();
      $('#js-importing-zones').toggle();
    }, 2000);
  }

  function stopLoadImport() {
    clearInterval(toggleInterval);
  }

  function nextOnBoardStep(nextStep) {
    $.ajax(onBoard.ajaxUrl, {
      method: 'POST',
      data: {
        action: 'nextOnBoardStep',
        ajax: 1,
        nextStep: nextStep
      },
      success: function (response) {
        response = JSON.parse(response);

        if (!response.status) {
          return false;
        }

        if (response.stepTemplate) {
          $onBoardSection = $('.dpd-on-board-section');

          $onBoardSection.find('#dpd-on-board').html(response.stepTemplate.onBoardTemplate);

          if (typeof response.stepTemplate.progressBarTemplate !== 'undefined') {
            if ($onBoardSection.find('.dpd-on-board-bottom-progress-container').length) {
              $onBoardSection.find('.dpd-on-board-bottom-progress-container').replaceWith(response.stepTemplate.progressBarTemplate);
            } else {
              $onBoardSection.append(response.stepTemplate.progressBarTemplate);
            }
          }

          $onBoardSection.removeClass('hidden d-none');
        }
      }
    });
  }
});
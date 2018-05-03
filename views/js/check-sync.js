/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Cappasity Inc <info@cappasity.com>
 * @copyright 2014-2018 Cappasity Inc.
 * @license   http://cappasity.us/eula_modules/  Cappasity EULA for Modules
 */

$(document).ready(function () {
  var label = $('#sync-wrapper .bootstrap:first-child');

  if (label) {
    var url = $('#sync-wrapper').data('url');
    var interval = setInterval(function() {
      $.get(url, function(count) {
        if (count === '0') {
          clearInterval(interval);
          label.removeClass('module_warning alert alert-warning');
          label.addClass('module_confirmation conf confirm alert alert-success');
          label.text('Synchronization complete');
        }
      });
    }, 1000 * 5);
  }
});

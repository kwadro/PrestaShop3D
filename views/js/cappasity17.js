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

// --- requestAnimationFrame polyfill start ---
// requestAnimationFrame polyfill by Erik Möller. fixes from Paul Irish and Tino Zijdel
// MIT license
(function() {
  var lastTime = 0;
  var vendors = ['ms', 'moz', 'webkit', 'o'];
  for(var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
    window.requestAnimationFrame = window[vendors[x]+'RequestAnimationFrame'];
    window.cancelAnimationFrame = window[vendors[x]+'CancelAnimationFrame']
      || window[vendors[x]+'CancelRequestAnimationFrame'];
  }

  if (!window.requestAnimationFrame)
    window.requestAnimationFrame = function(callback, element) {
      var currTime = new Date().getTime();
      var timeToCall = Math.max(0, 16 - (currTime - lastTime));
      var id = window.setTimeout(function() { callback(currTime + timeToCall); },
        timeToCall);
      lastTime = currTime + timeToCall;
      return id;
    };

  if (!window.cancelAnimationFrame)
    window.cancelAnimationFrame = function(id) {
      clearTimeout(id);
    };
}());
// --- requestAnimationFrame polyfill end ---

// --------------------------------
$(document).ready(function() {
  var embed = $('#cappasity-embed').data('embed');
  var bigImgSel = '.js-qv-product-cover';
  var modalImgSel = '.js-modal-product-cover';
  var THUMB_TITLE = '1000000000';

  if (!embed) {
    return;
  }

  var cpstId = 'cappasity-embed-id';
  var modalCpstId = 'modal-cappasity-embed-id';

  var $bigCpst = $(embed)
    .attr({ height: '100%', id: cpstId })
    .css({
      display: 'none',
      zIndex: 1,
      position: 'absolute',
      top: 0
    });
  var $modalCpst = $('<div />', { id: modalCpstId })
    .css({
      display: 'none',
      position: 'relative',
      width: '100%',
      paddingTop: '100%'
    });

  $modalCpst.append($(embed)
    .attr({ height: '100%', id: modalCpstId })
    .css({
      zIndex: 1,
      position: 'absolute',
      top: 0
    })
  );

  $(history).on('pushstate popstate', function handleHistoryStateChange(ev) {
    console.log(ev);
  });

  $('body').on('click', '.js-thumb', function handleThumbClick(ev) {
    if (ev.target.getAttribute('title') === THUMB_TITLE) {
      if (!$('#' + cpstId).length) {
        $(bigImgSel)
          .eq(0)
          .parent()
          .prepend($bigCpst);
      }

      $bigCpst.css({ display: 'block' });
    } else {
      $bigCpst.css({ display: 'none' });
    }
  });

  $('body').on('click', '.js-modal-thumb', function handleModalThumbClick(ev) {
    if (ev.target.getAttribute('title') === THUMB_TITLE) {
      requestAnimationFrame(function () {
        if (!$('#' + modalCpstId).length) {
          $(modalImgSel)
            .eq(0)
            .parent()
            .prepend($modalCpst);
        }

        $modalCpst.css({ display: 'block' })
        $(modalImgSel).css({ display: 'none' });
      });
    } else {
      requestAnimationFrame(function () {
        $(modalImgSel).css({ display: 'block' });
        $modalCpst.css({ display: 'none' });
      });
    }
  });
});


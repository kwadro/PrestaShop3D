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
  var $cappasityThumb = $('#thumbnail_1000000000');
  var $imageBlock = $('#image-block');
  var $iframe = $($('#cappasity-embed').data('embed'));
  var $thumbContainer = $('#thumbs_list');
  var $thumbList = $('ul#thumbs_list_frame');
  var cappasityLinkId = 'cappasity-link';
  var embedId = 'cappasity-embed-id';
  var iconHref = '/modules/cappasity3d/views/img/logo-3d.jpg';
  var iconBigHref = '/modules/cappasity3d/views/img/logo-3d-thickbox.jpg';
  var $embed = $('<div />', { id: embedId })
    .append($iframe)
    .css({
      position: 'absolute',
      top: 0,
      bottom: 0,
      left: 0,
      right: 0,
      zIndex: -1
    });

  if ($thumbList.length) {
    var $cappasityLink = $cappasityThumb.find('a').eq(0);
    var $cappasityImg = $cappasityThumb.find('img').eq(0);

    $cappasityLink.attr({
      id: cappasityLinkId,
      href: iconBigHref,
      'data-fancybox-type': 'iframe',
      'data-fancybox-width': $iframe.attr('width'),
      'data-fancybox-height': $iframe.attr('height'),
      'data-fancybox-href': $iframe.attr('src')
    });
    $cappasityImg.attr({
      id: 'cappasity-thumb',
      src: iconHref
    });

    var $viewFullSize = $('#view_full_size').eq(0);
    var hasBigPic = $('#bigpic, #view_full_size .jqzoom').eq(0).length;

    function displayImage($el) {
      var isCappasity = $el && $el.attr('id') === cappasityLinkId;
      var $bigPic = $('#bigpic, #view_full_size .zoomPad').eq(0);

      if ($bigPic.length) {
        $iframe.attr('width', $bigPic.css('width'));
        $iframe.attr('height', $bigPic.css('height'));

        if (isCappasity) {
          if (!$viewFullSize.find('#' + embedId).length) {
            $viewFullSize.append($embed);
          }
          $embed.css({ zIndex: 1 });
        } else {
          $embed.css({ zIndex: -1 });
        }
      }
    }

    if (jqZoomEnabled) {
      var rel = $cappasityLink.attr('rel');

      if (rel) {
        try {
          var newRel = $.extend({}, eval("(" + $.trim(rel) + ")"), {
            smallimage: iconHref,
            largeimage: iconBigHref
          });
          $cappasityLink.attr('rel', JSON.stringify(newRel));
        } catch (e) {}
      }


      $('.jqzoom').each(function() {
        var api = $(this).data('jqzoom');

        if (!api || !api.swapimage) return;

        var oldSwapImage = api.swapimage;

        api.swapimage = function(link) {
          var args = [].slice.call(arguments);
          var $link = $(link);

          if ($link.attr('id') !== cappasityLinkId) {
            $cappasityLink.removeClass('zoomThumbActive');
          }

          displayImage($(link));
          oldSwapImage.apply(this, args);
        };
      });

      return;
    }

    var oldDisplayImage = window.displayImage;

    if(oldDisplayImage && $viewFullSize.length && hasBigPic) {
      window.displayImage = function ($el) {
        var args = [].slice.call(arguments);

        displayImage($el);
        oldDisplayImage.apply(window, args);
      };
    }

    refreshProductImages(0);
    $thumbContainer.trigger('goto', 0);
    displayImage($cappasityLink);
  } else if ($imageBlock.length) {
    $imageBlock.empty().append($embed);
  }
});

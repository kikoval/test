
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @author     Tomi Belan <tomi.belan@gmail.com>
 */

(function ($) {
  var width = window.innerWidth || $('html').width();
  if (navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/Android/i) || (width && width < 500)) {
    $('html').addClass('mobile');
    $('html').delegate('.sidebar-toggle, #sidebar a.active', 'click keypress', function () {
      $('html').toggleClass('show-sidebar');
      return false;
    });
    $('head').append("<meta name='viewport' content='width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;' />");
    $(document).ready(function () {
      if (!$(document.body).hasClass('no-sidebar')) {
        $('#sidebar-inner').prepend('<button class="sidebar-toggle">Späť na aktuálnu kategóriu</button>');
        $('#content').prepend('<button href="#" class="sidebar-toggle">Menu</button>');
      }
    });
  }
})(jQuery);


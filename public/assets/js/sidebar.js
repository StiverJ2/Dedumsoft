(function () {
  'use strict';

  var sidebar = document.getElementById('ds-sidebar');
  var toggle = document.getElementById('ds-sidebar-toggle');

  if (!sidebar) {
    return;
  }

  if (!toggle) {
    var logoWraps = sidebar.getElementsByClassName('ds-logo-wrap');
    if (logoWraps && logoWraps.length) {
      toggle = logoWraps[0];
    } else {
      var logos = sidebar.getElementsByClassName('ds-logo');
      if (logos && logos.length) {
        toggle = logos[0];
      } else {
        var diamonds = sidebar.getElementsByClassName('diamond-logo');
        if (diamonds && diamonds.length) {
          toggle = diamonds[0];
        }
      }
    }
  }

  if (!toggle) {
    return;
  }

  var hasClass = function (el, name) {
    return (' ' + el.className + ' ').indexOf(' ' + name + ' ') > -1;
  };

  var addClass = function (el, name) {
    if (!hasClass(el, name)) {
      el.className = (el.className ? el.className + ' ' : '') + name;
    }
  };

  var removeClass = function (el, name) {
    var regex = new RegExp('(^|\\s)' + name + '(\\s|$)', 'g');
    el.className = el.className.replace(regex, ' ').replace(/\\s+/g, ' ').replace(/^\\s+|\\s+$/g, '');
  };

  var setExpanded = function (expanded) {
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  };

  var openNav = function () {
    addClass(sidebar, 'ds-sidebar--open');
    setExpanded(true);
  };

  var closeNav = function () {
    removeClass(sidebar, 'ds-sidebar--open');
    setExpanded(false);
  };

  var toggleNav = function () {
    if (hasClass(sidebar, 'ds-sidebar--open')) {
      closeNav();
    } else {
      openNav();
    }
  };

  var bindClick = function (el, handler) {
    if (!el) {
      return;
    }
    if (el.addEventListener) {
      el.addEventListener('click', handler, false);
    } else if (el.attachEvent) {
      el.attachEvent('onclick', handler);
    }
  };

  bindClick(toggle, function (event) {
    if (event && event.preventDefault) {
      event.preventDefault();
    }
    toggleNav();
  });

  var links = sidebar.getElementsByTagName('a');
  var i;
  for (i = 0; i < links.length; i++) {
    bindClick(links[i], function () {
      closeNav();
    });
  }

  var onToggleKeydown = function (event) {
    var key = event && (event.key || event.keyCode);
    if (key === 'Enter' || key === 13 || key === ' ' || key === 32) {
      if (event && event.preventDefault) {
        event.preventDefault();
      }
      toggleNav();
    }
  };

  if (!toggle.getAttribute('tabindex') && toggle.tagName !== 'BUTTON' && toggle.tagName !== 'A') {
    toggle.setAttribute('tabindex', '0');
  }

  if (toggle.addEventListener) {
    toggle.addEventListener('keydown', onToggleKeydown, false);
  } else if (toggle.attachEvent) {
    toggle.attachEvent('onkeydown', onToggleKeydown);
  }
})();

// IE8 HTML5 element shim
(function () {
    var tags = ['main', 'aside', 'section', 'nav', 'header', 'footer'];
    var i = 0;
    for (i = 0; i < tags.length; i += 1) {
        document.createElement(tags[i]);
    }
}());

var $ = window.jQuery = require('jquery');
require('bootstrap-sass');
var hash = window.location.hash;
if (hash == '#thankyou' && $('form:not(:has(ul))')) {

    $('#overlay').show();
    $('.overlay').show();
    $("#overlay").click(function () {
        $('#overlay').hide();
        $('.overlay').hide();
    });
    window.location.hash = '';
}

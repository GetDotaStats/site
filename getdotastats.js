$(document).ready(function () {

    checkURL();

    $('ul li a.nav-clickable').click(function (e) {
        checkURL(this.hash);
    });

    setInterval("checkURL(window.location.hash)", 1 * 60 * 1000); //refresh every minute
});

var lasturl = "";
function checkURL(hash) {
    if (!hash) {
        hash = window.location.hash;
        loadPage('home');
    }

    if (hash != lasturl) {
        lasturl = hash;
        if (hash != "") loadPage(hash);
    }
}

function loadPage(url) {
    url = url.replace('#', '').split('__').join('/');

    //alert(url.substr(url.length - 1));

    if (url.slice(-1) != '/') {
        url = url + '.php';
    }

    $('#loading').show({complete: function () {
        $.ajax({
            type: "GET",
            url: url,
            dataType: "html",
            success: function (msg) {
                $('#loading').hide({
                    complete: function () {
                        if (parseInt(msg) != 0) {
                            $('#main_content').html(msg);
                        }
                    }
                });
            },
            error: function(jqXHR, textStatus, errorThrown){
                $('#loading').hide();
            }
        });

    }});

}
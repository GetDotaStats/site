$(document).ready(function () {

    checkURL(window.location.hash);

    //$('a').click(function (e) {
    /*$('ul li a.nav-clickable').click(function (e) {
     //event.preventDefault();
     checkURL(this.hash);
     });*/

    $(document).on("click", 'a.nav-clickable', function (e) {
        checkURL(this.hash);
    });

    //setInterval("checkURL(window.location.hash)", 15 * 60 * 1000); //refresh every 15minutes
});

var lasturl = "";
function checkURL(hash) {
    if (!hash) {
        loadPage('home');
    }
    else if (hash != lasturl) {
        //alert('new');
        //lasturl = hash;
        loadPage(hash);
    }
}

function loadPage(url) {
    url = url.replace('#', '').split('__').join('/');

    if (url.indexOf('?') > -1 && url.indexOf('/?') < 0) {
        url = url.replace('?', '.php?');
    }
    else if (url.slice(-1) != '/' && url.indexOf('?') < 0) {
        url = url + '.php';
    }

    alert(url);

    $('#loading').show({
        start: function () {
            $('#loading_spinner1').show();
            $('#loading_spinner2').hide();
        },
        complete: function () {
            $('#loading_spinner1').hide();
            $('#loading_spinner2').show();

            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function (msg) {
                    setTimeout(function () {
                        $('#loading_spinner1').show();
                        $('#loading_spinner2').hide();

                        $('#loading').hide({
                            complete: function () {
                                if (parseInt(msg) != 0) {
                                    $('#main_content').html(msg);
                                }
                            }
                        });
                    }, 500);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#loading').hide();
                }
            });

        }});

}
$(document).ready(function () {
    var mouseBoolean = false;

    loadPage(window.location.hash, 0);

    $(document).on("click", 'a.nav-clickable', function (e) {
        loadPage(this.hash, 0);
    });

    $(document).on("click", 'a.nav-refresh', function (e) {
        loadPage(this.hash, 1);
    });

    //TO FACILITATE BACK BUTTON
    $(document).on("mouseover", function () {
        mouseBoolean = true;
    });

    //TO FACILITATE BACK BUTTON
    $(document).on("mouseleave", function () {
        mouseBoolean = false;
    });

    //TO FACILITATE BACK BUTTON
    window.onhashchange = function () {
        if (!mouseBoolean) {
            if (window.location.hash != '#undefined') {
                loadPage(window.location.hash, 0);
            }
        }
    };

    var rx = /INPUT|SELECT|TEXTAREA/i;

    //NO BACKSPACING TO GO BACK
    $(document).bind("keydown keypress", function (e) {
        if ((e.which || e.keyCode) == 8) { // 8 == backspace
            if (!rx.test(e.target.tagName) || e.target.disabled || e.target.readOnly) {
                e.preventDefault();
            }
        }
        else if ((e.which || e.keyCode) == 116) { // 116 == f5 -- refreshing
            e.preventDefault();
            if (window.location.hash != '#undefined') {
                loadPage(window.location.hash, 1);
            }
        }
    });
});

var ϰ = "Kappa";
var pageReloader;

function loadPage(url, refresh) {
    if (!url) {
        url = '#d2mods__lobby_list';
    }
    else {
        //SET THE MENU
        var testElement = $('#navBarCustom');
        testElement.find('.active').removeClass('active');
        testElement.find('a[href="' + url + '"]').parents('li').addClass('active');
    }

    if (pageReloader) {
        clearTimeout(pageReloader);
    }

    var oldURL = url;
    url = url.replace('#', '').replace(':', '').split('__').join('/');

    //console.log("Loading: " + url);

    if (url.indexOf('?') > -1 && url.indexOf('/?') < 0) {
        url = url.replace('?', '.php?');
    }
    else if (url.slice(-1) != '/' && url.indexOf('?') < 0) {
        url = url + '.php';
    }

    $('#loading').show({
        start: function () {
            $('#loading_spinner1').show();
        },
        complete: function () {
            $.ajax({
                type: "GET",
                url: url,
                dataType: "html",
                success: function (msg) {
                    setTimeout(function () {
                        $('#loading').hide({
                            complete: function () {
                                if (refresh == 1 && window.location.hash != oldURL) {
                                    window.history.pushState("", "", oldURL);
                                }
                                document.getElementById("nav-refresh-holder").setAttribute("href", oldURL);
                                if (parseInt(msg) != 0) {
                                    $('#main_content').html(msg);
                                }
                                if (refresh != 1) {
                                    $('html, body').animate({ scrollTop: 0 }, 'fast');
                                }
                            }
                        });
                    }, 500);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $('#loading').hide({
                        complete: function () {
                            $('#main_content').html('Failed to load page. Try again later.');
                            $('html, body').animate({ scrollTop: 0 }, 'fast');
                        }
                    });
                }
            });

        }});

}

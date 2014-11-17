$(document).ready(function () {
    checkURL(window.location.hash, 1);

    $(document).on("click", 'a.nav-clickable', function (e) {
        checkURL(this.hash, 0);
    });

    $(document).on("click", 'a.nav-refresh', function (e) {
        checkURL(this.hash, 1);
    });

    $(document).on("click", 'a.nav-back', function (e) {
        checkURL(this.hash, 2);
    });
});

function checkURL(hash, refresh) {
    if (!hash) {
        loadPage('#home', refresh);
    }
    else {
        var testElement = $('#navBarCustom');
        //if($(this).parent().closest('div').attr("id") == 'navBarCustom'){ //CHECK IF THE PARENT DIV IS THE NAVBAR
        testElement.find('.active').removeClass('active');
        testElement.find('a[href="' + hash + '"]').parents('li').addClass('active');
        //}
        loadPage(hash, refresh);
    }
}

function loadPage(url, refresh) {
    var oldURL = url;
    url = url.replace('#', '').replace('.', '').replace(':', '').split('__').join('/');

    console.log("Loading: " + url);

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
                    if (refresh == 2) {
                        document.getElementById("nav-back-holder").removeAttribute("href");
                        document.getElementById("nav-back-holder").setAttribute("class", "");
                    }
                    else {
                        var backURL = document.getElementById("nav-refresh-holder").getAttribute("href");
                        document.getElementById("nav-back-holder").setAttribute("href", backURL);
                        document.getElementById("nav-back-holder").setAttribute("class", "nav-back");
                    }
                    document.getElementById("nav-refresh-holder").setAttribute("href", oldURL);

                    setTimeout(function () {
                        $('#loading').hide({
                            complete: function () {
                                if (parseInt(msg) != 0) {
                                    $('#main_content').html(msg);
                                }
                                if (refresh == 0) {
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

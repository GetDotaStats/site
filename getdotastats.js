$(document).ready(function () {

    checkURL(window.location.hash);

    $(document).on("click", 'a.nav-clickable', function (e) {
        checkURL(this.hash);
    });

    $(document).on("click", 'a.nav-refresh', function (e) {
        checkURL2(this.hash);
    });

    //$('html, body').animate({ scrollTop: 0 }, 'fast');

    //setInterval("checkURL(window.location.hash)", 15 * 60 * 1000); //refresh every 15minutes
});

function checkURL(hash) {
    if (!hash) {
        loadPage('#home');
    }
    else {
        var testElement = $('#navBarCustom');
        //if($(this).parent().closest('div').attr("id") == 'navBarCustom'){ //CHECK IF THE PARENT DIV IS THE NAVBAR
        testElement.find('.active').removeClass('active');
        testElement.find('a[href="' + hash + '"]').parents('li').addClass('active');
        //}
        loadPage(hash);
    }
}

function checkURL2(hash) {
    if (!hash) {
        loadPage2('#home');
    }
    else {
        loadPage2(hash);
    }
}

function loadPage(url) {
    var oldURL = url;
    url = url.replace('#', '').replace('.', '').replace('/', '').replace(':', '').split('__').join('/');

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
                    document.getElementById("abcd").setAttribute("href", oldURL);
                    setTimeout(function () {
                        $('#loading').hide({
                            complete: function () {
                                if (parseInt(msg) != 0) {
                                    $('#main_content').html(msg);
                                }
                                $('html, body').animate({ scrollTop: 0 }, 'fast');
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

function loadPage2(url) {
    var oldURL = url;
    url = url.replace('#', '').replace('.', '').replace('/', '').replace(':', '').split('__').join('/');

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
                    document.getElementById("abcd").setAttribute("href", oldURL);
                    setTimeout(function () {
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
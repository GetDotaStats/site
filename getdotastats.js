$(document).ready(function () {

    checkURL(window.location.hash);

    $(document).on("click", 'a.nav-clickable', function (e) {
        checkURL(this.hash);
    });

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

function loadPage(url) {
    var oldURL = url;
    url = url.replace('#', '').split('__').join('/');

    if (url.indexOf('?') > -1 && url.indexOf('/?') < 0) {
        url = url.replace('?', '.php?');
    }
    else if (url.slice(-1) != '/' && url.indexOf('?') < 0) {
        url = url + '.php';
    }

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
                    document.getElementById("abcd").setAttribute("href", oldURL);
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
                    $('#loading').hide({
                        complete: function () {
                            $('#main_content').html('Failed to load page. Try again later.');
                        }
                    });
                }
            });

        }});

}
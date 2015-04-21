<?php
require_once('../global_functions.php');
//require_once('../connections/parameters.php');
?>
<h1>Руководство по использованию «Lobby Explorer»</h1>

<div class="row">
    <div class="col-sm-12 text-right">
        <strong><em>Другие Языки:</em></strong>
        <a class="nav-clickable" href="#d2mods__lobby_guide"><img alt="EN" width="16" height="16"
                                                                  src="<?= $CDN_image . '/images/misc/flags/regions/US.png' ?>"></a>
        <a class="nav-clickable" href="#d2mods__lobby_guide_ru"><img alt="RU" width="16" height="16"
                                                                     src="<?= $CDN_image . '/images/misc/flags/regions/RU.png' ?>"></a>
    </div>
</div>

<span class="h4">&nbsp;</span>

<div class="alert alert-info" role="alert">
    <p><strong>Кредиты</strong></p>

    <p>Это не было бы возможно без работы
        <a target="_blank" href="https://github.com/SinZ163">«SinZ»</a>,
        <a target="_blank" href="https://github.com/bmddota">«BMD»</a>,
        <a target="_blank" href="https://github.com/ash47">«Ash47»</a>, и
        <a target="_blank" href="https://github.com/jimmydorry">«jimmydorry»</a>.</p>

    <p>Первая половина этого руководства в значительной степени осуществляться из <a target="_blank"
                                                                                      href="http://steamcommunity.com/sharedfiles/filedetails/?id=298142924">«Wyk»</a>,
        так что спасибо ему тоже.</p>

    <p>Эти инструкции было переведено <a target="_blank" href="http://steamcommunity.com/id/toyoka/">«Toyoka»</a>.</p>
</div>

<div class="alert alert-warning alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <strong>внимание:</strong> если вы хотите только мини-игры в лобби, вам не нужно устанавливать «Dota 2 Workshop
    Tools Alpha». Вы можете
    пропустить до "Установите «GetDotaStats Lobby Explorer»". Ниже этого, вам нужно всего лишь следовать одно из двух
    Быстрый стартоф.
</div>

<h2>Быстрый старт (автоматически)</h2>

<p>Это руководство для тех у кого есть «Dota 2 Workshop Tools Alpha» и хотите чтобы сразу играть пользовательские игр.
    Если это Быстрый старт удается, прочитайте полную инструкцию внизу.</p>

<ol>
    <li>Убедитесь что «Dota 2 Workshop Tools Alpha» установлена и что «Dota 2 Workshop Tools DLC» включен для ваш Dota 2
        в библиотеки.
    </li>
    <li>Войти на этот сайтб здесь (с зеленой кнопки в верхней части сайта)</li>
    <li>Скачать <a target="_blank" class="btn btn-success btn-sm"
                   href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/LXUpdater.zip">LXUpdater</a></li>
    <li>Открывать и использовать «LXUpdater» <a target="_blank" href="http://gfycat.com/BewitchedCompassionateGull">пример
            видео</a></li>
    <li>Запустите/перезапустите Dota 2.</li>
    <li>Тогда новые кнопки для поиска и принятия лобби появится когда вы нажмите кнопку для воспроизведения.
        <ul>
            <li><a target="_blank" href="http://gfycat.com/GrippingGenuineIndochinesetiger">Найти лобби</a></li>
            <li><a target="_blank" href="http://gfycat.com/PlasticEnlightenedAngwantibo">Создание лобби</a></li>
        </ul>
    </li>
</ol>

<hr/>

<h2>Быстрый старт (вручную)</h2>

<p>Это руководство для тех у кого есть «Dota 2 Workshop Tools Alpha» и хотите чтобы сразу играть пользовательские игр.
    Если это Быстрый старт удается, прочитайте полную инструкцию внизу.</p>

<ol>
    <li>Убедитесь что «Dota 2 Workshop Tools Alpha» установлена и что «Dota 2 Workshop Tools DLC» включен для ваш Dota 2
        в библиотеки.
    </li>
    <li>Войти на этот сайт, здесь (с зеленой кнопки в верхней части сайта)</li>
    <li>Скачать <a target="_blank" class="btn btn-success btn-sm"
                   href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/play_weekend_tourney.zip">«Lobby
            Explorer Pack»</a></li>
    <li>Поставьте .SWF в здесь: <code>C:\Program Files (x86)\Steam\SteamApps\common\dota 2
            beta\dota\resource\flash3</code></li>
    <li>Запустите/перезапустите Dota 2.</li>
    <li>Тогда новые кнопки для поиска и принятия лобби появится когда вы нажмите кнопку для воспроизведения.</li>
</ol>

<hr/>

<h2>Полные инструкции</h2>

<p>Эти инструкции более подробно и с картинками, но такие же как инструкциями в раньше Быстрый старт.</p>

<h3>Системные Требования</h3>

<p>В этот момент, «Dota 2 Workshop Tools Alpha» минимально требуется следующую конфигурацию:</p>

<ul>
    <li>Windows (7 или более поздней версии)</li>
    <li>Direct3D 11 / Direct3D 9 GPU</li>
    <li>4 гигабайт оперативной памяти</li>
    <li>Интернет (конечно тем быстрее тем лучше)</li>
</ul>

<h3>Устанавливать «Dota 2 Workshop Tools»</h3>

<p>Для того чтобы играть в пользовательские игры в лобби, у Вас должен быть установлен «Dota 2 Workshop Tools». Чтобы
    установить, выполните следующие действия.</p>

<p><strong>1.</strong> В Стим с открытым Dota 2 в недвижимость, выберите последний вариант (DLC) и включить «Dota 2
    Workshop Tools DLC». Это начнет загрузку 6,6 Гб</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/1-1.png" width="500px"/></div>

<p><strong>2.</strong> В Стим, перейдите к инструментам в меню библиотеки.</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/1-2a.png" width="500px"/></div>

<p><strong>3.</strong> Найдите и установите «Dota 2 Workshop Tools Alpha»</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/1-2b.png" width="500px"/></div>

<p><strong>4.</strong> Подождите как закончить загрузку.</p>

<h3>Подписка на Пользовательские Игры</h3>

<p><strong>1.</strong> вы можете найти все игры на <a target="_blank"
                                                      href="http://steamcommunity.com/workshop/browse/?appid=570&section=readytouseitems">«Steam
        Workshop»</a>. Перейти к Мастерская и нажмите на "Просмотр" и перейдите в "<a target="_blank"
                                                                                      href="http://steamcommunity.com/workshop/browse/?appid=570&section=readytouseitems">Пользовательские
        игры</a>".</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/2-1.png" width="500px"/></div>

<p><strong>2.</strong> Нажмите кнопку "Подписаться" чтобы начать автоматическую загрузку. Вы можете скачать любую игру
    Пользовательские, но только игры Пользовательские мы утвердили могут быть воспроизведены в лобби программы вы
    скачали раньше. Это в основном из-за технических ограничений в лобби, но также из-за Пользовательские игр которые
    были созданы с небольшим усилием. Если вы хотите игру Пользовательские здесь, пожалуйста приводить разработчик
    здесь. Мы рады принять новый Пользовательские игры на сайте!</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/2-2.png" width="500px"/></div>

<h3>Установите «GetDotaStats Lobby Explorer»</h3>

<p><strong>1.</strong> <a target="_blank" class="btn btn-success btn-sm"
                          href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/play_weekend_tourney.zip">загрузить
        «Lobby Explorer Pack»</a></p>

<p><strong>2.</strong> Сейчас хорошее время для входа на наш сайт с Стимх OpenID. Нажмите на зеленую кнопку для входа в
    верхней части сайта. Вход в будет гарантировать, что вы получите полную функциональность с нашем лобби. Не
    подписания помешает вам использовать определенные функции.</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/3-2.png" width="500px"/></div>

<p><strong>3.</strong> Перемещение « play_weekend_tourney.swf » и другие файлы, полученные от скачивания, по следующему
    адресу:
    <code>C:\Program Files (x86)\Steam\SteamApps\common\dota 2 beta\dota\resource\flash3</code>
</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/3-3.png" width="500px"/></div>

<p>&nbsp;</p>

<div class="alert alert-danger">
    <p>Убедитесь что все файлы называются правильно, такие как «play_weekend_tourney.swf» и «minigames.kv». Если
        расположение приведенные выше не существует, создайте его!</p>
</div>

<hr/>

<h2>Найти / Регистрация Лобби</h2>

<p>После того, как Вы выполнили предыдущие инструкции, теперь вы можете использовать нашу систему! Список лобби можно
    обновить только вручную, на кликом символ обновления.</p>

<p><strong>1.</strong> Начать Dota 2</p>

<p><strong>*</strong> Перейти к "Play" и нажмите на «CUSTOM LOBBY BROWSER», чтобы найти лобби. (<a target="_blank"
                                                                                                   href="http://gfycat.com/GrippingGenuineIndochinesetiger">пример
        видео</a>)</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/4-1.jpg" width="500px"/></div>

<p><strong>*</strong> Перейти к "Play" и нажмите на «HOST CUSTOM LOBBY», чтобы провести лобби. (<a target="_blank"
                                                                                                   href="http://gfycat.com/PlasticEnlightenedAngwantibo">пример
        видео</a>)</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/4-2.jpg" width="500px"/></div>

<hr/>

<h2>Заметки</h2>

<p>Ошибки могут произойти. Вы можете связаться с нами через любой из перечисленных ниже местах.</p>

<p>Если вы не знакомы с IRC, попробуйте <a target="_blank"
                                           href="https://kiwiirc.com/client/irc.gamesurge.net/?#getdotastats">«kiwiirc»</a>.
</p>

<p>&nbsp;</p>

<div class="text-center">
    <a target="_blank" class="btn btn-danger btn-sm" href="irc://irc.gamesurge.net:6667/#getdotastats">IRC
        #getdotastats</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://chatwing.com/GetDotaStats">Site
        Chatbox</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="https://github.com/GetDotaStats/GetDotaLobby/issues">Github
        Issues</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://steamcommunity.com/id/jimmydorry/">Steam</a>
    <a target="_blank" class="btn btn-danger btn-sm"
       href="http://steamcommunity.com/groups/getdotastats/discussions/3/">Steam Group</a>
</div>

<p>&nbsp;</p>

<p>Я не принимаю случайные запросы, оставьте комментарий на профиль перед добавлением мне.</p>
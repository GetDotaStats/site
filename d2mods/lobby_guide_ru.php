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
    <p><strong>Благодарность</strong></p>

    <p>Всё это было бы невозможно без работы
        <a target="_blank" href="https://github.com/SinZ163">«SinZ»</a>,
        <a target="_blank" href="https://github.com/bmddota">«BMD»</a>,
        <a target="_blank" href="https://github.com/ash47">«Ash47»</a>, и
        <a target="_blank" href="https://github.com/jimmydorry">«jimmydorry»</a>.</p>

    <p>Отдельная благодарность <a target="_blank"
                                  href="http://steamcommunity.com/sharedfiles/filedetails/?id=298142924">«Wyk»</a>,
        за первую часть руководства.</p>

    <p>Туториал перевел
        <a target="_blank" href="https://steamcommunity.com/profiles/76561198009765825">«Toyoka»</a> и
        <a target="_blank" href="https://steamcommunity.com/profiles/76561198137607735">«Apacherus»</a>.</p>
</div>

<div class="alert alert-warning alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <strong>Замечание:</strong> если вы хотите установить только мини-игры, устанавливать «Dota 2 Workshop
    Tools Alpha» не требуется. Сразу перейдите к разделу "Установите «Lobby Explorer»".
</div>

<h2>Быстрый старт (автоматически)</h2>

<p>Данное руководство применимо к тем пользователям которые уже установили «Dota 2 Workshop Tools Alpha». В том случае
    если Вы
    не установили «Dota 2 Workshop Tools Alpha» или выполнить этот гайд не получилось, перейдите к полной инструкции. Вы
    можете
    проверить исходный код используемых скриптов (VBS) нажав правой кнопкой мыши и выбрав пункт "Изменить", или можете
    внести все изменения вручную - они описываются в следующем разделе.</p>

<ol>
    <li>Убедитесь что дополнение Workshop Tools установлено (и Workshop Tools DLC включено в настройка клиента Dota 2 в
        библиотеке Steam)
    </li>
    <li>Войдите на этом сайте (используя зеленую кнопку сверху)</li>
    <li>Загрузите <a target="_blank" class="btn btn-success btn-sm"
                     href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/LXUpdater.zip">«LXUpdater»</a></li>
    <li>Извлеките и запустите «LXUpdater» <a target="_blank"
                                             href="http://gfycat.com/BewitchedCompassionateGull">ВИДЕО</a></li>
    <li>Перезапустите клиент Dota 2</li>
    <li>Теперь в Dota 2 на вкладке "Игра" доступны новые кнопки для поиска и создания лобби пользовательских игр
        <ul>
            <li><a target="_blank" href="http://gfycat.com/GrippingGenuineIndochinesetiger">Поиск лобби</a></li>
            <li><a target="_blank" href="http://gfycat.com/PlasticEnlightenedAngwantibo">Создание лобби</a></li>
        </ul>
    </li>
</ol>

<hr/>

<h2>Быстрый старт (вручную)</h2>

<p>Данное руководство применимо к тем пользователям которые уже установили «Dota 2 Workshop Tools Alpha». В том случае
    если Вы
    не установили «Dota 2 Workshop Tools Alpha» или выполнить этот гайд не получилось, перейдите к полной
    инструкции.</p>

<ol>
    <li>Убедитесь что дополнение Workshop Tools установлено (и Workshop Tools DLC включено в настройка клиента Dota 2 в
        библиотеке Steam).
    </li>
    <li>Войдите на этом сайте (используя зеленую кнопку сверху).</li>
    <li>Загрузите <a target="_blank" class="btn btn-success btn-sm"
                     href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/play_weekend_tourney.zip">«Lobby
            Explorer Pack»</a></li>
    <li>Извлеките «Lobby Explorer» в папку flash3 расположенную в папке установки Dota 2 <code>C:\Program Files
            (x86)\Steam\SteamApps\common\dota 2
            beta\dota\resource\flash3</code></li>
    <li>Перезапустите Dota 2</li>
    <li>Теперь в Dota 2 на вкладке "Игра" доступны новые кнопки для поиска и создания лобби пользовательских игр</li>
</ol>

<hr/>

<h2>Полное руководство</h2>

<p>Детальный гайд по установке «Lobby Explorer». Результат соответствует предыдущим гайдам, различия лишь в более
    подробной инструкции.</p>

<h3>Системные требования</h3>

<p>Текущий выпуск инструментов совместим лишь с указанной ниже конфигурацией.</p>

<ul>
    <li>Windows (версии 7 или старше)</li>
    <li>Видеокарты совместимые с Direct3D 11 / Direct3D 9</li>
    <li>Не меньше 4Gb оперативной памяти</li>
    <li>Стабильное широкополосное интернет соединение</li>
</ul>

<h3>Установка «Dota 2 Workshop Tools»</h3>

<p>Для того чтобы играть в пользовательские игры должно быть установлено дополнение Dota 2 Workshop Tools. Инструкция по
    установке приведена ниже.</p>

<p><strong>1.</strong> Откройте Библиотеку, нажмите правой кнопкой мыши на Dota 2 и выберите пункт Свойства, перейдите
    на вкладку Дополнения и отметьте «Dota 2
    Workshop Tools DLC». После этого будет загружено примерно 6.6Гб.</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/1-1.png" width="500px"/></div>

<p><strong>2.</strong> Перейдите на вкладку инструменты из меню Библиотека.</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/1-2a.png" width="500px"/></div>

<p><strong>3.</strong> Найдите в списке «Dota 2 Workshop Tools Alpha» и установите.</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/1-2b.png" width="500px"/></div>

<p><strong>4.</strong> Дождитесь пока инструменты и дополнение будет загружено и установлено.</p>

<h3>Подписка на пользовательские игры</h3>

<!--- -->

<p><strong>1.</strong> Список загруженных пользовательских игр можно найти в
    <a target="_blank" href="http://steamcommunity.com/workshop/browse/?appid=570&section=readytouseitems">«Steam
        Workshop»</a>.
    Просто откройте мастерскую и найдите
    <a target="_blank" href="http://steamcommunity.com/workshop/browse/?appid=570&section=readytouseitems">«Custom
        Games»</a>.
</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/2-1.png" width="500px"/></div>

<p><strong>2.</strong> Подпишитесь на Custom Games чтобы автоматически загрузить пользовательские игры через Steam. Вы
    можете устанавливать и другие пользовательские игры, но через «Lobby Explorer» можно играть лишь в те игры, которые
    есть в разделе Custom Games. Это связано с техническими ограничениями работы системы, однако это значительно
    упрощает поиск и создание лобби. Если Вы хотите видеть какую-то карту здесь, просто скажите разработчику об этом. Мы
    всегда рады новым играм!</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/2-2.png" width="500px"/></div>

<h3>Установка «Lobby Explorer»</h3>

<p><strong>1.</strong> <a target="_blank" class="btn btn-success btn-sm"
                          href="https://github.com/GetDotaStats/GetDotaLobby/raw/master/play_weekend_tourney.zip">Установка
        «Lobby Explorer Pack»</a>
</p>

<p><strong>2.</strong> Войдите на сайт GetDotaStats.com используя OpenID Steam. Нажмите на большую кнопку "Sign in
    through STEAM" в верхней части сайта. Авторизация позволит Вам использовать все возможности «Lobby Explorer».</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/3-2.png" width="500px"/></div>

<p><strong>3.</strong> 3. Добавьте «play_weekend_tourney.swf» и другие файлы из архива который Вы скачали в папку
    flash3 расположенную в Вашей папке Dota 2. Обычно она находится здесь:
    <code>C:\Program Files (x86)\Steam\SteamApps\common\dota 2 beta\dota\resource\flash3</code>
</p>

<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/3-3.png" width="500px"/></div>

<p>&nbsp;</p>

<div class="alert alert-danger">
    <p>Убедитесь что все загруженные файлы называются корректно. Т.е. «play_weekend_tourney.swf» и «minigames.kv». Если
        данной папки не существует - создайте её!</p>
</div>

<hr/>

<h2>Поиск/создание лобби</h2>

<p>Теперь когда Вы установили «Dota 2 Workshop Tools Alpha», «Lobby Explorer» и вошли на сайт - можно использовать нашу
    систему лобби. Обратите внимание что список лобби в игре нужно обновлять вручную, для этого нажмите на кнопку
    Refresh в верхнем правом углу. Обновлять можно 1 раз в 5 секунд.</p>

<p><strong>1.</strong> Запустите клиент Dota 2</p>

<p><strong>*</strong> Перейдите на вкладку ИГРА и нажмите «CUSTOM LOBBY BROWSER» чтобы найти лобби. (<a target="_blank"
                                                                                                        href="http://gfycat.com/GrippingGenuineIndochinesetiger">ВИДЕО</a>)
</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/4-1.jpg" width="500px"/></div>

<p><strong>*</strong> Перейдите на вкладку ИГРА и нажмите «HOST CUSTOM LOBBY» что создать лобби. (<a target="_blank"
                                                                                                     href="http://gfycat.com/PlasticEnlightenedAngwantibo">ВИДЕО</a>)
</p>
<div class="text-center"><img src="//dota2.photography/images/lobbies/guide/4-2.jpg" width="500px"/></div>

<hr/>

<h2>Замечания</h2>

<p>Могут присутствовать ошибки. Вы можете связаться с нами любым способом указанным ниже.</p>

<p>Если Вы не использовали IRC ранее, попробуйте <a target="_blank"
                                           href="https://kiwiirc.com/client/irc.gamesurge.net/?#getdotastats">«kiwiirc»</a>.
</p>

<p>&nbsp;</p>

<div class="text-center">
    <a target="_blank" class="btn btn-danger btn-sm" href="irc://irc.gamesurge.net:6667/#getdotastats">IRC
        #getdotastats</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="https://github.com/GetDotaStats/GetDotaLobby/issues">Github
        Issues</a>
    <a target="_blank" class="btn btn-danger btn-sm" href="http://steamcommunity.com/id/jimmydorry/">Steam</a>
    <a target="_blank" class="btn btn-danger btn-sm"
       href="http://steamcommunity.com/groups/getdotastats/discussions/3/">Steam Group</a>
</div>

<p>&nbsp;</p>

<p>Я не добавляю случайные запросы в «Steam», укажите причину в комментариях перед тем как добавить меня в друзья.</p>
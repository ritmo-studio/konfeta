<?php
/**
 * Закрытие документа: скрипты и конец разметки.
 *
 * Видимого подвала здесь нет — копирайт живёт на финальном экране
 * (blocks/screen-reveal). Если появится страница без финального
 * экрана, подвал ей нужно будет подключить отдельным блоком.
 *
 * $bodyExtra — дополнительные скрипты конкретной страницы.
 *
 * Счётчики стоят здесь, а не в голове: тег метрики тащит за собой
 * <noscript> с картинкой, а <div> внутри <head> невалиден — браузер
 * закрывает голову на этом месте и всё следующее уезжает в body.
 */
$metrikaId = $metrikaId ?? null;  // счётчика у сайта пока нет
$isProd    = $isProd    ?? false;   // вычислен в head.php
?>
<?php /* Lenis идёт перед main.js: base.js ищет глобал при старте.
         Вендорится файлом, пакетного менеджера в проекте нет.
         Версия и лицензия — рядом, в assets/js/vendor.        */ ?>
<script src="/assets/js/vendor/lenis.min.js?v=1.3.27" defer></script>
<script src="/assets/js/main.js?v=<?= $assetVer ?? time() ?>" defer></script>
<?= $bodyExtra ?? '' ?>
<?php if ($isProd && $metrikaId): /* Небоевой хост не пишет в статистику:
    иначе Вебвизор пишет разработку, а отказы и время на сайте врут. */ ?>
<script>
(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
m[i].l=1*new Date();
for (var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}
k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
(window,document,'script','https://mc.yandex.ru/metrika/tag.js','ym');
ym(<?= (int)$metrikaId ?>, 'init', {webvisor:true, clickmap:true, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?= (int)$metrikaId ?>" style="position:absolute;left:-9999px" alt=""></div></noscript>
<?php endif; ?>
</body>
</html>

<?php
/**
 * Кнопка. Пока без действия: формы на сайте вырезаны вместе с
 * модалками WordPress, приёмник POST в движке только предстоит. До
 * него это button без обработчика, а не ссылка на несуществующий
 * адрес — мёртвый href потом пришлось бы выковыривать.
 *
 * $buttonLabel — подпись, $buttonWide — растянуть на всю ширину.
 */
$buttonLabel = $buttonLabel ?? '';
$buttonWide = $buttonWide ?? false;
$be = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
if ($buttonLabel !== ''):
?>
<button class="kf-button<?= $buttonWide ? ' kf-button--wide' : '' ?>" type="button" data-cta><?= $be($buttonLabel) ?></button>
<?php endif; ?>

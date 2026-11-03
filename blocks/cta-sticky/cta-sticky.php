<?php
/**
 * Прилипшая кнопка заказа.
 *
 * На широком экране висит в правом нижнем углу всегда, на узком —
 * круглая и появляется, когда секция из show_after прошла нижнюю
 * кромку экрана. Так на утверждённом сайте.
 *
 * Кнопка — atoms/button, то есть та же, что в мобильном меню и на
 * первом экране: приёмника форм пока нет, поэтому это button без
 * обработчика, а не ссылка в никуда.
 *
 * Порог показа приходит из контента и ложится в data-атрибут на
 * корне: блок не должен знать, какие секции стоят на странице.
 */
$data = $cta ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/cta.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$label = (string)($data['label'] ?? '');
$after = (string)($data['show_after'] ?? '');
if ($label !== ''):
?>
<div class="kf-cta-sticky"<?php if ($after !== ''): ?> data-show-after="<?= $te($after) ?>"<?php endif; ?>>
	<?php $buttonLabel = $label;
	include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/button/button.php';
	$buttonLabel = ''; ?>
</div>
<?php endif; ?>

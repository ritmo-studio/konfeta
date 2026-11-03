<?php
/**
 * Фильтр группами: на широком экране — строка кнопок, на узком —
 * один список. Так на утверждённом сайте, и так честнее: восемь
 * групп в строку на 375 не помещаются.
 *
 * Данные приходят в $filterItems: [['label' => 'Пакеты', 'count' => 3], …].
 * Подпись с числом собирается здесь, а не лежит в контенте: на сайте
 * число было вписано руками и разошлось бы с составом при первой правке.
 *
 * Атом без id — блок с ним может повторяться на странице. Поэтому и
 * паттерн не «табы» (он требует aria-controls, то есть id), а группа
 * кнопок-переключателей с aria-pressed: то же поведение, та же
 * доступность, без идентификаторов.
 *
 * Список скрыт до инициализации JS и показывается классом
 * kf-filter-ready, который ставит filter.js. Свой флаг, а не rs-js:
 * тот не ставится при prefers-reduced-motion, и фильтр остался бы
 * мёртвым у человека, который всего лишь отключил анимацию.
 */
$filterItems = $filterItems ?? [];
$filterLabel = $filterLabel ?? 'Группа';
$fe = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
if ($filterItems):
?>
<div class="kf-filter">
	<div class="kf-filter__tabs" role="group" aria-label="<?= $fe($filterLabel) ?>">
		<?php foreach ($filterItems as $i => $it): ?>
		<button class="kf-filter__tab<?= $i === 0 ? ' is-active' : '' ?>" type="button"
		        data-group="<?= (int)$i ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"><?= $fe($it['label']) ?><?php
		        if (!empty($it['count'])): ?> (<?= (int)$it['count'] ?>)<?php endif; ?></button>
		<?php endforeach; ?>
	</div>
	<label class="kf-filter__compact">
		<span class="kf-filter__label"><?= $fe($filterLabel) ?></span>
		<select class="kf-filter__select" data-filter-select>
			<?php foreach ($filterItems as $i => $it): ?>
			<option value="<?= (int)$i ?>"><?= $fe($it['label']) ?><?php
			        if (!empty($it['count'])): ?> (<?= (int)$it['count'] ?>)<?php endif; ?></option>
			<?php endforeach; ?>
		</select>
	</label>
</div>
<?php endif; ?>

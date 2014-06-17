<?php
$body_id = 'mooc-progress-index';

$progress = function ($block, $format = "") {
    return ceil($block['progress'] * 100) . $format;
};
?>



<h1><?= $courseware['title'] ?></h1>

<table class=chapters>
  <? foreach ($courseware['children'] as $chapter) : ?>
    <tr class=chapter>
      <th colspan=2>
        <?= htmlReady($chapter['title']) ?>
        <? if (sizeof($chapter['children'])) : ?>
          <span class=progress><?= $progress($chapter, "%") ?></span>
        <? endif ?>
      </th>
    </tr>

    <? foreach ($chapter['children'] as $subchapter) : ?>
      <tr class=subchapter>
        <th>
          <?= htmlReady($subchapter['title']) ?>
          <? if (sizeof($subchapter['children'])) : ?>
            <span class=progress><?= $progress($subchapter, "%") ?></span>
          <? endif ?>
        </th>
        <td>
          <ol class=sections>
            <? foreach ($subchapter['children'] as $section) : ?>
              <li>
                <a href="<?= $controller->url_for('courseware', array('selected' => $section['id'])) ?>"
                   title="<?= htmlReady($section['title']) ?>"
                   data-progress="<?= $progress($section) ?>">
                  <progress value=<?= $progress($section) ?> max=100><span><?= $progress($section) ?></span>%</progress>
                </a>
              </li>
            <? endforeach ?>
          </ol>
        </td>
      </tr>
    <? endforeach ?>

  <? endforeach ?>
</table>

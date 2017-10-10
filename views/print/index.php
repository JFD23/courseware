<? foreach ($courseware['children'] as $chapter) : ?>
<h1><?= $chapter['title'] ?></h1>
    <ul>
    <? foreach ($chapter['children'] as $subchapter) : ?>
        <li><h2><?= $subchapter['title'] ?></h2></li>
        <ul>
        <? foreach ($subchapter['children'] as $section) : ?>
            <li><h3><?= $section['title'] ?></h3></li>
            <ul>
            <? foreach ($section['children'] as $block) : ?>
                <? if ($block['type'] == 'HtmlBlock'): ?>
                    <li>
                        <?= $block['content']?>
                        
                    </li>
                <? endif?>
            <? endforeach?>
        </ul>
        <? endforeach?>
        </ul>
    <? endforeach?>
    </ul>
<? endforeach?>

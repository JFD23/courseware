<?
use Studip\Button;
use Mooc\UI\Courseware\Courseware;

if ($flash['success']) {
    PageLayout::postMessage(MessageBox::success($flash['success']));
}

?>

<form method="post" action="<?= $controller->url_for('courseware/settings') ?>">
    <?= CSRFProtection::tokenTag() ?>

    <table id="main_content" class="default">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>

        <caption>
            <?= _cw('Courseware-Einstellungen') ?>
        </caption>

        <tbody>
            <tr>
                <th colspan="2"><?= _cw('Allgemeines') ?></th>
            </tr>
            <tr>
                <td>
                    <label for="courseware-title">
                        <?= _cw('Titel der Courseware') ?><br>
                        <dfn id="courseware-title-description">
                            <?= _cw('Der Titel der Courseware erscheint als Beschriftung des Courseware-Reiters. Sie können den Reiter also z.B. auch "Online-Skript", "Lernmodul" o.ä. nennen.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="courseware-title" type="text" name="courseware[title]" value="<?= htmlReady($courseware_block->title) ?>" aria-describedby="courseware-progression-description">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="courseware-progression">
                        <?= _cw('Art der Kapitelabfolge') ?><br>
                        <dfn id="courseware-progression-description">
                            <?= _cw('Bei freier Kapitelabfolge können alle sichtbaren Kapitel in beliebiger Reihenfolge ausgewählt werden. Bei sequentieller Abfolge müssen alle vorangehenden Unterkapitel erfolgreich abgeschlossen sein, damit ein Unterkapitel ausgewählt und angezeigt werden kann.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <? $progression_type = $courseware_block->progression; ?>
                    <select name="courseware[progression]" id="courseware-progression" aria-describedby="courseware-progression-description">
                        <option value="free"<?= $courseware_block->progression === Courseware::PROGRESSION_FREE ? ' selected' : '' ?>> <?= _cw("frei") ?> </option>
                        <option value="seq"<?= $courseware_block->progression === Courseware::PROGRESSION_SEQ  ? ' selected' : '' ?>>  <?= _cw("sequentiell") ?> </option>
                    </select>
                </td>
            </tr>

            <?= $this->render_partial('courseware/_settings_editing_permission') ?>
            
            <tr>
                <td>
                    <label for="courseware-vipstab-visible">
                        <?= _cw('Vips-Reiter für AutorInnen entfernen') ?><br>
                        <dfn id="courseware-vipstab-visible-description">
                            <?= _cw('Diese Einstellung wird ab der Courseware-Version 4.4 und der Vips-Version 1.5 von Vips übernommen'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <a href="<?= \PluginEngine::getURL('vipsplugin', array(), 'sheets'); ?>" class="button"><?= _cw('Einstellungen in Vips vornehmen'); ?></a>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="courseware-section-navigation">
                        <?= _cw('Dritte Navigationsebene anzeigen') ?><br>
                        <dfn id="courseware-section-navigation-description">
                            <?= _cw('Wählen Sie hier aus wie die dritte Navigationsebene dargestellt werden soll.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <select name="courseware[section_navigation]" id="courseware-section-navigation">
                        <option value="default"  <?= (!$courseware_block->getSectionsAsChapters() && $courseware_block->getShowSectionNav()) ? "selected" : "" ?> ><?= _cw("Über dem Seiteninhalt horizontal anzeigen") ?></option>
                        <option value="chapter" <?= $courseware_block->getSectionsAsChapters() ? "selected" : "" ?>><?= _cw("Links in der Kapitelnavigation anzeigen") ?></option>
                        <option value="hide" <?= $courseware_block->getShowSectionNav() ? "" : "selected" ?> ><?= _cw("Nicht anzeigen") ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="courseware-scrollytelling">
                        <?= _cw('Scrollytelling aktivieren') ?><br>
                        <dfn id="courseware-scrollytelling-description">
                            <?= _cw('Wenn Sie diesen Schalter aktivieren, wird die Courseware in ein Scrollytelling verwandelt.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="courseware-scrollytelling"
                           name="courseware[scrollytelling]"
                           type="checkbox" <?= $courseware_block->getScrollytelling() ? "checked" : "" ?>>
                </td>
            </tr>

            <tr>
                <th colspan="2"><?= _cw('Blockeinstellungen') ?></th>
            </tr>

            <?= $this->render_partial('courseware/_settings_discussionblock') ?>

            <tr>
                <th colspan="2"><?= _cw('Selbsttests') ?></th>
            </tr>

            <tr>
                <td>
                    <label for="max-tries">
                        <?= _cw('Anzahl Versuche: Quiz (Selbsttest)') ?><br>
                        <dfn id="courseware-max-tries-description">
                            <?= _cw('Die Anzahl der Versuche, die ein Student beim Lösen von Aufgaben in einem Quiz vom Type Selbsttest hat, bevor die Lösung der Aufgabe angezeigt wird.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="max-tries" type="number" min="0" name="courseware[max-tries]" value="<?= htmlReady($courseware_block->max_tries) ?>" aria-describedby="courseware-max-tries-description">
                    <label style="margin-left: 20px;" for="num-counts-infinity">
                        <?= _cw('Unbegrenzt') ?>
                        <input id="max-tries-infinity" type="checkbox" name="courseware[max-tries-infinity]" aria-describedby="courseware-max-tries-description">
                    </label>
                    <? if ($courseware_block->max_tries === -1): ?>
                        <script>
                            document.getElementById('max-tries-infinity').checked = true;
                            document.getElementById('max-tries').value = 0;
                            document.getElementById('max-tries').disabled = true;
                        </script>
                    <? endif ?>
                    <script>
                        document.getElementById('max-tries-infinity').onchange = function() {
                            document.getElementById('max-tries').disabled = this.checked;
                        }
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="max-tries-iav">
                        <?= _cw('Anzahl Versuche: Interactive Video') ?><br>
                        <dfn id="courseware-max-tries-iav-description">
                            <?= _cw('Die Anzahl der Versuche, die ein Student beim Lösen von Aufgaben in einem interaktiven Video hat, bevor die Lösung der Aufgabe angezeigt wird.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="max-tries-iav" type="number" min="0" name="courseware[max-tries-iav]" value="<?= htmlReady($courseware_block->max_tries_iav) ?>" aria-describedby="courseware-max-tries-description">
                    <label style="margin-left: 20px;" for="num-counts-infinity">
                        <?= _cw('Unbegrenzt') ?>
                        <input id="max-tries-iav-infinity" type="checkbox" name="courseware[max-tries-iav-infinity]" aria-describedby="courseware-max-tries-iav-description">
                    </label>
                    <? if ($courseware_block->max_tries_iav === -1): ?>
                        <script>
                            document.getElementById('max-tries-iav-infinity').checked = true;
                            document.getElementById('max-tries-iav').value = 0;
                            document.getElementById('max-tries-iav').disabled = true;
                        </script>
                    <? endif ?>
                    <script>
                        document.getElementById('max-tries-iav-infinity').onchange = function() {
                            document.getElementById('max-tries-iav').disabled = this.checked;
                        }
                    </script>
                </td>
            </tr>

        </tbody>

        <tfoot>
            <tr>
                <td class="table_row_odd" colspan="2" align="center">
                    <?= Button::create(_cw('Übernehmen'), 'submit', array('title' => _cw('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

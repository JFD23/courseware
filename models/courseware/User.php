<?php

namespace Courseware;

use Mooc\DB\Block as DbBlock;
use Mooc\UI\Block as UiBlock;

/**
 * @author  <mlunzena@uos.de>
 *
 * @property string $perms
 */
class User extends \User
{
    private $container;

    /**
     * constructor, give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record.
     *
     * @param \Courseware\Container $container the DI container to use
     * @param mixed                 $id        primary key of table
     */
    public function __construct(Container $container, $id = null)
    {
        $this->container = $container;
        parent::__construct($id);
    }

    public function canCreate($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->canEditBlock($model);
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function canRead($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($this->canUpdate($model)) {
            return true;
        }

        if ($model instanceof DbBlock) {
            $perm = false;
            
            //checken ob es sich um eine ePortfolio Veranstaltung handelt
            $seminar = \Seminar::getInstance($model->seminar_id);
			$status = $seminar->getStatus();
            if ($status == \Config::get()->getValue('SEM_CLASS_PORTFOLIO')){
                //zugehörige kapitel_id
                if($model->type == 'Chapter'){
                    $chapter_id = $model->id;
                } else if ($model->type == 'Subchapter'){
                    $chapter_id = $model->parent_id;
                }  else if ($model->type == 'Section'){
                    $parent = $model->parent;
                    $chapter_id = $parent->parent_id;
                } else return true; //Coursewareblock
            
                if($chapter_id){
                    //normale user
                    //require_once('/var/www/html/studip3.5/public/plugins_packages/uos/EportfolioPlugin/models/EportfolioFreigabe.class.php');
                    require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/EportfolioFreigabe.class.php');
                    //\PluginEngine::getPlugin('ePortfolio');
                    $freigabe = new \EportfolioFreigabe();
                    $access = $freigabe::hasAccess($this->id, $model->seminar_id, $chapter_id);
                    if (!$access ){
                        return false;
                    }
                }
            }
            if ($this->isNobody()) {
                $course = \Course::find($model->seminar_id);
                $perm = get_config('ENABLE_FREE_ACCESS') && $course->lesezugriff == 0;
            } else {
                $perm = $this->hasPerm($model->seminar_id, 'user');
            }

            return $model->isPublished() && $perm;
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function canUpdate($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->canEditBlock($model);
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function canDelete($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->canEditBlock($model);
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function hasPerm($cid, $perm_level)
    {
        if (!$cid) {
            return false;
        }

        return $GLOBALS['perm']->have_studip_perm($perm_level, $cid, $this->id);
    }

    public function getPerm($cid)
    {
        if (!$cid) {
            return false;
        }

        return $GLOBALS['perm']->get_studip_perm($cid, $this->id);
    }

    public function isNobody()
    {
        return $this->id === 'nobody';
    }

    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////

    // get the editing permission level from the courseware's settings
    private function canEditBlock(DbBlock $block)
    {
        if ($this->isNobody()) {
            return false;
        }
        
        //checken ob es sich um eine ePortfolio Veranstaltung handelt
        $seminar = \Seminar::getInstance($block->seminar_id);
        $status = $seminar->getStatus();
        if ($status == \Config::get()->getValue('SEM_CLASS_PORTFOLIO')){

            require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/LockedBlock.class.php');
            if(\LockedBlock::isLocked($block->id)){
                return false;
            }

            if($block->type == 'Chapter'){
                $chapter_id = $block->id;
            } else if ($block->type == 'Subchapter'){
                $chapter_id = $block->parent_id;
            }  else if ($block->type == 'Section'){
                $parent = $block->parent;
                $chapter_id = $parent->parent_id;
            } else return true; //Coursewareblock

            if($chapter_id){
                //normale user
                //require_once('/var/www/html/studip3.5/public/plugins_packages/uos/EportfolioPlugin/models/EportfolioFreigabe.class.php');
                require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/EportfolioFreigabe.class.php');
                //\PluginEngine::getPlugin('ePortfolio');
                $freigabe = new \EportfolioFreigabe();
                $access = $freigabe::hasAccess($this->id, $block->seminar_id, $chapter_id);
                //var_dump('user: ' . $this->id. 'sem: '. $block->seminar_id .'chapter: '. $chapter_id. 'access: ' .$access);
                if (!$access){
                    return false;
                }
            }
        }
        
        $courseware = $this->container['current_courseware'];
        if(!$courseware){
            $courseware_model = $block->getCoursewareOfThisBlock();
            $courseware = $this->container['block_factory']->makeBlock($courseware_model);
        }

        return $this->hasPerm($block->seminar_id, $courseware->getEditingPermission());
    }
}

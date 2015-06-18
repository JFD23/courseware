<?php

require_once __DIR__.'/vendor/autoload.php';

use Mooc\Container;
use Mooc\DB\CoursewareFactory;
use Mooc\UI\BlockFactory;
use Mooc\User;

/**
 * MoocIP.class.php
 *
 * ...
 *
 * @author  <tgloeggl@uos.de>
 * @author  <mlunzena@uos.de>
 */
class Courseware extends StudIPPlugin implements StandardPlugin
{
    /**
     * @var Container
     */
    private $container;

    public function __construct() {
        parent::__construct();

        // adjust host system
        $this->setupCompatibility();

        $this->setupAutoload();
        $this->setupContainer();

        // deactivate Vips-Plugin for students if this course is capture by the mooc-plugin
        if ($this->isSlotModule()){
            // Navigation::removeItem('/course/files'); // TT DOUBLE HACK, no WYSIWYG-Upload if file tab is invisible...
            Navigation::removeItem('/course/blubberforum');
            if(!$GLOBALS['perm']->have_studip_perm("tutor", $this->container['cid'])) {
                Navigation::removeItem('/course/vipsplugin');
            }
        }
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize ()
    {
        PageLayout::setTitle($_SESSION['SessSemName']['header_line'] . ' - ' . $this->getPluginname());
        PageLayout::addStylesheet($this->getPluginURL().'/assets/style.css');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNavigation($course_id)
    {
        $cid = $course_id;
        $tabs = array();

        $courseware = $this->container['current_courseware'];

        $navigation = new Navigation($courseware->title,
                                     PluginEngine::getURL($this, compact('cid'), 'courseware', true));
        $navigation->setImage('icons/16/white/group3.png');
        $navigation->setActiveImage('icons/16/black/group3.png');

        $tabs['mooc_courseware'] = $navigation;

        $navigation->addSubnavigation('index',    clone $navigation);
        $navigation->addSubnavigation('settings',
                                      new Navigation(_("Einstellungen"),
                                                     PluginEngine::getURL($this, compact('cid'), 'courseware/settings', true)));

        // tabs for students
        if (!$this->container['current_user']->hasPerm($course_id, 'tutor')) {
            $progress_url = PluginEngine::getURL($this, compact('cid'), 'progress', true);
            $tabs['mooc_progress'] = new Navigation(_('Fortschrittsübersicht'), $progress_url);
        }

        // tabs for tutors and up
        else {
        }

        return $tabs;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getIconNavigation($course_id, $last_visit, $user_id)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfoTemplate($course_id)
    {
        return null;
    }
    

    public function perform($unconsumed_path)
    {
        require_once 'vendor/trails/trails.php';
        require_once 'app/controllers/studip_controller.php';
        require_once 'app/controllers/authenticated_controller.php';

        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            NULL
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return Request::option('cid') ?: $GLOBALS['SessionSeminar'];
    }

    /**
     * @return string
     */
    public function getCourseId()
    {
        return $this->container['cid'];
    }

    /**
     * @return CoursewareFactory
     */
    public function getCoursewareFactory()
    {
        return $this->container['courseware_factory'];
    }

    /**
     * @return BlockFactory
     */
    public function getBlockFactory()
    {
        return $this->container['block_factory'];
    }

    /**
     * @return string
     */
    public function getCurrentUserId()
    {
        return $this->container['current_user_id'];
    }

    /**
     * @return User
     */
    public function getCurrentUser()
    {
        return $this->container['current_user'];
    }

    /**
     * @return Pimple
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**********************************************************************/
    /* PRIVATE METHODS                                                    */
    /**********************************************************************/

    private function setupContainer()
    {
        $this->container = new Mooc\Container($this);
    }


    private function setupAutoload()
    {
        StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
    }

    private function getSemClass()
    {
        global $SEM_CLASS, $SEM_TYPE, $SessSemName;
        return $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']];
    }


    //HACKED
    private function isSlotModule()
    {
        if (!$this->getSemClass()) {
            return false;
        }
        return true;
        //TODO: why does it always return false?
        //return $this->getSemClass()->isSlotModule(get_class($this));
    }

    static function onEnable($id)
    {
        // enable nobody role by default
        RolePersistence::assignPluginRoles($id, array(7));
    }

    private function setupCompatibility()
    {
        if (!class_exists('\\Metrics')) {
            require_once __DIR__ . '/models/Metrics.v3_0.php';
        }
    }
}

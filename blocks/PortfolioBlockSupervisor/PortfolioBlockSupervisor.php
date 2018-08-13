<?php
namespace Mooc\UI\PortfolioBlockSupervisor;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlockSupervisor extends Block
{
    const NAME = 'Notiz für Supervisor';
    const BLOCK_CLASS = 'portfolio';
    const DESCRIPTION = 'Diese Notiz können nur meine Supervisoren lesen und beantworten';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('supervisorcontent', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $cid = $this->container['cid'];

        $roles  = \DBManager::get()->query("SELECT supervisor_id, owner_id FROM eportfolio WHERE Seminar_id = '$cid'")->fetchAll(\PDO::FETCH_ASSOC);
        $supervisorId     = $roles[0]['supervisor_id'];
        $ownerId     = $roles[0]['owner_id'];
  
        require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/Eportfoliomodel.class.php');
        $supervisoren = \Eportfoliomodel::getAllSupervisors($cid);
        
        if($this->getCurrentUser()->id == $supervisorId || in_array($this->getCurrentUser()->id, $supervisoren)) {
            $supervisor = true;
        } else {
            $supervisor = false;
        }

        if($this->getCurrentUser()->id == $ownerId) {
            $owner = true;
        } else {
            $owner = false;
        }

		$content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }
		$content = formatReady($content);
        
        $this->setGrade(1.0);
        if ($supervisor || $owner) {
            return array(
                'content' => $content,
                'supervisorcontent' => formatReady($this->supervisorcontent),
                'show_note' => true,
                'supervisor' => $supervisor,
                'owner' => $owner
            );
        } else {
            return array(
                'content' => "",
                'supervisorcontent' => "",
                'show_note' => false
            );
        }
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = htmlReady($this->content);

        return compact('content');
    }

    /**
     * Updates the block's contents.
     *
     * @param array $data The request data
     *
     * @return array The block's data
     */
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $content = \STUDIP\Markup::purifyHtml((string) $data['content']);
        if ($content == "") {
            $this->content = "";
        } else {
            $dom = new \DOMDocument();
            $dom->loadHTML($content);
            $xpath = new \DOMXPath($dom);
            $hrefs = $xpath->evaluate("//a");
            for ($i = 0; $i < $hrefs->length; $i++) {
                $href = $hrefs->item($i);
                if($href->getAttribute("class") == "link-extern") {
                $href->removeAttribute('target');
                $href->setAttribute("target", "_blank");
                }
            }
            $this->content = $dom->saveHTML();
            \NotificationCenter::postNotification('UserDidPostSupervisorNotiz', $this->id, \Course::findCurrent()->id);
        }
		$supervisorcontent = \STUDIP\Markup::purifyHtml((string) $data['supervisorcontent']);
        if ($supervisorcontent == "") {
            $this->supervisorcontent = "";
        } else {
            $dom = new \DOMDocument();
            $dom->loadHTML($supervisorcontent);
            $xpath = new \DOMXPath($dom);
            $hrefs = $xpath->evaluate("//a");
            for ($i = 0; $i < $hrefs->length; $i++) {
                $href = $hrefs->item($i);
                if($href->getAttribute("class") == "link-extern") {
                $href->removeAttribute('target');
                $href->setAttribute("target", "_blank");
                }
            }
            $this->supervisorcontent = $dom->saveHTML();
        }

        return array(
            'content' => $this->content,
            'supervisorcontent' => $this->supervisorcontent
        );
    }
    
    /**
     * Updates the block's contents.
     *
     * @param array $data                  The request data
     *
     * @return array The block's data
     */
    public function savesupervisor_handler(array $data)
    {
        $cid = $this->container['cid'];
        $supervisorQuery  = \DBManager::get()->query("SELECT supervisor_id FROM eportfolio WHERE Seminar_id = '$cid'")->fetchAll();
        $supervisorId     = $supervisorQuery[0][0];
        if($this->getCurrentUser()->id == $supervisorId) {
            // second param in if-block is special case for uos. old studip with new wysiwyg
            if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
                $this->supervisorcontent = \STUDIP\Markup::purifyHtml((string) $data['supervisorcontent']);
                \NotificationCenter::postNotification('SupervisorDidPostAnswer', $this->id, \Course::findCurrent()->id);
            } else {
              $this->supervisorcontent = (string) $data['supervisorcontent'];
              \NotificationCenter::postNotification('SupervisorDidPostAnswer', $this->id, \Course::findCurrent()->id);
            }
            return array(
                'content' => formatReady($this->content),
                'supervisorcontent' => formatReady($this->supervisorcontent)
            );
         } else {
            throw new Errors\AccessDenied(_cw("Sie sind nicht berechtigt diesen Block zu editieren.")); 
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/portfoliosupervisor/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/portfoliosupervisor/portfoliosupervisor-1.0.xsd';
    }
}

<?php
namespace Mooc\UI\PortfolioBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlock extends Block
{
    const NAME = 'Private Notiz';
    const BLOCK_CLASS = 'portfolio';
    const DESCRIPTION = 'Diese Notiz kann niemand ausser mir selbst sehen.';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $cid = $this->container['cid'];

        require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/Eportfoliomodel.class.php');
        $portfolio = \Eportfoliomodel::find($cid);
        if ($portfolio){
            $owner = $portfolio->owner;
        }

        $this->setGrade(1.0);
        
        $content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }
        $show_note = ($this->container['current_user']->id == $owner);
		$content = formatReady($content);
        
        return array(
            'content' => "",
            'show_note' => $show_note
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = htmlReady($this->content);

        return compact('content');
    }

    protected function authorizeUpdate(){
        parent::authorizeUpdate();
        $cid = $this->container['cid'];

        require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/Eportfoliomodel.class.php');
        $owner = \Eportfoliomodel::getOwner($cid);
        if ($this->container['current_user']->id != $owner) {
            throw new \Mooc\UI\Errors\AccessDenied(_cw("Sie sind nicht berechtigt diesen Block zu editieren."));
        }
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
        }

        return array('content' => $this->content);
    }

        /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/portfolio/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/portfolio/portfolio-1.0.xsd';
    }

}

<?
namespace Mooc\UI\PortfolioBlockUser;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlockUser extends Block
{
    const NAME = 'Öffentliche Notiz';
    const BLOCK_CLASS = 'portfolio';
    const DESCRIPTION = 'Diese Notiz können alle Nutzer lesen, die Zugriff auf mein Portfolio haben';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $cid = $this->container['cid'];
    
        require_once(get_config('PLUGINS_PATH') . '/uos/EportfolioPlugin/models/Eportfoliomodel.class.php');

        $this->setGrade(1.0);
        
        $content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }
		$content = formatReady($content);
        
        return array(
            'content' => $content,
        );
        
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = htmlReady($this->content);

        if ($this->container['wysiwyg_refined']) {
            $content = wysiwygReady($this->content);
        } else {
            $content = htmlReady($this->content);
        }
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
        }

        return array('content' => $this->content);
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/portfoliouser/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/portfoliouser/portfoliouser-1.0.xsd';
    }
}

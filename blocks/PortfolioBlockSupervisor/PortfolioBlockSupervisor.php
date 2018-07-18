<?
namespace Mooc\UI\PortfolioBlockSupervisor;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlockSupervisor extends Block
{
    const NAME = 'Notiz für Supervisor';

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
        //var_dump('supervisor: ' .$supervisorId);
        //var_dump('owner: ' . $ownerId);
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
                $this->supervisorcontent = \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify((string) $data['supervisorcontent']));
            } else {
              $this->supervisorcontent = (string) $data['supervisorcontent'];
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
    public function exportContents()
    {
        if (strlen($this->content) === 0) {
            return '';
        }

        $document = new \DOMDocument();
        $document->loadHTML($this->content);

        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('href'), function ($components) use ($block, $element) {
                $element->setAttribute('href', $block->buildUrl('http://internal.moocip.de', '/sendfile.php', $components));
            });
        }

        $imageElements = $document->getElementsByTagName('img');
        foreach ($imageElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('src')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('src'), function ($components) use ($block, $element) {
                $element->setAttribute('src', $block->buildUrl('http://internal.moocip.de', '/sendfile.php', $components));
            });
        }

        return $document->saveHTML();
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        /** @var \Seminar_User $user */
        global $user;

        $files = array();
        $crawler = new Crawler($this->content);
        $block = $this;

        // extract a file id from a URL
        $extractFile = function ($url) use ($user, $block) {
            return $block->applyCallbackOnInternalUrl($url, function ($components, $queryParams) use ($user) {
                if (isset($queryParams['file_id'])) {
                    $document = new \StudipDocument($queryParams['file_id']);

                    if (!$document->checkAccess($user->cfg->getUserId())) {
                        return null;
                    }

                    return array(
                        'id' => $queryParams['file_id'],
                        'name' => $document->name,
                        'description' => $document->description,
                        'filename' => $document->filename,
                        'filesize' => $document->filesize,
                        'url' => $document->url,
                        'path' => get_upload_file_path($queryParams['file_id']),
                    );
                }

                return null;
            });
        };

        // filter files referenced in anchor elements
        $crawler->filterXPath('//a')->each(function (Crawler $node) use ($extractFile, &$files) {
            $file = $extractFile($node->attr('href'));

            if ($file !== null) {
                $files[] = $file;
            }
        });

        // filter files referenced in image elements
        $crawler->filterXPath('//img')->each(function (Crawler $node) use ($extractFile, &$files) {
            $file = $extractFile($node->attr('src'));

            if ($file !== null) {
                $files[] = $file;
            }
        });

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function importContents($contents, array $files)
    {
        $document = new \DOMDocument();
        $document->loadHTML(studip_utf8decode($contents));

        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('href'), function ($components) use ($block, $element, $files) {
                parse_str($components['query'], $queryParams);
                $queryParams['file_id'] = $files[$queryParams['file_id']]->id;
                $components['query'] = http_build_query($queryParams);
                $element->setAttribute('href', $block->buildUrl($GLOBALS['ABSOLUTE_URI_STUDIP'], '/sendfile.php', $components));
            });
        }

        $imageElements = $document->getElementsByTagName('img');
        foreach ($imageElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('src')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('src'), function ($components) use ($block, $element, $files) {
                parse_str($components['query'], $queryParams);
                $queryParams['file_id'] = $files[$queryParams['file_id']]->id;
                $components['query'] = http_build_query($queryParams);
                $element->setAttribute('src', $block->buildUrl($GLOBALS['ABSOLUTE_URI_STUDIP'], '/sendfile.php', $components));
            });
        }
        $this->content = \STUDIP\Markup::purifyHtml($document->saveHTML());

        $this->save();
    }

    /**
     * Calls a callback if a given URL is an internal URL.
     *
     * @param string   $url      The url to check
     * @param callable $callback A callable to execute
     *
     * @return mixed The return value of the callback or null if the callback
     *               is not executed
     */
    public function applyCallbackOnInternalUrl($url, $callback)
    {
        if (!\Studip\MarkupPrivate\MediaProxy\isInternalLink($url) && substr($url, 0, 25) !== 'http://internal.moocip.de') {
            return null;
        }
        $components = parse_url($url);
        if (
            isset($components['path'])
            && substr($components['path'], -13) == '/sendfile.php'
            && isset($components['query'])
            && $components['query'] != ''
        ) {
            parse_str($components['query'], $queryParams);

            return $callback($components, $queryParams);
        }

        return null;
    }

    /**
     * Builds a dummy internal URL for file references.
     *
     * @param string   $baseUrl    The base URL
     * @param string   $path       The URL path
     * @param string[] $components The parts of the origin URL
     *
     * @return string The internal URL
     */
    public function buildUrl($baseUrl, $path, $components)
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/').'?'.$components['query'];
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

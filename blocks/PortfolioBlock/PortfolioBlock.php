<?
namespace Mooc\UI\PortfolioBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlock extends Block
{
    const NAME = 'Private Notiz';

    function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('ownerid', \Mooc\SCOPE_BLOCK, '');

        if ($this->ownerid === '') {
          $this->ownerid = $GLOBALS["user"]->id;
          $this->_fields['ownerid']->store();
        }
    }

    function student_view()
    {

        if (!$this->container['current_user']->canUpdate($this)) {
            // throw new Errors\AccessDenied(_cw("Sie sind nicht berechtigt Bl�cke zu l�schen."));
        }

        $this->setGrade(1.0);

        return array(
          'content' => formatReady($this->content),
          'ownerid' => $this->ownerid,
          'logged_in_userid' => $GLOBALS["user"]->id,
          'is_owner' => $this->ownerid === $GLOBALS["user"]->id,
          'viewMode' => 'true',
          'name' => 'ePortfolio Notiz',
        );
    }


    function author_view()
    {

        $this->authorizeUpdate();

        if ($this->container['wysiwyg_refined']) {
            $content = wysiwygReady($this->content);
        } else {
            $content = htmlReady($this->content);
        }
        return array('content' => $content, "ownerid" => $this->ownerid);

        if (!$this->container['current_user']->canUpdate($this)) {
            throw new Errors\AccessDenied(_cw("Sie sind nicht berechtigt Bl�cke zu l�schen."));
        }

    }

    /**
     * Updates the block's contents.
     *
     * @param array $data                  The request data
     *
     * @return array The block's data
     */
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        // second param in if-block is special case for uos. old studip with new wysiwyg
        if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
            $this->content = \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify((string) $data['content']));
        } else {
          $this->content = (string) $data['content'];
        }

        return array('content' => $this->content);
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

        if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
            return \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify($document->saveHTML()));
        } else {
            return $document->saveHTML();
        }
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
        $document->loadHTML(utf8_decode($contents));

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

        if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
            $this->content = \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify($document->saveHTML()));
        } else {
            $this->content = $document->saveHTML();
        }

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
}

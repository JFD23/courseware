<?php

use Mooc\DB\Block;
use Mooc\Export\Validator\XmlValidator;
use Mooc\Import\XmlImport;

/**
 *
 * @author Marcel Kipp <mkipp@uos.de>
 */
class ImportportfolioController extends CoursewareStudipController
{

    public function index_action()
    {
      $xml = $_POST['xml'];
      $xml = utf8_encode($xml);
      $tempDir = $_POST['path'];
      $tempDir = trim($tempDir);
      print_r(scandir($tempDir));

      $courseware = $this->container['current_courseware'];
      $importer = new XmlImport($this->plugin->getBlockFactory());
      $importer->import($tempDir, $courseware);
      //
      // $this->redirect(PluginEngine::getURL($this->plugin, array(), 'courseware'));
      // create a temporary directory
      // $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
      // mkdir($tempDir);
      //
      // file_put_contents($tempDir.'/data.xml', $xml);
      // print_r(scandir($tempDir));
      // $courseware = $this->container['current_courseware'];
      // $importer = new XmlImport($this->plugin->getBlockFactory());
      // $importer->import($tempDir, $courseware);
      //
      // $this->deleteRecursively($tempDir);

      exit();

    }

    private function deleteRecursively($path)
    {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                }

                if ($file->isFile() || $file->isLink()) {
                    unlink($file->getRealPath());
                } else if ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }

            rmdir($path);
        } else if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }
}

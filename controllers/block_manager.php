<?php

use Mooc\DB\Block;


/**
 * Controller to manage Courseware Blocks
 *
 * @author Ron Lucke <lucke@elan-ev.de>
 */
class BlockManagerController extends CoursewareStudipController
{
    public function index_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->plugin->getCourseId())) {
            throw new Trails_Exception(401);
        }

        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/block_manager.js');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/ziploader/zip-loader.min.js');

        if (Navigation::hasItem('/course/mooc_courseware/block_manager')) {
            Navigation::activateItem('/course/mooc_courseware/block_manager');
        }

        $this->cid = Request::get('cid');
        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $arr = $item->toArray();
                if (!$item->isStructuralBlock()) {
                    $arr['isBlock'] = true;
                    $arr['ui_block'] = $this->plugin->getBlockFactory()->makeBlock($item);
                }
                if ($arr['publication_date'] != null) {
                    $arr['publication_date'] = date('d.m.Y',$arr['publication_date']);
                }
                if ($arr['withdraw_date'] != null) {
                    $arr['withdraw_date'] = date('d.m.Y',$arr['withdraw_date']);
                }
                $arr['isPublished'] = $item->isPublished();
                $memo[$item->parent_id][] = $arr;
                return $memo;
            },
            array());
            
        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $this->courseware);
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $child);
            }
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        usort($parent['children'], function($a, $b) {
            return $a['position'] - $b['position'];
        });

        return $parent['children'];
    }
    
    public function store_changes_action()
    {
        $cid = Request::get('cid');
        $import = Request::get('import');
        $import_xml = Request::get('importXML');
        $chapter_list = json_decode(Request::get('chapterList'), true);
        $subchapter_list = json_decode(Request::get('subchapterList'), true);
        $section_list = json_decode(Request::get('sectionList'), true);
        $block_list = json_decode(Request::get('blockList'), true);

        $courseware = $this->container['current_courseware'];

        if ($import == 'false') {
            foreach(array($subchapter_list, $section_list, $block_list) as $list) {
                foreach($list as $key => $value) {
                    $parent = \Mooc\DB\Block::find($key);
                    foreach($value as $bid) {
                        $block = \Mooc\DB\Block::find($bid);
                        if ($parent->id != $block->parent_id) {
                            $block->parent_id = $parent->id;
                            $block->store();
                        }
                    }
                    $parent->updateChildPositions($value);
                }
            }
    
            $courseware = \Mooc\DB\Block::findCourseware($cid);
            if ($chapter_list != null) {
                $courseware->updateChildPositions($chapter_list);
            }
            return $this->redirect('block_manager?cid='.$cid.'&stored=true');
        } else {
            if ($import_xml == '') {
                return $this->redirect('block_manager?cid='.$cid.'&error=emptyxml');
            }

            // get relevant Blocks from Lists
            // find them in XML
            // create Blocks and change ids in Lists
            // update positions

            $xml = DOMDocument::loadXML($import_xml);

            foreach($chapter_list as &$chapter_id){
                if(strpos($chapter_id, 'import') > -1) {
                    $chapter_tempid = str_replace('import-', '', $chapter_id);
                    $chapter_title = '';
                    foreach($xml->getElementsByTagName('chapter') as $xml_chapter) {
                        if ($xml_chapter->getAttribute('temp-id') == $chapter_tempid) {
                            $chapter_title = $xml_chapter->getAttribute('title');
                        }
                    }
                    $data = array('title' => $chapter_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                    $block = $this->createAnyBlock($courseware->id, 'Chapter', $data);
                    $this->updateListKey($subchapter_list, $chapter_id, $block->id);
                    $chapter_id = $block->id;
                }
            }

            foreach($subchapter_list as $key => &$value) {
                $parent_id = $key;
                foreach($value as &$subchapter_id) {
                    if(strpos($subchapter_id, 'import') > -1) {
                        $subchapter_tempid = str_replace('import-', '', $subchapter_id);
                        $subchapter_title = '';
                        foreach($xml->getElementsByTagName('subchapter') as $xml_subchapter) {
                            if ($xml_subchapter->getAttribute('temp-id') == $subchapter_tempid) {
                                $subchapter_title = $xml_subchapter->getAttribute('title');
                            }
                        }
                        $data = array('title' => $subchapter_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($parent_id, 'Subchapter', $data);
                        $this->updateListKey($section_list, $subchapter_id, $block->id);
                        $subchapter_id = $block->id;
                    }
                }
            }

            foreach($section_list as $key => &$value) {
                $parent_id = $key;
                foreach($value as &$section_id) {
                    if(strpos($section_id, 'import') > -1) {
                        $section_tempid = str_replace('import-', '', $section_id);
                        $section_title = '';
                        foreach($xml->getElementsByTagName('section') as $xml_section) {
                            if ($xml_section->getAttribute('temp-id') == $section_tempid) {
                                $section_title = $xml_section->getAttribute('title');
                            }
                        }
                        $data = array('title' => $section_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($parent_id, 'Section', $data);
                        $this->updateListKey($block_list, $section_id, $block->id);
                        $section_id = $block->id;
                    }
                }
            }

            foreach($block_list as $key => &$value) {
                $parent_id = $key;
                foreach($value as &$block_id) {
                    if(strpos($block_id, 'import') > -1) {
                        $block_uuid = str_replace('import-', '', $block_id);
                        $block_title = '';
                        $block_node = '';
                        $block_type = '';
                        foreach($xml->getElementsByTagName('block') as $xml_block) {
                            if ($xml_block->getAttribute('uuid') == $block_uuid) {
                                $block_title = $xml_block->getAttribute('title');
                                $block_type = $xml_block->getAttribute('type');
                                $block_node = $xml_block;
                            }
                        }
                        //var_dump($block_node); echo '<br>';
                        $data = array('title' => $block_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($parent_id, $block_type, $data);
                        $this->updateListKey($block_list, $block_id, $block->id);
                        $block_id = $block->id;
                        $uiBlock = $this->plugin->getBlockFactory()->makeBlock($block);
                        if (gettype($uiBlock) != 'object') { 
                            $block->delete();
                            unset($block_list[$block_id]);
                            //TODO create error or message

                            return $this->redirect('block_manager?cid='.$cid.'&import=false&stored=true');
                        }
                        
                        $properties = array();

                        foreach ($block_node->attributes as $attribute) {
                           
                            if (!$attribute instanceof \DOMAttr) {
                                continue;
                            }
                            echo $attribute->name." : ".$attribute->value;
                            if ($attribute->namespaceURI !== null) {
                                $properties[$attribute->name] = $attribute->value;
                            }
                        }
                        if (count($properties) > 0) {
                            $uiBlock->importProperties($properties);
                        }
                        //TODO: import files
                        $files = array();

                        $uiBlock->importContents(trim($block_node->textContent), $files);
                    }
                }
            }

            return $this->redirect('block_manager?cid='.$cid.'&import=true&stored=true');
        }
    }

    private function createAnyBlock($parent, $type, $data)
    {
        $block = new \Mooc\DB\Block();
        $parent_id = is_object($parent) ? $parent->id : $parent;
        $block->setData(array(
            'seminar_id' => $data['cid'],
            'parent_id' => $parent_id,
            'type' => $type,
            'title' => $data['title'],
            'publication_date' => $data['publication_date'],
            'withdraw_date' => $data['withdraw_date'],
            'position' => $block->getNewPosition($parent_id)
        ));

        $block->store();

        return $block;
    }

    private function updateListKey(&$list, $oldkey, $newkey)
    {
        foreach($list as $key => $value) {
            if ($key == $oldkey) {
                $list[$newkey] = $list[$oldkey];
                unset($list[$oldkey]);

                return true;
            }
        }

        return false;
    }

}
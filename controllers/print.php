<?php

class PrintController extends CoursewareStudipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/courseware_print.css');

        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($this->plugin->getCourseId()));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks);
        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $memo[$item->parent_id][] = $item->toArray();
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
        }

        else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren(&$grouped, &$parent)
    {
        $parent['children'] = array_filter(
            isset($grouped[$parent['id']]) ? $grouped[$parent['id']] : array(),
            function ($item) {
                return $item['publication_date'] <= time();
        });
        if (isset($grouped[$parent['id']])) {
            foreach ($grouped[$parent['id']] as &$block) {
                $field = \Mooc\DB\Field::findOneBySQL('block_id = ? AND name = ?', array($block['id'], 'content'));
                $block["content"] = utf8_decode(json_decode($field['json_data']));
            }
        }
        return $parent['children'];
    }

}

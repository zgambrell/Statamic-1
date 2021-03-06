<?php

namespace Statamic\Addons\Nav;

use Statamic\API\URL;
use Statamic\API\Helper;
use Statamic\API\Content;

class TreeFactory
{
    /**
     * @var array
     */
    private $params;

    /**
     * Create a new TreeFactory
     *
     * @param  array  $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Create a new content tree
     *
     * @return  \Statamic\Addons\Nav\Tree
     */
    public function create()
    {
        // Get excluded pages and ensure they start with slashes
        if ($exclude = $this->params['exclude']) {
            $exclude = Helper::ensureArray($exclude);

            foreach ($exclude as $key => $val) {
                $exclude[$key] = URL::format($val);
            }
        }

        $tree_content = Content::tree(
            $this->params['from'],
            $this->params['depth'],
            $this->params['entries'],
            $this->params['unpublished'],
            $exclude
        );

        if (! $tree_content) {
            return false;
        }

        if ($this->params['include_home']) {
            $tree_content = $this->prependHome($tree_content);
        }

        $tree = new Tree($tree_content);

        $tree->filter($this->params['conditions']);

        return $tree;
    }

    /**
     * Add the home page to the start of the array
     *
     * @param array $output
     * @return array
     */
    private function prependHome($tree_content)
    {
        $home = [
            'page'     => Content::getRaw('/', null, true),
            'depth'    => 1,
            'children' => []
        ];

        $tree_content = array_reverse($tree_content);
        $tree_content[] = $home;

        return array_reverse($tree_content);
    }
}

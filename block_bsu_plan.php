<?php   // by zagorodnyuk 5/09/2012

include('lib_menu_plan.php');

class block_bsu_plan extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_bsu_plan');
    }

    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();

        $items = array();
        $this->content->footer = '';

        if (empty($this->instance)) {
            $this->content = '';
        } else {
            $this->load_content();
        }

        return $this->content;
    }

    function load_content() {
        global $CFG, $USER;

        $items = array();
        $index_items = get_items_menu_bsu_plan ($items);

		if (!empty($index_items))	{
			foreach ($index_items as $index_item)	{
				$this->content->items[] = $items[$index_item];
			}

        	$this->content->footer = '<a href="'.$CFG->wwwroot.'/blocks/bsu_plan/index.php">'.get_string('pluginname', 'block_bsu_plan').'</a>'.' ...';
 		}
    }

    function instance_allow_config() {
        return false;
    }
}



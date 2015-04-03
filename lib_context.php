<?php   // by Shtifanov 25/09/2012

/******************************** bsu_info ************************/
define('CONTEXT_UNIVERSITY', 1000);
define('CONTEXT_AREA', 1011);
define('CONTEXT_BUILDING', 1012);
define('CONTEXT_ROOM', 1013);

define('CONTEXT_FACULTY', 1021);
define('CONTEXT_ACADEMYGROUP', 1022);
define('CONTEXT_STUDENT',  1023);
define('CONTEXT_DISCIPLINE',  1024);
define('CONTEXT_SUBDEPARTMENT',  1031);

define('CONTEXT_DISSERSOVET',  1100);

context_helper::$alllevels[CONTEXT_UNIVERSITY] = 'context_university';
context_helper::$alllevels[CONTEXT_AREA] = 'context_area';
context_helper::$alllevels[CONTEXT_BUILDING] = 'context_building';
context_helper::$alllevels[CONTEXT_FACULTY] = 'context_faculty';
context_helper::$alllevels[CONTEXT_DISSERSOVET] = 'context_dissersovet';
context_helper::$alllevels[CONTEXT_SUBDEPARTMENT] = 'context_subdepartment';


/**
 * Faculty context class
 * @author Andrey Shtifanov
 * @since 2.2
 */
class context_university extends context {
    /**
     * Please use context_user::instance($userid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_UNIVERSITY) {
            throw new coding_exception("Invalid $record->contextlevel in context_university constructor.");
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('university', 'block_bsu_plan');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with User
     * @param boolean $short does not apply to user context
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($university = $DB->get_record('bsu_ref_university', array('id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('university', 'block_bsu_plan').': ';
            }
            $name .= $university->name;
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        global $COURSE;

        return new moodle_url('/blocks/bsu_plan/university.php', array('id'=>$this->_instanceid));
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel IN (".CONTEXT_UNIVERSITY.",".CONTEXT_FACULTY.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns user context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_user context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_UNIVERSITY, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_UNIVERSITY, 'instanceid'=>$instanceid))) {
            if ($university = $DB->get_record('bsu_ref_university', array('id'=>$instanceid), 'id', $strictness)) {
                $record = context::insert_context_record(CONTEXT_UNIVERSITY, $university->id, '/'.SYSCONTEXTID, 0);
            }
        }

        if ($record) {
            $context = new context_university($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Create missing context instances at user context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_UNIVERSITY.", u.id
                  FROM {bsu_ref_university} u
                 WHERE u.deleted = 0
                       AND NOT EXISTS (SELECT 'x'
                                         FROM {context} cx
                                        WHERE u.id = cx.instanceid AND cx.contextlevel=".CONTEXT_UNIVERSITY.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {bsu_ref_university} u ON (c.instanceid = u.id AND u.deleted = 0)
                   WHERE u.id IS NULL AND c.contextlevel = ".CONTEXT_UNIVERSITY."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at user context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        // First update normal users.
        $path = $DB->sql_concat('?', 'id');
        $pathstart = '/' . SYSCONTEXTID . '/';
        $params = array($pathstart);

        if ($force) {
            $where = "depth <> 2 OR path IS NULL OR path <> ({$path})";
            $params[] = $pathstart;
        } else {
            $where = "depth = 0 OR path IS NULL";
        }

        $sql = "UPDATE {context}  SET depth = 2, path = {$path}
                WHERE contextlevel = " . CONTEXT_UNIVERSITY . " AND ($where)";
        $DB->execute($sql, $params);
    }
}



/**
 * Area context class
 * @author Andrey Shtifanov
 * @since 2.2
 */
class context_area extends context {
    /**
     * Please use context_coursecat::instance($coursecatid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_AREA) {
            throw new coding_exception("Invalid $record->contextlevel in context_area constructor.");
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('area1', 'block_bsu_area');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($area = $DB->get_record('bsu_area', array('id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('area1', 'block_bsu_area').': ';
            }
            $name .= format_string($area->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/bsu_area/area.php', array('id'=>$this->_instanceid));
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT * FROM {capabilities}
                WHERE contextlevel IN (".CONTEXT_AREA.",".CONTEXT_BUILDING.",".CONTEXT_ROOM.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns faculty context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_coursecat context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_AREA, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_AREA, 'instanceid'=>$instanceid))) {
            if ($area = $DB->get_record('bsu_area', array('id'=>$instanceid), 'id', $strictness)) {
                $parentcontext = context_university::instance(1);
                $record = context::insert_context_record(CONTEXT_AREA, $area->id, $parentcontext->path);
            }
        }

        if ($record) {
            $context = new context_area($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts 
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_AREA);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at course category context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_AREA.", cc.id
                 FROM {bsu_area} cc
                 WHERE NOT EXISTS (SELECT 'x'
                 FROM {context} cx
                 WHERE cc.id = cx.instanceid AND cx.contextlevel=".CONTEXT_AREA.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "SELECT c.* FROM {context} c LEFT OUTER JOIN {bsu_area} cc ON c.instanceid = cc.id
                WHERE cc.id IS NULL AND c.contextlevel = ".CONTEXT_AREA;

        return $sql;
    }

    /**
     * Rebuild context paths and depths at course category context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_AREA." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
            }

            $sql = "INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {bsu_area} cm ON (cm.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_AREA.")
                      JOIN {context} pctx ON (pctx.instanceid = 1 AND pctx.contextlevel = ".CONTEXT_UNIVERSITY.")
                     WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                           $ctxemptyclause";
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('context_temp');
            $DB->execute($sql);
            context::merge_context_temp_table();
            $DB->delete_records('context_temp');
            $trans->allow_commit();
        }
    }
 
}


/**
 * Area context class
 * @author Andrey Shtifanov
 * @since 2.2
 */
class context_building extends context {
    /**
     * Please use context_coursecat::instance($coursecatid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_BUILDING) {
            throw new coding_exception("Invalid $record->contextlevel in context_building constructor.");
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('building1', 'block_bsu_area');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($building = $DB->get_record('bsu_area_building', array('id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('building1', 'block_bsu_area').': ';
            }
            $name .= format_string($building->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/bsu_area/building.php', array('id'=>$this->_instanceid));
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT * FROM {capabilities}
                WHERE contextlevel IN (".CONTEXT_BUILDING.",".CONTEXT_ROOM.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns faculty context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_coursecat context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_BUILDING, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_BUILDING, 'instanceid'=>$instanceid))) {
            if ($building = $DB->get_record('bsu_area_building', array('id'=>$instanceid), 'id, idarea', $strictness)) {
                $parentcontext = context_area::instance($building->idarea);
                $record = context::insert_context_record(CONTEXT_BUILDING, $building->id, $parentcontext->path);
            }
        }

        if ($record) {
            $context = new context_building($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts 
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_BUILDING);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at course category context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_BUILDING.", cc.id
                 FROM {bsu_area_building} cc
                 WHERE NOT EXISTS (SELECT 'x'
                 FROM {context} cx
                 WHERE cc.id = cx.instanceid AND cx.contextlevel=".CONTEXT_BUILDING.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "SELECT c.* FROM {context} c LEFT OUTER JOIN {bsu_area_building} cc ON c.instanceid = cc.id
                WHERE cc.id IS NULL AND c.contextlevel = ".CONTEXT_BUILDING;

        return $sql;
    }

    /**
     * Rebuild context paths and depths at course category context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_BUILDING." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
            }

            $sql = "INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {bsu_area_building} cm ON (cm.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_BUILDING.")
                      JOIN {context} pctx ON (pctx.instanceid = cm.idarea AND pctx.contextlevel = ".CONTEXT_AREA.")
                     WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                           $ctxemptyclause";
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('context_temp');
            $DB->execute($sql);
            context::merge_context_temp_table();
            $DB->delete_records('context_temp');
            $trans->allow_commit();
        }
    }
 
}





/**
 * Faculty context class
 * @author Andrey Shtifanov
 * @since 2.2
 */
class context_faculty extends context {
    /**
     * Please use context_coursecat::instance($coursecatid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_FACULTY) {
            throw new coding_exception("Invalid $record->contextlevel in context_faculty constructor.");
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('faculty', 'block_bsu_plan');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($faculty = $DB->get_record('bsu_ref_department', array('DepartmentCode'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('faculty', 'block_bsu_plan').': ';
            }
            $name .= format_string($faculty->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/bsu_ref/references.php?rn=bsu_ref_department', array('id'=>$this->_instanceid));
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT * FROM {capabilities}
                WHERE contextlevel IN (".CONTEXT_FACULTY.",".CONTEXT_SUBDEPARTMENT.",".CONTEXT_ACADEMYGROUP.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns faculty context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_coursecat context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_FACULTY, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_FACULTY, 'instanceid'=>$instanceid))) {
            if ($faculty = $DB->get_record('bsu_ref_department', array('DepartmentCode'=>$instanceid), 'Id,DepartmentCode', $strictness)) {
                $parentcontext = context_university::instance(1);
                $record = context::insert_context_record(CONTEXT_FACULTY, $faculty->departmentcode, $parentcontext->path);
            }
        }

        if ($record) {
            $context = new context_faculty($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts 
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_FACULTY);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at course category context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_FACULTY.", cc.DepartmentCode
                 FROM {bsu_ref_department} cc
                 WHERE NOT EXISTS (SELECT 'x'
                 FROM {context} cx
                 WHERE cc.DepartmentCode = cx.instanceid AND cx.contextlevel=".CONTEXT_FACULTY.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "SELECT c.* FROM {context} c LEFT OUTER JOIN {bsu_ref_department} cc ON c.instanceid = cc.DepartmentCode
                WHERE cc.DepartmentCode IS NULL AND c.contextlevel = ".CONTEXT_FACULTY;

        return $sql;
    }

    /**
     * Rebuild context paths and depths at course category context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_FACULTY." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
            }

            $sql = "INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {bsu_ref_department} cm ON (cm.DepartmentCode = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_FACULTY.")
                      JOIN {context} pctx ON (pctx.instanceid = 1 AND pctx.contextlevel = ".CONTEXT_UNIVERSITY.")
                     WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                           $ctxemptyclause";
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('context_temp');
            $DB->execute($sql);
            context::merge_context_temp_table();
            $DB->delete_records('context_temp');
            $trans->allow_commit();
        }
    }
 
}

/**
 * Dissersovet context class
 * @author fedoseev
 * @since 2.2
 */
class context_dissersovet extends context {
    /**
     * Please use context_coursecat::instance($coursecatid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_DISSERSOVET) {
            throw new coding_exception("Invalid $record->contextlevel in context_dissersovet constructor.");
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('disser', 'block_bsu_disser');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($disser = $DB->get_record('bsu_disser', array('id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('disser', 'block_bsu_disser').': ';
            }
            $name .= format_string($disser->namedisser, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/bsu_disser/index.php', array('id'=>$this->_instanceid));
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT * FROM {capabilities}
                WHERE contextlevel IN (".CONTEXT_DISSERSOVET.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns faculty context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_coursecat context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_DISSERSOVET, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_DISSERSOVET, 'instanceid'=>$instanceid))) {
            if ($disser = $DB->get_record('bsu_disser', array('id'=>$instanceid), 'id', $strictness)) {
                $parentcontext = context_university::instance(1);
                $record = context::insert_context_record(CONTEXT_DISSERSOVET, $disser->id, $parentcontext->path);
            }
        }

        if ($record) {
            $context = new context_dissersovet($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts 
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_DISSERSOVET);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at course category context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;
        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_DISSERSOVET.", cc.id
                 FROM {bsu_disser} cc
                 WHERE NOT EXISTS (SELECT 'x'
                 FROM {context} cx
                 WHERE cc.id = cx.instanceid AND cx.contextlevel=".CONTEXT_DISSERSOVET.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "SELECT c.* FROM {context} c LEFT OUTER JOIN {bsu_disser} cc ON c.instanceid = cc.id
                WHERE cc.id IS NULL AND c.contextlevel = ".CONTEXT_DISSERSOVET;

        return $sql;
    }

    /**
     * Rebuild context paths and depths at course category context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_DISSERSOVET." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
            }
            $sql = "INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {bsu_disser} cm ON (cm.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_DISSERSOVET.")
                      JOIN {context} pctx ON (pctx.instanceid = 1 AND pctx.contextlevel = ".CONTEXT_UNIVERSITY.")
                     WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                           $ctxemptyclause";
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('context_temp');
            $DB->execute($sql);
            context::merge_context_temp_table();
            $DB->delete_records('context_temp');
            $trans->allow_commit();
        }
    }
 
}

/**
 * Area context class
 * @author Andrey Shtifanov
 * @since 2.2
 */
//class context_building extends context {
class context_subdepartment extends context {
    /**
     * Please use context_coursecat::instance($coursecatid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_SUBDEPARTMENT) {
            throw new coding_exception("Invalid $record->contextlevel in context_subdepartment constructor.");
        }
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('subdepartment', 'block_bsu_plan');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
        if ($building = $DB->get_record('bsu_vw_ref_subdepartments', array('Id'=>$this->_instanceid))) {
            if ($withprefix){
                $name = get_string('subdepartment', 'block_bsu_plan').': ';
            }
            $name .= format_string($building->name, true, array('context' => $this));
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/blocks/bsu_ref/references.php?rn=bsu_vw_ref_subdepartments', array('Id'=>$this->_instanceid));
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT * FROM {capabilities}
                WHERE contextlevel IN (".CONTEXT_SUBDEPARTMENT.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns faculty context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_coursecat context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_SUBDEPARTMENT, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_SUBDEPARTMENT, 'instanceid'=>$instanceid))) {
            if ($building = $DB->get_record('bsu_vw_ref_subdepartments', array('Id'=>$instanceid), 'Id, DepartmentCode', $strictness)) {
                $parentcontext = context_faculty::instance($building->departmentcode);
                $record = context::insert_context_record(CONTEXT_SUBDEPARTMENT, $building->id, $parentcontext->path);
            }
        }

        if ($record) {
            $context = new context_subdepartment($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts 
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = array($this->_path.'/%', $this->depth+1, CONTEXT_SUBDEPARTMENT);
        $records = $DB->get_records_sql($sql, $params);

        $result = array();
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at course category context level
     * @static
     */
    protected static function create_level_instances() {
        global $DB;
        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_SUBDEPARTMENT.", cc.Id
                 FROM {bsu_vw_ref_subdepartments} cc
                 WHERE NOT EXISTS (SELECT 'x'
                 FROM {context} cx
                 WHERE cc.Id = cx.instanceid AND cx.contextlevel=".CONTEXT_SUBDEPARTMENT.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {

        $sql = "SELECT c.* FROM {context} c LEFT OUTER JOIN {bsu_vw_ref_subdepartments} cc ON c.instanceid = cc.Id
                WHERE cc.Id IS NULL AND c.contextlevel = ".CONTEXT_SUBDEPARTMENT;

        return $sql;
    }

    /**
     * Rebuild context paths and depths at course category context level.
     *
     * @static
     * @param $force
     */
    protected static function build_paths($force) {
        global $DB;

        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_SUBDEPARTMENT." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
            }
            $sql = "INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {bsu_vw_ref_subdepartments} cm ON (cm.Id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_SUBDEPARTMENT.")
                      JOIN {context} pctx ON (pctx.instanceid = cm.DepartmentCode AND pctx.contextlevel = ".CONTEXT_FACULTY.")
                     WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                           $ctxemptyclause";
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('context_temp');
            $DB->execute($sql);
            context::merge_context_temp_table();
            $DB->delete_records('context_temp');
            $trans->allow_commit();
        }
    }
 
}

?>
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block material_download
 *
 * @package    block_material_download
 * @copyright  2013 onwards Paola Frignani, TH Ingolstadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_material_download extends block_base {

    public function init() {
        $this->title = get_string('material_download', 'block_material_download');
    }

    public function get_content() {
        global $DB, $CFG, $OUTPUT, $COURSE;
        require_once("$CFG->libdir/resourcelib.php");

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $resources['resource'] = get_string('resource', 'block_material_download');
        $resources['folder'] = get_string('folder', 'block_material_download');

        $modinfo = get_fast_modinfo($COURSE);

        $meldung = <<<EOF
<script type="text/javascript">
function MM_jumpMenu(targ,selObj,restore){
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}
</script>
EOF;
?>
        <?php

        foreach ($modinfo->instances as $modname => $instances) {
            if (array_key_exists($modname, $resources)) {
                $ii = 0;
                foreach ($instances as $instancesid => $instance) {
                    if (!$instance->uservisible) {
                        continue;
                    }
                    $cms[$instance->id] = $instance;
                    $materialien[$instance->modname][] = $instance->id;

                    $ii++;
                }

                if ($ii > 0) {
                    $meldung .= $ii . ' ' . $resources[$modname] . '<br />';
                }
            }
        }

        $downloadlink = array();

        $sqlchk = "SELECT cm.id FROM {course_modules} cm WHERE cm.course = '" . $COURSE->id .
                "' AND ( cm.module = 14 OR cm.module = 6 )";
        $modules = $DB->get_records_sql($sqlchk);
        foreach ($modules as $module) {
            $checkid = $module->id;
            $sqlsec = "SELECT * FROM {course_sections} cs WHERE cs.course = ? AND ".
                    "( cs.sequence LIKE ? OR cs.sequence LIKE ? OR cs.sequence LIKE ? OR cs.sequence = ? ) LIMIT 1";
            $rowsec = $DB->get_records_sql($sqlsec, array($COURSE->id, $checkid . ",%", '%,' . $checkid . ',%', '%,' .
                $checkid, $checkid));
            foreach ($rowsec as $row) {
                if (!empty($row->section)) {
                    $sectid = $row->section;
                    $downloadlink[$sectid] = $row->name;
                }
            }
        }

        ksort($downloadlink);
        $showlink = '';
        foreach ($downloadlink as $value => $text) {
            $optionprefix = get_string('resource2', 'block_material_download') . ' ' .
                get_string('from', 'block_material_download') . ' ';

            // add section name modifier (i.e. "week" or "topic") if the course
            // format is known
            if ($COURSE->format == "weeks") {
                $optionprefix .= get_string('week', 'block_material_download') .' ';
            } elseif ($COURSE->format == "topics") {
                $optionprefix .= get_string('topic', 'block_material_download') .' ';
            } else {
                $optionprefix .= get_string('section', 'block_material_download') .' ';
            }
            // add title to option if there is long form of the section title
            if ($text) {
              $title = ' title="' . $text .'" ';
            } else {
              $title = '';
            }
            $showlink .= '<option ' . $title . ' value="' . $CFG->wwwroot .
                '/blocks/material_download/download_materialien.php?courseid=' . ($COURSE->id) . '&ccsectid=' .
                $value . '">' . $optionprefix . $value . '</option>';
        }
        if ($meldung != '') {
            $this->content->text = $meldung . '<br />';
            $this->content->footer = '<form><select name="jumpMenu" id="jumpMenu" onchange="MM_jumpMenu(\'parent\',this,0)">' .
                    '<option value="' . $CFG->wwwroot . $_SERVER['PHP_SELF'] . '?id=' . ($COURSE->id) . '">' .
                    get_string('choose', 'block_material_download') . '</option><option value="' . $CFG->wwwroot .
                    '/blocks/material_download/download_materialien.php?courseid=' . ($COURSE->id) . '&ccsectid=0">' .
                    get_string('download_files', 'block_material_download') . '</option>' . $showlink . '</select></form>';
        } else {
            $this->content->text = get_string('no_file_exist', 'block_material_download');
        }

        return $this->content;
    }

    public function applicable_formats() {
        return array('my' => false);
    }

}

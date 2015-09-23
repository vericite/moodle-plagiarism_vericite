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

defined('MOODLE_INTERNAL') || die();


class restore_plagiarism_vericite_plugin extends restore_plagiarism_plugin {
    protected $existingcourse;

    protected function define_module_plugin_structure() {
        $paths = array();

        $elename = 'vericiteconfigmod';
        $elepath = $this->get_pathfor('vericite_configs/vericite_config');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;

    }

    public function process_vericiteconfigmod($data) {
        global $DB;

        if ($this->task->is_samesite() && !$this->existingcourse) {
            if (! is_object($data)) {
                $data = (object) $data;
            }
            $recexists = $DB->record_exists(
                'plagiarism_vericite_config',
                array('name' => $data->name, 'value' => $data->value, 'cm' => $this->task->get_moduleid())
            );
            if (!$recexists) {
                $data = (object)$data;
                $data->cm = $this->task->get_moduleid();

                $DB->insert_record('plagiarism_vericite_config', $data);
            }
        }
    }
}
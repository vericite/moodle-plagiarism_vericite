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
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_plagiarism_vericite_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2015021000) {
      $table = new xmldb_table('plagiarism_vericite_files');
      $field1 = new xmldb_field('data', XMLDB_TYPE_TEXT, 'long', null, null, null, null, 'timeretrieved');
      $field2 = new xmldb_field('status', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'data');
      $field3 = new xmldb_field('attempts', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, 'status');

      if (!$dbman->field_exists($table, $field1)) {
        $dbman->add_field($table, $field1);
      }
      if (!$dbman->field_exists($table, $field2)) {
        $dbman->add_field($table, $field2);
      }
      if (!$dbman->field_exists($table, $field3)) {
        $dbman->add_field($table, $field3);
      }
      upgrade_plugin_savepoint(true, 2015021000, 'plagiarism', 'vericite');
    }

    if ($oldversion < 2015021202) {
      $table = new xmldb_table('plagiarism_vericite_score');
      if (!$dbman->table_exists($table)) {
        $field1 = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $field2 = new xmldb_field('cm', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $field3 = new xmldb_field('timeretrieved', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'cm');

        $key1 = new xmldb_key('primary');
        $key1->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);
        $key2 = new xmldb_key('cm');
        $key2->set_attributes(XMLDB_KEY_FOREIGN, array('cm'), 'course_modules', array('cm'));

        $table->addField($field1);
        $table->addField($field2);
        $table->addField($field3);
        $table->addKey($key1);
        $table->addKey($key2);
        $dbman->create_table($table);
      }
      upgrade_plugin_savepoint(true, 2015021202, 'plagiarism', 'vericite');
    }

    if ($oldversion < 2015021300) {
      // Nothing to update.
      upgrade_plugin_savepoint(true, 2015021300, 'plagiarism', 'vericite');
    }

    if($oldversion < 2016012600){
      $table = new xmldb_table('plagiarism_vericite_files');
      if ($dbman->table_exists($table)) {
        $field1 = new xmldb_field('timesubmitted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'attempts');
        if (!$dbman->field_exists($table, $field1)) {
          $dbman->add_field($table, $field1);
        }
      }

      upgrade_plugin_savepoint(true, 2016012600, 'plagiarism', 'vericite');
    }

    if ($oldversion < 2016021900) {
      //need to add tokenUser column in Token table
      $table = new xmldb_table('plagiarism_vericite_tokens');
      if ($dbman->table_exists($table)) {
        $field1 = new xmldb_field('tokenuser', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'token');
        if (!$dbman->field_exists($table, $field1)) {
          $dbman->add_field($table, $field1);
        }
        //change API url to new url
        $vcApi = $DB->get_record('config_plugins', array('plugin' => 'plagiarism', 'name' => 'vericite_api'));
        if($vcApi){
          $vcApi->value = "https://api.vericite.com/lms/";
          $DB->update_record('config_plugins', $vcApi);
        }

      }

      upgrade_plugin_savepoint(true, 2016021900, 'plagiarism', 'vericite');
    }

    if($oldversion < 2016082900){
      // Nothing to update.
      upgrade_plugin_savepoint(true, 2016082900, 'plagiarism', 'vericite');
    }

    if($oldversion < 2017021000){
        // Nothing to update.
        upgrade_plugin_savepoint(true, 2017021000, 'plagiarism', 'vericite');
    }

    if($oldversion < 2017021600){
        //need to add preliminary column in files table
        $table = new xmldb_table('plagiarism_vericite_files');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('preliminary', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'similarityscore');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2017021600, 'plagiarism', 'vericite');
    }

    if($oldversion < 2017051700){
        // Nothing to update.
        upgrade_plugin_savepoint(true, 2017051700, 'plagiarism', 'vericite');
    }

    return true;
}

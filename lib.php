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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since 2.0
 * @package    plagiarism_vericite
 * @subpackage plagiarism
 * @copyright  2010 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//get global class
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');

class plagiarism_plugin_vericite extends plagiarism_plugin {
     public function get_settings() {
        static $plagiarismsettings;
        if (!empty($plagiarismsettings) || $plagiarismsettings === false) {
            return $plagiarismsettings;
        }
        $plagiarismsettings = (array)get_config('plagiarism');
        // Check if enabled.
        if (isset($plagiarismsettings['vericite_use']) && $plagiarismsettings['vericite_use']) {
            // Now check to make sure required settings are set!
            if (empty($plagiarismsettings['vericite_api'])) {
                error("VeriCite API URL not set!");
            }
            if (empty($plagiarismsettings['vericite_accountid'])) {
                error("VeriCite Account Id not set!");
	    }
            if (empty($plagiarismsettings['vericite_secretkey'])) {
                error("VeriCite Secret not set!");
	    }
            return $plagiarismsettings;
        } else {
            return false;
        }
    }

     /**
     * hook to allow plagiarism specific information to be displayed beside a submission 
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     * 
     */
    public function get_links($linkarray) {
	global $COURSE;
	$vericite = array();
	$vericite['courseId'] = $COURSE->id;
	$vericite['courseTitle'] = $COURSE->fullname;
	$vericite['cmid'] = $linkarray['cmid'];
        $vericite['userid'] = $linkarray['userid'];
	if (!empty($linkarray['content'])) {
            $linkarray['content'] = '<html>' . $linkarray['content'] . '</html>';
            $vericite['filename'] = "Inline Submission";
            $vericite['filetype'] = "inline";
            $vericite['fileid'] = sha1($linkarray['content']);
            $vericite['filepath'] = "";
	} else if (!empty($linkarray['file'])) {
            $vericite['filename'] = $linkarray['file']->get_filename();
            $vericite['filetype'] = "file";
	    $vericite['fileid'] = $linkarray['file']->get_pathnamehash();
            $vericite['filepath'] =  $linkarray['file']->get_filepath();
        }
	$output = '';
        //add link/information about this file to $output
	foreach($vericite as $key => $value){
		$output .= $key . "=>" . $value . "<br/>";
	}
/*
      	$results = $this->get_file_results($cmid, $userid, $file);
        if (empty($results)) {
            // no results
            return '<br />';
        }

        if (array_key_exists('error', $results)) {
            return $results['error'];
        }
	$rank = plagiarism_get_css_rank($results['score']);

        $similaritystring = '<span class="' . $rank . '">' . $results['score'] . '%</span>';
        if (!empty($results['reporturl'])) {
            // User gets to see link to similarity report & similarity score
            $output = '<span class="plagiarismreport"><a href="' . $results['reporturl'] . '" target="_blank">';
            $output .= get_string('similarity', 'plagiarism_vericite').':</a>' . $similaritystring . '</span>';
        } else {
            // User only sees similarity score
            $output = '<span class="plagiarismreport">' . get_string('similarity', 'plagiarism_turnitin') . $similaritystring . '</span>';
        }
 */
echo "<pre>"; 
	print_r($linkarray);
echo "</pre>"; 
        return $output . "<br/>";
    }


    public function get_file_results($cmid, $userid, $file) {
        global $DB, $USER, $COURSE, $OUTPUT;

        $plagiarismsettings = $this->get_settings();
        if (empty($plagiarismsettings)) {
            // VeriCite is not enabled
            return false;
        }
        $plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm'=>$cmid), '', 'name,value');
        if (empty($plagiarismvalues['use_vericite'])) {
            // VeriCite not in use for this cm
            return false;
        }

        $filehash = $file->identifier;
        $modulesql = 'SELECT m.id, m.name, cm.instance'.
                ' FROM {course_modules} cm' .
                ' INNER JOIN {modules} m on cm.module = m.id ' .
                'WHERE cm.id = ?';
        $moduledetail = $DB->get_record_sql($modulesql, array($cmid));
        if (!empty($moduledetail)) {
            $module = $DB->get_record($moduledetail->name, array('id'=>$moduledetail->instance));
        }
        if (empty($module)) {
            // No such cmid
            return false;
        }

        $modulecontext = get_context_instance(CONTEXT_MODULE, $cmid);

        // Whether the user has permissions to see all items in the context of this module.
        $viewsimilarityscore = has_capability('plagiarism/vericite:viewsimilarityscore', $modulecontext);
        $viewfullreport = has_capability('plagiarism/vericite:viewfullreport', $modulecontext);
        if ($USER->id == $userid) {
            // The user wants to see details on their own report
            if ($plagiarismvalues['plagiarism_show_student_score'] == 1) {
                $viewsimilarityscore = true;
            }
            if ($plagiarismvalues['plagiarism_show_student_report'] == 1) {
                $viewfullreport = true;
            }
        }

        if (!$viewsimilarityscore && !$viewfullreport) {
            // The user has no right to see the requested detail.
            return false;
        }

        $plagiarismfile = $DB->get_record('plagiarism_vericite_files',
                array('cm' => $cmid, 'userid' => $userid, 'identifier' => $filehash));
        if (empty($plagiarismfile)) {
            // No record of that submission - so no links can be returned
            return false;
        }
        $results = array(
                'analyzed' => 0,
                'score' => '',
                'reporturl' => '',
                );
        if (isset($plagiarismfile->statuscode) && $plagiarismfile->statuscode != 'success') {
            //always display errors - even if the student isn't able to see report/score.
            $results['error'] = vericite_error_text($plagiarismfile->statuscode);
            return $results;
        }

        // All non-standard situations handled.
        $results['score'] = $plagiarismfile->similarityscore;
        if ($viewfullreport) {
            // User gets to see link to similarity report
            $results['reporturl'] = vericite_get_report_link($plagiarismfile, $COURSE, $plagiarismsettings);
        }

        if (!empty($plagiarismsettings['turnitin_enablegrademark'])) {
            $results['grademarklink'] = turnitin_get_grademark_link($plagiarismfile, $COURSE, $module, $plagiarismsettings);
        }
        return $results;
    }

    function vericite_get_report_link($file, $course, $plagiarismsettings, $isInstructor=false){
	$contextId = $course->id;
    }

    function vericite_error_text($statuscode, $notify=true) {
      global $OUTPUT, $CFG, $PAGE;
      $return = '';
      $statuscode = (int) $statuscode;

      if (!empty($statuscode)) {
        if($statuscode == 2){
	//don't have any errors right now; could do something like $return = get_string('vericiteerror'.$statuscode, 'plagiarism_vericite');
	}
	if (!empty($return) && $notify) {
            $return = $OUTPUT->notification($return, 'notifyproblem');
        }
      }
      return $return;
    }

    function vericite_generate_url($context, $assignment, $user){
	$url = $plagiarismsettings['vericite_api'];
    	if(!endsWith($url, "/")){
		$url .= "/";
	}
	if(isset($context)){
		$url .= $context . "/";
		if(isset($assignment)){
			$url .= $assignment . "/";
			if(isset($user)){
				$url .= $user . "/";
			}
		}
	}
    }

    function endsWith($haystack, $needle)
    {
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }


    /* hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
    */
    public function save_form_elements($data) {

    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context) {
	global $CFG, $DB;
	$plagiarismsettings = $this->get_settings();
        if (!$plagiarismsettings) {
            return;
        }
	$cmid = optional_param('update', 0, PARAM_INT);
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm'=>$cmid), '', 'name,value');
        }
        $plagiarismelements = $this->config_options();
	//add form:
	$mform->addElement('header', 'plagiarismdesc', get_string('pluginname', 'plagiarism_vericite'));
	$ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
	$mform->addElement('checkbox', 'use_vericite', get_string("usevericite", "plagiarism_vericite"));
	if(isset($plagiarismvalues['use_vericite'])){
		$mform->setDefault('use_vericite', $plagiarismvalues['use_vericite']);
	}else if(isset($plagiarismsettings['vericite_use_default'])){
		$mform->setDefault('use_vericite', $plagiarismsettings['vericite_use_default']);
	}
	$mform->addElement('checkbox', 'plagiarism_show_student_score', get_string("studentscorevericite", "plagiarism_vericite"));
	$mform->addHelpButton('plagiarism_show_student_score', 'studentscorevericite', 'plagiarism_vericite');
	$mform->disabledIf('plagiarism_show_student_score', 'use_vericite');
	if(isset($plagiarismvalues['plagiarism_show_student_score'])){
                $mform->setDefault('plagiarism_show_student_score', $plagiarismvalues['plagiarism_show_student_score']);
        }else if(isset($plagiarismsettings['vericite_student_score_default'])){
                $mform->setDefault('plagiarism_show_student_score', $plagiarismsettings['vericite_student_score_default']);
        }
	$mform->addElement('checkbox', 'plagiarism_show_student_report', get_string("studentreportvericite", "plagiarism_turnitin"));
	$mform->addHelpButton('plagiarism_show_student_report', 'studentreportvericite', 'plagiarism_vericite');
	$mform->disabledIf('plagiarism_show_student_report', 'use_vericite');
	if(isset($plagiarismvalues['plagiarism_show_student_report'])){
                $mform->setDefault('plagiarism_show_student_report', $plagiarismvalues['plagiarism_show_student_report']);
        }else if(isset($plagiarismsettings['vericite_student_score_default'])){
                $mform->setDefault('plagiarism_show_student_report', $plagiarismsettings['vericite_student_report_default']);
        }
    }

    public function config_options() {
        return array('use_vericite', 'plagiarism_show_student_score', 'plagiarism_show_student_report');
    }

    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;
        $plagiarismsettings = (array)get_config('plagiarism');
        //TODO: check if this cmid has plagiarism enabled.
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        echo format_text($plagiarismsettings['vericite_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        echo $OUTPUT->box_end();
    }

    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        //called at top of submissions/grading pages - allows printing of admin style links or updating status
    }

    /**
     * called by admin/cron.php 
     *
     */
    public function cron() {
        //do any scheduled task stuff
    }
}

function event_file_uploaded($eventdata) {
    $result = true;
        //a file has been uploaded - submit this to the plagiarism prevention service.

    return $result;
}
function event_files_done($eventdata) {
    $result = true;
        //mainly used by assignment finalize - used if you want to handle "submit for marking" events
        //a file has been uploaded/finalised - submit this to the plagiarism prevention service.

    return $result;
}

function event_mod_created($eventdata) {
    $result = true;
        //a new module has been created - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function event_mod_updated($eventdata) {
    $result = true;
        //a module has been updated - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function event_mod_deleted($eventdata) {
    $result = true;
        //a module has been deleted - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

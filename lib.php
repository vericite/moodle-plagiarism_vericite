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

     public $STATUS_SEND=0;
     public $STATUS_SUCCESS=1;
     public $STATUS_LOCKED=2;
     public $STATUS_FAILED=3;
     public $TOKEN_CACHE_MIN=20;

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
                print_error("VeriCite API URL not set!");
            }
            if (empty($plagiarismsettings['vericite_accountid'])) {
                print_error("VeriCite Account Id not set!");
	    }
            if (empty($plagiarismsettings['vericite_secretkey'])) {
                print_error("VeriCite Secret not set!");
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
	
	if (!empty($linkarray["file"])) {
            $file = $linkarray["file"];
            $filearea = $file->get_filearea();
	    if ($filearea == "feedback_files" 
		|| $filearea == "introattachment") {
                return;
            }
        }
	$plagiarismsettings = $this->get_settings();
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: get_links");
		error_log(print_r($linkarray, true));
	}	
	$vericite = array();
	$vericite['courseId'] = $COURSE->id;
	$vericite['courseTitle'] = $COURSE->fullname;
	$vericite['cmid'] = $linkarray['cmid'];
        $vericite['userid'] = $linkarray['userid'];
	if(!empty($linkarray['assignment']) && !is_number($linkarray['assignment'])){
		$vericite['assignmentTitle'] = $linkarray['assignment']->name;
	}
	if (!empty($linkarray['content']) && trim($linkarray['content']) != false) {
            $file = array();
	    $linkarray['content'] = '<html>' . $linkarray['content'] . '</html>';
            $file['filename'] = "InlineSubmission";
            $file['type'] = "inline";
	    $inlinePostfix = "";
	    if(isset($plagiarismsettings['vericite_disable_dynamic_inline']) && $plagiarismsettings['vericite_disable_dynamic_inline']){
		$inlinePostfix = "inline";
	    }else{
		$inlinePostfix = sha1($linkarray['content']);
	    }

            $file['identifier'] = $this->plagiarism_vericite_identifier_prefix($plagiarismsettings['vericite_accountid'], $linkarray['cmid'], $linkarray['userid']) . $inlinePostfix;
	    $file['filepath'] = "";
	    $file['userid'] = $linkarray['userid'];
	    $file['size'] = 100;
	    $file['content'] = $linkarray['content'];
	    $vericite['file'] = $file;
	} else if (!empty($linkarray['file'])) {
	    $file = array();
            $file['filename'] = (!empty($linkarray['file']->filename)) ? $linkarray['file']->filename : $linkarray['file']->get_filename();
            $file['type'] = "file";
	    $file['identifier'] = $this->plagiarism_vericite_identifier_prefix($plagiarismsettings['vericite_accountid'], $linkarray['cmid'], $linkarray['userid']) . $linkarray['file']->get_pathnamehash();
            $file['filepath'] =  (!empty($linkarray['file']->filepath)) ? $linkarray['file']->filepath : $linkarray['file']->get_filepath();
	    $file['userid'] = $linkarray['file']->get_userid();
	    $file['size'] = $linkarray['file']->get_filesize();
	    $vericite['file'] = $file;
        }
	if(empty($vericite['userid']) || !isset($file) 
		|| $file['userid'] !== $vericite['userid'] || $file['size'] > 52428800){
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: file isn't set or user id is wrong or file size is too large");
		}
		return "";
	}
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: vericite: " . print_r($vericite, true));
	}
	$output = '';
        //add link/information about this file to $output
      	$results = $this->get_file_results($vericite['cmid'], $vericite['userid'], !empty($linkarray['file']) ? $linkarray['file'] : null, $vericite);
        if (empty($results)) {
            // no results
            return '<br />';
        }

        if (array_key_exists('error', $results)) {
            return $results['error'];
        }
	$rank = $this->plagiarism_vericite_get_css_rank($results['score']);

        $similaritystring = '&nbsp;<span class="' . $rank . '">' . $results['score'] . '%</span>';
        if (!empty($results['reporturl'])) {
            // User gets to see link to similarity report & similarity score
            $output = '<span class="plagiarismreport"><a href="' . $results['reporturl'] . '" target="_blank">';
            $output .= get_string('similarity', 'plagiarism_vericite').':</a>' . $similaritystring . '</span>';
        } else {
            // User only sees similarity score
            $output = '<span class="plagiarismreport">' . get_string('similarity', 'plagiarism_vericite') . $similaritystring . '</span>';
        }
        return "<br/>" . $output . "<br/>";
    }


    public function get_file_results($cmid, $userid, $file, $vericite=null) {
        global $DB, $USER, $COURSE, $OUTPUT, $CFG;
     	$SCORE_CACHE_MIN = 60;
	$SCORE_FETCH_CACHE_MIN = 5;
        $plagiarismsettings = $this->get_settings();
        if (empty($plagiarismsettings)) {
            // VeriCite is not enabled
            return false;
        }
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
        	error_log("VeriCite: get_file_results: cmid: " . $cmid . ", userId: " . $userid . ", file: " . print_r($file, true)); 
	}
	$plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm'=>$vericite['cmid']), '', 'name,value');
	if (empty($plagiarismvalues['use_vericite'])) {
	    if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: not in use for this cm");
	    }
            // VeriCite not in use for this cm
            return false;
        }

        $modulecontext = context_module::instance($vericite['cmid']);

        // Whether the user has permissions to see all items in the context of this module.
        // this is determined by whether the user is a "course instructor" if they have assignment:grade

	$gradeassignment = has_capability('mod/assign:grade', $modulecontext);
	$viewsimilarityscore = $gradeassignment;
	$viewfullreport = $gradeassignment;

        if ($USER->id == $vericite['userid']) {
            // The user wants to see details on their own report
            if ($plagiarismvalues['plagiarism_show_student_score'] == 1) {
                $viewsimilarityscore = true;
            }
            if ($plagiarismvalues['plagiarism_show_student_report'] == 1) {
                $viewfullreport = true;
            }
        }

        if (!$viewsimilarityscore && !$viewfullreport) {
	   if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
           	error_log("VeriCite: The user has no right to see the requested detail");
	    }
	    // The user has no right to see the requested detail.
            return false;
        }

        $results = array(
                'analyzed' => 0,
                'score' => '',
                'reporturl' => '',
                );

	//first check if we already have looked up the score for this class
	$fileId = $vericite['file']['identifier'];
	$score = -1;
	
	$myContent = null;
	$contentScore = $DB->get_records('plagiarism_vericite_files', array('cm'=>$vericite['cmid'], 'userid'=>$userid, 'identifier'=>$fileId), '', 'id,cm,userid,identifier,similarityscore, timeretrieved, status');
	if(!empty($contentScore)){
		foreach($contentScore as $content){
			$myContent = $content;
			if(!empty($plagiarismsettings['vericite_enable_debugging']) 
				&& $plagiarismsettings['vericite_enable_debugging']){
				error_log("VeriCite: content: " . print_r($myContent, true));
			}
			if($content->status == $this->STATUS_SUCCESS && time() - (60 * $SCORE_CACHE_MIN) < $content->timeretrieved){
				//since our reports are dynamic, only use the db as a cache
				//if its too old of a results, don't set the score and just grab a new one
				//from the API
				$score = $content->similarityscore;
				if(!empty($plagiarismsettings['vericite_enable_debugging']) 
					&& $plagiarismsettings['vericite_enable_debugging']){
					error_log("VeriCite: content score: " . $score);
				}
			}else{
				if(!empty($plagiarismsettings['vericite_enable_debugging']) 
					&& $plagiarismsettings['vericite_enable_debugging']){
					error_log("VeriCite: content was too old: " . $content->timeretrieved);
				}
			}
			break;
		}
	}else{
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: no content scores in db");
		}
	}
	//check to see if we've already looked up the scores just recently:
	$scoreCacheArray = $DB->get_records('plagiarism_vericite_score', array('cm'=>$cmid), '', 'id, cm, timeretrieved');
	$scoreCache = array_shift($scoreCacheArray);
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: score cache: " . print_r($scoreCache, true));
	}
	if($score < 0 && (empty($scoreCache) || $scoreCache->timeretrieved < time() - (60 * $SCORE_FETCH_CACHE_MIN))){
		//ok, we couldn't find the score in the cache, try to look it up with the webservice
		$score = $this->plagiarism_vericite_get_scores($plagiarismsettings, $COURSE->id, $cmid, $fileId, $userid);
		//update score DB table with the current fetch time
		if(empty($scoreCache)){
			$scorecacheelement = new object();
			$scorecacheelement->cm = $cmid;
			$scorecacheelement->timeretrieved = time();		
			$DB->insert_record('plagiarism_vericite_score', $scorecacheelement);
		}else{
			$scoreCache->timeretrieved = time();
                        $DB->update_record('plagiarism_vericite_score', $scoreCache);
                }
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: looked up score in VeriCite: " . $score);
		}
	}else{
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: Score lookup throttled, didn't look up score in VeriCite: score: " . $score . " ; now: " . time());
		}
	}
	if($score < 0 && (empty($myContent) || $myContent->status == $this->STATUS_SUCCESS)){
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: can't find the score in the cache, the db, or VeriCite and its not scheduled to be uploaded");
		}
		//ok can't find the score in the cache, the db, or VeriCite and its not scheduled to be uploaded
		$user = ($userid == $USER->id ? $USER : $DB->get_record('user', array('id'=>$userid)));

		$customData = array(
					'courseId' => $COURSE->id,
					'cmid' => $cmid,
					'userid' => $user->id,
					'userEmail' => $user->email,
					'userFirstName' => $user->firstname,
					'userLastName' => $user->lastname,
					'vericite' => $vericite,
					'file' => (!empty($file)) ? serialize($file) : "",
					'dataroot' => $CFG->dataroot,
					'contentUserGradeAssignment' => has_capability('mod/assign:grade', $modulecontext, $userid)
					);
		//store for cron job to submit the file
		$update = true;
		if(empty($myContent)){
			$newelement = new object();
			$update = false;
		}else{
			$newelement = $myContent;
		}
		$newelement->cm = $cmid;
		$newelement->timeretrieved = 0;
		$newelement->identifier = $fileId;
		$newelement->userid = $userid;
		$newelement->data = base64_encode(serialize($customData));
		$newelement->status = $this->STATUS_SEND;
		try{
			if($update){
				$DB->update_record('plagiarism_vericite_files', $newelement);
			}else{
				$DB->insert_record('plagiarism_vericite_files', $newelement);
			}
		}catch (Exception $e) {
			error_log($e->getMessage());
			error_log("VeriCite: newelement: " . print_r($newelement, true));
	        	error_log("VeriCite: user: " . print_r($user, true));
		}
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: scheduled file to be uploaded: " . print_r($newelement, true));
		}
	}

			
	if($score >= 0){
		//we have successfully found the score and it has been evaluated:
		$results['analyzed'] = 1;
		$results['score'] = $score;
		if($viewfullreport){
			//see if the token already exists
			$conditions = array('cm'=>$vericite['cmid'], 'userid'=>$USER->id);
			if(!$gradeassignment){
				//instructors can view anything in the site, so don't pass in the identifier
				//however, this isn't an instructor, so look it up by the fileid
				$conditions['identifier'] = $fileId;
			}
			if(!empty($plagiarismsettings['vericite_enable_debugging']) 
				&& $plagiarismsettings['vericite_enable_debugging']){
				error_log("VeriCite: looking for token: " . print_r($conditions, true));
			}
			$dbTokens = $DB->get_records('plagiarism_vericite_tokens', $conditions);
			foreach($dbTokens as $dbToken){
				if(!empty($plagiarismsettings['vericite_enable_debugging']) 
					&& $plagiarismsettings['vericite_enable_debugging']){
					error_log("VeriCite: db token: " . print_r($dbToken, true));
				}
				//instructors can have multiple tokens, see if any of them aren't expired
				//if it's not an instructor, there should only be one token in the array anyways
				if(time() - (60 * $this->TOKEN_CACHE_MIN) < $dbToken->timeretrieved){
					//we found an existing token, set token and break out
					$token = $dbToken->token;
					break;
				}	
			}	
			
			if(!isset($token)){
				//didn't find it in cache, get a new token
				$fields = array();
				$fields['consumer'] = $plagiarismsettings['vericite_accountid'];
	            		$fields['consumerSecret'] = $plagiarismsettings['vericite_secretkey'];
				if(!$gradeassignment){
					//non instructors can only see their own items
					$fields['externalContentId'] = $fileId;
					$fields['userRole'] = 'Learner';
					//also make sure their token requires the context id, assignment id and user id
					$url = $this->plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $COURSE->id, $vericite['cmid'], $USER->id);
				}else{
					//send over the param for role so that instructors can see more details:
					$fields['userRole'] = 'Instructor';
					//instructors only need to pass in the site id since they can view anything in this site
					$url = $this->plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $COURSE->id);
				}
				$fields['tokenRequest'] = 'true';
				if(!empty($plagiarismsettings['vericite_enable_debugging']) 
					&& $plagiarismsettings['vericite_enable_debugging']){
					error_log("VeriCite: requesting token: url: " . $url . " ; fields: " . print_r($fields, true));
				}
				$c = new curl(array('proxy'=>true));
        			$status = json_decode($c->post($url, $fields));
				if(!empty($plagiarismsettings['vericite_enable_debugging']) 
					&& $plagiarismsettings['vericite_enable_debugging']){
					error_log("VeriCite: token response: " . print_r($status, true));
				}
				if (! empty ( $status ) && isset ( $status->token )) {
					$token = $status->token;
					
					// store token in db to use again:
					$id = - 1;
					foreach ( $dbTokens as $dbToken ) {
						if ($dbToken->cm == $cmid && $dbToken->userid == $USER->id && ($gradeassignment || $dbToken->identifier == $fileId)) {
							// we found an existing score in the db, update it
							$id = $dbToken->id;
						}
						// this is a matched db item from the query above,
						// so we should update the token id and time no matter what
						$dbToken->token = $token;
						$dbToken->timeretrieved = time ();
						$DB->update_record ( 'plagiarism_vericite_tokens', $dbToken );
					}
					if ($id < 0) { // token doesn't already exist, add it
						$newelement = new object ();
						$newelement->cm = $cmid;
						$newelement->userid = $USER->id;
						if (! $gradeassignment) {
							// not an instructor, so make sure the token set the fileid
							$newelement->identifier = $fileId;
						}
						$newelement->timeretrieved = time ();
						$newelement->token = $token;
						$DB->insert_record ( 'plagiarism_vericite_tokens', $newelement );
					}
				}
			}
			if(isset($token)){
				//create url for user:
				$fields = array();
				$fields['consumer'] = $plagiarismsettings['vericite_accountid'];
				$fields['token'] = $token;
				$fields['externalContentId'] = $fileId;
				$fields['viewReport'] = 'true';
				if($gradeassignment){
					$fields['userRole'] = 'Instructor';
				}else{
					$fields['userRole'] = 'Learner';
				}
				
				$urlParams = "";
				foreach($fields as $key => $value){
					if(!empty($urlParams)){
						$urlParams .= "&";
					}
					$urlParams .= $key . "=" . rawurlencode($value);
				}
				//create a new url that passes in all the information for context, assignment and userId
				$url = $this->plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $COURSE->id, $vericite['cmid'], $USER->id);
				$results['reporturl'] = $url . "?" . $urlParams;
			}
		}
	}else{
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: score wasn't found, returning nothing");
		}
		return false;
	}
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: results: " . print_r($results, true));
	}
        return $results;
    }

    /* hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
    */
    public function save_form_elements($data) {
	global $DB;
	if (!$this->get_settings()) {
            return;
        }
	    //array of posible plagiarism config options.
            $plagiarismelements = $this->config_options();
	    //array of posible plagiarism config options.
            //first get existing values
	    $existingelements = $DB->get_records_menu('plagiarism_vericite_config', array('cm'=>$data->coursemodule), '', 'name,id');
	    foreach ($plagiarismelements as $element) {
                $newelement = new object();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                $newelement->value = (isset($data->$element) ? $data->$element : 0);
		if (isset($existingelements[$element])) { //update
                    $newelement->id = $existingelements[$element];
                    $DB->update_record('plagiarism_vericite_config', $newelement);
                } else { //insert
                    $DB->insert_record('plagiarism_vericite_config', $newelement);
                }

            }
            
         //now save the assignment title and instructrions and files (not a big deal if this fails, so wrap in try catch)
         try {
	         $plagiarismsettings = $this->get_settings();
	         if ($plagiarismsettings) {
		         $fields = array();
		         $fields['consumer'] = $plagiarismsettings['vericite_accountid'];
		         $fields['consumerSecret'] = $plagiarismsettings['vericite_secretkey'];
		         $fields['updateAssignmentDetails'] = 'true';
		         $fields['assignmentTitle'] = $data->name;
		         $fields['assignmentInstructions'] = $data->intro;
		         $url = $this->plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $data->course, $data->coursemodule);
		         $c = new curl(array('proxy'=>true));
		         $status = json_decode($c->post($url, $fields));
		}
         } catch (Exception $e) {
         }
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context, $modulename = '') {
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
	}else if(strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_use_default'])){
		$mform->setDefault('use_vericite', $plagiarismsettings['vericite_use_default']);
	}
	$mform->addElement('checkbox', 'plagiarism_show_student_score', get_string("studentscorevericite", "plagiarism_vericite"));
	$mform->addHelpButton('plagiarism_show_student_score', 'studentscorevericite', 'plagiarism_vericite');
	$mform->disabledIf('plagiarism_show_student_score', 'use_vericite');
	if(isset($plagiarismvalues['plagiarism_show_student_score'])){
                $mform->setDefault('plagiarism_show_student_score', $plagiarismvalues['plagiarism_show_student_score']);
        }else if(strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_student_score_default'])){
                $mform->setDefault('plagiarism_show_student_score', $plagiarismsettings['vericite_student_score_default']);
        }else if(strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_student_score_default_forums'])){
                $mform->setDefault('plagiarism_show_student_score', $plagiarismsettings['vericite_student_score_default_forums']);
	}
	$mform->addElement('checkbox', 'plagiarism_show_student_report', get_string("studentreportvericite", "plagiarism_vericite"));
	$mform->addHelpButton('plagiarism_show_student_report', 'studentreportvericite', 'plagiarism_vericite');
	$mform->disabledIf('plagiarism_show_student_report', 'use_vericite');
	if(isset($plagiarismvalues['plagiarism_show_student_report'])){
                $mform->setDefault('plagiarism_show_student_report', $plagiarismvalues['plagiarism_show_student_report']);
        }else if(strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_student_score_default'])){
                $mform->setDefault('plagiarism_show_student_report', $plagiarismsettings['vericite_student_report_default']);
        }else if(strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_student_score_default_forums'])){
                $mform->setDefault('plagiarism_show_student_report', $plagiarismsettings['vericite_student_report_default_forums']);
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
	global $OUTPUT,$DB;
        $output = '';
	if (!empty($cmid)) {
		$plagiarismsettings = (array)get_config('plagiarism');
		$disclosure = $plagiarismsettings['vericite_student_disclosure'];
		if(!empty($disclosure)){
        		$plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm'=>$cmid), '', 'name,value');
			if(!empty($plagiarismvalues) && $plagiarismvalues['use_vericite']){
				$contents = format_text($disclosure, FORMAT_MOODLE, array("noclean" => true));
				$output = $OUTPUT->box($contents, 'generalbox boxaligncenter', 'intro');
			}
		}
	}
	return $output;
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
	global $CFG, $DB;

	$plagiarismsettings = $this->get_settings();

	//Submit queued files:
	$dbFiles = $DB->get_records('plagiarism_vericite_files', array('status'=>$this->STATUS_SEND), '', 'id, cm, userid, identifier, data, status, attempts');
	if(!empty($dbFiles)){
	  $fileIds = array();
	  foreach($dbFiles as $dbFile){
    		//lock DB records that will be worked on
		array_push($fileIds, $dbFile->id);		
	  }
	  list($dsql, $dparam) = $DB->get_in_or_equal($fileIds);
	  //TODO: Oracle 1000 in clause limit
	  $DB->execute("update {plagiarism_vericite_files} set status = " . $this->STATUS_LOCKED . " where id " . $dsql, $dparam); 

	  foreach($dbFiles as $dbFile){
	    try{
		$customdata = unserialize(base64_decode($dbFile->data));	
		$userid = $customdata['userid'];
    		$vericite = $customdata['vericite'];
		if(!empty($customdata['file'])){
                       $file = get_file_storage();
                       $file = unserialize($customdata['file']);
                }
		$url = $this->plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $customdata['courseId'], $customdata['cmid'], $userid);
		$fields = array();    		
    		if(!empty($customdata['userFirstName'])){
    			$fields['userFirstName'] = $customdata['userFirstName'];
    		}
    		if(!empty($customdata['userLastName'])){
    			$fields['userLastName'] = $customdata['userLastName'];
    		}
    		if(!empty($customdata['userEmail'])){
    			$fields['userEmail'] = $customdata['userEmail'];
    		}
    		$fields['userRole'] = $customdata['contentUserGradeAssignment'] ?  'Instructor' : 'Learner';
    		$fields['consumer'] = $plagiarismsettings['vericite_accountid'];
    		$fields['consumerSecret'] = $plagiarismsettings['vericite_secretkey'];
    		if(isset($vericite['assignmentTitle'])){
    			$fields['assignmentTitle'] = $vericite['assignmentTitle'];
    		}
    		$fields['externalContentId'] = $dbFile->identifier;
    		//create a tmp file to store data:
    		if (!check_dir_exists($customdata['dataroot']."/plagiarism/", true, true)) {
    			mkdir($customdata['dataroot']."/plagiarism/", 0700);
    		}
    		$filename = $customdata['dataroot'] . "/plagiarism/" . time() . $vericite['file']['filename'];
    		$fh = fopen($filename, 'w');
		if (!empty($vericite['file']['type']) && $vericite['file']['type'] == "file"){
			if(!empty($file->filepath)){
                               fwrite($fh, file_get_contents($file->filepath));
                        }else{
                               fwrite($fh, $file->get_content());
                        }
		}else{
			fwrite($fh, $vericite['file']['content']);
		}
    		fclose($fh);
    		$fields['fileName'] = $vericite['file']['filename'];
    		
		if(!empty($plagiarismsettings['vericite_enable_debugging']) 
			&& $plagiarismsettings['vericite_enable_debugging']){
			error_log("VeriCite: cron submit: url: " . $url . " ; fields: " . print_r($fields, true));
		}
		
		if (class_exists('CURLFile')) {
			$fields['filedata'] = new CURLFile($filename);
    		}else{
			$fields['filedata'] = '@' . $filename;
		}
    		$c = new curl(array('proxy'=>true));
		$status = json_decode($c->post($url, $fields));
		if(!empty($status) && isset($status->result) && strcmp("success", $status->result) == 0){
			//success: do nothing
			if(!empty($plagiarismsettings['vericite_enable_debugging']) 
				&& $plagiarismsettings['vericite_enable_debugging']){
				error_log("VeriCite: cron submit success.");
			}
		}else{
			//error of some sort, do not save
			throw new Exception('failed to send file to VeriCite: ' . $status);
		} 
		unlink($filename);
		//now update the record to show we have retreived it
	    	$dbFile->status=$this->STATUS_SUCCESS;
		$dbFile->data = "";
    		$DB->update_record('plagiarism_vericite_files', $dbFile);
		//clear cache scores so that the score will be looked up immediately
		$DB->execute("delete from {plagiarism_vericite_score} where cm = " . $dbFile->cm);
	    }catch(Exception $e){
		error_log("VeriCite Cron Error: " . $e->getMessage());
		error_log(print_r($e,true));
		//something happened, unlock this to try again later
		if($dbFile->attempts < 500){
			$dbFile->status=$this->STATUS_SEND;
			$dbFile->attempts=$dbFile->attempts + 1;
		}else{
			$dbFile->status=$this->STATUS_FAILED;
		}
		$DB->update_record('plagiarism_vericite_files', $dbFile);
            }
	  }
	}

	//Delete old tokens:
	$DB->execute("delete from {plagiarism_vericite_tokens} where timeretrieved < " . (time() - (60 * $this->TOKEN_CACHE_MIN))); 

    }



   function plagiarism_vericite_get_scores($plagiarismsettings, $courseId, $cmid, $fileId, $userid){
	global $DB;
	$score = -1;
	$url = $this->plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $courseId, $cmid);
	$fields = array();
	$fields['consumer'] = $plagiarismsettings['vericite_accountid'];
	$fields['consumerSecret'] = $plagiarismsettings['vericite_secretkey'];
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: looking up scores in VeriCite: url: " . $url . " ; fields: " . print_r($fields, true));
	}
	$c = new curl(array('proxy'=>true));
      	$scores = json_decode($c->post($url, $fields));
	if(!empty($plagiarismsettings['vericite_enable_debugging']) 
		&& $plagiarismsettings['vericite_enable_debugging']){
		error_log("VeriCite: score results: " . print_r($scores, true));
	}
	//store results in the cache and set $score if you find the appropriate file score
	$apiScores = array();
	if(!empty($scores)){
		foreach($scores as $resultUserId => $resultUserScores){
			foreach($resultUserScores as $resultCMID => $resultCMIDScores){
				foreach($resultCMIDScores as $resultContentId => $resultContentScore){
					$newelement = new object();
					$newelement->cm = $resultCMID;
					$newelement->userid = $resultUserId;
					$newelement->identifier = $resultContentId;
					$newelement->similarityscore = $resultContentScore;
					$newelement->timeretrieved = time();
					$newelement->status = $this->STATUS_SUCCESS;
					$newelement->data = '';
					$apiScores = array_merge($apiScores, array($newelement));
					if($resultContentId == $fileId && $resultUserId == $userid){
						//we found this file's score, so set it:
						$score = $resultContentScore;
					}
				}
			}
		}
	}	
    	if(!empty($apiScores)){
		//we found some scores, let's update the DB:
		$dbScores = $DB->get_records('plagiarism_vericite_files', array('cm'=>$cmid), '', 'id, cm, userid, identifier, similarityscore, timeretrieved');
		$sql = "INSERT INTO {plagiarism_vericite_files} (id, cm, userid, identifier, similarityscore, timeretrieved, data, status) VALUES ";
		$executeQuery = false;
		foreach($apiScores as $apiScore){
			foreach($dbScores as $dbScore){
				if($dbScore->cm == $apiScore->cm && $dbScore->userid == $apiScore->userid 
					&& $dbScore->identifier == $apiScore->identifier){
					//we found an existing score in the db, update it
					$apiScore->id = $dbScore->id;
					break;
				}
			}

			if($executeQuery){
				//add a comma since this isn't the first
				$sql .= ",";
			}else{
				//we have at least one update
				$executeQuery = true;
			}

			$id = (empty($apiScore->id)) ? "null" : $apiScore->id;
			$sql .= "(". $id . "," .$apiScore->cm . "," .$apiScore->userid . ",'" .$apiScore->identifier . "'," .$apiScore->similarityscore . "," .$apiScore->timeretrieved . ",'" .$apiScore->data . "'," . $apiScore->status . ")";
		}
		if($executeQuery){
			try{
				//TODO: Create an Oracle version of this query
				$sql .= " ON DUPLICATE KEY UPDATE similarityscore=VALUES(similarityscore), timeretrieved=VALUES(timeretrieved), status=VALUES(status), data=VALUE(data)";
				$DB->execute($sql);
			}catch (Exception $e) {
				//the fancy bulk update query didn't work, so fall back to one update at a time
				foreach($apiScores as $apiScore){
					if (!empty($apiScore->id)) { //update
						$DB->update_record('plagiarism_vericite_files', $apiScore);
				        } else { //insert
		                                $DB->insert_record('plagiarism_vericite_files', $apiScore);
					}
				}
			}
		}

	}

	return $score;
  }



  function plagiarism_vericite_ends_with($str, $test)
  {
    return substr_compare($str, $test, -strlen($test), strlen($test)) === 0;
  }

  function plagiarism_vericite_generate_url($url, $context, $assignment=null, $user=null){
     if(!$this->plagiarism_vericite_ends_with($url, "/")){
            $url .= "/";
     }
     if(isset($context)){
        $url = $url . $context . "/";
	if(isset($assignment)){
              $url .= $assignment . "/";
              if(isset($user)){
                    $url .= $user . "/";
              }
        }
    }
    return $url;
  }

  function plagiarism_vericite_get_css_rank ($score) {
    $rank = "none";
    if ($score >  90) { $rank = "1"; }
    else if ($score >  80) { $rank = "2"; }
    else if ($score >  70) { $rank = "3"; }
    else if ($score >  60) { $rank = "4"; }
    else if ($score >  50) { $rank = "5"; }
    else if ($score >  40) { $rank = "6"; }
    else if ($score >  30) { $rank = "7"; }
    else if ($score >  20) { $rank = "8"; }
    else if ($score >  10) { $rank = "9"; }
    else if ($score >=  0) { $rank = "10"; }

    return "rank$rank";
  }

  function plagiarism_vericite_identifier_prefix($consumer, $cmid, $userid){
	return $consumer . "_" . $cmid . "_" . $userid . "_";
  }
}


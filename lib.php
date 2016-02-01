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
 * @copyright  2015 Longsight, Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die ('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');

define('PLAGIARISM_VERICITE_STATUS_SEND', 0);
define('PLAGIARISM_VERICITE_STATUS_SUCCESS', 1);
define('PLAGIARISM_VERICITE_STATUS_LOCKED', 2);
define('PLAGIARISM_VERICITE_STATUS_FAILED', 3);
define('PLAGIARISM_VERICITE_TOKEN_CACHE_MIN', 20);
define('PLAGIARISM_VERICITE_ACTION_ASSIGNMENTS', "assignments");
define('PLAGIARISM_VERICITE_ACTION_REPORTS_SUBMIT_REQUEST', "reportsSubmitRequest");
define('PLAGIARISM_VERICITE_ACTION_REPORTS_SCORES', "reportsScores");
define('PLAGIARISM_VERICITE_ACTION_REPORTS_URLS', "reportsUrls");
define('PLAGIARISM_VERICITE_REQUEST_ATTEMPTS', 4);


class plagiarism_plugin_vericite extends plagiarism_plugin {

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
            if ($filearea == "feedback_files" || $filearea == "introattachment") {
                return;
            }
        }
        $plagiarismsettings = plagiarism_vericite_get_settings();
        plagiarism_vericite_log("VeriCite: get_links");
        plagiarism_vericite_log(print_r($linkarray, true));
        $vericite = array();
        $vericite['courseid'] = $COURSE->id;
        $vericite['courseTitle'] = $COURSE->fullname;
        $vericite['cmid'] = $linkarray['cmid'];
        $vericite['userid'] = $linkarray['userid'];
        if (!empty($linkarray['assignment']) && !is_number($linkarray['assignment'])) {
            $vericite['assignmentTitle'] = $linkarray['assignment']->name;
        }
        if (!empty($linkarray['content']) && trim($linkarray['content']) != false) {
            $file = array();
            $linkarray['content'] = '<html>' . $linkarray['content'] . '</html>';
            $file['filename'] = "InlineSubmission";
            $file['type'] = "inline";
            $inlinepostfix = "";
            if (isset($plagiarismsettings['vericite_disable_dynamic_inline']) && $plagiarismsettings['vericite_disable_dynamic_inline']) {
                $inlinepostfix = "inline";
            } else {
                $inlinepostfix = sha1($linkarray['content']);
            }

            $file['identifier'] = $this->plagiarism_vericite_identifier_prefix($plagiarismsettings['vericite_accountid'], $linkarray['cmid'], $linkarray['userid']) . $inlinepostfix;
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
        if (empty($vericite['userid']) || !isset($file) || $file['userid'] !== $vericite['userid'] || $file['size'] > 52428800) {
            plagiarism_vericite_log("VeriCite: file isn't set or user id is wrong or file size is too large");
            return "";
        }
        plagiarism_vericite_log("VeriCite: vericite: " . print_r($vericite, true));
        $output = '';
        // Add link/information about this file to $output.
        $results = $this->get_file_results($vericite['cmid'], $vericite['userid'], !empty($linkarray['file']) ? $linkarray['file'] : null, $vericite);
        if (empty($results)) {
            // No results were found.
            return '<br />';
        }

        if (array_key_exists('error', $results)) {
            return $results['error'];
        }
        $rank = $this->plagiarism_vericite_get_css_rank($results['score']);

        $similaritystring = '&nbsp;<span class="' . $rank . '">' . $results['score'] . '%</span>';
        if (!empty($results['reporturl'])) {
            // User gets to see link to similarity report & similarity score
            $output = '<span class="vericite-report"><a href="' . $results['reporturl'] . '" target="_blank">';
            $output .= get_string('similarity', 'plagiarism_vericite').':</a>' . $similaritystring . '</span>';
        } else {
            // User only sees similarity score
            $output = '<span class="vericite-report">' . get_string('similarity', 'plagiarism_vericite') . $similaritystring . '</span>';
        }
        return "<br/>" . $output . "<br/>";
    }


    public function get_file_results($cmid, $userid, $file, $vericite=null) {
        global $DB, $USER, $COURSE, $OUTPUT, $CFG;
        $SCORE_CACHE_MIN = 60;
        $SCORE_FETCH_CACHE_MIN = 5;
        $plagiarismsettings = plagiarism_vericite_get_settings();
        if (empty($plagiarismsettings)) {
            // VeriCite is not enabled
            return false;
        }
        plagiarism_vericite_log("VeriCite: get_file_results: cmid: " . $cmid . ", userId: " . $userid . ", file: " . print_r($file, true)); 
        $plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm' => $vericite['cmid']), '', 'name,value');
        if (empty($plagiarismvalues['use_vericite'])) {
            plagiarism_vericite_log("VeriCite: not in use for this cm");
            // VeriCite not in use for this cm
            return false;
        }

        $modulecontext = context_module::instance($vericite['cmid']);

        // Whether the user has permissions to see all items in the context of this module.
        // This is determined by whether the user is a "course instructor" if they have assignment:grade.

        $gradeassignment = has_capability('mod/assign:grade', $modulecontext);
        $viewsimilarityscore = $gradeassignment;
        $viewfullreport = $gradeassignment;

        if ($USER->id == $vericite['userid']) {
            // The user wants to see details on their own report.
            if ($plagiarismvalues['plagiarism_show_student_score'] == 1) {
                $viewsimilarityscore = true;
            }
            if ($plagiarismvalues['plagiarism_show_student_report'] == 1) {
                $viewfullreport = true;
            }
        }

        if (!$viewsimilarityscore && !$viewfullreport) {
            plagiarism_vericite_log("VeriCite: The user has no right to see the requested detail");
            // The user has no right to see the requested detail.
            return false;
        }

        $results = array(
                'analyzed' =>  0,
                'score' => '',
                'reporturl' => '',
        );

        // First check if we already have looked up the score for this class.
        $fileid = $vericite['file']['identifier'];
        $score = -1;

        $mycontent = null;
        $contentscore = $DB->get_records('plagiarism_vericite_files', array('cm' => $vericite['cmid'], 'userid' => $userid, 'identifier' => $fileid), '', 'id,cm,userid,identifier,similarityscore, timeretrieved, status');
        if (!empty($contentscore)) {
            foreach ($contentscore as $content) {
                $mycontent = $content;
                plagiarism_vericite_log("VeriCite: content: " . print_r($mycontent, true));
                if ($content->status == PLAGIARISM_VERICITE_STATUS_SUCCESS && time() - (60 * $SCORE_CACHE_MIN) < $content->timeretrieved) {
                    // Since our reports are dynamic, only use the db as a cache.
                    // if its too old of a results, don't set the score and just grab a new one from the API.
                    $score = $content->similarityscore;
                    plagiarism_vericite_log("VeriCite: content score: " . $score);
                } else {
                    plagiarism_vericite_log("VeriCite: content was too old: " . $content->timeretrieved);
                }
                break;
            }
        } else {
            plagiarism_vericite_log("VeriCite: no content scores in db");
        }
        // Check to see if we've already looked up the scores just recently.
        $scorecachearray = $DB->get_records('plagiarism_vericite_score', array('cm' => $cmid), '', 'id, cm, timeretrieved');
        $scorecache = array_shift($scorecachearray);
        plagiarism_vericite_log("VeriCite: score cache: " . print_r($scorecache, true));
        if ($score < 0 && (empty($scorecache) || $scorecache->timeretrieved < time() - (60 * $SCORE_FETCH_CACHE_MIN))) {
            // Ok, we couldn't find the score in the cache, try to look it up with the webservice.
            $score = $this->plagiarism_vericite_get_scores($plagiarismsettings, $COURSE->id, $cmid, $fileid, $userid);
            // Update score DB table with the current fetch time.
            if (empty($scorecache)) {
                $scorecacheelement = new object();
                $scorecacheelement->cm = $cmid;
                $scorecacheelement->timeretrieved = time();
                $DB->insert_record('plagiarism_vericite_score', $scorecacheelement);
            } else {
                $scorecache->timeretrieved = time();
                $DB->update_record('plagiarism_vericite_score', $scorecache);
            }
            plagiarism_vericite_log("VeriCite: looked up score in VeriCite: " . $score);
        } else {
            plagiarism_vericite_log("VeriCite: Score lookup throttled, didn't look up score in VeriCite: score: " . $score . " ; now: " . time());
        }

        if ($score < 0 && (empty($mycontent) || $mycontent->status == PLAGIARISM_VERICITE_STATUS_SUCCESS)) {
            plagiarism_vericite_log("VeriCite: can't find the score in the cache, the db, or VeriCite and its not scheduled to be uploaded");
            // Ok can't find the score in the cache, the db, or VeriCite and its not scheduled to be uploaded.
            $user = ($userid == $USER->id ? $USER : $DB->get_record('user', array('id' => $userid)));

            $customdata = array(
                    'courseid' => $COURSE->id,
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
            // Store for cron job to submit the file.
            $update = true;
            if (empty($mycontent)) {
                $newelement = new object();
                $update = false;
            } else {
                $newelement = $mycontent;
            }
            $newelement->cm = $cmid;
            $newelement->timeretrieved = 0;
            $newelement->identifier = $fileid;
            $newelement->userid = $userid;
            $newelement->data = base64_encode(serialize($customdata));
            $newelement->status = PLAGIARISM_VERICITE_STATUS_SEND;
            try {
                if ($update) {
                    $DB->update_record('plagiarism_vericite_files', $newelement);
                } else {
                    $DB->insert_record('plagiarism_vericite_files', $newelement);
                }
            } catch (Exception $e) {
                plagiarism_vericite_log("VeriCite: newelement: " . print_r($newelement, true) . ";user: " . print_r($user, true), $e);
            }
            plagiarism_vericite_log("VeriCite: scheduled file to be uploaded: " . print_r($newelement, true));
        }

        if ($score >= 0) {
            // We have successfully found the score and it has been evaluated.
            $results['analyzed'] = 1;
            $results['score'] = $score;
            if ($viewfullreport) {
                // See if the token already exists.
                $conditions = array('cm' => $vericite['cmid'], 'userid' => $USER->id, 'identifier' => $fileid);
                plagiarism_vericite_log("VeriCite: looking for token: " . print_r($conditions, true));
                $dbtokens = $DB->get_records('plagiarism_vericite_tokens', $conditions);
                foreach ($dbtokens as $dbtoken) {
                    plagiarism_vericite_log("VeriCite: db token: " . print_r($dbtoken, true));
                    // Instructors can have multiple tokens, see if any of them aren't expired.
                    // If it's not an instructor, there should only be one token in the array anyways.
                    if (time() - (60 * PLAGIARISM_VERICITE_TOKEN_CACHE_MIN) < $dbtoken->timeretrieved) {
                        // We found an existing token, set token and break out.
                        $token = $dbtoken->token;
                        break;
                    }
                }

                if (!isset($token)) {
                    // Didn't find it in cache, get a new token.
					$url = plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_REPORTS_URLS, $COURSE->id);
                    $fields = array();
                    // $fields['consumer'] = $plagiarismsettings['vericite_accountid'];
                    // $fields['consumerSecret'] = $plagiarismsettings['vericite_secretkey'];
					$fields['assignmentIDFilter'] = $vericite['cmid'];
					$fields['tokenUser'] = $USER->id;
                    if (!$gradeassignment) {
                        // Non instructors can only see their own items.
                        $fields['externalContentIDFilter'] = $fileid;
                        $fields['userIDFilter'] = $USER->id;
						$fields['tokenUserRole'] = 'Learner';
                        // Also make sure their token requires the context id, assignment id and user id
                        $url = plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], "", $COURSE->id, $vericite['cmid'], $USER->id);
                    } else {
                        // Send over the param for role so that instructors can see more details.
                        $fields['tokenUserRole'] = 'Instructor';
                    }
					//create curl request
					$ch = curl_init();
					// set url
					$url .= "?" . http_build_query($fields);
					curl_setopt($ch, CURLOPT_URL, $url);
					//set timeout in seconds:
					curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 120);
					//we expect a response, so set the flag:
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					//set headers for consumer & secret
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					    'consumer: ' . $plagiarismsettings['vericite_accountid'],
					    'consumerSecret: ' . $plagiarismsettings['vericite_secretkey']
					    ));
					//log headers
					curl_setopt($ch, CURLINFO_HEADER_OUT, true);
					curl_setopt($ch, CURLOPT_HTTPGET, true);
					//execute post
                    plagiarism_vericite_log("VeriCite: requesting token: url: " . $url . " ; fields: " . print_r($fields, true));
					$status = plagiarism_vericite_curl_exec($ch);
                    if (!empty ($status) && isset($status->urls)) {
						plagiarism_vericite_log("status and urls are not empty");
						foreach ($status->urls as $reportUrlLinkResponse) {
							plagiarism_vericite_log("for each url");
							if(isset($reportUrlLinkResponse->externalContentID)){
								plagiarism_vericite_log("id: " . $reportUrlLinkResponse->externalContentID);
								//see if we found the exact token url we are looking for, if so, set it
								if($fileid == $reportUrlLinkResponse->externalContentID){
									$token = $reportUrlLinkResponse->url;
								}
								// Store token in db to use again.
								$id = - 1;
                    			foreach ($dbtokens as $dbtoken) {
                            		if ($dbtoken->identifier == $reportUrlLinkResponse->externalContentID) {
                                		// We found an existing score in the db, update it.
                                		$id = $dbtoken->id;
                            		
										// This is a matched db item from the query above,
										// so we should update the token id and time no matter what.
										$dbtoken->token = $reportUrlLinkResponse->url;
										$dbtoken->timeretrieved = time ();
										$DB->update_record ( 'plagiarism_vericite_tokens', $dbtoken );
									}
								}
								//if we didn't find and update the db for an existing token, go ahead and
								//store a new record in the db for this token url
                        		if ($id < 0) {
                            		// Token doesn't already exist, add it.
									$newelement = new object ();
									$newelement->cm = $reportUrlLinkResponse->assignmentID;
									$newelement->userid = $reportUrlLinkResponse->userID;
									$newelement->identifier = $reportUrlLinkResponse->externalContentID;
									$newelement->timeretrieved = time ();
									$newelement->token = $reportUrlLinkResponse->url;
									$DB->insert_record ('plagiarism_vericite_tokens', $newelement);
								}
							}
						}
                    }
                }

                if (isset($token)) {
                    $results['reporturl'] = $token;
                }
            }
        } else {
            plagiarism_vericite_log("VeriCite: score wasn't found, returning nothing");
            return false;
        }

        plagiarism_vericite_log("VeriCite: results: " . print_r($results, true));
        return $results;
    }

    /* Hook to save plagiarism specific settings on a module settings page.
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;
        if (!plagiarism_vericite_get_settings()) {
            return;
        }

        // Array of posible plagiarism config options.
        $plagiarismelements = $this->config_options();
        // First get existing values.
        if (!isset($data->use_vericite)) {
            $data->use_vericite = 0;
        }

        $existingelements = $DB->get_records_menu('plagiarism_vericite_config', array('cm' => $data->coursemodule), '', 'name,id');
        foreach ($plagiarismelements as $element) {
            $newelement = new object();
            $newelement->cm = $data->coursemodule;
            $newelement->name = $element;
            $newelement->value = (isset($data->$element) ? $data->$element : 0);
            if (isset($existingelements[$element])) {
                $newelement->id = $existingelements[$element];
                $DB->update_record('plagiarism_vericite_config', $newelement);
            } else {
                $DB->insert_record('plagiarism_vericite_config', $newelement);
            }

        }

        // Now save the assignment title and instructrions and files (not a big deal if this fails, so wrap in try catch).
        try {
            $plagiarismsettings = plagiarism_vericite_get_settings();
            if ($plagiarismsettings && !empty($data->use_vericite)) {
				$url = plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_ASSIGNMENTS, $data->course, $data->coursemodule);			
				$fields = array();
                $assignmentData = array();
                $assignmentData['assignmentTitle'] = $data->name;
                $assignmentData['assignmentInstructions'] = $data->intro;
                $assignmentData['assignmentExcludeQuotes'] = !empty($data->plagiarism_exclude_quotes) ? true : false;
				if(isset($data->duedate)){
					$assignmentData['assignmentDueDate'] = $data->duedate * 1000; //VeriCite expects the time to be in milliseconds
				}
				//TODO: attachments (introattachments)

				$fields['assignmentData'] = $assignmentData;
				$fields_json = json_encode($fields);
				//create curl request
				$ch = curl_init();
				// set url
				curl_setopt($ch, CURLOPT_URL, $url);
				//set timeout in seconds:
				curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 120);
				//we expect a response, so set the flag:
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				//set headers for json, consumer & secret
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
				    'consumer: ' . $plagiarismsettings['vericite_accountid'],
				    'consumerSecret: ' . $plagiarismsettings['vericite_secretkey']
				    ));
				//log headers
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				//				
				curl_setopt($ch,CURLOPT_POST, true);
				curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_json);
				plagiarism_vericite_log("Assignment update: url: \n" . $url . "\nfields: \n" . $fields_json);
				//execute post
				$resultJson = plagiarism_vericite_curl_exec($ch);      
            }
        } catch (Exception $e) {
            plagiarism_vericite_log("Attempted to save the assignment title and instructions.", $e);
        }
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context, $modulename = '') {
        global $CFG, $DB;
        $plagiarismsettings = plagiarism_vericite_get_settings();
        if (!$plagiarismsettings) {
            return;
        }

        $cmid = optional_param('update', 0, PARAM_INT);
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm' => $cmid), '', 'name,value');
        }
        $plagiarismelements = $this->config_options();
        // Add form.
        $mform->addElement('header', 'plagiarismdesc', get_string('pluginname', 'plagiarism_vericite'));
        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('checkbox', 'use_vericite', get_string("usevericite", "plagiarism_vericite"));
        if (isset($plagiarismvalues['use_vericite'])) {
            $mform->setDefault('use_vericite', $plagiarismvalues['use_vericite']);
        } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_use_default'])) {
            $mform->setDefault('use_vericite', $plagiarismsettings['vericite_use_default']);
        }
        $mform->addElement('checkbox', 'plagiarism_show_student_score', get_string("studentscorevericite", "plagiarism_vericite"));
        $mform->addHelpButton('plagiarism_show_student_score', 'studentscorevericite', 'plagiarism_vericite');
        $mform->disabledIf('plagiarism_show_student_score', 'use_vericite');
        $mform->setDefault('plagiarism_show_student_score', true);
        // Only show DB saved setting if use_vericite is enabled, otherwise, only show defaults.
        if (!empty($plagiarismvalues['use_vericite']) && isset($plagiarismvalues['plagiarism_show_student_score'])) {
            $mform->setDefault('plagiarism_show_student_score', $plagiarismvalues['plagiarism_show_student_score']);
        } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_student_score_default'])) {
            $mform->setDefault('plagiarism_show_student_score', $plagiarismsettings['vericite_student_score_default']);
        } else if (strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_student_score_default_forums'])) {
            $mform->setDefault('plagiarism_show_student_score', $plagiarismsettings['vericite_student_score_default_forums']);
        }
        $mform->addElement('checkbox', 'plagiarism_show_student_report', get_string("studentreportvericite", "plagiarism_vericite"));
        $mform->addHelpButton('plagiarism_show_student_report', 'studentreportvericite', 'plagiarism_vericite');
        $mform->disabledIf('plagiarism_show_student_report', 'use_vericite');
        $mform->setDefault('plagiarism_show_student_report', true);
        // Only show DB saved setting if use_vericite is enabled, otherwise, only show defaults.
        if (!empty($plagiarismvalues['use_vericite']) && isset($plagiarismvalues['plagiarism_show_student_report'])) {
            $mform->setDefault('plagiarism_show_student_report', $plagiarismvalues['plagiarism_show_student_report']);
        } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_student_score_default'])) {
            $mform->setDefault('plagiarism_show_student_report', $plagiarismsettings['vericite_student_report_default']);
        } else if (strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_student_score_default_forums'])) {
            $mform->setDefault('plagiarism_show_student_report', $plagiarismsettings['vericite_student_report_default_forums']);
        }

        $mform->addElement('checkbox', 'plagiarism_exclude_quotes', get_string("excludequotesvericite", "plagiarism_vericite"));
        $mform->addHelpButton('plagiarism_exclude_quotes', 'excludequotesassignment', 'plagiarism_vericite');
        $mform->disabledIf('plagiarism_exclude_quotes', 'use_vericite');
        $mform->setDefault('vericite_student_score_default', true);

        // Only show DB saved setting if use_vericite is enabled, otherwise, only show defaults.
        if (!empty($plagiarismvalues['use_vericite']) && isset($plagiarismvalues['plagiarism_exclude_quotes'])) {
            $mform->setDefault('plagiarism_exclude_quotes', $plagiarismvalues['plagiarism_exclude_quotes']);
        } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_exclude_quotes_default'])) {
            $mform->setDefault('plagiarism_exclude_quotes', $plagiarismsettings['vericite_exclude_quotes_default']);
        } else if (strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_exclude_quotes_default_forums'])) {
            $mform->setDefault('plagiarism_exclude_quotes', $plagiarismsettings['vericite_exclude_quotes_default_forums']);
        }
    }

    public function config_options() {
        return array('use_vericite', 'plagiarism_show_student_score', 'plagiarism_show_student_report', 'plagiarism_exclude_quotes');
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT, $DB;
        $output = '';
        if (!empty($cmid)) {
            $plagiarismsettings = (array)get_config('plagiarism');
            $disclosure = $plagiarismsettings['vericite_student_disclosure'];
            if (!empty($disclosure)) {
                $plagiarismvalues = $DB->get_records_menu('plagiarism_vericite_config', array('cm' => $cmid), '', 'name,value');
                if (!empty($plagiarismvalues) && $plagiarismvalues['use_vericite']) {
                    $contents = format_text($disclosure, FORMAT_MOODLE, array("noclean" => true));
                    $output = $OUTPUT->box($contents, 'generalbox boxaligncenter', 'intro');
                }
            }
        }
        return $output;
    }

    /**
     * Hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        // Called at top of submissions/grading pages - allows printing of admin style links or updating status
    }
	
	/**
	 * called by admin/cron.php 
	 *
	 */
	public function cron() {
	}

    function plagiarism_vericite_get_scores($plagiarismsettings, $courseid, $cmid, $fileid, $userid) {
        global $DB;
        $score = -1;
        $url = plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_REPORTS_SCORES, $courseid);
		$url .= "?assignmentId=" . $cmid;	
		//create curl request
		$ch = curl_init();
		// set url
		curl_setopt($ch, CURLOPT_URL, $url);
		//set timeout in seconds:
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 120);
		//we expect a response, so set the flag:
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//set headers for consumer & secret
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'consumer: ' . $plagiarismsettings['vericite_accountid'],
		    'consumerSecret: ' . $plagiarismsettings['vericite_secretkey']
		    ));
		//log headers
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		//execute post
        plagiarism_vericite_log("VeriCite: looking up scores in VeriCite: url: " . $url);
		$scores = plagiarism_vericite_curl_exec($ch); 
        // Store results in the cache and set $score if you find the appropriate file score.
        $apiscores = array();
        if (!empty($scores) && isset($scores->scores)) {
            foreach ($scores->scores as $resultuserid => $resultuserscores) {
                foreach ($resultuserscores as $resultcmid => $resultcmidscores) {
                    foreach ($resultcmidscores as $resultcontentid => $resultcontentscore) {
                        $newelement = new object();
                        $newelement->cm = $resultcmid;
                        $newelement->userid = $resultuserid;
                        $newelement->identifier = $resultcontentid;
                        $newelement->similarityscore = $resultcontentscore;
                        $newelement->timeretrieved = time();
                        $newelement->status = PLAGIARISM_VERICITE_STATUS_SUCCESS;
                        $newelement->data = '';
                        $apiscores = array_merge($apiscores, array($newelement));
                        if ($resultcontentid == $fileid && $resultuserid == $userid) {
                            // We found this file's score, so set it.
                            $score = $resultcontentscore;
                        }
                    }
                }
            }
        }

        if (!empty($apiscores)) {
            // We found some scores, let's update the DB.
            $dbscores = $DB->get_records('plagiarism_vericite_files', array('cm' => $cmid), '', 'id, cm, userid, identifier, similarityscore, timeretrieved');
            $sql = "INSERT INTO {plagiarism_vericite_files} (id, cm, userid, identifier, similarityscore, timeretrieved, data, status) VALUES ";
            $executequery = false;
            foreach ($apiscores as $apiscore) {
                foreach ($dbscores as $dbscore) {
                    if ($dbscore->cm == $apiscore->cm && $dbscore->userid == $apiscore->userid && $dbscore->identifier == $apiscore->identifier) {
                        // We found an existing score in the db, update it.
                        $apiscore->id = $dbscore->id;
                        break;
                    }
                }

                if ($executequery) {
                    // Add a comma since this isn't the first.
                    $sql .= ",";
                } else {
                    // We have at least one update.
                    $executequery = true;
                }

                $id = (empty($apiscore->id)) ? "null" : $apiscore->id;
                $sql .= "(". $id . "," .$apiscore->cm . "," .$apiscore->userid . ",'" .$apiscore->identifier . "'," .$apiscore->similarityscore . "," .$apiscore->timeretrieved . ",'" .$apiscore->data . "'," . $apiscore->status . ")";
            }
            if ($executequery) {
                try {
                    // TODO: Create an Oracle version of this query.
                    $sql .= " ON DUPLICATE KEY UPDATE similarityscore=VALUES(similarityscore), timeretrieved=VALUES(timeretrieved), status=VALUES(status), data=VALUE(data)";
                    $DB->execute($sql);
                } catch (Exception $e) {
                    // The fancy bulk update query didn't work, so fall back to one update at a time.
                    foreach ($apiscores as $apiscore) {
                        if (!empty($apiscore->id)) {
                            $DB->update_record('plagiarism_vericite_files', $apiscore);
                        } else {
                            $DB->insert_record('plagiarism_vericite_files', $apiscore);
                        }
                    }
                }
            }

        }

        return $score;
    }

    private function plagiarism_vericite_get_css_rank ($score) {
        $rank = ceil((100 - (int)$score) / 10);
        return 'vericite-rank-' . $rank;
    }

    private function plagiarism_vericite_identifier_prefix($consumer, $cmid, $userid) {
        return $consumer . '_' . $cmid . '_' . $userid . '_';
    }

}

function plagiarism_vericite_log($logstring, $e=null) {
    if (!empty($plagiarismsettings['vericite_enable_debugging']) && $plagiarismsettings['vericite_enable_debugging']) {
        global $CFG;
        $logfile = $CFG->dataroot.'/vericite.log';
        if (!$fp = fopen($logfile, 'a')) {
            return;
        }
        fwrite($fp, date('c').' - '.$logstring."\n");
        // See if there is an exception to report on.
        if (isset($e) && is_callable(array($e, 'getMessage'))) {
            fwrite($fp, $e->getMessage() . " \n");
        }
        fclose($fp);
    }
}

function plagiarism_vericite_get_settings() {
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

function plagiarism_vericite_ends_with($str, $test) {
    return substr_compare($str, $test, -strlen($test), strlen($test)) === 0;
}

function plagiarism_vericite_generate_url($url, $action, $context, $assignment=null, $user=null) {
    if (!plagiarism_vericite_ends_with($url, '/')) {
        $url .= '/';
    }
	if(strcmp(PLAGIARISM_VERICITE_ACTION_ASSIGNMENTS, $action) == 0){
		$url .= "assignments/";
	}else if(strcmp(PLAGIARISM_VERICITE_ACTION_REPORTS_SUBMIT_REQUEST, $action) == 0){
		$url .= "reports/submit/request/";
	}else if(strcmp(PLAGIARISM_VERICITE_ACTION_REPORTS_SCORES, $action) == 0){
		$url .= "reports/scores/";
	}else if(strcmp(PLAGIARISM_VERICITE_ACTION_REPORTS_URLS, $action) == 0){
		$url .= "reports/urls/";
	}
		
    if (isset($context)) {
        $url = $url . $context . '/';
        if (isset($assignment)) {
            $url .= $assignment . '/';
            if (isset($user)) {
                $url .= $user . '/';
            }
        }
    }
    return $url;
}

function plagiarism_vericite_serialize_fields($fields){
	$fields_string = '';
	foreach($fields as $key=>$value) {
		if (is_array($value)){
			$fields_string .= $key .'=' . json_encode($value) . '&';
		}else{
			$fields_string .= $key .'=' . $value . '&';
		}
	}
	$fields_string = rtrim($fields_string, '&');
	return $fields_string;
}

function plagiarism_vericite_curl_exec($ch){
	$result = null;
	$attempts = 0;
	$success = false;
	do{
		//execute post
		$result = curl_exec($ch);
		$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		plagiarism_vericite_log(curl_getinfo($ch, CURLINFO_HEADER_OUT));
		plagiarism_vericite_log("attempts: " . $attempts);
		plagiarism_vericite_log("response code: " . $responseCode);
       	plagiarism_vericite_log("result:\n" . $result);
		if($result === FALSE || $responseCode != 200){
			//make sure to return null so that the caller knows it failed
			$result = null;
			$success = false;
		}else{
			//a request could time out, check for a result message error
			if(!empty($result) && substr($result, 0, 1) === "{"){
				$result = json_decode($result);
				if(isset($result->message) && strpos($result->message, "timed out") !== FALSE){
					//The request endpoint timed out, let's call again
					plagiarism_vericite_log("timed out");
					$success = false;
					$result = null;
				}else{
					plagiarism_vericite_log("success 1");
					$success = true;
					//make sure we return a non-empty result to show success
					if(empty($result)){
						$result = 1;
					}
				}
			}else{
				//no message is a good message :)
				plagiarism_vericite_log("success 2");
				$success = true;
				//make sure we return a non-empty result to show success
				if(empty($result)){
					$result = 1;
				}
			}
		}
		$attempts++;
	}while(!$success && $attempts < PLAGIARISM_VERICITE_REQUEST_ATTEMPTS);
	//close connection
	curl_close($ch);
	return $result;
}

	

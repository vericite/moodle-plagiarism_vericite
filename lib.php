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
 * @since      2.0
 * @package    plagiarism_vericite
 * @subpackage plagiarism
 * @copyright  2015 Longsight, Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once $CFG->dirroot.'/plagiarism/lib.php';
require_once $CFG->dirroot.'/plagiarism/vericite/sdk/autoload.php';

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
define('PLAGIARISM_VERICITE_SCORE_CACHE_MIN', 30); //after PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN of submission, use this cache time
define('PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN', 60); //do not consult the cache until X minutes after the initial submission
define('PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN_RECHECK', 3); //within PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN of submission, use this cache time
define('PLAGIARISM_VERICITE_SCORE_CACHE_PRELIMINARY_IGNORE_MIN', 5);  //preliminary scores to be rechecked soon
define('PLAGIARISM_VERICITE_API_VERSION', "v1");


class plagiarism_plugin_vericite extends plagiarism_plugin
{

    /**
     * hook to allow plagiarism specific information to be displayed beside a submission
     *
     * @param  array $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     */
    public function get_links($linkarray) 
    {
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
            $file['filename'] = "InlineSubmission.html";
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

        $similaritystring = '&nbsp;<span class="' . format_string($rank) . '">' . format_string($results['score']) . '%</span>';

        if (!($results['isPreliminary'] == 1 && $results['viewPreliminaryReport'] == 0)) {
            //Show Report and/or Score if allowed.
            if (!empty($results['reporturl'])) {
                // User gets to see link to similarity report & similarity score
                if (($results['viewFullReport'] && $results['viewSimilarityScore']) || $results['isInstructor']) {
                    $output = '<span class="vericite-report"><a href="' . format_string($results['reporturl']) . '" target="_blank">';
                    $output .= get_string('similarity', 'plagiarism_vericite').':</a>' . $similaritystring . '</span>';
                } else if ($results['viewFullReport'] && !$results['viewSimilarityScore']) {
                    $output = '<span class="vericite-report"><a href="' . format_string($results['reporturl']) . '" target="_blank">';
                    $output .= get_string('similarity', 'plagiarism_vericite').'</a></span>';
                } else if (!$results['viewFullReport'] && $results['viewSimilarityScore']) {
                    $output = '<span class="vericite-report">' . get_string('similarity', 'plagiarism_vericite').':' . $similaritystring . '</span>';
                } else {
                    //Not able to view report or score...
                }
            } else if ($results['viewSimilarityScore'] || $results['isInstructor']) {
                $output = '<span class="vericite-report">' . get_string('similarity', 'plagiarism_vericite') . $similaritystring . '</span>';
            }
        }

        return "<br/>" . $output . "<br/>";
    }


    public function get_file_results($cmid, $userid, $file, $vericite=null) 
    {
        global $DB, $USER, $COURSE, $OUTPUT, $CFG;
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

        // Whether the user has permissions to see all items in the context of this module.
        // This is determined by whether the user is a "course instructor" if they have assignment:grade.
        $modulecontext = context_module::instance($vericite['cmid']);
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

        $viewPreliminaryReport = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_preliminary_report', true);

        $results = array(
                'analyzed' =>  0,
                'score' => '',
                'reporturl' => '',
                'viewSimilarityScore' => $viewsimilarityscore,
                'viewFullReport' => $viewfullreport,
                'isInstructor' => $gradeassignment,
                'viewPreliminaryReport' => $viewPreliminaryReport,
        );


        // First check if we already have looked up the score for this class.
        $fileid = $vericite['file']['identifier'];
        $score = -1;
        //do not consult the cache if the timesubmitted is less than PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN
        $cacheTime = 60 * PLAGIARISM_VERICITE_SCORE_CACHE_MIN;
        $mycontent = null;
        $contentscore = $DB->get_records('plagiarism_vericite_files', array('cm' => $vericite['cmid'], 'userid' => $userid, 'identifier' => $fileid), '', 'id,cm,userid,identifier,similarityscore,preliminary,timeretrieved, status, timesubmitted');
        if (!empty($contentscore)) {
            //there should only be 1 result
            foreach ($contentscore as $content) {
                $mycontent = $content;
                if (isset($mycontent->timesubmitted) && (time() - $mycontent->timesubmitted) < (60 * PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN)) {
                             //don't consult the cache until at least PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN after submission
                             plagiarism_vericite_log("don't consult cache");
                             //recheck after PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN_RECHECK minutes instead of the normal cache time
                             $cacheTime = 60 * PLAGIARISM_VERICITE_SCORE_CACHE_IGNORE_MIN_RECHECK;
                }

                $results['isPreliminary'] = $mycontent->preliminary;

                if ($mycontent->preliminary==1) {
                    $cacheTime = 60 * PLAGIARISM_VERICITE_SCORE_CACHE_PRELIMINARY_IGNORE_MIN;
                }


                plagiarism_vericite_log("cacheTime: " . $cacheTime . " , " . time() . " - " . $mycontent->timesubmitted . " = " . (time() - $mycontent->timesubmitted));
                plagiarism_vericite_log("VeriCite: content: " . print_r($mycontent, true));
                if ($content->status == PLAGIARISM_VERICITE_STATUS_SUCCESS && (time() - $cacheTime) < $content->timeretrieved) {
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
        if ($score < 0) {
               // Check to see if we've already looked up the scores just recently.
               $scorecachearray = $DB->get_records('plagiarism_vericite_score', array('cm' => $cmid), '', 'id, cm, timeretrieved');
               $scorecache = array_shift($scorecachearray);
               plagiarism_vericite_log("VeriCite: score cache: " . print_r($scorecache, true));

            if (empty($scorecache) || $scorecache->timeretrieved < time() - $cacheTime) {
                // We couldn't find the score in the cache, try to look it up with the webservice.
                $scoreElement = $this->plagiarism_vericite_get_scores($plagiarismsettings, $COURSE->id, $cmid, $fileid, $userid);
                if ($scoreElement != null) {
                    $score = $scoreElement->similarityscore;
                    $results['isPreliminary'] = $scoreElement->preliminary;
                }
                // Update score DB table with the current fetch time.
                if (empty($scorecache)) {
                    $scorecacheelement = new StdClass();
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
        }

        if ($score < 0 && (empty($mycontent) || $mycontent->status == PLAGIARISM_VERICITE_STATUS_SUCCESS)) {
            plagiarism_vericite_log("VeriCite: can't find the score in the cache, the db, or VeriCite and it is not scheduled to be uploaded");
            // Ok can't find the score in the cache, the db, or VeriCite and it is not scheduled to be uploaded.
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
                $newelement = new StdClass();
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
                $conditions = array('cm' => $vericite['cmid'], 'userid' => $vericite['userid'], 'tokenuser' => $USER->id, 'identifier' => $fileid);
                plagiarism_vericite_log("VeriCite: looking for token: " . print_r($conditions, true));
                $dbtokens = $DB->get_records('plagiarism_vericite_tokens', $conditions);
                foreach ($dbtokens as $dbtoken) {
                    plagiarism_vericite_log("VeriCite: db token: " . print_r($dbtoken, true));
                    if (time() - (60 * PLAGIARISM_VERICITE_TOKEN_CACHE_MIN) < $dbtoken->timeretrieved) {
                        // We found an existing token, set token and break out.
                        $token = $dbtoken->token;
                        break;
                    }
                }

                if (!isset($token)) {
                    $apiArgs = array();
                    $apiArgs['context_id'] = $COURSE->id;
                    $apiArgs['assignment_id_filter'] = $vericite['cmid'];
                    $apiArgs['consumer'] = $plagiarismsettings['vericite_accountid'];
                    $apiArgs['consumer_secret'] = $plagiarismsettings['vericite_secretkey'];
                    $apiArgs['token_user'] = $USER->id;
                    if (!$gradeassignment) {
                        // Non instructors can only see their own items.
                        $apiArgs['external_content_id_filter'] = $fileid;
                        $apiArgs['user_id_filter'] = $USER->id;
                        $apiArgs['token_user_role'] = 'Learner';
                    } else {
                        // Send over the param for role so that instructors can see more details.
                        $apiArgs['external_content_id_filter'] = null;
                        $apiArgs['user_id_filter'] = null;
                        $apiArgs['token_user_role'] = 'Instructor';
                    }

                    $urls = plagiarism_vericite_call_api($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_REPORTS_URLS, $apiArgs);
                    if (is_array($urls) && count($urls) > 0) {
                        foreach ($urls as $report_url_link_response) {
                            $report_url_link_url = $report_url_link_response->getUrl();
                            $report_url_link_identifier = $report_url_link_response->getExternalContentId();

                            // We have an incomplete report response.
                            if (empty($report_url_link_url) || empty($report_url_link_identifier)) {
                                plagiarism_vericite_log("VeriCite: unexpectedly found a response missing data: " . print_r($report_url_link_response, true));
                                continue;
                            }

                            plagiarism_vericite_log("for each url:\ngetExternalContentId:\n" . $report_url_link_identifier . "\ngetUrl:\n" . $report_url_link_url);
                            // See if we found the exact token url we are looking for, if so, set it
                            if ($fileid == $report_url_link_identifier) {
                                $token = $report_url_link_url;
                            }
                            // Store token in db to use again.
                            $id = - 1;
                            foreach ($dbtokens as $dbtoken) {
                                if ($dbtoken->identifier == $report_url_link_identifier) {
                                    // We found an existing score in the db, update it.
                                    $id = $dbtoken->id;

                                    // This is a matched db item from the query above,
                                    // so we should update the token id and time no matter what.
                                    $dbtoken->token = $report_url_link_url;
                                    $dbtoken->timeretrieved = time();
                                    $DB->update_record('plagiarism_vericite_tokens', $dbtoken);
                                }
                            }
                            // If we didn't find and update the db for an existing token, go ahead and
                            // store a new record in the db for this token url.
                            if ($id < 0) {
                                // Token doesn't already exist, add it.
                                $newelement = new StdClass();
                                $newelement->cm = $report_url_link_response->getAssignmentId();
                                $newelement->userid = $report_url_link_response->getUserId();
                                $newelement->identifier = $report_url_link_identifier;
                                $newelement->timeretrieved = time();
                                $newelement->token = $report_url_link_url;
                                $newelement->tokenuser = $USER->id;
                                $DB->insert_record('plagiarism_vericite_tokens', $newelement);
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
    public function save_form_elements($data) 
    {
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

        $plagiarismsettings = plagiarism_vericite_get_settings();
        if ($plagiarismsettings && !empty($data->use_vericite)) {

            // Set values for settings that were hidden from instructor, but set in plugin settings
            if (strcmp("assign", $data->modulename) == 0) {
                if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default_hideinstructor', false) == 1) {
                    if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default', true) == 1) {
                        $data->plagiarism_exclude_quotes = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default', true);
                    } else {
                        unset($data->plagiarism_exclude_quotes);
                    }
                }

                if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default_hideinstructor', false) == 1) {
                    if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default', true) == 1) {
                        $data->plagiarism_exclude_self_plag = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default', true);
                    } else {
                        unset($data->plagiarism_exclude_self_plag);
                    }
                }

                if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default_hideinstructor', false) == 1) {
                    if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default', true) == 1) {
                        $data->plagiarism_store_inst_index = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default', true);
                    } else {
                        unset($data->plagiarism_store_inst_index);
                    }
                }

            } else if (strcmp("forum", $data->modulename) == 0) {
                if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default_forums_hideinstructor', false) == 1) {
                    if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default_forums', true) == 1) {
                        $data->plagiarism_exclude_quotes = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default_forums', true);
                    } else {
                        unset($data->plagiarism_exclude_quotes);
                    }
                }

                if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default_forums_hideinstructor', false) == 1) {
                    if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default_forums', true) == 1) {
                        $data->plagiarism_exclude_self_plag = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default_forums', true);
                    } else {
                        unset($data->plagiarism_exclude_self_plag);
                    }
                }

                if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default_forums_hideinstructor', false) == 1) {
                    if (plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default_forums', true) == 1) {
                        $data->plagiarism_store_inst_index = plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default_forums', true);
                    } else {
                        unset($data->plagiarism_store_inst_index);
                    }
                }
            }
        }

        $existingelements = $DB->get_records_menu('plagiarism_vericite_config', array('cm' => $data->coursemodule), '', 'name,id');
        foreach ($plagiarismelements as $element) {
            $newelement = new StdClass();
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
                $apiArgs = array();
                $apiArgs['context_id'] = $data->course;
                $apiArgs['assignment_id'] = $data->coursemodule;
                $apiArgs['consumer'] = $plagiarismsettings['vericite_accountid'];
                $apiArgs['consumer_secret'] = $plagiarismsettings['vericite_secretkey'];
                $assignmentinfo = array();
                $assignmentinfo['assignment_title'] = $data->name;
                $assignmentinfo['assignment_instructions'] = $data->intro;

                $assignmentinfo['assignment_exclude_quotes'] = (!empty($data->plagiarism_exclude_quotes) && $data->plagiarism_exclude_quotes == 1) ? true : false;
                $assignmentinfo['assignment_exclude_self_plag'] = (!empty($data->plagiarism_exclude_self_plag) && $data->plagiarism_exclude_self_plag == 1) ? true : false;
                $assignmentinfo['assignment_store_in_index'] = (!empty($data->plagiarism_store_inst_index) && $data->plagiarism_store_inst_index == 1) ? true : false;

                $assignmentinfo['assignment_due_date'] = isset($data->duedate) ? $data->duedate * 1000 : '';  //VeriCite expects the time to be in milliseconds
                // Pass in 0 to delete a grade, otherwise, set the grade.
                $assignmentinfo['assignment_grade'] = !empty($data->grade) ? $data->grade : 0;
                //TODO: attachments (introattachments)
                // $assignmentinfo['assignment_attachment_external_content'] = new \Swagger\Client\Model\ExternalContentData(array('file_name' => 'myfilename', 'upload_content_type' => 'uploadtype', 'upload_content_length' => 123456, 'external_content_id' => 44444));
                $assignmentinfo['assignment_attachment_external_content'] = null;
                $assignmentdata = new \Swagger\Client\Model\AssignmentData($assignmentinfo);
                plagiarism_vericite_log("assignment info: " . serialize($assignmentinfo));
                $apiArgs['assignment_data'] = $assignmentdata;
                plagiarism_vericite_call_api($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_ASSIGNMENTS, $apiArgs);
            }
        } catch (Exception $e) {
            plagiarism_vericite_log("Attempted to save the assignment title and instructions.", $e);
        }
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     *
     * @param object $mform   - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context, $modulename = '') 
    {
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

        // Exclude Quotes
        if (!((strcmp("mod_assign", $modulename) == 0 && plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default_hideinstructor', false) == 1) 
            || (strcmp("mod_forum", $modulename) == 0  && plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_quotes_default_forums_hideinstructor', false) == 1)            )
        ) {
            $mform->addElement('checkbox', 'plagiarism_exclude_quotes', get_string("excludequotesvericite", "plagiarism_vericite"));
            $mform->addHelpButton('plagiarism_exclude_quotes', 'excludequotesvericite', 'plagiarism_vericite');
            $mform->disabledIf('plagiarism_exclude_quotes', 'use_vericite');
            // Only show DB saved setting if use_vericite is enabled, otherwise, only show defaults.
            if (!empty($plagiarismvalues['use_vericite']) && isset($plagiarismvalues['plagiarism_exclude_quotes'])) {
                $mform->setDefault('plagiarism_exclude_quotes', $plagiarismvalues['plagiarism_exclude_quotes']);
            } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_exclude_quotes_default'])) {
                $mform->setDefault('plagiarism_exclude_quotes', $plagiarismsettings['vericite_exclude_quotes_default']);
            } else if (strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_exclude_quotes_default_forums'])) {
                $mform->setDefault('plagiarism_exclude_quotes', $plagiarismsettings['vericite_exclude_quotes_default_forums']);
            }
        }

        // Exclude Self Plag
        if (!((strcmp("mod_assign", $modulename) == 0 && plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default_hideinstructor', false) == 1) 
            || (strcmp("mod_forum", $modulename) == 0  && plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_exclude_self_plag_default_forums_hideinstructor', false) == 1)            )
        ) {
            $mform->addElement('checkbox', 'plagiarism_exclude_self_plag', get_string("excludeselfplagvericite", "plagiarism_vericite"));
            $mform->addHelpButton('plagiarism_exclude_self_plag', 'excludeselfplagvericite', 'plagiarism_vericite');
            $mform->disabledIf('plagiarism_exclude_self_plag', 'use_vericite');
            // Only show DB saved setting if use_vericite is enabled, otherwise, only show defaults.
            if (!empty($plagiarismvalues['use_vericite']) && isset($plagiarismvalues['plagiarism_exclude_self_plag'])) {
                $mform->setDefault('plagiarism_exclude_self_plag', $plagiarismvalues['plagiarism_exclude_self_plag']);
            } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_exclude_self_plag_default'])) {
                $mform->setDefault('plagiarism_exclude_self_plag', $plagiarismsettings['vericite_exclude_self_plag_default']);
            } else if (strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_exclude_self_plag_default_forums'])) {
                $mform->setDefault('plagiarism_exclude_self_plag', $plagiarismsettings['vericite_exclude_self_plag_default_forums']);
            }
        }

        // Store in Inst Index
        if (!((strcmp("mod_assign", $modulename) == 0 && plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default_hideinstructor', false) == 1) 
            || (strcmp("mod_forum", $modulename) == 0  && plagiarism_vericite_get_setting_boolean($plagiarismsettings, 'vericite_store_inst_index_default_forums_hideinstructor', false) == 1)            )
        ) {
            $mform->addElement('checkbox', 'plagiarism_store_inst_index', get_string("storeinstindexvericite", "plagiarism_vericite"));
            $mform->addHelpButton('plagiarism_store_inst_index', 'storeinstindexvericite', 'plagiarism_vericite');
            $mform->disabledIf('plagiarism_store_inst_index', 'use_vericite');
            // Only show DB saved setting if use_vericite is enabled, otherwise, only show defaults.
            if (!empty($plagiarismvalues['use_vericite']) && isset($plagiarismvalues['plagiarism_store_inst_index'])) {
                $mform->setDefault('plagiarism_store_inst_index', $plagiarismvalues['plagiarism_store_inst_index']);
            } else if (strcmp("mod_forum", $modulename) != 0 && isset($plagiarismsettings['vericite_store_inst_index_default'])) {
                $mform->setDefault('plagiarism_store_inst_index', $plagiarismsettings['vericite_store_inst_index_default']);
            } else if (strcmp("mod_forum", $modulename) == 0 && isset($plagiarismsettings['vericite_store_inst_index_default_forums'])) {
                $mform->setDefault('plagiarism_store_inst_index', $plagiarismsettings['vericite_store_inst_index_default_forums']);
            }

            $mform->setDefault('vericite_student_score_default', true);
        }

    }

    public function config_options() 
    {
        return array('use_vericite', 'plagiarism_show_student_score', 'plagiarism_show_student_report', 'plagiarism_exclude_quotes', 'plagiarism_exclude_self_plag', 'plagiarism_store_inst_index');
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission
     *
     * @param  int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) 
    {
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
     * @param object $cm     - full cm object
     */
    public function update_status($course, $cm) 
    {
        // Called at top of submissions/grading pages - allows printing of admin style links or updating status
    }

    /**
     * called by admin/cron.php
     */
    public function cron() 
    {
    }

    function plagiarism_vericite_get_scores($plagiarismsettings, $courseid, $cmid, $fileid, $userid) 
    {
        global $DB;
        $scoreElement = null;
        $apiArgs = array();
        $apiArgs['context_id'] = $courseid;
        $apiArgs['consumer'] = $plagiarismsettings['vericite_accountid'];
        $apiArgs['consumer_secret'] = $plagiarismsettings['vericite_secretkey'];
        $apiArgs['assignment_id'] = $cmid;
        $apiArgs['user_id'] = null;
        $apiArgs['external_content_id'] = null;
        $scores = plagiarism_vericite_call_api($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_REPORTS_SCORES, $apiArgs);
        // Store results in the cache and set $score if you find the appropriate file score.
        $apiscores = array();
        if (is_array($scores) && count($scores) > 0) {
            foreach ($scores AS $reportscoreresponse) {
                $rsrassignment = $reportscoreresponse->getAssignment();
                $rsruser = $reportscoreresponse->getUser();
                $rsrcontentid = $reportscoreresponse->getExternalContentId();
                $rsrscore = $reportscoreresponse->getScore();

                // Prior to PHP 5.5, empty() only supports variables; anything else will result in a parse error.
                if(!empty($rsrassignment) && !empty($rsruser) && !empty($rsrcontentid) && isset($rsrscore)) {
                  plagiarism_vericite_log("scores for each\ngetAssignment: " . $rsrassignment . "\ngetUser: " . $rsruser . "\ngetExternalContentId: " . $rsrcontentid . "\ngetScore: " . $rsrscore);
                  $newelement = new StdClass();
                  $newelement->cm = $rsrassignment;
                  $newelement->userid = $rsruser;
                  $newelement->identifier = $rsrcontentid;
                  $newelement->similarityscore = $rsrscore;
                  $newelement->preliminary = $reportscoreresponse->getPreliminary();
                  $newelement->timeretrieved = time();
                  $newelement->status = PLAGIARISM_VERICITE_STATUS_SUCCESS;
                  $newelement->data = '';
                  $apiscores = array_merge($apiscores, array($newelement));
                  if ($newelement->identifier == $fileid && $newelement->userid == $userid) {
                      // We found this file's score, so set it.
                      plagiarism_vericite_log("score found");
                      $scoreElement = $newelement;
                  }
                }
            }
        }
        if (count($apiscores) > 0) {
            // We found some scores, let's update the DB.
            $dbscores = $DB->get_records('plagiarism_vericite_files', array('cm' => $cmid), '', 'id, cm, userid, identifier, similarityscore, preliminary, timeretrieved');
            foreach ($apiscores as $apiscore) {
                foreach ($dbscores as $dbscore) {
                    if ($dbscore->cm == $apiscore->cm && $dbscore->userid == $apiscore->userid && $dbscore->identifier == $apiscore->identifier) {
                        // We found an existing score in the db, update it.
                        $apiscore->id = $dbscore->id;
                        break;
                    }
                }
                if (!empty($apiscore->id)) {
                    $DB->update_record('plagiarism_vericite_files', $apiscore);
                } else {
                    $DB->insert_record('plagiarism_vericite_files', $apiscore);
                }
            }
        }

        return $scoreElement;
    }

    private function plagiarism_vericite_get_css_rank($score) 
    {
        $rank = ceil((100 - (int)$score) / 10);
        return 'vericite-rank-' . $rank;
    }

    private function plagiarism_vericite_identifier_prefix($consumer, $cmid, $userid) 
    {
        return $consumer . '_' . $cmid . '_' . $userid . '_';
    }

}

function plagiarism_vericite_log($logstring, $e=null) 
{
    $plagiarismsettings = plagiarism_vericite_get_settings();
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

function plagiarism_vericite_get_settings() 
{
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

function plagiarism_vericite_get_setting_boolean($plagiarismsettings, $key, $default = false) 
{
    return isset($plagiarismsettings[$key]) ? $plagiarismsettings[$key] : $default;
}

function plagiarism_vericite_ends_with($str, $test) 
{
    return substr_compare($str, $test, -strlen($test), strlen($test)) === 0;
}

function plagiarism_vericite_call_api($host_url, $action, $args) {
    global $CFG;

    // Use the Moodle central proxy settings.
    $config = new \Swagger\Client\Configuration();
    if (!empty($CFG->proxytype)) {
        $config->setCurlProxyType($CFG->proxytype);
    }
    if (!empty($CFG->proxyhost)) {
        $config->setCurlProxyHost($CFG->proxyhost);
    }
    if (!empty($CFG->proxyport)) {
        $config->setCurlProxyPort($CFG->proxyport);
    }
    if (!empty($CFG->proxyuser)) {
        $config->setCurlProxyUser($CFG->proxyuser);
    }
    if (!empty($CFG->proxypassword)) {
        $config->setCurlProxyPassword($CFG->proxypassword);
    }

    if (!plagiarism_vericite_ends_with($host_url, '/')) {
        $host_url .= '/';
    }
    $host_url .= PLAGIARISM_VERICITE_API_VERSION;
    $config->setHost($host_url);

    $api_client = new \Swagger\Client\ApiClient($config);
    $assignments_api = new \Swagger\Client\Api\AssignmentsApi($api_client);
    $reports_api = new \Swagger\Client\Api\ReportsApi($api_client);
    $success = false;
    $attempts = 0;
    $result = null;
    while (!$success && $attempts < 3) {
        try{
            plagiarism_vericite_log("VeriCite API call: " . $action . ", attempt: " . $attempts . ", args: \n" . serialize($args));
            switch ($action) {
            case PLAGIARISM_VERICITE_ACTION_ASSIGNMENTS:
                $result = $assignments_api->createUpdateAssignment($args['context_id'], $args['assignment_id'], $args['consumer'], $args['consumer_secret'], $args['assignment_data']);
                break;
            case PLAGIARISM_VERICITE_ACTION_REPORTS_SUBMIT_REQUEST:
                $result = $reports_api->submitRequest($args['context_id'], $args['assignment_id'], $args['user_id'], $args['consumer'], $args['consumer_secret'], $args['report_meta_data'], null, "true");
                break;
            case PLAGIARISM_VERICITE_ACTION_REPORTS_SCORES:
                $result = $reports_api->getScores($args['context_id'], $args['consumer'], $args['consumer_secret'], $args['assignment_id'], $args['user_id'], $args['external_content_id']);
                break;
            case PLAGIARISM_VERICITE_ACTION_REPORTS_URLS:
                $result = $reports_api->getReportUrls($args['context_id'], $args['assignment_id_filter'], $args['consumer'], $args['consumer_secret'], $args['token_user'], $args['token_user_role'], $args['user_id_filter'], $args['external_content_id_filter']);
                break;
            }
            $success = true;
        }catch (Exception $e) {
            plagiarism_vericite_log("VeriCite API Exception", $e);
        }
        $attempts++;
    }
    if (!$success) {
        $result = null;
    }
    plagiarism_vericite_log("VeriCite API call complete:\n" . serialize($result));
    return $result;
}

function plagiarism_vericite_serialize_fields($fields) {
    $fields_string = '';
    foreach ($fields as $key=>$value) {
        if (is_array($value)) {
            $fields_string .= $key .'=' . json_encode($value) . '&';
        } else {
            $fields_string .= $key .'=' . $value . '&';
        }
    }
    $fields_string = rtrim($fields_string, '&');
    return $fields_string;
}

function plagiarism_vericite_curl_exec($ch) {
    global $CFG;

    if (!empty($CFG->proxyhost)) {
        if (empty($CFG->proxyport)) {
            curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost);
        } else {
            curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
        }
        if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypassword);
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
        }
        if (!empty($CFG->proxytype)) {
            if ($CFG->proxytype == 'SOCKS5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            } else {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, FALSE);
            }
        }
    }

    $result = null;
    $attempts = 0;
    $success = false;
    do {
        $result = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        plagiarism_vericite_log(curl_getinfo($ch, CURLINFO_HEADER_OUT));
        plagiarism_vericite_log("attempts: " . $attempts . "; response code: " . $responseCode . "; result:\n" . $result);
        if ($result === false || $responseCode != 200) {
            // Make sure to return null so that the caller knows it failed.
            $result = null;
            $success = false;
        } else {
            // A request could time out, check for a result message error.
            if (!empty($result) && (substr($result, 0, 1) === "{" || substr($result, 0, 1) === "[")) {
                $result = json_decode($result);
                if (isset($result->errorMessage)) {
                    // The request endpoint timed out, let's call again.
                    plagiarism_vericite_log($result->errorMessage);
                    $success = false;
                    $result = null;
                } else if (isset($result->message) && strpos($result->message, "timed out") !== false) {
                    // The request endpoint timed out, let's call again.
                    plagiarism_vericite_log("timed out");
                    $success = false;
                    $result = null;
                } else {
                    plagiarism_vericite_log("success 1");
                    $success = true;
                    // Make sure we return a non-empty result to show success.
                    if (empty($result)) {
                        $result = 1;
                    }
                }
            } else {
                // No message is a good message.
                plagiarism_vericite_log("success 2");
                $success = true;
                // Make sure we return a non-empty result to show success.
                if (empty($result)) {
                    $result = 1;
                }
            }
        }
        $attempts++;
    } while (!$success && $attempts < PLAGIARISM_VERICITE_REQUEST_ATTEMPTS);
    if ($attempts >= PLAGIARISM_VERICITE_REQUEST_ATTEMPTS) {
        plagiarism_vericite_log("no more attempts");
    }
    curl_close($ch);
    return $result;
}

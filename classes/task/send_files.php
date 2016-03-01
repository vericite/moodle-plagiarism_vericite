<?php
namespace plagiarism_vericite\task;

class send_files extends \core\task\scheduled_task {
	public function get_name() {
		// Shown in admin screens.
		return get_string('sendfiles', 'plagiarism_vericite');
	}

	public function execute() {
		global $CFG;
		require_once($CFG->dirroot.'/plagiarism/vericite/lib.php');
		$this->plagiarism_vericite_send_files();
	}

    /**
     * Called by task: send_files
     *
     */
    public function plagiarism_vericite_send_files() {
        global $CFG, $DB;

        $plagiarismsettings = plagiarism_vericite_get_settings();

        // Submit queued files.
        $dbfiles = $DB->get_records('plagiarism_vericite_files', array('status' => PLAGIARISM_VERICITE_STATUS_SEND), '', 'id, cm, userid, identifier, data, status, attempts');
        if (!empty($dbfiles)) {
            foreach ($dbfiles as $dbfile) {
                // Lock the record in the database.
                $dbfile->status = PLAGIARISM_VERICITE_STATUS_LOCKED;
                $DB->update_record('plagiarism_vericite_files', $dbfile);

                try {
                    $customdata = unserialize(base64_decode($dbfile->data));
                    $userid = $customdata['userid'];
                    $vericite = $customdata['vericite'];
                    #$file = unserialize($customdata['file']);
                    if (!empty($customdata['file'])) {
                        $file = get_file_storage();
                        $file = unserialize($customdata['file']);
                    }

                    $reportMetaData = array(
                        'user_first_name' => !empty($customdata['userFirstName']) ? $customdata['userFirstName'] : '',
                        'user_last_name' => !empty($customdata['userLastName']) ? $customdata['userLastName'] : '',
                        'user_email' => !empty($customdata['userEmail']) ? $customdata['userEmail'] : '',
                        'user_role' => $customdata['contentUserGradeAssignment'] ?  'Instructor' : 'Learner',
                        'assignment_title' => !empty($customdata['assignmentTitle']) ? $customdata['assignmentTitle'] : 'TODO',
                        'context_title' => !empty($vericite['courseTitle']) ? $vericite['courseTitle'] : '',
                    );

                    // Create a tmp file to store data.
                    if (!check_dir_exists($customdata['dataroot']."/plagiarism/", true, true)) {
                        mkdir($customdata['dataroot']."/plagiarism/", 0700);
                    }
                    $filepath = $customdata['dataroot'] . "/plagiarism/" . time() . $vericite['file']['filename'];
                    $uploadContentType = pathinfo($filepath, PATHINFO_EXTENSION);
                    $fh = fopen($filepath, 'w');
                    if (!empty($vericite['file']['type']) && $vericite['file']['type'] == "file") {
                        if (!empty($file->filepath)) {
                            fwrite($fh, file_get_contents($file->filepath));
                        } else {
                            fwrite($fh, $file->get_content());
                        }
                    } else {
                        fwrite($fh, $vericite['file']['content']);
                    }
                    fclose($fh);
					
					$externalContentData = array();
					$externalContentData['upload_content_type'] = pathinfo($vericite['file']['filename'], PATHINFO_EXTENSION);
					$filename = pathinfo($vericite['file']['filename'], PATHINFO_FILENAME);
					if(empty($filename)){
						$filename = $vericite['file']['filename'];
					}
					$externalContentData['file_name'] = $filename;
					$externalContentData['external_content_id'] = $dbfile->identifier;
					$externalContentData['upload_content_length'] = filesize($filepath);
					$reportMetaData['external_content_data'] = new \Swagger\Client\Model\ExternalContentData($externalContentData);
                    $externalData = new \Swagger\Client\Model\ReportMetaData($reportMetaData);

                    $api = new \Swagger\Client\Api\DefaultApi();
                    $resultJson = $api->reportsSubmitRequestContextIDAssignmentIDUserIDPost($customdata['courseid'], $customdata['cmid'], $userid, $plagiarismsettings['vericite_accountid'], $plagiarismsettings['vericite_secretkey'], $externalData);

                    // Check to see if the original request submit was not successful.
                    if(empty($resultJson)){
                        // Error of some sort, do not save.
                        plagiarism_vericite_log('VeriCite returns empty result, maybe they want us to check back later.');
                        throw new \Exception('failed to request submit file to VeriCite');
                    }

                    if(!empty($resultJson) && is_array($resultJson)) {
                        foreach ($resultJson AS $externalcontentuploadinfo) {
                            // Now see if there are any presigned URLs we need to upload our attachment to.
                            if($externalcontentuploadinfo) {
                                plagiarism_vericite_log("urlPost:\n" . $externalcontentuploadinfo->getUrlPost() . "\nfilepath: " . $filepath);
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $externalcontentuploadinfo->getUrlPost());
                                $fh_res = fopen($filepath, 'r');
                                curl_setopt($ch, CURLOPT_INFILE, $fh_res);
                                curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filepath));
                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
                                curl_setopt($ch, CURLOPT_PUT, 1);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                $curl_result = curl_exec($ch);
                                $responseCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                fclose($fh_res);

                                if($responseCode === 200) {
                                    // Success: do nothing.
                                    plagiarism_vericite_log("VeriCite: cron submit success.");
                                } else {
                                    // Error of some sort, do not save.
                                    plagiarism_vericite_log('failed to send file to VeriCite');
                                    throw new \Exception('Failed to send file to VeriCite pre-signed URL');
                                }
                            }
                        }
                    }
					
                    // Now update the record to show we have retreived it.
                    $dbfile->status = PLAGIARISM_VERICITE_STATUS_SUCCESS;
                    $dbfile->data = "";
                    $DB->update_record('plagiarism_vericite_files', $dbfile);
                    // Clear cache scores so that the score will be looked up immediately.
                    $DB->delete_records('plagiarism_vericite_score', array('cm' => $dbfile->cm));
                } catch (\Exception $e) {
                    plagiarism_vericite_log("Cron Error: " . $e->getMessage(), $e);
                    // Something unexpected happened, unlock this to try again later.
                    if ($dbfile->attempts < 100) {
                        $dbfile->status = PLAGIARISM_VERICITE_STATUS_SEND;
                        $dbfile->attempts = $dbfile->attempts + 1;
                    } else {
                        $dbfile->status = PLAGIARISM_VERICITE_STATUS_FAILED;
                    }
                    $DB->update_record('plagiarism_vericite_files', $dbfile);
                }
            }
        }

        // Delete old tokens:
        $DB->delete_records_select('plagiarism_vericite_tokens', 'timeretrieved < ?', array(time() - (60 * PLAGIARISM_VERICITE_TOKEN_CACHE_MIN)));
    }
}

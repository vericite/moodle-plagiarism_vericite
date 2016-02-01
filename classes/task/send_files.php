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
            $fileids = array();
            foreach ($dbfiles as $dbfile) {
                // Lock DB records that will be worked on.
                array_push($fileids, $dbfile->id);
            }
            list($dsql, $dparam) = $DB->get_in_or_equal($fileids);
            // TODO: Oracle 1000 in clause limit
            $DB->execute("update {plagiarism_vericite_files} set status = " . PLAGIARISM_VERICITE_STATUS_LOCKED . " where id " . $dsql, $dparam);

            foreach ($dbfiles as $dbfile) {
                try {
                    $customdata = unserialize(base64_decode($dbfile->data));
                    $userid = $customdata['userid'];
                    $vericite = $customdata['vericite'];
                    if (!empty($customdata['file'])) {
                        $file = get_file_storage();
                        $file = unserialize($customdata['file']);
                    }
                    $url = plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], PLAGIARISM_VERICITE_ACTION_REPORTS_SUBMIT_REQUEST, $customdata['courseid'], $customdata['cmid'], $userid);
                    $fields = array();
					$reportMetaData = array();
                    if (!empty($customdata['userFirstName'])) {
                        $reportMetaData['userFirstName'] = $customdata['userFirstName'];
                    }
                    if (!empty($customdata['userLastName'])) {
                        $reportMetaData['userLastName'] = $customdata['userLastName'];
                    }
                    if (!empty($customdata['userEmail'])) {
                        $reportMetaData['userEmail'] = $customdata['userEmail'];
                    }
                    $reportMetaData['userRole'] = $customdata['contentUserGradeAssignment'] ?  'Instructor' : 'Learner';
                    if (isset($vericite['assignmentTitle'])) {
                        $reportMetaData['assignmentTitle'] = $vericite['assignmentTitle'];
                    }
					
                    // Create a tmp file to store data.
                    if (!check_dir_exists($customdata['dataroot']."/plagiarism/", true, true)) {
                        mkdir($customdata['dataroot']."/plagiarism/", 0700);
                    }
                    $filepath = $customdata['dataroot'] . "/plagiarism/" . time() . $vericite['file']['filename'];
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
					$externalContentData['uploadContentType'] = pathinfo($filepath, PATHINFO_EXTENSION);
					$externalContentData['fileName'] = $vericite['file']['filename'];
					$externalContentData['externalContentID'] = $dbfile->identifier;
					$externalContentData['uploadContentLength'] = filesize($filepath);
					$reportMetaData['externalContentData'] = array($externalContentData);
					$fields['reportMetaData'] = $reportMetaData;
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
					curl_setopt($ch,CURLOPT_POST, true);
					curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_json);
					
			        plagiarism_vericite_log("url:\n" . $url . "\nfields:\n" . $fields_json);
					$resultJson = plagiarism_vericite_curl_exec($ch);
					if(!empty($resultJson)){
						//now see if there are any presigned URLs we need to upload our attachment to:
						if(isset($resultJson->externalContentUploadInfo)){
							foreach($resultJson->externalContentUploadInfo as $externalContentUploadInfo){
								if(isset($externalContentUploadInfo->urlPost)){
									plagiarism_vericite_log("urlPost:\n" . $externalContentUploadInfo->urlPost . "\nfilepath:\n" . $filepath);
									//create curl request
									$ch = curl_init();
									// set url
									curl_setopt($ch, CURLOPT_URL, $externalContentUploadInfo->urlPost);
									//upload file to this presigned URL
									$fh_res = fopen($filepath, 'r');
									curl_setopt($ch, CURLOPT_INFILE, $fh_res);
									curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filepath));									
									//set timeout in seconds:
									curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 120);
									curl_setopt($ch,CURLOPT_PUT, 1);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									$resultJson = plagiarism_vericite_curl_exec($ch);
									if(!empty($resultJson)){
				                        // Success: do nothing.
				                        plagiarism_vericite_log("VeriCite: cron submit success.");
				                    } else {
				                        // Error of some sort, do not save.
				                        throw new \Exception('failed to send file to VeriCite');
				                    }
								}
							}
						}
					}
					//delete temp file
                    unlink($filepath);
                    // Now update the record to show we have retreived it.
                    $dbfile->status = PLAGIARISM_VERICITE_STATUS_SUCCESS;
                    $dbfile->data = "";
                    $DB->update_record('plagiarism_vericite_files', $dbfile);
                    // Clear cache scores so that the score will be looked up immediately.
                    $DB->execute("delete from {plagiarism_vericite_score} where cm = " . $dbfile->cm);
                } catch (\Exception $e) {
                    plagiarism_vericite_log("Cron Error", $e);
                    // Something unexpected happened, unlock this to try again later.
                    if ($dbfile->attempts < 500) {
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
        $DB->execute("delete from {plagiarism_vericite_tokens} where timeretrieved < " . (time() - (60 * PLAGIARISM_VERICITE_TOKEN_CACHE_MIN)));

    }
}

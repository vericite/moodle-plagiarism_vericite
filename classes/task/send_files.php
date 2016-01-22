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
                    $url = plagiarism_vericite_generate_url($plagiarismsettings['vericite_api'], $customdata['courseid'], $customdata['cmid'], $userid);
                    $fields = array();
                    if (!empty($customdata['userFirstName'])) {
                        $fields['userFirstName'] = $customdata['userFirstName'];
                    }
                    if (!empty($customdata['userLastName'])) {
                        $fields['userLastName'] = $customdata['userLastName'];
                    }
                    if (!empty($customdata['userEmail'])) {
                        $fields['userEmail'] = $customdata['userEmail'];
                    }
                    $fields['userRole'] = $customdata['contentUserGradeAssignment'] ?  'Instructor' : 'Learner';
                    $fields['consumer'] = $plagiarismsettings['vericite_accountid'];
                    $fields['consumerSecret'] = $plagiarismsettings['vericite_secretkey'];
                    if (isset($vericite['assignmentTitle'])) {
                        $fields['assignmentTitle'] = $vericite['assignmentTitle'];
                    }
                    $fields['externalContentId'] = $dbfile->identifier;
                    // Create a tmp file to store data.
                    if (!check_dir_exists($customdata['dataroot']."/plagiarism/", true, true)) {
                        mkdir($customdata['dataroot']."/plagiarism/", 0700);
                    }
                    $filename = $customdata['dataroot'] . "/plagiarism/" . time() . $vericite['file']['filename'];
                    $fh = fopen($filename, 'w');
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
                    $fields['fileName'] = $vericite['file']['filename'];

                    plagiarism_vericite_log("VeriCite: cron submit: url: " . $url . " ; fields: " . print_r($fields, true));

                    if (class_exists('CURLFile')) {
                        $fields['filedata'] = new \CURLFile($filename);
                    } else {
                        $fields['filedata'] = '@' . $filename;
                    }
                    $c = new \curl(array('proxy' => true));
                    $status = json_decode($c->post($url, $fields));
                    if (!empty($status) && isset($status->result) && strcmp("success", $status->result) == 0) {
                        // Success: do nothing.
                        plagiarism_vericite_log("VeriCite: cron submit success.");
                    } else {
                        // Error of some sort, do not save.
                        throw new \Exception('failed to send file to VeriCite');
                    }
                    unlink($filename);
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

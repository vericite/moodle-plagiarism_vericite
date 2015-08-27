<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_setup_form extends moodleform {

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $choices = array('No','Yes');
        $mform->addElement('html', get_string('vericiteexplain', 'plagiarism_vericite'));
        $mform->addElement('checkbox', 'vericite_use', get_string('usevericite', 'plagiarism_vericite'));

        $mform->addElement('textarea', 'vericite_student_disclosure', get_string('studentdisclosure','plagiarism_vericite'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('vericite_student_disclosure', 'studentdisclosure', 'plagiarism_vericite');
        $mform->setDefault('vericite_student_disclosure', get_string('studentdisclosuredefault','plagiarism_vericite'));

	$mform->addElement('text', 'vericite_accountid', get_string('vericiteaccountid', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_accountid', 'vericiteaccountid', 'plagiarism_vericite');
        $mform->addRule('vericite_accountid', null, 'required', null, 'client');
	$mform->setType('vericite_accountid', PARAM_TEXT);

        $mform->addElement('passwordunmask', 'vericite_secretkey', get_string('vericitesecretkey', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_secretkey', 'vericitesecretkey', 'plagiarism_vericite');
        $mform->addRule('vericite_secretkey', null, 'required', null, 'client');

	$mform->addElement('text', 'vericite_api', get_string('vericiteapi', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_api', 'vericiteapi', 'plagiarism_vericite');
        $mform->addRule('vericite_api', null, 'required', null, 'client');
	$mform->setType('vericite_api', PARAM_URL);

        $mform->addElement('html', get_string('advanced_settings', 'plagiarism_vericite') . "<br/>");
        
	$mform->addElement('checkbox', 'vericite_disable_dynamic_inline', get_string('disable_dynamic_inline', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_disable_dynamic_inline', 'disable_dynamic_inline', 'plagiarism_vericite');
	$mform->addElement('checkbox', 'vericite_enable_debugging', get_string('enable_debugging', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_enable_debugging', 'enable_debugging', 'plagiarism_vericite');

        $mform->addElement('html', get_string('vericitedefaultsettings', 'plagiarism_vericite') . "<br/>");

        $mform->addElement('checkbox', 'vericite_use_default', get_string('usevericite', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_use_default', 'usevericite', 'plagiarism_vericite');
        
        $mform->addElement('checkbox', 'vericite_student_score_default', get_string('studentscorevericite', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_student_score_default', 'studentscorevericite', 'plagiarism_vericite');
	
        $mform->addElement('checkbox', 'vericite_student_report_default', get_string('studentreportvericite', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_student_report_default', 'studentreportvericite', 'plagiarism_vericite');
	
        $mform->addElement('html', get_string('vericitedefaultsettingsforums', 'plagiarism_vericite') . "<br/>");

        $mform->addElement('checkbox', 'vericite_student_score_default_forums', get_string('studentscorevericite', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_student_score_default_forums', 'studentscorevericite', 'plagiarism_vericite');
	
        $mform->addElement('checkbox', 'vericite_student_report_default_forums', get_string('studentreportvericite', 'plagiarism_vericite'));
        $mform->addHelpButton('vericite_student_report_default_forums', 'studentreportvericite', 'plagiarism_vericite');
	
	$this->add_action_buttons(true);
    }
}


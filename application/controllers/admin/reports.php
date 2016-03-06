<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all files related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Reports extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.seo.html';//'project.reports.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu
        $this->data['vars']['css_active_tab_reports'] = 'side-menu-main-active'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_reports'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

        $this->data['visible']['wi_reports'] = 1;

        $this->load->model('reports_model', 'model');

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     *
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/files/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //PERMISSIONS CHECK - ACCESS
        //do this check before __commonAll_ProjectBasics()
        if ($this->data['vars']['my_group'] != 1) {
            if (!in_array($this->project_id, $this->data['my_projects_array'])) {
                redirect('/admin/error/permission-denied');
            }
        }

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['view_item_my_project_files'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //create pulldown lists
        $this->__pulldownLists();

        //javascript allowed files array
        js_allowedFileTypes();

        //javascript file size limit
        js_fileSizeLimit();

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch ($action) {

            case 'view':
                $this->__seoView();
                //$this->__filesView();
                break;

            case 'add':
                $this->__filesAdd();
                break;

            case 'edit':
                $this->__filesEdit();
                break;

            default:
                $this->__filesView();
                break;
        }

        //css - active tab
        //$this->data['vars']['css_active_tab_files'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * Load the view
     */
    function __seoView()
    {
        if (!empty($_POST)) {
            $this->model->saveSeoUrl($this->project_id);
            redirect(current_url());
        }

        $type = $this->uri->segment(5);
        $type = empty($type) ? 'ranking' : 'backlinks';

        $this->data['vars']['css_active_'.$type.'_reports'] = 'side-menu-main-active';

        $link = $this->model->getSeoUrl($this->project_id, $type);

        $this->data['vars']['has_link'] = false;
        $this->data['vars']['seo_type'] = $type;
        if (false != $link) {
            $this->data['vars']['has_link'] = true;
            $this->data['reg_blocks'][] = 'project';
            $this->data['blocks']['project'] = $link;
            $this->data['project'] = $link;
            $this->data['vars']['link'] = $link['projects_seo_link'];
        } else {
            $this->data['visible']['wi_tabs_notification'] = 1;
            $this->data['visible']['wi_notification'] = true;
            $this->data['vars']['notification'] = $this->data['lang']['lang_no_seolink'];
        }
    }

    /**
     * example of a paginated method with no cached search
     */
    function __filesView()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /admin/files/2/view/54/asc/sortby_fileid/20
        * (2)->controller
        * (3)->project id
        * (4)->router
        * (5)->search id
        * (6)->sort_by
        * (7)->sort_by_column
        * (8)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //uri segments
        $search_id = $sort_by = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_fileid' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;
        $search_id = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'files';
        $this->data['blocks']['files'] = $this->model->searchFiles($offset, 'search', $this->project_id);
        $this->data['debug'][] = $this->model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->model->searchFiles($offset, 'count', $this->project_id);
        $this->data['debug'][] = $this->model->debug_data;

        //pagination
        $config = pagination_default_config();
        $config['base_url'] = site_url("admin/reports/" . $this->project_id . "/view/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 8; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_fileid',
            'sortby_filename',
            'sortby_milestone',
            'sortby_projectid',
            'sortby_downloads',
            'sortby_filetype',
            'sortby_uploadedby',
            'sortby_date',
            'sortby_size');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("admin/reports/" . $this->project_id . "/view/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility
        if ($rows_count > 0) {
            //show results
            $this->data['visible']['wi_files_table'] = 1;
        } else {
            //show mothing found
            $this->notifications('wi_tabs_notification', $this->data['lang']['lang_no_files_for_this_project']);
        }

        /** SEND DATA FOR ADDITIONAL PREPARATION **/
        $this->data['blocks']['files'] = $this->__prepFilesView($this->data['blocks']['files']);
        /*echo '<pre>';
        print_r($this->data['blocks']['files']);
        die;*/

    }

    /**
     * additional data preparations for __filesView() data
     *
     */
    function __prepFilesView($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //check if data is not empty
        if (count($thedata) == 0 || !is_array($thedata)) {
            return $thedata;
        }

        /* -----------------------PREPARE FILES DATA ----------------------------------------/
        *  Loop through all the files in this array and for each file:
        *  -----------------------------------------------------------
        *  (1) add visibility for the [control] buttons
        *  (2) process user names (files uploaded by)
        *  -----------------------------------------------------------
        *  (1) above is base on what rights I have on the file, i.e:
        *           - am I the file owner
        *           - am I the project leader
        *           - am I a system administrator
        *
        * [NOTES]
        * Usage is during conditional loading at TBS->MERGEBLOCK level and should be as follows:
        * <!--[onshow;block=div;when [files.wi_files_control_buttons;block=tr] == 1;comm]-->
        * --AS OPPOSED TO--
        * <!--[onshow;block=div;when [files.wi_files_control_buttons] == 1;comm]-->
        *
        *------------------------------------------------------------------------------------*/
        /*echo '<pre>';
        print_r($thedata[0]);die;*/
        for ($i = 0; $i < count($thedata); $i++) {

            //-----(1) VISIBILITY OF CONTROL BUTTONS--------------------------------\\

            //default visibility
            $visibility_control = 0;

            $thedata[$i]['reports_size_human'] = ($thedata[$i]['reports_size'] / 1024);
            if ($thedata[$i]['reports_size_human'] > 1024) {
                $thedata[$i]['reports_size_human'] = number_format(($thedata[$i]['reports_size_human'] / 2014), 2) . 'MB';
            } else {
                $thedata[$i]['reports_size_human'] = number_format($thedata[$i]['reports_size_human'], 2) . ' KB';
            }

            $thedata[$i]['reports_url'] = site_url('files/projects/' . $thedata[$i]['reports_project_id'] . '/' . $thedata[$i]['reports_foldername'] . '/' . $thedata[$i]['reports_name']);
            if (in_array($thedata[$i]['reports_extension'], array('jpg', 'rpg', 'png', 'tiff', 'jpeg'))) {
                $thedata[$i]['has_preview'] = 1;
            } else {
                $thedata[$i]['has_preview'] = 0;
            }

            //file preview url
            if (in_array($thedata[$i]['reports_extension'], array('doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'))) {
                $thedata[$i]['preview_url'] = 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode(site_url('files/projects/' . $thedata[$i]['reports_project_id'] . '/' . $thedata[$i]['reports_foldername'] . '/' . $thedata[$i]['reports_name']));
            } elseif (in_array($thedata[$i]['reports_extension'], array('pdf'))) {
                $thedata[$i]['preview_url'] = site_url('files/projects/' . $thedata[$i]['reports_project_id'] . '/' . $thedata[$i]['reports_foldername'] . '/' . $thedata[$i]['reports_name']);
            } else {
                //[conf.site_url;noerr]/admin/file/[vars.project_id]/view/[files.files_id;block=tr;noerr]
                $thedata[$i]['preview_url'] = site_url('admin/report/' . $thedata[$i]['reports_project_id'] . '/view/' . $thedata[$i]['reports_id']);
            }


            //grant visibility if I am the one who uploaded the file
            if ($this->data['vars']['my_id'] == $thedata[$i]['reports_uploaded_by_id']) {
                $visibility_control = 1;
            }

            //grant visibility if I am an admin or I am the project leader
            if ($this->data['vars']['my_group'] == 1 || $this->data['vars']['my_id'] == $this->data['vars']['project_leaders_id']) {
                $visibility_control = 1;
            }

            //add my rights into $thedata array
            $thedata[$i]['wi_files_control_buttons'] = $visibility_control;

            //-----(2) PROCESS (TEAM/CLIENT) USER NAMES--------------------------------\\

            //--team member-------------------------------------------------------------
            if ($thedata[$i]['reports_uploaded_by'] == 'team') {

                //is the users data available
                if ($thedata[$i]['team_profile_full_name'] != '') {

                    //trim max lenght
                    $user_id = $thedata[$i]['team_profile_id'];
                    //create users name label
                    $thedata[$i]['uploaded_by'] = $thedata[$i]['team_profile_full_name'];

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }
            }

            //--client user--------------------------------------------------------------
            if ($thedata[$i]['reports_uploaded_by'] == 'client') {

                //is the users data available
                if ($thedata[$i]['client_users_full_name'] != '') {

                    //trim max lenght
                    $user_id = $thedata[$i]['client_users_id'];
                    //create html
                    $thedata[$i]['uploaded_by'] = $thedata[$i]['client_users_full_name'];

                } else {

                    //this user is unavailable (has been deleted etc)
                    $thedata[$i]['uploaded_by'] = '<span class="tooltips" 
                                                       data-original-title="' . $this->data['lang']['lang_userd_details_unavailable'] . '">
                                                       ' . $this->data['lang']['lang_unavailable'] . '</span>';
                }
            }

        }
        /*echo '<pre>';
        print_r($thedata);die;*/

        //---return the processed array--------
        return $thedata;

    }

    /**
     * database entry part of the file uploading process
     *
     */
    function __filesAdd()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/reports/' . $this->project_id . '/view');
        }

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['add_item_my_project_files'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //validate form & display any errors
        if (!$this->__flmFormValidation('add_file')) {
            //show error
            $this->notices('error', $this->form_processor->error_message);

            $next = false;
        }

        //validate hidden fields
        if ($next) {

            //array of hidden fields and their check type
            $hidden_fields = array(
                'reports_project_id' => 'numeric',
                'reports_client_id' => 'numeric',
                'reports_events_id' => 'string',
                'reports_uploaded_by' => 'string',
                'reports_uploaded_by_id' => 'numeric',
                'reports_size' => 'numeric',
                'reports_foldername' => 'string',
                'reports_extension' => 'string'); //loop through and validate each hidden field
            foreach ($hidden_fields as $key => $value) {

                if (($value == 'numeric' && !is_numeric($_POST[$key])) || ($value == 'string' && $_POST[$key] == '')) {

                    //log this error
                    $this->__errorLogging(__line__, __function__, __file__, "Adding new file failed: Required hidden form field ($key) missing or invalid"); //show error
                    $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);

                    $next = false;
                }
            }
        }

        //add new file to database
        if ($next) {

            //human file size
            $this->data['vars']['files_size_human'] = convert_file_size($this->input->post('reports_size'));

            if ($result = $this->model->addFile()) {
                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

                //events tracker
                $this->__eventsTracker('new_file', array('target_id' => $result));

                //email notification
                $this->__emailer('mailqueue_new_file', $email_vars);

            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }
        $this->data['debug'][] = $this->tasks_model->debug_data;

        //show files page
        $this->__filesView();
    }

    /**
     * edit file details
     *
     */
    function __filesEdit()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //initial state

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/admin/reports/' . $this->project_id . '/view');
        }

        //PERMISSIONS CHECK - PROJECT
        //do this check after __commonAll_ProjectBasics()
        if ($this->data['project_permissions']['edit_item_my_project_files'] != 1) {
            redirect('/admin/error/permission-denied');
        }

        //validate form & display any errors
        if ($next) {
            if (!$this->__flmFormValidation('edit_file')) {

                //show error
                $this->notices('error', $this->form_processor->error_message);

                $next = false;
            }
        }

        //validate hidden fields
        if ($next) {
            if ($_POST['reports_events_id'] == '' || !is_numeric($_POST['reports_id'])) {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing file failed: Required hidden form fields missing or invalid'); //show error
                $this->notices('Eror', $this->data['lang']['lang_request_could_not_be_completed']);

                $next = false;
            }
        }

        //add new milstone to database
        if ($next) {
            if ($this->model->editFile()) {

                //show success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed']);

            } else {

                //log this error
                $this->__errorLogging(__line__, __function__, __file__, 'Editing file failed: Database error'); //show error

                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed']);
            }
        }
        $this->data['debug'][] = $this->model->debug_data;

        //show milestone page
        $this->__filesView();
    }

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     */
    function __cachedFormSearch()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //create array containg all post data in format:: array('name'=>$this->input->post('name));
        $search_array = array();
        foreach ($_POST as $key => $value) {
            $search_array[$key] = $this->input->post($key);
        }

        //save serch query in database & get id of database record
        $search_id = $this->input->save_query($search_array); //change url to "list" and redirect with cached search id.
        redirect("admin/reports/" . $this->project_id . "/view/$search_id");
    }

    /**
     * validates forms for various methods in this class
     * @param    string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation

        if ($form == 'add_file') {

            //check required fields
            $fields = array('reports_description' => $this->data['lang']['lang_description'], 'reports_name' => $this->data['lang']['lang_file_name']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        if ($form == 'edit_file') {

            //check required fields
            $fields = array('reports_description' => $this->data['lang']['lang_description']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }
        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_milestones]
        $data = $this->milestones_model->allMilestones('milestones_title', 'ASC', $this->project_id);
        $this->data['debug'][] = $this->milestones_model->debug_data;
        $this->data['lists']['all_milestones'] = create_pulldown_list($data, 'milestones', 'id');
    }

    /**
     * log any error message into the log file
     */
    function __errorLogging($theline = '', $thefunction = '', $thefile = '', $themessage = 'system error')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;
        $message_log = "[FILE: $thefile]  [LINE: $theline]  [FUNCTION: $thefunction]  [MESSAGE: $themessage]";
        log_message('debug', $message_log);
    }

    // -- __emailer-------------------------------------------------------------------------------------------------------
    /**
     * send out an email
     *
     * @access    private
     * @param    string
     * @return    void
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];

        //------------------------------------queue email in database-------------------------------
        /** THIS WIL NOT SEND BUT QUEUE THE EMAILS*/
        if ($email == 'mailqueue_new_file') {

            //email vars
            $this->data['email_vars']['email_title'] = $this->data['lang']['lang_new_file_uploaded'];

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('general_notification_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //general temaplate - email body
            $this->data['email_vars']['email_message'] = '
                    <div style=" border:#CCCCCC solid 1px; padding:8px;">
                    <strong>' . $this->data['lang']['lang_project'] . ':</strong> ' . $this->data['fields']['project_details']['projects_title'] . '<br />
                    ------------------------<br />
                    <strong>' . $this->data['lang']['lang_file_name'] . ': </strong>' . $this->input->post('reports_name') . '<br />
                    <strong>' . $this->data['lang']['lang_file_description'] . ':</strong> ' . $this->input->post('reports_description') . '
                    </div>';

            //loop through all project members (mailing list)
            for ($i = 0; $i < count($this->data['vars']['project_mailing_list']); $i++) {

                //dynamic email vars based on (client/team) member
                $this->data['email_vars']['addressed_to'] = $this->data['vars']['project_mailing_list'][$i]['name'];
                if ($this->data['vars']['project_mailing_list'][$i]['user_type'] == 'team') {
                    $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];
                } else {
                    $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_client'];
                }

                //set sqldata() for database
                $sqldata['email_queue_message'] = parse_email_template($template['message'], $this->data['email_vars']);
                $sqldata['email_queue_subject'] = $this->data['lang']['lang_project_update'] . ' - ' . $this->data['lang']['lang_new_file_uploaded'];
                $sqldata['email_queue_email'] = $this->data['vars']['project_mailing_list'][$i]['email'];

                //add to email queue database - excluding uploader (no need to send them an email)
                if ($sqldata['email_queue_email'] != $this->data['vars']['my_email']) {
                    $this->email_queue_model->addToQueue($sqldata);
                    $this->data['debug'][] = $this->email_queue_model->debug_data;
                }

            }
        }
    }

    /**
     * records new project events (timeline)
     *
     * @param    string $type identify the loop to run in this function
     * @param   array $vents_data an optional array that can be used to directly pass data]
     */
    function __eventsTracker($type = '', $events_data = array())
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'new_file') {

            //build data array
            $events = array();
            $events['project_events_project_id'] = $this->project_id;
            $events['project_events_type'] = 'file';
            $events['project_events_details'] = $this->input->post('reports_name');
            $events['project_events_action'] = 'lang_tl_uplaoded_file';
            $events['project_events_target_id'] = ($events_data['target_id'] == '') ? 0 : $events_data['target_id'];
            $events['project_events_user_id'] = $this->data['vars']['my_id'];
            $events['project_events_user_type'] = 'team';

            //add data to database
            $this->project_events_model->addEvent($events);
            $this->data['debug'][] = $this->project_events_model->debug_data;
        }

    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file files.php */
/* Location: ./application/controllers/admin/reports.php */

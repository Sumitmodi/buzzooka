<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Ticket related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Ticket extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/ticket.html';

        //css settings
        $this->data['vars']['css_menu_tickets'] = 'open'; //menu

    }

    /**
     * This is our re-routing function and is the inital function called
     *
     * 
     */
    function index()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonClient_LoggedInCheck();

        //javascript allowed files array
        js_allowedFileTypes();

        //javascript file size limit
        js_fileSizeLimit();

        //uri - action segment
        $action = $this->uri->segment(4);

        //ticket id
        $ticket_id = $this->uri->segment(3);

        /** CLIENT CHECK PERMISSION **/
        if (!$this->permissions->ticketsView($ticket_id)) {
            redirect('/client/error/permission-denied-or-not-found');
        }

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_tickets'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text"></i>';

        $this->data['vars']['sub_title'] = '';
        $this->data['vars']['sub_title_icon'] = '';

        //re-route to correct method
        switch ($action) {
            case 'view':
                $this->__viewTicket();
                break;

            case 'add-reply':
                $this->__addReply();
                break;

            case 'edit':
                $this->__editTicket();
                break;

            default:
                $this->__viewTicket();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * load support ticket
     *
     */
    function __viewTicket()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //ticket id
        $ticket_id = $this->uri->segment(3);

        //get ticket details
        if ($next) {
            $this->data['reg_fields'][] = 'ticket';
            $this->data['fields']['ticket'] = $this->tickets_model->getTicket($ticket_id);
            $this->data['debug'][] = $this->tickets_model->debug_data;

            if ($this->data['fields']['ticket']) {

                //show tickets
                $this->data['visible']['wi_ticket'] = 1;

            } else {
                //halt
                $next = false;
            }
        }

        //get replies
        if ($next) {
            $this->data['reg_blocks'][] = 'replies';
            $this->data['blocks']['replies'] = $this->tickets_replies_model->getReplies($ticket_id);
            $this->data['debug'][] = $this->tickets_replies_model->debug_data;
        }

        //error loding item
        if (!$next) {

            //show error
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
        }

        //final data preparation
        if ($next) {
            $this->data['fields']['ticket'] = dataprep_tickets($this->data['fields']['ticket']);
            $this->data['blocks']['replies'] = dataprep_ticket_replies($this->data['blocks']['replies']);
        }

    }

    /**
     *  save new ticket
     *
     */
    function __addReply()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/client/ticket/' . $this->uri->segment(3) . '/view');
        }

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('add_reply')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                $next = false;
            }
        }

        //validate hidden fields
        if ($next) {
            if (!$this->__flmFormValidation('add_reply_hidden')) {
                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Add ticket reply failed: Required hidden form fields are missing or invalid]");
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'html');
                $next = false;
            }
        }

        //SANITY: make sure client is replying to their own ticket
        //TODO

        //add to database
        if ($next) {

            //add ticket
            $ticket_id = $this->tickets_replies_model->addReply();
            $this->data['debug'][] = $this->tickets_replies_model->debug_data;

            //check if there was an errro inserting record
            if (!$ticket_id) {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Add ticket reply failed - Database error");

                //halt
                $next = false;
            }

        }

        //attachments
        if ($next) {

            //is there an attachment? - move the uploaded file into /files/tickets folder
            if ($this->input->post('tickets_file_folder')) {

                //move the attachments to final destination
                if (!tickets_move_attachment($this->input->post('tickets_file_folder'), $this->input->post('tickets_file_name'))) {

                    //delete ticket
                    $this->tickets_replies_model->deleteReply($ticket_id);
                    $this->data['debug'][] = $this->tickets_replies_model->debug_data;
                }
            }
        }

        //results
        if ($next) {

            //everything went ok, now update status of main ticket
            $this->tickets_model->updateStatus($this->input->post('tickets_replies_ticket_id'), 'answered');
            $this->data['debug'][] = $this->tickets_model->debug_data;

            //show success
            $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');

        } else {

            //show error
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty'); //noty or html
        }

        //show tickets list page
        $this->__viewTicket();

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'add_user') {

            //check required fields
            $fields = array('company_name_field' => $this->data['lang']['lang_company_name'], 'email_field' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check email fields
            $fields = array('users_email' => $this->data['lang']['lang_email']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //check password (lenght only - 8 characters min)
            $fields = array('password_field' => $this->data['lang']['lang_password']);
            if (!$this->form_processor->validateFields($fields, 'length')) {
                return false;
            }

            //everything ok
            return true;
        }

        //---------------validate form post data--------------------------
        if ($form == 'add_reply') {

            //check required fields
            $fields = array('tickets_replies_message' => $this->data['lang']['lang_message']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }

            //everything ok
            return true;
        }

        //---------------validate form post data--------------------------
        if ($form == 'add_reply_hidden') {

            //check required fields
            $fields = array('tickets_replies_ticket_id' => $this->data['lang']['lang_ticket_id']);
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

/* End of file ticket.php */
/* Location: ./application/controllers/client/ticket.php */

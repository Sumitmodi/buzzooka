<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Tickets related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Tickets extends MY_Controller
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
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/tickets.html';

        //css settings
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

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

        //create pulldown lists
        $this->__pulldownLists();

        //javascript allowed files array
        js_allowedFileTypes();

        //javascript file size limit
        js_fileSizeLimit();

        //uri - action segment
        $action = $this->uri->segment(3);

        //default page titles
        $this->data['vars']['main_title'] = $this->data['lang']['lang_tickets'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text"></i>';

        $this->data['vars']['sub_title'] = '';
        $this->data['vars']['sub_title_icon'] = '';

        //re-route to correct method
        switch ($action) {
            case 'list':
                $this->__listTickets();
                break;

            case 'search-tickets':
                $this->__cachedFormSearch();
                break;

            case 'new':
                $this->__newTicket();
                break;

            case 'create':
                $this->__createTicket();
                break;

            case 'add-new':
                $this->__addTicket();
                break;

            default:
                $this->__listTickets();
        }

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * list all tickets by default or results of search. if no search data is posted, list all tickets
     *
     */
    function __listTickets()
    {

        /* --------------URI SEGMENTS---------------
        * [example]
        * /client/tickets/list/new/54/desc/sortby_ticketid/0
        * (2)->controller
        * (3)->router
        * (4)->status
        * (5)->search id
        * (6)->sort_by
        * (7)->sort_by_column
        * (8)->offset
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //any session notices

        //table title
        $this->data['vars']['ticket_table_title'] = $this->uri->segment(4);

        //uri segments
        $status = $this->uri->segment(4);
        $search_id = (is_numeric($this->uri->segment(5))) ? $this->uri->segment(5) : 0;
        $sort_by = ($this->uri->segment(6) == 'desc') ? 'desc' : 'asc';
        $sort_by_column = ($this->uri->segment(7) == '') ? 'sortby_ticketid' : $this->uri->segment(7);
        $offset = (is_numeric($this->uri->segment(8))) ? $this->uri->segment(8) : 0;

        //load the original posted search into $_get array
        $this->input->load_query($search_id);

        //get results and save for tbs block merging
        $this->data['reg_blocks'][] = 'tickets';
        $this->data['blocks']['tickets'] = $this->tickets_model->searchTickets($offset, 'search');
        $this->data['debug'][] = $this->tickets_model->debug_data;

        //count results rows - used by pagination class
        $rows_count = $this->tickets_model->searchTickets($offset, 'count');
        $this->data['debug'][] = $this->tickets_model->debug_data;

        //pagination
        $config = pagination_default_config(); //load all other settings from helper
        $config['base_url'] = site_url("client/tickets/list/$status/$search_id/$sort_by/$sort_by_column");
        $config['total_rows'] = $rows_count;
        $config['per_page'] = $this->data['settings_general']['results_limit'];
        $config['uri_segment'] = 8; //the offset var
        $this->pagination->initialize($config);
        $this->data['vars']['pagination'] = $this->pagination->create_links();

        //sorting links for menus on the top of the table
        //the array names mustbe same as used in clients_model.php->searchClients()
        $link_sort_by = ($sort_by == 'asc') ? 'desc' : 'asc'; //flip the sort_by
        $link_sort_by_column = array(
            'sortby_ticketid',
            'sortby_datecreated',
            'sortby_dateactive',
            'sortby_status');
        foreach ($link_sort_by_column as $column) {
            $this->data['vars'][$column] = site_url("client/tickets/list/$status/$search_id/$link_sort_by/$column/$offset");
        }

        //visibility - show table or show nothing found
        if ($rows_count > 0 && !empty($this->data['blocks']['tickets'])) {

            //final processing of data
            $this->data['blocks']['tickets'] = dataprep_tickets_list($this->data['blocks']['tickets']);

            //show table
            $this->data['visible']['wi_tickets_table'] = 1;

        } else {

            //show no results
            $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
        }

    }

    /**
     *  show new ticket form
     *
     */
    function __newTicket($thedata = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show form
        $this->data['visible']['wi_new_ticket'] = 1;

    }

    /**
     *  save new ticket
     *
     */
    function __addTicket()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //check if any post data (avoid direct url access)
        if (!isset($_POST['submit'])) {
            redirect('/client/tickets/new');
        }

        //validate form
        if ($next) {
            if (!$this->__flmFormValidation('add_ticket')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                $next = false;
            }
        }

        //add to database
        if ($next) {

            //add ticket
            $ticket_id = $this->tickets_model->addTicket();
            $this->data['debug'][] = $this->tickets_model->debug_data;

            //check if there was an errro inserting record
            if (!$ticket_id) {

                //log this error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Add new ticket failed - Database error");

                //halt
                $next = false;
            }

        }

        //is there an attachment? - move the uploaded file into /files/tickets folder
        if ($next) {

            //do we have an attachemeny
            if ($this->input->post('tickets_file_folder')) {

                //move the attachments to final destination
                if (!tickets_move_attachment($this->input->post('tickets_file_folder'), $this->input->post('tickets_file_name'))) {

                    //delete ticket
                    $this->tickets_model->deleteTicket($ticket_id);
                    $this->data['debug'][] = $this->tickets_model->debug_data;
                }
            }
        }

        //results
        if ($next) {
            //show success
            $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
        } else {
            //show error
            $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty'); //noty or html
        }

        //show tickets list page
        $this->__listTickets();

    }

    /**
     * takes all posted (search form) data and saves it to an array
     * array is then saved in database
     * the unique id of the database record is now used in redirect for all page results
     *
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
        redirect("client/tickets/view/search/$search_id");
    }

    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //[all_departments]
        $data = $this->tickets_departments_model->allDepartments();
        $this->data['debug'][] = $this->tickets_departments_model->debug_data;
        $this->data['lists']['all_departments'] = create_pulldown_list($data, 'tickets_departments', 'id');

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //---------------validate form post data--------------------------
        if ($form == 'add_ticket') {

            //check required fields
            $fields = array(
                'tickets_department_id' => $this->data['lang']['lang_department'],
                'tickets_title' => $this->data['lang']['lang_title'],
                'tickets_message' => $this->data['lang']['lang_message']);
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

/* End of file tickets.php */
/* Location: ./application/controllers/client/tickets.php */

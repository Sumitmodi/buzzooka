<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class to fetch a project's quotation
 *
 * @author   Sandeep Giri
 * @access   public
 */
class Myquotation extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.quotation.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_project_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text-alt"></i>';

        //load the quotation model
        $this->load->model('myquotation_model','model');

        //load the form builder library
        $this->load->library('formbuilder');
    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/myquotation/2/view/*.*
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

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch (strtolower($action)) {

            case 'view':
                $this->__viewNotes();
                break;
            default:
                $this->__viewNotes();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_quotation'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    /**
     * view a members project note
     */
    function __viewNotes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show text view
        $this->data['visible']['wi_my_note_view'] = 1;

        //check if team member has a note for this project
        $quotations = $this->model->getQuotation($this->project_id);
        
        $this->data['debug'][] = $this->model->debug_data;

        if (!$quotations) {
            //success
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            $this->data['visible']['wi_project_quotations'] = 0;
        } else {
           foreach($quotations as $key=>$quo){
                $quotations[$key]['project_id'] = $this->project_id;
            }
           
            $this->data['reg_blocks'][] = 'quotations';
            $this->data['blocks']['quotations'] = $quotations;

            //show the quotation
            $this->data['visible']['wi_project_quotations'] = 1;
        }

        if($this->uri->segment(5) != null){
            $quotation = $this->model->getQuotationData($this->uri->segment(5));

            if($quotation == FALSE){
                $this->data['visible']['is_visible_quotationform'] = 0;
            } else {
                $this->data['reg_blocks'][] = 'quotation';
                $this->data['blocks']['quotation'] = array($quotation);

                $theform = $quotation['quotations_form_data'];
                $postdata = $quotation['quotations_post_data'];

                $this->data['reg_blocks'][] = 'quotationform';
                $this->data['blocks']['quotationform'] = $this->formbuilder->reBuildForm($theform, $postdata);

                $this->data['visible']['is_visible_quotationform'] = 1;
            }
        } else {
            $this->data['visible']['is_visible_quotationform'] = 0;
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

/* End of file myquotation.php */
/* Location: ./application/controllers/admin/myquotation.php */

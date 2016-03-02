<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Quotation related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Quotation extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'quotation.html';
        $this->data['vars']['css_submenu_quotations'] = 'style="display:block; visibility:visible;"';

        //css settings
        $this->data['vars']['css_menu_quotations'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-paste"></i>';

        //load form builder library
        $this->load->library('formbuilder');
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
        $this->__commonAdmin_LoggedInCheck();

        //uri - action segment
        $action = $this->uri->segment(3);

        //re-route to correct method
        switch ($action) {
            case 'view':
                $this->__viewQuotation();
                break;

            case 'update':
                $this->__updateQuotation();
                break;
            case 'download':
                $this->__downloadQuotation();
                break;    
            case 'add-to-project':
                $this->__addQuotationToProject();
                break;    
            case 'add-client':
                $this->__addNewClient();
                break;    
            default:
                $this->__viewQuotation();
        }

        //load view
        $this->__flmView('admin/main');

    }

    public function __addNewClient(){
        $company = $this->quotations_model->getClientByCompanyName(strtolower($this->input->post('company')));
        if(false != $company){        
            $this->quotations_model->updateQuotationClient($this->uri->segment(4),array(
                    'quotations_by_client'  => 'yes',
                    'quotations_client_id'  => $company->clients_id
                ));
            echo json_encode(array('code'=>400,'response'=>'Error','message'=>'Client already added.'));
            exit;
        }

        $insert = array(
            'clients_date_created'  => date('Y-m-d'),
            'clients_company_name'  => $this->input->post('company'),
            'clients_address'  => $this->input->post('address'),
            'clients_city'  => $this->input->post('city'),
            'clients_state'  => $this->input->post('state'),
            'clients_zipcode'  => $this->input->post('zip'),
            'clients_country'  => $this->input->post('country'),
            'clients_website'  => $this->input->post('website')
        );

        $id = $this->quotations_model->addNewClient($insert,1);

        if((bool)$id == 0){
            echo json_encode(array('code'=>400,'response'=>'Error','message'=>'Client could not be added.'));
            exit;
        }

        $insert = array(
            'client_users_clients_id'   => $id,
            'client_users_full_name'    => $this->input->post('name',TRUE),
            'client_users_email'        => $this->input->post('email',TRUE),
            'client_users_telephone'    => $this->input->post('telephone',TRUE),
            'client_users_password'     => substr(md5(time()),0,12)
        );

        $user_id = $this->quotations_model->addNewClient($insert,2);

        if(!$user_id){
            echo json_encode(array('code'=>400,'response'=>'Error','message'=>'Client could not be added.'));
            return;
        }
        
        $this->users_model->updatePrimaryContact($id, $user_id);
        
        echo json_encode(array('code'=>200,'response'=>'Success','message'=>'Client has been saved.'));

        $this->quotations_model->updateQuotationClient($this->uri->segment(4),array(
                'quotations_by_client'  => 'yes',
                'quotations_client_id'  => $id
            ));

        $this->__email_new_client('new_client_welcome_client',$insert);
        $this->__email_new_client('new_client_admin',$insert);

        exit;
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    private function __email_new_client($email = '', $data = array())
    {

        //common variables
        $this->data['email_vars']['clients_company_name'] = $this->input->post('company');
        $this->data['email_vars']['client_users_full_name'] = $data['client_users_full_name'];
        $this->data['email_vars']['client_users_email'] = $data['client_users_email'];
        $this->data['email_vars']['client_users_password'] = $data['client_users_password'];
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['admin_dashboard_url'] = $this->data['vars']['site_url_admin'];

        //new client welcom email-------------------------------
        if ($email == 'new_client_welcome_client') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_client_welcome_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->data['email_vars']['client_users_email']);
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();

        }

        //new client welcom email-------------------------------
        if ($email == 'new_client_admin') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_client_admin');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data;

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);

            //send email to multiple admins
            foreach ($this->data['vars']['mailinglist_admins'] as $email_address) {
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($email_address);
                $this->email->subject($template['subject']);
                $this->email->message($email_message);
                $this->email->send();
            }
        }

    }

    /**
     * Add a quotation to a project
     *
     */
    public function __addQuotationToProject()
    {
        //get project id
        global $_POST;
        $project = $_POST['project'];

        //quotation id
        $quotation_id = $this->uri->segment(4);
        $p = $this->quotations_model->getProjectById($_POST['project']);
        if(false == $p){
            echo json_encode(array('code'=>400,'response'=>'Project does not exist.'));
            return;
        }

        if($response = $this->quotations_model->updateQuotationProject($quotation_id,$project) !== false){           
            $url = '<a href="'.base_url('/admin/myquotation/'.$p['projects_id'].'/view').'">'.$p['projects_title'].'</a>';
            echo json_encode(array('code'=>200,'response'=>'success','url'=>$url));
        } else {
            echo json_encode(array('code'=>400,'response'=>'Error'));
        }
        exit;
    }

    /**
     * Download a quotation
     *
     */
    public function __downloadQuotation()
    {
     
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //quotation id
        $quotation_id = $this->uri->segment(4);

        //validate id
        if (!is_numeric($quotation_id)) {
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            //halt
            $next = false;
        }

        //get quotation
        if ($next) {
            $this->data['reg_fields'][] = 'quotation';
            $this->data['fields']['quotation'] = $this->quotations_model->getQuotation($quotation_id);
            $this->data['debug'][] = $this->quotations_model->debug_data;
            if (!$this->data['fields']['quotation']) {
                //success
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            } else {
                //get the required data
                $theform = $this->data['fields']['quotation']['quotations_form_data'];
                $postdata = $this->data['fields']['quotation']['quotations_post_data'];
            }
        }

        //rebuild the form
        if ($next) {
            
            $this->data['reg_blocks'][] = 'quotationform';
            
            $this->data['blocks']['quotationform'] = $this->formbuilder->reBuildForm($theform, $postdata);

            $this->data['visible']['wi_quotation'] = 1;
            
            $xml = $this->load->view('admin/pdf',array('data'=>$this->data['blocks']['quotationform'],'quot'=>$this->data['fields']['quotation']),TRUE);
            
            require_once APPPATH.'third_party/MPDF57/mpdf.php';
            
            $mpdf = new mPDF();
            $mpdf->WriteHTML($xml);
            $mpdf->Output();
            exit;          
        }

    }

    /**
     * Load a quoation from the database
     *
     */
    function __viewQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //quotation id
        $quotation_id = $this->uri->segment(4);

        //validate id
        if (!is_numeric($quotation_id)) {
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            //halt
            $next = false;
        }

        //get quotation
        if ($next) {
            $this->data['reg_fields'][] = 'quotation';
            $this->data['fields']['quotation'] = $this->quotations_model->getQuotation($quotation_id);
            $this->data['debug'][] = $this->quotations_model->debug_data;
            if (!$this->data['fields']['quotation']) {
                //success
                $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            } else {
                //get the required data
                $theform = $this->data['fields']['quotation']['quotations_form_data'];
                $postdata = $this->data['fields']['quotation']['quotations_post_data'];
            }
        }

        //get projects list
        $projects = $this->projects_model->allProjects();
        $this->data['debug'][] = $this->projects_model->debug_data;
        $list = array();
        foreach($projects as $project){
            $list[] = array('projects_id'=>$project['projects_id'],'projects_title'=>$project['projects_title']);
        }
        
        $this->data['reg_fields'][] = 'projects';
        $this->data['fields']['projects'] = $list;

        $this->data['reg_blocks'][] = 'projects';
        $this->data['blocks']['projects'] = $list;

        $this->data['projects'] = $list;

        //projects added to quotation
        $added = $this->quotations_model->getProjects($quotation_id);

        
        $this->data['reg_fields'][] = 'quotation_projects';
        $this->data['fields']['quotation_projects'] = $added;

        $this->data['reg_blocks'][] = 'quotation_projects';
        $this->data['blocks']['quotation_projects'] = $added;

        $this->data['quotation_projects'] = $added;


        //rebuild the form
        if ($next) {
            $this->data['reg_blocks'][] = 'quotationform';
            $this->data['blocks']['quotationform'] = $this->formbuilder->reBuildForm($theform, $postdata);
            $this->data['visible']['wi_quotation'] = 1;
        }

    }

    /**
     * price a quotation and emal client (optional)
     *
     */
    function __updateQuotation()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //flow control
        $next = true;

        //prevent direct access
        if (!isset($_POST['submit'])) {
            //redirect to 'view' url instead
            $this_url = uri_string();
            $redirect = str_replace('update', 'view', $this_url);
            redirect($redirect);
        }

        //validate input
        if ($next) {
            if (!$this->__flmFormValidation('update_quotation')) {
                //show error
                $this->notices('error', $this->form_processor->error_message, 'html');
                //halt
                $next = false;
            }
        }

        //update database
        if ($next) {
            $result = $this->quotations_model->updateQuotation($this->input->post('quotations_id'));
            $this->data['debug'][] = $this->quotations_model->debug_data;
            if ($result) {
                //success
                $this->notices('success', $this->data['lang']['lang_request_has_been_completed'], 'noty');
            } else {
                //show error
                $this->notices('error', $this->data['lang']['lang_request_could_not_be_completed'], 'noty');
                //halt
                $next = false;
            }
        }

        //send email
        if ($next) {
            if ($this->input->post('send_email') == 'yes') {
                $this->__emailer('quotation_updated', array(
                    'quotation_notes' => $this->input->post('quotations_admin_notes'),
                    'client_users_full_name' => $this->input->post('client_users_full_name'),
                    'quotation_amount' => $this->input->post('quotations_amount')));
            }
        }

        //load quotation
        $this->__viewQuotation();
    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //common variables
        $this->data['email_vars']['todays_date'] = $this->data['vars']['todays_date'];
        $this->data['email_vars']['company_email_signature'] = $this->data['settings_company']['company_email_signature'];
        $this->data['email_vars']['client_dashboard_url'] = $this->data['vars']['site_url_client'];
        $this->data['email_vars']['currency_symbol'] = $this->data['settings_general']['currency_symbol'];

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //-------------send out email-------------------------------
        if ($email == 'quotation_updated') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_quotation_client');
            $this->data['debug'][] = $this->settings_emailtemplates_model->debug_data; //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']); //send email
            email_default_settings(); //defaults (from emailer helper)
            $this->email->to($this->input->post('clients_email'));
            $this->email->subject($template['subject']);
            $this->email->message($email_message);
            $this->email->send();
        }

    }

    /**
     * validates forms for various methods in this class
     * @param	string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__; //form validation
        if ($form == 'update_quotation') {

            //check amount is numeric
            $fields = array('quotations_amount' => $this->data['lang']['lang_amount']);
            if (!$this->form_processor->validateFields($fields, 'numeric')) {
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
        $this->data['controller_profiling'][] = __function__; //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']); //complete the view
        $this->__commonAll_View($view);
    }

}

/* End of file quotation.php */
/* Location: ./application/controllers/admin/quotation.php */

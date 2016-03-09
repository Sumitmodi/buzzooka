<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Projects related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Services extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'services.php';

        //css settings
        $this->data['vars']['css_menu_services'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_services'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

        //PERMISSIONS CHECK - GENERAL
        //Administrator only
        if ($this->data['vars']['my_group'] != 1) {
            redirect('/admin/error/permission-denied');
        }

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
            case 'list':
                $this->__listServices();
                break;

            case 'add-service':
                $this->__addService();
                break;

            case 'load-fields':
                $this->load_fields();
                break;

            case 'delete-service':
                $this->__deleteService();
                break;

            case 'save-fields':
                $this->saveFields();
                break;

            default:
                $this->__listServices();
        }

        //load view
        $this->__flmView('admin/main');

    }

    protected function saveFields()
    {
        $id = $this->input->post('service');
        $res = $this->crm->save_fields($id);
        if (false == $res) {
            $this->session->set_flashdata('message', $this->data['lang']['update_failed']);
        } else {
            $this->session->set_flashdata('message', $this->data['lang']['update_success']);
        }
        redirect('/admin/services/list');
    }

    protected function load_fields()
    {
        if ($this->input->is_ajax_request() && $this->uri->segment(4) != null) {
            $id = $this->uri->segment(4);
            $fields = $this->crm->load_fields($id);
            echo json_encode(array('code' => 200, 'data' => $fields));
            exit;
        }
        $id = $this->input->post('id');
        $fields = $this->crm->load_fields($id);
        $this->lang->load('default');
        $out = $this->load->view('admin/services/fields', array('fields' => $fields), true);
        echo $out;
        exit;
    }

    public function __addService()
    {
        if (false == $this->input->post(null, true)) {
            $this->notices('error', lang('data_expected'));
            return;
        }
        $result = $this->crm->update_service($this->input->post(null, true));
        if (false == (bool)$result) {
            $this->session->set_flashdata('message', lang('update_failed'));
        } else {
            $this->session->set_flashdata('message', lang('update_success'));
        }
        redirect('/admin/services');
    }

    public function __deleteService()
    {
        if ($this->crm->delete_service($this->uri->segment(4))) {
            $this->jsondata = array('results' => 'success', 'message' => lang('service_deleted'));
        } else {
            $this->jsondata = array('results' => 'error', 'message' => lang('service_not_deleted'));
        }
        echo json_encode($this->jsondata);
        exit;
    }

    public function __listServices()
    {
        $this->data['visible']['wi_services'] = 0;
        $services = $this->crm->listAll('services');

        if (false == $services) {
            $this->notifications('wi_notification', $this->data['lang']['no_services']);
            return false;
        }

        if ($this->session->flashdata('message') != false) {
            $this->notices('info', $this->session->flashdata('message'));
        }

        $this->data['visible']['wi_services'] = 1;
        $this->data['reg_blocks'][] = 'services';
        $this->data['blocks']['services'] = $services;
        $this->data['services'] = $services;
    }

    /**
     * validates forms for various methods in this class
     * @param    string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //form validation
        if ($form == 'add-service') {

            //check required fields
            $fields = array(
                'services_name' => $this->data['lang']['lang_service_name']
            );
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
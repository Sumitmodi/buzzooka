<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Milestones related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Citations extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //css settings
        //$this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_active_tab_reports'] = 'open'; //menu
        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_milestones'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        $this->data['vars']['css_active_citations'] = 'side-menu-main-active';
        $this->load->model('milestone_model', 'model');
    }

    public function index()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //project id
        $this->project_id = $this->uri->segment(3);

        //show wi_project_milestones widget
        $this->data['visible']['wi_project_milestones'] = 1;

        //get the action from url
        $action = $this->uri->segment(4);

        switch (strtolower($action)) {
            case 'view':
                $this->listCitations();
                break;
            case 'upload':
                $this->uploadCitations();
                break;
            default:
                $this->listCitations();
        }

    }

    private function listCitations()
    {

    }

    private function uploadCitations()
    {

    }
}
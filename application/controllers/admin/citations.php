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
        $this->data['vars']['css_active_tab_reports'] = 'side-menu-main-active'; //menu
        $this->data['vars']['css_active_citations'] = 'active'; //menu

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_project_citations'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';
        $this->load->model('milestone_model', 'model');
    }

    public function index()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //project id
        $this->project_id = $this->uri->segment(3);

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get project events (timeline)
        $this->data['reg_blocks'][] = 'timeline';
        $this->data['blocks']['timeline'] = $this->project_events_model->getEvents($this->project_id);
        $this->data['debug'][] = $this->project_events_model->debug_data;
        //further process events data
        $this->data['blocks']['timeline'] = prepare_events($this->data['blocks']['timeline']);

        //show wi_project_milestones widget
        $this->data['visible']['wi_project_milestones'] = 1;

        $this->data['visible']['wi_reports'] = 1;
        $this->data['vars']['project_id'] = $this->project_id;

        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.citations.html';

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

        $this->__flmView('admin/main');

    }

    private function listCitations()
    {
        $this->db->where('project_id', $this->project_id);
        $sort_column = $this->uri->segment(5);
        $sort = $this->uri->segment(6);
        if (empty($sort)) {
            $sort = 'asc';
        }

        if (!empty($sort_column)) {
            $this->db->order_by($sort_column, $sort);
        }
        $res = $this->db->get('citations');
        $this->data['vars']['sort'] = $sort == 'asc' ? 'desc' : 'asc';

        if ($res->num_rows() == 0) {
            $this->data['visible']['wi_notifications'] = 1;
            $this->data['visible']['citations'] = 0;
            $this->data['vars']['notification'] = $this->data['lang']['citations_not_added'];
        } else {
            $this->data['visible']['citations'] = 1;
            $this->data['visible']['wi_notifications'] = 0;

            static $live = 0;
            static $submitted = 0;
            static $pending = 0;
            static $unknown = 0;

            $results = array_map(function ($row) use (&$live, &$submitted, &$pending, &$unknown) {
                static $index = 0;
                $status = empty($row['status']) ? 'unknown' : strtolower($row['status']);
                ++$$status;
                $row['index'] = ++$index;
                return $row;
            }, array_filter($res->result_array()));

            $this->data['vars']['live'] = $live;
            $this->data['vars']['submitted'] = $submitted;
            $this->data['vars']['pending'] = $pending;
            $this->data['vars']['unknown'] = $unknown;

            $this->data['reg_blocks'][] = 'citations';
            $this->data['blocks']['citations'] = $results;
        }
        if ($this->session->flashdata('message')) {
            $this->notices('success', $this->session->flashdata('message'));
        }
    }

    private function uploadCitations()
    {
        if (isset($_FILES['file'])) {

            $res = $this->db->where('project_id', $this->project_id)->get('citations');
            $citations = array();
            if ($res->num_rows() > 0) {
                foreach ($res->result_object() as $r) {
                    $citations[] = strtolower($r->site);
                }
            }

            $this->load->library('CsvParser');
            $result = $this->csvparser->parse($_FILES['file']['tmp_name']);
            $results = array_map(function ($row) {
                $r = array_chunk($row, 3);
                if (count(array_filter($r[0])) > 1) {
                    return array(
                        'site' => $r[0][0],
                        'status' => $r[0][1],
                        'link' => $r[0][2],
                        'project_id' => $this->project_id,
                        'client_id' => $this->project_details['projects_clients_id'],
                        'event_id' => random_string('alnum', 40)
                    );
                }
                return null;
            }, $result);
            unset($results[0]);
            $results = array_filter($results);
            $insert = array();
            foreach ($results as $res) {
                if (in_array(strtolower($res['site']), $citations)) {
                    $this->db->where('project_id', $this->project_id)->where('LOWER(site)', strtolower($res['site']))->update('citations', $res);
                    continue;
                }
                $insert[] = $res;
            }
            if (count($insert)) {
                $this->db->insert_batch('citations', array_filter($insert));
            }
            $this->session->set_flashdata('message', $this->data['lang']['lang_request_has_been_completed']);
        } else {
            $this->session->set_flashdata('message', $this->data['lang']['lang_an_error_has_occurred']);
        }
        redirect(sprintf('admin/citations/%d/view', $this->project_id));
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
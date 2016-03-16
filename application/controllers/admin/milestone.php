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
class Milestone extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //css settings
        //$this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_milestone'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_milestones'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-folder-open"></i>';

        $this->load->model('milestone_model', 'model');

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
        * /admin/milestones/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //show wi_project_milestones widget
        $this->data['visible']['wi_project_milestones'] = 1;

        //get the action from url
        $action = $this->uri->segment(3);

        //route the request
        switch ($action) {

            case 'list-milestone-groups':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-group.html';
                $this->__listMilestoneGroups();
                break;

            case 'add-milestone-groups':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-group.html';
                $this->__addMilestoneGroups();
                break;

            case 'add-to-project':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-group.html';
                $this->__addMilestoneGroupToProject();
                break;

            case 'edit-milestone-groups':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-group.html';
                $this->__editMilestoneGroups();
                break;

            case 'list-milestones':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-list.html';
                $this->__listMilestones();
                break;

            case 'edit-milestone':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-list.html';
                $this->__editMilestone();
                break;

            case 'delete-milestone':
                $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.milestones-list.html';
                $this->__deleteMilestone();
                break;

            default:
                $this->__listMilestoneGroups();
                break;
        }

        //css - active tab
        //$this->data['vars']['css_menu_milestone'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    function __listMilestoneGroups()
    {
        $query = "SELECT team_profile.team_profile_full_name as user,DATE_FORMAT(date_added,'%Y-%m-%d') as date_added,title,status,id from milestone_groups LEFT JOIN team_profile ON team_profile.team_profile_id = milestone_groups.user_id";
        $groups = $this->db->query($query);

        if ($groups->num_rows() == 0) {
            $this->notices('error', 'Milestone groups have not been created.');

        } else {

            $projects = $this->projects_model->allProjects();
            $list = array();

            foreach ($projects as $project) {
                $list[] = array('projects_id' => $project['projects_id'], 'projects_title' => $project['services_name']);
            }

            $this->data['reg_fields'][] = 'projects';
            $this->data['fields']['projects'] = $list;

            $this->data['reg_blocks'][] = 'projects';
            $this->data['blocks']['projects'] = $list;

            $this->data['projects'] = $list;

            $this->data['reg_blocks'][] = 'groups';
            $this->data['groups'] = $groups->result_array();
            $this->data['blocks']['groups'] = $groups->result_array();

            $this->notices('success', 'Milestone groups loaded successfully.');
        }

    }

    function __listMilestones()
    {
        $group_id = $this->uri->segment(4);
        $query = "SELECT team_profile.team_profile_full_name as user,DATE_FORMAT(date_added,'%Y-%m-%d') as date_added,title,status,id from milestone_groups LEFT JOIN team_profile ON team_profile.team_profile_id = milestone_groups.user_id where milestone_groups.id = '{$group_id}'";

        $res = $this->db->query($query);
        $group = $res->row_array();

        $this->data['vars']['main_title'] .= ' : ' . $group['title'];

        if ($this->input->post()) {
            $insert = array(
                'title' => $this->input->post('title', true),
                'days' => $this->input->post('days', true),
                'group_id' => $group_id,
                'user_id' => $this->session->userdata('team_profile_id')
            );


            if ($this->db->insert('milestone_lists', $insert)) {
                $this->notices('success', 'New milestone added.');

            } else {
                $this->notices('error', 'New milestone could not be added.');
            }
        }

        $query = "SELECT title,start_date,end_date,days,id from milestone_lists where group_id = '{$group_id}'";
        $groups = $this->db->query($query);

        if ($groups->num_rows() == 0) {
            $this->notices('error', 'Milestone lists have not been created.');

        } else {

            $this->data['reg_blocks'][] = 'milestones';
            $this->data['milestones'] = $groups->result_array();
            $this->data['blocks']['milestones'] = $groups->result_array();
        }
    }

    function __addMilestoneGroups()
    {
        $insert = array(
            'title' => $this->input->post('title'),
            'status' => $this->input->post('status'),
            'user_id' => $this->session->userdata('team_profile_id')
        );

        $this->data['visible']['wi_notification'] = 1;

        if ($this->db->insert('milestone_groups', $insert)) {
            $this->notices('success', 'New milestone group created.');

        } else {
            $this->notices('error', 'Milestone group could not be created.');

        }

        $this->__listMilestoneGroups();
    }

    function __addMilestoneGroupToProject()
    {
        $project = $this->input->post('projects_title', true);
        $group = $this->input->post('groud_id', true);//typo in here

        $res = $this->db->where('projects_id', $project)->get('projects');

        if ($res->num_rows() == 0) {
            $this->notices('error', 'Selected project does not exist.');

        } else {
            $client = $res->row()->projects_clients_id;

            $query = "SELECT title,start_date,end_date,id from milestone_lists where group_id = '{$group}'";
            $res = $this->db->query($query);

            if ($res->num_rows() == 0) {
                $this->notices('error', 'Milestones have not been added to this group of milestones.');

            } else {
                $milestones = $res->result_object();
                $insert = array();

                foreach ($milestones as $m) {
                    $temp = array(
                        'milestones_project_id' => $project,
                        'milestones_title' => $m->title,
                        'milestones_start_date' => $m->start_date,
                        'milestones_end_date' => $m->end_date,
                        'milestones_created_by' => $this->session->userdata('team_profile_id'),
                        'milestones_events_id' => $this->data['vars']['new_events_id'],
                        'milestones_client_id' => $client
                    );

                    array_push($insert, $temp);
                }

                if ($this->db->insert_batch('milestones', $insert)) {
                    $this->notices('success', 'Milestones added to selected project successfully.');

                } else {
                    $this->notices('error', 'Milestones could not be added to selected project.');

                }
            }
        }

        $this->__listMilestoneGroups();
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
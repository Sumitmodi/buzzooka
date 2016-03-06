<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all reports related data abstraction
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Reports_Model extends Super_Model
{

    var $debug_methods_trail;
    var $number_of_rows;

    // -- __construct ----------------------------------------------------------------------------------------------
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        // Call the Model constructor
        parent::__construct();
    }

    /**
     * Get the report project seo url
     * @param int $project_id
     * @return array
     */
    public function getSeoUrl($project_id, $type)
    {
        $res = $this->db->where('projects_id', $project_id)->where('projects_seo_type', $type)->get('projects');
        if ($res->num_rows() == 0) {
            return false;
        }
        return $res->row_array();
    }

    /**
     * Save the SEO url of the project
     * @param int $project_id
     * @return bool
     */
    public function saveSeoUrl($project_id)
    {
        return $this
            ->db
            ->where('projects_id', $project_id)
            ->update('projects', array('projects_seo_link' => $this->input->post('projects_seo_link', TRUE)));
    }

    // -- searchreports ----------------------------------------------------------------------------------------------
    /**
     * search reports for a particular project or for searched reports (can be any project etc)
     * @return    array
     */

    function searchfiles($offset = 0, $type = 'search', $project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';
        $limiting = '';

        //system page limit or set default 25
        $limit = (is_numeric($this->data['settings_general']['results_limit'])) ? $this->data['settings_general']['results_limit'] : 25;

        //if project_id has been specified, show only for this project
        if (is_numeric($project_id)) {
            $conditional_sql .= " AND reports.reports_project_id = $project_id";
        }

        //create the order by sql additional condition
        //these sorting keys are passed in the url and must be same as the ones used in the controller.
        $sort_order = ($this->uri->segment(6) == 'asc') ? 'asc' : 'desc'; //reverse order of normal desc as default
        $sort_columns = array(
            'sortby_reportid' => 'reports.reports_id',
            'sortby_reportname' => 'reports.reports_name',
            'sortby_projectid' => 'reports.reports_project_id',
            'sortby_downloads' => 'reports.download_count',
            'sortby_reporttype' => 'reports.reports_extension',
            'sortby_uploadedby' => 'reports.reports_uploaded_by_id',
            'sortby_date' => 'reports.reports_date_uploaded',
            'sortby_size' => 'reports.reports_size');
        $sort_by = (array_key_exists('' . $this->uri->segment(7), $sort_columns)) ? $sort_columns[$this->uri->segment(7)] : 'reports.reports_id';
        $sorting_sql = "ORDER BY $sort_by $sort_order";

        //are we searching records or just counting rows
        //row count is used by pagination class
        if ($type == 'search') {
            $limiting = "LIMIT $limit OFFSET $offset";
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT reports.*, client_users.*, team_profile.*,
                                          (SELECT COUNT(reportdownloads_id) FROM reportdownloads
                                                  WHERE reportdownloads.reportdownloads_report_id = reports.reports_id) AS downloads_count,
                                          (SELECT COUNT(report_comments_id) FROM report_comments
                                                  WHERE report_comments.report_comments_report_id = reports.reports_id) AS comments_count
                                          FROM reports
                                          LEFT OUTER JOIN client_users
                                               ON client_users.client_users_id = reports.reports_uploaded_by_id
                                               AND reports.reports_uploaded_by = 'client'
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = reports.reports_uploaded_by_id
                                               AND reports.reports_uploaded_by = 'team'
                                          WHERE 1 = 1
                                          $conditional_sql
                                          $sorting_sql
                                          $limiting");
        //results (search or rows)
        //rows are used by pagination class & results are used by tbs block merge
        if ($type == 'search') {
            $results = $query->result_array();
        } else {
            $results = $query->num_rows();
        }

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;

    }

    // -- addreport ----------------------------------------------------------------------------------------------
    /**
     * add project report to database from post data
     *
     * @return    numeric [insert id]
     */

    function addfile($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }
        $reports_size_human = $this->db->escape($this->data['vars']['reports_size_human']);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO reports (
                                          reports_client_id,
                                          reports_description,
                                          reports_events_id,
                                          reports_extension,
                                          reports_foldername,
                                          reports_name,
                                          reports_project_id,
                                          reports_uploaded_by,
                                          reports_uploaded_by_id,
                                          reports_date_uploaded,
                                          reports_size,
                                          reports_size_human,
                                          reports_time_uploaded
                                          )VALUES(
                                          $reports_client_id,
                                          $reports_description,
                                          $reports_events_id,
                                          $reports_extension,
                                          $reports_foldername,
                                          $reports_name,
                                          $reports_project_id,
                                          $reports_uploaded_by,
                                          $reports_uploaded_by_id,
                                          NOW(),
                                          $reports_size,
                                          $reports_size_human,
                                          NOW())");

        $results = $this->db->insert_id(); //last item insert id

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- superUsers ----------------------------------------------------------------------------------------------
    /**
     * return a array of all users who have edit/delete access for this report
     *
     * @param numeric $report_id
     * @return    array
     */

    function superUsers($report_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric(reports_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [report id=$report_id]", '');
            return false;
        }

        //escape params items
        $report_id = $this->db->escape($report_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT reports.*, projects.*
                                          FROM reports 
                                          LEFT OUTER JOIN projects
                                          ON projects.projects_id = reports.reports_project_id
                                          WHERE reports_id = $report_id");

        $results = $query->row_array(); //single row array

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //create array of users id's
        $users = array($results['reports_uploaded_by_id'], $results['projects_team_lead_id']);
        return $users;
    }

    // -- editreport ----------------------------------------------------------------------------------------------
    /**
     * edit a reports details
     *
     * @param    void
     * @return    numeric [affected rows]
     */

    function editfile()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if task id value exists in the post data
        if (!is_numeric($this->input->post('reports_id'))) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [report id: is not numeric/is unavailable]", '');
            return false;
        }

        //escape all post item
        foreach ($_POST as $key => $value) {
            $$key = $this->db->escape($this->input->post($key));
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("UPDATE reports
                                          SET 
                                          reports_description = $reports_description
                                          WHERE reports_id = $reports_id");

        $results = $this->db->affected_rows(); //affected rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //benchmark/debug
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if (is_numeric($results) || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- deletereport ----------------------------------------------------------------------------------------------
    /**
     * delete a report(s) based on a 'delete_by' id
     *
     * @param numeric $id reference id of item(s)
     * @param   string $delete_by report-id, milestone-id, project-id, client-id
     * @return    bool
     */

    function deletefile($id = '', $delete_by = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [report_id=$id]", '');
            //ajax-log error to report
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting report(s) failed (report_id: $id is invalid)]");
            return false;
        }

        //check if delete_by is valid
        $valid_delete_by = array(
            'report-id',
            'project-id',
            'client-id');

        if (!in_array($delete_by, $valid_delete_by)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [delete_by=$delete_by]", '');
            //ajax-log error to report
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: deleting report(s) failed (delete_by: $delete_by is invalid)]");
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //conditional sql
        switch ($delete_by) {

            case 'report-id':
                $conditional_sql = "AND reports_id = $id";
                break;

            case 'project-id':
                $conditional_sql = "AND reports_project_id = $id";
                break;

            case 'client-id':
                $conditional_sql = "AND reports_client_id = $id";
                break;

            default:
                $conditional_sql = "AND reports_id = '0'"; //safety precaution else we wipe out whole table
                break;

        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("DELETE FROM reports
                                          WHERE 1 = 1
                                          $conditional_sql");

        $results = $this->db->affected_rows(); //affected rows

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        if ($results > 0 || $transaction_result === true) {
            return true;
        } else {
            return false;
        }
    }

    // -- getreport ----------------------------------------------------------------------------------------------
    /**
     * return a single reports record based on its ID
     *
     * @param numeric $id
     * @return    array
     */

    function getfile($id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [report id=$id]", '');
            return false;
        }

        //escape params items
        $id = $this->db->escape($id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT reports.*, client_users.*, team_profile.*,
                                             (SELECT COUNT(reportdownloads_report_id)
                                                     FROM reportdownloads
                                                     WHERE reportdownloads.reportdownloads_report_id = reports.reports_id)
                                                     AS downloads
                                          FROM reports
                                          LEFT OUTER JOIN client_users
                                               ON client_users.client_users_id = reports.reports_uploaded_by_id
                                               AND reports.reports_uploaded_by = 'client'
                                          LEFT OUTER JOIN team_profile
                                               ON team_profile.team_profile_id = reports.reports_uploaded_by_id
                                               AND reports.reports_uploaded_by = 'team'
                                          WHERE reports.reports_id = $id");

        $results = $query->row_array(); //single row array

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- downloadCounter ----------------------------------------------------------------------------------------------
    /**
     * increase the reports download count by 1
     *
     * @param numeric $id
     * @return    numeric [insert id]
     */

    function downloadCounter($id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //if no valie client id, return false
        if (!is_numeric($id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [report id=$id]", '');
            return false;
        }

        //escape params items
        $my_id = $this->data['vars']['my_id'];
        $my_user_type = $this->data['vars']['my_user_type'];
        $project_id = $this->data['vars']['project_id'];
        $client_id = $this->data['vars']['client_id'];

        //validate data
        if (!is_numeric($id) || !is_numeric($my_id) || !is_numeric($project_id) || !is_numeric($client_id) || $my_user_type == '') {
            //log this
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Download counter failed. Invalid input data]");
            //return
            return;
        }

        //escape data
        $id = $this->db->escape($id);
        $my_id = $this->db->escape($my_id);
        $my_user_type = $this->db->escape($my_user_type);
        $project_id = $this->db->escape($project_id);
        $client_id = $this->db->escape($client_id);

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------monitor transaction start----------
        $this->db->trans_start();

        //_____SQL QUERY_______
        $query = $this->db->query("INSERT INTO reportdownloads (
                                          reportdownloads_project_id,
                                          reportdownloads_client_id,
                                          reportdownloads_report_id,
                                          reportdownloads_date,
                                          reportdownloads_user_type
                                          )VALUES(
                                          $project_id,
                                          $client_id,
                                          $id,
                                          NOW(),
                                          $my_user_type)");

        $results = $this->db->insert_id(); //last item insert id

        //----------monitor transaction end----------
        $this->db->trans_complete();
        $transaction_result = $this->db->trans_status();
        if ($transaction_result === false) {

            //log this error
            $db_error = $this->db->_error_message();
            log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Database Error -  $db_error]");

            return false;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //return results
        return $results;
    }

    // -- validateClientOwner ----------------------------------------------------------------------------------------------
    /**
     * confirm if a given client owns this requested item
     *
     * @param numeric $resource_id
     * @param   numeric $client_id
     * @return    bool
     */

    function validateClientOwner($resource_id = '', $client_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //validate id
        if (!is_numeric($resource_id) || !is_numeric($client_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Input Data", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->query("SELECT *
                                          FROM reports 
                                          WHERE reports_id = $resource_id
                                          AND reports_client_id = $client_id");

        $results = $query->num_rows(); //count rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return true;
        } else {
            return false;
        }
    }

    // -- bulkDelete ----------------------------------------------------------------------------------------------
    /**
     * bulk delete based on list of project ID's
     * typically used when deleting project/s
     *
     * @param    string $projects_list a mysql array/list formatted projects list] [e.g. 1,2,3,4]
     * @return    bool
     */

    function bulkDelete($projects_list = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //declare
        $conditional_sql = '';

        //flow control
        $next = true;

        //sanity check - ensure we have a valid projects_list, with only numeric id's
        $lists = explode(',', $projects_list);
        for ($i = 0; $i < count($lists); $i++) {
            if (!is_numeric(trim($lists[$i]))) {
                //log error
                log_message('error', '[report: ' . __FILE__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Bulk Deleting reports, for projects($clients_projects) failed. Invalid projects list]");
                //exit
                return false;
            }
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        if ($next) {
            $query = $this->db->query("DELETE FROM reports
                                          WHERE reports_project_id IN($projects_list)");
        }
        $results = $this->db->affected_rows(); //affected rows

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if (is_numeric($results)) {
            return true;
        } else {
            return false;
        }
    }
}

/* End of report reports_model.php */
/* Location: ./application/models/reports_model.php */

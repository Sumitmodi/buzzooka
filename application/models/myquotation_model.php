<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Myquotation_model extends Super_Model
{

    var $debug_methods_trail; //method profiling

    // -- __construct ----------------------------------------------------------------------------------------------
    /**
     */
    function __construct()
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        // Call the Model constructor
        parent::__construct();
    }


    // -- getQuotation ----------------------------------------------------------------------------------------------
    /**
     * - get a project quotation
     * @param    int [project_id: Project id]
     * @return    array
     */

    function getQuotation($project_id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->where('project_id', $project_id)->join('quotations', 'quotations.quotations_id = project_quotations.quotation_id')->get('project_quotations');

        //another query for files and links
        $res = $this->db->where('quotations_project_id', $project_id)->where('quotations_file_type !=', '')->get('quotations');
        if ($res->num_rows() > 0) {
            $result = $res->result_array();
        }

        //other results
        $results = $query->num_rows() == 0 ? false : $query->result_array(); //multi row array

        if ($results != false && isset($result)) {
            $results = array_merge($results, $result);
        } elseif (isset($result) && false == $results) {
            $results = $result;
        }

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    function getQuotationData($quotation_id)
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($quotation_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [quotation_id=$quotation_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //_____SQL QUERY_______
        $query = $this->db->where('quotations_id', $quotation_id)->get('quotations');

        //other results
        $results = $query->num_rows() == 0 ? false : $query->row_array(); //multi row array

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;
    }

    public function quoteAction($data = array(), $id = 0)
    {
        if ($id == 0) {
            return $this->db->insert('quotations', $data);
        } else {
            return $this->db->where('quotations_id', intval($id))->update('quotations', $data);
        }
    }

    public function getQuote($id)
    {
        $res = $this->db->where('quotations_id', $id)->get('quotations');
        return $res->num_rows() == 0 ? false : $res->row();
    }

    public function unlink_record($row)
    {
        if (is_null($row->quotations_file_type)) {
            return $this->db->where('project_id', $row->quotations_project_id)->delete('project_quotations');
        } else {
            return $this->db->where('quotations_id', $row->quotations_id)->delete('quotations');
        }
    }
}

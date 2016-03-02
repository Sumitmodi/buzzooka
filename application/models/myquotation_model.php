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
     * @param	int [project_id: Project id]
     * @return	array
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
        $query = $this->db->where('project_id',$project_id)->join('quotations','quotations.quotations_id = project_quotations.quotation_id')->get('project_quotations');

        //other results
        $results = $query->num_rows() == 0 ? false : $query->result_array(); //multi row array

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
        $query = $this->db->where('quotations_id',$quotation_id)->get('quotations');

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
}

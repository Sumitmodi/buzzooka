<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Milestone_model extends Super_Model
{

    var $debug_methods_trail; //method profiling
    var $number_of_rows;

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


    // -- getNotes ----------------------------------------------------------------------------------------------
    /**
     * - get a team members notes
     * @param	string [id: team members id]
     * @return	array
     */

    function checkNotes($project_id = '', $id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }
    
    
    // -- getNotes ----------------------------------------------------------------------------------------------
    /**
     * - get a team members notes
     * @param	string [id: team members id]
     * @return	array
     */

    function getNotes($project_id = '', $id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;
        
        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');

        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        return $results;

    }

    // -- newNote ----------------------------------------------------------------------------------------------
    /**
     * - create a new note for a team member
     * @param	string [id: team members id]
     * @return	array
     */

    function newNote($project_id = '', $id = '')
    {

        //profiling::
        $this->debug_methods_trail[] = __function__;

        //validate id
        if (!is_numeric($id) || !is_numeric($project_id)) {
            $this->__debugging(__line__, __function__, 0, "Invalid Data [id=$id] or [project_id=$project_id]", '');
            return false;
        }

        //----------sql & benchmarking start----------
        $this->benchmark->mark('code_start');


        //----------benchmarking end------------------
        $this->benchmark->mark('code_end');
        $execution_time = $this->benchmark->elapsed_time('code_start', 'code_end');

        //debugging data
        $this->__debugging(__line__, __function__, $execution_time, __class__, $results);
        //----------sql & benchmarking end----------

        //---return
        if ($results > 0) {
            return $results;
        } else {
            return false;
        }

    }
    

    function updateNote($project_id = '', $id = '', $notes='')
    {

    }
}

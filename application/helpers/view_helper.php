<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -----------------------------------------------------------------------------------------------------------------
/**
 * Check Template File - Checks if a given template file exists
 * 
 * @param $template path to template file
 * @return if exists: return given file path; if not: returns 404 error
 */
if (! function_exists('help_template_verify')) {
    function help_verify_template($template = '')
    {
        if (is_file($template)) {
            return $template;
        } else {

            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\
            $message = '<b>File: </b>' . __file__ . '<br/>
                        <b>Function: </b>' . __function__ . '<br/>
                        <b>Line: </b>' . __line__ . '<br/>
                        <b>Notes: </b> Template file could not be found (' . $template . ')';
            //log error
            log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Template file could not be found ($template)]");
            //disply error
            show_error($message, 500);
            //---- CODEIGNITER SYSTEM ERROR HANDLING-----\\

        }
    }
}


function prepare_events($thedata)
{
    $ci = get_instance();
    //check if data is not empty
    if (count($thedata) == 0 || !is_array($thedata)) {
        return $thedata;
    }

    /* -----------------------PREPARE FILES DATA ----------------------------------------/
    *  Loop through all the files in this array and for each file:
    *  -----------------------------------------------------------
    *  (1) process user names ('event by' data)
    *  (2) add back the language for the action carried out
    *
    *
    *------------------------------------------------------------------------------------*/
    for ($i = 0; $i < count($thedata); $i++)
    {

        //--team member---------------------
        if ($thedata[$i]['project_events_user_type'] == 'team') {
            $thedata[$i]['user_name'] = $thedata[$i]['team_profile_full_name'];
        }

        //--client user---------------------
        if ($thedata[$i]['project_events_user_type'] == 'client') {
            $thedata[$i]['user_name'] = $thedata[$i]['client_users_full_name'];
        }

        //add back langauge
        $word = $thedata[$i]['project_events_action'];
        $thedata[$i]['project_events_action_lang'] = $ci->data['lang'][$word];

        //add #hash to numbers (e.g invoice number) and create a new key called 'project_events_item'
        if (is_numeric($thedata[$i]['project_events_details'])) {
            $thedata[$i]['project_events_item'] = '#' . $thedata[$i]['project_events_details'];
        } else {
            $thedata[$i]['project_events_item'] = $thedata[$i]['project_events_details'];
        }

    }

    //retun the processed data
    return $thedata;
}

/* End of file view_helper.php */
/* Location: ./application/helpers/view_helper.php */

<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class to fetch a project's quotation
 *
 * @author   Sandeep Giri
 * @access   public
 */
class Myquotation extends MY_Controller
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
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'project.quotation.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_project_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text-alt"></i>';

        //load the quotation model
        $this->load->model('myquotation_model', 'model');

        //load the form builder library
        $this->load->library('formbuilder');
    }

    /**
     * This is our re-routing function and is the inital function called
     */
    function index()
    {

        /* --------------URI SEGMENTS---------------
        * [segment example]
        * /admin/myquotation/2/view/*.*
        * (2)->controller
        * (3)->project_id
        * (4)->router
        ** -----------------------------------------*/

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //login check
        $this->__commonAdmin_LoggedInCheck();

        //javascript allowed files array
        js_allowedFileTypes();

        //javascript file size limit
        js_fileSizeLimit();

        //get project id
        $this->project_id = $this->uri->segment(3);

        //set project_id for global use in template
        $this->data['vars']['project_id'] = $this->project_id;

        //check if project exists & set some basic data
        $this->__commonAll_ProjectBasics($this->project_id);

        //get project events (timeline)
        $this->data['reg_blocks'][] = 'timeline';
        $this->data['blocks']['timeline'] = $this->project_events_model->getEvents($this->project_id);
        $this->data['debug'][] = $this->project_events_model->debug_data;
        //further process events data
        $this->data['blocks']['timeline'] = prepare_events($this->data['blocks']['timeline']);

        //get the action from url
        $action = $this->uri->segment(4);

        //route the request
        switch (strtolower($action)) {
            case 'add':
                $this->__addQuote();
                break;
            case 'view':
                $this->__viewNotes();
                break;
            default:
                $this->__viewNotes();
                break;
        }

        //css - active tab
        $this->data['vars']['css_active_tab_quotation'] = 'side-menu-main-active';

        //load view
        $this->__flmView('admin/main');

    }

    protected function __addQuote()
    {
        $this->load->model('myquotation_model');
        if (isset($_POST['files_link'])) {
            $data = array(
                'quotations_client_id' => $_POST['files_client_id'],
                'quotations_project_id' => $_POST['files_project_id'],
                'quotations_by_client' => 'yes',
                'quotations_form_title' => $_POST['link_title'],
                'quotations_file_description' => $_POST['files_description'],
                'quotations_file_url' => $_POST['files_link'],
                'quotations_file_type' => 'link',
                'quotations_status' => 'completed',
                'quotations_date' => date('Y-m-d H:i:s')
            );
            $this->notices('success', 'Link added');
        } else {
            $data = array(
                'quotations_client_id' => $_POST['files_client_id'],
                'quotations_project_id' => $_POST['files_project_id'],
                'quotations_by_client' => 'yes',
                'quotations_form_title' => $_POST['files_name'],
                'quotations_file_description' => $_POST['files_description'],
                'quotations_file_url' => sprintf('/files/projects/%d/%s/%s', $_POST['files_project_id'], $_POST['files_foldername'], $_POST['files_name']),
                'quotations_file_type' => 'file',
                'quotations_status' => 'completed',
                'quotations_date' => date('Y-m-d H:i:s')
            );
            $this->notices('success', 'File uploaded.');
        }
        $this->myquotation_model->quoteAction($data);
        $this->__viewNotes();
    }

    /**
     * view a members project note
     */
    function __viewNotes()
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //show text view
        $this->data['visible']['wi_my_note_view'] = 1;

        //check if team member has a note for this project
        $quotations = $this->model->getQuotation($this->project_id);

        $this->data['debug'][] = $this->model->debug_data;

        if (!$quotations) {
            //success
            $this->notifications('wi_notification', $this->data['lang']['lang_requested_item_not_loaded']);
            $this->data['visible']['wi_project_quotations'] = 0;
        } else {

            foreach ($quotations as $key => $quo) {
                if (!empty($quo['quotations_file_type'])) {
                    if ($quo['quotations_file_type'] == 'file') {
                        $ext = pathinfo($quo['quotations_file_url'], PATHINFO_EXTENSION);
                        $quotations[$key]['icon'] = site_url(sprintf('/files/filetype_icons/%s.png', $ext));
                        $quotations[$key]['download_url'] = site_url(sprintf('%s', $quo['quotations_file_url']));
                        switch (strtolower($ext)) {
                            case 'jpg':
                            case 'png':
                            case 'jpeg':
                                $quotations[$key]['file_type_id'] = 'images';
                                $quotations[$key]['icon'] = $quotations[$key]['download_url'];
                                break;
                            case 'mp4':
                            case 'swf':
                                $quotations[$key]['file_type_id'] = 'vidoes';
                                break;
                            case 'doc':
                            case 'docx':
                            case 'ppt':
                            case 'pptx':
                            case 'xls':
                            case 'xlsx':
                                $quotations[$key]['file_type_id'] = 'docs';
                                break;
                            case 'pdf':
                                $quotations[$key]['file_type_id'] = 'pdf';
                                break;
                            default:
                                $quotations[$key]['file_type_id'] = 'docs';
                        }
                        $quotations[$key]['open_url'] = site_url(sprintf('admin/myquotation/%d/view/%d', $this->project_id, $quo['quotations_id']));
                    } else {
                        $uri = parse_url($quo['quotations_file_url']);
                        $quotations[$key]['download_url'] = $quo['quotations_file_url'];
                        $quotations[$key]['file_type_id'] = 'links';
                        switch ($uri['host']) {
                            case 'docs.google.com':
                            case 'drive.google.com':
                            case 'www.docs.google.com':
                            case 'www.drive.google.com':
                                $quotations[$key]['icon'] = site_url(sprintf('/files/filetype_icons/%s.png', 'drive'));
                                if ($this->uri->segment(5) == null) {
                                    $this->data['reg_blocks'][] = 'quotation';
                                    $this->data['blocks']['quotation'] = array($quo);
                                    $this->data['vars']['quote_url'] = $this->getQuoteLink($quo);
                                    $this->data['visible']['is_visible_quotationform'] = 2;
                                }
                                break;
                            case 'youtube.com':
                            case 'www.youtube.com':
                                $quotations[$key]['icon'] = site_url(sprintf('/files/filetype_icons/%s.png', 'youtube'));
                                break;
                            default:
                                $quotations[$key]['icon'] = site_url(sprintf('/files/filetype_icons/%s.png', 'link'));
                        }
                        $quotations[$key]['open_url'] = site_url(sprintf('admin/myquotation/%d/view/%d', $this->project_id, $quo['quotations_id']));
                    }
                } else {
                    $quotations[$key]['icon'] = site_url(sprintf('/files/filetype_icons/%s.png', 'file'));
                    $quotations[$key]['download_url'] = site_url(sprintf('admin/quotation/download/%d', $quo['quotations_id']));
                    $quotations[$key]['open_url'] = site_url(sprintf('admin/myquotation/%d/view/%d', $this->project_id, $quo['quotations_id']));
                    $quotations[$key]['file_type_id'] = 'links';
                }
                $quotations[$key]['project_id'] = $this->project_id;
            }

            $this->data['reg_blocks'][] = 'quotations';
            $this->data['blocks']['quotations'] = $quotations;

            //show the quotation
            $this->data['visible']['wi_project_quotations'] = 1;
            $this->data['visible']['wi_quotation_table'] = 1;
        }

        if ($this->uri->segment(5) != null) {
            $quotation = $this->model->getQuotationData($this->uri->segment(5));

            if ($quotation == FALSE) {
                $this->data['visible']['is_visible_quotationform'] = 0;
            } else {
                $this->data['reg_blocks'][] = 'quotation';
                $this->data['blocks']['quotation'] = array($quotation);

                $theform = $quotation['quotations_form_data'];
                $postdata = $quotation['quotations_post_data'];
                if (!is_null($quotation['quotations_file_type'])) {
                    $this->data['reg_blocks'][] = 'quotationform';
                    $this->data['vars']['quote_url'] = $this->getQuoteLink($quotation);
                    $this->data['visible']['is_visible_quotationform'] = 2;
                } else {
                    $this->data['reg_blocks'][] = 'quotationform';
                    $this->data['blocks']['quotationform'] = $this->formbuilder->reBuildForm($theform, $postdata);
                    $this->data['visible']['is_visible_quotationform'] = 1;
                }
            }
        } else {
            $this->data['visible']['is_visible_quotationform'] = 0;
        }
    }

    protected function getQuoteLink($quote)
    {
        $ext = pathinfo($quote['quotations_file_url'], PATHINFO_EXTENSION);
        switch (strtolower($ext)) {
            case 'jpg':
            case 'png':
            case 'jpeg':
                $return = sprintf('<img src="%s" style="width:100%%;"/>', site_url($quote['quotations_file_url']));
                break;
            case 'mp4':
            case 'swf':
                $return = sprintf('<video width="400" controls><source src="%s" type="video/mp4">Your browser does not support HTML5 video.</video>', site_url($quote['quotations_file_url']));
                break;
            case 'doc':
            case 'docx':
            case 'ppt':
            case 'pptx':
            case 'xls':
            case 'xlsx':
                $return = sprintf('<iframe src="https://view.officeapps.live.com/op/view.aspx?src=%s" style="width:100%;min-height:640px; frameborder="0"></iframe>', urlencode(site_url($quote['quotations_file_url'])));
                break;
            case 'pdf':
                $return = sprintf('<iframe src="%s" style="width:100%%;min-height:640px;" frameborder="0"></iframe>', site_url($quote['quotations_file_url']));
                break;
        }
        if ($quote['quotations_file_type'] == 'link') {
            $return = sprintf('<iframe src="%s" style="width:100%%;min-height:640px;" frameborder="0"></iframe>', $quote['quotations_file_url']);
        }
        return $return;
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

/* End of file myquotation.php */
/* Location: ./application/controllers/admin/myquotation.php */

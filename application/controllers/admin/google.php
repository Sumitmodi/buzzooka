<?php
require_once APPPATH . '/third_party/google/autoload.php';

class Google extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'cloud.google.html';

        //css settings
        $this->data['vars']['css_submenu_projects'] = 'style="display:block; visibility:visible;"';
        $this->data['vars']['css_menu_projects'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_my_project_quotation'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-file-text-alt"></i>';

        $this->client = $this->getClient();

    }

    public function index()
    {

        $service = new Google_Service_Drive($this->client);

        $optParams = array();

        $this->results = $service->files->listFiles($optParams);
        $list = $this->results->getItems();

        $files = array();
        $ids  = array();

        if (count($list) == 0) {
            $this->data['vars']['nofiles'] = 1;
        } else {
            $this->data['vars']['nofiles'] = 0;

            foreach ($list as $k => $l) {
                $l = (object)$l;

                if (empty($l->title) || $l->mimeType == 'application/vnd.google-apps.folder') {
                    continue;
                }

                $temp = array(
                    'sn' => $k + 1,
                    'alternate_link' => $l->alternateLink,
                    'created_date' => date('Y-m-d', strtotime($l->createdDate)),
                    'description' => $l->description,
                    'download_link' => $l->downloadUrl,
                    'extension' => $l->fileExtension,
                    'size' => number_format($l->fileSize / 1024 / 1024,2),
                    'icon_link' => $l->iconLink,
                    'id' => $l->id,
                    'file_name' => substr($l->title, 0, 20),
                    'full_name'	=> $l->title,
                    'owner_name' => $l->ownerNames[0],
                    'thumbnail_link' => empty($l->thumbnailLink) ? $l->iconLink : $l->thumbnailLink,
                    'self_link' => $l->selfLink
                );

                if(!empty($l->webContentLink)){
                	$temp['download_url'] = $l->webContentLink;
                	$temp['has_download_url'] = 1;
                } else {
                	$temp['has_download_url'] = 0;
                }

                array_push($files, $temp);
                array_push($ids,$l->id);
            }

            $this->data['reg_blocks'][] = 'files';
            $this->data['files'] = $files;
            $this->data['blocks']['files'] = $files;
        }

        $this->data['vars']['id'] = '43246939368';
        $this->data['vars']['files'] = json_encode($ids);
        $this->data['vars']['token'] = $this->session->userdata('drive-token');
        
        $this->data['vars']['notification'] = $this->session->flashdata('message') ? $this->session->flashdata('message') : 'Files loaded successfully.';
        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View('admin/main');

    }

    private function getClient()
    {
        try {
            $client = new Google_Client();
            $client->setApplicationName(APPLICATION_NAME);
            $client->setScopes(SCOPES);
            $client->setAuthConfigFile(CLIENT_SECRET_PATH);
            $client->setRedirectUri(REDIRECT_URI);
            $client->setAccessType('offline');

            if ($this->session->userdata('drive-token')) {
                $accessToken = $this->session->userdata('drive-token');
            } else {
                $authUrl = $client->createAuthUrl();

                if ($this->input->get('code') == false) {
                    redirect($authUrl);
                    return;
                }

                $accessToken = $client->authenticate($this->input->get('code'));
                $this->session->set_userdata('drive-token', $accessToken);

                redirect('/admin/google-drive');
            }

            $client->setAccessToken($accessToken);

            // Refresh the token if it's expired.
            if ($client->isAccessTokenExpired()) {
                $client->refreshToken($client->getRefreshToken());
                $this->session->set_userdata('drive-token', $client->getAccessToken());
            }

            return $client;
        } catch (Exception $e) {
           $this->session->unset_userdata('drive-token');
           redirect('admin/google-drive');
        }
    }

    public function upload()
    {
        $target_path = "./files";

        if(!isset($_FILES['userfile'])){
        	return redirect('/admin/google-drive');
        }

        $service = new Google_Service_Drive($this->client);
        $files = new Google_Service_Drive_DriveFile();

        $uploaded = 0;
        foreach ($_FILES['userfile']['tmp_name'] as $key => $file) 
        {
	        if (move_uploaded_file($_FILES['userfile']['tmp_name'][$key], $target_path.'/'.$_FILES['userfile']['name'])) 
	        {
	            $files->setTitle($_FILES['userfile']['name'][$key]);

	            $result = $service->files->insert($files, array(
	                'data' => file_get_contents($target_path.'/'.$_FILES['userfile']['name']),
	                'mimeType' => 'application/octet-stream',
	                'uploadType' => 'multipart'
	            ));

	            unlink($target_path.'/'.$_FILES['userfile']['name']);

	            $uploaded++;            
	        } 
        }

        $this->session->set_flashdata('message',$uploaded. ' files were saved to drive.');

        redirect('/admin/google-drive');

    }

    public function logout()
    {
        $this->session->unset_userdata('drive-token');
        redirect('/admin/google-drive');

    }

}
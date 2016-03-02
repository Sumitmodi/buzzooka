<?php
class Tracking extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		//the api base url
		$this->api_url = 'http://www.proranktracker.com/api/';

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //css settings
        $this->data['vars']['css_menu_track'] = 'open'; //menu

        //default page title
        $this->data['vars']['main_title'] = $this->data['lang']['lang_track_api_title'];
        $this->data['vars']['main_title_icon'] = '<i class="icon-paste"></i>';
	}

	public function index()
	{

        //login check
        $this->__commonAdmin_LoggedInCheck();        

        //uri - action segment
        $action = $this->uri->segment(3);
        if(empty($action))
        {
        	$this->__reports();
        } else 
        {
        	$this->__urlReport($action);
        }

        //load view
        /*if($this->uri->segment(3) == 2){
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'tracking-v2.html';
        } else {
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'tracking-iframe.html';
        }*/
        $this->__flmView('admin/main');
	}

	private function get_curl_response($url = null,$body = array())
	{
		$ch = curl_init($url == null ? $this->api_url : $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

		if(!empty($body))
		{
			curl_setopt ($ch, CURLOPT_POST, 1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $body);
		}

		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_USERPWD, TRACK_EMAIL . ":" . TRACK_PASSWORD);
		$res = curl_exec($ch);
		curl_close  ($ch);

		return $res;
	}

	private function __urlReport($url_id)
	{
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'tracking-report.html';

		if(empty($url_id))
		{
			return $this->notices('error', 'URL invalid.');
		}

		$details = json_decode($this->get_curl_response(null,array('cmd'=>'url.get','url_id'=>$url_id)));

		$terms = array();
		foreach ($details->data->terms as $key => $detail) 
		{
			array_push(
				$terms,
				array(
					'sno'			=> $key + 1,
					'term'			=> $detail->name,
					'type'			=> '-',
					'url'			=> !isset($detail->matchedurl) 				? '-' 	: $detail->matchedurl,
					'engine'		=> !isset($detail->engine) 					? '-' 	: $detail->engine,
					'country_code'	=> !isset($detail->country_code) 			? '-' 	: $detail->country_code,
					'city'			=> !isset($detail->city) 					? '-' 	: $detail->city,
					'rank'			=> !isset($detail->rank) 					? '-' 	: $detail->rank,
					'day'			=> !isset($detail->yesterdayrank) 			? '-' 	: $detail->yesterdayrank,
					'week'			=> !isset($detail->weekagorank) 			? '-' 	: $detail->weekagorank,
					'month'			=> !isset($detail->monthagorank) 			? '-' 	: $detail->monthagorank,
					'local'			=> !isset($detail->localmonthlysearches) 	? 0 	: $detail->localmonthlysearches,
					'global' 		=> !isset($detail->globalmonthlysearches) 	? 0 	: $detail->globalmonthlysearches
				)
			);
		}

		$url = $details->data->url;
		
		$urls = json_decode($this->get_curl_response(null,array('cmd'=>'urls.get','include_terms'=>0)),TRUE);
		$info = array();
		foreach ($urls['data']['urls'] as $key => $u) {
			if($u['url'] == $url)
			{	
				foreach ($u as $key => $v) {
					$info[$key] = $v;
				}
				break;
			}
		}

		$info['terms'] = count($terms);
		$info['groups'] = empty($info['groups']) ? 'No groups' : count($info['groups']);
		$info['date'] = date('Y-m-d',strtotime($info['created_at_date']));

		$this->data['reg_blocks'][] = 'terms';
        $this->data['blocks']['terms'] = $terms;
        $this->data['terms'] = $terms;

		$this->data['reg_blocks'][] = 'info';
        $this->data['blocks']['info'] = array($info);
        $this->data['info'] = array($info);
	}

	private function __reports()
	{
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'tracking.html';

		$urls = $this->get_curl_response(null,array('cmd'=>'urls.get','include_terms'=>1));
		if(empty($urls))
		{
			return $this->notices('error', 'No data available.');
		}
		$urls = json_decode($urls);

		$output = array();
		foreach ($urls->data->urls as $key => $url) {
			array_push(
				$output,
				array(
					'sno'	=> $key + 1,
					'id'	=> $url->id,
					'url' => $url->url,
					'date'	=> date('Y-m-d',strtotime($url->created_at_date)),
					'terms'	=> count($url->terms),
					'groups'	=> empty($url->groups) ? 'No groups' : count($url->groups),
					'rank'	=> $url->toprank,
					'updated'	=> $url->last_updated_hours_ago
				)
			);
		}

		$this->data['reg_blocks'][] = 'tracking';
        $this->data['blocks']['tracking'] = $output;
        $this->data['tracking'] = $output;
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
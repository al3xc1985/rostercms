<?php
/**
 * WoWRoster.net WoWRoster
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3.
 * @package    WoWRoster
 */

require_once ROSTER_API . 'tools/Curl.php';
require_once ROSTER_API . 'tools/url.php';
//require_once API_DIR . '/tools/ResourceException.php';
//include_once ROSTER_API . 'tools/HttpException.php';

/**
 * Resource skeleton
 * 
 * @throws ResourceException If no methods are defined.
 */
abstract class Resource {
	/**
	 * API uri for Wow's API
	 */
	//const API_URI = 'http://%s.battle.net/';

	/**
	 * @var string Serve region(`us` or `eu`)
	 */
	protected $region;

	/**
	 * Methods allowed by this resource (or available).
	 *
	 * @var array
	 */
	protected $methods_allowed;

	/**
	 * Curl object instance.
	 *
	 * @var \Curl
	 */
	protected $Curl;
	var $querytime;
	var $query_count = 0;
	public $usage = array(
				'type'				=> '',
				'url'				=> '',
				'responce_code'		=> '',
				'content_type'		=> '',
				'locale'			=> '',
			);
	/**
	 * @throws ResourceException If no methods are allowed
	 * @param string $region Server region(`us` or `eu`)
	 */
	public function __construct($region='us') 
	{
		if (empty($this->methods_allowed)) 
		{
			throw new ResourceException('No methods defined in this resource.');
		}
		$this->region = $region;
		$this->Curl = new Curl();
		$this->url = new url();
	}

	/**
	 * Consumes the resource by method and returns the results of the request.
	 *
	 * @param string $method Request method
	 * @param array $params Parameters
	 * @throws ResourceException If request method is not allowed
	 * @return array Request data
	 */
	public function consume($method, $params=array()) 
	{
		global $roster;
		$makecache = false;
		$msg = '';
		if (!in_array($method, $this->methods_allowed)) 
		{
			$roster->set_message( 'The selected api action is not allowed', 'Method not allowed.', 'error' );
			//throw new ResourceException('Method not allowed.', 405);
		}
		// new prity url builder ... much better then befor...
		$ui = API_URI;//sprintf(self::API_URI, $this->region);
		// new cache system see hwo old teh file is only live update files more then X days/hours old
			
			$this->querytime = format_microtime();
			$roster->db->query_count++;

			$url = $this->url->BuildUrl($ui,$method,$params['server'],$params['name'],$params);
			if ($method == 'auction')
			{
				$data = $this->Curl->makeRequest($url,$params['type'], $params,$url,$method);
				if ($this->Curl->errno !== CURLE_OK) 
				{
					throw new ResourceException($this->Curl->error, $this->Curl->errno);
				}
					$auction = json_decode($data['response'], true);
				$url = $auction['files'][0]['url'];
			}

			$data = $this->Curl->makeRequest($url,null, $params,$url,$method);

			if ($this->Curl->errno !== CURLE_OK) 
			{
				//throw new ResourceException($this->Curl->error, $this->Curl->errno);
				$roster->set_message( "The selected api action is not allowed <br/>\n\r [".$this->Curl->errno.'] : '.$this->Curl->error.'', 'Curl has Failed!', 'error' );
			}
			$errornum = empty($data['response_headers']['http_code']) ? $data['response_headers']['http_code'] : '_911_';			
			//Battle.net returned a HTTP error code
			$x = json_decode($data['response'], true);

			//$makecache
			if (isset($x['reason']))
			{
				$this->seterrors(array('type'=>$method,'msg'=>$x['reason']));
				$msg = $this->transhttpciode($data['response_headers']['http_code']);
				$roster->set_message( ' '.$method.': '.$msg.' : '.$x['reason'].'<br>'.$url.' ', 'Api call fail!', 'error' );
				//$roster->set_message( ' '.$method.': '.$x['reason'].' ', 'Api call fail!', 'error' );
				$this->query['result'] = false; // over ride cache and set to false no data or no url no file lol
			}
			$roster->api2->cache->api_track($method, $url, $this->Curl->http_code, $this->Curl->content_type);
			$this->usage = array(
				'type'				=> $method,
				'url'				=> $url,
				'responce_code'		=> $this->Curl->http_code,
				'content_type'		=> $this->Curl->content_type,
				'locale'			=> $this->region,
			);
			if (method_exists($roster->api2->cache, 'insert'.$method) && is_callable(array($roster->api2->cache, 'insert'.$method)))
			{
				call_user_func(array($roster->api2->cache, 'insert'.$method),$result,$this->usage,$params);
			}
		
		
			//print_r($data['response_headers']);
			$data = json_decode($data['response'], true);
			$info = $data;//$this->utf8_array_decode($data);


		return $info;
	}
	function seterrors($errors)
	{
		$this->errors[] = $errors;
	}


	/**
	 * Returns all errors
	 *
	 * @return string
	 */
	function geterrors()
	{
		return implode("\n",$this->errors) . "\n";
	}


	/**
	 * Resets the stored errors
	 *
	 */
	function reseterrors()
	{
		$this->errors = array();
	}
	public function transhttpciode($code)
	{
		switch ($code)
		{
			case '404':
				return 'A request was made to a resource that doesn\'t exist.';
			break;
			case '500':
				return 'If at first you don\'t succeed, blow it up again';			
			break;
			case '200':
				return 'Access to this API url is Restricted';			
			break;
			case '303':
				return 'Local Cache file used.';			
			break;
			
			default:
			break;
		}	
	}

	/**
	 * Returns the URI for use with the request object
	 *
	 * @param string $method
	 * @return string API URI
	 *
	private function getResourceUri($method) {
		return sprintf(self::API_URI, $this->region) . strtolower(get_class($this)) . '/' . $method;
	}
	*/
}

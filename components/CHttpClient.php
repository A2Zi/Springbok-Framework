<?php
/* http://phpfour.com/blog/2008/01/php-http-class/
 * https://github.com/shuber/curl/blob/master/lib/curl.php */
class CHttpClient{
	public static $MAX_REDIRECT=5,$TIMEOUT=25,$USER_AGENT='Mozilla/5.0 (Ubuntu; X11; Linux x86_64; rv:8.0) Gecko/20100101 Firefox/8.0';
	private $target,$host='',$port=0,$path='',$schema='http',$params=array(),
		$cookies=array(),$_cookies=array(),$referer='',$cookiePath='default',$useCookies=false,$saveCookies=true,
		$username=null,$password=null,
		$result,$headers,$status=0,$redirect=true,$curRedirect=0,$error;

	public function &target($url){$this->target=&$url;return $this;}
	public function &port($port){$this->port=&$port;return $this;}
	//public function &setreferer($url){$this->referer=&$url;return $this;}
	public function &setCookiePath($path){$this->cookiePath=&$path;return $this;}
	public function &params($params){$this->params=&$params;return $this;}
	public function &addParam($name,$value){$this->params[$name]=&$value;return $this;}
	public function &auth($username,$password){$this->username=&$username;$this->password=&$password;return $this;}
	
	public function &addCookie($name,$value){$this->cookies[$name]=&$value;return $this;}
	public function &saveCookies($val){$this->saveCookies=&$val;return $this;}
	public function &followRedirects($value){$this->redirect=&$value;return $this;}
	public function &getResult(){return $this->result;}
	public function &getStatus(){return $this->status;}
	public function &getError(){return $this->error;}
	
	public function get($target,$referer=null){
		return $this->execute($target, $referer,'GET');
	}
	public function post($target,$referer=null){
		return $this->execute($target, $referer,'POST');
	}
	
	private function &execute($target,$referer,$method){
		if($referer!==null) $this->referer=$referer;
		
		$this->status=0;
		
		if(!empty($this->params)){
			$queryString=http_build_query($this->params);
			if($method==='GET') $target.='?'.$queryString;
		}
		$this->target=$target;
		
		$urlParsed = parse_url($target);
		if ($urlParsed['scheme'] == 'https'){
			$this->host = 'ssl://' . $urlParsed['host'];
			if($this->port !==0) $this->port =443;
		}else{
			$this->host = $urlParsed['host'];
			if($this->port !==0) $this->port =80;
		}
		$this->path  = (isset($urlParsed['path']) ? $urlParsed['path'] : '/') . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
		$this->schema = $urlParsed['scheme'];
		if($this->useCookies) $this->_passCookies();
		
		$ch = curl_init();
		if($method==='GET'){
			curl_setopt($ch,CURLOPT_HTTPGET,true);
			curl_setopt($ch,CURLOPT_POST,false);
		}else{
			if(isset($queryString)) curl_setopt($ch,CURLOPT_POSTFIELDS,$queryString);
			 curl_setopt($ch,CURLOPT_HTTPGET,false);
			 curl_setopt($ch,CURLOPT_POST,true);
		}
		if($this->username===null)
			curl_setopt($ch,CURLOPT_USERPWD,$this->username.':'.$this->password);
		
		if($this->useCookies===true){
			if(!empty($this->cookies))
				curl_setopt ($ch,CURLOPT_COOKIE,http_build_query($this->cookies));
			curl_setopt($ch,CURLOPT_COOKIEJAR,APP.'tmp/'.$this->cookiePath); // Cookie management.
		}
		
		curl_setopt($ch,CURLOPT_HEADER,false);
		//curl_setopt($ch,CURLOPT_NOBODY,false);
		curl_setopt($ch,CURLOPT_TIMEOUT,self::$TIMEOUT);
		curl_setopt($ch,CURLOPT_USERAGENT,self::$USER_AGENT); // Webbot name
		curl_setopt($ch,CURLOPT_URL,$this->target); // Target site
		if(!empty($this->referer)) curl_setopt($ch,CURLOPT_REFERER,$this->referer); // Referer value
		
		curl_setopt($ch,CURLOPT_VERBOSE,false); // Minimize logs
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,$this->redirect);
		curl_setopt($ch,CURLOPT_MAXREDIRS,self::$MAX_REDIRECT); // Limit redirections
		//curl_setopt($ch,CURLOPT_RETURNTRANSFER,0); // Return in string
		
		$content=curl_exec($ch);//debug($content);exit;
		//var_dump($target,$this->target,$content,curl_error($ch),curl_getinfo($ch));exit;
		//$contentArray=explode("\r\n\r\n",$content);
		//$cContentArray=count($contentArray);

		$this->status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
		
		// Store the contents
		//$this->result=$contentArray[$cContentArray-1];
		$this->result=&$content;
		//$this->_parseHeaders($contentArray[$cContentArray - 2]);
		$this->error=curl_error($ch);//debug($this->error);
		curl_close($ch);
		return $this->result;
	}

	private function _parseHeaders($responseHeader){
		$headers=explode("\r\n",$responseHeader);
		$this->headers=array();
		if($this->status===0){
			if(!preg_match("#^http/[0-9]+\\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)\$#",$headers[0],$matches)){
				$this->error='Unexpected HTTP response status';
				return false;
			}
			$this->status = $matches[1];
			array_shift($headers);
		}
		return;
		foreach ($headers as $header){
			$headerName  = strtolower($this->_tokenize($header,':'));
			$headerValue = trim(chop($this->_tokenize("\r\n")));
			if(isset($this->headers[$headerName])){
				if(is_string($this->headers[$headerName])) $this->headers[$headerName] = array($this->headers[$headerName]);
				$this->headers[$headerName][] = $headerValue;
			}else $this->headers[$headerName] = $headerValue;
		}
		if($this->saveCookie && isset($this->headers['set-cookie']))
			$this->_parseCookie();
	}
	
	private function _parseCookie(){
		// !empty($this->headers['set-cookie'])
		if(is_array($this->headers['set-cookie']))
			$cookieHeaders = $this->headers['set-cookie'];
		else $cookieHeaders = array($this->headers['set-cookie']);
		
		for ($cookie = 0; $cookie < count($cookieHeaders); $cookie++){
			$cookieName  = trim($this->_tokenize($cookieHeaders[$cookie], "="));
			$cookieValue = $this->_tokenize(";");
			$urlParsed   = parse_url($this->target);
			$domain      = $urlParsed['host'];
			$secure      = '0';
			$path        = "/";
			$expires     = "";
			
			while(($name = trim(urldecode($this->_tokenize("=")))) != "")
            {
                $value = urldecode($this->_tokenize(";"));
                
                switch($name)
                {
                    case "path"     : $path     = $value; break;
                    case "domain"   : $domain   = $value; break;
                    case "secure"   : $secure   = ($value != '') ? '1' : '0'; break;
                }
            }
            
            $this->_setCookie($cookieName, $cookieValue, $expires, $path , $domain, $secure);
		}
	}

	private function _setCookie($name, $value, $expires = "" , $path = "/" , $domain = "" , $secure = 0){
        if(strlen($name) == 0)
        {
            return($this->_setError("No valid cookie name was specified."));
        }

        if(strlen($path) == 0 || strcmp($path[0], "/"))
        {
            return($this->_setError("$path is not a valid path for setting cookie $name."));
        }
            
        if($domain == "" || !strpos($domain, ".", $domain[0] == "." ? 1 : 0))
        {
            return($this->_setError("$domain is not a valid domain for setting cookie $name."));
        }
        
        $domain = strtolower($domain);
        
        if(!strcmp($domain[0], "."))
        {
            $domain = substr($domain, 1);
        }
            
        $name  = $this->_encodeCookie($name, true);
        $value = $this->_encodeCookie($value, false);
        
        $secure = intval($secure);
        
        $this->_cookies[] = array( "name"      =>  $name,
                                   "value"     =>  $value,
                                   "domain"    =>  $domain,
                                   "path"      =>  $path,
                                   "expires"   =>  $expires,
                                   "secure"    =>  $secure
                                 );
    }

function _encodeCookie($value, $name)
    {
        return($name ? str_replace("=", "%25", $value) : str_replace(";", "%3B", $value));
    }
	 function _passCookies()
    {
        if (is_array($this->_cookies) && count($this->_cookies) > 0)
        {
            $urlParsed = parse_url($this->target);
            $tempCookies = array();
            
            foreach($this->_cookies as $cookie)
            {
                if ($this->_domainMatch($urlParsed['host'], $cookie['domain']) && (0 === strpos($urlParsed['path'], $cookie['path']))
                    && (empty($cookie['secure']) || $urlParsed['protocol'] == 'https')) 
                {
                    $tempCookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
                }
            }
            
            // cookies with longer paths go first
            foreach ($tempCookies as $name => $values) 
            {
                krsort($values);
                foreach ($values as $value) 
                {
                    $this->addCookie($name, $value);
                }
            }
        }
    }
	 function _domainMatch($requestHost, $cookieDomain)
    {
        if ('.' != $cookieDomain{0}) 
        {
            return $requestHost == $cookieDomain;
        } 
        elseif (substr_count($cookieDomain, '.') < 2) 
        {
            return false;
        } 
        else 
        {
            return substr('.'. $requestHost, - strlen($cookieDomain)) == $cookieDomain;
        }
    }
	 function _tokenize($string, $separator = '')
    {
        if(!strcmp($separator, ''))
        {
            $separator = $string;
            $string = $this->nextToken;
        }
        
        for($character = 0; $character < strlen($separator); $character++)
        {
            if(gettype($position = strpos($string, $separator[$character])) == "integer")
            {
                $found = (isset($found) ? min($found, $position) : $position);
            }
        }
        
        if(isset($found))
        {
            $this->nextToken = substr($string, $found + 1);
            return(substr($string, 0, $found));
        }
        else
        {
            $this->nextToken = '';
            return($string);
        }
    }
}

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package ActionWebservice
 * @subpackage WebClient
 * @author Bermi Ferrer
 */
/*

class AkHttpClient extends AkObject
{
    public $HttpRequest;
    public $error;
    public $Response;
    private $_cookie_path;
    private $_cookie_jar = 'default';

    public function get($url, $options = array())
    {
        return $this->customRequest($url, 'GET', $options);
    }

    public function post($url, $options = array(), $body = '')
    {
        return $this->customRequest($url, 'POST', $options, $body);
    }

    public function put($url, $options = array(), $body = '')
    {
        return $this->customRequest($url, 'PUT', $options, $body);
    }

    public function delete($url, $options = array())
    {
        return $this->customRequest($url, 'DELETE', $options);
    }

    // prefix_options, query_options = split_options(options)

    public function customRequest($url, $http_verb = 'GET', $options = array(), $body = '')
    {
        $this->getRequestInstance($url, $http_verb, $options, $body);
        return empty($options['cache']) ? $this->sendRequest() : $this->returnCustomRequestFromCache($url,$options);
    }

    public function returnCustomRequestFromCache($url, $options)
    {
        $Cache = Ak::cache();
        $Cache->init(is_numeric($options['cache']) ? $options['cache'] : 86400, !isset($options['cache_type']) ? 1 : $options['cache_type']);
        if (!$data = $Cache->get('AkHttpClient_'.md5($url))) {
            $data = $this->sendRequest();
            $Cache->save($data);
        }
        return $data;
    }

    public function urlExists($url)
    {
        $this->getRequestInstance($url, 'GET');
        $this->sendRequest(false);
        return $this->code == 200;
    }

    public function getRequestInstance($url, $http_verb = 'GET', $options = array(), $body = '')
    {
        $default_options = array(
        'header' => array(),
        'params' => array(),
        );

        $options = array_merge($default_options, $options);

        $options['header']['user-agent'] = empty($options['header']['user-agent']) ?
        'Akelos PHP Framework AkHttpClient (http://akelos.org)' : $options['header']['user-agent'];

        list($user_name, $password) = $this->_extractUserNameAndPasswordFromUrl($url);

        require_once(AK_VENDOR_DIR.DS.'pear'.DS.'HTTP'.DS.'Request.php');

        $this->{'_setParamsFor'.ucfirst(strtolower($http_verb))}($url, $options['params']);

        $this->HttpRequest = new HTTP_Request($url);

        $user_name ? $this->HttpRequest->setBasicAuth($user_name, $password) : null;

        $this->HttpRequest->setMethod(constant('HTTP_REQUEST_METHOD_'.$http_verb));

        if(!empty($body)){
            $this->setBody($body);
        }elseif ($http_verb == 'PUT' && !empty($options['params'])){
            $this->setBody($options['params']);
        }

        !empty($options['params']) && $this->addParams($options['params']);

        isset($options['cookies']) &&  $this->addCookieHeader($options, $url);

        $this->addHeaders($options['header']);

        return $this->HttpRequest;
    }

    public function addHeaders($headers)
    {
        foreach ($headers as $k=>$v){
            $this->addHeader($k, $v);
        }
    }

    public function addHeader($name, $value)
    {
        $this->HttpRequest->removeHeader($name);
        $this->HttpRequest->addHeader($name, $value);
    }

    public function getResponseHeader($name)
    {
        return $this->HttpRequest->getResponseHeader($name);
    }

    public function getResponseHeaders()
    {
        return $this->HttpRequest->getResponseHeader();
    }

    public function getResponseCode()
    {
        return $this->HttpRequest->getResponseCode();
    }

    public function addParams($params = array())
    {
        if(!empty($params)){
            foreach (array_keys($params) as $k){
                $this->HttpRequest->addPostData($k, $params[$k]);
            }
        }
    }

    public function setBody($body)
    {
        Ak::compat('http_build_query');
        $this->HttpRequest->setBody(http_build_query((array)$body));
    }

    public function sendRequest($return_body = true)
    {
        $this->Response = $this->HttpRequest->sendRequest();
        $this->code = $this->HttpRequest->getResponseCode();
        $this->persistCookies();
        if (PEAR::isError($this->Response)) {
            $this->error = $this->Response->getMessage();
            return false;
        } else {
            return $return_body ? $this->HttpRequest->getResponseBody() : true;
        }
    }


    public function addCookieHeader(&$options, $url)
    {
        if(isset($options['cookies'])){
            $url_details = parse_url($url);
            $jar = Ak::sanitize_include((empty($options['jar']) ? $this->_cookie_jar : $options['jar']), 'paranoid');
            $this->setCookiePath(AK_TMP_DIR.DS.'cookies'.DS.$jar.DS.Ak::sanitize_include($url_details['host'],'paranoid'));
            if($options['cookies'] === false){
                $this->deletePersistedCookie();
                return;
            }
            if($cookie_value = $this->getPersistedCookie()){
                $this->_persisted_cookie = $cookie_value;
                $options['header']['cookie'] = $cookie_value;
            }
        }
    }

    public function setCookiePath($path)
    {
        $this->_cookie_path = $path;
    }

    public function getPersistedCookie()
    {
        if(file_exists($this->_cookie_path)){
            return Ak::file_get_contents($this->_cookie_path);
        }
        return false;
    }

    public function deletePersistedCookie()
    {
        if(file_exists($this->_cookie_path)){
            Ak::file_delete($this->_cookie_path);
            $this->_cookie_path = false;
            return;
        }
        return false;
    }

    public function persistCookies()
    {
        if($this->_cookie_path){
            $cookies_from_response = $this->HttpRequest->getResponseCookies();
            if(!empty($this->_persisted_cookie)){
                $this->HttpRequest->_cookies = array();
                $persisted_cookies = $this->HttpRequest->_response->_parseCookie($this->_persisted_cookie);
                $this->HttpRequest->_cookies = $cookies_from_response;
            }
            if(!empty($cookies_from_response)){
                $all_cookies = array_merge(isset($persisted_cookies)?$persisted_cookies:array(), $cookies_from_response);
                $cookies = array();
                foreach($all_cookies as $cookie){
                    if(!empty($cookie['value'])){
                        $cookies[$cookie['name']] = "{$cookie['name']}={$cookie['value']}";
                    }
                }
                $cookie_string = trim(join($cookies, '; '));
                Ak::file_put_contents($this->_cookie_path, $cookie_string);
            }
        }
    }

    private function _extractUserNameAndPasswordFromUrl(&$url)
    {
        return array(null,null);
    }

    public function getParamsOnUrl($url)
    {
        $parts = parse_url($url);
        if($_tmp = (empty($parts['query']) ? false : $parts['query'])){
            unset($parts['query']);
            $url = $this->_httpRenderQuery($parts);
        }
        $result = array();
        !empty($_tmp) && parse_str($_tmp, $result);
        return $result;
    }

    public function getUrlWithParams($url, $params)
    {
        $parts = parse_url($url);
        Ak::compat('http_build_query');
        $parts['query'] = http_build_query($params);
        return $this->_httpRenderQuery($parts);
    }

    private function _setParamsForGet(&$url, &$params)
    {
        $url_params = $this->getParamsOnUrl($url);
        if(!count($url_params) && !empty($params)){
            $url = $this->getUrlWithParams($url, $params);
        }else{
            $params = $url_params;
        }
    }

    private function _setParamsForPost(&$url, &$params)
    {
        empty($params) && $params = $this->getParamsOnUrl($url);
    }

    private function _setParamsForPut(&$url, &$params)
    {
        empty($params) && $params = $this->getParamsOnUrl($url);
    }

    private function _setParamsForDelete(&$url, &$params)
    {
        if(!$this->getParamsOnUrl($url) && !empty($params)){
            $url = $this->getUrlWithParams($url, $params);
        }
    }

    private function _httpRenderQuery($parts)
    {
        return is_array($parts) ? (
        (isset($parts['scheme']) ? $parts['scheme'].':'.((strtolower($parts['scheme']) == 'mailto') ? '' : '//') : '').
        (isset($parts['user']) ? $parts['user'].(isset($parts['pass']) ? ':'.$parts['pass'] : '').'@' : '').
        (isset($parts['host']) ? $parts['host'] : '').
        (isset($parts['port']) ? ':'.$parts['port'] : '').
        (isset($parts['path'])?((substr($parts['path'], 0, 1) == '/') ? $parts['path'] : ('/'.$parts['path'])):'').
        (isset($parts['query']) ? '?'.$parts['query'] : '').
        (isset($parts['fragment']) ? '#'.$parts['fragment'] : '')
        ) : false;
    }
}
*/

?>
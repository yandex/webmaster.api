<?php
/**
 * Class webmaster_api
 *
 * Let me speak from my heart!
 *
 * @author Dmitriy V. Popov <dima@subdomain.ru>
 *
 *
 */
class webmasterApi
{

    /**
     * Access token to Webmaster Api
     * @var string
     */
    private $accessToken = '';

    /**
     * Url of webmaster API
     *
     * @var string
     */
    private $apiUrl= 'https://api.webmaster.yandex.net/v3';

    /**
     * UserID in webmaster
     *
     * @var int
     */
    public $userID = null;


    /**
     * Last error message
     *
     * @var string
     */
    public $lastError = '';


    /**
     * webmasterApi constructor.
     *
     * @param $accessToken string
     */
    function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
        $this->userID = $this->getUserID();
        if(isset($this->userID->error_message))
        {
            $this->errorCritical($this->userID->error_message);
            $this->userID = null;
        }

    }


    static function initApi($accessToken)
    {
        $wmApi = new self($accessToken);
        if(!empty($wmApi->lastError))
        {
            return (object) array('error_message'=>$wmApi->lastError);
        }
        return $wmApi;
    }


    /**
     * Convert post-data array to string
     *
     * Make query string from post-data array
     *
     * @param $data array
     * @return string
     */
    private function dataToString($data)
    {
        $new_data = array();
        foreach ($data as $param=>$value)
        {
            if(is_string($value)) $new_data[] = urlencode($param)."=".urlencode($value);
            elseif (is_array($value))
            {
                foreach ($value as $value_item)
                {
                    $new_data[] = urlencode($param)."=".urlencode($value_item);
                }
            }
        }
        return implode("&",$new_data);
    }


    public function getApiUrl($resource)
    {
        $apiurl = $this->apiUrl;
        if($resource!=='/user/')
        {

            if(!$this->userID) return $this->errorWarning("Can't get hand ".$resource." without userID");
            $apiurl .= "/user/" . $this->userID;
        }
        return $apiurl.$resource;
    }


    /**
     * Get request to hand
     *
     * @param $resource string Name of api resource
     * @param $data array Array with request params (useful to CURLOPT_POSTFIELDS: http://php.net/curl_setopt )
     *
     * @return json
     */
    protected function get($resource, $data=array())
    {
        $apiurl = $this->getApiUrl($resource);

        $headers = array("Authorization: OAuth ".$this->accessToken,"Accept: application/json","Content-type: application/json");

        $url = $apiurl."?".$this->dataToString($data);

        // Шлем запрос в курл
        $ch = curl_init($url);

        // нужно вернуть результат
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // передаем заголовки
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $curl_error =curl_error($ch);
        curl_close($ch);

        if (!$response) return $this->errorCritical('Error in curl when get ['.$url.'] '.$curl_error);
        $response = json_decode($response);


        if (!is_object($response)) return $this->errorCritical('Unkonwn error in response: Not object given');
        return $response;
    }


    /**
     * Post data to hand
     *
     * @param $resource string Name of api resource
     * @param $data array Array with request params (useful to CURLOPT_POSTFIELDS: http://php.net/curl_setopt )
     * @return false|JsonSerializable
     */
    protected function post($resource,$data)
    {
        $url = $this->getApiUrl($resource);

        $headers = array("Authorization: OAuth ".$this->accessToken,"Accept: application/json","Content-type: application/json");





        // Шлем запрос в курл
        $ch = curl_init($url);
        $data_json = json_encode($data);

        // нужно вернуть результат
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // передаем заголовки
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data_json);
        $response = curl_exec($ch);

        curl_close($ch);


        if (!$response) return $this->errorCritical('Unkonwn error in curl');
        $response = json_decode($response);

        if (!is_object($response)) return $this->errorCritical('Unkonwn error in curl');
        return $response;
    }



    /**
     * Delete data from hand
     *
     * @param $resource string Name of api resource
     * @param $data array Array with request params (useful to CURLOPT_POSTFIELDS: http://php.net/curl_setopt )
     * @return false|JsonSerializable
     */
    protected function delete($resource,$data=array())
    {
        $headers = array("Authorization: OAuth ".$this->accessToken,"Accept: application/json","Content-type: application/json");



        $url = $this->getApiUrl($resource);


        // Шлем запрос в курл
        $ch = curl_init($url);
        $data_json = json_encode($data);

        // нужно вернуть результат
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // передаем заголовки
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data_json);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);




        if($httpCode=='204') return (object) array(true);

        if (!$response) return $this->errorCritical('Unkonwn error in curl');
        $response = json_decode($response);

        if (!is_object($response)) return $this->errorCritical('Unkonwn error in curl');
        return $response;
    }



    /**
     * Save error message and return false
     *
     * @param $message string Text of message
     * @param $json boolean return false as json error
     *
     * @return false|object
     */
    private function errorCritical($message, $json = true)
    {
        $this->lastError = $message;
        if($json)
        {
            return (object) array('error_code'=>'CRITICAL_ERROR','error_message'=>$message);
        }
        return false;
    }


    /**
     * Save error message and return false
     *
     * @param $message string Text of message
     * @return false
     */
    private function errorWarning($message, $json = true)
    {
        $this->lastError = $message;
        if($json)
        {
            return (object) array('error_code'=>'CRITICAL_ERROR','error_message'=>$message);
        }
        return false;
    }



    /**
     *
     * Get User ID for current access token
     *
     * @return int|false
     */
    private function getUserID()
    {
        $ret = $this->get('/user/');
        if(!isset($ret->user_id)||!intval($ret->user_id))
        {
            $mes = "Can't resolve USER ID";
            if(isset($ret->error_message)) $mes.= ". ".$ret->error_message;
            return $this->errorCritical($mes);
        }
        return (int)$ret->user_id;
    }


    /**
     * Add new host
     *
     * @param $url
     * @return object Json
     */
    public function addHost($url)
    {
        $ret = $this->post('/hosts/',array("host_url"=>$url));
        return $ret;
    }


    /**
     * Delete host from webmaster
     *
     * @param $hostID
     * @return Json
     */
    public function deleteHost($hostID)
    {
        $ret = $this->delete('/hosts/'.$hostID."/");
        return $ret;
    }


    /**
     * Get host list
     *
     * @return object Json
     */
    public function getHosts()
    {
        $ret = $this->get('/hosts/');

        return $ret;
    }


    public function checkVerification($hostID)
    {
        $ret = $this->get('/hosts/'.$hostID.'/verification/');
        return $ret;
    }


    public function verifyHost($hostID, $type)
    {

        $ret = $this->post('/hosts/'.$hostID.'/verification/?verification_type='.$type,array());
        return $ret;
    }


    /**
     * Get host info
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function getHostInfo($hostID)
    {
        $ret = $this->get('/hosts/'.$hostID."/");

        return $ret;
    }


    /**
     * Get host summary info
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function getHostSummary($hostID)
    {
        $ret = $this->get('/hosts/'.$hostID."/summary/");

        return $ret;
    }

    /**
     * Get host ownerss
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function getHostOwners($hostID)
    {
        $ret = $this->get('/hosts/'.$hostID."/owners/");

        return $ret;
    }

    /**
     * Get host sitemaps
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function getHostSitemaps($hostID, $parentID=null)
    {

        $get = array();
        if($parentID)
        {
            $get['parent_id']=$parentID;
        }

        $ret = $this->get('/hosts/'.$hostID."/sitemaps/",$get);

        return $ret;
    }

    /**
     * Get host user-added sitemaps
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function addSitemap($hostID, $url)
    {

        $ret = $this->post('/hosts/'.$hostID."/user-added-sitemaps/",array("url"=>$url));

        return $ret;
    }

    /**
     * Delete host user-added sitemap
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function deleteSitemap($hostID, $sitemap_id)
    {

        $ret = $this->delete('/hosts/'.$hostID."/user-added-sitemaps/".$sitemap_id."/");

        return $ret;
    }


    /**
     * Add sitemap
     *
     * @param $hostid string Host id in webmaster
     *
     * @return object Json
     */
    public function getHostUserSitemaps($hostID)
    {
        $ret = $this->get('/hosts/'.$hostID."/user-added-sitemaps/");

        return $ret;
    }


    /**
     * Get original texts from host
     *
     * @param $hostid string Host id in webmaster
     * @param $offset int
     * @param $limit int
     *
     * @return object Json
     */
    public function getOriginalTexts($hostID,$offset=0,$limit=100)
    {
        $ret = $this->get('/hosts/'.$hostID."/original-texts/",array("offset"=>$offset,"limit"=>$limit));

        return $ret;
    }


    /**
     * Get Indexing history
     *
     * @param $hostid string Host id in webmaster
     * @param $indexing_indicators array('DOWNLOADED','EXCLUDED','SEARCHABLE',...)
     * @param $date_from time
     * @param $date_to time
     *
     * @return object Json
     */
    public function getIndexingHistory($hostID,$indexing_indicators=array('DOWNLOADED','EXCLUDED','SEARCHABLE',),$date_from=null,$date_to=null)
    {
        if(!$date_from) $date_from = time()-1209600;
        $date_to = time();
        $ret = $this->get('/hosts/'.$hostID."/indexing-history/",array("indexing_indicator"=>$indexing_indicators,'date_from'=>date(DATE_ISO8601,$date_from),'date_to'=>date(DATE_ISO8601,$date_to)));

        return $ret;
    }


    /**
     * Get Tic history
     *
     * @param $hostid string Host id in webmaster
     * @param $date_from time
     * @param $date_to time
     *
     * @return object Json
     */
    public function getTicHistory($hostID,$date_from=null,$date_to=null)
    {
        if(!$date_from) $date_from = time()-1209600;
        $date_to = time();
        $ret = $this->get('/hosts/'.$hostID."/tic-history/",array('date_from'=>date(DATE_ISO8601,$date_from),'date_to'=>date(DATE_ISO8601,$date_to)));

        return $ret;
    }



    /**
     * Get TOP-500 popular queries from host
     *
     * @param $hostid string Host id in webmaster
     * @param $order_by TOTAL_CLICKS|TOTAL_SHOWS
     * @param $indicators array('TOTAL_SHOWS','TOTAL_CLICKS','AVG_SHOW_POSITION','AVG_CLICK_POSITION')
     *
     * @return object Json
     */
    public function getPopularQueries($hostID,$order_by='TOTAL_CLICKS',$indicators=array())
    {
        $ret = $this->get('/hosts/'.$hostID."/search-queries/popular/",array("order_by"=>$order_by,"query_indicator"=>$indicators));

        return $ret;
    }


    /**
     * Add new original text to host
     *
     * @param $hostid string Host id in webmaster
     * @param $content string Text to add
     *
     * @return object Json
     */
    public function addOriginalText($hostID,$content)
    {
        $ret = $this->post('/hosts/'.$hostID."/original-texts/",array("content"=>$content));

        return $ret;
    }



    /**
     * Delete existing original text from host
     *
     * @param $hostid string Host id in webmaster
     * @param $content string Text to add
     *
     * @return object Json
     */
    public function deleteOriginalText($hostID,$text_id)
    {
        $ret = $this->delete('/hosts/'.$hostID."/original-texts/".urlencode($text_id)."/");


        return $ret;
    }



    /**
     * Get Access token by code and client secret
     *
     * How to use:
     * 1. Go to https://oauth.yandex.ru/client/new
     * 2. Type name of programm
     * 3. Select "Яндекс.Вебмастер" in rules section
     * 4. Select both checkboxes
     * 5. In Callback url write: "https://oauth.yandex.ru/verification_code"
     * 6. Save it
     * 7. Remember your client ID and client Secret
     * 8. Go to https://oauth.yandex.ru/authorize?response_type=code&client_id=[Client_ID]
     * 9. Remember your code
     * 10. Use this function to get access token
     * 11. Remember it
     * 12. Enjoy!
     *
     *
     * @deprecated This function is deprecated. It's only for debug
     *
     *
     * @param $code
     * @param $client_id
     * @param $client_secret
     * @return object
     */
    static function getAccessToken($code, $client_id, $client_secret)
    {
        $postData = array("grant_type" => "authorization_code", "code" => $code, "client_id" => $client_id, "client_secret" => $client_secret);

        $ch = curl_init('https://oauth.yandex.ru/token');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) die('Unkonwn error in curl');

        $response = json_decode($response);

        if (!is_object($response)) die('Unkonwn error in curl');


        return $response;
    }
}





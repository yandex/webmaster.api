  <?php
  /**
   * PHP-SDK to Yandex Webmaster Api
   *
   * Let me speak from my heart! please
   *
   *
   *
   *
   */


  /**
   * Class webmaster_api
   *
   * Класс является SDK к апи Яндекс.Вебмастера (api.webmaster.yandex.net).
   * Обратите внимание: В случае возникновения ошибок в некоторых ситуациях кидается сообщение в стандартный поток ошибок PHP
   *
   * Во всех случаях - методы возвращают объект, и в случае возникнования ошибки у объекта есть непустое свойство error_message и error_code, на которые нужно смотреть.
   * Так ведет себя API вебмастера, такое же поведение стараемся эмулировать в случае ошибок на уровне класса...
   *
   * Т.е., типичный вызов метода класса выглядит так:
   *
   * $hostSummary = $wmApi->getHostSummary($hostID);
   * if(!empty($hostSummary->error_code))
   * {
   *      обрабатываем ситуацию с ошибкой
   * } else
   * {
   *      обрабатываем ситуацию когда все хорошо
   * }
   *
   *
   * !! Обратите внимание!!
   * Никогда не зашивайте в коде своих программ ID хостов, пользователей и других объектов: Яндекс.Вебмастер имеет право изменить этот формат и они перестанут работать.
   * Тем более не пытайтесь самостоятельно генерировать эти ID - получайте их через функцию getHosts.
   *
   * @author Dmitriy V. Popov <dima@subdomain.ru>
   * @copyright Yandex LLC
   */
  class webmasterApi
  {

      /**
       * Access token to Webmaster Api
       *
       * Свойство заполняется при инициализации объекта
       *
       *
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
       *
       * User trigger errors
       *
       * Передавать ли возникающие ошибки в стандартный поток ошибок/
       *
       * @var boolean
       */
      public $triggerError = true;


      /**
       * webmasterApi constructor.
       *
       * Инициализирует класс работы с апи. Необходимо передать acceetoken, полученный на oauth-сервере Яндекс.
       * Обратите внимание на статический метод getAccessToken(), которую можно использовать для его получения
       *
       * @param $accessToken string access token from Yandex ouath serverh
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


      /**
       * webmasterApi true constructor.
       *
       * Коорректный способ создания объектов класса: При ошибке возвращает объект со стандартными ошибками.
       *
       * @param $accessToken string
       *
       * @return webmasterApi
       */
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
       * Send HTTP request
       *
       * Функция отправки http запросов.
       *
       * @param $url string
       * @param $headers array
       * @param $customRequest string
       * @param $post boolean
       * @param $postFields string or array
       * @return object
       */
      static function MakeRequest($url, $headers, $customRequest = null, $post = null, $postFields = null)
      {
          if (function_exists('curl_init')) {
            // Используем curl
            $ch = curl_init($url);
            self::CurlOpts($ch);
            if(isset($headers)){
              curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if(isset($customRequest)){
              curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "".$customRequest."");
            }
            if($post){
              curl_setopt($ch,CURLOPT_POST, 1);
            }
            if(isset($postFields)){
              curl_setopt($ch,CURLOPT_POSTFIELDS,$postFields);
            }
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if($httpCode=='204') return (object) array(true);
            if (!$response) return self::errorCritical('Error in curl when get ['.$url.'] '.$curl_error);
            $response = json_decode($response);
            if (!is_object($response)) return self::errorCritical('Unknown error in response: Not object given');
            return $response;
          } else {
            // Используем сокеты
            $fp = fsockopen("ssl://".parse_url($url, PHP_URL_HOST), 443, $errno, $errstr, 10);
            if (!$fp) {
              return self::errorCritical("$errstr($errno)");
            } else {
              if(!isset($customRequest)){
                $customRequest = ($post<>1) ? "GET" : "POST";
              }
              $request = $customRequest." /".preg_replace("/https:\/\/.*?(\/)/", "", $url)." HTTP/1.0\r\n";
              $request .= "Host: ".parse_url($url, PHP_URL_HOST)."\r\n";

            if(isset($headers)){
              foreach ($headers as $value) {
                $request .= $value."\r\n";
              }
            }

            if($post){
              if(is_array($postFields)){
                $postFields = http_build_query($postFields);
              }
              $request .= "Content-Length: ".strlen($postFields)."\r\n";
              $request .= "\r\n";
              $request .= $postFields."\r\n\r\n";
            }else{
              $request .= "Connection: Close\r\n\r\n";
            }
            fputs($fp, $request);
            while (!feof($fp)) {
              $result .= fgets ($fp,128);
            }
            fclose ($fp);
            $result = explode("\r\n\r\n", $result, 2);
            return json_decode($result[1]);
            }
          }
      }

      /**
       * Get handler url for this resource
       *
       * Простая обертка, возвращающая правильный путь к ручке API
       * На самом деле все что она делает - дописывает /user/userID/, кроме, непосредственно, ручки /user/
       *
       * @param $resource string
       * @return string
       */
      public function getApiUrl($resource)
      {
          $apiurl = $this->apiUrl;
          if($resource!=='/user/')
          {
              if(!$this->userID) return $this->errorCritical("Can't get hand ".$resource." without userID");
              $apiurl .= "/user/" . $this->userID;
          }
          return $apiurl.$resource;
      }


      /**
       * Get request to hand
       *
       * Выполнение простого GET-запроса к ручке API.
       * В случае если переда массив $data - его значения будут записаны в запрос. Подробнее об этом массиве см. в описании
       * метода dataToString
       *
       *
       * @param $resource string Name of api resource
       * @param $data array Array with request params (useful to CURLOPT_POSTFIELDS: http://php.net/curl_setopt )
       *
       * @return object
       */
      protected function get($resource, $data=array())
      {
          $apiurl = $this->getApiUrl($resource);

          $headers = array("Authorization: OAuth ".$this->accessToken,"Accept: application/json","Content-type: application/json");

          $url = $apiurl."?".$this->dataToString($data);
          return $this->makeRequest($url, $headers);
      }


      /**
       * Post data to hand
       *
       * Выполнение POST-запроса к ручке API. Массив data передается в API как json-объект
       *
       * @param $resource string Name of api resource
       * @param $data array Array with request params (useful to CURLOPT_POSTFIELDS: http://php.net/curl_setopt )
       * @return false|JsonSerializable
       */
      protected function post($resource,$data)
      {
          $url = $this->getApiUrl($resource);

          $headers = array("Authorization: OAuth ".$this->accessToken,"Accept: application/json","Content-type: application/json");
          $data_json = json_encode($data);

          return $this->makeRequest($url, $headers, null, 1, $data_json);
      }



      /**
       * Delete data from hand
       *
       * Выполнение DELETE запроса к  ручке API.
       *
       * @param $resource string Name of api resource
       * @param $data array Array with request params (useful to CURLOPT_POSTFIELDS: http://php.net/curl_setopt )
       * @return false|object
       */
      protected function delete($resource,$data=array())
      {
          $headers = array("Authorization: OAuth ".$this->accessToken,"Accept: application/json","Content-type: application/json");
          $url = $this->getApiUrl($resource);
          $data_json = json_encode($data);
          return $this->makeRequest($url, $headers, "DELETE", null, $data_json);
      }

      /**
       *
       * Set Curl Options
       *
       * Устанавливаем дефолтные необходимые параметры вызова curl
       *
       * @param $ch resource curl
       * @return true
       */
      static function CurlOpts(&$ch)
      {
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
          curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
          return true;
      }

      /**
       * Convert post-data array to string
       *
       * простой метод, который преобразует массив в обычную query-строку.
       * Ключи - названия get-переменных, value-значение. В случае если value - массив, в итоговую строку будет записана
       * одна и та же переменная со множеством значений. Например, это актуально для вызова ручки /indexing-history/
       * в которую можно передать множетсво индикаторов, которые мы хотим передать, несколько раз задав параметр запроса indexing_indicator
       *
       * @param $data array
       * @return string
       */
      private function dataToString($data)
      {
          $new_data = array();
          foreach ($data as $param=>$value)
          {
              if(is_string($value)||is_int($value)||is_float($value)) $new_data[] = urlencode($param)."=".urlencode($value);
              elseif (is_array($value))
              {
                  foreach ($value as $value_item)
                  {
                      $new_data[] = urlencode($param)."=".urlencode($value_item);
                  }
              } else
              {
                  $this->errorWarning("Bad type of key ".$param.". Value must be string or array");
                  continue;
              }
          }
          return implode("&",$new_data);
      }


      /**
       * Save error message and return false
       *
       * @param $message string Text of message
       * @param $json boolean return false as json error
       *
       * @return false|object
       */

      static function errorCritical($message, $json = true)
      {
          if($json)
          {
            //  if(self::triggerError) trigger_error($message,E_USER_ERROR);
              return (object) array('error_code'=>'CRITICAL_ERROR','error_message'=>$message);
          }
          return false;
      }

      /**
       * Save and log notice message and return false
       *
       * @param $message string Text of message
       * @param $json boolean return false as json error
       *
       * @return false|object
       */
      private function errorWarning($message, $json = true)
      {
          $this->lastError = $message;
          if($json)
          {
              if($this->triggerError) trigger_error($message,E_USER_NOTICE);
              return (object) array('error_code'=>'CRITICAL_ERROR','error_message'=>$message);
          }
          return false;
      }



      /**
       * Get User ID for current access token
       *
       * Узнать userID для текущего токена. Метод вызывается при инициализации класса, и не нужен "снаружи":
       * Текущего пользователя можно получить через публичное свойство userID
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
       * Добавление нового хоста. Параметром передается полный адрес хоста (лучше - с протоколом). Возвращается всегда объект,
       * но, в случае ошибки - объект будет содержать свойства error_code и error_message
       * В случае успеха - это будет объект со свойством host_id, содержащим ID хоста
       *
       * @param $url string
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
       * Удаление хоста из вебмастера. hostID - ID хоста, полученный функцией getHosts
       *
       * @param $hostID string
       * @return object
       */
      public function deleteHost($hostID)
      {
          $ret = $this->delete('/hosts/'.$hostID."/");
          return $ret;
      }


      /**
       * Get host list
       *
       * Получить список всех хостов добавленных в вебмастер для текущего пользователя.
       * Возвращается массив объектов, каждый из которых содержит данные об отдельном хосте
       *
       * @return object Json
       */
      public function getHosts()
      {
          $ret = $this->get('/hosts/');

          return $ret;
      }

      /**
       * Check verification status of host
       *
       * Проверяем статус верификации хоста
       *
       * @param $hostID string ID of host
       * @return object
       */
      public function checkVerification($hostID)
      {
          $ret = $this->get('/hosts/'.$hostID.'/verification/');
          return $ret;
      }


      /**
       * Start verification of host
       *
       *
       * Запуск процедуры верификации хоста. Обратите внимание, если запустить эту функцию для хоста, который находится
       * в процесс верификации, или же уже верифицирован - метод вернет объект с ошибкой. Проверить статус верификации можно с помощью метода
       * checkVerification
       *
       * @param $hostID string id of host
       * @param $type type of verification (DNS|HTML_FILE|META_TAG|WHOIS): get it from applicable_verifiers method of checkVerification return
       * @return false|object
       */
      public function verifyHost($hostID, $type)
      {

          $ret = $this->post('/hosts/'.$hostID.'/verification/?verification_type='.$type,array());
          return $ret;
      }


      /**
       * Get host info
       *
       * Получить подробную информацию об отдельном хосте
       *
       * @param $hostID string Host id in webmaster
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
       * Метод позволяет получить подробную информацию об отдельном хосте, включая его ключевые показатели индексирования.
       *
       * @param $hostID string Host id in webmaster
       *
       * @return object Json
       */
      public function getHostSummary($hostID)
      {
          $ret = $this->get('/hosts/'.$hostID."/summary/");

          return $ret;
      }


      /**
       * Get host owners
       *
       * Метод позволяет узнать всех владельцев хоста, и, для каждого из них узнать uid и метод верификации
       *
       * @param $hostID string Host id in webmaster
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
       * Узнать список всех файлов sitemap, которые используются роботами при обходе данного хоста.
       * Если передается параметр parentID, вернется список файлов, которые относятся к файлу sitemap index с этим id
       * Если параметр не задан - вернутся все файлы, которые лежат в корне.
       *
       * Обратите внимание: Метод не возвращает те файлы, которые добавлены через Яндекс.Вебмастер но еще не используются
       * при обходе. Для получения списка этих файлов используйте метод getHostUserSitemaps
       *
       * @param $hostID string Host id in webmaster
       * @param $parentID string Id of parent sitemap
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
       * Get list of user added sitemap files
       *
       * Метод позволяет получить список все файлов sitemap, добавленных через Яндекс.Вебмастер или API
       *
       * @param $hostID string Host id in webmaster
       *
       * @return object Json
       */
      public function getHostUserSitemaps($hostID)
      {
          $ret = $this->get('/hosts/'.$hostID."/user-added-sitemaps/");

          return $ret;
      }

      /**
       * Add new sitemap
       *
       * Добаление новой карты сайта
       *
       * @param $hostID string Host id in webmaster
       * @param $url string URL with new sitemap
       *
       * @return object
       */
      public function addSitemap($hostID, $url)
      {

          $ret = $this->post('/hosts/'.$hostID."/user-added-sitemaps/",array("url"=>$url));

          return $ret;
      }

      /**
       * Delete host user-added sitemap
       *
       * Удаление существующего файла sitemap.
       * Обратите внимание, что удалять можно только те файлы, которые были вручную добавлены через вебмастер или апи вебмастера
       * (эти файлы можно получить через метод getHostUserSitemaps).
       *
       * Файлы добавленные через robots.txt удалить этим методом нельзя.
       *
       * @param $hostID string Host id in webmaster
       * @param $sitemap_id string sitemap ID
       *
       * @return object Json
       */
      public function deleteSitemap($hostID, $sitemap_id)
      {

          $ret = $this->delete('/hosts/'.$hostID."/user-added-sitemaps/".$sitemap_id."/");

          return $ret;
      }



      /**
       * Get Indexing history
       *
       * Получить историю индексирования хоста. В массиве $indexing_indicators можно передать список тех показателей, которые интересуют:
       * DOWNLOADED  - загруженные страницы
       * EXCLUDED - исключенные страницы
       * SEARCHABLE - страницы в поиске
       * По умолчанию - вытаскивается статистика за последний месяц. Период можно изменить передав соответствующие timestamps в параметрах
       * $date_from и $date_to
       *
       * @param $hostID string Host id in webmaster
       * @param $indexing_indicators array('DOWNLOADED','EXCLUDED','SEARCHABLE',...)
       * @param $date_from int Date from in timestamp
       * @param $date_to int Date to in timestamp
       *
       * @return object Json
       */
      public function getIndexingHistory($hostID,$indexing_indicators=array('DOWNLOADED','EXCLUDED','SEARCHABLE',),$date_from=null,$date_to=null)
      {
          if(!$date_from) $date_from = time()-1209600;
          if(!$date_to) $date_to = time();
          if(!intval($date_to)||!$date_to) return $this->errorCritical("Bad timestamp to \$date_to");
          if(!intval($date_from)||!$date_from) return $this->errorCritical("Bad timestamp to \$date_from");
          if($date_to<$date_from) return $this->errorCritical("Date to can't be smaller then Date from");
          $ret = $this->get('/hosts/'.$hostID."/indexing-history/",array("indexing_indicator"=>$indexing_indicators,'date_from'=>date(DATE_ISO8601,$date_from),'date_to'=>date(DATE_ISO8601,$date_to)));

          return $ret;
      }


      /**
       * Get Tic history
       *
       * Получить историю Тиц
       * По умолчанию - вытаскивается статистика за последний месяц. Период можно изменить передав соответствующие timestamps в параметрах
       * $date_from и $date_to
       *
       * @param $hostID string Host id in webmaster
       * @param $date_from int
       * @param $date_to int
       *
       * @return object Json
       */
      public function getTicHistory($hostID,$date_from=null,$date_to=null)
      {
          if(!$date_from) $date_from = time()-1209600;
          if(!$date_to) $date_to = time();
          $ret = $this->get('/hosts/'.$hostID."/tic-history/",array('date_from'=>date(DATE_ISO8601,$date_from),'date_to'=>date(DATE_ISO8601,$date_to)));

          return $ret;
      }



      /**
       * Get TOP-500 popular queries from host
       *
       * Получить TOP-500 популярных запросов.
       *
       * @param $hostID string Host id in webmaster
       * @param $order_by string ordering: TOTAL_CLICKS|TOTAL_SHOWS
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
       * Get original texts from host
       *
       * Получить список всех оригинальных текстов для заданного хоста.
       *
       * @param $hostID string Host id in webmaster
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
       * Add new original text to host
       *
       * Добавить оригинальный текст.
       * Здесь мы не проверяем размер текста, т.к. эти ошибки вернет само API.
       * Теоретически требования к ОТ могут меняться, потому неправильно поддерживать это в клиентской библиотеке
       *
       * @param $hostID string Host id in webmaster
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
       * Удалить сущестующий ОТ для хоста
       *
       * @param $hostID string Host id in webmaster
       * @param $text_id string text ID to delete
       *
       * @return object Json
       */
      public function deleteOriginalText($hostID, $text_id)
      {
          $ret = $this->delete('/hosts/'.$hostID."/original-texts/".urlencode($text_id)."/");


          return $ret;
      }




      /**
       * Get Access token by code and client secret
       *
       * How to use:
       * 1. Go to https://oauth.yandex.ru/client/new
       * 2. Type name of program
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
          return self::MakeRequest("https://oauth.yandex.ru/token", null, null, 1, $postData);
      }
  }

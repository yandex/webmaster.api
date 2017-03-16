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

namespace yandex\webmaster\api;

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
    private $accessToken;

    /**
     * Url of webmaster API
     *
     * @var string
     */
    private $apiUrl = 'https://api.webmaster.yandex.net/v3';

    /**
     * UserID in webmaster
     *
     * @var int
     */
    public $userID;


    /**
     * Last error message
     *
     * @var string
     */
    public $lastError;

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
    protected function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
        $response = $this->getUserID();
        if (isset($response->error_message)) {
            $this->errorCritical($response->error_message);
        }
        $this->userID = $response;
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
    public static function initApi($accessToken)
    {
        $wmApi = new static($accessToken);
        if (!empty($wmApi->lastError)) {
            return (object)array('error_message' => $wmApi->lastError);
        }

        return $wmApi;
    }


    /**
     * Get handler url for this resource
     *
     * Простоая обертка, возвращающая правильный путь к ручке API
     * На самом деле все что она делает - дописывает /user/userID/, кроме, непосредственно, ручки /user/
     *
     * @param $resource string
     * @return string
     */
    public function getApiUrl($resource)
    {
        $apiUrl = $this->apiUrl;
        if ($resource !== '/user/') {
            if (!$this->userID) {
                return $this->errorCritical("Can't get hand {$resource} without userID");
            }
            $apiUrl .= '/user/' . $this->userID;
        }

        return $apiUrl . $resource;
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
    protected function get($resource, $data = array())
    {
        $apiUrl = $this->getApiUrl($resource);

        $headers = $this->getDefaultHttpHeaders();

        $url = $apiUrl . '?' . $this->dataToString($data);

        $allow_url_fopen = ini_get('allow_url_fopen');
        if (function_exists('curl_init')) {
            // Шлем запрос в курл
            $ch = curl_init($url);
            // основные опции curl
            $this->curlOpts($ch);
            // передаем заголовки
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);
        } elseif (!empty($allow_url_fopen)) {
            $opts = array(
                'http' => array(
                    'method' => 'GET',
                    'header' => $headers
                )
            );

            # создание контекста потока
            $context = stream_context_create($opts);
            # отправляем запрос и получаем ответ от сервера
            $response = @file_get_contents($url, 0, $context);

        } else {
            return $this->errorCritical('CURL not installed & file_get_contents disabled');
        }


        if (!$response) {
            return $this->errorCritical('Error in curl when get [' . $url . '] ' . (isset($curl_error) ? $curl_error : ''));
        }
        $response = json_decode($response, false, 512, JSON_BIGINT_AS_STRING);


        if (!is_object($response)) {
            return $this->errorCritical('Unknown error in response: Not object given');
        }
        return $response;
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
    protected function post($resource, $data)
    {
        $url = $this->getApiUrl($resource);

        $headers = $this->getDefaultHttpHeaders();

        $dataJson = json_encode($data);

        $allow_url_fopen = ini_get('allow_url_fopen');
        if (function_exists('curl_init')) {
            // Шлем запрос в курл
            $ch = curl_init($url);
            // основные опции курл
            $this->curlOpts($ch);
            // передаем заголовки
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
            $response = curl_exec($ch);
            curl_close($ch);
        } elseif (!empty($allow_url_fopen)) {
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'content' => $dataJson,
                    'header' => $headers
                )
            );

            # создание контекста потока
            $context = stream_context_create($opts);
            # отправляем запрос и получаем ответ от сервера
            $response = @file_get_contents($url, 0, $context);
        } else {
            return $this->errorCritical('CURL not installed & file_get_contents disabled');
        }

        if (!$response) {
            return $this->errorCritical('Unknown error in curl');
        }
        $response = json_decode($response);

        if (!is_object($response)) {
            return $this->errorCritical('Unknown error in curl');
        }
        return $response;
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
    protected function delete($resource, $data = array())
    {
        $url = $this->getApiUrl($resource);
        $headers = $this->getDefaultHttpHeaders();
        $dataJson = json_encode($data);

        $allow_url_fopen = ini_get('allow_url_fopen');
        if (function_exists('curl_init')) {
            // Шлем запрос в курл
            $ch = curl_init($url);
            // основные опции курл
            $this->curlOpts($ch);
            // передаем заголовки
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode == '204') {
                return (object)array(true);
            }
        } elseif (!empty($allow_url_fopen)) {
            $opts = array(
                'http' => array(
                    'method' => 'DELETE',
                    'content' => $dataJson,
                    'header' => $headers
                )
            );

            # создание контекста потока
            $context = stream_context_create($opts);
            # отправляем запрос и получаем ответ от сервера
            $response = @file_get_contents($url, 0, $context);

            if (in_array('HTTP/1.1 204 No Content', $http_response_header)) {
                return (object)array(true);
            }
        } else {
            return $this->errorCritical('CURL not installed & file_get_contents disabled');
        }

        if (!$response) {
            return $this->errorCritical('Unknown error in curl');
        }

        $response = json_decode($response);
        if (!is_object($response)) {
            return $this->errorCritical('Unknown error in curl');
        }

        return $response;
    }

    protected function getDefaultHttpHeaders()
    {
        return array(
            'Authorization: OAuth ' . $this->accessToken,
            'Accept: application/json',
            'Content-type: application/json'
        );
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
    protected function curlOpts(&$ch)
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
        $queryString = array();
        foreach ($data as $param => $value) {
            if (is_string($value) || is_int($value) || is_float($value)) {
                $queryString[] = urlencode($param) . '=' . urlencode($value);
            } elseif (is_array($value)) {
                foreach ($value as $valueItem) {
                    $queryString[] = urlencode($param) . '=' . urlencode($valueItem);
                }
            } else {
                $this->errorWarning("Bad type of key {$param}. Value must be string or array");
                continue;
            }
        }

        return implode('&', $queryString);
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
        if ($json) {
            if ($this->triggerError) {
                trigger_error($message, E_USER_ERROR);
            }

            return (object)array('error_code' => 'CRITICAL_ERROR', 'error_message' => $message);
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
        if ($json) {
            if ($this->triggerError) {
                trigger_error($message, E_USER_NOTICE);
            }

            return (object)array('error_code' => 'CRITICAL_ERROR', 'error_message' => $message);
        }

        return false;
    }


    /**
     * Get User ID for current access token
     *
     * Узнать userID для текущего токена. Метод вызывается при инициализации класса, и не нужен 'снаружи':
     * Текущего пользователя можно получить через публичное свойство userID
     *
     * @return int|false
     */
    private function getUserID()
    {
        $response = $this->get('/user/');
        if (!isset($response->user_id) || !intval($response->user_id)) {
            $message = "Can't resolve USER ID";
            if (isset($response->error_message)) {
                $message .= '. ' . $response->error_message;
            }

            return $this->errorCritical($message);
        }

        return $response->user_id;
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
        return $this->post('/hosts/', array('host_url' => $url));
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
        return $this->delete('/hosts/' . $hostID . '/');
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
        return $this->get('/hosts/');
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
        return $this->get('/hosts/' . $hostID . '/verification/');
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
        return $this->post('/hosts/' . $hostID . '/verification/?verification_type=' . $type, array());
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
        return $this->get('/hosts/' . $hostID . '/');
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
        return $this->get('/hosts/' . $hostID . '/summary/');
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
        return $this->get('/hosts/' . $hostID . '/owners/');
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
    public function getHostSitemaps($hostID, $parentID = null)
    {
        $get = array();
        if ($parentID) {
            $get['parent_id'] = $parentID;
        }

        return $this->get('/hosts/' . $hostID . '/sitemaps/', $get);
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
        return $this->get('/hosts/' . $hostID . '/user-added-sitemaps/');
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
        return $this->post('/hosts/' . $hostID . '/user-added-sitemaps/', array('url' => $url));
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
     * @param $sitemapId string sitemap ID
     *
     * @return object Json
     */
    public function deleteSitemap($hostID, $sitemapId)
    {
        return $this->delete('/hosts/' . $hostID . '/user-added-sitemaps/' . $sitemapId . '/');
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
     * @param $dateFrom int Date from in timestamp
     * @param $dateTo int Date to in timestamp
     *
     * @return object Json
     */
    public function getIndexingHistory($hostID, $indexing_indicators = array('DOWNLOADED', 'EXCLUDED', 'SEARCHABLE',), $dateFrom = null, $dateTo = null)
    {
        if (!$dateFrom) {
            $dateFrom = strtotime('-1 month');
        }
        if (!$dateTo) {
            $dateTo = time();
        }
        if (!intval($dateTo) || !$dateTo) {
            return $this->errorCritical("Bad timestamp to {$dateTo}");
        }
        if (!intval($dateFrom) || !$dateFrom) {
            return $this->errorCritical("Bad timestamp to {$dateFrom}");
        }
        if ($dateTo < $dateFrom) {
            return $this->errorCritical("Date to can't be smaller then Date from");
        }

        return $this->get('/hosts/' . $hostID . '/indexing-history/', array('indexing_indicator' => $indexing_indicators, 'date_from' => date(DATE_ATOM, $dateFrom), 'date_to' => date(DATE_ATOM, $dateTo)));
    }


    /**
     * Get Tic history
     *
     * Получить историю Тиц
     * По умолчанию - вытаскивается статистика за последний месяц. Период можно изменить передав соответствующие timestamps в параметрах
     * $date_from и $date_to
     *
     * @param $hostID string Host id in webmaster
     * @param $dateFrom int
     * @param $dateTo int
     *
     * @return object Json
     */
    public function getTicHistory($hostID, $dateFrom = null, $dateTo = null)
    {
        if (!$dateFrom) {
            $dateFrom = strtotime('-1 month');
        }
        if (!$dateTo) {
            $dateTo = time();
        }

        return $this->get('/hosts/' . $hostID . '/tic-history/', array('date_from' => date(DATE_ATOM, $dateFrom), 'date_to' => date(DATE_ATOM, $dateTo)));
    }

    /**
     * Get External links history
     *
     * Получение истории изменения количества внешних ссылок на сайт
     *
     * @param $hostID string Host id in webmaster
     * @param $indicator string - Индикатор количества внешних ссылок
     *
     * @return object Json
     */
    public function getExternalLinksHistory($hostID, $indicator = 'LINKS_TOTAL_COUNT')
    {
        return $this->get('/hosts/' . $hostID . '/links/external/history/', array('indicator' => $indicator));
    }


    /**
     * Get TOP-500 popular queries from host
     *
     * Получить TOP-500 популярных запросов.
     *
     * @param $hostID string Host id in webmaster
     * @param $orderBy string ordering: TOTAL_CLICKS|TOTAL_SHOWS
     * @param $indicators array('TOTAL_SHOWS','TOTAL_CLICKS','AVG_SHOW_POSITION','AVG_CLICK_POSITION')
     *
     * @return object Json
     */
    public function getPopularQueries($hostID, $orderBy = 'TOTAL_CLICKS', $indicators = array())
    {
        return $this->get('/hosts/' . $hostID . '/search-queries/popular/', array('order_by' => $orderBy, 'query_indicator' => $indicators));
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
    public function getOriginalTexts($hostID, $offset = 0, $limit = 100)
    {
        return $this->get('/hosts/' . $hostID . '/original-texts/', array('offset' => $offset, 'limit' => $limit));
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
    public function addOriginalText($hostID, $content)
    {
        return $this->post('/hosts/' . $hostID . '/original-texts/', array('content' => $content));
    }


    /**
     * Delete existing original text from host
     *
     * Удалить сущестующий ОТ для хоста
     *
     * @param $hostID string Host id in webmaster
     * @param $textId string text ID to delete
     *
     * @return object Json
     */
    public function deleteOriginalText($hostID, $textId)
    {
        return $this->delete('/hosts/' . $hostID . '/original-texts/' . urlencode($textId) . '/');
    }


    public function getExternalLinks($hostID, $offset = 0, $limit = 100)
    {
        return $this->get('/hosts/' . $hostID . '/links/external/samples/', array('offset' => $offset, 'limit' => $limit));
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
     * @param $clientId
     * @param $clientSecret
     * @return object
     */
    public static function getAccessToken($code, $clientId, $clientSecret)
    {
        $postData = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        );

        $ch = curl_init('https://oauth.yandex.ru/token');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);


        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) die('Unknown error in curl');

        $response = json_decode($response);

        if (!is_object($response)) die('Unknown error in curl');

        return $response;
    }
}

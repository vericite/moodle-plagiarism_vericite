<?php
/**
 * ReportsApi
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * VeriCiteLmsApiV1
 *
 * No description provided (generated by Swagger Codegen https://github.com/swagger-api/swagger-codegen)
 *
 * OpenAPI spec version: 1.0.0
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\Api;

use \Swagger\Client\ApiClient;
use \Swagger\Client\ApiException;
use \Swagger\Client\Configuration;
use \Swagger\Client\ObjectSerializer;

/**
 * ReportsApi Class Doc Comment
 *
 * @category Class
 * @package  Swagger\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class ReportsApi
{
    /**
     * API Client
     *
     * @var \Swagger\Client\ApiClient instance of the ApiClient
     */
    protected $apiClient;

    /**
     * Constructor
     *
     * @param \Swagger\Client\ApiClient|null $apiClient The api client to use
     */
    public function __construct(\Swagger\Client\ApiClient $apiClient = null)
    {
        if ($apiClient === null) {
            $apiClient = new ApiClient();
        }

        $this->apiClient = $apiClient;
    }

    /**
     * Get API client
     *
     * @return \Swagger\Client\ApiClient get the API client
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * Set the API client
     *
     * @param \Swagger\Client\ApiClient $apiClient set the API client
     *
     * @return ReportsApi
     */
    public function setApiClient(\Swagger\Client\ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        return $this;
    }

    /**
     * Operation getReportUrls
     *
     * 
     *
     * @param string $context_id Context ID (required)
     * @param string $assignment_id_filter ID of assignment to filter results on (required)
     * @param string $consumer the consumer (required)
     * @param string $consumer_secret the consumer secret (required)
     * @param string $token_user ID of user who will view the report (required)
     * @param string $token_user_role role of user who will view the report (required)
     * @param string $user_id_filter ID of user to filter results on (optional)
     * @param string $external_content_id_filter external content id to filter results on (optional)
     * @param bool $print_report_page flag to indicate a request for the print report page URL (optional)
     * @throws \Swagger\Client\ApiException on non-2xx response
     * @return \Swagger\Client\Model\InlineResponse2002[]
     */
    public function getReportUrls($context_id, $assignment_id_filter, $consumer, $consumer_secret, $token_user, $token_user_role, $user_id_filter = null, $external_content_id_filter = null, $print_report_page = null)
    {
        list($response) = $this->getReportUrlsWithHttpInfo($context_id, $assignment_id_filter, $consumer, $consumer_secret, $token_user, $token_user_role, $user_id_filter, $external_content_id_filter, $print_report_page);
        return $response;
    }

    /**
     * Operation getReportUrlsWithHttpInfo
     *
     * 
     *
     * @param string $context_id Context ID (required)
     * @param string $assignment_id_filter ID of assignment to filter results on (required)
     * @param string $consumer the consumer (required)
     * @param string $consumer_secret the consumer secret (required)
     * @param string $token_user ID of user who will view the report (required)
     * @param string $token_user_role role of user who will view the report (required)
     * @param string $user_id_filter ID of user to filter results on (optional)
     * @param string $external_content_id_filter external content id to filter results on (optional)
     * @param bool $print_report_page flag to indicate a request for the print report page URL (optional)
     * @throws \Swagger\Client\ApiException on non-2xx response
     * @return array of \Swagger\Client\Model\InlineResponse2002[], HTTP status code, HTTP response headers (array of strings)
     */
    public function getReportUrlsWithHttpInfo($context_id, $assignment_id_filter, $consumer, $consumer_secret, $token_user, $token_user_role, $user_id_filter = null, $external_content_id_filter = null, $print_report_page = null)
    {
        // verify the required parameter 'context_id' is set
        if ($context_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $context_id when calling getReportUrls');
        }
        // verify the required parameter 'assignment_id_filter' is set
        if ($assignment_id_filter === null) {
            throw new \InvalidArgumentException('Missing the required parameter $assignment_id_filter when calling getReportUrls');
        }
        // verify the required parameter 'consumer' is set
        if ($consumer === null) {
            throw new \InvalidArgumentException('Missing the required parameter $consumer when calling getReportUrls');
        }
        // verify the required parameter 'consumer_secret' is set
        if ($consumer_secret === null) {
            throw new \InvalidArgumentException('Missing the required parameter $consumer_secret when calling getReportUrls');
        }
        // verify the required parameter 'token_user' is set
        if ($token_user === null) {
            throw new \InvalidArgumentException('Missing the required parameter $token_user when calling getReportUrls');
        }
        // verify the required parameter 'token_user_role' is set
        if ($token_user_role === null) {
            throw new \InvalidArgumentException('Missing the required parameter $token_user_role when calling getReportUrls');
        }
        // parse inputs
        $resourcePath = "/reports/urls/{contextID}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept([]);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType([]);

        // query params
        if ($assignment_id_filter !== null) {
            $queryParams['assignmentIDFilter'] = $this->apiClient->getSerializer()->toQueryValue($assignment_id_filter);
        }
        // query params
        if ($user_id_filter !== null) {
            $queryParams['userIDFilter'] = $this->apiClient->getSerializer()->toQueryValue($user_id_filter);
        }
        // query params
        if ($external_content_id_filter !== null) {
            $queryParams['externalContentIDFilter'] = $this->apiClient->getSerializer()->toQueryValue($external_content_id_filter);
        }
        // query params
        if ($print_report_page !== null) {
            $queryParams['printReportPage'] = $this->apiClient->getSerializer()->toQueryValue($print_report_page);
        }
        // query params
        if ($token_user !== null) {
            $queryParams['tokenUser'] = $this->apiClient->getSerializer()->toQueryValue($token_user);
        }
        // query params
        if ($token_user_role !== null) {
            $queryParams['tokenUserRole'] = $this->apiClient->getSerializer()->toQueryValue($token_user_role);
        }
        // header params
        if ($consumer !== null) {
            $headerParams['consumer'] = $this->apiClient->getSerializer()->toHeaderValue($consumer);
        }
        // header params
        if ($consumer_secret !== null) {
            $headerParams['consumerSecret'] = $this->apiClient->getSerializer()->toHeaderValue($consumer_secret);
        }
        // path params
        if ($context_id !== null) {
            $resourcePath = str_replace(
                "{" . "contextID" . "}",
                $this->apiClient->getSerializer()->toPathValue($context_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'GET',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Swagger\Client\Model\InlineResponse2002[]',
                '/reports/urls/{contextID}'
            );

            return [$this->apiClient->getSerializer()->deserialize($response, '\Swagger\Client\Model\InlineResponse2002[]', $httpHeader), $statusCode, $httpHeader];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse2002[]', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 400:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 401:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 500:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 0:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }

    /**
     * Operation getScores
     *
     * 
     *
     * @param string $context_id Context ID (required)
     * @param string $consumer the consumer (required)
     * @param string $consumer_secret the consumer secret (required)
     * @param string $assignment_id ID of assignment (optional)
     * @param string $user_id ID of user (optional)
     * @param string $external_content_id external content id (optional)
     * @throws \Swagger\Client\ApiException on non-2xx response
     * @return \Swagger\Client\Model\InlineResponse2001[]
     */
    public function getScores($context_id, $consumer, $consumer_secret, $assignment_id = null, $user_id = null, $external_content_id = null)
    {
        list($response) = $this->getScoresWithHttpInfo($context_id, $consumer, $consumer_secret, $assignment_id, $user_id, $external_content_id);
        return $response;
    }

    /**
     * Operation getScoresWithHttpInfo
     *
     * 
     *
     * @param string $context_id Context ID (required)
     * @param string $consumer the consumer (required)
     * @param string $consumer_secret the consumer secret (required)
     * @param string $assignment_id ID of assignment (optional)
     * @param string $user_id ID of user (optional)
     * @param string $external_content_id external content id (optional)
     * @throws \Swagger\Client\ApiException on non-2xx response
     * @return array of \Swagger\Client\Model\InlineResponse2001[], HTTP status code, HTTP response headers (array of strings)
     */
    public function getScoresWithHttpInfo($context_id, $consumer, $consumer_secret, $assignment_id = null, $user_id = null, $external_content_id = null)
    {
        // verify the required parameter 'context_id' is set
        if ($context_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $context_id when calling getScores');
        }
        // verify the required parameter 'consumer' is set
        if ($consumer === null) {
            throw new \InvalidArgumentException('Missing the required parameter $consumer when calling getScores');
        }
        // verify the required parameter 'consumer_secret' is set
        if ($consumer_secret === null) {
            throw new \InvalidArgumentException('Missing the required parameter $consumer_secret when calling getScores');
        }
        // parse inputs
        $resourcePath = "/reports/scores/{contextID}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept([]);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType([]);

        // query params
        if ($assignment_id !== null) {
            $queryParams['assignmentID'] = $this->apiClient->getSerializer()->toQueryValue($assignment_id);
        }
        // query params
        if ($user_id !== null) {
            $queryParams['userID'] = $this->apiClient->getSerializer()->toQueryValue($user_id);
        }
        // query params
        if ($external_content_id !== null) {
            $queryParams['externalContentID'] = $this->apiClient->getSerializer()->toQueryValue($external_content_id);
        }
        // header params
        if ($consumer !== null) {
            $headerParams['consumer'] = $this->apiClient->getSerializer()->toHeaderValue($consumer);
        }
        // header params
        if ($consumer_secret !== null) {
            $headerParams['consumerSecret'] = $this->apiClient->getSerializer()->toHeaderValue($consumer_secret);
        }
        // path params
        if ($context_id !== null) {
            $resourcePath = str_replace(
                "{" . "contextID" . "}",
                $this->apiClient->getSerializer()->toPathValue($context_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'GET',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Swagger\Client\Model\InlineResponse2001[]',
                '/reports/scores/{contextID}'
            );

            return [$this->apiClient->getSerializer()->deserialize($response, '\Swagger\Client\Model\InlineResponse2001[]', $httpHeader), $statusCode, $httpHeader];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse2001[]', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 400:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 401:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 500:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 0:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }

    /**
     * Operation submitRequest
     *
     * 
     *
     * @param string $context_id Context ID (required)
     * @param string $assignment_id ID of assignment (required)
     * @param string $user_id ID of user (required)
     * @param string $consumer the consumer (required)
     * @param string $consumer_secret the consumer secret (required)
     * @param \Swagger\Client\Model\ReportMetaData $report_meta_data  (required)
     * @param bool $immediate_score_only Will only run the report for immediate scoring (optional)
     * @param bool $encrypted Flag to indicate encryption (optional)
     * @throws \Swagger\Client\ApiException on non-2xx response
     * @return \Swagger\Client\Model\InlineResponse200[]
     */
    public function submitRequest($context_id, $assignment_id, $user_id, $consumer, $consumer_secret, $report_meta_data, $immediate_score_only = null, $encrypted = null)
    {
        list($response) = $this->submitRequestWithHttpInfo($context_id, $assignment_id, $user_id, $consumer, $consumer_secret, $report_meta_data, $immediate_score_only, $encrypted);
        return $response;
    }

    /**
     * Operation submitRequestWithHttpInfo
     *
     * 
     *
     * @param string $context_id Context ID (required)
     * @param string $assignment_id ID of assignment (required)
     * @param string $user_id ID of user (required)
     * @param string $consumer the consumer (required)
     * @param string $consumer_secret the consumer secret (required)
     * @param \Swagger\Client\Model\ReportMetaData $report_meta_data  (required)
     * @param bool $immediate_score_only Will only run the report for immediate scoring (optional)
     * @param bool $encrypted Flag to indicate encryption (optional)
     * @throws \Swagger\Client\ApiException on non-2xx response
     * @return array of \Swagger\Client\Model\InlineResponse200[], HTTP status code, HTTP response headers (array of strings)
     */
    public function submitRequestWithHttpInfo($context_id, $assignment_id, $user_id, $consumer, $consumer_secret, $report_meta_data, $immediate_score_only = null, $encrypted = null)
    {
        // verify the required parameter 'context_id' is set
        if ($context_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $context_id when calling submitRequest');
        }
        // verify the required parameter 'assignment_id' is set
        if ($assignment_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $assignment_id when calling submitRequest');
        }
        // verify the required parameter 'user_id' is set
        if ($user_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $user_id when calling submitRequest');
        }
        // verify the required parameter 'consumer' is set
        if ($consumer === null) {
            throw new \InvalidArgumentException('Missing the required parameter $consumer when calling submitRequest');
        }
        // verify the required parameter 'consumer_secret' is set
        if ($consumer_secret === null) {
            throw new \InvalidArgumentException('Missing the required parameter $consumer_secret when calling submitRequest');
        }
        // verify the required parameter 'report_meta_data' is set
        if ($report_meta_data === null) {
            throw new \InvalidArgumentException('Missing the required parameter $report_meta_data when calling submitRequest');
        }
        // parse inputs
        $resourcePath = "/reports/submit/request/{contextID}/{assignmentID}/{userID}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept([]);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType([]);

        // query params
        if ($immediate_score_only !== null) {
            $queryParams['immediateScoreOnly'] = $this->apiClient->getSerializer()->toQueryValue($immediate_score_only);
        }
        // query params
        if ($encrypted !== null) {
            $queryParams['encrypted'] = $this->apiClient->getSerializer()->toQueryValue($encrypted);
        }
        // header params
        if ($consumer !== null) {
            $headerParams['consumer'] = $this->apiClient->getSerializer()->toHeaderValue($consumer);
        }
        // header params
        if ($consumer_secret !== null) {
            $headerParams['consumerSecret'] = $this->apiClient->getSerializer()->toHeaderValue($consumer_secret);
        }
        // path params
        if ($context_id !== null) {
            $resourcePath = str_replace(
                "{" . "contextID" . "}",
                $this->apiClient->getSerializer()->toPathValue($context_id),
                $resourcePath
            );
        }
        // path params
        if ($assignment_id !== null) {
            $resourcePath = str_replace(
                "{" . "assignmentID" . "}",
                $this->apiClient->getSerializer()->toPathValue($assignment_id),
                $resourcePath
            );
        }
        // path params
        if ($user_id !== null) {
            $resourcePath = str_replace(
                "{" . "userID" . "}",
                $this->apiClient->getSerializer()->toPathValue($user_id),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // body params
        $_tempBody = null;
        if (isset($report_meta_data)) {
            $_tempBody = $report_meta_data;
        }

        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'POST',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Swagger\Client\Model\InlineResponse200[]',
                '/reports/submit/request/{contextID}/{assignmentID}/{userID}'
            );

            return [$this->apiClient->getSerializer()->deserialize($response, '\Swagger\Client\Model\InlineResponse200[]', $httpHeader), $statusCode, $httpHeader];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse200[]', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 400:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 401:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 500:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
                case 0:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Swagger\Client\Model\InlineResponse400', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }
}

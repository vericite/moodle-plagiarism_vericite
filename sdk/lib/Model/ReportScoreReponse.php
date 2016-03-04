<?php
/**
 * ReportScoreReponse
 *
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */
/**
 *  Copyright 2016 SmartBear Software
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\Model;

use \ArrayAccess;
/**
 * ReportScoreReponse Class Doc Comment
 *
 * @category    Class
 * @description 
 * @package     Swagger\Client
 * @author      http://github.com/swagger-api/swagger-codegen
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class ReportScoreReponse implements ArrayAccess
{
    /**
      * Array of property to type mappings. Used for (de)serialization 
      * @var string[]
      */
    static $swaggerTypes = array(
        'user' => 'string',
        'assignment' => 'string',
        'external_content_id' => 'string',
        'score' => 'int'
    );
  
    /** 
      * Array of attributes where the key is the local name, and the value is the original name
      * @var string[] 
      */
    static $attributeMap = array(
        'user' => 'user',
        'assignment' => 'assignment',
        'external_content_id' => 'externalContentId',
        'score' => 'score'
    );
  
    /**
      * Array of attributes to setter functions (for deserialization of responses)
      * @var string[]
      */
    static $setters = array(
        'user' => 'setUser',
        'assignment' => 'setAssignment',
        'external_content_id' => 'setExternalContentId',
        'score' => 'setScore'
    );
  
    /**
      * Array of attributes to getter functions (for serialization of requests)
      * @var string[]
      */
    static $getters = array(
        'user' => 'getUser',
        'assignment' => 'getAssignment',
        'external_content_id' => 'getExternalContentId',
        'score' => 'getScore'
    );
  
    
    /**
      * $user 
      * @var string
      */
    protected $user;
    
    /**
      * $assignment 
      * @var string
      */
    protected $assignment;
    
    /**
      * $external_content_id 
      * @var string
      */
    protected $external_content_id;
    
    /**
      * $score 
      * @var int
      */
    protected $score;
    

    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null)
    {
        if ($data != null) {
            $this->user = $data["user"];
            $this->assignment = $data["assignment"];
            $this->external_content_id = $data["external_content_id"];
            $this->score = $data["score"];
        }
    }
    
    /**
     * Gets user
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }
  
    /**
     * Sets user
     * @param string $user 
     * @return $this
     */
    public function setUser($user)
    {
        
        $this->user = $user;
        return $this;
    }
    
    /**
     * Gets assignment
     * @return string
     */
    public function getAssignment()
    {
        return $this->assignment;
    }
  
    /**
     * Sets assignment
     * @param string $assignment 
     * @return $this
     */
    public function setAssignment($assignment)
    {
        
        $this->assignment = $assignment;
        return $this;
    }
    
    /**
     * Gets external_content_id
     * @return string
     */
    public function getExternalContentId()
    {
        return $this->external_content_id;
    }
  
    /**
     * Sets external_content_id
     * @param string $external_content_id 
     * @return $this
     */
    public function setExternalContentId($external_content_id)
    {
        
        $this->external_content_id = $external_content_id;
        return $this;
    }
    
    /**
     * Gets score
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }
  
    /**
     * Sets score
     * @param int $score 
     * @return $this
     */
    public function setScore($score)
    {
        
        $this->score = $score;
        return $this;
    }
    
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset 
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }
  
    /**
     * Gets offset.
     * @param  integer $offset Offset 
     * @return mixed 
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }
  
    /**
     * Sets value based on offset.
     * @param  integer $offset Offset 
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }
  
    /**
     * Unsets offset.
     * @param  integer $offset Offset 
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
  
    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        } else {
            return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this));
        }
    }
}

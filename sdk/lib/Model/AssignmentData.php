<?php
/**
 * AssignmentData
 *
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   Swaagger Codegen team
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

namespace Swagger\Client\Model;

use \ArrayAccess;

/**
 * AssignmentData Class Doc Comment
 *
 * @category    Class
 * @package     Swagger\Client
 * @author      Swagger Codegen team
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class AssignmentData implements ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'assignmentData';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'assignment_attachment_external_content' => '\Swagger\Client\Model\AssignmentscontextIDassignmentIDAssignmentAttachmentExternalContent[]',
        'assignment_due_date' => 'int',
        'assignment_enable_student_preview' => 'bool',
        'assignment_exclude_quotes' => 'bool',
        'assignment_exclude_self_plag' => 'bool',
        'assignment_grade' => 'int',
        'assignment_instructions' => 'string',
        'assignment_store_in_index' => 'bool',
        'assignment_title' => 'string'
    ];

    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of attributes where the key is the local name, and the value is the original name
     * @var string[]
     */
    protected static $attributeMap = [
        'assignment_attachment_external_content' => 'assignmentAttachmentExternalContent',
        'assignment_due_date' => 'assignmentDueDate',
        'assignment_enable_student_preview' => 'assignmentEnableStudentPreview',
        'assignment_exclude_quotes' => 'assignmentExcludeQuotes',
        'assignment_exclude_self_plag' => 'assignmentExcludeSelfPlag',
        'assignment_grade' => 'assignmentGrade',
        'assignment_instructions' => 'assignmentInstructions',
        'assignment_store_in_index' => 'assignmentStoreInIndex',
        'assignment_title' => 'assignmentTitle'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'assignment_attachment_external_content' => 'setAssignmentAttachmentExternalContent',
        'assignment_due_date' => 'setAssignmentDueDate',
        'assignment_enable_student_preview' => 'setAssignmentEnableStudentPreview',
        'assignment_exclude_quotes' => 'setAssignmentExcludeQuotes',
        'assignment_exclude_self_plag' => 'setAssignmentExcludeSelfPlag',
        'assignment_grade' => 'setAssignmentGrade',
        'assignment_instructions' => 'setAssignmentInstructions',
        'assignment_store_in_index' => 'setAssignmentStoreInIndex',
        'assignment_title' => 'setAssignmentTitle'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'assignment_attachment_external_content' => 'getAssignmentAttachmentExternalContent',
        'assignment_due_date' => 'getAssignmentDueDate',
        'assignment_enable_student_preview' => 'getAssignmentEnableStudentPreview',
        'assignment_exclude_quotes' => 'getAssignmentExcludeQuotes',
        'assignment_exclude_self_plag' => 'getAssignmentExcludeSelfPlag',
        'assignment_grade' => 'getAssignmentGrade',
        'assignment_instructions' => 'getAssignmentInstructions',
        'assignment_store_in_index' => 'getAssignmentStoreInIndex',
        'assignment_title' => 'getAssignmentTitle'
    ];

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    public static function setters()
    {
        return self::$setters;
    }

    public static function getters()
    {
        return self::$getters;
    }

    

    

    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['assignment_attachment_external_content'] = isset($data['assignment_attachment_external_content']) ? $data['assignment_attachment_external_content'] : null;
        $this->container['assignment_due_date'] = isset($data['assignment_due_date']) ? $data['assignment_due_date'] : null;
        $this->container['assignment_enable_student_preview'] = isset($data['assignment_enable_student_preview']) ? $data['assignment_enable_student_preview'] : null;
        $this->container['assignment_exclude_quotes'] = isset($data['assignment_exclude_quotes']) ? $data['assignment_exclude_quotes'] : null;
        $this->container['assignment_exclude_self_plag'] = isset($data['assignment_exclude_self_plag']) ? $data['assignment_exclude_self_plag'] : null;
        $this->container['assignment_grade'] = isset($data['assignment_grade']) ? $data['assignment_grade'] : null;
        $this->container['assignment_instructions'] = isset($data['assignment_instructions']) ? $data['assignment_instructions'] : null;
        $this->container['assignment_store_in_index'] = isset($data['assignment_store_in_index']) ? $data['assignment_store_in_index'] : null;
        $this->container['assignment_title'] = isset($data['assignment_title']) ? $data['assignment_title'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = [];

        if ($this->container['assignment_title'] === null) {
            $invalid_properties[] = "'assignment_title' can't be null";
        }
        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {

        if ($this->container['assignment_title'] === null) {
            return false;
        }
        return true;
    }


    /**
     * Gets assignment_attachment_external_content
     * @return \Swagger\Client\Model\AssignmentscontextIDassignmentIDAssignmentAttachmentExternalContent[]
     */
    public function getAssignmentAttachmentExternalContent()
    {
        return $this->container['assignment_attachment_external_content'];
    }

    /**
     * Sets assignment_attachment_external_content
     * @param \Swagger\Client\Model\AssignmentscontextIDassignmentIDAssignmentAttachmentExternalContent[] $assignment_attachment_external_content
     * @return $this
     */
    public function setAssignmentAttachmentExternalContent($assignment_attachment_external_content)
    {
        $this->container['assignment_attachment_external_content'] = $assignment_attachment_external_content;

        return $this;
    }

    /**
     * Gets assignment_due_date
     * @return int
     */
    public function getAssignmentDueDate()
    {
        return $this->container['assignment_due_date'];
    }

    /**
     * Sets assignment_due_date
     * @param int $assignment_due_date Assignment due date. Pass in 0 to delete.
     * @return $this
     */
    public function setAssignmentDueDate($assignment_due_date)
    {
        $this->container['assignment_due_date'] = $assignment_due_date;

        return $this;
    }

    /**
     * Gets assignment_enable_student_preview
     * @return bool
     */
    public function getAssignmentEnableStudentPreview()
    {
        return $this->container['assignment_enable_student_preview'];
    }

    /**
     * Sets assignment_enable_student_preview
     * @param bool $assignment_enable_student_preview set status for enableStudentPreview
     * @return $this
     */
    public function setAssignmentEnableStudentPreview($assignment_enable_student_preview)
    {
        $this->container['assignment_enable_student_preview'] = $assignment_enable_student_preview;

        return $this;
    }

    /**
     * Gets assignment_exclude_quotes
     * @return bool
     */
    public function getAssignmentExcludeQuotes()
    {
        return $this->container['assignment_exclude_quotes'];
    }

    /**
     * Sets assignment_exclude_quotes
     * @param bool $assignment_exclude_quotes exclude quotes
     * @return $this
     */
    public function setAssignmentExcludeQuotes($assignment_exclude_quotes)
    {
        $this->container['assignment_exclude_quotes'] = $assignment_exclude_quotes;

        return $this;
    }

    /**
     * Gets assignment_exclude_self_plag
     * @return bool
     */
    public function getAssignmentExcludeSelfPlag()
    {
        return $this->container['assignment_exclude_self_plag'];
    }

    /**
     * Sets assignment_exclude_self_plag
     * @param bool $assignment_exclude_self_plag exclude self plagiarism
     * @return $this
     */
    public function setAssignmentExcludeSelfPlag($assignment_exclude_self_plag)
    {
        $this->container['assignment_exclude_self_plag'] = $assignment_exclude_self_plag;

        return $this;
    }

    /**
     * Gets assignment_grade
     * @return int
     */
    public function getAssignmentGrade()
    {
        return $this->container['assignment_grade'];
    }

    /**
     * Sets assignment_grade
     * @param int $assignment_grade Assignment grade. Pass in 0 to delete.
     * @return $this
     */
    public function setAssignmentGrade($assignment_grade)
    {
        $this->container['assignment_grade'] = $assignment_grade;

        return $this;
    }

    /**
     * Gets assignment_instructions
     * @return string
     */
    public function getAssignmentInstructions()
    {
        return $this->container['assignment_instructions'];
    }

    /**
     * Sets assignment_instructions
     * @param string $assignment_instructions Instructions for assignment
     * @return $this
     */
    public function setAssignmentInstructions($assignment_instructions)
    {
        $this->container['assignment_instructions'] = $assignment_instructions;

        return $this;
    }

    /**
     * Gets assignment_store_in_index
     * @return bool
     */
    public function getAssignmentStoreInIndex()
    {
        return $this->container['assignment_store_in_index'];
    }

    /**
     * Sets assignment_store_in_index
     * @param bool $assignment_store_in_index store submissions in institutional index
     * @return $this
     */
    public function setAssignmentStoreInIndex($assignment_store_in_index)
    {
        $this->container['assignment_store_in_index'] = $assignment_store_in_index;

        return $this;
    }

    /**
     * Gets assignment_title
     * @return string
     */
    public function getAssignmentTitle()
    {
        return $this->container['assignment_title'];
    }

    /**
     * Sets assignment_title
     * @param string $assignment_title The title of the assignment
     * @return $this
     */
    public function setAssignmentTitle($assignment_title)
    {
        $this->container['assignment_title'] = $assignment_title;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        }

        return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this));
    }
}



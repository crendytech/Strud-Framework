<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 7/25/2017
 * Time: 11:37 AM
 */

namespace Strud\Helper\Response;

use Strud\Registry;
use Strud\Utils\ArrayUtil;

class Flash
{
    // Message types and shortcuts
    const INFO    = 'i';
    const SUCCESS = 's';
    const WARNING = 'w';
    const ERROR   = 'e';

    // Default message type
    const defaultType = self::INFO;

    /**
     * @var Flash
     */
    private static $instance;

    protected $msgTypes = [
        self::ERROR   => 'error',
        self::WARNING => 'warning',
        self::SUCCESS => 'success',
        self::INFO    => 'info',
    ];

    // Each message gets wrapped in this
    protected $msgWrapper = "<div class='%s'>%s</div>\n";

    // Prepend and append to each message (inside of the wrapper)
    protected $msgBefore = '';
    protected $msgAfter  = '';

    // HTML for the close button
    protected $closeBtn  = '<button type="button" class="close" 
                                data-dismiss="alert" 
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>';

    // CSS Classes
    protected $stickyCssClass = 'sticky';
    protected $msgCssClass = 'alert dismissable';
    protected $cssClassMap = [
        self::INFO    => 'alert-info',
        self::SUCCESS => 'alert-success',
        self::WARNING => 'alert-warning',
        self::ERROR   => 'alert-danger',
    ];

    // Where to redirect the user after a message is queued
    protected $redirectUrl = null;

    // The unique ID for the session/messages (do not edit)
    protected $msgId;
    private $registry;
    private $flash;

    public static function getInstance()
    {
        if(!static::$instance)
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function __construct()
    {
        $this->msgId = sha1(uniqid());
        $this->registry = Registry::getInstance();
        if(!$this->registry->has('flash')) $this->registry->put('flash', []);
        $this->flash =  ($this->registry->has('flash')) ? $this->registry->get("flash") : [];

    }

    /**
     * Add a message of one of the following types
     *
     * @param  string  $message      The message text
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  boolean $sticky       Sticky the message (hides the close button)
     * @return object
     *
     */
    public function info($message, $redirectUrl=null, $sticky=false)
    {
        return $this->add($message, self::INFO, $redirectUrl, $sticky);
    }

    public function success($message, $redirectUrl=null, $sticky=false)
    {
        return $this->add($message, self::SUCCESS, $redirectUrl, $sticky);
    }

    public function warning($message, $redirectUrl=null, $sticky=false)
    {
        return $this->add($message, self::WARNING, $redirectUrl, $sticky);
    }

    public function error($message, $redirectUrl=null, $sticky=false)
    {
        return $this->add($message, self::ERROR, $redirectUrl, $sticky);
    }

    public function sticky($message=true, $redirectUrl=null, $type=self::defaultType)
    {
        return $this->add($message, $type, $redirectUrl, true);
    }

    /**
     * Add a flash message to the session data
     *
     * @param  string  $message      The message text
     * @param  string  $type         The $msgType
     * @param  string  $redirectUrl  Where to redirect once the message is added
     * @param  boolean $sticky       Whether or not the message is stickied
     * @return object
     *
     */
    public function add($message, $type=self::defaultType, $redirectUrl=null, $sticky=false)
    {
        // Make sure a message and valid type was passed
        if (empty($message) || $message == "" || !isset($message)) return false;
        if (strlen(trim($type)) > 1) $type = strtolower($type[0]);
        if (!ArrayUtil::keyExist($type, $this->msgTypes)) $type = $this->defaultType;

        // Add the message to the session data
        if (!ArrayUtil::keyExist( $type, $this->flash )) $this->flash[$type] = array();
        $this->flash[$type][] = ['sticky' => $sticky, 'message' => $message];

        // Handle the redirect if needed
        if (!is_null($redirectUrl)) $this->redirectUrl = $redirectUrl;
        $this->doRedirect();

        return $this;
    }
    //Get Messages Function
    public function getMessages($types = null, $withValue = false)
    {
        if (!isset($this->flash)) return false;

        $output = [];

        $types =  $this->getTypes($types);
        foreach ($types as $type) {
            if (!isset($this->flash[$type]) || empty($this->flash[$type]) ) continue;
            foreach( $this->flash[$type] as $msgData ) {
                if($withValue == true) $output["message"] =  $msgData["message"];
                else
                {
                    if(is_array($msgData["message"]))
                    {
                        $arr = "";
                        foreach ($msgData["message"] as $index => $value)
                        {
                            $arr = $value;
                        }
                        $output["message"] = $arr;
                    }
                }
            }
            return $output;
            $this->clear($type);
        }
    }
    //Display Function
    public function display($types=null, $print=true)
    {

        if (!isset($this->flash)) return false;

        $output = '';

        // Print all the message types
        $types = $this->getTypes($types);


        // Retrieve and format the messages, then remove them from session data
        foreach ($types as $type) {
            if (!isset($this->flash[$type]) || empty($this->flash[$type]) ) continue;
            foreach( $this->flash[$type] as $msgData ) {
                $output .= $this->formatMessage($msgData, $type);
            }
            $this->clear($type);
        }


        // Print everything to the screen (or return the data)
        if ($print) {
            echo $output;
        } else {
            return $output;
        }
    }
    public function getTypes($types = null)
    {
        // Print all the message types
        if (is_null($types) || !$types || (is_array($types) && empty($types)) ) {
            $types = array_keys($this->msgTypes);

            // Print multiple message types (as defined by an array)
        } elseif (is_array($types) && !empty($types)) {
            $theTypes = $types;
            $types = [];
            foreach($theTypes as $type) {
                $types[] = strtolower($type[0]);
            }

            // Print only a single message type
        } else {
            $types = [strtolower($types[0])];
        }
        return $types;
    }
    public function hasErrors()
    {
        return empty($this->flash[self::ERROR]) ? false : true;
    }

    public function getErrors()
    {
        return self::getMessages(self::ERROR, true);
    }

    /**
     * See if there are any queued message
     *
     * @param  string  $type  The $msgType
     * @return boolean
     *
     */
    public function hasMessages($type=null) {
        if (!is_null($type)) {
            if (!empty($this->flash[$type])) return $this->flash[$type];
        } else {
            foreach (array_keys($this->msgTypes) as $type) {
                if (isset($this->flash[$type]) && !empty($this->flash[$type])) return $this->flash[$type];
            }
        }
        return false;
    }

    /**
     * Format a message
     *
     * @param  array  $msgDataArray   Array of message data
     * @param  string $type           The $msgType
     * @return string                 The formatted message
     *
     */
    protected function formatMessage($msgDataArray, $type)
    {

        $msgType = isset($this->msgTypes[$type]) ? $type : $this->defaultType;
        $cssClass = $this->msgCssClass . ' ' . $this->cssClassMap[$type];
        $msgBefore = $this->msgBefore;

        // If sticky then append the sticky CSS class
        if ($msgDataArray['sticky']) {
            $cssClass .= ' ' . $this->stickyCssClass;

            // If it's not sticky then add the close button
        } else {
            $msgBefore = $this->closeBtn . $msgBefore;
        }

        // Wrap the message if necessary
        $msgDataArray['message'] = (is_array($msgDataArray['message']))? implode("<br>",$msgDataArray['message']) : $msgDataArray['message'];
        $formattedMessage = $msgBefore . $msgDataArray['message'] . $this->msgAfter;

        return sprintf(
            $this->msgWrapper,
            $cssClass,
            $formattedMessage
        );
    }

    /**
     * Redirect the user if a URL was given
     *
     * @return object
     *
     */
    protected function doRedirect()
    {
        if ($this->redirectUrl) {
            header('Location: ' . $this->redirectUrl);
            exit();
        }
        return $this;
    }
    protected function clear($types=[])
    {
        if ((is_array($types) && empty($types)) || is_null($types) || !$types) {
            $this->registry->remove("flash_messages");
        } elseif (!is_array($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            unset($this->flash[$type]);
        }

        return $this;
    }



    /**
     * Set the HTML that each message is wrapped in
     *
     * @param string $msgWrapper The HTML that each message is wrapped in.
     *                           Note: Two placeholders (%s) are expected.
     *                           The first is the $msgCssClass,
     *                           The second is the message text.
     * @return object
     *
     */
    public function setMsgWrapper($msgWrapper='')
    {
        $this->msgWrapper = $msgWrapper;
        return $this;
    }

    /**
     * Prepend string to the message (inside of the message wrapper)
     *
     * @param string $msgBefore string to prepend to the message
     * @return object
     *
     */
    public function setMsgBefore($msgBefore='')
    {
        $this->msgBefore = $msgBefore;
        return $this;
    }

    /**
     * Append string to the message (inside of the message wrapper)
     *
     * @param string $msgAfter string to append to the message
     * @return object
     *
     */
    public function setMsgAfter($msgAfter='')
    {
        $this->msgAfter = $msgAfter;
        return $this;
    }

    /**
     * Set the HTML for the close button
     *
     * @param string  $closeBtn  HTML to use for the close button
     * @return object
     *
     */
    public function setCloseBtn($closeBtn='')
    {
        $this->closeBtn = $closeBtn;
        return $this;
    }

    /**
     * Set the CSS class for sticky notes
     *
     * @param string  $stickyCssClass  the CSS class to use for sticky messages
     * @return object
     *
     */
    public function setStickyCssClass($stickyCssClass='')
    {
        $this->stickyCssClass = $stickyCssClass;
        return $this;
    }

    /**
     * Set the CSS class for messages
     *
     * @param string $msgCssClass The CSS class to use for messages
     *
     * @return object
     *
     */
    public function setMsgCssClass($msgCssClass='')
    {
        $this->msgCssClass = $msgCssClass;
        return $this;
    }

    /**
     * Set the CSS classes for message types
     *
     * @param mixed  $msgType    (string) The message type
     *                           (array) key/value pairs for the class map
     * @param mixed  $cssClass   (string) the CSS class to use
     *                           (null) not used when $msgType is an array
     * @return object
     *
     */
    public function setCssClassMap($msgType, $cssClass=null)
    {

        if (!is_array($msgType) ) {
            // Make sure there's a CSS class set
            if (is_null($cssClass)) return $this;
            $msgType = [$msgType => $cssClass];
        }

        foreach ($msgType as $type => $cssClass) {
            $this->cssClassMap[$type] = $cssClass;
        }

        return $this;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/6/2015
 * Time: 11:52 AM
 */

class lm_rating_xml {

    const TAG_EMPLOYEE = 'employee';
    const TAG_METRIC   = 'metrik';
    const TAG_PARAMS   = 'params';
    const TAG_PARAM    = 'param';

    /**
     * @var string $file
     */
    private $file = NULL;

    /**
     * @var XMLReader $xml
     */
    private $xml = NULL;

    public function __construct($file) {
        global $CFG;
        if ($file instanceof stored_file) {
            if ($file->get_mimetype() !== 'application/xml') return NULL;

            $hash = $file->get_contenthash();
            $dir = $hash{0} . $hash{1} . "/" . $hash{2} . $hash{3};
            $this->file = $CFG->dataroot . '/filedir/' . $dir . '/' . $hash;
        } else {
            $this->file = $file;
        }

        if ( ! $this->_open_file()) return NULL;
    }

    private function _open_file() {
        if ( ! file_exists($this->file)) return false;

        $this->xml = new XMLReader();
        $this->xml->open($this->file);

        return true;
    }

    private function _get_next_param() {
        $param = false;
        // find next param node
        while ($this->xml->read()) {
            if ($this->xml->nodeType == XMLReader::ELEMENT
                && $this->xml->localName === self::TAG_PARAM)
            {
                $param = new stdClass;
                // get param attributes
                while ($this->xml->moveToNextAttribute()) {
                    $param->{$this->xml->localName} = $this->xml->value;
                }
                // get properties
                while ($this->xml->read()) {
                    if ($this->xml->nodeType == XMLReader::ELEMENT) {
                        $param->{$this->xml->localName} = $this->xml->readString();
                    } else if ($this->xml->nodeType == XMLReader::END_ELEMENT
                               && $this->xml->localName === self::TAG_PARAM)
                    {
                        break;
                    }
                }
                break;
            } else if ($this->xml->nodeType == XMLReader::END_ELEMENT
                       && $this->xml->localName === self::TAG_PARAMS)
            {
                break;
            }
        }
        return $param;
    }

    private function _get_next_metric() {
        $metric = false;
        // find next metric node
        while ($this->xml->read()) {
            if ($this->xml->nodeType == XMLReader::ELEMENT
                && $this->xml->localName === self::TAG_METRIC)
            {
                $metric = new stdClass;
                // get employee attributes
                while ($this->xml->moveToNextAttribute()) {
                    $metric->{$this->xml->localName} = $this->xml->value;
                }
                // get properties
                while ($this->xml->read()) {
                    if ($this->xml->nodeType == XMLReader::ELEMENT) {
                        if ($this->xml->localName === self::TAG_PARAMS) {
                            // get param values
                            $metric->params = array();
                            while ($param = $this->_get_next_param()) {
                                $metric->params[] = $param;

                            }

                        } else {
                            $metric->{$this->xml->localName} = $this->xml->readString();
                        }
                    } else if ($this->xml->nodeType == XMLReader::END_ELEMENT
                               && $this->xml->localName === self::TAG_METRIC)
                    {
                        break;
                    }
                }
                break;
            } else if ($this->xml->nodeType == XMLReader::END_ELEMENT
                       && $this->xml->localName === self::TAG_EMPLOYEE)
            {
                break;
            }
        }
        return $metric;
    }

    public function get_next_employee() {
        $employee = false;
        // find next employee node
        while ($this->xml->read()) {
            if ($this->xml->nodeType == XMLReader::ELEMENT
                && $this->xml->localName === self::TAG_EMPLOYEE)
            {
                $employee = new stdClass;
                // get employee attributes
                while ($this->xml->moveToNextAttribute()) {
                    $employee->{$this->xml->localName} = $this->xml->value;
                }
                // get metrics
                $employee->metrics = array();
                while ($metric = $this->_get_next_metric()) {
                    $employee->metrics[] = $metric;
                }
                break;
            }
        }
        return $employee;
    }
}

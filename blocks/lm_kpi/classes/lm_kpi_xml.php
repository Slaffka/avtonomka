<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/6/2015
 * Time: 11:52 AM
 */

class lm_kpi_xml {

    const TAG_EMPLOYEE = 'employee';
    const TAG_KPI      = 'kpi';

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

    private function _get_next_kpi() {
        $param = false;
        // find next param node
        while ($this->xml->read()) {
            if ($this->xml->nodeType == XMLReader::ELEMENT
                && $this->xml->localName === self::TAG_KPI)
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
                               && $this->xml->localName === self::TAG_KPI)
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
        return $param;
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
                // get kpis
                $employee->metrics = array();
                while ($kpi = $this->_get_next_kpi()) {
                    $employee->kpis[] = $kpi;
                }
                break;
            }
        }
        return $employee;
    }
}

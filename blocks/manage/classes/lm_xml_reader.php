<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 9:59 AM
 */
class lm_xml_reader {

    /**
     * @var string $file
     */
    private $file = NULL;

    /**
     * @var int $depth
     */
    public $depth = 0;

    /**
     * @var int $path
     */
    private $path = array();

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

    public function next($nodeName = '') {
        $node  = false;
        while ( ! $node && $this->xml->read()) {
            if ($this->xml->nodeType === XMLReader::ELEMENT) {
                $this->path[$this->depth++] = $this->xml->localName;
                if (empty($nodeName) || $this->xml->localName === $nodeName) {
                    $node = $this->xml->localName;
                }
            } elseif ($this->xml->nodeType === XMLReader::END_ELEMENT) {
                array_pop($this->path);
                $this->depth--;
            }
        }
        return $node;
    }

    public function skip() {
        $depth = $this->depth - 1;
        $node = $this->path[count($this->path) - 1];
        while ((
                $this->xml->nodeType !== XMLReader::END_ELEMENT
                || $this->xml->localName !== $node
                || $this->depth !== $depth
            ) && $this->xml->read()
        ) {
            if ($this->xml->nodeType === XMLReader::ELEMENT) $this->depth++;
            elseif ($this->xml->nodeType === XMLReader::END_ELEMENT) $this->depth--;
        }
    }

    public function path() {
        return $this->path;
    }

    public function attrs() {
        if ($this->xml->nodeType === XMLReader::ELEMENT) {
            $attrs = array();
            while ($this->xml->moveToNextAttribute()) {
                $attrs[$this->xml->localName] = $this->xml->value;
            }
            return $attrs;
        } else {
            throw new Exeption('xml cursor not point on Element');
        }
    }

    public function value() {
        $value = '';
        if ($this->xml->nodeType === XMLReader::ELEMENT) {
            $value = $this->xml->readString();
        } else {
            throw new Exeption('xml cursor not point on Element');
        }
        return $value;
    }

    public function object() {
        if ($this->xml->nodeType === XMLReader::ELEMENT && (empty($nodeName) || $this->xml->localName === $nodeName)) {
            $object = new SimpleXMLElement($this->xml->readOuterXML());
            $this->skip();
            return $object;
        } else {
            throw new Exeption('xml cursor not point on Element');
        }
    }
}
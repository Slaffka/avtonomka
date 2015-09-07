<?php

class lm_unireader
{
    public $iid = NULL;

    public $file = NULL;

    /**
     * @var XMLReader
     */
    protected $xml = NULL;

    /**
     * @var csv_import_reader
     */
    protected $csv = NULL;

    public $filetype = NULL;

    public static $acceptedtypes = array('xml', 'csv', 'txt');

    protected $acceptedmime = array('application/xml' => 'xml', 'text/csv' => 'csv', 'text/plain' => 'txt');

    public function __construct($iid)
    {
        if (!$iid) {
            return false;
        }

        if ($iid instanceof stored_file) {
            $this->file = $iid;
        } else {
            $this->iid = $iid;
        }

        if ($this->file) {
            $mimetype = $this->file->get_mimetype();
            if (!isset($this->acceptedmime[$mimetype])) {
                return NULL;
            }


            $this->filetype = $this->acceptedmime[$mimetype];
            if ($this->filetype == 'txt') {
                //TODO: определить что внутри (xml, csv или ошибка)
            }

            if ($this->filetype == 'xml') {
                $this->iid = $this->file->get_contenthash();
            } else if ($this->filetype == 'csv') {
                $this->iid = csv_import_reader::get_new_iid('staff');
            }
        } else {
            $this->filetype = 'xml';
            if (is_numeric($iid)) {
                $this->filetype = 'csv';
            }
        }
    }

    public function start()
    {
        global $CFG;

        switch ($this->filetype) {
            case 'xml':
                if (is_string($this->iid)) {
                    $filepath = $this->iid;
                } else {
                    $path = $this->iid{0} . $this->iid{1} . "/" . $this->iid{2} . $this->iid{3};
                    $filepath = $CFG->dataroot . "/filedir/" . $path . "/" . $this->iid;
                }
                $this->xml = new XMLReader();
                $this->xml->open($filepath);
                break;

            case 'csv':

                $this->csv = new csv_import_reader($this->iid, 'staff');

                if ($this->file) { // Если только загрузили файл (Первый шаг)
                    $fcontent = $this->file->get_content();
                    $encoding = mb_detect_encoding($fcontent);
                    //die( 'Encoding: '.$encoding. '; Mime: ' . $mimetype );
                    $this->csv->load_csv_content($fcontent, $encoding, 'semicolon');
                }

                $this->csv->init();

                break;
        }
    }

    public function next($node = 'position')
    {

        if ($this->xml) {
            $item = array();

            while ($this->xml->read()) {
                if ($this->xml->nodeType == XMLReader::ELEMENT) {
                    // если находим в xml элемент <position> начинаем обрабатывать его
                    if ($this->xml->localName == $node) {
                        while ($this->xml->moveToNextAttribute()) {
                            // здесь мы получаем атрибуты если они есть
                            $item['_attrs'][$this->xml->localName] = $this->xml->value;
                        }

                        while ($this->xml->read()) {
                            if ($this->xml->nodeType == XMLReader::ELEMENT) {
                                $name = strtolower($this->xml->localName);
                                while ($this->xml->moveToNextAttribute()) {
                                    // здесь мы получаем атрибуты если они есть
                                    $item[$name]['_attrs'][$this->xml->localName] = $this->xml->value;
                                }
                                $this->xml->read();
                                //TODO: remove bydlokod!
                                if ($this->xml->nodeType == XMLReader::ELEMENT) {
                                    $item[$name][$this->xml->localName] = $this->xml->readString();
                                } elseif (isset($item[$name]) && is_array($item[$name])) {
                                    $item[$name]['value'] = $this->xml->value;
                                } else {
                                    $item[$name] = $this->xml->value;
                                }
                            }

                            if ($this->xml->nodeType == XMLReader::END_ELEMENT && $this->xml->localName == $node) {
                                break;
                            }
                        }

                        break;
                    }
                }
            }

            if (!empty($item)) {
                return $item;
            }
        }

        if ($this->csv) {
            return $this->csv->next();
        }

        return false;
    }
}

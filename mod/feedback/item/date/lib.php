<?php
/**
 * Библиотека для типа вопроса date
 *
 * @package    Yota
 * @author     Lobov Maxim, lobovmaxim@yandex.ru
 * @version    $Id:$
 * @since      24/10/2013
 *
 */
 
defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

class feedback_item_date extends feedback_item_base {
    protected $type = "date";
    private $commonparams;
    private $item_form;
    private $item;

    public function init() {

    }

    public function build_editform($item, $feedback, $cm) {
        global $DB, $CFG;
        require_once('date_form.php');

        //get the lastposition number of the feedback_items
        $position = $item->position;
        $lastposition = $DB->count_records('feedback_item', array('feedback'=>$feedback->id));
        if ($position == -1) {
            $i_formselect_last = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        } else {
            $i_formselect_last = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);


        //all items for dependitem
        //$feedbackitems = feedback_get_depend_candidates_for_item($feedback, $item);
        $commonparams = array('cmid' => $cm->id,
                             'id' => isset($item->id) ? $item->id : null,
                             'typ' => $item->typ,
                             //'items' => $feedbackitems,
                             'feedback' => $feedback->id);

        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position);

        $this->item_form = new feedback_date_form('edit_item.php', $customdata);
    }

    //this function only can used after the call of build_editform()
    public function show_editform() {
        $this->item_form->display();
    }

    public function is_cancelled() {
        return $this->item_form->is_cancelled();
    }

    public function get_data() {
        if ($this->item = $this->item_form->get_data()) {
            return true;
        }
        return false;
    }

    public function save_item() {
        global $DB;

        if (!$item = $this->item_form->get_data()) {
            return false;
        }

        if (isset($item->clone_item) AND $item->clone_item) {
            $item->id = ''; //to clone this item
            $item->position++;
        }

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('feedback_item', $item);
        } else {
            $DB->update_record('feedback_item', $item);
        }

        return $DB->get_record('feedback_item', array('id'=>$item->id));
    }


    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    public function get_analysed($item, $groupid = false, $courseid = false) {
        global $DB;

        $analysed_val = new stdClass();
        $analysed_val->data = null;
        $analysed_val->answercount  = 0;
        $analysed_val->name = $item->name;

        $values = feedback_get_group_values($item, $groupid, $courseid);
        if ($values) {
            $analysed_val->answercount = count($values);

            $data = array();
            foreach ($values as $value) {
                if(!isset($data[$value->value])){
                    $data[$value->value] = 1;
                }else{
                    $data[$value->value] += 1;
                }
            }
            $analysed_val->data = $data;
        }
        return $analysed_val;
    }

    public function get_printval($item, $value) {

        if (!isset($value->value)) {
            return '';
        }
        return date('d.m.Y', $value->value);
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {

        global $OUTPUT;

        $sep_dec = get_string('separator_decimal', 'feedback');
        if (substr($sep_dec, 0, 2) == '[[') {
            $sep_dec = FEEDBACK_DECIMAL;
        }

        $sep_thous = get_string('separator_thousand', 'feedback');
        if (substr($sep_thous, 0, 2) == '[[') {
            $sep_thous = FEEDBACK_THOUSAND;
        }

        $analysed_item = $this->get_analysed($item, $groupid, $courseid);
        if ($analysed_item->name) {
            echo '<tr><th colspan="2" align="left">';
            if($item->label){
                echo $itemnr.'&nbsp;('.$item->label.') ';
            }
            echo $analysed_item->name;
            echo '</th></tr>';

            $pixnr = 0;
            foreach ($analysed_item->data as $timestamp=>$val) {
                if($analysed_item->answercount){
                    $quotient = $val / $analysed_item->answercount;
                    $intvalue = $pixnr % 10;
                    $pix = $OUTPUT->pix_url('multichoice/' . $intvalue, 'feedback');
                    $pixnr++;
                    $pixwidth = intval($quotient * FEEDBACK_MAX_PIX_LENGTH);
                    $quotient = number_format(($quotient * 100), 2, $sep_dec, $sep_thous);
                    $str_quotient = '';
                    if ($quotient > 0) {
                        $str_quotient = '&nbsp;('. $quotient . '&nbsp;%)';
                    }
                    echo '<tr>';
                    echo '<td align="left" valign="top">
                                -&nbsp;&nbsp;'.trim(date("d.m.Y", $timestamp)).':
                          </td>
                          <td align="left" style="width:'.FEEDBACK_MAX_PIX_LENGTH.';">
                            <img alt="'.$intvalue.'" src="'.$pix.'" height="5" width="'.$pixwidth.'" style="height:50px" />
                            &nbsp;'.$analysed_item->answercount.$str_quotient.'
                          </td>';
                    echo '</tr>';
                }
            }
        }
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false) {

        $data = feedback_get_group_values($item, $groupid, $courseid);

        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $item->name, $xls_formats->head2);

        if (is_array($data)) {
            $row_offset++;
            $col = 2;
            $n = 1;
            $sizeofdata = count($data);
            for ($i = 0; $i < $sizeofdata; $i++) {
                $worksheet->write_string($row_offset-1, $col, $n, $xls_formats->value_bold);
                $worksheet->write_string($row_offset, $col, date("d.m.Y", $data[$i]->value), $xls_formats->default);
                $col ++;
                $n ++;
            }
            $row_offset++;
        }
        $row_offset++;
        return $row_offset;
    }

    /**     
     * print the item at the edit-page of feedback
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item) {
        global $OUTPUT, $DB, $CFG;
        require_once($CFG->libdir . '/form/dateselector.php');



        $align = right_to_left() ? 'right' : 'left';
        $str_required_mark = '<span class="feedback_required_mark">*</span>';

        $presentation = explode ("|", $item->presentation);
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        echo '<div class="feedback_item_label_'.$align.'">';
        echo '('.$item->label.') ';
        echo format_text($item->name.$requiredmark, true, false, false);
        echo '</div>';

        //print the presentation
        echo '<div class="feedback_item_presentation_'.$align.'">';
        echo '<span class="feedback_item_date">';

        $datetime = new MoodleQuickForm_date_selector();
        $datetime->setValue(array('day'=>date("j", time()), 'year'=>date("Y", time()), 'month'=>date("n", time())));
        echo $datetime->toHtml();

        echo '</span>';
        echo '</div>';
    }

    /**     
     * print the item at the complete-page of feedback
     *
     * @global object
     * @param object $item
     * @param string $value
     * @param bool $highlightrequire
     * @return void
     */
    public function print_item_complete($item, $value = '', $highlightrequire = false) {
        global $OUTPUT, $CFG;

        require_once($CFG->libdir . '/form/dateselector.php');



        $align = right_to_left() ? 'right' : 'left';
        $str_required_mark = '<span class="feedback_required_mark">*</span>';

        $presentation = explode ("|", $item->presentation);
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        echo '<div class="feedback_item_label_'.$align.'">';
        echo '('.$item->label.') ';
        echo format_text($item->name.$requiredmark, true, false, false);
        echo '</div>';

        //print the presentation
        echo '<div class="feedback_item_presentation_'.$align.'">';
        echo '<span class="feedback_item_date">';

        $datetime = new MoodleQuickForm_date_selector($item->typ.'_'.$item->id);
        $datetime->setValue(array('day'=>date("j", time()), 'year'=>date("Y", time()), 'month'=>date("n", time())));
        echo $datetime->toHtml();

        echo '</span>';
        echo '</div>';
    }

    /**     
     * print the item at the complete-page of feedback
     *
     * @global object
     * @param object $item
     * @param string $value
     * @return void
     */
    public function print_item_show_value($item, $value = '') {
        global $OUTPUT;
        $align = right_to_left() ? 'right' : 'left';
        $str_required_mark = '<span class="feedback_required_mark">*</span>';

        $presentation = explode ("|", $item->presentation);
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';

        //print the question and label
        echo '<div class="feedback_item_label_'.$align.'">';
            echo '('.$item->label.') ';
            echo format_text($item->name . $requiredmark, true, false, false);
        echo '</div>';
        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        echo $value ? date("d.m.Y", $value) : '&nbsp;';
        echo $OUTPUT->box_end();
    }

    public function check_value($value, $item) {
        //if the item is not required, so the check is true if no value is given
        if ($item->required != 1) {
            return true;
        }
        if (!isset($value['day']) || !isset($value['month']) || !isset($value['year'])) {
            return false;
        }
        return true;
    }

    public function create_value($data) {
        $data = make_timestamp($data['year'],
            $data['month'],
            $data['day'],
            0, 0, 0,
            4,
            true);

        return $data;
    }

    //compares the dbvalue with the dependvalue
    //dbvalue is the value put in by the user
    //dependvalue is the value that is compared
    public function compare_value($item, $dbvalue, $dependvalue) {
        if ($dbvalue == $dependvalue) {
            return true;
        }
        return false;
    }

    public function get_presentation($data) {
        //return $data->itemsize . '|'. $data->itemmaxlength;
        return '';
    }

    public function get_hasvalue() {
        return 1;
    }

    public function can_switch_require() {
        return true;
    }

    public function value_type() {
        return PARAM_RAW;
    }

    public function clean_input_value($value) {
        return $value;
    }
}

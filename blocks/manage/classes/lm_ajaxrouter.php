<?php
/**
 * Универсальный интерфейс для ajax-запросов.
 *
 * Повзволяет обращаться к методам потомков класса DPage, которые расположены в /application/page/*.php
 * через ajax-запросы напрямую. Обязательные параметры, которые должны быть переданы через GET или POST:
 *
 *     __ajc    - ajax command, комманда в соответствии с которой происходит перенаправление запроса
 *                КлассБезПрефикса::ИмяМетода
 *                Имя метода в классе должно начинаться с ключевого слова ajax, но в команде указывается без него.
 *
 * Пример такого запроса приведен ниже (Jquery):
 *
 *     $.ajax({
 *         type: "POST",
 *         url: '/blocks/manage/?__ajc=partners::method_name',
 *         data: "",
 *         success: function(a){
 *             alert(a);
 *         }
 *     });
 *
 */

class lm_ajaxrouter {
    function __construct()
    {

    }

    /**
     * Метод определяет нужно ли маршрутизировать этот запрос, т.е. является ли он
     * ajax-запросом установленного формата. Если да, то вызывает соответствующий
     * метод класса типа block_manage_renderer. Имя метода в этом классе должно начинаться с
     * ключевого слова ajax.
     *
     * @return void В случае успешного роута, работа приложения завершится после
     *              завершения выполнения вызванного метода класса block_manage_renderer.
     *              В случае ошибки формата - die().
     *              В случае, если это __ajc, приложение продолжит работу.
     */
    static public function try_route(){

        // Проверяем передали ли ключевой параметр __ajc, по которому
        // мы определяем, что это ajax-запрос заданный по нашим правилам.
        $ajax_command = optional_param('__ajc', '', PARAM_TEXT);

        if($ajax_command){

            // Разбиваем команду на две части. Левая часть - путь, правая команда.
            $ajax_parts = explode('::', $ajax_command);
            if(isset($ajax_parts[1]) && !empty($ajax_parts)){

                $params = array_merge($_POST, $_GET);
                foreach($params as $pname => $val){
                    if($pname == '__ajc'){
                        unset($params[$pname]);
                    }
                }
                $func_params = (object) $params;

                if( $renderer = lm_renderer::get($ajax_parts[0]) ) {
                    $class_method = 'ajax_' . $ajax_parts[1];

                    $a = (object)array('html'=>'', 'error'=>'');
                    $result = $renderer->$class_method($func_params, $a);

                    if (is_array($result) || is_object($result)) {
                        $result = json_encode($result);
                    }

                    $json_last_error = json_last_error(); // номер ошибки.

                    echo $result;

                    // Завершаем работу приложения
                    die();
                }
            }
        }
    }

    static public function has_errors($exeptions){
        foreach($exeptions as $error=>$isexeption){
            if($isexeption){
                return $error;
            }
        }

        return '';
    }
}
<?php

class block_manage_admin_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=admin';
    public $pagename = 'Администрирование';
    public $type = 'manage_admin';
    public $pagelayout = "base";
    public $details = '';
    public $rolecustomblocks = true;

    public function init_page(){
        global $USER;

        if($this->details = optional_param('details', '', PARAM_TEXT)) {
            $this->pagelayout = "base";
        }

        parent::init_page();

        $this->page->navbar->add('Профиль', new moodle_url('/user/view.php?id='.$USER->id));
        if($this->details) {
            $this->page->navbar->add(get_string("pluginname", 'block_'.$this->details));
        }

        $this->page->requires->js('/blocks/manage/yui/base.js');
    }

    public function navigation(){
        $subpages = array('index' => 'Основное', 'import' => 'Импорт данных');
        return $this->subnav($subpages);
    }

    public static function has_access(){
        return lm_user::is_admin();
    }

    public function main_content(){

        $groups = array();
        switch($this->subpage) {
            case 'index':
                $items = array();
                $items[] = (object) array('code'=>'matrix', 'name'=>'Матрица развития', 'url'=>'/blocks/manage/?_p=matrix');
                $items[] = (object) array('code'=>'coursemanagement', 'name'=>'Курсы и категории', 'url'=>'/course/management.php');
                $items[] = (object) array('code'=>'programs', 'name'=>'Программы', 'url'=>'/blocks/manage/?_p=programs');
                $groups[] = (object) array('items'=>$items, 'name'=>'Обучение');

                $items = array();
                $items[] = (object) array('code'=>'userlist', 'name'=>'Список пользователей', 'url'=>'/admin/user.php');
                $items[] = (object) array('code'=>'rolesmanagement', 'name'=>'Управление ролями', 'url'=>'/admin/roles/manage.php');
                $items[] = (object) array('code'=>'rolesassign', 'name'=>'Назначение ролей', 'url'=>'/admin/roles/assign.php?contextid=1');
                $items[] = (object) array('code'=>'rating', 'name'=>'Рейтинги', 'url'=>'/blocks/manage/?_p=lm_rating');
                $items[] = (object) array('code'=>'rating', 'name'=>'Динамика монет', 'url'=>'/blocks/manage/?_p=lm_bank');
                $items[] = (object) array('code'=>'rating', 'name'=>'Обращения', 'url'=>'/blocks/manage/?_p=lm_feedback');
                $groups[] = (object) array('items'=>$items, 'name'=>'Сотрудники');


                $items = array();
                $items[] = (object) array('code'=>'companies', 'name'=>'Компании', 'url'=>'/blocks/manage/?_p=companies');
                $items[] = (object) array('code'=>'regions', 'name'=>'Регионы', 'url'=>'/blocks/manage/?_p=regions');
                if (has_capability('block/manage:editplaces', context_system::instance())) {
                    $items[] = (object) array('code'=>'auditories', 'name'=>'Аудитории', 'url'=>'/blocks/manage/?_p=places');
                    $items[] = (object) array('code'=>'tt', 'name'=>'Тоговые точки', 'url'=>'/blocks/manage/?_p=places&type=tt');
                }
                if (has_capability('block/manage:partnersview', context_system::instance())) {
                    $items[] = (object) array('code'=>'partners', 'name'=>'Партнеры', 'url'=>'/blocks/manage/?_p=partners');
                }
                $groups[] = (object) array('items'=>$items, 'name'=>'Справочники');

                $items = array();
                $items[] = (object) array('code'=>'plugins', 'name'=>'Все плагины', 'url'=>'/admin/plugins.php');
                $items[] = (object) array('code'=>'blocks', 'name'=>'Блоки', 'url'=>'/admin/blocks.php');
                $items[] = (object) array('code'=>'modules', 'name'=>'Элементы курса', 'url'=>'/admin/modules.php');
                $groups[] = (object) array('items'=>$items, 'name'=>'Плагины');

                $items = array();
                $items[] = (object) array('code'=>'theme', 'name'=>'Выбор темы оформления', 'url'=>'/theme/index.php');
                $items[] = (object) array('code'=>'log', 'name'=>'Журнал событий', 'url'=>'/report/log/index.php');
                $groups[] = (object) array('items'=>$items, 'name'=>'Другое');


                $items = array();
                $items[] = (object) array('code'=>'updates', 'name'=>'Уведомления', 'url'=>'/admin/index.php');
                $items[] = (object) array('code'=>'cachesettings', 'name'=>'Настройки кэширования', 'url'=>'/report/performance/index.php');
                $items[] = (object) array('code'=>'purgecaches', 'name'=>'Очистка кэша', 'url'=>'/admin/purgecaches.php');
                $items[] = (object) array('code'=>'phpinfo', 'name'=>'Информация о php', 'url'=>'/admin/phpinfo.php');
                $items[] = (object) array('code'=>'environment', 'name'=>'Среда (версии ПО)', 'url'=>'/admin/environment.php');
                $groups[] = (object) array('items'=>$items, 'name'=>'Разработчику');

                /*if (has_capability('block/manage:listprograms', context_system::instance())) {
                    $navblock->content .= '<li><a href="/blocks/manage?_p=programs">Программы</a></li>';
                }

                if (has_capability('block/manage:listcompanies', context_system::instance())) {
                    $navblock->content .= '<li><a href="/blocks/manage?_p=companies">Компании</a></li>';
                }

                if (has_capability('block/manage:listregions', context_system::instance())) {
                    $navblock->content .= '<li><a href="/blocks/manage?_p=regions">Регионы</a></li>';
                }*/


                break;


            case 'import':
                $items = array();
                $items[] = (object) array('code'=>'importsales', 'name'=>'Загрузить продажи', 'url'=>'/blocks/manage/?_p=importsales');
                $items[] = (object) array('code'=>'importorg', 'name'=>'Загрузить оргструктуру', 'url'=>'/blocks/manage/?_p=importorg');
                $items[] = (object) array('code'=>'importstaff', 'name'=>'Загрузить сотрудников', 'url'=>'/blocks/manage/?_p=importstaff');
                $items[] = (object) array('code'=>'importkpi', 'name'=>'Загрузить KPI', 'url'=>'/blocks/manage/?_p=importkpi');
                $items[] = (object) array('code'=>'importrating', 'name'=>'Загрузить Рейтинги', 'url'=>'/blocks/manage/?_p=importrating');
                $groups[] = (object) array('items'=>$items, 'name'=>'');

                break;

            default:

                break;
        }

        $this->tpl->groups = $groups;

        return $this->fetch('admin/index.tpl');
    }
}
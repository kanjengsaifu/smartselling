<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Menu extends MY_ICES_Controller {

    private $title = '';
    private $title_icon = '';
    private $path = array();

    function __construct() {
        parent::__construct();
        $this->title = Lang::get(array('Menu'), true, true, false, false, true);
        get_instance()->load->helper(ICES_Engine::$app['app_base_dir'] . 'menu/menu_engine');
        $this->path = Menu_Engine::path_get();
        $this->title_icon = '';
    }

    public function index() {
        //<editor-fold defaultstate="collapsed">
        $action = "";

        $app = new App();
        $db = $this->db;

        $app->set_title($this->title);
        $app->set_breadcrumb($this->title, strtolower('menu'));
        $app->set_content_header($this->title, $this->title_icon, $action);

        $row = $app->engine->div_add()->div_set('class', 'row');
        $form = $row->form_add()->form_set('title', Lang::get('Menu', 'List'))->form_set('span', '12');
        $form->form_group_add()->button_add()->button_set('class', 'primary')->button_set('value', Lang::get(array('New', 'Menu')))
                ->button_set('icon', 'fa fa-plus')->button_set('href', ICES_Engine::$app['app_base_url'] . 'menu/add');



        $cols = array(
            array("name" => "username", "label" => "Username", "data_type" => "text", 'is_key' => true),
            array("name" => "firstname", "label" => "First Name", "data_type" => "text"),
            array("name" => "lastname", "label" => "Last Name", "data_type" => "text"),
            array("name" => "menu_status", "label" => "Status", "data_type" => "text")
        );

        $tbl = $form->table_ajax_add();
        $tbl->table_ajax_set('id', 'ajax_table')
                ->table_ajax_set('base_href', $this->path->index . 'view')
                ->table_ajax_set('lookup_url', $this->path->index . 'ajax_search/menu')
                ->table_ajax_set('columns', $cols)
        ;
        $app->render();
        //</editor-fold>
    }

    public function add() {
        //<editor-fold defaultstate="collapsed">
        $post = $this->input->post();
        $this->view('', 'add');
        //</editor-fold>
    }

    public function view($id = "", $method = "view") {
        //<editor-fold defaultstate="collapsed">
        $this->load->helper($this->path->menu_data_support);
        $this->load->helper($this->path->menu_renderer);

        $action = $method;
        $cont = true;

        if (!in_array($method, array('add', 'view'))) {
            Message::set('error', array("Method error"));
            $cont = false;
        }

        if ($cont) {
            if (in_array($method, array('view'))) {
                if (!count(Menu_Data_Support::menu_get($id)) > 0) {
                    Message::set('error', array("Data doesn't exist"));
                    $cont = false;
                }
            }
        }

        if ($cont) {
            if ($method == 'add')
                $id = '';
            $data = array(
                'id' => $id
            );

            $app = new App();
            $app->set_title($this->title);
            $app->set_menu('collapsed', true);
            $app->set_breadcrumb($this->title, strtolower('menu'));
            $app->set_content_header($this->title, $this->title_icon, $action);
            $row = $app->engine->div_add()->div_set('class', 'row');

            $nav_tab = $row->div_add()->div_set("span", "12")->nav_tab_add();

            $detail_tab = $nav_tab->nav_tab_set('items_add'
                    , array("id" => '#detail_tab', "value" => "Detail", 'class' => 'active'));
            $detail_pane = $detail_tab->div_add()->div_set('id', 'detail_tab')->div_set('class', 'tab-pane active');
            Menu_Renderer::menu_render($app, $detail_pane, array("id" => $id), $this->path, $method);
            if ($method === 'view') {
                $history_tab = $nav_tab->nav_tab_set('items_add'
                        , array("id" => '#status_log_tab', "value" => "Status Log"));
                $history_pane = $history_tab->div_add()->div_set('id', 'status_log_tab')->div_set('class', 'tab-pane');
                Menu_Renderer::menu_status_log_render($app, $history_pane, array("id" => $id), $this->path);

                $history_tab = $nav_tab->nav_tab_set('items_add'
                        , array("id" => '#u_group_log_tab', "value" => "User Group Log"));
                $history_pane = $history_tab->div_add()->div_set('id', 'u_group_log_tab')->div_set('class', 'tab-pane');
                Menu_Renderer::u_group_log_render($app, $history_pane, array("id" => $id), $this->path);
            }


            $app->render();
        } else {
            redirect($this->path->index);
        }
        //</editor-fold>
    }

    public function menu_add() {
        $post = $this->input->post();
        if ($post != null) {
            $param = array('id' => '', 'method' => 'menu_add', 'primary_data_key' => 'menu', 'data_post' => $post);
            SI::data_submit()->submit('menu_engine', $param);
        }
    }

    public function menu_active($id = '') {
        $post = $this->input->post();
        if ($post != null) {
            $param = array('id' => $id, 'method' => 'menu_active', 'primary_data_key' => 'menu', 'data_post' => $post);
            SI::data_submit()->submit('menu_engine', $param);
        }
    }

    public function menu_inactive($id = '') {
        $post = $this->input->post();
        if ($post != null) {
            $param = array('id' => $id, 'method' => 'menu_inactive', 'primary_data_key' => 'menu', 'data_post' => $post);
            $result = SI::data_submit()->submit('menu_engine', $param);
        }
    }

    public function ajax_search($method = '') {
        //<editor-fold defaultstate="collapsed">
        $data = json_decode($this->input->post(), true);
        $lookup_data = isset($data['data']) ? Tools::_str($data['data']) : '';
        $result = array();
        switch ($method) {
            case 'menu':
                $db = new DB();
                $lookup_str = $db->escape('%' . $lookup_data . '%');
                $config = array(
                    'additional_filter' => array(
                    ),
                    'query' => array(
                        'basic' => '
                            select * from (
                                select distinct *
                                from menu t1
                                where t1.status>0
                                
                        ',
                        'where' => '
                            and (
                                t1.name like ' . $lookup_str . ' 
                            )
                        ',
                        'group' => '
                            )tfinal
                        ',
                        'order' => 'order by id desc'
                    ),
                );
                $temp_result = SI::form_data()->ajax_table_search($config, $data, array('output_type' => 'object'));
                $t_data = $temp_result->data;
                foreach ($t_data as $i => $row) {
                    $row->menu_status = SI::get_status_attr(
                                    SI::type_get('Menu_Engine', $row->menu_status, '$status_list')['text']
                    );
                }
                $temp_result = json_decode(json_encode($temp_result), true);
                $result = $temp_result;

                break;
        }

        echo json_encode($result);
        //</editor-fold>
    }

    public function data_support($method = '') {
        //<editor-fold defaultstate="collapsed">
        $path = Menu_Engine::path_get();
        get_instance()->load->helper($path->menu_data_support);
        $data = json_decode($this->input->post(), true);
        $result = SI::result_format_get();
        $msg = [];
        $success = 1;
        $response = array();
        switch ($method) {
            case 'menu_get':
                //<editor-fold defaultstate="collapsed">
                $db = new DB();
                $response = array();
                $menu_id = Tools::_str(isset($data['data']) ? $data['data'] : '');
                $temp = Menu_Data_Support::menu_get($menu_id);
                if (count($temp) > 0) {
                    $menu = $temp['menu'];
                    $menu['menu_status'] = array(
                        'id' => $menu['menu_status'],
                        'text' => SI::get_status_attr(
                                SI::type_get('menu_engine', $menu['menu_status'], '$status_list'
                                )['text']
                        ),
                    );


                    $next_allowed_status_list = SI::form_data()
                            ->status_next_allowed_status_list_get('menu_engine', $menu['menu_status']['id']
                    );

                    $u_group = array();
                    foreach ($temp['u_group'] as $idx => $row) {
                        $row['text'] = Tools::html_tag('strong', $row['name'])
                                . ' ' . SI::type_get('ICES_Engine', $row['app_name'], '$app_list')['text'];
                        $u_group[] = $row;
                    }
                    unset($menu['u_group']);

                    $response['u_group'] = $u_group;
                    $response['menu'] = $menu;
                    $response['menu_status_list'] = $next_allowed_status_list;
                }


                //</editor-fold>
                break;
            case 'u_group_get':
                //<editor-fold defaultstate="collapsed">
                get_instance()->load->helper(ICES_Engine::$app['app_base_dir'] . 'u_group/u_group_engine');
                $u_group_path = U_Group_Engine::path_get();
                get_instance()->load->helper($u_group_path->u_group_data_support);

                $t_u_group = U_Group_Data_Support::u_group_list_get(array('u_group_status' => 'active'));
                $u_group = array();
                foreach ($t_u_group as $idx => $row) {
                    $u_group[] = array(
                        'id' => $row['id'],
                        'text' => Tools::html_tag('strong', $row['name'])
                        . ' ' . SI::type_get('ICES_Engine', $row['app_name'], '$app_list')['text'],
                    );
                }
                $response['u_group'] = $u_group;
                //</editor-fold>
                break;
        }
        $result['success'] = $success;
        $result['msg'] = $msg;
        $result['response'] = $response;
        echo json_encode($result);
        //</editor-fold>
    }

}

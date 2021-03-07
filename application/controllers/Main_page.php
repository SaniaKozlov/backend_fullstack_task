<?php

use Model\Boosterpack_model;
use Model\Login_model;
use Model\Post_model;
use Model\User_model;
use Model\Like_model;
use Model\Comment_model;
use Model\Transaction_model;
use Model\Transaction_type;
use Model\Transaction_info;
use Model\Null_Model;

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 10.11.2018
 * Time: 21:36
 */
class Main_page extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        if (is_prod())
        {
            die('In production it will be hard to debug! Run as development environment!');
        }
    }

    public function index()
    {
        $user = User_model::get_user();

        App::get_ci()->load->view('main_page', ['user' => User_model::preparation($user, 'default')]);
    }

    public function get_all_posts()
    {
        $posts =  Post_model::preparation(Post_model::get_all(), 'main_page');
        return $this->response_success(['posts' => $posts]);
    }

    public function get_post($post_id){ // or can be $this->input->post('news_id') , but better for GET REQUEST USE THIS

        $post_id = intval($post_id);

        if (empty($post_id)){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }

        try
        {
            $post = new Post_model($post_id);
        } catch (EmeraldModelNoDataException $ex){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NO_DATA);
        }


        $posts =  Post_model::preparation($post, 'full_info');
        return $this->response_success(['post' => $posts]);
    }


    public function comment($post_id, $message, $parent){ // or can be App::get_ci()->input->post('news_id') , but better for GET REQUEST USE THIS ( tests )

        if (!User_model::is_logged()){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NEED_AUTH);
        }

        $post_id = intval($post_id);
        $parent = intval($parent);

        if (empty($post_id) || empty($message)){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }

        try
        {
            $post = new Post_model($post_id);
        } catch (EmeraldModelNoDataException $ex){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_NO_DATA);
        }

        // Todo: 2 nd task Comment
        $post->comment([
            'user_id'   => User_model::get_session_id(),
            'assign_id' => $post_id,
            'text'      => html_entity_decode($message),
            'parent_id' => ($parent !== 0) ? $parent : null,
        ]);

        $posts =  Post_model::preparation($post, 'full_info');
        return $this->response_success(['post' => $posts]);
    }


    public function login()
    {

        $data = json_decode($this->input->raw_input_stream, 1);
        $login = $data['login']??null;
        $password = $data['password']??null;
        if (empty($login) || empty($password)){
            return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }

        $user = User_model::get_user_by_email($login);

        if (!hash_equals($user->get_password(), $password)) {
            return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }


        // But data from modal window sent by POST request.  App::get_ci()->input...  to get it.


        //Todo: 1 st task - Authorisation.

        Login_model::start_session($user);

        return $this->response_success(['user' => $user]);
    }


    public function logout()
    {
        Login_model::logout();
        redirect(site_url('/'));
    }

    public function add_money(){
        $data = json_decode($this->input->raw_input_stream, 1);
        $user_balance = User_model::refill_balance($data['sum']);
        Transaction_model::create([
            'user_id' => User_model::get_session_id(),
            'ent_id' => Null_Model::get_id(),
            'ent_type' => Null_Model::class,
            'type' => Transaction_type::TRANSACTION_TYPE_REFILL,
            'balance_after' => $user_balance,
            'amount' => $data['sum'],
        ]);

        return $this->response_success(['amount' => $user_balance]); // Колво лайков под постом \ комментарием чтобы обновить . Сейчас рандомная заглушка
    }

    public function buy_boosterpack(){
        $data = json_decode($this->input->raw_input_stream, 1);
        App::get_ci()->s->start_trans();
        try {
            $boosterpack = new Boosterpack_model($data['id']??null);
            $new_balance = User_model::withdraw_balance($boosterpack->get_price());
            Transaction_model::create([
                'user_id' => User_model::get_session_id(),
                'ent_id' => $boosterpack->get_id(),
                'ent_type' => get_class($boosterpack),
                'type' => Transaction_type::TRANSACTION_TYPE_WRITE_OFF,
                'info' => Transaction_info::TRANSACTION_BUY_BOOSTERPACK,
                'balance_after' => $new_balance,
                'amount' => $boosterpack->get_price(),
            ]);
            $likes = Boosterpack_model::buy_pack($boosterpack);
            $likeBalance = User_model::refill_likes($likes);
            Transaction_model::create([
                'user_id' => User_model::get_session_id(),
                'ent_id' => $boosterpack->get_id(),
                'ent_type' => get_class($boosterpack),
                'type' => Transaction_type::TRANSACTION_TYPE_REFILL,
                'info' => Transaction_info::TRANSACTION_REFILL_LIKES,
                'balance_after' => $likeBalance,
                'amount' => $likes,
            ]);
        } catch (Exception $e) {
            App::get_ci()->s->rollback();
            return $this->response_error($e->getMessage());
        }

        App::get_ci()->s->commit();

        return $this->response_success(['amount' => $likes]); // Колво лайков под постом \ комментарием чтобы обновить . Сейчас рандомная заглушка
    }


    public function like($id, $type){
        switch ($type) {
            case 'post':
                $model = new Post_model($id);
                break;
            case 'comment':
                $model = new Comment_model($id);
                break;
            default:
                return $this->response_error(CI_Core::RESPONSE_GENERIC_WRONG_PARAMS);
        }
        App::get_ci()->s->start_trans();
        try {
            User_model::withdraw_likes(1);
            Like_model::save_like($model);
        } catch (Exception $e) {
            App::get_ci()->s->rollback();

            return $this->response_error($e->getMessage());
        }

        App::get_ci()->s->commit();

        return $this->response_success(['likes' => $model->get_likes()]); // Колво лайков под постом \ комментарием чтобы обновить . Сейчас рандомная заглушка
    }


    //GENERATE TEST DATA FOR SQL QUERIES
    public function generate()
    {
        App::get_ci()->s->from('user')
            ->where('id >', 0)
            ->update(['wallet_balance' => 100000])->execute();
        $start = (new DateTime())->modify('-1 month')->setTime(0,0,0);
        $end = (new DateTime())->setTime(0,0,0);
        for($i = $start; $i <= $end; $i->modify('+1 day')){
            $date = $i;
            for ($h = 0; $h < 24; $h++) {
                $cnt = rand(5, 20);
                for($r = 0; $r < $cnt; $r++) {
                    Login_model::start_session(new User_model(rand(1,2)));
                    $boosterpack = new Boosterpack_model(rand(1,3));
                    $new_balance = User_model::withdraw_balance($boosterpack->get_price());
                    Transaction_model::create([
                        'user_id' => User_model::get_session_id(),
                        'ent_id' => $boosterpack->get_id(),
                        'ent_type' => get_class($boosterpack),
                        'type' => Transaction_type::TRANSACTION_TYPE_WRITE_OFF,
                        'info' => Transaction_info::TRANSACTION_BUY_BOOSTERPACK,
                        'balance_after' => $new_balance,
                        'amount' => $boosterpack->get_price(),
                        'time_cerated' => $date->format('Y-m-d H:i:s')
                    ]);
                    $likes = Boosterpack_model::buy_pack($boosterpack);
                    $likeBalance = User_model::refill_likes($likes);
                    Transaction_model::create([
                        'user_id' => User_model::get_session_id(),
                        'ent_id' => $boosterpack->get_id(),
                        'ent_type' => get_class($boosterpack),
                        'type' => Transaction_type::TRANSACTION_TYPE_REFILL,
                        'info' => Transaction_info::TRANSACTION_REFILL_LIKES,
                        'balance_after' => $likeBalance,
                        'amount' => $likes,
                        'time_cerated' => $date->format('Y-m-d H:i:s')
                    ]);
                }
                $date->modify("+$h hour");
            }
        }
    }

}

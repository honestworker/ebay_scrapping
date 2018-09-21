<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	private $userSession = false;
	private $userAccount = false;
    private $userNotifications = array();
    private $isAuthed = false;

	function __construct() {
        parent::__construct();
        $this->load->library('session');

        if($sessionData = $this->session->get_userdata()){
        	if(!empty($sessionData['_session-id'])){
        		if($session = $this->sessions->getById($sessionData['_session-id'])){
        			$this->userSession = $session;

        			if($user = $this->users->getById($session->userID)){
        				$this->userAccount = $user;
                        $this->isAuthed = true;
        				return;
        			}
        		}
        	}
        }
    }

    public function watchlist_delete($id){
        $this->load->model('watchlist');

        if($this->isAuthed){
            $this->watchlist->delete($id);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array()));
    }

    public function watchlist_update(){
        $this->load->model('watchlist');

        $data = array(
            'stars' => (int)$this->input->post('stars'),
            'message' => trim($this->input->post('message'))
        );

        $id = (int)$this->input->post('ID');

        if($this->isAuthed){
            $this->watchlist->update($id, $data);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array()));
    }

    public function watchlist_add() {
        $this->load->model('watchlist');
        $stars = (int)$this->input->post('stars');
        $sellerid = (int)$this->input->post('sellerID');
        $message = trim($this->input->post('message'));

        if($this->isAuthed){
            $this->watchlist->add($this->userSession->userID, $sellerid, $stars, $message);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array()));
    }

    public function watchlist_check($sellerid){
        $this->load->model('watchlist');
        $ret = array('watching' => false);

        if($this->isAuthed && $this->watchlist->exists($this->userSession->userID, $sellerid)){
            $ret['watching'] = true;
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
    }

    public function delete_users(){
        $ret = array();

        if($this->isAuthed && $this->usergroups->getNameById($this->userAccount->groupId) === 'admin'){
            if($users = $this->input->post('users')){
                $users = array_map('intval', $users);

                foreach($users as $id){
                    $this->users->delete($id);
                }
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
    }

    public function get_notifications(){
        $ret = array();
        $this->load->model('notifications');

        if($this->isAuthed){
            $ret = $this->notifications->getByUser($this->userSession->userID);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
    }

    public function delete_notification($id){
        $ret['status'] = 'error';

        $this->load->model('notifications');
        if($this->isAuthed){
            $this->notifications->deleteByIdAndUser($id, $this->userSession->userID);
            $ret['status'] = 'success';
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
    }

    public function set_notification_seen($id){
        $ret['status'] = 'error';

        $this->load->model('notifications');
        if($this->isAuthed){
            $this->notifications->setSeen($id);
            $ret['status'] = 'success';
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ret));
    }

    public function check_api_available() {
        $user_info = $this->users->getById($this->userSession->userID);
        
        $hot_items_count = -3;
        if ($user_info) {
            $group_id = $user_info->groupId;
            $hot_items_update = $user_info->hot_items_update;
            $hot_items_count = $user_info->hot_items_count;
            $group_name = $this->usergroups->getNameById($group_id);
            if ($group_name === 'admin') {
                $hot_items_count = 0;
            } else if ($group_name === 'premium' || $group_name === 'professional') {
                $now_date = $trans_before = date("Y-m-d H:i:s");
                $date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
                $update_flag = 0;
                if ($group_name === 'premium') {
                    $limit_count = 10;
                } else if ($group_name === 'professional') {
                    $limit_count = 20;
                }
                if ($hot_items_update < $date_before) {
                    $hot_items_count = $limit_count;
                    $update_flag = 1;
                }
                $hot_items_count = $hot_items_count - 1;
                if ($hot_items_count < 0) {
                    if ($group_name === 'premium') {
                        $hot_items_count = -1;
                    } else if ($group_name === 'professional') {
                        $hot_items_count = -2;
                    }
                } else {
                    if ($update_flag == 1) {
                        $this->users->update($this->userSession->userID, array('hot_items_update' => $now_date, 'hot_items_count' => $hot_items_count));
                    } else {
                        $this->users->update($this->userSession->userID, array('hot_items_count' => $hot_items_count));
                    }
                }
            }
        }
        
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($hot_items_count));
    }
}
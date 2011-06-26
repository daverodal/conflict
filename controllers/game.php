<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Game extends CI_Controller {
  protected $alive = true;
  function kill(){
    $doc = $this->couch->get("TheGame");
    $newDoc = new stdClass();
    $newDoc->_id = $doc->_id;
    $newDoc->_rev = $doc->_rev;
    $success = $this->couch->put($doc->_id,$newDoc);
    echo $success;
     
  }
  function index(){
    $this->load->helper("url");
    $user = $this->session->userdata("user");
    if(!$user){
      redirect("/game/login/");
    }
    echo "Welcome $user";
    $this->load->view("game");

  }
  function logout(){
    $user = $this->session->userdata("user");
    $this->load->helper("url");
    $doc = $this->couch->get("TheGame");
    $newUsers = array();
    if(in_array($user,$doc->users)){
      foreach($doc->users as $aUser){
        if($user != $aUser){
          $newUsers[] = $aUser;
        }
      }
    }
    $doc->users[] = $newUsers;
    $this->couch->put("TheGame",$doc);
    $user = $this->session->sess_destroy();
    redirect("/game/");
  }

  function login(){
    $this->load->helper("url");
    $user = $this->session->userdata("user");
    $data = $this->input->post();
    if(!$user && $data){
      $user = $data['name'];
      $this->session->set_userdata(array("user"=>$user));
      $doc = $this->couch->get("TheGame");
      if(!is_array($doc->users)){
        $doc->users = array();
      }
      if(!in_array($user,$doc->users)){
        $doc->users[] = $user;
      }
      $doc->$user->data->army = array();
      $doc->$user->data->gold = 50;
      $doc->$user->data->mines = 5;
      $doc->$user->data->factories = 0;
      $doc->$user->data->startdate = time();
      $this->couch->put("TheGame",$doc);
      redirect("/game/");
    }
    $this->load->view("login");

  }
  public function fetch($last_seq = '') {
    header("Content-Type: application/json");
    if($last_seq){
      $seq = $this->couch->get("/_changes?since=$last_seq&feed=longpoll");

    }else{
      $seq = $this->couch->get("/_changes");
    }
    $last_seq = $seq->last_seq;
    $data = $this->input->post();
    $chatsIndex = 0;
    if($data["chatsIndex"])
    $chatsIndex = $data["chatsIndex"];
    $doc = $this->couch->get("TheGame");
    $user = $this->session->userdata("user");
    foreach($doc->users as $auser){
      if($auser != $user){
        $myEnemy = $auser;
        break;
      }
    }
    $chats = array_slice($doc->chats,$chatsIndex);
    $chatsIndex = count($doc->chats);
    $userData = $doc->$user->data;
    $gold = $userData->gold;
    $mines = $userData->mines;
    $factories = $userData->factories;
    $army = $userData->army;
    $users = $userData->users;
    $lose = $userData->lose;
    $win = $doc->$myEnemy->data->lose;
    $clock = $doc->clock;
    $building = $userData->building;
    $enemy = $doc->$myEnemy->data->army;
    $battle = array($doc->$user->data->battle,$doc->$myEnemy->data->battle);
    echo json_encode(compact('chats','chatsIndex','last_seq', 'users', 'army','mines','factories','gold','clock','building',"enemy","lose","battle","win"));
  }
  public function add($chat) {
    $user = $this->session->userdata("user");
    if(!$user){
      redirect("/game/login");
    }
    $saved = false;
    echo "HI";
    if ($_POST) {
      $data = $this->input->post();
      while(!$saved){
        try{
          $doc = $this->couch->get("TheGame");

          if($data["army"]){
            if($doc->$user->data->gold >= 100){
              $doc->$user->data->gold -= 100;
              $num = count($doc->$user->data->army);
              $doc->$user->data->building[] = array("name"=>"$num Army","hp"=>0);
              var_dump($doc);
            }
          }
          if($data["mines"]){
            if($doc->$user->data->gold >= 50){
              $doc->$user->data->gold -= 50;
              $doc->$user->data->mines++;
            }
          }
          if($data["factories"]){
            if($doc->$user->data->gold >= 50){
              $doc->$user->data->gold -= 50;
              $doc->$user->data->factories++;
            }
          }
          if($data["chats"]){
            $doc->chats[] = $data["chats"];
          }
          $success = $this->couch->put($doc->_id,$doc);
          $saved = true;
        }catch(Exception $e){if($e->getCode() == 409)continue;$success = $e->getMessage();}
      }
    }
    return compact('success');
  }
  public function clock(){
    //$this->`();
    $startdate = time();
    $doc = $this->couch->get("TheGame");
    foreach($doc->users as $user){
      $doc->$user->data->lose = false;
      $doc->$user->data->gold = 50;
      $doc->$user->data->mines = 5;
      $doc->$user->data->factories = 0;
      $doc->$user->data->battle = false;
      $doc->$user->data->building = array();
      $doc->$user->data->army = array();
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
      $doc->$user->data->army[] = array("name"=>"$num Army","hp"=>30);
    }
    $success = $this->couch->put($doc->_id,$doc);


    try{
      $loop = 0;
      while($this->alive){
        $date = date("H:i:s A");
        echo "getting\n";
        try{
          $doc = $this->couch->get("TheGame");
        }catch(Exceptioin $e){
          echo "Getting "+$e->getMessage();
          echo "Getting "+$e->getCode();
        }
        if($startdate){
          $doc->startdate = $startdate;
          $startdate = false;
        }
        echo "Got\n";
        echo $date = time();
        $doc->date = $date;
        echo $doc->startdate;
        $sd = new DateTime("@".$doc->startdate);
        $cd = new DateTime("@$date");
        $datediff = $cd->diff($sd);
        echo $doc->clock = $datediff->format("%H:%I:%S");
        // echo $doc->clock = date_diff(new DateTime("@$date","@".$doc->startdate))->format("%H:%I:%S");
        foreach($doc->users as $user){
          $doc->$user->data->gold += $doc->$user->data->mines * .33;
          $factories = $doc->$user->data->factories;
          $building = array();
          foreach($doc->$user->data->building as $k => $unit){
            if($factories > 0){
              $unit->hp++;
              $factories--;
              if($unit->hp >= 30){
                $doc->$user->data->army[] = $unit;
              }else{
                $building[] = $unit;
              }
            }else{
              $building[] = $unit;
            }
          }
          $doc->$user->data->building = $building;
          if(!$doc->$user->data->battle){
            if(!$army = array_shift($doc->$user->data->army)){
              $doc->$user->data->lose = true;
              $this->alive = false;
            }else{
              $doc->$user->data->battle = $army;
            }
          }else{
            $doc->$user->data->battle->hp -= 1;
            
            if($doc->$user->data->battle->hp <= 0){
              $doc->$user->data->battle = false;
            }
          }

        }
        try{
          echo "putting\n";
          $success = $this->couch->put($doc->_id,$doc);
          if($army)var_dump($army);
          echo "put $loop\n";$loop++;
        }catch(Exception $e){
          if($e->getCode() == 409){
            $this->alive = true;
            continue;
            echo "Exception !!! "+$e->getCode();
            echo "Exception !!! "+$e->getMessage();
          }
        }
        sleep(1);
      }
      echo "out of here why?";
    }catch(Exception $e){echo $e->getMessage; echo $e->getCode; echo $loop;}
  }

}
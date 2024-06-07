<?php
namespace src\controllers;

use \core\Controller;
use \src\handlers\UserHandler;
use \src\handlers\PostHandler;

class ProfileController extends Controller {

    private $loggedUser;

    public function __construct() {
        $this->loggedUser = UserHandler::checkLogin();
        if( $this->loggedUser === false ) {
            $this->redirect('/login');
        }

    }
    public function index($atts = []) {        
        $page = intval(filter_input(INPUT_GET, 'page'));
        //detectando usuário acessado
        $id = $this->loggedUser->id;
        if(!empty($atts['id'])) {
            $id = $atts['id'];
        }
        //Pegando info do usuário
        $user = UserHandler::getUser($id, true);

        if (!$user) {
            $this->redirect('/');
        }
         

        $dateFrom = new \DateTime($user->birthdate);
        $dateTo = new \DateTime('today');
        $user->ageYears = $dateFrom->diff($dateTo)->y;
        //pegando o feed
        $feed = PostHandler::getUserFeed(
            $id, 
            $page, 
            $this->loggedUser->id
    );
    //verificar se está seguindo o usuário
    $isFollowing = false;
    if($user->id != $this->loggedUser->id) {
        $isFollowing = UserHandler::isFollowing($this->loggedUser->id, $user->id);
    }

        $this->render('profile', [
            'loggedUser'=>$this->loggedUser,
            'user'=>$user,
            'feed'=>$feed,
            'isFollowing'=>$isFollowing,
        ]);
    }

    public function follow($atts) {
        $to = intval($atts['id']);

        if (UserHandler::idExists($to)) {
            if(UserHandler::isFollowing($this->loggedUser->id, $to)) {
                UserHandler::unfollow($this->loggedUser->id, $to);
            } else {
                UserHandler::follow($this->loggedUser->id, $to);
            }

        }
        $this->redirect("/perfil/$to");
    }

    public function friends($atts = []) {
        $id = $this->loggedUser->id;
        if(!empty($atts['id'])) {
            $id = $atts['id'];
        }
        //Pegando info do usuário
        $user = UserHandler::getUser($id, true);

            if (!$user) {
                $this->redirect('/');
            }         

            $dateFrom = new \DateTime($user->birthdate);
            $dateTo = new \DateTime('today');
            $user->ageYears = $dateFrom->diff($dateTo)->y;

            $isFollowing = false;
                if($user->id != $this->loggedUser->id) {
                    $isFollowing = UserHandler::isFollowing($this->loggedUser->id, $user->id);
        }

        $this->render('profile_friends', [
            'loggedUser'=>$this->loggedUser,
            'user'=>$user,
            'isFollowing'=>$isFollowing,
        ]);
    }

    public function photos($atts = []) {
        $id = $this->loggedUser->id;
        if(!empty($atts['id'])) {
            $id = $atts['id'];
        }
        //Pegando info do usuário
        $user = UserHandler::getUser($id, true);

            if (!$user) {
                $this->redirect('/');
            }         

            $dateFrom = new \DateTime($user->birthdate);
            $dateTo = new \DateTime('today');
            $user->ageYears = $dateFrom->diff($dateTo)->y;

            $isFollowing = false;
                if($user->id != $this->loggedUser->id) {
                    $isFollowing = UserHandler::isFollowing($this->loggedUser->id, $user->id);
        }

        $this->render('profile_photos', [
            'loggedUser'=>$this->loggedUser,
            'user'=>$user,
            'isFollowing'=>$isFollowing,
        ]);
    }
    public function config() {
        $id = $this->loggedUser->id;
       
        $user = UserHandler::getUser($id, true);

        $this->render('config', [
            'loggedUser'=>$this->loggedUser,
            'user'=>$user,
            'flash'=>$_SESSION['flash'] ?? ''
        ]);
        $_SESSION['flash'] = '';
    }

    public function configUpdate() {
        $id = $this->loggedUser->id;
        $user = UserHandler::getUser($id);


        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $birthdate = filter_input(INPUT_POST, 'birthdate');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $work = filter_input(INPUT_POST, 'work', FILTER_SANITIZE_STRING);
        $newPassword = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $password_confirm = filter_input(INPUT_POST, 'password_confirm', FILTER_SANITIZE_STRING);

        //verifica se o usuário existe


        if($user) {
            $updates = [];

            if($name) {
                $updates['name'] = $name;
            }
            

            if ($birthdate) {
                $birthdateArray = explode('/', $birthdate);

                if(count($birthdateArray) === 3) {
                    $birthdate = $birthdateArray[2].'/'.$birthdateArray[1].'/'.$birthdateArray[0];
                    if (strtotime($birthdate) === false) {
                        $_SESSION['flase'] = 'Data de nascimento inválida';
                        $this->redirect('/config');
                        exit;
                    }
                    $updates['birthdate'] = $birthdate;
                } else {
                    $_SESSION['flash'] = 'Data de nascimento inválida';
                    $this->redirect('/config');
                    exit;
                }

            }

            if ($email) {
                if (!UserHandler::emailExists($email) || $email == $user->email) {                    
                    $updates['email'] = $email;
                } else {
                    $_SESSION['flash'] = 'Email já cadastrado!';
                    $this->redirect('/config');
                }
            }

            if ($city) {
                $updates['city'] = $city;
            }

            if ($work) {
                $updates['work'] = $work;
            }

            if ($newPassword && $password_confirm) {

                if ($newPassword != $password_confirm) {
                    $_SESSION['flash'] = 'As senhas não conferem!';
                    $this->redirect('/config');
                }

                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updates['password'] = $hash;                
           
            }

            if(!empty($updates)) {
                UserHandler::updateUser($id, $updates);
            }
            $this->redirect('/config');
        } else {
           ' <script> alert($_SESSION["flash"] = "Usuário não encontrado!")</script>';
            $this->redirect('/config');
        }


    }


}
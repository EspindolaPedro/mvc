<?php
namespace src\controllers;

use \core\Controller;
use \src\handlers\UserHandler;

class ConfigController extends Controller {

    private $loggedUser;


    public function __construct() {
        $this->loggedUser = UserHandler::checkLogin();
        if( $this->loggedUser === false ) {
            $this->redirect('/login');
        }
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
        if ($user) {
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
            // Avatar
            if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name'])) {
                $newAvatar = $_FILES['avatar'];
                
                if (in_array($newAvatar['type'], ['image/jpeg', 'image/jpg', 'image/png'])) {
                    $avatarName = $this->cutImage($newAvatar, 200, 200, 'media/avatars');
                    $updates['avatar'] = $avatarName;
                }
            }
            // Cover
            if (isset($_FILES['cover']) && !empty($_FILES['cover']['tmp_name'])) {
                $newCover = $_FILES['cover'];
                
                if (in_array($newCover['type'], ['image/jpeg', 'image/jpg', 'image/png'])) {
                    $coverName = $this->cutImage($newCover, 850, 310, 'media/covers');
                    $updates['cover'] = $coverName;
                }
            }
            if (!empty($updates)) {
                UserHandler::updateUser( $id, $updates );
            }
            $this->redirect('/config');
        } else {
           ' <script> alert($_SESSION["flash"] = "Usuário não encontrado!")</script>';
            $this->redirect('/config');
        }
    }
    private function cutImage($file, $w, $h, $folder) {
        list($widthOrigin, $heightOrigin) = getimagesize($file['tmp_name']);
        $ratio = $widthOrigin / $heightOrigin;
    
        $newWidth = $w;
        $newHeight = $newWidth / $ratio;
        if ($newHeight < $h) {
            $newHeight = $h;
            $newWidth = $newHeight * $ratio;
        }     
        $x = $w - $newWidth;   
        $y = $h - $newHeight;
        $x = $x < 0 ? $x / 2 : $x;
        $y = $y < 0 ? $y / 2 : $y;
    
        $finalImage = imagecreatetruecolor($w, $h);
        switch ($file['type']) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
            default:
               $_SESSION['flash'] = "Este tipo de imagem não é suportado";
        }
    
        imagecopyresampled($finalImage, $image, 
        $x, $y, 0, 0, 
        $newWidth, $newHeight, 
        $widthOrigin, $heightOrigin);
    
        $fileName = md5(time() . rand(0, 9999)) . '.jpg';
        imagejpeg($finalImage, $folder . '/' . $fileName);
    
        return $fileName;
    }
    
}
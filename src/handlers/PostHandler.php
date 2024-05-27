<?php
namespace src\handlers;
use \src\models\Post;
use \src\models\User;
use \src\models\UserRelation;

class PostHandler {
    public static function addPost($idUser, $type, $body) {
        $body = trim($body);
        if (!empty($idUser) && !empty($body) ) {
            Post::insert([
                'id_user'=>$idUser,
                'type'=>$type,
                'created_at'=>date('Y-m-d H-m-s'),
                'body'=>$body
            ])->execute();
        } 
    }

    public static function getHomeFeed($idUser) {
        //1º
        $userList = UserRelation::select()->where('user_from', $idUser)->get();
        $users = [];
        foreach($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }
        $users[] = $idUser;
        //2º
        $postList = Post::select()
            ->where('id_user', 'in', $users)
            ->orderBy('created_at', 'desc')
        ->get();
        //3º
        $posts = [];
        foreach($postList as $postItem) {
            $newPost = new Post();
            $newPost->id = $postItem['id'];
            $newPost->type = $postItem['type'];
            $newPost->created_at = $postItem['created_at'];
            $newPost->body = $postItem['body'];

            if($postItem['id_user'] == $idUser) { //Verifica se a postagem pe do usuário.
                $newPost->mine = true;
            }
            //4º
            $newUser = User::select()->where('id', $postItem['id_user'])->one();
            $newPost->user = new User();
            $newPost->user->id = $newUser['id'];
            $newPost->user->name = $newUser['name'];
            $newPost->user->avatar = $newUser['avatar'];
            
            //5º info de
            $newPost->likeCount = 0;
            $newPost->liked = false;
            $newPost->comments = [];

            $posts[] = $newPost;
        }

        return $posts;
    }
}
<?php
namespace src\handlers;
use \src\models\Post;
use \src\models\User;
use \src\models\UserRelation;
use \src\models\PostLike;
use \src\models\PostComment;

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
        };
    }

    public static function _postListToObject($postList, $loggedUserId) {
        $posts = [];
        foreach($postList as $postItem) {
            $newPost = new Post();
            $newPost->id = $postItem['id'];
            $newPost->type = $postItem['type'];
            $newPost->created_at = $postItem['created_at'];
            $newPost->body = $postItem['body'];
    
            if($postItem['id_user'] == $loggedUserId) { //Verifica se a postagem do usuário.
                $newPost->mine = true;
            }
            //4º
            $newUser = User::select()->where('id', $postItem['id_user'])->one();
            $newPost->user = new User();
            $newPost->user->id = $newUser['id'];
            $newPost->user->name = $newUser['name'];
            $newPost->user->avatar = $newUser['avatar'];
          //5º info de
            $likes = PostLike::select()->where('id_post', $postItem['id'])->get();
            
          $newPost->likeCount = count($likes);
          $newPost->liked = self::isLiked($postItem['id'], $loggedUserId);

          $newPost->comments = PostComment::select()->where('id_post', $postItem['id'])->get();
          foreach($newPost->comments as $key => $comment) {
            $newPost->comments[$key]['user'] = User::select()->where('id', $comment['id_user'])->one();
          }
  
          $posts[] = $newPost;
      }  
      return $posts;
    }

    public static function isLiked($id, $loggedUserId) {
        $myLike = PostLike::select()
        ->where('id_post', $id)
        ->where('id_user', $loggedUserId)
        ->get();

        if (count($myLike) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function deleteLike($id, $loggedUserId) {
        PostLike::delete()
            ->where('id_post', $id)
            ->where('id_user', $loggedUserId)
        ->execute();
    }

    public static function addLike($id, $loggedUserId) {
        PostLike::insert([
            'id_post' => $id,
            'id_user' => $loggedUserId,
            'created_at' => date('Y-m-d H:i:s')
        ])->execute();
    }

    public static function addComment($id, $txt, $loggedUserId) {
        PostComment::insert([
            'id_post' => $id,
            'id_user' => $loggedUserId,
            'created_at' => date('Y-m-d H:i:s'),
            'body' => $txt,
        ])->execute();
    }
    
    public static function getUserFeed($idUser, $page, $loggedUserId) {
        $perPage = 2;
        
        $postList = Post::select()
        ->where('id_user', $idUser)
        ->orderBy('created_at', 'desc')
        ->page($page, $perPage) /*Pega as páginas e mostra 2 items por page */
    ->get();

    $total = Post::select() 
        ->where('id_user', $idUser)
    ->count();
    $pageCount = ceil($total / $perPage);
    //3º
        
       $posts = self::_postListToObject($postList, $loggedUserId);
      
       return [
        'posts'=>$posts,
        'pageCount'=>$pageCount,
        'currentPage'=>$page
    ];
    }

    public static function getHomeFeed($idUser, $page) {
        $perPage = 2;
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
            ->page($page, $perPage) /*Pega as páginas e mostra 2 items por page */
        ->get();

        $total = Post::select() //Vai retornar o total dos posts e podemos pegar esse total e dividir os items por página
            ->where('id_user', 'in', $users)
        ->count();
        $pageCount = ceil($total / $perPage);
        //3º
       $posts = self::_postListToObject($postList, $idUser);

        return [
            'posts'=>$posts,
            'pageCount'=>$pageCount,
            'currentPage'=>$page
        ];
    }
    public static function getPhotosFrom($idUser) {
        $photosData = Post::select()
            ->where('id_user', $idUser)
            ->where('type', 'photo')
        ->get();

        $photos = [];

        foreach($photosData as $photo) {
            $newPost = new Post();
            $newPost->id = $photo['id'];
            $newPost->type = $photo['type'];
            $newPost->created_at = $photo['created_at'];
            $newPost->body = $photo['body'];

            $photos[] = $newPost;
        }
        return $photos;
    }
}
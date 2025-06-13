<?php

namespace models;

use classes\Model;
use classes\Core;
use Utils\ApiDeletable;
use Utils\ApiReadable;
use Utils\ApiCreatable;
use Utils\ApiUpdatable;

/**
 * @property int $id
 * @property int $thread_id
 * @property int $parent_id
 * @property int $user_id
 * @property string $content
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Comments extends Model
{
    public static $table = 'comments';
    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;


    public function __construct()
    {
        parent::__construct();
    }

    public static function getCommentsThread($threadId)
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
            SELECT c.*, 
                   CONCAT(u.name, ' ', u.lastname) AS author_name,
                   u.avatar AS author_avatar,

                   -- Лайки коментаря
        (
            SELECT COUNT(*) 
            FROM likes l 
            WHERE l.target_type = 'comment' 
              AND l.target_id   = c.id 
              AND l.type        = 'like'
        ) AS likes_count,

        -- Дизлайки коментаря
        (
            SELECT COUNT(*) 
            FROM likes l 
            WHERE l.target_type = 'comment' 
              AND l.target_id   = c.id 
              AND l.type        = 'dislike'
        ) AS dislikes_count

            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.thread_id = :thread_id
            ORDER BY c.created_at ASC
        ", ['thread_id' => $threadId]);
    }

    public static function countCommentsInThread()
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
        SELECT t.subcategory_id, COUNT(c.id) as cnt
        FROM comments c
        JOIN threads t ON c.thread_id = t.id
        GROUP BY t.subcategory_id
    ");
    }
}

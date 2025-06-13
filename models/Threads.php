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
 * @property int $subcategory_id
 * @property int $user_id
 * @property string $title
 * @property string $content
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Threads extends Model
{
    public static $table = 'threads';
    use ApiCreatable, ApiReadable, ApiUpdatable, ApiDeletable;

    public function __construct()
    {
        parent::__construct();
    }

    public static function getThread($threadId)
    {
        $db = Core::getInstance()->db;
        $thread =  $db->selectQuery("
            SELECT t.*, 
                   CONCAT(u.name, ' ', u.lastname) AS author_name,
                   u.avatar AS author_avatar, 
                   s.slug AS subcategory_slug,

                   -- Кількість лайків треду
                   (
                       SELECT COUNT(*) 
                       FROM likes l 
                       WHERE l.target_type = 'thread' 
                         AND l.target_id   = t.id 
                         AND l.type        = 'like'
                   ) AS likes_count,
           
                   -- Кількість дизлайків треду
                   (
                       SELECT COUNT(*) 
                       FROM likes l 
                       WHERE l.target_type = 'thread' 
                         AND l.target_id   = t.id 
                         AND l.type        = 'dislike'
                   ) AS dislikes_count

            FROM threads t
            JOIN users u ON t.user_id = u.id
            JOIN subcategories s ON t.subcategory_id = s.id
            WHERE t.id = :id
            LIMIT 1
        ", ['id' => $threadId])[0] ?? null;

        if (!$thread) {
            Core::getInstance()->error(404);
            exit;
        }

        return $thread;
    }

    public static function countThreadsInSubcategory($subcategoryId)
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
            SELECT COUNT(*) AS count FROM threads WHERE subcategory_id = :subcategory_id
        ", ['subcategory_id' => $subcategoryId])[0]['count'];
    }

    public static function getLatestThreads()
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
        SELECT th.id, th.title, th.created_at, CONCAT(u.name, ' ', u.lastname) AS username
        FROM threads th
        JOIN users u ON th.user_id = u.id
        ORDER BY th.created_at DESC
        LIMIT 5
    ");
    }

    public static function getLatestActiveThreads()
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
        SELECT subcategory_id, CONCAT(name, ' ', lastname, ' ', patronymic) as username, created_at FROM (
            SELECT 
                t.subcategory_id, 
                u.name, u.lastname, u.patronymic,
                th.created_at
            FROM threads th
            JOIN users u ON th.user_id = u.id
            JOIN threads t ON th.id = t.id

            UNION ALL

            SELECT 
                t.subcategory_id,
                u.name, u.lastname, u.patronymic,
                c.created_at
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN threads t ON c.thread_id = t.id
        ) AS combined
        WHERE (subcategory_id, created_at) IN (
            SELECT subcategory_id, MAX(created_at)
            FROM (
                SELECT t.subcategory_id, th.created_at
                FROM threads th
                JOIN threads t ON th.id = t.id

                UNION ALL

                SELECT t.subcategory_id, c.created_at
                FROM comments c
                JOIN threads t ON c.thread_id = t.id
            ) AS sub
            GROUP BY subcategory_id
        )
    ");
    }

    public static function getThreads($subcategoryId, $pageSize, $offset)
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
            SELECT 
            t.id,
            t.title,
            t.created_at,
            t.pinned,
            CONCAT(u.name, ' ', u.lastname) AS author_name,
        
            -- Коментарі: якщо немає — вивести 1
            (
                SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id
            ) + 1 AS comments_count,
            
            -- Лайки як є
            (SELECT COUNT(*) FROM likes l WHERE l.target_type = 'thread' AND l.target_id = t.id) AS likes_count,
            
            -- Якщо немає коментарів — автор треду
            COALESCE((
                SELECT CONCAT(us.name, ' ', us.lastname)
                FROM comments c2
                JOIN users us ON c2.user_id = us.id
                WHERE c2.thread_id = t.id
                ORDER BY c2.created_at DESC
                LIMIT 1
            ), CONCAT(u.name, ' ', u.lastname)) AS last_comment_user,
        
            -- Якщо немає коментарів — дата створення треду
            COALESCE((
                SELECT c2.created_at
                FROM comments c2
                WHERE c2.thread_id = t.id
                ORDER BY c2.created_at DESC
                LIMIT 1
            ), t.created_at) AS last_comment_date,
        
            -- Якщо немає коментарів — аватар автора треду
            COALESCE((
                SELECT us.avatar
                FROM comments c2
                JOIN users us ON c2.user_id = us.id
                WHERE c2.thread_id = t.id
                ORDER BY c2.created_at DESC
                LIMIT 1
            ), u.avatar) AS last_comment_avatar
        
        FROM threads t
        JOIN users u ON t.user_id = u.id
        WHERE t.subcategory_id = :subcategory_id
        ORDER BY 
            CASE WHEN t.pinned IS NULL OR t.pinned = 0 THEN 1 ELSE 0 END,
            t.pinned ASC,
            t.created_at DESC

        LIMIT :limit OFFSET :offset    
        ", [
            'subcategory_id' => $subcategoryId,
            'limit' => $pageSize,
            'offset' => $offset
        ]);
    }

    public static function threadCounts()
    {
        $db = Core::getInstance()->db;
        return $db->selectQuery("
        SELECT subcategory_id, COUNT(*) as cnt
        FROM threads
        GROUP BY subcategory_id
    ");
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Posts_model extends CI_Model
{
    /* جلب المقالات */
    function get_posts($offset, $limit)
    {
        $sql = "
        SELECT
        parts.part_title
        , posts.post_id
        , posts.post_part_id
        , posts.post_title
        , posts.post_date
        , posts.post_visits
        , posts.post_tags
        , users.user_name
    FROM
        posts
        INNER JOIN parts
            ON (posts.post_part_id = parts.part_id)
        INNER JOIN users
        ON (posts.post_author_id = users.user_id)
        ORDER BY posts.post_id DESC
        LIMIT $offset , $limit
    ";
        return $this->db->query($sql)->result();
    }


    /* عدد كل المقالات */
    function count_total_posts()
    {
        $sql = "SELECT count(*) total FROM posts ";
        return $this->db->query($sql)->result();
    }

    /* إضافة مقال */
    function add_post($data)
    {
        $this->db->insert("posts", $data);
    }

    /* تحديث مقال */
    function update_post($data, $id)
    {
        $this->db->where('post_id', $id);
        $this->db->update('posts', $data);
    }

    /* حذف مقال */
    function delete_post($id)
    {
        $this->db->where('post_id', $id);
        $this->db->delete('posts');
    }

    /* جلب مقال حسب الآي دي  */
    function get_post_by_id($id)
    {
        $this->db->where('post_id', $id);
        $q = $this->db->get('posts');
        if ($q->num_rows() > 0) return $q->result();
        else return false;
    }

    /* جلب آخر المقالات */
    function get_last_posts($limit)
    {
        $sql = " SELECT post_id,post_title,post_content,post_date FROM posts ORDER BY post_id DESC LIMIT $limit ";
        return $this->db->query($sql)->result();
    }

    /* جلب آخر التعليقات */
    function get_last_comments($limit, $offset = 0)
    {
        $sql = "
                SELECT c.`comment_content`,c.`comment_date`,c.`comment_post_id`,p.`post_title`,u.`user_name`
                FROM comments c
                INNER JOIN posts p
                ON c.`comment_post_id`<=>p.`post_id`
                INNER JOIN users u
                ON c.`comment_user_id`<=>u.`user_id`
                WHERE c.`comment_approved`<=>TRUE 
                ORDER BY c.`comment_id` DESC
                LIMIT $offset, $limit
            ";
        return $this->db->query($sql)->result();
    }

    /* الأكثر قراءة */
    function get_most_read_posts($limit)
    {
        $sql = " SELECT post_id,post_title,post_content,post_visits FROM posts ORDER BY post_visits DESC LIMIT $limit ";
        return $this->db->query($sql)->result();
    }

    /* جلب مقال */
    function get_post($id)
    {
        $sql = "
                SELECT posts.* , parts.part_id , parts.part_title , users.user_name
                FROM posts
                INNER JOIN parts
                ON (posts.post_part_id = parts.part_id)
                INNER JOIN users
                ON (posts.post_author_id = users.user_id)
                WHERE (posts.post_id <=>$id)
            ";
        $q   = $this->db->query($sql);
        if ($q->num_rows() > 0) {
            $this->db->query("UPDATE posts SET post_visits = post_visits + 1 WHERE post_id = $id");
            return $q->result();
        } else return false;
    }

    /* البحث عن المقالات التي تحوي نص معين */
    function search($key)
    {
        $sql = "
                SELECT parts.part_title , posts.* , users.user_name, parts.part_image
                FROM posts
                INNER JOIN parts
                ON (posts.post_part_id = parts.part_id)
                INNER JOIN users
                ON (posts.post_author_id = users.user_id)
                WHERE (posts.post_title LIKE '%$key%'
                OR posts.post_content LIKE '%$key%'
                OR posts.post_tags LIKE '%$key%')
                LIMIT 10
    ";
        $q   = $this->db->query($sql);
        if ($q->num_rows() > 0) return $q->result();
        else return false;
    }

    /* إضافة تعليق */
    function add_comment($comment)
    {
        $this->db->insert("comments", $comment);
    }

    /* جلب تعليقات مقال */
    function get_comments($post_id)
    {
        $sql = "
                SELECT comments.*, users.user_name, users.user_photo 
                FROM comments 
                INNER JOIN users 
                ON ( comments.comment_user_id = users.user_id ) 
                WHERE comments.comment_post_id <=>$post_id
                AND comment_approved<=>TRUE
            ";
        $q   = $this->db->query($sql);
        if ($q->num_rows() > 0)
            return $q->result();
        else return false;
    }

    /* جلب التعليقات  */
    function get_all_comments($offset, $limit)
    {
        $sql = "
                SELECT c.*, p.`post_title`,u.`user_name`
                FROM comments c
                INNER JOIN posts p
                ON c.`comment_post_id`<=>p.`post_id`
                INNER JOIN users u
                ON c.`comment_user_id`<=>u.`user_id`
                ORDER BY c.`comment_id` DESC
                LIMIT $offset, $limit
            ";
        return $this->db->query($sql)->result();
    }

    /* إحصاء عدد التعليقات */
    function comments_count()
    {
        return $this->db->count_all_results('comments');
    }

    /* حذف تعليق */
    function delete_comment($id)
    {
        $this->db->where('comment_id', $id);
        $this->db->delete('comments');
    }

    /* تبديل حالة تعليق: موافق أو غير موافق */
    function approve_comment($id)
    {
        $current_statue = $this->db->where("comment_id", $id)
            ->get("comments")->row()->comment_approved;
        if ($current_statue)
            $this->db->where("comment_id", $id)->update('comments', ['comment_approved' => false]);
        else
            $this->db->where("comment_id", $id)->update('comments', ['comment_approved' => true]);
    }

    /* جلب عدد التعليقات */
    function count_new_comments()
    {
        $this->db->where("comment_approved", false);
        return $this->db->count_all_results('comments');
    }
}
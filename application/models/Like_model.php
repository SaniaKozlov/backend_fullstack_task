<?php


namespace Model;

use CI_Emerald_Model;
use App;

class Like_model extends CI_Emerald_Model implements Buyable
{
    const CLASS_TABLE = 'likes';

    protected $id;
    protected $ent_type;
    protected $ent_id;
    protected $time_created;

    /**
     * @return string
     */
    public function get_time_created(): string
    {
        return $this->time_created;
    }

    /**
     * @param string $time_created
     *
     * @return bool
     */
    public function set_time_created(string $time_created)
    {
        $this->time_created = $time_created;
        return $this->save('time_created', $time_created);
    }

    public static function save_like(LikedInterface $model)
    {
        App::get_ci()->s->from(self::CLASS_TABLE)->insert([
            'ent_type' => get_class($model),
            'ent_id' => $model->get_id(),
            'user_id' => User_model::get_session_id(),
        ])->execute();
        return new static(App::get_ci()->s->get_insert_id());
    }

    public static function get_likes_count($model)
    {
        return App::get_ci()->s->from(Like_model::CLASS_TABLE)->where([
            'ent_type' => get_class($model),
            'ent_id' => $model->get_id(),
        ])->count();
    }
}
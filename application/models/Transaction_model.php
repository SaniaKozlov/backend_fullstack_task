<?php


namespace Model;

use CI_Emerald_Model;
use App;

class Transaction_model extends CI_Emerald_Model {
    const CLASS_TABLE = 'transactions';

    /** @var int */
    protected $id;
    /** @var int */
    protected $user_id;
    /** @var int */
    protected $ent_id;
    /** @var string */
    protected $ent_type;
    /** @var int */
    protected $type;
    /** @var int */
    protected $info;
    /** @var float */
    protected $amount;
    /** @var float */
    protected $balance_after;
    /** @var float */
    /** @var string */
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

    public static function create($data)
    {
        App::get_ci()->s->from(self::CLASS_TABLE)->insert($data)->execute();
    }

}
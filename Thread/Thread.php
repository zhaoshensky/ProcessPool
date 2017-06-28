<?php
/**
 * User: Today
 * Email: zhaojintian@ediankai.com
 * Introduce:
 *     线程基础类
 * Date: 2017/6/5
 * Time: 17:06
 */

namespace Shark\Library\Thread;


class Thread
{

    /**
     * 线程id
     * @var int
     */
    private $id;

    /**
     * 数据库id
     * @var int
     */
    private $record_id;

    /**
     * 线程存在时间
     * @var int
     */
    private $alive;

    /**
     * 线程状态
     * @var int
     * @introduce
     */
    private $status;

    /**
     * 线程详细信息
     * @var array
     */
    private $info;

    function __construct()
    {
        //进程id
        $threadId = getmypid ( );
        if( $threadId > 10  ) {
            $this->id = $threadId;
        }

    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
//    public function setId($id)
//    {
//        $this->id = $id;
//    }

    /**
     * @return int
     */
    public function getAlive()
    {
        return $this->alive;
    }

    /**
     * @param int $alive
     */
    public function setAlive($alive)
    {
        $this->alive = $alive;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param array $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * @return int
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * @param int $record_id
     */
    public function setRecordId($record_id)
    {
        $this->record_id = $record_id;
    }




}
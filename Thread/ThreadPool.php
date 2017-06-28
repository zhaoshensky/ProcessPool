<?php
/**
 * User: Today
 * Email: zhaojintian@ediankai.com
 * Introduce:
 *      线程池控制方法 -- 计划任务方式
 * warning: 此线程池添加线程非线程安全
 * version：1.0
 * Date: 2017/6/5
 * Time: 17:09
 */

namespace Shark\Library\Thread;

use Shark\Console\Commands\ExportCSVConsole;
use Shark\Model\Export\Export;
use Shark\Model\RedisModel;
class ThreadPool
{

    /**
     * 池大小
     * @var int|mixed
     */
    private $pool_size;

    /**
     * 默认单位为分钟
     * @var
     */
    private $check_dead_limit;

    /**
     * 是否需要自己抛出进程
     * @var int|mixed
     */
//    private $is_self_thread ;

    /**
     * 线程数组
     * @var array
     */
//    private $threads_array ;


    /**
     * redis 数据存储
     * @var
     */
    private $redis_threads;

    /**
     * redis key前缀
     * @var
     */
    private $redis_threads_key_pre ;

    /**
     * 供应商id
     * @var int
     */
    private $supplier_id;

    /**
     * User: Today
     * Email: zhaojintian@ediankai.com
     * @param ${PARAM_LIST}
     * ThreadPool constructor.
     */

    function __construct(  )
    {

        $this->redis_threads = new RedisModel();
//        $this->redis_threads_key_pre = 'Threads:Report:' . $supplier_id ;
        //每个供应商并发数设置
        $this->pool_size = config('sys.export_pool_size');;
        //检测超时 分钟
        $this->check_dead_limit = config('sys.check_dead_limit') ;
        $this->redis_threads_key_pre = 'Threads:Export:';
//        $this->is_self_thread = false;
//        $this->threads_array = array();
    }

    /**
     * @return mixed
     */
    public function getRedisThreadsKeyPre()
    {
        $pre_key = $this->redis_threads_key_pre;
        $pre_key = $pre_key . $this->getSupplierId() ;
        return $pre_key;
    }

    /**
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * @param int $supplier_id
     */
    public function setSupplierId($supplier_id)
    {
        $this->supplier_id = $supplier_id;
    }


    /**
     * @param mixed $redis_threads_key_pre
     */
//    public function setRedisThreadsKeyPre($redis_threads_key_pre)
//    {
//        $this->redis_threads_key_pre = $redis_threads_key_pre;
//    }



    /**
     * @return int|mixed
     */
    public function getPoolSize()
    {
        return $this->pool_size;
    }

    /**
     * @param int|mixed $pool_size
     */
    public function setPoolSize($pool_size)
    {
        $this->pool_size = $pool_size;
    }

    /**
     * @return int|mixed
     */
    public function getExportTargetLimit()
    {
        return $this->export_target_limit;
    }

    /**
     * @param int|mixed $export_target_limit
     */
    public function setExportTargetLimit($export_target_limit)
    {
        $this->export_target_limit = $export_target_limit;
    }

    /**
     * @return mixed
     */
    public function getCheckDeadLimit()
    {
        return $this->check_dead_limit ;
    }

    /**
     * @param mixed $check_dead_limit
     */
//    public function setCheckDeadLimit($check_dead_limit)
//    {
//        $this->check_dead_limit = $check_dead_limit;
//    }

    /**
     * @return int|mixed
     */
    public function getIsSelfThread()
    {
        return $this->is_self_thread;
    }

    /**
     * @param int|mixed $is_self_thread
     */
    public function setIsSelfThread($is_self_thread)
    {
        $this->is_self_thread = $is_self_thread;
    }

    /**
     * 添加线程方法
     * @return array
     */
    public function add( $Thread )
    {
        if( ! $Thread instanceof Thread){
            //非派生自Thread对象
            return false;
        }

        if( $this->isFull() ){
            //已经超过最大池限制
            return false;
        }
        //数据库记录id
        $recordId = $Thread->getRecordId();
        //进程id

        if( !($Thread->getId() > 10)  ) {
            return false;
        }
//        $cur_thread_key = $this->redis_threads_key_pre + $threadId;
        /**
         * 此部分为非线程安全
         */
        //判断id是否存在
        if( $this->get( $recordId ) ){
            //线程id已存在
            return false;
        }

        return $this->redis_threads->hSet( $this->getRedisThreadsKeyPre(),$recordId ,serialize($Thread) );
    }

    /**
     * 获取对应线程信息
     * @param int
     */
    public function get( $recordId )
    {
//        $cur_thread_key = $this->redis_threads_key_pre + $threadId;
        $threadInfo = $this->redis_threads->hGet( $this->getRedisThreadsKeyPre(),$recordId );
        return unserialize($threadInfo);
    }

    /**
     * Method getThreadNum
     * User: Today
     * Email: zhaojintian@ediankai.com
     * @return mixed
     *
     * 获取当前pool中线程总数
     */
    public function getThreadNum(){

        $n = $this->redis_threads->hLen( $this->getRedisThreadsKeyPre() );
        if( !($n > 0 ) ) $n = 0;
        return $n;

    }

    /**
     * Method ifFull
     * User: Today
     * Email: zhaojintian@ediankai.com
     * @return bool
     * 判断线程池是否可接受线程
     */
    public function isFull(){

        if( $this->getThreadNum() >= $this->getPoolSize() ){
            //已经超过最大池限制
            return true;
        }

        return false;

    }

    /**
     * Method checkAlive
     * User: Today
     * Email: zhaojintian@ediankai.com
     * 心跳检测
     */
    public function checkAlive(){

        $suppliers_info = $this->redis_threads->keys( $this->redis_threads_key_pre."*" );
        if( empty($suppliers_info) ) return;

        foreach($suppliers_info as $supplier_info){
//            $supplier_info  也就是商户id
            $threads_info = $this->redis_threads->hGetAll( $supplier_info );
            if( empty($threads_info) ) return ;
            foreach( $threads_info as $thread_info ){
                $thread_info = unserialize($thread_info);
                //获取线程启动时间
                $alive_time = $thread_info->getAlive();
                if( time() - $alive_time > $this->getCheckDeadLimit() * 60 ){
                    //超过最大生存周期 通知更新状态并且杀死进程
                    $this->killThread( $supplier_info,$thread_info->getRecordId() );
                }
            }

        }


    }

    /**
     * Method killThread
     * User: Today
     * Email: zhaojintian@ediankai.com
     * @param $threadId
     *
     * 超过最大生存周期 通知更新状态并且杀死进程 删除进程
     */
    private function killThread( $pre_key,$recordId ){
        
        $data=[
            'status'=>4,//状态 1 待导出 2 导出中 3导出成功 4 导出失败
            'export_result'=>'当前导出已经超过最大时间'.$this->getCheckDeadLimit().'分钟，被杀死',
            'updated_at'=>date('Y-m-d H:i:s'),
        ];
        $ExportModel = new Export();
        $ExportModel->updateOne($recordId,$data);
        $this->delThread( $pre_key, $recordId);
//        $this->redis_threads->hDel($this->redis_threads_key_pre,$recordId);
    }

    /**
     * Method delThread
     * User: Today
     * Email: zhaojintian@ediankai.com
     * @param $recordId
     * 删除线程记录
     */
    public function delThread( $pre_key,$recordId ){

        $this->redis_threads->hDel( $pre_key,$recordId);

    }

}
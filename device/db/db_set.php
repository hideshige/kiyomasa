<?php
/**
 * データベースのセットアップ
 *
 * @author   Sawada Hideshige
 * @version  1.0.2.0
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

use Php\Framework\Device\S;
use Php\Framework\Device\Log;

class DbSet
{
    /**
     * データベースの接続
     * @param bool $debug
     * @return void
     */
    public function dbConnect(bool $debug): void
    {
        S::$dbm = new DbModule();
        $res_dbm = S::$dbm->connect(DB_MASTER_SERVER, DB_MASTER_USER,
            DB_MASTER_PASSWORD, DB_MASTER_NAME, 'mysql', $debug);
        if ($res_dbm === false) {
            throw new \Error('DB_MASTER Connect Error');
        }
        S::$dbs = new DbModule();
        $res_dbs = S::$dbs->connect(DB_SLAVE_SERVER, DB_SLAVE_USER,
            DB_SLAVE_PASSWORD, DB_SLAVE_NAME, 'mysql', $debug);
        if ($res_dbs === false) {
            Log::error('DB_SLAVE Connect Error ---> DB_MASTER Connect Change');
            S::$dbs = S::$dbm;
        }
    }
}

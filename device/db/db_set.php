<?php
/**
 * データベースのセットアップ
 *
 * @author   Sawada Hideshige
 * @version  1.0.1.0
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
     */
    public function dbConnect(bool $debug): void
    {
        S::$dbm = new DbCrud();
        $res_dbm = S::$dbm->connect(
            DB_MASTER_SERVER, DB_MASTER_USER, DB_MASTER_PASSWORD, DB_MASTER_NAME
        );
        if (!$res_dbm) {
            throw new \Error('DB_MASTER Connect Error');
        }
        S::$dbs = new DbCrud();
        $res_dbs = S::$dbs->connect(
            DB_SLAVE_SERVER, DB_SLAVE_USER, DB_SLAVE_PASSWORD, DB_SLAVE_NAME
        );
        if (!$res_dbs) {
            Log::error(
                'DB_SLAVE Connect Error ---> DB_MASTER Connect Change'
            );
            S::$dbs = S::$dbm;
        }
        S::$dbm->debug = $debug;
        S::$dbs->debug = $debug;
    }
}

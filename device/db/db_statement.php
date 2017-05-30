<?php
/**
 * データベース(プリペアドステートメント関連)
 *
 * @author   Sawada Hideshige
 * @version  1.4.4.1
 * @package  device/db
 *
 */

namespace Php\Framework\Device\Db;

class DbStatement extends DbModule
{
    /**
     * ステートメントの準備
     * @global int $g_counter
     * @param string $statement_id プリペアドステートメントID
     * @param string $sql クエリ
     * @return object|bool
     */
    public function prepare(
        string $statement_id = 'stmt',
        string $sql = ''
    ) {
        try {
            global $g_counter;
            if ($sql) {
                $this->sql = $sql;
            }

            $this->stmt[$statement_id] = $this->connect->prepare($this->sql);

            if ($this->debug) {
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}PREPARE {{STATEMENT}}%s FROM '%s';\n",
                    $g_counter,
                    $statement_id,
                    $this->sql
                );
                $g_counter ++;
            }

            return $this->stmt[$statement_id];
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
    
    /**
     * 明示的にプレースホルダを変える
     * (通常は使用しなくても問題がないが必要な時があれば利用する)
     * @param bool $name_flag プレースホルダが:nameならTRUE,?ならFALSE
     * @param string $statement_id プリペアドステートメントID
     */
    public function nameFlag(
        bool $name_flag,
        string $statement_id = 'stmt'
    ): void {
        $this->name[$statement_id] = $name_flag;
    }

    /**
     * ステートメントの実行
     * @global int $g_counter
     * @param array $params 挿入する値
     * @param string $statement_id プリペアドステートメントID
     * @return int 成功すれば変更した件数を返す
     */
    public function bind(
        array $params = [],
        string $statement_id = 'stmt'
    ): int {
        try {
            global $g_counter;
            
            $this->before();
            
            if (!isset($this->name[$statement_id])) {
                $this->name[$statement_id] = true;
            }

            $i = 1;
            $u = $this->bind_params = [];
            if (is_array($params)) {
                $this->addTimeColumn(
                    $this->do[$statement_id], $params, $statement_id, true);

                foreach ($params as $k => $v) {
                    if ($k === 0) {
                        // array_spliceで入れたupdated_atはキーが0になるためキー名を変える
                        $k = 'updated_at';
                    }
                    
                    $d_v = (strlen($v) > 5000) ? '[longtext or binary]' : $v;

                    $name = $this->name[$statement_id] ? $k : $i;
                    if ($this->debug) {
                        if ($d_v === null) {
                            $this->disp_sql .= sprintf(
                                "{{COUNTER %d}}SET {{AT}}@%s = {{NULL}}NULL;\n",
                                $g_counter,
                                $name
                            );
                        } else if (is_numeric($d_v)) {
                            $this->disp_sql .= sprintf(
                                "{{COUNTER %d}}SET {{AT}}@%s = {{INT}}%d;\n",
                                $g_counter,
                                $name,
                                $d_v
                            );
                        } else {
                            $this->disp_sql .= sprintf(
                                "{{COUNTER %d}}SET {{AT}}@%s = {{STRING}}'%s';\n",
                                $g_counter,
                                $name,
                                $d_v
                            );
                        }
                        $g_counter ++;
                    }
                    $this->bind_params[$name] = $v;

                    $this->stmt[$statement_id]->bindValue(
                        $name,
                        $v,
                        is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR
                    );
                    $u[] = sprintf('{{AT}}@%s', $name);
                    $i ++;
                }
            }

            $this->stmt[$statement_id]->execute();
            $qt = $this->after($this->bind_params);
            $this->bind_params = [];
            $count = $this->stmt[$statement_id]->rowCount();

            if ($this->debug) {
                //デバッグ表示
                $using = count($u) ? sprintf('USING %s', implode(', ', $u)) : '';
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}EXECUTE {{STATEMENT}}%s %s;"
                    . " {{TIME}} (%s秒) [行数 %d]\n",
                    $g_counter,
                    $statement_id,
                    $using,
                    $qt,
                    $count
                );
                $g_counter ++;
            }
            return $count;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }

    /**
     * ステートメントの結合と抽出
     * （これは全件を一挙にフェッチするため負荷に注意する）
     * @param array $param 結合するパラメータ
     * @param string $statement_id プリペアドステートメントID
     * @param bool $class_flag クラスオブジェクトとして取得する場合TRUE
     * @return array|stdClass
     */
    public function bindSelect(
        array $param = [],
        string $statement_id = 'stmt',
        bool $class_flag = false
    ): array {
        try {
            $count = $this->bind($param, $statement_id);
            if ($count and $class_flag) {
                $this->stmt[$statement_id]->setFetchMode(\PDO::FETCH_CLASS,
                    'stdClass');
                $rows = $this->stmt[$statement_id]->fetchObject('stdClass');
            } else if ($count) {
                $this->stmt[$statement_id]->setFetchMode(\PDO::FETCH_ASSOC);
            }
            $rows = $this->stmt[$statement_id]->fetchAll();
            if ($rows === false) {
                throw new \Error('Fetch Error');
            }
            if ($this->debug) {
                $this->dbSelectDump($rows);
            }
            return $rows;
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }

    /**
     * プリペアドステートメントの解放
     * @global int $g_counter
     * @param string $statement_id プリペアドステートメントID
     * @throws \Error
     */
    public function stmtClose(string $statement_id = 'stmt'): void
    {
        try {
            if (!$this->stmt[$statement_id]) {
                throw new \Error('No Statement');
            }
            
            $this->stmt[$statement_id]->closeCursor();
            
            if ($this->debug) {
                global $g_counter;
                $this->disp_sql .= sprintf(
                    "{{COUNTER %d}}DEALLOCATE PREPARE {{STATEMENT}}%s;\n",
                    $g_counter,
                    $statement_id
                );
                $g_counter ++;
            }
            
            unset($this->do[$statement_id]);
            unset($this->name[$statement_id]);
        } catch (\PDOException $e) {
            $this->dbLog($e->getMessage());
        }
    }
}

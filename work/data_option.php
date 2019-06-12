<?php
/**
 * データ取得　共通モデル
 * 
 * @author   Sawada Hideshige
 * @version  1.0.0.0
 * @package  work
 * 

// NGRAMによるWHERE句検索例

$where = "WHERE MATCH(search_content,search_comment,search_name) AGAINST('+" . $full_text . "' IN BOOLEAN MODE)";

// NGRAM 設定例

DROP FUNCTION IF EXISTS `NGRAM`;

DELIMITER $$
CREATE FUNCTION `NGRAM`(`tText` TEXT CHARACTER SET utf8mb4, `n` INT)
    RETURNS TEXT CHARACTER SET utf8mb4
    DETERMINISTIC
BEGIN
    DECLARE tResult TEXT CHARACTER SET utf8mb4;
    DECLARE nLength INT;
    DECLARE nPosition INT;
    DECLARE tPart VARCHAR(16) CHARACTER SET utf8mb4;
    DECLARE wFlag INT;

    IF tText IS NULL THEN
        RETURN NULL;
    END IF;

    IF n = 0 THEN
        RETURN NULL;
    END IF;

    SET wFlag = 1;
    SET tResult = '';

    SET tText = TRIM(REPLACE(tText, '　', ''));
    SET nLength = CHAR_LENGTH(tText);

    SET nPosition = 1;
    WHILE nPosition <= nLength DO
        SET tPart = SUBSTR(tText, nPosition, 1);
        IF LENGTH(tPart) = 1 THEN
            SET tResult = CONCAT(tResult, tPart);
   SET wFlag = 0;
        ELSE
            SET tPart = TRIM(SUBSTR(tText, nPosition, n));
         IF CHAR_LENGTH(tPart) > 1 THEN
                if wFlag = 0 THEN
                    SET tResult = CONCAT(tResult, ' ', TRIM(SUBSTR(tText, nPosition - 1, n)), ' ');
                END IF;
             SET tResult = CONCAT(tResult, tPart, ' ');
    SET wFlag = 1;
         END IF;
        END IF;
        SET nPosition = nPosition + 1;
    END WHILE;

    RETURN TRIM(tResult);
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `search_ngram_insert`;

DELIMITER $$
CREATE TRIGGER `search_ngram_insert` BEFORE INSERT ON `table_name` FOR EACH ROW BEGIN

    IF NEW.search_flag = 1 AND NEW.no_flag = 0 THEN
        SET NEW.search_name = NGRAM(NEW.name, 2);
        SET NEW.search_content = NGRAM(NEW.content, 2);
        SET NEW.search_comment = NGRAM(NEW.comment, 2);
    END IF;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `search_ngram_update`;

DELIMITER $$
CREATE TRIGGER `search_ngram_update` BEFORE UPDATE ON `table_name` FOR EACH ROW BEGIN

    IF NEW.search_flag = 1 AND NEW.no_flag = 0 THEN
        SET NEW.search_name = NGRAM(NEW.name, 2);
        SET NEW.search_content = NGRAM(NEW.content, 2);
        SET NEW.search_comment = NGRAM(NEW.comment, 2);
    END IF;
END$$

DELIMITER ;

 */

namespace Yourname\Yourproject\Work;

use Php\Framework\Device as D;

class DataOption
{
    /**
     * 全文索引検索
     * @param string $word
     * @return string
     */
    public static function fullText(string $word): string
    {
        $full_text = '';
        if ($word) {
            $word = preg_replace('/　|,|、|\.|%|@|\+|\-|~|\(|\)|\*/',
                ' ', trim($word));
            $sp_word = explode(' ', $word);
            $words = self::fullTextSet($sp_word);
            $full_text = '"' . implode('" +"', $words) . '"';
        }
        return $full_text;
    }
    
    /**
     * 全文索引検索のセット
     * @param array $sp_word
     * @return array
     */
    private static function fullTextSet(array $sp_word): array
    {
        // 単体検索が無視される単語
        $ful_text_stop_words = [
            'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com',
            'de', 'en', 'for', 'from', 'how', 'i', 'in', 'is', 'it', 'la',
            'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what',
            'when', 'where', 'who', 'will', 'with', 'und', 'the', 'www'];
        
        $words = [];
        $i = 0;
        foreach ($sp_word as $v) {
            D\S::$dbs->prepare('ngrams', 'SELECT NGRAM(:ngram, 2) ngram');
            $param = ['ngram' => $v];
            D\S::$dbs->bind($param, 'ngrams');
            $res = D\S::$dbs->fetch('', 'ngrams');
            // MySQLで単体検索が無視される単語は次の単語につなげる
            $words[$i] = isset($words[$i]) ?
                $words[$i] . ' ' . $res['ngram'] : $res['ngram'];
            if (!in_array($res['ngram'], $ful_text_stop_words)) {
                $i ++;
            }
        }
        return $words;
    }
}

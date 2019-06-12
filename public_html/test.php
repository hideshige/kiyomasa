<?php
ini_set('display_errors', 'On');
/*
memcache get readtrough cas
*/
//----------------ReflectionClass Test----------------------------
//namespace A\B;
class Foo {}

$function = new \ReflectionClass('stdClass');

var_dump($function->inNamespace());
var_dump($function->getName());
var_dump($function->getNamespaceName());
var_dump($function->getShortName());

$function2 = new \ReflectionClass('A\\B\\Foo');

var_dump($function2->inNamespace());
var_dump($function2->getName());
var_dump($function2->getNamespaceName());
var_dump($function2->getShortName());

class Bar {
    const BAR_TEST = 5;
    private const BAR_TEST2 = 15;
    private $a = 10;
    public function test() {
        echo $this->a, PHP_EOL;
    }
    public function test2() {
        echo static::BAR_TEST2, PHP_EOL;
    }
}

\ReflectionClass::export('A\\B\\Bar', false); // クラス情報を画面に出力

$bar = new \ReflectionClass('A\\B\\Bar');
var_dump($bar->getConstants());
var_dump($bar->getEndLine());
var_dump($bar->getMethods());
var_dump($bar->getProperties());
$hoge = $bar->newInstance();
$hoge->test();
$hoge->test2();
echo Bar::BAR_TEST;
exit;
//----------------usort test----------------------------
function cmp($a, $b)
{
    if ($a === 5) {
        return -1;
    }
    // 宇宙船演算子を使うとかなり楽になった
    return $a <=> $b;
}
$a = array(3, 2, 5, 6, 1);
usort($a, 'cmp');
var_dump($a);
exit;
//----------------Regex Test----------------------------
preg_match('@^(?:http://)?([^/]+)@i', "http://www.php.net/index.html", $matches);
$host = $matches[1];
var_dump($matches);
preg_match('/[^.]+\.[^.]+$/', $host, $matches);
var_dump($matches);
echo "domain name is: {$matches[0]}\n";

$text = 'بيتر هو صبي.';
mb_regex_encoding('UTF-8');
if(mb_ereg('[\x{0600}-\x{06FF}]', $text)) {
    echo "Text has some arabic characters.\n";
}

$keywords = preg_split("/[\s,]+/", "hypertext language, programming");
var_dump($keywords);

$array = array("23.32","22","12.009","23.43.43");
var_dump(preg_grep("/^(\d+)?\.\d+$/", $array));

// エラーテスト
preg_match('/(?:\D+|<\d+>)*[!?]/', 'foobar foobar foobar');
var_dump(preg_last_error());

$subject = array('1', 'a', '2', 'b', '3', 'A', 'B', '4'); 
$pattern = array('/\d/', '/[a-z]/', '/[1a]/'); 
$replace = array('A:$0', 'B:$0', 'C:$0'); 

echo "preg_filter returns\n";
var_dump(preg_filter($pattern, $replace, $subject)); 

echo "preg_replace returns\n";
var_dump(preg_replace($pattern, $replace, $subject)); 
exit;
//----------------Closure Test----------------------------
// 無名関数
$piyo = 'ccc';
$closure = function ($hoge) use ($piyo) {return 'aaa' . $hoge . $piyo;};
echo "{$closure('bbb')}\n";
exit;
//----------------CreateFunction Test----------------------------
// 匿名関数（PHP7.2より非推奨）
$func = create_function('$hoge', 'return "aaa " . $hoge;');
echo "関数名 $func\n{$func('piyo')}\n";
exit;
//----------------JSON Test----------------------------
$date2 = ['aa/a' => ['c"c"c'=>"b'b'/b",['d\dd']]];
var_dump(json_encode($date2),json_decode(json_encode($date2),false,4));
json_decode('[]]');
echo json_last_error();
exit;
//----------------DateTime Test----------------------------
$d1 = new DateTime("2012-07-08 10:15:15.638276");
$d2 = new DateTime("2012-07-09 12:14:05.889342");
$diff = $d2->diff($d1);
var_dump($diff->format('%d %h:%i:%s')); 

$date = new DateTime('2000-01-20');
$date->sub($diff);
echo $date->format('Y-m-d H:i:s.u'), PHP_EOL;

$date2 = new DateTime('2000-01-20');
$date2->sub(new DateInterval('P7Y5M10D'));
echo $date2->format('Y-m-d'), PHP_EOL;
exit;

$begin = new DateTime('2012-08-01');
$end = new DateTime('2012-08-31');
$end->modify('+1 day');

$interval = new DateInterval('P2D');
$daterange = new DatePeriod($begin, $interval ,$end);

foreach($daterange as $date){
    echo $date->format('Y-m-d'), PHP_EOL;
}
exit;

$d = new DateTime(now, new DateTimeZone('Asia/Tokyo'));
echo $d->format('Y-m-d H:i:s.u'), PHP_EOL;
exit;

date_default_timezone_set('Asia/Tokyo');
var_dump(getdate(time()));
echo date('Y-m-d H:i:s', time()), PHP_EOL;
exit;
//----------------Iterator Test----------------------------
class myData implements IteratorAggregate {
    public $property1 = "Public property one";
    public $property2 = "Public property two";
    public $property3 = "Public property three";

    public function __construct() {
        $this->property4 = "last property";
    }

    public function getIterator() {
        return new ArrayIterator($this);
    }
}

$obj = new myData;

foreach($obj as $value) {
    echo $value, PHP_EOL;
}
exit;
class myIterator implements Iterator {
    private $position = 0;
    private $array = ['A', 'B', 'C'];  

    public function __construct() {
        $this->position = 0;
    }

    public function rewind() {
        echo __METHOD__, PHP_EOL;
        $this->position = 0;
    }

    public function current() {
        echo __METHOD__, PHP_EOL;
        return $this->array[$this->position];
    }

    public function key() {
        echo __METHOD__, PHP_EOL;
        return $this->position;
    }

    public function next() {
        echo __METHOD__, PHP_EOL;
        $this->position ++;
    }

    public function valid() {
        echo __METHOD__, PHP_EOL;
        return isset($this->array[$this->position]);
    }
}

$it = new myIterator;

foreach($it as $value) {
    echo $value, PHP_EOL;
}
exit;
//----------------Benchmark Test----------------------------
$time_a = microtime(true);
for ($i = 0; $i < 10000000; $i ++) {
    // 測定したいこと
    
}
$time_b = microtime(true);
$time = $time_b - $time_a;
var_dump($time);
exit;
//----------------SplFixedArray Test----------------------------
// 固定長の配列
$array = new SplFixedArray(5);

$array[1] = 2;
$array[4] = 'foo';
$array->setSize(10);
$array[9] = 'bar';
var_dump($array);
$array->setSize(2);
var_dump($array);
var_dump(isset($array['hoge']));

try {
    $a = $array['piyo'];
} catch (RuntimeException $re) {
    echo 'RuntimeException: ', $re->getMessage(), PHP_EOL;
}

try {
    var_dump($array[5]);
} catch (RuntimeException $re) {
    echo 'RuntimeException: ', $re->getMessage(), PHP_EOL;
}
exit;
//----------------SplHeap Test----------------------------
class HeapTest extends SplHeap
{
    public function compare($array1, $array2)
    {
        $values1 = array_values($array1);
        $values2 = array_values($array2);
        if ($values1[0] === $values2[0]) return 0;
        return $values1[0] < $values2[0] ? -1 : 1;
    }
}

$heap = new HeapTest();
$heap->insert(array('team1' => 15));
$heap->insert(array('team2' => 20));
$heap->insert(array('team3' => 11));
$heap->insert(array('team4' => 12));
$heap->top();
while ($heap->valid()) {
    list($team, $score) = each($heap->current());
    echo $team . ': ' . $score . PHP_EOL;
    $heap->next();
}
exit;

$h = new SplMinHeap();

$h->insert(2);
$h->insert(4);
$h->insert(6);
$h->insert(1);
$h->insert(2);
$h->insert(5);
$h->insert(9);
$h->insert(7);
for ($h->top(); $h->valid(); $h->next()) {
    echo $h->current(), ' ';
}
exit;
//----------------SplDoublyLinkedList Test----------------------------
// PUSH右に追加,POP右を削除,UNSHIFT左に追加,SHIFT左を削除
$splDoubleLinkedList = new SplDoublyLinkedList();
$splDoubleLinkedList->push('a');
$splDoubleLinkedList->push('3');
$splDoubleLinkedList->push('v');
$splDoubleLinkedList->push('1');
$splDoubleLinkedList->push('p');
$splDoubleLinkedList->shift();
$splDoubleLinkedList->unshift('Q');
$splDoubleLinkedList->pop();
// 先頭に
$splDoubleLinkedList->rewind();
// ノードの確認
while ($splDoubleLinkedList->valid()){
    // 現在のノードの表示
    echo $splDoubleLinkedList->current(), ' ';
    // 次のノードへ
    $splDoubleLinkedList->next();
}
exit;
// スタック
$stack = new SplStack();

$stack[] = 'apple';
$stack[] = 'orange';
$stack[] = 'melon';
$stack->push('banana');
$stack->pop();
$stack->add(1, 'grape');
var_dump($stack);

$stack->rewind();
while ($stack->valid()) {
    echo $stack->current(), ' ';
    $stack->next();
}
exit;
// キュー
$queue = new SplQueue();
$queue->enqueue('A');
$queue->enqueue('B');
$queue->dequeue();
$queue->enqueue('C');
var_dump($queue);
exit;
//----------------ArrayAccess Test----------------------------
class obj implements ArrayAccess {
    private $container = array();

    public function __construct() {
        $this->container = array(
            "one"   => 1,
            "two"   => 2,
            "three" => 3,
        );
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        echo 'bb';
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        return $this->container[$offset] ?? null;
    }
}

$obj = new obj;
var_dump($obj);
exit;
//----------------USER ERROR TEST----------------------------
set_error_handler(
    /**
     * エラー処理
     * @param int $no
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     * @throws \Error
     */
    function (int $no, string $message, string $file, int $line): void
    {
        switch ($no) {
            case E_ERROR: $type = 'エラー'; break;
            case E_WARNING : $type = '警告'; break;
            case E_NOTICE: $type = '注意'; break;
            case E_PARSE: $type = '構文不正'; break;
            case E_DEPRECATED: $type = '非推奨'; break;
            default: $type = '番号' . $no; break;
        }
        
        // ユーザエラーはユーザ用の例外へ
        if ($no === E_USER_ERROR or
            $no === E_USER_WARNING or $no === E_USER_NOTICE) {
            throw new Error($type . $message, 10);
        }
    }
);

try {
    if ($divisor == 0) {
        trigger_error('ゼロで割ることはできません');
    }
} catch (\Error $e) {
    var_dump($e->getMessage());
}
exit;
//----------------PDF TEST----------------------------
require('../.library/fpdf/mbfpdf.php');

$fpdf = new MBFPDF();
$fpdf->AddPage();
$fpdf->SetFont('Arial','B',10);
$fpdf->Text(10,10,'555');
$fpdf->Ln(10);
$fpdf->Cell(10,5,'666');
$fpdf->Cell(10,5,'777');
$fpdf->AddPage();
$fpdf->Cell(10,5,'888');
$fpdf->Output();
exit;
//---------------DOM TEST---------------------------------------
$doc = new DOMDocument();
$doc->Load('book.xml');
$element = $doc->createElement('row', 'AAA');
$element2 = $doc->createElement('row2', 'BBB');
$element3 = $doc->createElement('row3', 'CCC');
$element4 = $doc->createElement('row4', 'DDD');
$node = $doc->getElementsByTagName('tbody')->item(0);
$node->appendChild($element);
$node->appendChild($element2);
$node->replaceChild($element3, $element);
$node->removeChild($element2);
$node2 = $doc->getElementsByTagName('row3')->item(0);
$node2->appendChild($element4);
$element5 = $node2->cloneNode(true);
$node->appendChild($element5);
$doc->normalizeDocument();
//var_dump($doc->validate());
echo $doc->saveHTML();
exit;
//-----------------SimpleXML Test-------------------------------------
$doc = simplexml_load_file('book.xml');
$para = $doc->addChild('para');
$para->addChild('uho', 'uhoho');
$doc->asXML();
$sxi = new SimpleXmlIterator('book.xml', null, true);
$sxi->rewind();
$sxi->next();
var_dump($sxi->item(0)->key());
exit;
//----------------------XPath Test--------------------------------
$doc = new DOMDocument;

$doc->preserveWhiteSpace = false;

$doc->load('book.xml');

$xpath = new DOMXPath($doc);

// root 要素から開始します
$query = '//book/chapter/para/informaltable/tgroup/tbody/child::row/entry[. = "en"]';

$entries = $xpath->query($query);
foreach ($entries as $entry) {
    echo "Found {$entry->previousSibling->previousSibling->nodeValue}," .
         " by {$entry->previousSibling->nodeValue}\n";
}
exit;
//----------------------PDO TEST--------------------------------
$conn = new PDO('mysql:host=localhost;dbname=bts', 'bts', 'password');

function readData($dbh) {
    try {
        $sql = 'SELECT ac.account_name, ac.account_email, ap.account_point, ac.created_at, ap.created_at FROM bts_account ac
    LEFT JOIN bts_account_point ap ON ac.account_id = ap.account_id';
        $sql2 = ' WHERE ac.account_name LIKE ?';
        $q = $dbh->query($sql);
        $a = $q->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $dbh->prepare($sql . $sql2);
        $data = '%%';
        $stmt->bindParam(1, $data, PDO::PARAM_STR, 12);
        $stmt->execute();
        $stmt->debugDumpParams();

        /* カラム番号によってバインドする */
        $stmt->bindColumn(1, $account_name);
        $stmt->bindColumn(3, $account_point);

        /* カラム名によってバインドする */
        $stmt->bindColumn('account_email', $email);

        $test = new test();
        $stmt->setFetchMode(PDO::FETCH_INTO, $test);
        while ($row = $stmt->fetch()) {
            var_dump($row);
//            $data = $account_name . "\t" . $account_point . "\t" . $email . "\n";
//            print $data;
        }
    } catch (PDOException $e) {
        print $e->getMessage();
    }
}
readData($conn);

class test {
    public $account_name = 'oaoalal';
    public function __construct() {
        var_dump($this->account_name);
    }
}
exit;
//--------------------LDAP TEST----------------------------------
echo "<h3>LDAP query test</h3>";
//$ds=ldap_connect("localhost"); 
$ds=ldap_connect("LDAP://dc01.digitalhearts.com");  

if ($ds) { 
    $r=ldap_bind($ds, 'hideshige.sawada@digitalhearts.com', base64_decode('Vm13aW9wZWEyQDU='));

    $sr=ldap_search($ds, "ou=All Users, dc=digitalhearts, dc=com", '(|(employeeid=H*))');//['cn', 'mail', 'extensionattribute1']);

    echo ldap_count_entries($ds, $sr) . "件ヒット<br />";

    echo '<hr />';
    $info = ldap_get_entries($ds, $sr);
//    var_dump($info);
    for ($i=0; $i<$info["count"]; $i++) {
        echo "Name: " . mb_convert_encoding($info[$i]["cn"][0] ?? '', 'utf8', 'sjis') . "<br />";
        echo "名前: " . mb_convert_encoding($info[$i]["extensionattribute1"][0] ?? '', 'utf8', 'sjis') . "<br />";
        echo "メール: " . ($info[$i]["mail"][0] ?? '') . "<br /><hr />";
    }
    ldap_close($ds);
} else {
    echo "<h4>Unable to connect to LDAP server</h4>";
}
exit;
//-------------------Excel Test-----------------------------------
require '/var/www/html/vendor/autoload.php';

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//
//$spreadsheet = new Spreadsheet();
//$sheet = $spreadsheet->getActiveSheet();
//$sheet->setCellValue('A1', 'Hello World !');
//
//$source2 = "業務データ2.xlsx";
//$writer = new Xlsx($spreadsheet);
//$writer->save($source2);

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Writer\CSV as CSVWriter;

$source = "業務データ.xlsx";
$source2 = "業務データ2.xlsx";
$reader = new XlsxReader;

//シートを指定してインスタンス作成
//業務データ.xlsx内の"売上明細", "売上伝票","顧客名簿"のシートをメモリ上に取ってくるイメージ
$reader->setLoadSheetsOnly(['Sheet1']);
$spreadsheet = $reader->load($source);

//ロードしたシートの中から"売上明細"シートを$sheetとする
$sheet = $spreadsheet->getSheetByName('Sheet1');

//A1に"データ"というテキストを入れる
$sheet->setCellValue("A1", "データ");

//G1セルからP5セルの範囲を結合
$sheet->mergeCells('G1:P5');

//画像の貼り付け
$drawing = new PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
$drawing->setName('Logo');
$drawing->setDescription('Logo');
$drawing->setPath('img/bugbutton.png');
$drawing->setHeight(500);
$drawing->setCoordinates('B1');
$drawing->setWorksheet($sheet);

// Excel(.xlsx)として書き出す
$writer = new XlsxWriter($spreadsheet);
$writer->save($source2);

header(sprintf('Content-disposition: attachment; filename=%s', $source2));
header(sprintf('Content-type: application/octet-stream; name=%s', $source2));
include($source2);
exit;

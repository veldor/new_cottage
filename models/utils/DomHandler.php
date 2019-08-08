<?php


namespace app\models\utils;


use app\models\exceptions\ExceptionWithStatus;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use yii\base\Model;

class DomHandler extends Model
{
    public $dom; // DOM-модель
    public $xpath; // XPath-представление

    /**
     * DomHandler constructor.
     * @param $xml
     * @param array $config
     * @throws ExceptionWithStatus
     */
    public function __construct($xml, array $config = [])
    {
        parent::__construct($config);
        // в параметрах ищу строку с xml
        $this->dom = self::getDom($xml);
        $this->xpath = self::getXpath($this->dom);
    }

    /**
     * Загрузка структуры документа
     * @param $domString string
     * @return DOMDocument
     * @throws ExceptionWithStatus
     */
    public static function getDom(string $domString): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        try{
            $dom->loadXML($domString);
        }
        catch (Exception $e){
            throw new ExceptionWithStatus('Не удалось загрузить структуру документа.', 3);
        }
        return $dom;
    }

    /**
     * Создание XPath
     * @param $dom DOMDocument
     * @return DOMXpath
     * @throws ExceptionWithStatus
     */
    public static function getXpath(DOMDocument $dom): \DOMXpath
    {
        try{
            $xpath = new \DOMXpath($dom);
        }
        catch (Exception $e){
            throw new ExceptionWithStatus('Не удалось загрузить структуру документа.', 4);
        }
        return $xpath;
    }

    /**
     * Поиск выражения в XPath
     * @param $expr string
     * @return DOMNodeList|false
     */
    public function query($expr)
    {
        return $this->xpath->query($expr);
    }

    /**
     * Верну список всех аттрибутов элемента
     * @param $domElement \DOMElement
     * @return array
     */
    public static function getElemAttributes($domElement): array
    {
        $attributes = $domElement->attributes;
        $answer = [];
        foreach ($attributes as $attribute){
            $answer[$attribute->nodeName] = $attribute->nodeValue;
        }
        return $answer;
    }
}
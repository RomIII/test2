<?php
/**
 * Created by PhpStorm
 * User: Sergey Pokoev
 * www.pokoev.ru
 * @ Академия 1С-Битрикс - 2015
 * @ academy.1c-bitrix.ru
 */
use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Type;
use \Bitrix\Main\Application;
//
//use Bitrix\Main\HttpRequest
//include_once('');

class d7OrmCRUD extends CBitrixComponent
{

    protected $action = 'showCreateForm';
    protected $id = 0;
    protected $table = 'Article';

    /**
     * префикс для значений описывающий поле в форме
     * @var string
     */
    protected $prefix = 'rom';


    /**
     * проверяет подключение необходиимых модулей
     * @throws LoaderException
     */
    protected function checkModules()
    {
        if (!Main\Loader::includeModule('rom.city'))
            throw new Main\LoaderException(Loc::getMessage('ACADEMY_D7_MODULE_NOT_INSTALLED'));
    }

    protected function getAction()
    {
        $request = Application::getInstance()->getContext()->getRequest();


        if (null !== $request->getQuery("id") && $request->getQuery("id") != '') {
            $this->action = 'showEditForm';
            $this->id = (int)$request->getQuery("id");
        }

        if (null !== $request->getPost("ID") && $request->getPost("ID") != '') {
            $this->action = 'updateData';
            $this->request = $request->getPostList();
        }

//        echo $this->action;

        $this->arResult['ACTION'] = $this->action;

    }

    protected function getResult()
    {
        if ($this->action == 'showCreateForm') {
            $this->createFieldArray();
        } elseif ($this->action == 'showEditForm') {
            $this->createFieldArray();
            $this->addValueToField($this->id);
        } elseif ($this->action == 'updateData') {
            $this->updateData();
        }
        //echo $this->action;
    }


    /**
     * Обновляем данные
     */
    protected function updateData()
    {
        $this->arResult['REQUEST'] = $this->request;
        $ID = $this->request['ID'];
        $arData=$this->accessProtected($this->request, 'values');
        unset($arData['ID']);
        $result = \Rom\City\Entity\ArticleTable::update($ID, $arData);

        if ($result->isSuccess())
        {
            $id = $result->getId();
            $this->arResult='Запись изменена с id: '.$id;
        }
        else
        {
            $error=$result->getErrorMessages();
            $this->arResult='Произошла ошибка при изменении: <pre>'.var_export($error,true).'</pre>';
        }
    }

    /**
     * собираем массив данных для отображения полей формы $arResult['FORM']
     */
    protected function createFieldArray()
    {
        $formaFields = \Rom\City\Entity\ArticleTable::getMap();
        foreach ($formaFields as $obField) {
            $strFieldName = $this->accessProtected($obField, 'name');
            $arField = $this->accessProtected($obField, 'initialParameters');
            foreach ($arField as $key => $val) {
                if (strpos($key, 'rom_') === false) {
                    unset($arField[$key]);
                } else {
                    $newKey = strtoupper(str_replace('rom_', '', $key));
                    $arField[$newKey] = $val;
                    unset($arField[$key]);
                }
            }
            if (isset($arField['TYPE'])) {
                $this->arResult['FORM'][$strFieldName] = $arField;
            }
        }
    }

    /**
     * добавление текущих значений для редактирования в массив $arResult['FORM']
     * @param $ID
     */
    protected function addValueToField($ID)
    {
        $book = \Rom\City\Entity\ArticleTable::getById($ID)->fetch();
        //foreach($this->arParams as $key=>$val){}
        foreach ($this->arResult['FORM'] as $key => $val) {
            $this->arResult['FORM'][$key]['VALUE'] = $book[$key];
        }
        $this->arResult['FORM']['ID'] = array('TYPE' => 'hidden', 'VALUE' => $ID);
    }

    /**
     * Функция для получения доступа к protected свойствам объекта
     * @param $obj
     * @param $prop
     * @return mixed
     */
    function accessProtected($obj, $prop)
    {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    } 


    public function executeComponent()
    {
        $this->includeComponentLang('class.php');

        $this->checkModules();

        $this->getAction();

        $this->getResult();

         $this->includeComponentTemplate();
    }
}

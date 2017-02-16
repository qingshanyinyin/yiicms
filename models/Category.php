<?php

namespace app\models;
use app\components\AppActiveRecord;
use Yii;
use app\models\Content;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "category".
 *
 * @property integer $id
 * @property string $name
 * @property integer $pid
 * @property string $path
 * @property integer $type
 * @property integer $created_at
 * @property integer $updated_at
 * @property array $types
 *
 * @const TYPE_NEWS \app\models\Content::TYPE_NEWS
 * @const TYPE_PRODUCTS \app\models\Content::TYPE_PRODUCTS
 * @const TYPE_PHOTO \app\models\Content::TYPE_PHOTO
 */
class Category extends AppActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'category';
    }
    /**
     * 获取可能的全部父类
     */
    public function getPossibleParentArr()
    {
        $list = self::find()
            ->where(['type'=>$this->type])
            ->andFilterWhere(['<>', 'id', $this->id])
            ->asArray()
            ->all();
        array_unshift($list, self::$topCategory);
//        print_r($list);
        return $list;
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        $parent = $this->getParent();
        if($parent instanceof static){
            if(empty($parent->path)){
                $this->path = $parent->id;
            } else {
                $this->path = trim($parent->path, '/') . '/' . $parent->id;
            }
        }else{
            $this->path = '';
        }
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * 获取完整的父类名称
     * @return null|string
     */
    public function getFullParentName()
    {
        if(empty($this->path)){
            return null;
        }
        $list = ArrayHelper::toArray($this->getFullParent());
        return implode('/',array_column($list, 'name'));
    }

    /**
     * 顶级分类信息
     * @var array $topCategory
     */
    static public $topCategory = [
        'id'=>0,
        'name'=>'作为一级分类',
        'pid'=>null,
        'path'=>'',
    ];

    /**
     * 获取完整的分类名称
     * @return string
     */
    public function getFullName()
    {
        $baseName = $this->getFullParentName();
        return $baseName?$baseName.'/'.$this->name:$this->name;
    }
    /** @var  array */
    private $_parents;
    /**
     * 获取全部完整父类
     */
    public function getFullParent()
    {
        if(empty($this->path)){
            return null;
        }
        if(empty($this->_parents['fullParent']) || empty($this->_parents['path']) || $this->_parents['path']!=$this->path){
            $pids = explode('/',$this->path);
            $this->_parents['fullParent'] = self::find()->andFilterWhere(['in', 'id', $pids])->orderBy('path')->all();
            if($this->_parents['fullParent']) {
                $this->_parents['parent'] = end($this->_parents['fullParent']);
            }
        }
        return $this->_parents['fullParent'];
    }

    /**
     * 获取父类名称
     * @return array
     */
    public function getParentName()
    {
        if(empty($this->pid)){
            return self::$topCategory['name'];
        }
        $category = $this->getParent();

        return empty($category)?null:$category['name'];
    }

    /**
     * 获取父类
     * @return static|array
     */
    public function getParent()
    {
        if(empty($this->pid)){
            return self::$topCategory;
        }
        if(empty($this->_parents['parent']) || $this->_parents['parent']->id!=$this->pid){
            $this->_parents['parent'] = self::findOne($this->pid);
        }
        return $this->_parents['parent'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name','pid', 'type'], 'required'],
            [['pid', 'type'], 'integer'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '分类名',
            'fullName' => '完整分类名',
            'pid' => '父类',
            'type' => '分类类型',
            'created_at' => '创建时间',
            'updated_at' => '最后修改',
        ];
    }

    /**
     * 分类类型
     * @return array
     */
    public static function getTypes()
    {
        return ArrayHelper::merge(Content::$types, Ad::$types);
    }

    /**
     * 类型文字
     * @return mixed|null
     */
    public function getTypeText()
    {
        $types =  self::getTypes();
        return isset($types[$this->type])?$types[$this->type]:null;
    }
}

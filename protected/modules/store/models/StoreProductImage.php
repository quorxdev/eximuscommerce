
<?php

/**
 * This is the model class for table "StoreProductImage".
 *
 * The followings are the available columns in table 'StoreProductImage':
 * @property integer $id
 * @property integer $product_id
 * @property string $name
 * @property integer $is_main
 * @property integer $uploaded_by
 * @property string $date_uploaded
 */
class StoreProductImage extends BaseModel
{
    /**
     * Returns the static model of the specified AR class.
     * @return StoreProductImage the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'StoreProductImage';
    }

    public function relations()
    {
        return array(
            'author'=>array(self::BELONGS_TO, 'User', 'uploaded_by'),
        );
    }

    public function defaultScope()
    {
        return array(
            'order'=>'date_uploaded DESC',
        );
    }

    /**
     * @return string Url to image file
     */
    public function getUrl($random = false)
    {
        if ($random === true)
            return Yii::app()->params['storeImages']['url'].$this->name.'?'.rand(1,10000);
        return Yii::app()->params['storeImages']['url'].$this->name;
    }

    public function attributeLabels()
    {
        return array(
            'product_id'=>Yii::t('StoreModule.admin', 'Продукт'),
            'name'=>Yii::t('StoreModule.admin', 'Имя файла'),
            'is_main'=>Yii::t('StoreModule.admin', 'Главное'),
            'author'=>Yii::t('StoreModule.admin', 'Автор'),
            'uploaded_by'=>Yii::t('StoreModule.admin', 'Автор'),
            'date_uploaded'=>Yii::t('StoreModule.admin', 'Дата загрузки'),
        );
    }

    /**
     * Delete file, etc...
     */
    public function afterDelete()
    {
        // Delete file
        $fullPath = Yii::getPathOfAlias(Yii::app()->params['storeImages']['path']).'/'.$this->name;
        if (file_exists($fullPath))
            unlink($fullPath);

        // If main image was deleted
        if ($this->is_main)
        {
            // Get first image and set it as main
            $model = StoreProductImage::model()->find();
            if ($model)
            {
                $model->is_main = 1;
                $model->save(false);
            }
        }

        return parent::afterDelete();
    }

}
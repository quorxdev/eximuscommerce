<?php

/**
 * This is the model class for table "StoreProduct".
 *
 * The followings are the available columns in table 'StoreProduct':
 * @property integer $id
 * @property string $name
 * @property string $url
 * @property double $price
 * @property boolean $is_active
 * @property string $short_description
 * @property string $full_description
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $layout
 * @property string $view
 * @property string $sku
 * @property string $quantity
 * @property string $auto_decrease_quantity
 * @property string $availability
 * @property string $created
 * @property string $updated
 */
class StoreProduct extends BaseModel
{

	/**
	 * @var null Id if product to exclude from search
	 */
	public $exclude = null;

	/**
	 * @var array of related products
	 */
	private $_related;

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className
	 * @return StoreProduct the static model class
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
		return 'StoreProduct';
	}

	public function scopes()
	{
		return array(
			'active'=>array(
				'condition'=>'is_active=1',
			),
		);
	}

	/**
	 * Find product by url.
	 * Scope.
	 * @param string Product url
	 * @return StoreProduct
	 */
	public function withUrl($url)
	{
		$this->getDbCriteria()->mergeWith(array(
			'condition'=>'url=:url',
			'params'=>array(':url'=>$url)
		));
		return $this;
	}


	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('price', 'commaToDot'),
			array('price', 'numerical'),
			array('is_active', 'boolean'),
			array('quantity, availability', 'numerical', 'integerOnly'=>true),
			array('name, price', 'required'),
			array('url', 'LocalUrlValidator'),
			array('name, url, meta_title, meta_keywords, meta_description, layout, view, sku', 'length', 'max'=>255),
			array('short_description, full_description, auto_decrease_quantity', 'type'),
			// Search
			array('id, name, url, price, short_description, full_description, created, updated', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * Replaces comma to dot
	 * @param $attr
	 */
	public function commaToDot($attr)
	{
		$this->$attr = str_replace(',','.', $this->$attr);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'images'=>array(self::HAS_MANY, 'StoreProductImage', 'product_id'),
			'related'=>array(self::HAS_MANY, 'StoreRelatedProduct', 'product_id'),
			'relatedProducts'=>array(self::HAS_MANY, 'StoreProduct', array('related_id'=>'id'), 'through'=>'related'),
			'categorization'=>array(self::HAS_MANY, 'StoreProductCategoryRef', 'product'),
			'categories'=>array(self::HAS_MANY, 'StoreCategory',array('category'=>'id'), 'through'=>'categorization'),
			'mainCategory'=>array(self::HAS_ONE, 'StoreCategory', array('category'=>'id'), 'through'=>'categorization', 'condition'=>'categorization.is_main = 1')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'url' => 'Url',
			'price' => 'Price',
			'short_description' => 'Short Description',
			'full_description' => 'Full Description',
			'created' => 'Created',
			'updated' => 'Updated',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('price',$this->price);
		$criteria->compare('short_description',$this->short_description,true);
		$criteria->compare('full_description',$this->full_description,true);
		$criteria->compare('sku',$this->sku,true);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('updated',$this->updated,true);

		// Id of product to exclude from search
		if($this->exclude)
			$criteria->compare('t.id !', array(':id'=>$this->exclude));

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
				'defaultOrder'=>'t.created DESC'
			),
			'pagination'=>array(
				'pageSize'=>20,
			)
		));
	}

	/**
	 * Save related products. Notice, related product will be saved after save() method called.
	 * @param array $ids Array of related products
	 */
	public function setRelatedProducts($ids = array())
	{
		$this->_related = $ids;
	}

	public function beforeSave()
	{
		if (empty($this->url))
		{
			// Create slug
			Yii::import('ext.SlugHelper.SlugHelper');
			$this->url = SlugHelper::run($this->name);
		}

		// Check if url available
		if($this->isNewRecord)
		{
			$test = StoreProduct::model()
				->withUrl($this->url)
				->count();
		}
		else
		{
			$test = StoreProduct::model()
				->withUrl($this->url)
				->count('id!=:id', array(':id'=>$this->id));
		}

		// Create unique url
		if ($test > 0)
			$this->url .= '-'.date('YmdHis');

		return parent::beforeSave();
	}

	public function afterSave()
	{
		// Process related products
		if($this->_related !== null)
		{
			$this->clearRelatedProducts();

			foreach($this->_related as $id)
			{
				$related = new StoreRelatedProduct;
				$related->product_id = $this->id;
				$related->related_id = $id;
				$related->save();
			}
		}

		return parent::afterSave();
	}

	public function afterDelete()
	{
		// Delete related products
		$this->clearRelatedProducts();
		StoreRelatedProduct::model()->deleteAll('related_id=:id', array('id'=>$this->id));

		// Delete categorization
		StoreProductCategoryRef::model()->deleteAllByAttributes(array(
			'product'=>$this->id
		));

		// Delete images
		$images = $this->images;
		if(!empty($images))
		{
			foreach ($images as $image)
				$image->delete();
		}

		return parent::afterDelete();
	}

	/**
	 * Clear all related products
	 */
	private function clearRelatedProducts()
	{
		StoreRelatedProduct::model()->deleteAll('product_id=:id', array('id'=>$this->id));
	}

	/**
	 * @return array
	 */
	public function getAvailabilityItems()
	{
		return array(
			1=>Yii::t('StoreModule.core', 'Есть на складе'),
			2=>Yii::t('StoreModule.core', 'Нет на складе'),
		);
	}

	/**
	 * @param array $categories
	 */
	public function setCategories(array $categories, $main_category)
	{
		$dontDelete = array();

		if(!StoreCategory::model()->countByAttributes(array('id'=>$main_category)))
			$main_category = 1;

		if(!in_array($main_category, $categories))
			array_push($categories, $main_category);

		foreach ($categories as $c)
		{
			$count = StoreProductCategoryRef::model()->countByAttributes(array(
				'category'=>$c,
				'product'=>$this->id
			));

			if($count == 0)
			{
				$record = new StoreProductCategoryRef;
				$record->category = (int)$c;
				$record->product = $this->id;
				$record->save(false);
			}

			$dontDelete[] = $c;
		}

		// Clear main category
		StoreProductCategoryRef::model()->updateAll(array(
			'is_main'=>0
		), 'product=:p', array(':p'=>$this->id));

		// Set main category
		StoreProductCategoryRef::model()->updateAll(array(
			'is_main'=>1
		), 'product=:p AND category=:c ', array(':p'=>$this->id,':c'=>$main_category));

		// Delete not used relations
		if(sizeof($dontDelete) > 0)
		{
			$cr = new CDbCriteria;
			$cr->addNotInCondition('category', $dontDelete);

			StoreProductCategoryRef::model()->deleteAllByAttributes(array(
				'product'=>$this->id,
			), $cr);
		}
		else
		{
			// Delete all relations
			StoreProductCategoryRef::model()->deleteAllByAttributes(array(
				'product'=>$this->id,
			));
		}
	}
}